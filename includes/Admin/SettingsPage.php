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
	private const OPTION_HDRI_MAP_ATTACHMENT_ID       = 'wp3ds_hdri_map_attachment_id';
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
			__( 'WP 3D Showcase Settings', 'wp-3d-showcase' ),
			__( 'WP 3D Showcase', 'wp-3d-showcase' ),
			'manage_options',
			'wp3ds-settings',
			array( $this, 'render_page' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'wp3ds_settings',
			self::OPTION_HDRI_MAP_ATTACHMENT_ID,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_hdri_map_attachment_id' ),
				'default'           => 0,
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
			'wp3ds_environment_section',
			__( 'Environment Lighting', 'wp-3d-showcase' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Choose a self-hosted HDRI file from the WordPress Media Library. It is applied globally to every 3D viewer.', 'wp-3d-showcase' ) . '</p>';
			},
			'wp3ds-settings'
		);

		add_settings_field(
			self::OPTION_HDRI_MAP_ATTACHMENT_ID,
			__( 'HDRI Map', 'wp-3d-showcase' ),
			array( $this, 'render_hdri_field' ),
			'wp3ds-settings',
			'wp3ds_environment_section'
		);

		add_settings_section(
			'wp3ds_interaction_section',
			__( 'Part Selection & Focus', 'wp-3d-showcase' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Control hover feedback, the selected-part silhouette, and how strongly the rest of the model fades in Focus mode.', 'wp-3d-showcase' ) . '</p>';
			},
			'wp3ds-settings'
		);

		add_settings_field(
			self::OPTION_SELECTION_HIGHLIGHT_COLOR,
			__( 'Selected Part Silhouette Color', 'wp-3d-showcase' ),
			array( $this, 'render_selection_highlight_color_field' ),
			'wp3ds-settings',
			'wp3ds_interaction_section'
		);

		add_settings_field(
			self::OPTION_HOVER_HIGHLIGHT_COLOR,
			__( 'Hover Color', 'wp-3d-showcase' ),
			array( $this, 'render_hover_highlight_color_field' ),
			'wp3ds-settings',
			'wp3ds_interaction_section'
		);

		add_settings_field(
			self::OPTION_SELECTION_GLOW_INTENSITY,
			__( 'Selection Silhouette Strength', 'wp-3d-showcase' ),
			array( $this, 'render_selection_glow_intensity_field' ),
			'wp3ds-settings',
			'wp3ds_interaction_section'
		);

		add_settings_field(
			self::OPTION_ISOLATE_DIM_OPACITY,
			__( 'Background Part Opacity', 'wp-3d-showcase' ),
			array( $this, 'render_isolate_dim_opacity_field' ),
			'wp3ds-settings',
			'wp3ds_interaction_section'
		);
	}

	public function sanitize_hdri_map_attachment_id( $value ): int {
		$attachment_id = Helpers::resolve_local_attachment_id( $value, self::HDR_EXTENSION );

		if ( $attachment_id > 0 ) {
			return $attachment_id;
		}

		if ( empty( $value ) ) {
			return 0;
		}

		add_settings_error(
			'wp3ds_settings',
			'wp3ds_invalid_hdri',
			__( 'Please choose a .hdr file from the Media Library.', 'wp-3d-showcase' )
		);

		return $this->get_hdri_map_attachment_id();
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

	public function render_hdri_field(): void {
		$value          = $this->get_hdri_map_url();
		$attachment_id  = $this->get_hdri_map_attachment_id();
		?>
		<div class="wp3ds-admin-fields wp3ds-admin-media-field">
			<input type="hidden" id="wp3ds_hdri_map_attachment_id" name="<?php echo esc_attr( self::OPTION_HDRI_MAP_ATTACHMENT_ID ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
			<input type="url" id="wp3ds_hdri_map_url" value="<?php echo esc_attr( $value ); ?>" class="regular-text widefat" readonly>
			<p>
				<button type="button" class="button" data-media-target="#wp3ds_hdri_map_url" data-media-id-target="#wp3ds_hdri_map_attachment_id" data-allowed-extension="hdr" data-media-title="<?php echo esc_attr__( 'Select HDRI Map', 'wp-3d-showcase' ); ?>" data-media-button="<?php echo esc_attr__( 'Use this HDRI map', 'wp-3d-showcase' ); ?>"><?php esc_html_e( 'Select HDRI', 'wp-3d-showcase' ); ?></button>
				<button type="button" class="button-link-delete" data-clear-media="#wp3ds_hdri_map_url" data-clear-media-id="#wp3ds_hdri_map_attachment_id"><?php esc_html_e( 'Remove file', 'wp-3d-showcase' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Only self-hosted .hdr files from the WordPress Media Library are accepted.', 'wp-3d-showcase' ); ?></p>
		</div>
		<?php
	}

	public function render_selection_highlight_color_field(): void {
		$this->render_color_field(
			self::OPTION_SELECTION_HIGHLIGHT_COLOR,
			$this->get_selection_highlight_color(),
			__( 'Camera-view silhouette color used when a part is selected.', 'wp-3d-showcase' )
		);
	}

	public function render_hover_highlight_color_field(): void {
		$this->render_color_field(
			self::OPTION_HOVER_HIGHLIGHT_COLOR,
			$this->get_hover_highlight_color(),
			__( 'Used when a part is hovered before it is selected.', 'wp-3d-showcase' )
		);
	}

	public function render_selection_glow_intensity_field(): void {
		?>
		<div class="wp3ds-admin-fields">
			<input type="number" id="<?php echo esc_attr( self::OPTION_SELECTION_GLOW_INTENSITY ); ?>" name="<?php echo esc_attr( self::OPTION_SELECTION_GLOW_INTENSITY ); ?>" value="<?php echo esc_attr( (string) $this->get_selection_glow_intensity() ); ?>" min="0" max="1" step="0.01" class="small-text">
			<p class="description"><?php esc_html_e( 'Controls how strong the selected part silhouette appears. Set 0 to disable it.', 'wp-3d-showcase' ); ?></p>
		</div>
		<?php
	}

	public function render_isolate_dim_opacity_field(): void {
		?>
		<div class="wp3ds-admin-fields">
			<input type="number" id="<?php echo esc_attr( self::OPTION_ISOLATE_DIM_OPACITY ); ?>" name="<?php echo esc_attr( self::OPTION_ISOLATE_DIM_OPACITY ); ?>" value="<?php echo esc_attr( (string) $this->get_isolate_dim_opacity() ); ?>" min="0" max="1" step="0.01" class="small-text">
			<p class="description"><?php esc_html_e( 'Opacity applied to non-selected parts while Focus mode is active. Use 1 to keep all parts fully visible.', 'wp-3d-showcase' ); ?></p>
		</div>
		<?php
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'wp-3d-showcase' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP 3D Showcase Settings', 'wp-3d-showcase' ); ?></h1>
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
		if ( ! current_user_can( 'upload_files' ) || ! $this->is_current_upload_for_custom_files() ) {
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

	public function get_hdri_map_attachment_id(): int {
		return absint( get_option( self::OPTION_HDRI_MAP_ATTACHMENT_ID, 0 ) );
	}

	public function get_hdri_map_url(): string {
		return Helpers::get_attachment_url( $this->get_hdri_map_attachment_id(), self::HDR_EXTENSION );
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
				__( 'Please provide a valid hex color value.', 'wp-3d-showcase' )
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

	private function is_current_upload_for_custom_files(): bool {
		$extensions = $this->get_uploaded_file_extensions( $_FILES ?? array() );

		return in_array( self::HDR_EXTENSION, $extensions, true ) || in_array( self::GLB_EXTENSION, $extensions, true );
	}

	/**
	 * @param array<string, mixed> $files Uploaded files array.
	 * @return string[]
	 */
	private function get_uploaded_file_extensions( array $files ): array {
		$extensions = array();

		array_walk_recursive(
			$files,
			static function ( $value, $key ) use ( &$extensions ): void {
				if ( 'name' !== $key || ! is_string( $value ) ) {
					return;
				}

				$extension = strtolower( (string) pathinfo( $value, PATHINFO_EXTENSION ) );

				if ( '' !== $extension ) {
					$extensions[] = $extension;
				}
			}
		);

		return array_values( array_unique( $extensions ) );
	}
}
