<?php

namespace SCSSPlayground\App;

class Init
{
    public static function register()
    {
        // Ajax handlers
        add_action('wp_ajax_get_scss_files', array(__NAMESPACE__ . '\\AjaxHandlers', 'get_scss_files'));
        add_action('wp_ajax_load_scss_file', array(__NAMESPACE__ . '\\AjaxHandlers', 'load_scss_file'));
        add_action('wp_ajax_save_scss_file', array(__NAMESPACE__ . '\\AjaxHandlers', 'save_scss_file'));

        // REST API endpoints
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
    }

    public static function register_rest_routes()
    {
        register_rest_route('scss-playground/v1', '/files', array(
            'methods' => 'GET',
            'callback' => array(__NAMESPACE__ . '\\RestHandlers', 'get_scss_files_rest'),
        ));

        register_rest_route('scss-playground/v1', '/save', array(
            'methods' => 'POST',
            'callback' => array(__NAMESPACE__ . '\\RestHandlers', 'save_scss_file_rest'),
        ));
    }
}
