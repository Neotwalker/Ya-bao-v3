<?php
defined( 'ABSPATH' ) || exit;

// update_order_review is rendered over Woo AJAX, so keep zero trimming here too.
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
?>
<div class="woocommerce-checkout-review-order-table">
<div class="checkout-items">
	<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
		$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) { continue; }
		$variant = yabao_cart_item_variant_text( $cart_item );
	?>
	<article class="checkout-item">
		<div class="checkout-item__media"><?php echo $_product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<div class="checkout-item__body"><h3><?php echo esc_html( $_product->get_name() ); ?></h3><p><?php echo $variant ? esc_html( $variant ) : 'Количество: ' . esc_html( (string) $cart_item['quantity'] ) . ' шт.'; ?></p></div>
		<strong class="checkout-item__price"><?php echo WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
	</article>
	<?php endforeach; ?>
</div>

<div class="checkout-summary__row"><span>Товаров</span><strong><?php echo esc_html( (string) WC()->cart->get_cart_contents_count() ); ?></strong></div>
<div class="checkout-summary__row"><span>Товары</span><strong><?php wc_cart_totals_subtotal_html(); ?></strong></div>

<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
<div class="checkout-summary__row coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>"><span>Купон: <?php echo esc_html( wc_cart_totals_coupon_label( $coupon, false ) ); ?></span><strong><?php wc_cart_totals_coupon_html( $coupon ); ?></strong></div>
<?php endforeach; ?>

<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
<div class="checkout-summary__row"><span><?php echo esc_html( $fee->name ); ?></span><strong><?php wc_cart_totals_fee_html( $fee ); ?></strong></div>
<?php endforeach; ?>

<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
	<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
		<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?><div class="checkout-summary__row tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>"><span><?php echo esc_html( $tax->label ); ?></span><strong><?php echo wp_kses_post( $tax->formatted_amount ); ?></strong></div><?php endforeach; ?>
	<?php else : ?><div class="checkout-summary__row tax-total"><span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span><strong><?php wc_cart_totals_taxes_total_html(); ?></strong></div><?php endif; ?>
<?php endif; ?>

<div class="checkout-summary__row checkout-summary__row--total"><span>Итого</span><strong><?php wc_cart_totals_order_total_html(); ?></strong></div>
</div>
