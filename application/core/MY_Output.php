<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PHP Codeigniter Simplicity
 *
 *
 * Copyright (C) 2013  John Skoumbourdis.
 *
 * GROCERY CRUD LICENSE
 *
 * Codeigniter Simplicity is released with dual licensing, using the GPL v3 and the MIT license.
 * You don't have to do anything special to choose one license or the other and you don't have to notify anyone which license you are using.
 * Please see the corresponding license file for details of these licenses.
 * You are free to use, modify and distribute this software, but all copyright information must remain.
 *
 * @package    	Codeigniter Simplicity
 * @copyright  	Copyright (c) 2013, John Skoumbourdis
 * @license    	https://github.com/scoumbourdis/grocery-crud/blob/master/license-grocery-crud.txt
 * @version    	0.6
 * @author     	John Skoumbourdis <scoumbourdisj@gmail.com>
 */
class MY_Output extends CI_Output {

    const OUTPUT_MODE_NORMAL = 10;
    const OUTPUT_MODE_TEMPLATE = 11;
    const TEMPLATE_ROOT = "themes/";

    /**
     * @var array Status message.
     * The 'type' stores the type of message.
     * The 'message' stores the message itself.
     */
    protected static $messages;
    private $_title = "";
    private $_charset = "utf-8";
    private $_language = "en-us";
    private $_canonical = "";
    private $_meta = array("keywords" => array(), "description" => null);
    private $_rdf = array("keywords" => array(), "description" => null);
    private $_template = null;
    private $_mode = self::OUTPUT_MODE_NORMAL;
    private $_messages = array("error" => "", "info" => "", "debug" => "");
    private $_output_data = array();

    /** @var {CI} An instance of the CI super object. */
    private static $ci;

    /**
     * This constructor is here purely for CI's benefit, as this is a static class.
     *
     * @return void
     */
    public function __construct() {
        if (function_exists("get_instance") && class_exists("CI_Controller")) {
            self::$ci = & get_instance();
        }
    }

    /**
     * Set the  template that should be contain the output <br /><em><b>Note:</b> This method set the output mode to MY_Output::OUTPUT_MODE_TEMPLATE</em>
     *
     * @uses MY_Output::set_mode()
     * @param string $template_view
     * @return void
     */
    function set_template($template_view) {
        get_instance()->load->library('DX_Auth');
        $this->set_mode(self::OUTPUT_MODE_TEMPLATE);
        $template_view = str_replace(".php", "", $template_view);
        $this->_template = self::TEMPLATE_ROOT . $template_view;
    }

    /*     * set_mode alias
     *
     * Enter description here ...
     */

    function unset_template() {
        $this->_template = null;
        $this->set_mode(self::OUTPUT_MODE_NORMAL);
    }

    public function set_common_meta($title, $description, $keywords) {
        $this->set_meta("description", $description);
        $this->set_meta("keywords", $keywords);
        $this->set_title($title);
    }

    /**
     * Sets the way that the final output should be handled.<p>Accepts two possible values 	MY_Output::OUTPUT_MODE_NORMAL for direct output
     * or MY_Output::OUTPUT_MODE_TEMPLATE for displaying the output contained in the specified template.</p>
     *
     * @throws Exception when the given mode hasn't defined.
     * @param integer $mode one of the constants MY_Output::OUTPUT_MODE_NORMAL or MY_Output::OUTPUT_MODE_TEMPLATE
     * @return void
     */
    function set_mode($mode) {

        switch ($mode) {
            case self::OUTPUT_MODE_NORMAL:
            case self::OUTPUT_MODE_TEMPLATE:
                $this->_mode = $mode;
                break;
            default:
                throw new Exception(get_instance()->lang->line("Unknown output mode."));
        }

        return;
    }

    /**
     * Set the title of a page, it works only with MY_Output::OUTPUT_MODE_TEMPLATE
     *
     *
     * @param string $title
     * @return void
     */
    function set_title($title) {
        $this->_title = $title;
    }

    /**
     * Append the given string at the end of the current page title
     *
     * @param string $title
     * @return void
     */
    function append_title($title) {
        $this->_title .= " - {$title}";
    }

    /**
     * Prepend the given string at the bigining of the curent title.
     *
     * @param string $title
     * @return void
     */
    function prepend_title($title) {
        $this->_title = "{$title} - {$this->_title}";
    }

    /**
     * Sets a status message (for displaying small success/error messages).
     *
     * This function is used in place of the session->flashdata function to allow
     * the message to show up without requiring a page refresh.
     *
     * @param string $message The text of the message.
     * @param string $type    The type of message, usually added as the value of
     * the class attribute on the message's container.
     *
     * @return void
     */
    public static function set_message($message = '', $type = 'info', $flashdata = TRUE) {

        if (empty($message)) {
            return;
        }
        if ($flashdata) {
            if (!isset(self::$ci) && function_exists("get_instance") && class_exists("CI_Controller")) {
                self::$ci = & get_instance();
            }
            if (isset($_SESSION['message']) && !empty($_SESSION['message'])) {
                $messages = array_unique(unserialize($_SESSION['message']));
            }
            $messages[] = "{$type}::{$message}";
            $messages = serialize($messages);

            if (isset(self::$ci->session)) {
                self::$ci->session->set_flashdata('message', $messages);
            }
        }

        self::$messages[] = array('type' => $type, 'message' => $message);
    }

    /**
     * (non-PHPdoc)
     * @see system/libraries/CI_Output#_display($output)
     */
    function _display($output = '') {

        if ($output == '')
            $output = $this->get_output();

        switch ($this->_mode) {
            case self::OUTPUT_MODE_TEMPLATE:
                $output = $this->get_template_output($output);
                break;
            case self::OUTPUT_MODE_NORMAL:
            default:
                $output = $output;
                break;
        }

        parent::_display($output);
    }

    function set_output_data($varname, $value) {
        $this->_output_data[$varname] = $value;
    }

    private function get_template_output($output) {

        if (function_exists("get_instance") && class_exists("CI_Controller")) {
            self::$ci = get_instance();

            $inline = self::$ci->load->get_inline_scripting();

            if ($inline["infile"] != "") {
                $checksum = md5($inline["infile"], false);
                self::$ci->load->driver('cache');
                self::$ci->cache->memcached->save($checksum, $inline["infile"], 5 * 60);
                self::$ci->load->js(site_url("content/js/{$checksum}.js"), true);
            }

            if (strlen($inline['stripped'])) {
                $inline['unstripped'] .= "\r\n\r\n<script type=\"text/javascript\">{$inline['stripped']}</script>";
            }

            $data = array();

            $css_files = self::$ci->load->get_css_files();
            $js_files = self::$ci->load->get_js_files();

            $cached_js_files = self::$ci->load->get_cached_js_files();
            if (!empty($cached_js_files)) {
                $cached_js_files_string = '';
                foreach ($cached_js_files as $cahed_js_file) {
                    $cached_js_files_string .= str_replace("\t", "", file_get_contents($cahed_js_file, FILE_USE_INCLUDE_PATH));
                }

                $cache_file_name = 'cache_' . md5(serialize($cached_js_files)) . '.js';
                $cache_file_path = 'assets/themes/default/js/' . $cache_file_name;

                $fh = fopen($cache_file_path, 'w') or die("can't open file");
                fwrite($fh, $cached_js_files_string);
                fclose($fh);

                $js_files[] = base_url() . $cache_file_path;
            }

            if (is_array($this->_meta["keywords"])) {
                $this->_meta["keywords"] = implode(" ,", $this->_meta["keywords"]);
            }

            $data["output"] = $output;
            $data["messages"] = $this->_messages;
            $data["modules"] = self::$ci->load->get_sections();
            $data["title"] = $this->_title;
            $data["meta"] = $this->_meta;
            $data["language"] = $this->_language;
            $data["rdf"] = $this->_rdf;
            $data["charset"] = $this->_charset;
            $data["js"] = $js_files;
            $data["css"] = $css_files;
            $data["inline_scripting"] = $inline['unstripped'];
            $data["canonical"] = $this->_canonical;
            $data["ci"] = &get_instance();

            $data = array_merge($data, $this->_output_data);

            $output = self::$ci->load->view($this->_template, $data, true);
        }

        return $output;
    }

    /**
     * Adds meta tags.
     *
     * @access public
     * @param string $name the name of the meta tag
     * @param string $content the content of the meta tag
     * @return bool
     */
    public function set_meta($name, $content) {
        $this->_meta[$name] = $content;
        return true;
    }

    public function set_canonical($url) {
        $this->_canonical = $url;
    }

    /**
     * Displays a status message (small success/error messages).
     *
     * If data exists in 'message' session flashdata, that will override any other
     * messages. Renders the message based on the template provided in the config
     * file ('template.message_template').
     *
     * @param string $message A string to be the message. (Optional) If included, will override any other messages in the system.
     * @param string $type    The class to attached to the div. (i.e. 'information', 'attention', 'error', 'success')
     *
     * @return string A string with the results of inserting the message into the message template.
     */
    public static function message() {

        self::$ci = & get_instance();
        // Does session data exist?
        if (class_exists('CI_Session', false)) {
            if (!isset(self::$ci)) {
                self::$ci = & get_instance();
            }
            $flash_message = self::$ci->session->flashdata('message');
            $messages = unserialize($flash_message);
            if (isset($messages['0'])) {
                // Split out the message parts
                foreach ($messages as $msg) {
                    $temp_message = explode('::', $msg);
                    $type[] = $temp_message[0];
                    $message[] = $temp_message[1];

                    unset($temp_message);
                }
            }
        }

        // If message is empty, check the $message property.
        if (!isset($messages['0']) && isset(self::$messages)) {

            foreach (self::$messages as $msg) {
                if (empty($msg['message'])) {
                    return '';
                }

                $message[] = $msg['message'];
                $type[] = $msg['type'];
            }
        }
        // Get the message template and replace the placeholders.
        $template = '';
        if (isset($message) && is_array($message)) {
            for ($index = 0; $index < count($message); $index++) {
                $template .= str_replace(
                                array('{type}', '{message}'), array($type[$index],
                            $message[$index]), self::$ci->config->item('message_template')
                        ) . '<br>';
            }
        }
        // Clear the session data to prevent extra messages. (This was a very rare
        // occurence, but clearing should resolve the problem.)
        if (class_exists('CI_Session', false)) {
            self::$ci->session->set_flashdata('message', '');
        }

        return $template;
    }

    /*
      Created on : Nov 4, 2015, 11:34:57 AM
      Author     : Waleed Akleh
     */

    public function end_method($data, $view = NULL, $title = NULL) {
        if (function_exists("get_instance") && class_exists("CI_Controller")) {
            self::$ci = get_instance();
        }
        if (self::$ci->input->is_ajax_request()) {
            $data['message'] = self::message();
            echo json_encode($data);
            exit;
        } else {
            $data['auth_message'] = self::message();
            self::$ci->load->view($view, $data);
            self::$ci->output->set_title(self::$ci->lang->line($title));
        }
    }

}
