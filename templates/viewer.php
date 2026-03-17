<?php
/** @var array $config */
/** @var string $instance_id */
?>
<div id="<?php echo esc_attr( $instance_id ); ?>" class="s3ds-viewer is-loading" data-viewer-state="loading" data-load-error-type="<?php echo esc_attr( $config['load_error_type'] ); ?>" style="--s3ds-height: <?php echo esc_attr( $config['height'] ); ?>; --s3ds-bg: <?php echo esc_attr( $config['background'] ); ?>;">
	<div class="s3ds-brand"><?php echo esc_html( $config['brand_label'] ); ?></div>
	<model-viewer
		id="<?php echo esc_attr( $instance_id ); ?>-model"
		<?php if ( ! empty( $config['model_url'] ) ) : ?>src="<?php echo esc_url( $config['model_url'] ); ?>"<?php endif; ?>
		<?php if ( ! empty( $config['poster_url'] ) ) : ?>poster="<?php echo esc_url( $config['poster_url'] ); ?>"<?php endif; ?>
		data-model-url="<?php echo esc_attr( $config['model_url'] ); ?>"
		data-load-error-type="<?php echo esc_attr( $config['load_error_type'] ); ?>"
		alt="<?php esc_attr_e( '3D model viewer', 'simple-3d-showcase' ); ?>"
		loading="<?php echo esc_attr( $config['loading'] ); ?>"
		reveal="auto"
		environment-image="neutral"
		interaction-prompt="none"
		touch-action="pan-y"
		camera-orbit="45deg 75deg auto"
		field-of-view="30deg"
		exposure="<?php echo esc_attr( $config['exposure'] ); ?>"
		shadow-intensity="<?php echo esc_attr( $config['shadow_intensity'] ); ?>"
		data-kiosk-mode="<?php echo esc_attr( (int) ! empty( $config['kiosk_mode'] ) ); ?>"
		<?php if ( ! empty( $config['auto_rotate'] ) ) : ?>auto-rotate<?php endif; ?>
		<?php if ( ! empty( $config['camera_controls'] ) ) : ?>camera-controls<?php endif; ?>
	></model-viewer>

	<div class="s3ds-viewer-message" role="status" aria-live="polite" data-loading-text="<?php esc_attr_e( 'Loading 3D model…', 'simple-3d-showcase' ); ?>" hidden><?php echo esc_html( $config['load_error_message'] ); ?></div>

	<?php if ( ! empty( $config['touch_hint_enabled'] ) ) : ?>
		<div class="s3ds-hint"><?php echo esc_html( $config['hint_text'] ); ?></div>
	<?php endif; ?>

	<div class="s3ds-controls" aria-label="<?php esc_attr_e( '3D viewer controls', 'simple-3d-showcase' ); ?>">
		<button type="button" class="s3ds-btn js-s3ds-reset" aria-label="<?php echo esc_attr( $config['reset_label'] ); ?>"><?php echo esc_html( $config['reset_label'] ); ?></button>
		<button type="button" class="s3ds-btn js-s3ds-rotate" aria-label="<?php echo esc_attr( $config['rotate_label'] ); ?>" data-label-rotate="<?php echo esc_attr( $config['rotate_label'] ); ?>" data-label-pause="<?php echo esc_attr( $config['pause_label'] ); ?>"><?php echo ! empty( $config['auto_rotate'] ) ? esc_html( $config['pause_label'] ) : esc_html( $config['rotate_label'] ); ?></button>
		<?php if ( ! empty( $config['fullscreen_enabled'] ) ) : ?>
			<button type="button" class="s3ds-btn js-s3ds-fullscreen" aria-label="<?php echo esc_attr( $config['fullscreen_label'] ); ?>"><?php echo esc_html( $config['fullscreen_label'] ); ?></button>
		<?php endif; ?>
	</div>
</div>
