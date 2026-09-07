<?php
/**
 * Stage 65 — product data contract → WooCommerce mapping helpers.
 *
 * This file does not import or mutate products. Stage 66 will consume these
 * rules from the importer/sync layer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_META_EXTERNAL_ID       = '_yabao_external_id';
const YABAO_META_SOURCE_TYPE       = '_yabao_type';
const YABAO_META_SOURCE_STATUS     = '_yabao_source_status';
const YABAO_META_SALE_MODE         = '_yabao_sale_mode';
const YABAO_META_TEA_FORM          = '_yabao_tea_form';
const YABAO_META_MIN_WEIGHT_G      = '_yabao_min_weight_g';
const YABAO_META_SOURCE_PRICE      = '_yabao_source_price';
const YABAO_META_SOURCE_UPDATED_AT = '_yabao_source_updated_at';
const YABAO_META_IS_DEMO           = '_yabao_is_demo';
const YABAO_META_DEMO_NOTE         = '_yabao_demo_note';
const YABAO_META_VARIANT_ID        = '_yabao_variant_id';

const YABAO_WEIGHT_ATTRIBUTE_SLUG  = 'weight';
const YABAO_WEIGHT_ATTRIBUTE_LABEL = 'Вес';

/**
 * Contract statuses that remain visible in the storefront.
 */
function yabao_mapping_is_public_status( string $status ): bool {
	return in_array( $status, array( 'active', 'out_of_stock' ), true );
}

/**
 * Source status → WordPress post status.
 *
 * missing_from_feed is deliberately preserved as draft rather than deleted.
 */
function yabao_mapping_post_status( string $status ): string {
	return yabao_mapping_is_public_status( $status ) ? 'publish' : 'draft';
}

/**
 * Contract stock status → WooCommerce stock status.
 */
function yabao_mapping_stock_status( string $stock_status ): string {
	return 'in_stock' === $stock_status ? 'instock' : 'outofstock';
}

/**
 * Decide whether a contract item becomes a simple or variable Woo product.
 */
function yabao_mapping_product_type( array $product ): string {
	$sale_mode = isset( $product['sale_mode'] ) ? (string) $product['sale_mode'] : '';
	$variants  = isset( $product['variants'] ) && is_array( $product['variants'] ) ? $product['variants'] : array();

	if ( 'weight' === $sale_mode || ( 'unit' === $sale_mode && ! empty( $variants ) ) ) {
		return 'variable';
	}

	return 'simple';
}

/**
 * Stable label/slug for the global WooCommerce weight attribute.
 */
function yabao_mapping_weight_label( int $weight_g ): string {
	return max( 0, $weight_g ) . ' г';
}

function yabao_mapping_weight_term_slug( int $weight_g ): string {
	return max( 0, $weight_g ) . '-g';
}

/**
 * ACF field names reserved for editorial content.
 *
 * The importer must not overwrite non-empty editorial fields after initial
 * creation unless an explicit future sync policy says otherwise.
 */
function yabao_mapping_editorial_fields(): array {
	return array(
		'seo_title',
		'meta_description',
		'long_description',
		'taste',
		'aroma',
		'brewing',
		'related_content',
	);
}

/**
 * Machine-readable summary used by Stage 66 importer tests.
 */
function yabao_product_mapping_contract(): array {
	return array(
		'identity' => array(
			'external_id' => YABAO_META_EXTERNAL_ID,
			'sku'         => 'woocommerce_sku',
			'variant_id'  => YABAO_META_VARIANT_ID,
		),
		'classification' => array(
			'type'        => YABAO_META_SOURCE_TYPE,
			'sale_mode'   => YABAO_META_SALE_MODE,
			'category'    => 'product_cat',
			'tea_form'    => YABAO_META_TEA_FORM,
			'min_weight_g'=> YABAO_META_MIN_WEIGHT_G,
		),
		'commerce' => array(
			'price'        => 'woocommerce_price_or_variation_price',
			'stock_status' => 'woocommerce_stock_status',
			'quantity'     => 'woocommerce_stock_quantity',
			'weight'       => 'pa_' . YABAO_WEIGHT_ATTRIBUTE_SLUG,
		),
		'source_state' => array(
			'status'     => YABAO_META_SOURCE_STATUS,
			'updated_at' => YABAO_META_SOURCE_UPDATED_AT,
			'is_demo'    => YABAO_META_IS_DEMO,
			'demo_note'  => YABAO_META_DEMO_NOTE,
		),
		'editorial' => yabao_mapping_editorial_fields(),
	);
}
