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

        $model_url     = get_post_meta($post_id, '_wp3ds_model_url', true);
        $model_name    = get_the_title($post_id) ?: __('3D Model', 'wp-3d-showcase');
        $plugin_label  = sprintf(
            /* translators: 1: plugin name, 2: plugin version */
            __('%1$s v%2$s', 'wp-3d-showcase'),
            'WP 3D Showcase',
            WP3DS_VERSION
        );
        $bg_color      = get_post_meta($post_id, '_wp3ds_bg_color', true) ?: '#f5f5f5';
        $auto_rotate   = get_post_meta($post_id, '_wp3ds_auto_rotate', true) === '1';
        $explode_step  = get_post_meta($post_id, '_wp3ds_explode_step', true) ?: '0.15';
        $explode_parts = get_post_meta($post_id, '_wp3ds_explode_parts', true) ?: '[]';

        $settings_page = new \WP3DS\Admin\SettingsPage();
        $hdri_map_url = $settings_page->get_hdri_map_url();
        $interaction_settings = $settings_page->get_interaction_settings();

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
            data-explode-parts="<?php echo esc_attr($explode_parts); ?>"
            data-hdri-map-url="<?php echo esc_url($hdri_map_url); ?>"
            data-selection-highlight-color="<?php echo esc_attr($interaction_settings['selectionHighlightColor']); ?>"
            data-hover-highlight-color="<?php echo esc_attr($interaction_settings['hoverHighlightColor']); ?>"
            data-selection-glow-intensity="<?php echo esc_attr((string) $interaction_settings['selectionGlowIntensity']); ?>"
            data-isolate-dim-opacity="<?php echo esc_attr((string) $interaction_settings['isolateDimOpacity']); ?>"
        >
            <div class="wp3ds-toolbar" aria-label="3D viewer controls">
                <button type="button" data-action="reset" aria-label="Reset view">Reset</button>
                <button type="button" data-action="autorotate" aria-label="Toggle auto rotation">Rotate</button>
                <button type="button" data-action="explode" aria-label="Toggle explode view">Explode</button>
                <button type="button" data-action="isolate" aria-label="Toggle isolate mode">Focus</button>
                <button type="button" data-action="fullscreen" aria-label="Toggle fullscreen">Full</button>
            </div>

            <div class="wp3ds-canvas-wrap">
                <div class="wp3ds-model-name" aria-label="3D model name"><?php echo esc_html($model_name); ?></div>
                <canvas></canvas>
                <div class="wp3ds-loading">Loading 3D model…</div>
                <div class="wp3ds-plugin-meta" aria-label="Plugin name and version"><?php echo esc_html($plugin_label); ?></div>
                <div class="wp3ds-part-modal" data-part-modal hidden>
                    <button type="button" class="wp3ds-part-modal__close" data-action="close-part-modal" aria-label="Close part details">×</button>
                    <div class="wp3ds-part-modal__eyebrow">Part details</div>
                    <h3 class="wp3ds-part-modal__title" data-part-title>Select a part</h3>
                    <p class="wp3ds-part-modal__description" data-part-description>Click any object in the model to open a compact info card with its details.</p>
                    <div class="wp3ds-part-modal__meta">
                        <div class="wp3ds-part-modal__section" data-part-characteristics-section hidden>
                            <div class="wp3ds-part-modal__label">Characteristics</div>
                            <ul class="wp3ds-part-modal__list" data-part-characteristics></ul>
                        </div>
                        <div class="wp3ds-part-modal__section">
                            <div class="wp3ds-part-modal__label">Mesh key</div>
                            <code class="wp3ds-part-modal__code" data-part-key>—</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
