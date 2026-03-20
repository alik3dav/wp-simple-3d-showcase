<?php
/**
 * Meta boxes for 3D items.
 *
 * @package WP3DS
 */

namespace WP3DS\Admin;

use WP3DS\Helpers;
use WP3DS\PostTypes\ShowcasePostType;

defined( 'ABSPATH' ) || exit;

class MetaBoxes {
	private const META_MODEL_ATTACHMENT_ID = '_wp3ds_model_attachment_id';
	private const META_MODEL_URL           = '_wp3ds_model_url';
	private const META_BG_COLOR            = '_wp3ds_bg_color';
	private const META_AUTO_ROTATE         = '_wp3ds_auto_rotate';
	private const META_EXPLODE_STEP        = '_wp3ds_explode_step';
	private const META_EXPLODE_PARTS       = '_wp3ds_explode_parts';

	public function register(): void {
		add_meta_box(
			'wp3ds_model_settings',
			__( '3D Model Settings', 'three-d-showcase' ),
			array( $this, 'render' ),
			ShowcasePostType::POST_TYPE,
			'normal',
			'default'
		);
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'wp3ds_save_meta', 'wp3ds_meta_nonce' );

		$model_attachment_id = absint( get_post_meta( $post->ID, self::META_MODEL_ATTACHMENT_ID, true ) );
		$model_url           = Helpers::get_attachment_url( $model_attachment_id, 'glb' );

		if ( '' === $model_url ) {
			$model_url = $this->get_legacy_local_model_url( $post->ID );
		}

		$bg_color          = get_post_meta( $post->ID, self::META_BG_COLOR, true ) ?: '#f5f5f5';
		$auto_rotate       = get_post_meta( $post->ID, self::META_AUTO_ROTATE, true );
		$explode_step      = get_post_meta( $post->ID, self::META_EXPLODE_STEP, true ) ?: '0.15';
		$explode_parts     = wp_json_encode( Helpers::normalize_explode_parts( get_post_meta( $post->ID, self::META_EXPLODE_PARTS, true ) ) );
		$shortcode_by_id   = sprintf( '[wp3ds_viewer id="%d"]', $post->ID );
		$shortcode_by_slug = '' !== $post->post_name ? sprintf( '[wp3ds_viewer slug="%s"]', $post->post_name ) : '';
		?>
		<div class="wp3ds-admin-fields">
			<div class="notice notice-info inline">
				<p><strong><?php esc_html_e( 'How to embed this 3D item', 'three-d-showcase' ); ?></strong></p>
				<p><?php esc_html_e( 'Paste one of these shortcodes into any post, page, or widget that supports shortcodes.', 'three-d-showcase' ); ?></p>
				<p><code><?php echo esc_html( $shortcode_by_id ); ?></code></p>
				<?php if ( '' !== $shortcode_by_slug ) : ?>
					<p><code><?php echo esc_html( $shortcode_by_slug ); ?></code></p>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'Save or publish this item to generate a slug-based shortcode too.', 'three-d-showcase' ); ?></p>
				<?php endif; ?>
			</div>
			<p>
				<label for="wp3ds_model_url"><strong><?php esc_html_e( 'GLB File', 'three-d-showcase' ); ?></strong></label><br>
				<input type="hidden" id="wp3ds_model_attachment_id" name="wp3ds_model_attachment_id" value="<?php echo esc_attr( (string) $model_attachment_id ); ?>">
				<input type="url" id="wp3ds_model_url" name="wp3ds_model_url" value="<?php echo esc_attr( $model_url ); ?>" class="widefat" readonly>
			</p>
			<p>
				<button type="button" class="button" data-media-target="#wp3ds_model_url" data-media-id-target="#wp3ds_model_attachment_id" data-allowed-extension="glb" data-media-title="<?php echo esc_attr__( 'Select GLB File', 'three-d-showcase' ); ?>" data-media-button="<?php echo esc_attr__( 'Use this GLB file', 'three-d-showcase' ); ?>"><?php esc_html_e( 'Select GLB', 'three-d-showcase' ); ?></button>
				<button type="button" class="button-link-delete" data-clear-media="#wp3ds_model_url" data-clear-media-id="#wp3ds_model_attachment_id"><?php esc_html_e( 'Remove file', 'three-d-showcase' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Only self-hosted .glb files from the WordPress Media Library are accepted.', 'three-d-showcase' ); ?></p>

			<p>
				<label for="wp3ds_bg_color"><strong><?php esc_html_e( 'Background Color', 'three-d-showcase' ); ?></strong></label><br>
				<input type="color" id="wp3ds_bg_color" name="wp3ds_bg_color" value="<?php echo esc_attr( $bg_color ); ?>">
			</p>

			<p>
				<label for="wp3ds_explode_step"><strong><?php esc_html_e( 'Explode Strength', 'three-d-showcase' ); ?></strong></label><br>
				<input type="number" step="0.01" min="0" max="5" id="wp3ds_explode_step" name="wp3ds_explode_step" value="<?php echo esc_attr( (string) $explode_step ); ?>">
			</p>

			<p>
				<label>
					<input type="checkbox" name="wp3ds_auto_rotate" value="1" <?php checked( $auto_rotate, '1' ); ?>>
					<?php esc_html_e( 'Enable auto-rotate by default', 'three-d-showcase' ); ?>
				</label>
			</p>

			<div class="wp3ds-explode-parts" data-explode-parts="<?php echo esc_attr( $explode_parts ?: '[]' ); ?>">
				<input type="hidden" id="wp3ds_explode_parts" name="wp3ds_explode_parts" value="<?php echo esc_attr( $explode_parts ?: '[]' ); ?>">
				<div class="wp3ds-explode-parts__header">
					<strong><?php esc_html_e( 'Explode View Parts', 'three-d-showcase' ); ?></strong>
					<p class="description"><?php esc_html_e( 'Mesh parts are detected automatically from the GLB model. Fine-tune the explode direction and the part details shown in the front-end info card.', 'three-d-showcase' ); ?></p>
				</div>
				<div class="wp3ds-explode-parts__status" data-parts-status><?php esc_html_e( 'Select a GLB file to detect model parts.', 'three-d-showcase' ); ?></div>
				<div class="wp3ds-explode-parts__table-wrap" data-parts-list hidden></div>
			</div>
		</div>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['wp3ds_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp3ds_meta_nonce'] ) ), 'wp3ds_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$model_attachment_id = isset( $_POST['wp3ds_model_attachment_id'] ) ? Helpers::resolve_local_attachment_id( wp_unslash( $_POST['wp3ds_model_attachment_id'] ), 'glb' ) : 0;
		$model_url           = Helpers::get_attachment_url( $model_attachment_id, 'glb' );
		$bg_color            = isset( $_POST['wp3ds_bg_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['wp3ds_bg_color'] ) ) : '#f5f5f5';
		$explode_step        = isset( $_POST['wp3ds_explode_step'] ) ? max( 0, min( 5, (float) wp_unslash( $_POST['wp3ds_explode_step'] ) ) ) : 0.15;
		$auto_rotate         = isset( $_POST['wp3ds_auto_rotate'] ) ? '1' : '0';
		$explode_parts       = isset( $_POST['wp3ds_explode_parts'] ) ? wp_json_encode( Helpers::normalize_explode_parts( wp_unslash( $_POST['wp3ds_explode_parts'] ) ) ) : '[]';

		update_post_meta( $post_id, self::META_MODEL_ATTACHMENT_ID, $model_attachment_id );
		update_post_meta( $post_id, self::META_MODEL_URL, $model_url );
		update_post_meta( $post_id, self::META_BG_COLOR, $bg_color ?: '#f5f5f5' );
		update_post_meta( $post_id, self::META_EXPLODE_STEP, (string) round( $explode_step, 2 ) );
		update_post_meta( $post_id, self::META_AUTO_ROTATE, $auto_rotate );
		update_post_meta( $post_id, self::META_EXPLODE_PARTS, $explode_parts ?: '[]' );
	}

	private function get_legacy_local_model_url( int $post_id ): string {
		$legacy_url     = (string) get_post_meta( $post_id, self::META_MODEL_URL, true );
		$attachment_id  = Helpers::resolve_local_attachment_id( $legacy_url, 'glb' );
		$attachment_url = Helpers::get_attachment_url( $attachment_id, 'glb' );

		if ( $attachment_id > 0 && '' !== $attachment_url ) {
			update_post_meta( $post_id, self::META_MODEL_ATTACHMENT_ID, $attachment_id );
			update_post_meta( $post_id, self::META_MODEL_URL, $attachment_url );
			return $attachment_url;
		}

		return '';
	}
}
