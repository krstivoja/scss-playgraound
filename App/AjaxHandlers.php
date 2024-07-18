<?php

namespace SCSSPlayground\App;

class AjaxHandlers
{
    public static function get_scss_files()
    {
        $upload_dir = wp_upload_dir()['basedir'] . '/code';
        $files = array_diff(scandir($upload_dir), array('.', '..'));
        $scss_files = array_filter($files, fn ($file) => pathinfo($file, PATHINFO_EXTENSION) === 'scss');
        wp_send_json_success(['files' => array_values($scss_files)]);
    }

    public static function load_scss_file()
    {
        $file = sanitize_file_name($_GET['file']);
        $upload_dir = wp_upload_dir()['basedir'] . '/code';
        $file_path = "$upload_dir/$file";
        if (file_exists($file_path)) {
            echo file_get_contents($file_path);
        }
        wp_die();
    }

    public static function save_scss_file()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $file = sanitize_file_name($data['file']);
        $content = sanitize_textarea_field($data['content']);
        $upload_dir = wp_upload_dir()['basedir'] . '/code';
        file_put_contents("$upload_dir/$file", $content);
        wp_die();
    }
}
