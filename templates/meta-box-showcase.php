<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$shortcode = sprintf( '[simple_3d_showcase id="%d"]', (int) $post->ID );
?>
<div class="s3ds-meta-grid">
	<p><label><?php esc_html_e( 'Model URL (GLB/GLTF)', 'simple-3d-showcase' ); ?><br /><input type="url" class="widefat s3ds-media-url" name="s3ds[model_url]" value="<?php echo esc_attr( $data['model_url'] ?? '' ); ?>" /></label> <button type="button" class="button s3ds-media-pick" data-target="model_url"><?php esc_html_e( 'Select from Media', 'simple-3d-showcase' ); ?></button></p>
	<p><label><?php esc_html_e( 'Poster Image URL', 'simple-3d-showcase' ); ?><br /><input type="url" class="widefat s3ds-media-url" name="s3ds[poster_url]" value="<?php echo esc_attr( $data['poster_url'] ?? '' ); ?>" /></label> <button type="button" class="button s3ds-media-pick" data-target="poster_url"><?php esc_html_e( 'Select Poster', 'simple-3d-showcase' ); ?></button></p>
	<p><label><?php esc_html_e( 'Height Override', 'simple-3d-showcase' ); ?><input type="text" class="widefat" name="s3ds[height]" value="<?php echo esc_attr( $data['height'] ?? '' ); ?>" placeholder="600px" /></label></p>
	<p><label><?php esc_html_e( 'Background Override', 'simple-3d-showcase' ); ?><input type="text" class="widefat" name="s3ds[background]" value="<?php echo esc_attr( $data['background'] ?? '' ); ?>" placeholder="#dbdbdb" /></label></p>
	<p><label><?php esc_html_e( 'Auto-Rotate Override', 'simple-3d-showcase' ); ?><select name="s3ds[auto_rotate]" class="widefat"><option value=""><?php esc_html_e( 'Use global default', 'simple-3d-showcase' ); ?></option><option value="true" <?php selected( $data['auto_rotate'] ?? '', 'true' ); ?>>true</option><option value="false" <?php selected( $data['auto_rotate'] ?? '', 'false' ); ?>>false</option></select></label></p>
	<p><label><?php esc_html_e( 'Camera Controls Override', 'simple-3d-showcase' ); ?><select name="s3ds[camera_controls]" class="widefat"><option value=""><?php esc_html_e( 'Use global default', 'simple-3d-showcase' ); ?></option><option value="true" <?php selected( $data['camera_controls'] ?? '', 'true' ); ?>>true</option><option value="false" <?php selected( $data['camera_controls'] ?? '', 'false' ); ?>>false</option></select></label></p>
	<p><label><?php esc_html_e( 'Exposure Override', 'simple-3d-showcase' ); ?><input type="number" step="0.1" class="widefat" name="s3ds[exposure]" value="<?php echo esc_attr( $data['exposure'] ?? '' ); ?>" /></label></p>
	<p><label><?php esc_html_e( 'Shadow Override', 'simple-3d-showcase' ); ?><input type="number" step="0.1" class="widefat" name="s3ds[shadow_intensity]" value="<?php echo esc_attr( $data['shadow_intensity'] ?? '' ); ?>" /></label></p>
	<p><label><?php esc_html_e( 'Hint Text Override', 'simple-3d-showcase' ); ?><input type="text" class="widefat" name="s3ds[hint_text]" value="<?php echo esc_attr( $data['hint_text'] ?? '' ); ?>" /></label></p>
	<p><label><?php esc_html_e( 'Reset Button Label', 'simple-3d-showcase' ); ?><input type="text" class="widefat" name="s3ds[reset_label]" value="<?php echo esc_attr( $data['reset_label'] ?? '' ); ?>" /></label></p>
	<p><label><?php esc_html_e( 'Rotate Button Label', 'simple-3d-showcase' ); ?><input type="text" class="widefat" name="s3ds[rotate_label]" value="<?php echo esc_attr( $data['rotate_label'] ?? '' ); ?>" /></label></p>
	<p><label><?php esc_html_e( 'Pause Button Label', 'simple-3d-showcase' ); ?><input type="text" class="widefat" name="s3ds[pause_label]" value="<?php echo esc_attr( $data['pause_label'] ?? '' ); ?>" /></label></p>
	<p><label><?php esc_html_e( 'Fullscreen Label', 'simple-3d-showcase' ); ?><input type="text" class="widefat" name="s3ds[fullscreen_label]" value="<?php echo esc_attr( $data['fullscreen_label'] ?? '' ); ?>" /></label></p>
	<p><label><?php esc_html_e( 'Status', 'simple-3d-showcase' ); ?><select name="s3ds[status]" class="widefat"><option value="active" <?php selected( $data['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'simple-3d-showcase' ); ?></option><option value="inactive" <?php selected( $data['status'] ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'simple-3d-showcase' ); ?></option></select></label></p>
</div>
<p><strong><?php esc_html_e( 'Shortcode', 'simple-3d-showcase' ); ?>:</strong> <input type="text" readonly class="regular-text code" value="<?php echo esc_attr( $shortcode ); ?>" onclick="this.select();" /></p>
