<?php

namespace WP3DS\REST;

defined('ABSPATH') || exit;

class Routes
{
    public function register(): void
    {
        register_rest_route('wp3ds/v1', '/item/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_item'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_item(\WP_REST_Request $request): \WP_REST_Response
    {
        $post_id = absint($request['id']);

        if (get_post_type($post_id) !== 'wp3ds_item') {
            return new \WP_REST_Response(['message' => 'Not found'], 404);
        }

        return new \WP_REST_Response([
            'id'           => $post_id,
            'title'        => get_the_title($post_id),
            'model_url'    => get_post_meta($post_id, '_wp3ds_model_url', true),
            'bg_color'     => get_post_meta($post_id, '_wp3ds_bg_color', true),
            'auto_rotate'  => get_post_meta($post_id, '_wp3ds_auto_rotate', true) === '1',
            'explode_step' => get_post_meta($post_id, '_wp3ds_explode_step', true),
            'hdri_map_url' => get_option('wp3ds_hdri_map_url', ''),
        ]);
    }
}