<?php

namespace WP3DS\Admin;

defined('ABSPATH') || exit;

class MetaBoxes
{
    public function register(): void
    {
        add_meta_box(
            'wp3ds_model_settings',
            __('3D Model Settings', 'wp-3d-showcase'),
            [$this, 'render'],
            'wp3ds_item',
            'normal',
            'default'
        );
    }

    public function render(\WP_Post $post): void
    {
        wp_nonce_field('wp3ds_save_meta', 'wp3ds_meta_nonce');

        $model_url    = get_post_meta($post->ID, '_wp3ds_model_url', true);
        $bg_color     = get_post_meta($post->ID, '_wp3ds_bg_color', true) ?: '#f5f5f5';
        $auto_rotate  = get_post_meta($post->ID, '_wp3ds_auto_rotate', true);
        $explode_step = get_post_meta($post->ID, '_wp3ds_explode_step', true) ?: '0.15';
        ?>
        <div class="wp3ds-admin-fields">
            <p>
                <label for="wp3ds_model_url"><strong><?php esc_html_e('GLB File URL', 'wp-3d-showcase'); ?></strong></label><br>
                <input type="text" id="wp3ds_model_url" name="wp3ds_model_url" value="<?php echo esc_attr($model_url); ?>" class="widefat">
            </p>

            <p>
                <label for="wp3ds_bg_color"><strong><?php esc_html_e('Background Color', 'wp-3d-showcase'); ?></strong></label><br>
                <input type="color" id="wp3ds_bg_color" name="wp3ds_bg_color" value="<?php echo esc_attr($bg_color); ?>">
            </p>

            <p>
                <label for="wp3ds_explode_step"><strong><?php esc_html_e('Explode Strength', 'wp-3d-showcase'); ?></strong></label><br>
                <input type="number" step="0.01" min="0" max="5" id="wp3ds_explode_step" name="wp3ds_explode_step" value="<?php echo esc_attr($explode_step); ?>">
            </p>

            <p>
                <label>
                    <input type="checkbox" name="wp3ds_auto_rotate" value="1" <?php checked($auto_rotate, '1'); ?>>
                    <?php esc_html_e('Enable auto-rotate by default', 'wp-3d-showcase'); ?>
                </label>
            </p>
        </div>
        <?php
    }

    public function save(int $post_id): void
    {
        if (!isset($_POST['wp3ds_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wp3ds_meta_nonce'])), 'wp3ds_save_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $model_url    = isset($_POST['wp3ds_model_url']) ? esc_url_raw(wp_unslash($_POST['wp3ds_model_url'])) : '';
        $bg_color     = isset($_POST['wp3ds_bg_color']) ? sanitize_hex_color(wp_unslash($_POST['wp3ds_bg_color'])) : '#f5f5f5';
        $explode_step = isset($_POST['wp3ds_explode_step']) ? sanitize_text_field(wp_unslash($_POST['wp3ds_explode_step'])) : '0.15';
        $auto_rotate  = isset($_POST['wp3ds_auto_rotate']) ? '1' : '0';

        update_post_meta($post_id, '_wp3ds_model_url', $model_url);
        update_post_meta($post_id, '_wp3ds_bg_color', $bg_color);
        update_post_meta($post_id, '_wp3ds_explode_step', $explode_step);
        update_post_meta($post_id, '_wp3ds_auto_rotate', $auto_rotate);
    }
}