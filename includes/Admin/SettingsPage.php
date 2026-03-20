<?php
/**
 * Plugin settings page.
 *
 * @package WP3DS
 */

namespace WP3DS\Admin;

use WP3DS\Helpers;

defined( 'ABSPATH' ) || exit;

class SettingsPage {
	private const OPTION_HDRI_MAP_URL                 = 'wp3ds_hdri_map_url';
	private const OPTION_SELECTION_HIGHLIGHT_COLOR    = 'wp3ds_selection_highlight_color';
	private const OPTION_HOVER_HIGHLIGHT_COLOR        = 'wp3ds_hover_highlight_color';
	private const OPTION_SELECTION_GLOW_INTENSITY     = 'wp3ds_selection_glow_intensity';
	private const OPTION_ISOLATE_DIM_OPACITY          = 'wp3ds_isolate_dim_opacity';
	private const DEFAULT_SELECTION_HIGHLIGHT_COLOR   = '#2f6df6';
	private const DEFAULT_HOVER_HIGHLIGHT_COLOR       = '#333333';
	private const DEFAULT_SELECTION_GLOW_INTENSITY    = 0.22;
	private const DEFAULT_ISOLATE_DIM_OPACITY         = 0.18;
	private const HDR_EXTENSION                       = 'hdr';
	private const HDR_MIME_TYPE                       = 'image/vnd.radiance';
	private const GLB_EXTENSION                       = 'glb';
	private const GLB_MIME_TYPE                       = 'model/gltf-binary';

	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'upload_mimes', array( $this, 'allow_custom_uploads' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_custom_filetypes' ), 10, 5 );
	}

	public function register_menu(): void {
		add_options_page(
			__( '3D Model Viewer Settings', '3D-Model-Viewer' ),
			__( '3D Model Viewer', '3D-Model-Viewer' ),
			'manage_options',
			'wp3ds-settings',
			array( $this, 'render_page' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'wp3ds_settings',
			self::OPTION_HDRI_MAP_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_hdri_map_url' ),
				'default'           => '',
			)
		);

		register_setting(
			'wp3ds_settings',
			self::OPTION_SELECTION_HIGHLIGHT_COLOR,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_selection_highlight_color' ),
				'default'           => self::DEFAULT_SELECTION_HIGHLIGHT_COLOR,
			)
		);

		register_setting(
			'wp3ds_settings',
			self::OPTION_HOVER_HIGHLIGHT_COLOR,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_hover_highlight_color' ),
				'default'           => self::DEFAULT_HOVER_HIGHLIGHT_COLOR,
			)
		);

		register_setting(
			'wp3ds_settings',
			self::OPTION_SELECTION_GLOW_INTENSITY,
			array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_selection_glow_intensity' ),
				'default'           => self::DEFAULT_SELECTION_GLOW_INTENSITY,
			)
		);

		register_setting(
			'wp3ds_settings',
			self::OPTION_ISOLATE_DIM_OPACITY,
			array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_isolate_dim_opacity' ),
				'default'           => self::DEFAULT_ISOLATE_DIM_OPACITY,
			)
		);

		add_settings_section(
			'wp3ds_shortcode_usage_section',
			__( 'Using the Shortcode', '3D-Model-Viewer' ),
			array( $this, 'render_shortcode_usage_section' ),
			'wp3ds-settings'
		);

		add_settings_section(
			'wp3ds_environment_section',
			__( 'Environment Lighting', '3D-Model-Viewer' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Provide an external direct URL to a .hdr environment map. It is applied globally to every 3D viewer.', '3D-Model-Viewer' ) . '</p>';
			},
			'wp3ds-settings'
		);

		add_settings_field(
			self::OPTION_HDRI_MAP_URL,
			__( 'HDRI Map', '3D-Model-Viewer' ),
			array( $this, 'render_hdri_field' ),
			'wp3ds-settings',
			'wp3ds_environment_section'
		);

		add_settings_section(
			'wp3ds_interaction_section',
			__( 'Part Selection & Focus', '3D-Model-Viewer' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Control hover feedback, the selected-part silhouette, and how strongly the rest of the model fades in Focus mode.', '3D-Model-Viewer' ) . '</p>';
			},
			'wp3ds-settings'
		);

		add_settings_field(
			self::OPTION_SELECTION_HIGHLIGHT_COLOR,
			__( 'Selected Part Silhouette Color', '3D-Model-Viewer' ),
			array( $this, 'render_selection_highlight_color_field' ),
			'wp3ds-settings',
			'wp3ds_interaction_section'
		);

		add_settings_field(
			self::OPTION_HOVER_HIGHLIGHT_COLOR,
			__( 'Hover Color', '3D-Model-Viewer' ),
			array( $this, 'render_hover_highlight_color_field' ),
			'wp3ds-settings',
			'wp3ds_interaction_section'
		);

		add_settings_field(
			self::OPTION_SELECTION_GLOW_INTENSITY,
			__( 'Selection Silhouette Strength', '3D-Model-Viewer' ),
			array( $this, 'render_selection_glow_intensity_field' ),
			'wp3ds-settings',
			'wp3ds_interaction_section'
		);

		add_settings_field(
			self::OPTION_ISOLATE_DIM_OPACITY,
			__( 'Background Part Opacity', '3D-Model-Viewer' ),
			array( $this, 'render_isolate_dim_opacity_field' ),
			'wp3ds-settings',
			'wp3ds_interaction_section'
		);
	}

	public function sanitize_hdri_map_url( $value ): string {
		$url = esc_url_raw( (string) $value );

		if ( '' === $url ) {
			return '';
		}

		if ( Helpers::is_local_url( $url ) || ! Helpers::is_allowed_attachment_extension( $url, self::HDR_EXTENSION ) ) {
			add_settings_error(
				'wp3ds_settings',
				'wp3ds_invalid_hdri',
				__( 'Please provide a valid external .hdr URL.', '3D-Model-Viewer' )
			);

			return $this->get_hdri_map_url();
		}

		return $url;
	}

	public function sanitize_selection_highlight_color( $value ): string {
		return $this->sanitize_hex_color_setting( $value, self::DEFAULT_SELECTION_HIGHLIGHT_COLOR );
	}

	public function sanitize_hover_highlight_color( $value ): string {
		return $this->sanitize_hex_color_setting( $value, self::DEFAULT_HOVER_HIGHLIGHT_COLOR );
	}

	public function sanitize_selection_glow_intensity( $value ): float {
		return $this->sanitize_unit_interval_setting( $value, self::DEFAULT_SELECTION_GLOW_INTENSITY );
	}

	public function sanitize_isolate_dim_opacity( $value ): float {
		return $this->sanitize_unit_interval_setting( $value, self::DEFAULT_ISOLATE_DIM_OPACITY );
	}

	public function render_shortcode_usage_section(): void {
		?>
		<p><?php esc_html_e( 'After you publish a 3D item, embed it in any post or page with the shortcode below.', '3D-Model-Viewer' ); ?></p>
		<p><code>[wp3ds_viewer id="123"]</code></p>
		<p><?php esc_html_e( 'You can also target an item by its slug if you prefer a portable shortcode.', '3D-Model-Viewer' ); ?></p>
		<p><code>[wp3ds_viewer slug="example-item"]</code></p>
		<p><?php esc_html_e( 'The item editor also shows a ready-to-copy shortcode for the current 3D item.', '3D-Model-Viewer' ); ?></p>
		<?php
	}

	public function render_hdri_field(): void {
		$value = $this->get_hdri_map_url();
		?>
		<div class="wp3ds-admin-fields">
			<input type="url" id="wp3ds_hdri_map_url" name="<?php echo esc_attr( self::OPTION_HDRI_MAP_URL ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text widefat" placeholder="https://example.com/path/to/your-map.hdr">
			<p>
				<button type="button" class="button-link-delete" data-clear-media="#wp3ds_hdri_map_url"><?php esc_html_e( 'Clear URL', '3D-Model-Viewer' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Enter an external direct link to a .hdr file. Media Library HDRI selection is disabled.', '3D-Model-Viewer' ); ?></p>
		</div>
		<?php
	}

	public function render_selection_highlight_color_field(): void {
		$this->render_color_field(
			self::OPTION_SELECTION_HIGHLIGHT_COLOR,
			$this->get_selection_highlight_color(),
			__( 'Camera-view silhouette color used when a part is selected.', '3D-Model-Viewer' )
		);
	}

	public function render_hover_highlight_color_field(): void {
		$this->render_color_field(
			self::OPTION_HOVER_HIGHLIGHT_COLOR,
			$this->get_hover_highlight_color(),
			__( 'Used when a part is hovered before it is selected.', '3D-Model-Viewer' )
		);
	}

	public function render_selection_glow_intensity_field(): void {
		?>
		<div class="wp3ds-admin-fields">
			<input type="number" id="<?php echo esc_attr( self::OPTION_SELECTION_GLOW_INTENSITY ); ?>" name="<?php echo esc_attr( self::OPTION_SELECTION_GLOW_INTENSITY ); ?>" value="<?php echo esc_attr( (string) $this->get_selection_glow_intensity() ); ?>" min="0" max="1" step="0.01" class="small-text">
			<p class="description"><?php esc_html_e( 'Controls how strong the selected part silhouette appears. Set 0 to disable it.', '3D-Model-Viewer' ); ?></p>
		</div>
		<?php
	}

	public function render_isolate_dim_opacity_field(): void {
		?>
		<div class="wp3ds-admin-fields">
			<input type="number" id="<?php echo esc_attr( self::OPTION_ISOLATE_DIM_OPACITY ); ?>" name="<?php echo esc_attr( self::OPTION_ISOLATE_DIM_OPACITY ); ?>" value="<?php echo esc_attr( (string) $this->get_isolate_dim_opacity() ); ?>" min="0" max="1" step="0.01" class="small-text">
			<p class="description"><?php esc_html_e( 'Opacity applied to non-selected parts while Focus mode is active. Use 1 to keep all parts fully visible.', '3D-Model-Viewer' ); ?></p>
		</div>
		<?php
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', '3D-Model-Viewer' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( '3D Model Viewer Settings', '3D-Model-Viewer' ); ?></h1>
			<?php settings_errors( 'wp3ds_settings' ); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wp3ds_settings' );
				do_settings_sections( 'wp3ds-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function allow_custom_uploads( array $mimes ): array {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $mimes;
		}

		$mimes[ self::HDR_EXTENSION ] = self::HDR_MIME_TYPE;
		$mimes[ self::GLB_EXTENSION ] = self::GLB_MIME_TYPE;

		return $mimes;
	}

	public function fix_custom_filetypes( array $data, string $file, string $filename, ?array $mimes, $real_mime ): array {
		unset( $file, $mimes, $real_mime );

		$extension = strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) );

		if ( self::HDR_EXTENSION === $extension ) {
			$data['ext']             = self::HDR_EXTENSION;
			$data['type']            = self::HDR_MIME_TYPE;
			$data['proper_filename'] = $filename;
		}

		if ( self::GLB_EXTENSION === $extension ) {
			$data['ext']             = self::GLB_EXTENSION;
			$data['type']            = self::GLB_MIME_TYPE;
			$data['proper_filename'] = $filename;
		}

		return $data;
	}

	public function get_hdri_map_url(): string {
		$url = esc_url_raw( (string) get_option( self::OPTION_HDRI_MAP_URL, '' ) );

		if ( '' === $url || Helpers::is_local_url( $url ) || ! Helpers::is_allowed_attachment_extension( $url, self::HDR_EXTENSION ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * @return array{selectionHighlightColor: string, hoverHighlightColor: string, selectionGlowIntensity: float, isolateDimOpacity: float}
	 */
	public function get_interaction_settings(): array {
		return array(
			'selectionHighlightColor' => $this->get_selection_highlight_color(),
			'hoverHighlightColor'     => $this->get_hover_highlight_color(),
			'selectionGlowIntensity'  => $this->get_selection_glow_intensity(),
			'isolateDimOpacity'       => $this->get_isolate_dim_opacity(),
		);
	}

	private function sanitize_hex_color_setting( $value, string $fallback ): string {
		$sanitized = sanitize_hex_color( (string) $value );

		if ( null === $sanitized ) {
			add_settings_error(
				'wp3ds_settings',
				'wp3ds_invalid_highlight_color',
				__( 'Please provide a valid hex color value.', '3D-Model-Viewer' )
			);

			return $fallback;
		}

		return $sanitized;
	}

	private function sanitize_unit_interval_setting( $value, float $fallback ): float {
		$parsed = is_numeric( $value ) ? (float) $value : $fallback;
		$parsed = max( 0, min( 1, $parsed ) );

		return round( $parsed, 2 );
	}

	private function render_color_field( string $option_name, string $value, string $description ): void {
		?>
		<div class="wp3ds-admin-fields">
			<input type="text" id="<?php echo esc_attr( $option_name ); ?>" name="<?php echo esc_attr( $option_name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="#2f6df6" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$">
			<p class="description"><?php echo esc_html( $description ); ?></p>
		</div>
		<?php
	}

	private function get_selection_highlight_color(): string {
		return sanitize_hex_color( (string) get_option( self::OPTION_SELECTION_HIGHLIGHT_COLOR, self::DEFAULT_SELECTION_HIGHLIGHT_COLOR ) ) ?: self::DEFAULT_SELECTION_HIGHLIGHT_COLOR;
	}

	private function get_hover_highlight_color(): string {
		return sanitize_hex_color( (string) get_option( self::OPTION_HOVER_HIGHLIGHT_COLOR, self::DEFAULT_HOVER_HIGHLIGHT_COLOR ) ) ?: self::DEFAULT_HOVER_HIGHLIGHT_COLOR;
	}

	private function get_selection_glow_intensity(): float {
		return $this->sanitize_unit_interval_setting( get_option( self::OPTION_SELECTION_GLOW_INTENSITY, self::DEFAULT_SELECTION_GLOW_INTENSITY ), self::DEFAULT_SELECTION_GLOW_INTENSITY );
	}

	private function get_isolate_dim_opacity(): float {
		return $this->sanitize_unit_interval_setting( get_option( self::OPTION_ISOLATE_DIM_OPACITY, self::DEFAULT_ISOLATE_DIM_OPACITY ), self::DEFAULT_ISOLATE_DIM_OPACITY );
	}

}
