<?php

namespace WP3DS\PostTypes;

defined('ABSPATH') || exit;

class ShowcasePostType
{
    public function register(): void
    {
        $labels = [
            'name'               => __('3D Showcase Items', 'wp-3d-showcase'),
            'singular_name'      => __('3D Showcase Item', 'wp-3d-showcase'),
            'add_new'            => __('Add New', 'wp-3d-showcase'),
            'add_new_item'       => __('Add New 3D Item', 'wp-3d-showcase'),
            'edit_item'          => __('Edit 3D Item', 'wp-3d-showcase'),
            'new_item'           => __('New 3D Item', 'wp-3d-showcase'),
            'view_item'          => __('View 3D Item', 'wp-3d-showcase'),
            'search_items'       => __('Search 3D Items', 'wp-3d-showcase'),
            'not_found'          => __('No items found', 'wp-3d-showcase'),
            'not_found_in_trash' => __('No items found in trash', 'wp-3d-showcase'),
            'menu_name'          => __('3D Showcase', 'wp-3d-showcase'),
        ];

        register_post_type('wp3ds_item', [
            'labels'       => $labels,
            'public'       => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-format-image',
            'supports'     => ['title', 'editor', 'thumbnail'],
            'has_archive'  => false,
            'rewrite'      => ['slug' => '3d-showcase'],
        ]);
    }
}