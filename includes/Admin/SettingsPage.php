<?php

namespace WP3DS\Admin;


defined('ABSPATH') || exit;

class SettingsPage
{
    private const OPTION_HDRI_MAP = 'wp3ds_hdri_map_url';
    private const OPTION_SELECTION_HIGHLIGHT_COLOR = 'wp3ds_selection_highlight_color';
    private const OPTION_HOVER_HIGHLIGHT_COLOR = 'wp3ds_hover_highlight_color';
    private const OPTION_SELECTION_GLOW_INTENSITY = 'wp3ds_selection_glow_intensity';
    private const OPTION_ISOLATE_DIM_OPACITY = 'wp3ds_isolate_dim_opacity';
    private const DEFAULT_SELECTION_HIGHLIGHT_COLOR = '#2f6df6';
    private const DEFAULT_HOVER_HIGHLIGHT_COLOR = '#333333';
    private const DEFAULT_SELECTION_GLOW_INTENSITY = 0.22;
    private const DEFAULT_ISOLATE_DIM_OPACITY = 0.18;
    private const HDR_EXTENSION = 'hdr';
    private const HDR_MIME_TYPE = 'application/octet-stream';

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

        register_setting(
            'wp3ds_settings',
            self::OPTION_SELECTION_HIGHLIGHT_COLOR,
            [
                'type'              => 'string',
                'sanitize_callback' => [$this, 'sanitize_selection_highlight_color'],
                'default'           => self::DEFAULT_SELECTION_HIGHLIGHT_COLOR,
            ]
        );

        register_setting(
            'wp3ds_settings',
            self::OPTION_HOVER_HIGHLIGHT_COLOR,
            [
                'type'              => 'string',
                'sanitize_callback' => [$this, 'sanitize_hover_highlight_color'],
                'default'           => self::DEFAULT_HOVER_HIGHLIGHT_COLOR,
            ]
        );

        register_setting(
            'wp3ds_settings',
            self::OPTION_SELECTION_GLOW_INTENSITY,
            [
                'type'              => 'number',
                'sanitize_callback' => [$this, 'sanitize_selection_glow_intensity'],
                'default'           => self::DEFAULT_SELECTION_GLOW_INTENSITY,
            ]
        );

        register_setting(
            'wp3ds_settings',
            self::OPTION_ISOLATE_DIM_OPACITY,
            [
                'type'              => 'number',
                'sanitize_callback' => [$this, 'sanitize_isolate_dim_opacity'],
                'default'           => self::DEFAULT_ISOLATE_DIM_OPACITY,
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

        add_settings_section(
            'wp3ds_interaction_section',
            __('Part Selection & Focus', 'wp-3d-showcase'),
            function (): void {
                echo '<p>' . esc_html__('Control how selected and hovered parts are highlighted, and how strongly the rest of the model fades in focus mode.', 'wp-3d-showcase') . '</p>';
            },
            'wp3ds-settings'
        );

        add_settings_field(
            self::OPTION_SELECTION_HIGHLIGHT_COLOR,
            __('Selected Part Color', 'wp-3d-showcase'),
            [$this, 'render_selection_highlight_color_field'],
            'wp3ds-settings',
            'wp3ds_interaction_section'
        );

        add_settings_field(
            self::OPTION_HOVER_HIGHLIGHT_COLOR,
            __('Hover Color', 'wp-3d-showcase'),
            [$this, 'render_hover_highlight_color_field'],
            'wp3ds-settings',
            'wp3ds_interaction_section'
        );

        add_settings_field(
            self::OPTION_SELECTION_GLOW_INTENSITY,
            __('Selection Glow Strength', 'wp-3d-showcase'),
            [$this, 'render_selection_glow_intensity_field'],
            'wp3ds-settings',
            'wp3ds_interaction_section'
        );

        add_settings_field(
            self::OPTION_ISOLATE_DIM_OPACITY,
            __('Background Part Opacity', 'wp-3d-showcase'),
            [$this, 'render_isolate_dim_opacity_field'],
            'wp3ds-settings',
            'wp3ds_interaction_section'
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

        if ($extension !== self::HDR_EXTENSION) {
            add_settings_error(
                'wp3ds_settings',
                'wp3ds_invalid_hdri',
                __('Please provide a valid .hdr file URL for the environment map.', 'wp-3d-showcase')
            );

            return $this->get_hdri_map_url();
        }

        return $url;
    }

    public function sanitize_selection_highlight_color($value): string
    {
        return $this->sanitize_hex_color_setting($value, self::DEFAULT_SELECTION_HIGHLIGHT_COLOR);
    }

    public function sanitize_hover_highlight_color($value): string
    {
        return $this->sanitize_hex_color_setting($value, self::DEFAULT_HOVER_HIGHLIGHT_COLOR);
    }

    public function sanitize_selection_glow_intensity($value): float
    {
        return $this->sanitize_unit_interval_setting($value, self::DEFAULT_SELECTION_GLOW_INTENSITY);
    }

    public function sanitize_isolate_dim_opacity($value): float
    {
        return $this->sanitize_unit_interval_setting($value, self::DEFAULT_ISOLATE_DIM_OPACITY);
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

    public function render_selection_highlight_color_field(): void
    {
        $this->render_color_field(
            self::OPTION_SELECTION_HIGHLIGHT_COLOR,
            $this->get_selection_highlight_color(),
            __('Outline color used when a part is selected.', 'wp-3d-showcase')
        );
    }

    public function render_hover_highlight_color_field(): void
    {
        $this->render_color_field(
            self::OPTION_HOVER_HIGHLIGHT_COLOR,
            $this->get_hover_highlight_color(),
            __('Used when a part is hovered before selection.', 'wp-3d-showcase')
        );
    }

    public function render_selection_glow_intensity_field(): void
    {
        ?>
        <div class="wp3ds-admin-fields">
            <input
                type="number"
                id="<?php echo esc_attr(self::OPTION_SELECTION_GLOW_INTENSITY); ?>"
                name="<?php echo esc_attr(self::OPTION_SELECTION_GLOW_INTENSITY); ?>"
                value="<?php echo esc_attr((string) $this->get_selection_glow_intensity()); ?>"
                min="0"
                max="1"
                step="0.01"
                class="small-text"
            >
            <p class="description">
                <?php esc_html_e('Controls how strong the outline glow appears. Use 0 for outline-only highlighting.', 'wp-3d-showcase'); ?>
            </p>
        </div>
        <?php
    }

    public function render_isolate_dim_opacity_field(): void
    {
        ?>
        <div class="wp3ds-admin-fields">
            <input
                type="number"
                id="<?php echo esc_attr(self::OPTION_ISOLATE_DIM_OPACITY); ?>"
                name="<?php echo esc_attr(self::OPTION_ISOLATE_DIM_OPACITY); ?>"
                value="<?php echo esc_attr((string) $this->get_isolate_dim_opacity()); ?>"
                min="0"
                max="1"
                step="0.01"
                class="small-text"
            >
            <p class="description">
                <?php esc_html_e('Opacity applied to non-selected parts while Focus mode is active. Use 1 to keep all parts fully visible.', 'wp-3d-showcase'); ?>
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
        if (!$this->is_current_upload_for_hdr()) {
            return $mimes;
        }

        $mimes[self::HDR_EXTENSION] = self::HDR_MIME_TYPE;

        return $mimes;
    }

    public function fix_hdr_filetype(array $data, string $file, string $filename, ?array $mimes, $real_mime): array
    {
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension !== self::HDR_EXTENSION) {
            return $data;
        }

        $data['ext'] = self::HDR_EXTENSION;
        $data['type'] = self::HDR_MIME_TYPE;
        $data['proper_filename'] = $filename;

        return $data;
    }

    public function get_hdri_map_url(): string
    {
        return (string) get_option(self::OPTION_HDRI_MAP, '');
    }

    /**
     * @return array{selectionHighlightColor: string, hoverHighlightColor: string, selectionGlowIntensity: float, isolateDimOpacity: float}
     */
    public function get_interaction_settings(): array
    {
        return [
            'selectionHighlightColor' => $this->get_selection_highlight_color(),
            'hoverHighlightColor' => $this->get_hover_highlight_color(),
            'selectionGlowIntensity' => $this->get_selection_glow_intensity(),
            'isolateDimOpacity' => $this->get_isolate_dim_opacity(),
        ];
    }

    private function sanitize_hex_color_setting($value, string $fallback): string
    {
        $sanitized = sanitize_hex_color((string) $value);

        if ($sanitized === null) {
            add_settings_error(
                'wp3ds_settings',
                'wp3ds_invalid_highlight_color',
                __('Please provide a valid hex color value.', 'wp-3d-showcase')
            );

            return $fallback;
        }

        return $sanitized;
    }

    private function sanitize_unit_interval_setting($value, float $fallback): float
    {
        $parsed = is_numeric($value) ? (float) $value : $fallback;
        $parsed = max(0, min(1, $parsed));

        return round($parsed, 2);
    }

    private function render_color_field(string $optionName, string $value, string $description): void
    {
        ?>
        <div class="wp3ds-admin-fields">
            <input
                type="text"
                id="<?php echo esc_attr($optionName); ?>"
                name="<?php echo esc_attr($optionName); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="regular-text"
                placeholder="#2f6df6"
                pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$"
            >
            <p class="description"><?php echo esc_html($description); ?></p>
        </div>
        <?php
    }

    private function get_selection_highlight_color(): string
    {
        return sanitize_hex_color((string) get_option(self::OPTION_SELECTION_HIGHLIGHT_COLOR, self::DEFAULT_SELECTION_HIGHLIGHT_COLOR))
            ?: self::DEFAULT_SELECTION_HIGHLIGHT_COLOR;
    }

    private function get_hover_highlight_color(): string
    {
        return sanitize_hex_color((string) get_option(self::OPTION_HOVER_HIGHLIGHT_COLOR, self::DEFAULT_HOVER_HIGHLIGHT_COLOR))
            ?: self::DEFAULT_HOVER_HIGHLIGHT_COLOR;
    }

    private function get_selection_glow_intensity(): float
    {
        $value = get_option(self::OPTION_SELECTION_GLOW_INTENSITY, self::DEFAULT_SELECTION_GLOW_INTENSITY);

        return $this->sanitize_unit_interval_setting($value, self::DEFAULT_SELECTION_GLOW_INTENSITY);
    }

    private function get_isolate_dim_opacity(): float
    {
        $value = get_option(self::OPTION_ISOLATE_DIM_OPACITY, self::DEFAULT_ISOLATE_DIM_OPACITY);

        return $this->sanitize_unit_interval_setting($value, self::DEFAULT_ISOLATE_DIM_OPACITY);
    }

    private function is_current_upload_for_hdr(): bool
    {
        return in_array(self::HDR_EXTENSION, $this->get_uploaded_file_extensions($_FILES ?? []), true);
    }

    /**
     * @param array<string, mixed> $files
     * @return string[]
     */
    private function get_uploaded_file_extensions(array $files): array
    {
        $extensions = [];

        array_walk_recursive(
            $files,
            static function ($value, $key) use (&$extensions): void {
                if ($key !== 'name' || !is_string($value)) {
                    return;
                }

                $extension = strtolower((string) pathinfo($value, PATHINFO_EXTENSION));

                if ($extension !== '') {
                    $extensions[] = $extension;
                }
            }
        );

        return array_values(array_unique($extensions));
    }
}
