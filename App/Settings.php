<?php

namespace SCSSPlayground\App;

class Settings
{
    public static function register_settings()
    {
        register_setting('scss_playground_options_group', 'scss_playground_option');
        add_settings_section('scss_playground_section', 'SCSS Playground Settings', null, 'scss-playground');
        add_settings_field('scss_playground_field', 'SCSS Playground Field', [self::class, 'field_callback'], 'scss-playground', 'scss_playground_section');
    }

    public static function field_callback()
    {
        $value = get_option('scss_playground_option', '');
        echo '<input type="text" name="scss_playground_option" value="' . esc_attr($value) . '" />';
    }
}
