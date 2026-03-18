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

        $model_url       = get_post_meta($post->ID, '_wp3ds_model_url', true);
        $bg_color        = get_post_meta($post->ID, '_wp3ds_bg_color', true) ?: '#f5f5f5';
        $auto_rotate     = get_post_meta($post->ID, '_wp3ds_auto_rotate', true);
        $explode_step    = get_post_meta($post->ID, '_wp3ds_explode_step', true) ?: '0.15';
        $explode_parts   = get_post_meta($post->ID, '_wp3ds_explode_parts', true);
        $explode_parts   = is_string($explode_parts) ? $explode_parts : '[]';
        $decoded_parts   = json_decode($explode_parts, true);
        $explode_parts   = is_array($decoded_parts) ? wp_json_encode($decoded_parts) : '[]';
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

            <div
                class="wp3ds-explode-parts"
                data-explode-parts="<?php echo esc_attr($explode_parts); ?>"
            >
                <input type="hidden" id="wp3ds_explode_parts" name="wp3ds_explode_parts" value="<?php echo esc_attr($explode_parts); ?>">
                <div class="wp3ds-explode-parts__header">
                    <strong><?php esc_html_e('Explode View Parts', 'wp-3d-showcase'); ?></strong>
                    <p class="description"><?php esc_html_e('The plugin automatically detects mesh parts from the GLB model and lets you fine-tune the explode direction for each part on the X, Y, and Z axes.', 'wp-3d-showcase'); ?></p>
                </div>
                <div class="wp3ds-explode-parts__status" data-parts-status>
                    <?php esc_html_e('Select or enter a GLB file URL to detect model parts.', 'wp-3d-showcase'); ?>
                </div>
                <div class="wp3ds-explode-parts__table-wrap" data-parts-list hidden></div>
            </div>
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

        $model_url      = isset($_POST['wp3ds_model_url']) ? esc_url_raw(wp_unslash($_POST['wp3ds_model_url'])) : '';
        $bg_color       = isset($_POST['wp3ds_bg_color']) ? sanitize_hex_color(wp_unslash($_POST['wp3ds_bg_color'])) : '#f5f5f5';
        $explode_step   = isset($_POST['wp3ds_explode_step']) ? sanitize_text_field(wp_unslash($_POST['wp3ds_explode_step'])) : '0.15';
        $auto_rotate    = isset($_POST['wp3ds_auto_rotate']) ? '1' : '0';
        $explode_parts  = isset($_POST['wp3ds_explode_parts']) ? $this->sanitize_explode_parts_json(wp_unslash($_POST['wp3ds_explode_parts'])) : '[]';

        update_post_meta($post_id, '_wp3ds_model_url', $model_url);
        update_post_meta($post_id, '_wp3ds_bg_color', $bg_color);
        update_post_meta($post_id, '_wp3ds_explode_step', $explode_step);
        update_post_meta($post_id, '_wp3ds_auto_rotate', $auto_rotate);
        update_post_meta($post_id, '_wp3ds_explode_parts', $explode_parts);
    }

    private function sanitize_explode_parts_json(string $raw_json): string
    {
        $decoded = json_decode($raw_json, true);

        if (!is_array($decoded)) {
            return '[]';
        }

        $sanitized = [];

        foreach ($decoded as $part) {
            if (!is_array($part) || empty($part['key'])) {
                continue;
            }

            $sanitized[] = [
                'key'  => sanitize_text_field((string) $part['key']),
                'name' => sanitize_text_field((string) ($part['name'] ?? 'Part')),
                'x'    => $this->sanitize_axis_value($part['x'] ?? 0),
                'y'    => $this->sanitize_axis_value($part['y'] ?? 0),
                'z'    => $this->sanitize_axis_value($part['z'] ?? 0),
            ];
        }

        return wp_json_encode($sanitized) ?: '[]';
    }

    private function sanitize_axis_value($value): float
    {
        return round((float) $value, 4);
    }
}
