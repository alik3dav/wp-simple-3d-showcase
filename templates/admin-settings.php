<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap s3ds-admin-wrap">
	<h1><?php esc_html_e( 'Simple 3D Showcase Settings', 'simple-3d-showcase' ); ?></h1>
	<p><?php esc_html_e( 'Global defaults are applied when no showcase or shortcode override is present.', 'simple-3d-showcase' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( 's3ds_settings_group' ); ?>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="viewer_height"><?php esc_html_e( 'Default Viewer Height', 'simple-3d-showcase' ); ?></label></th><td><input name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[viewer_height]" id="viewer_height" value="<?php echo esc_attr( $settings['viewer_height'] ); ?>" class="regular-text" /></td></tr>
			<tr><th scope="row"><label for="background_color"><?php esc_html_e( 'Default Background Color', 'simple-3d-showcase' ); ?></label></th><td><input name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[background_color]" id="background_color" type="text" value="<?php echo esc_attr( $settings['background_color'] ); ?>" class="regular-text" /></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Default Interaction', 'simple-3d-showcase' ); ?></th><td>
				<label><input type="checkbox" name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[auto_rotate]" value="1" <?php checked( $settings['auto_rotate'], 1 ); ?> /> <?php esc_html_e( 'Auto-rotate enabled', 'simple-3d-showcase' ); ?></label><br />
				<label><input type="checkbox" name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[camera_controls]" value="1" <?php checked( $settings['camera_controls'], 1 ); ?> /> <?php esc_html_e( 'Camera controls enabled', 'simple-3d-showcase' ); ?></label>
			</td></tr>
			<tr><th scope="row"><label for="exposure"><?php esc_html_e( 'Default Exposure', 'simple-3d-showcase' ); ?></label></th><td><input name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[exposure]" id="exposure" value="<?php echo esc_attr( $settings['exposure'] ); ?>" class="small-text" /></td></tr>
			<tr><th scope="row"><label for="shadow_intensity"><?php esc_html_e( 'Default Shadow Intensity', 'simple-3d-showcase' ); ?></label></th><td><input name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[shadow_intensity]" id="shadow_intensity" value="<?php echo esc_attr( $settings['shadow_intensity'] ); ?>" class="small-text" /></td></tr>
			<tr><th scope="row"><label for="loading"><?php esc_html_e( 'Default Loading Behavior', 'simple-3d-showcase' ); ?></label></th><td><select name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[loading]" id="loading"><option value="eager" <?php selected( $settings['loading'], 'eager' ); ?>>eager</option><option value="lazy" <?php selected( $settings['loading'], 'lazy' ); ?>>lazy</option><option value="auto" <?php selected( $settings['loading'], 'auto' ); ?>>auto</option></select></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Viewer UI & Kiosk', 'simple-3d-showcase' ); ?></th><td>
				<label><input type="checkbox" name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[fullscreen_enabled]" value="1" <?php checked( $settings['fullscreen_enabled'], 1 ); ?> /> <?php esc_html_e( 'Fullscreen button enabled', 'simple-3d-showcase' ); ?></label><br />
				<label><input type="checkbox" name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[touch_hint_enabled]" value="1" <?php checked( $settings['touch_hint_enabled'], 1 ); ?> /> <?php esc_html_e( 'Touch hint enabled', 'simple-3d-showcase' ); ?></label><br />
				<label><input type="checkbox" name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[kiosk_mode]" value="1" <?php checked( $settings['kiosk_mode'], 1 ); ?> /> <?php esc_html_e( 'Kiosk mode enabled', 'simple-3d-showcase' ); ?></label>
			</td></tr>
			<tr><th scope="row"><label for="brand_label"><?php esc_html_e( 'Brand Label', 'simple-3d-showcase' ); ?></label></th><td><input name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[brand_label]" id="brand_label" value="<?php echo esc_attr( $settings['brand_label'] ); ?>" class="regular-text" /></td></tr>
			<tr><th scope="row"><label for="model_viewer_source"><?php esc_html_e( 'model-viewer Source', 'simple-3d-showcase' ); ?></label></th><td><select name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[model_viewer_source]" id="model_viewer_source"><option value="cdn" <?php selected( $settings['model_viewer_source'], 'cdn' ); ?>><?php esc_html_e( 'CDN (current)', 'simple-3d-showcase' ); ?></option><option value="local" <?php selected( $settings['model_viewer_source'], 'local' ); ?>><?php esc_html_e( 'Local placeholder', 'simple-3d-showcase' ); ?></option></select></td></tr>
			<tr><th scope="row"><label for="debug_mode"><?php esc_html_e( 'Debug Mode', 'simple-3d-showcase' ); ?></label></th><td><label><input type="checkbox" name="<?php echo esc_attr( S3DS_OPTION_KEY ); ?>[debug_mode]" value="1" <?php checked( $settings['debug_mode'], 1 ); ?> /> <?php esc_html_e( 'Enable debug mode', 'simple-3d-showcase' ); ?></label></td></tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
