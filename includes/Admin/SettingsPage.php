<?php

namespace WP3DS\Admin;


defined('ABSPATH') || exit;

class SettingsPage
{
    private const OPTION_HDRI_MAP = 'wp3ds_hdri_map_url';

    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_filter('upload_mimes', [$this, 'allow_hdr_uploads']);
        add_filter('wp_check_filetype_and_ext', [$this, 'fix_hdr_filetype'], 10, 5);
    }

    public function register_menu(): void
    {
        add_options_page(
            __('WP 3D Showcase Settings', 'wp-3d-showcase'),
            __('WP 3D Showcase', 'wp-3d-showcase'),
            'manage_options',
            'wp3ds-settings',
            [$this, 'render_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'wp3ds_settings',
            self::OPTION_HDRI_MAP,
            [
                'type'              => 'string',
                'sanitize_callback' => [$this, 'sanitize_hdri_map'],
                'default'           => '',
            ]
        );

        add_settings_section(
            'wp3ds_environment_section',
            __('Environment Lighting', 'wp-3d-showcase'),
            function (): void {
                echo '<p>' . esc_html__('Set a global HDRI map to light and reflect across all 3D viewers.', 'wp-3d-showcase') . '</p>';
            },
            'wp3ds-settings'
        );

        add_settings_field(
            self::OPTION_HDRI_MAP,
            __('HDRI Map URL', 'wp-3d-showcase'),
            [$this, 'render_hdri_field'],
            'wp3ds-settings',
            'wp3ds_environment_section'
        );
    }

    public function sanitize_hdri_map($value): string
    {
        $url = esc_url_raw((string) $value);

        if ($url === '') {
            return '';
        }

        $path = wp_parse_url($url, PHP_URL_PATH);
        $extension = is_string($path) ? strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) : '';

        if ($extension !== 'hdr') {
            add_settings_error(
                'wp3ds_settings',
                'wp3ds_invalid_hdri',
                __('Please provide a valid .hdr file URL for the environment map.', 'wp-3d-showcase')
            );

            return $this->get_hdri_map_url();
        }

        return $url;
    }

    public function render_hdri_field(): void
    {
        $value = $this->get_hdri_map_url();
        ?>
        <div class="wp3ds-admin-fields">
            <input
                type="url"
                id="wp3ds_hdri_map_url"
                name="<?php echo esc_attr(self::OPTION_HDRI_MAP); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="regular-text widefat"
                placeholder="https://example.com/studio.hdr"
            >
            <p>
                <button type="button" class="button" data-media-target="#wp3ds_hdri_map_url" data-media-title="<?php echo esc_attr__('Select HDRI Map', 'wp-3d-showcase'); ?>" data-media-button="<?php echo esc_attr__('Use this HDRI map', 'wp-3d-showcase'); ?>">
                    <?php esc_html_e('Select HDRI', 'wp-3d-showcase'); ?>
                </button>
            </p>
            <p class="description">
                <?php esc_html_e('Use a .hdr equirectangular map URL. The selected file will be applied globally as the environment map for all viewers.', 'wp-3d-showcase'); ?>
            </p>
        </div>
        <?php
    }

    public function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP 3D Showcase Settings', 'wp-3d-showcase'); ?></h1>
            <?php settings_errors('wp3ds_settings'); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp3ds_settings');
                do_settings_sections('wp3ds-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function allow_hdr_uploads(array $mimes): array
    {
        $mimes['hdr'] = 'image/vnd.radiance';

        return $mimes;
    }

    public function fix_hdr_filetype(array $data, string $file, string $filename, array $mimes, $real_mime): array
    {
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === 'hdr') {
            $data['ext'] = 'hdr';
            $data['type'] = 'image/vnd.radiance';
            $data['proper_filename'] = $filename;
        }

        return $data;
    }

    public function get_hdri_map_url(): string
    {
        return (string) get_option(self::OPTION_HDRI_MAP, '');
    }
}
