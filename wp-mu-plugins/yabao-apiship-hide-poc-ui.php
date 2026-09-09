<?php
/**
 * Temporary Stage 68.1 visual-QA cleanup.
 *
 * The permanent Ya Bao × ApiShip adapter currently contains a diagnostic PoC
 * panel that replays ApiShip's stock after-rate controls. Keep calculation and
 * rate logic active, but hide that diagnostic panel for final checkout visual
 * QA. This MU file is temporary and must be removed/consolidated during the
 * Stage 68.1 code audit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function yabao_apiship_hide_poc_ui(): void {
	remove_action( 'woocommerce_review_order_before_payment', 'yabao_apiship_render_poc_controls', 5 );
	remove_action( 'wp_enqueue_scripts', 'yabao_apiship_enqueue_poc_styles', 80 );
}
add_action( 'wp_loaded', 'yabao_apiship_hide_poc_ui', 20 );
