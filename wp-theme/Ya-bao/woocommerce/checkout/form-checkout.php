<?php
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

// Current delivery scope is Russia only. Keep Woo's country value in the form
// for taxes/shipping/order data, but do not ask the customer to choose it.
if ( WC()->customer ) {
	WC()->customer->set_billing_country( 'RU' );
	WC()->customer->set_shipping_country( 'RU' );
}
?>
<form name="checkout" method="post" class="checkout woocommerce-checkout checkout-layout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="Оформление заказа">
	<div class="checkout-form">
		<?php if ( $checkout->get_checkout_fields() ) : ?>
			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
			<section class="checkout-panel" id="customer_details" aria-labelledby="checkout-contact-title">
				<p class="eyebrow">Покупатель</p>
				<div class="checkout-panel__heading"><h2 id="checkout-contact-title">Контактные данные</h2><span>WooCommerce</span></div>
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
			</section>
			<section class="checkout-panel" aria-labelledby="checkout-shipping-title">
				<p class="eyebrow">Получение</p>
				<div class="checkout-panel__heading"><h2 id="checkout-shipping-title">Доставка и комментарий</h2><a class="checkout-panel__link" href="<?php echo esc_url( yabao_page_url( 'delivery' ) ); ?>">Условия получения</a></div>
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</section>
			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
		<?php endif; ?>
	</div>

	<aside class="checkout-summary" aria-labelledby="order_review_heading">
		<div class="checkout-summary__heading"><div><p class="eyebrow">Заказ</p><h2 id="order_review_heading">Ваш заказ</h2></div><a href="<?php echo esc_url( wc_get_cart_url() ); ?>">Изменить</a></div>
		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
		<div id="order_review" class="woocommerce-checkout-review-order"><?php do_action( 'woocommerce_checkout_order_review' ); ?></div>
		<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
	</aside>
</form>
<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
