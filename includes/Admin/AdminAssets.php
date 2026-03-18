<?php

namespace WP3DS\Admin;

defined('ABSPATH') || exit;

class AdminAssets
{
    public function hooks(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('admin_head', [$this, 'print_import_map']);
        add_filter('script_loader_tag', [$this, 'add_module_type'], 10, 3);
    }

    public function enqueue(string $hook): void
    {
        global $post_type;

        $allowed_hooks = ['settings_page_wp3ds-settings'];

        if ($post_type !== 'wp3ds_item' && !in_array($hook, $allowed_hooks, true)) {
            return;
        }

        wp_enqueue_style(
            'wp3ds-admin',
            WP3DS_URL . 'assets/css/admin.css',
            [],
            WP3DS_VERSION
        );

        wp_enqueue_media();

        wp_enqueue_script(
            'wp3ds-admin',
            WP3DS_URL . 'assets/js/admin.js',
            [],
            WP3DS_VERSION,
            true
        );
    }

    public function print_import_map(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (!$screen || ($screen->post_type !== 'wp3ds_item' && $screen->id !== 'settings_page_wp3ds-settings')) {
            return;
        }
        ?>
        <script type="importmap">
        {
          "imports": {
            "three": "https://cdn.jsdelivr.net/npm/three@0.160.1/build/three.module.js",
            "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.160.1/examples/jsm/"
          }
        }
        </script>
        <?php
    }

    public function add_module_type(string $tag, string $handle, string $src): string
    {
        if ($handle !== 'wp3ds-admin') {
            return $tag;
        }

        return '<script type="module" src="' . esc_url($src) . '"></script>';
    }
}
