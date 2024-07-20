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
    register_rest_route('scss-playground/v1', '/file/(?P<filename>[^/]+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_scss_file',
    ));
    register_rest_route('scss-playground/v1', '/css-file', array(
        'methods' => 'POST',
        'callback' => 'save_css_file',
    ));
    register_rest_route('scss-playground/v1', '/active-css', array(
        'methods' => 'GET',
        'callback' => 'get_active_css_file',
    ));
});

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
    if (file_exists($file_path)) {
        return file_get_contents($file_path);
    } else {
        return new WP_Error('file_not_found', 'File not found', array('status' => 404));
    }
}

function save_scss_file($request)
{
    $filename = sanitize_file_name($request['filename']);
    $content = wp_unslash($request->get_param('content')); // Properly handle slashes
    $upload_dir = wp_upload_dir();
    $scss_dir = $upload_dir['basedir'] . '/wpeditor/scss';
    $file_path = $scss_dir . '/' . $filename;
    file_put_contents($file_path, $content);
    return array('success' => true);
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

function delete_scss_file($request)
{
    $filename = sanitize_file_name($request['filename']);
    $upload_dir = wp_upload_dir();
    $scss_dir = $upload_dir['basedir'] . '/wpeditor/scss';
    $file_path = $scss_dir . '/' . $filename;

    if (file_exists($file_path)) {
        unlink($file_path);
        return array('success' => true);
    } else {
        return new WP_Error('file_not_found', 'File not found', array('status' => 404));
    }
}

function get_active_css_file()
{
    // Logic to determine the active CSS file
    $upload_dir = wp_upload_dir();
    $css_dir = $upload_dir['basedir'] . '/wpeditor/css';
    $css_files = glob($css_dir . '/*.css');
    return array('active_css' => !empty($css_files) ? basename($css_files[0]) : null);
}

// Enqueue the script for hot reloading
add_action('wp_footer', 'enqueue_hot_reload_script');
function enqueue_hot_reload_script()
{
?>
    <script>
        let activeCssFile = '';

        // Fetch the active CSS file
        fetch('<?php echo esc_url(rest_url('scss-playground/v1/active-css')); ?>')
            .then(response => response.json())
            .then(data => {
                if (data.active_css) {
                    activeCssFile = data.active_css;
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = '<?php echo site_url('/wp-content/uploads/wpeditor/css/'); ?>' + activeCssFile;
                    document.head.appendChild(link);

                    // Listen for changes and reload the CSS
                    const eventSource = new EventSource('<?php echo esc_url(rest_url('scss-playground/v1/file/' . activeCssFile)); ?>');
                    eventSource.onmessage = function() {
                        link.href = '<?php echo site_url('/wp-content/uploads/wpeditor/css/'); ?>' + activeCssFile + '?t=' + new Date().getTime(); // Add timestamp to force reload
                    };
                }
            });
    </script>
<?php
}
?>