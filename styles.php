<?php
/*
Plugin Name: SCSS Playground
Description: A plugin to read and write SCSS files in the wp-content/uploads/wpeditor/scss/ directory using React and Monaco Editor.
Version: 1.0
Author: Your Name
*/

// Create the uploads/wpeditor/scss and uploads/wpeditor/css directories if they don't exist
register_activation_hook(__FILE__, 'create_scss_directory');
function create_scss_directory()
{
    $upload_dir = wp_upload_dir();
    $scss_dir = $upload_dir['basedir'] . '/wpeditor/scss';
    if (!file_exists($scss_dir)) {
        mkdir($scss_dir, 0755, true);
    }
    $css_dir = $upload_dir['basedir'] . '/wpeditor/css';
    if (!file_exists($css_dir)) {
        mkdir($css_dir, 0755, true);
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

    // Enqueue the CSS file
    $main_css_url = plugins_url('frontend/index.css', __FILE__);
    wp_enqueue_style('scss-playground-css', $main_css_url, array(), null);

    // Enqueue Tailwind CSS
    wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), null, true);

    // Enqueue Gutenberg block library styles if Gutenberg is disabled
    wp_enqueue_style(
        'wp-editor',
        includes_url('css/dist/editor/style.css'),
        [],
        filemtime(ABSPATH . WPINC . '/css/dist/editor/style.css')
    );
}

// Enqueue CSS files on the frontend
add_action('wp_enqueue_scripts', 'enqueue_frontend_css_files');
function enqueue_frontend_css_files()
{
    // Enqueue all CSS files from the uploads/wpeditor/css directory
    $upload_dir = wp_upload_dir();
    $css_dir = $upload_dir['basedir'] . '/wpeditor/css';
    if (is_dir($css_dir)) {
        $css_files = glob($css_dir . '/*.css');
        foreach ($css_files as $css_file) {
            $css_url = site_url('/wp-content/uploads/wpeditor/css/' . basename($css_file));
            wp_register_style('scss-playground-css-' . basename($css_file), $css_url);
            wp_enqueue_style('scss-playground-css-' . basename($css_file));
        }
    }
}

// Include REST API endpoints
require_once plugin_dir_path(__FILE__) . 'inc/rest-endpoints.php';


function get_scss_files()
{
    $upload_dir = wp_upload_dir();
    $scss_dir = $upload_dir['basedir'] . '/wpeditor/scss';
    $files = array_diff(scandir($scss_dir), array('..', '.'));
    return array_values($files);
}



function get_scss_file($request)
{
    $filename = sanitize_file_name($request['filename']);
    $upload_dir = wp_upload_dir();
    $scss_dir = $upload_dir['basedir'] . '/wpeditor/scss';
    $file_path = $scss_dir . '/' . $filename;

    if (!file_exists($file_path)) {
        error_log("File not found: " . $file_path); // Log the error
        return new WP_Error('file_not_found', 'File not found: ' . $filename, array('status' => 404));
    }

    $content = file_get_contents($file_path);
    if ($content === false) {
        error_log("Failed to read file: " . $file_path); // Log the error
        return new WP_Error('file_read_error', 'Failed to read file: ' . $filename, array('status' => 500));
    }

    return $content;
}

function save_css_file($request)
{
    $filename = sanitize_file_name($request['filename']);
    $content = wp_unslash($request->get_param('content')); // Properly handle slashes
    $upload_dir = wp_upload_dir();
    $css_dir = $upload_dir['basedir'] . '/wpeditor/css';
    if (!file_exists($css_dir)) {
        mkdir($css_dir, 0755, true);
    }
    $file_path = $css_dir . '/' . $filename;
    file_put_contents($file_path, $content);
    return array('success' => true);
}

function delete_file($request)
{
    $filename = sanitize_file_name($request['filename']);
    $upload_dir = wp_upload_dir();
    $scss_dir = $upload_dir['basedir'] . '/wpeditor/scss';
    $file_path = $scss_dir . '/' . $filename;

    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            return array('success' => true, 'message' => 'File deleted successfully');
        } else {
            error_log("Failed to delete file: " . $file_path); // Log the error
            return new WP_Error('file_delete_error', 'Failed to delete file', array('status' => 500));
        }
    }

    error_log("File not found for deletion: " . $file_path); // Log the error
    return new WP_Error('file_not_found', 'File not found', array('status' => 404));
}


require_once plugin_dir_path(__FILE__) . 'inc/hotreload.php';

?>