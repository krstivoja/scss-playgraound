<?php

namespace SCSSPlayground\App;

class RestHandlers
{
    public static function get_scss_files_rest()
    {
        $files = glob(WP_CONTENT_DIR . '/uploads/code/*.scss');
        $files = array_map('basename', $files);
        return rest_ensure_response($files);
    }

    public static function save_scss_file_rest(\WP_REST_Request $request)
    {
        $body = json_decode($request->get_body(), true);
        if (!isset($body['content'])) {
            return new \WP_Error('no_content', 'No content provided', array('status' => 400));
        }

        $file_path = WP_CONTENT_DIR . '/uploads/code/' . sanitize_file_name($body['file']);
        if (file_put_contents($file_path, sanitize_textarea_field($body['content'])) !== false) {
            return rest_ensure_response('File saved successfully');
        } else {
            return new \WP_Error('save_failed', 'Failed to save file', array('status' => 500));
        }
    }
}
