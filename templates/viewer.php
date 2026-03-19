<?php
/**
 * Viewer template.
 *
 * @package WP3DS
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	class="wp3ds-viewer"
	style="height: <?php echo esc_attr( $height ); ?>;"
	data-model-url="<?php echo esc_url( $model_url ); ?>"
	data-bg-color="<?php echo esc_attr( $bg_color ); ?>"
	data-auto-rotate="<?php echo esc_attr( $auto_rotate ? 'true' : 'false' ); ?>"
	data-explode-step="<?php echo esc_attr( $explode_step ); ?>"
	data-explode-parts="<?php echo esc_attr( $explode_parts ); ?>"
	data-hdri-map-url="<?php echo esc_url( $hdri_map_url ); ?>"
	data-selection-highlight-color="<?php echo esc_attr( $interaction_settings['selectionHighlightColor'] ); ?>"
	data-hover-highlight-color="<?php echo esc_attr( $interaction_settings['hoverHighlightColor'] ); ?>"
	data-selection-glow-intensity="<?php echo esc_attr( (string) $interaction_settings['selectionGlowIntensity'] ); ?>"
	data-isolate-dim-opacity="<?php echo esc_attr( (string) $interaction_settings['isolateDimOpacity'] ); ?>"
>
	<div class="wp3ds-toolbar" aria-label="<?php echo esc_attr( $controls_aria_label ); ?>">
		<button type="button" data-action="reset" aria-label="<?php echo esc_attr( $reset_aria_label ); ?>"><?php echo esc_html( $reset_label ); ?></button>
		<button type="button" data-action="autorotate" aria-label="<?php echo esc_attr( $rotate_aria_label ); ?>"><?php echo esc_html( $rotate_label ); ?></button>
		<button type="button" data-action="explode" aria-label="<?php echo esc_attr( $explode_aria_label ); ?>"><?php echo esc_html( $explode_label ); ?></button>
		<button type="button" data-action="isolate" aria-label="<?php echo esc_attr( $focus_aria_label ); ?>"><?php echo esc_html( $focus_label ); ?></button>
		<button type="button" data-action="fullscreen" aria-label="<?php echo esc_attr( $fullscreen_aria_label ); ?>"><?php echo esc_html( $fullscreen_label ); ?></button>
	</div>

	<div class="wp3ds-canvas-wrap">
		<div class="wp3ds-model-name" aria-label="<?php echo esc_attr( $model_name_aria_label ); ?>"><?php echo esc_html( $model_name ); ?></div>
		<canvas></canvas>
		<div class="wp3ds-loading"><?php echo esc_html( $loading_label ); ?></div>
		<div class="wp3ds-plugin-meta" aria-label="<?php echo esc_attr( $plugin_meta_aria_label ); ?>"><?php echo esc_html( $plugin_label ); ?></div>
		<div class="wp3ds-part-modal" data-part-modal hidden>
			<button type="button" class="wp3ds-part-modal__close" data-action="close-part-modal" aria-label="<?php echo esc_attr( $close_aria_label ); ?>">×</button>
			<div class="wp3ds-part-modal__eyebrow"><?php echo esc_html( $part_details_eyebrow ); ?></div>
			<h3 class="wp3ds-part-modal__title" data-part-title><?php echo esc_html( $select_part_label ); ?></h3>
			<p class="wp3ds-part-modal__description" data-part-description><?php echo esc_html( $part_details_description ); ?></p>
			<div class="wp3ds-part-modal__meta">
				<div class="wp3ds-part-modal__section" data-part-characteristics-section hidden>
					<div class="wp3ds-part-modal__label"><?php echo esc_html( $characteristics_label ); ?></div>
					<ul class="wp3ds-part-modal__list" data-part-characteristics></ul>
				</div>
				<div class="wp3ds-part-modal__section">
					<div class="wp3ds-part-modal__label"><?php echo esc_html( $mesh_key_label ); ?></div>
					<code class="wp3ds-part-modal__code" data-part-key>—</code>
				</div>
			</div>
		</div>
	</div>
</div>
