<?php

namespace WP3DS\Frontend;

defined('ABSPATH') || exit;

class FrontendAssets
{
    public function hooks(): void
    {
        add_action('wp_head', [$this, 'print_import_map'], 1);
        add_action('wp_enqueue_scripts', [$this, 'register']);
        add_filter('script_loader_tag', [$this, 'add_module_type'], 10, 3);
    }

    public function print_import_map(): void
    {
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

    public function register(): void
    {
        wp_register_style(
            'wp3ds-frontend',
            WP3DS_URL . 'assets/css/frontend.css',
            [],
            WP3DS_VERSION
        );

        wp_register_script(
            'wp3ds-frontend',
            WP3DS_URL . 'assets/js/frontend.js',
            [],
            WP3DS_VERSION,
            true
        );
    }

    public function add_module_type(string $tag, string $handle, string $src): string
    {
        if ($handle !== 'wp3ds-frontend') {
            return $tag;
        }

        return '<script type="module" src="' . esc_url($src) . '"></script>';
    }
}