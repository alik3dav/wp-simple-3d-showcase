<?php

namespace WP3DS;

use WP3DS\PostTypes\ShowcasePostType;
use WP3DS\Admin\MetaBoxes;
use WP3DS\Admin\AdminAssets;
use WP3DS\Admin\SettingsPage;
use WP3DS\Frontend\Shortcode;
use WP3DS\Frontend\FrontendAssets;
use WP3DS\REST\Routes;

defined('ABSPATH') || exit;

class Plugin
{
    public function boot(): void
    {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [new ShowcasePostType(), 'register']);
        add_action('init', [new Shortcode(), 'register']);

        add_action('add_meta_boxes', [new MetaBoxes(), 'register']);
        add_action('save_post_wp3ds_item', [new MetaBoxes(), 'save']);

        $settings_page = new SettingsPage();

        add_action('admin_enqueue_scripts', [new AdminAssets(), 'enqueue']);
        $settings_page->hooks();

        $frontend_assets = new FrontendAssets();
        $frontend_assets->hooks();

        add_action('rest_api_init', [new Routes(), 'register']);
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('wp-3d-showcase', false, dirname(plugin_basename(WP3DS_FILE)) . '/languages');
    }
}