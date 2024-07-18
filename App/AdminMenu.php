<?php

namespace SCSSPlayground\App;

class AdminMenu
{
    public static function add_settings_page()
    {
        add_menu_page(
            'SCSS Playground',   // Page title
            'SCSS Playground',   // Menu title
            'manage_options',    // Capability
            'scss-playground',   // Menu slug
            [self::class, 'render_settings_page'], // Callback function
            'dashicons-editor-code', // Icon URL
            6 // Position
        );
    }

    public static function render_settings_page()
    {
?>
        <div class="wrap">
            <h1>SCSS Playground</h1>
            <div id="root"></div>
            <div id="root2">Marko</div>
        </div>
<?php
    }
}
