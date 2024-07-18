<?php
/*
Plugin Name: SCSS Playground
Description: A plugin to compile SCSS to CSS in a settings page.
Version: 1.0
Author: Your Name
*/

// Autoload files
require_once __DIR__ . '/autoload.php';

// Import the Init class
use SCSSPlayground\App\Init;

// Initialize the plugin
Init::register();

// Enqueue scripts
function scss_playground_enqueue_scripts()
{
    $script_asset_path = plugin_dir_path(__FILE__) . 'build/index.asset.php';
    $script_asset = require($script_asset_path);

    wp_enqueue_script(
        'scss-playground-script',
        plugins_url('build/index.js', __FILE__),
        $script_asset['dependencies'],
        $script_asset['version'],
        true
    );

    wp_localize_script('scss-playground-script', 'scssPlaygroundAjax', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('admin_enqueue_scripts', 'scss_playground_enqueue_scripts');
