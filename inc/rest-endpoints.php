<?php
// Register REST API endpoints
add_action('rest_api_init', function () {
    register_rest_route('scss-playground/v1', '/files', array(
        'methods' => 'GET',
        'callback' => 'get_scss_files',
        'permission_callback' => '__return_true', // Allow public access
    ));
    register_rest_route('scss-playground/v1', '/file', array(
        'methods' => 'POST',
        'callback' => 'save_scss_file',
        'permission_callback' => '__return_true', // Allow public access
    ));
    register_rest_route('scss-playground/v1', '/file/(?P<filename>[^/]+)', array(
        'methods' => 'GET',
        'callback' => 'get_scss_file',
        'permission_callback' => '__return_true', // Allow public access
    ));
    register_rest_route('scss-playground/v1', '/file/(?P<filename>[^/]+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_file', // Change this to match the function name
        'permission_callback' => '__return_true', // Allow public access
    ));
    register_rest_route('scss-playground/v1', '/css-file', array(
        'methods' => 'POST',
        'callback' => 'save_css_file',
        'permission_callback' => '__return_true', // Allow public access
    ));
});
