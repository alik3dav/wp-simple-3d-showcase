<?php
/**
 * Uninstall handler.
 *
 * @package WP3DS
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'wp3ds_hdri_map_attachment_id' );
delete_option( 'wp3ds_selection_highlight_color' );
delete_option( 'wp3ds_hover_highlight_color' );
delete_option( 'wp3ds_selection_glow_intensity' );
delete_option( 'wp3ds_isolate_dim_opacity' );
