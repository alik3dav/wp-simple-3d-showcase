<?php

namespace WP3DS\Admin;

defined('ABSPATH') || exit;

class AdminAssets
{
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
            ['jquery'],
            WP3DS_VERSION,
            true
        );
    }
}