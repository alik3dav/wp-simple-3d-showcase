<?php

namespace WP3DS\Frontend;

defined('ABSPATH') || exit;

class Shortcode
{
    public function register(): void
    {
        add_shortcode('wp3ds_viewer', [$this, 'render']);
    }

    public function render(array $atts = []): string
    {
        $atts = shortcode_atts([
            'id'     => 0,
            'slug'   => '',
            'height' => '600px',
        ], $atts, 'wp3ds_viewer');

        $post_id = 0;

        if (!empty($atts['id'])) {
            $post_id = absint($atts['id']);
        }

        if (!$post_id && !empty($atts['slug'])) {
            $post = get_page_by_path(sanitize_title($atts['slug']), OBJECT, 'wp3ds_item');
            if ($post) {
                $post_id = $post->ID;
            }
        }

        if (!$post_id && get_post_type() === 'wp3ds_item') {
            $post_id = get_the_ID();
        }

        if (!$post_id || get_post_type($post_id) !== 'wp3ds_item') {
            return '<p>Invalid 3D item.</p>';
        }

        $model_url    = get_post_meta($post_id, '_wp3ds_model_url', true);
        $bg_color     = get_post_meta($post_id, '_wp3ds_bg_color', true) ?: '#f5f5f5';
        $auto_rotate  = get_post_meta($post_id, '_wp3ds_auto_rotate', true) === '1';
        $explode_step = get_post_meta($post_id, '_wp3ds_explode_step', true) ?: '0.15';
        $hdri_map_url = get_option('wp3ds_hdri_map_url', '');

        if (!$model_url) {
            return '<p>No GLB file assigned.</p>';
        }

        wp_enqueue_style('wp3ds-frontend');
        wp_enqueue_script('wp3ds-frontend');

        ob_start();
        ?>
        <div
            class="wp3ds-viewer"
            style="height: <?php echo esc_attr($atts['height']); ?>;"
            data-model-url="<?php echo esc_url($model_url); ?>"
            data-bg-color="<?php echo esc_attr($bg_color); ?>"
            data-auto-rotate="<?php echo esc_attr($auto_rotate ? 'true' : 'false'); ?>"
            data-explode-step="<?php echo esc_attr($explode_step); ?>"
            data-hdri-map-url="<?php echo esc_url($hdri_map_url); ?>"
        >
            <div class="wp3ds-toolbar">
                <button type="button" data-action="reset">Reset</button>
                <button type="button" data-action="autorotate">Auto Rotate</button>
                <button type="button" data-action="explode">Explode</button>
                <button type="button" data-action="isolate">Isolate</button>
                <button type="button" data-action="fullscreen">Fullscreen</button>
            </div>

            <div class="wp3ds-canvas-wrap">
                <canvas></canvas>
                <div class="wp3ds-loading">Loading 3D model…</div>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
