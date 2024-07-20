<?php
/*
Plugin Name: SCSS Playground
Description: A plugin to read and write SCSS files in the wp-content/uploads/scss/ directory using React and Monaco Editor.
Version: 1.0
Author: Your Name
*/

// Create the uploads/scss directory if it doesn't exist
register_activation_hook(__FILE__, 'create_scss_directory');
function create_scss_directory()
{
    $upload_dir = wp_upload_dir();
    $scss_dir = $upload_dir['basedir'] . '/scss';
    if (!file_exists($scss_dir)) {
        mkdir($scss_dir, 0755, true);
    }
}

// Add admin menu
add_action('admin_menu', 'scss_playground_menu');
function scss_playground_menu()
{
    add_menu_page('SCSS Playground', 'SCSS Playground', 'manage_options', 'scss-playground', 'scss_playground_page');
}

// Plugin page content
function scss_playground_page()
{
?>
    <div id="scss-playground-root"></div>
<?php
}

// Enqueue scripts
add_action('admin_enqueue_scripts', 'enqueue_scss_playground_scripts');
function enqueue_scss_playground_scripts($hook)
{
    if ($hook !== 'toplevel_page_scss-playground') {
        return;
    }

    // Enqueue the main JS file
    $asset_file = plugin_dir_path(__FILE__) . 'frontend/index.asset.php';
    $asset = file_exists($asset_file) ? include($asset_file) : array('dependencies' => array(), 'version' => filemtime($asset_file));

    $main_js_url = plugins_url('frontend/index.js', __FILE__);
    wp_enqueue_script('scss-playground-react', $main_js_url, $asset['dependencies'], $asset['version'], true);
    wp_localize_script('scss-playground-react', 'scssPlayground', array(
        'apiUrl' => home_url('/wp-json/scss-playground/v1/')
    ));
}

// Register REST API endpoints
add_action('rest_api_init', function () {
    register_rest_route('scss-playground/v1', '/files', array(
        'methods' => 'GET',
        'callback' => 'get_scss_files',
    ));
    register_rest_route('scss-playground/v1', '/file', array(
        'methods' => 'POST',
        'callback' => 'save_scss_file',
    ));
    register_rest_route('scss-playground/v1', '/file/(?P<filename>[^/]+)', array(
        'methods' => 'GET',
        'callback' => 'get_scss_file',
    ));
});

function get_scss_files()
{
    $upload_dir = wp_upload_dir();
    $scss_dir = $upload_dir['basedir'] . '/scss';
    $files = array_diff(scandir($scss_dir), array('..', '.'));
    return array_values($files);
}

function get_scss_file($request)
{
    $filename = sanitize_file_name($request['filename']);
    $upload_dir = wp_upload_dir();
    $scss_dir = $upload_dir['basedir'] . '/scss';
    $file_path = $scss_dir . '/' . $filename;
    if (file_exists($file_path)) {
        return file_get_contents($file_path);
    } else {
        return new WP_Error('file_not_found', 'File not found', array('status' => 404));
    }
}

function save_scss_file($request)
{
    $filename = sanitize_file_name($request['filename']);
    $content = wp_unslash($request['content']); // Properly handle slashes
    $upload_dir = wp_upload_dir();
    $scss_dir = $upload_dir['basedir'] . '/scss';
    $file_path = $scss_dir . '/' . $filename;
    file_put_contents($file_path, $content);
    return array('success' => true);
}
?>