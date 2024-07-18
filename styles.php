<?php

/**
 * Plugin Name: SCSS Playground
 * Description: A plugin to write and save SCSS files.
 * Version: 1.0.0
 * Author: Your Name
 */

defined('ABSPATH') || exit;

add_action('admin_menu', 'scss_playground_menu');

function scss_playground_menu()
{
    add_menu_page('Styles', 'Styles', 'manage_options', 'scss-playground', 'scss_playground_page', 'dashicons-editor-code', 100);
}

function scss_playground_page()
{
    echo '<div id="scss-playground-root"></div>';
}

add_action('admin_enqueue_scripts', 'scss_playground_enqueue_scripts');

function scss_playground_enqueue_scripts($hook)
{
    if ($hook !== 'toplevel_page_scss-playground') {
        return;
    }

    wp_enqueue_script(
        'scss-playground-script',
        plugins_url('build/index.js', __FILE__),
        ['wp-element'],
        filemtime(plugin_dir_path(__FILE__) . 'build/index.js'),
        true
    );

    wp_localize_script('scss-playground-script', 'scssPlayground', [
        'ajax_url' => home_url(), // Use home_url() to build the base URL for the REST API
    ]);
}

add_action('rest_api_init', function () {
    register_rest_route('scss-playground/v1', '/load-files', [
        'methods' => 'GET',
        'callback' => 'load_scss_files',
        'permission_callback' => 'scss_playground_permissions_check',
    ]);

    register_rest_route('scss-playground/v1', '/save-file', [
        'methods' => 'POST',
        'callback' => 'save_scss_file',
        'permission_callback' => 'scss_playground_permissions_check',
    ]);
});

function scss_playground_permissions_check()
{
    // Check if user is logged in
    if (!is_user_logged_in()) {
        error_log('User is not logged in');
        return new WP_Error('rest_forbidden', esc_html__('You are not logged in.', 'my-text-domain'), ['status' => 401]);
    }

    // Check if user has edit_posts capability
    if (!current_user_can('edit_posts')) {
        error_log('User does not have the required permissions');
        return new WP_Error('rest_forbidden', esc_html__('You do not have sufficient permissions.', 'my-text-domain'), ['status' => 403]);
    }

    // All checks passed
    return true;
}

function load_scss_files()
{
    $files = get_scss_files();

    if (is_wp_error($files)) {
        return new WP_Error('load_files_error', 'Error loading files', ['status' => 500]);
    }

    return new WP_REST_Response($files, 200);
}

function get_scss_files()
{
    $directory = plugin_dir_path(__FILE__) . 'scss-files';
    $files = scandir($directory);

    if ($files === false) {
        return new WP_Error('file_error', 'Unable to read directory');
    }

    $files = array_filter($files, function ($file) use ($directory) {
        return is_file($directory . '/' . $file);
    });

    return array_values($files);
}

function save_scss_file(WP_REST_Request $request)
{
    $file_name = sanitize_text_field($request->get_param('file_name'));
    $content = sanitize_textarea_field($request->get_param('content'));

    $file_path = plugin_dir_path(__FILE__) . 'scss-files/' . $file_name;

    if (file_put_contents($file_path, $content) === false) {
        return new WP_Error('save_file_error', 'Error saving file', ['status' => 500]);
    }

    return new WP_REST_Response(['message' => 'File saved successfully'], 200);
}



add_action('rest_api_init', function () {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function ($value) {
        header('Access-Control-Allow-Origin: ' . get_home_url());
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Credentials: true');
        return $value;
    });
}, 15);
