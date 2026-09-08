<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_price_trim_zeros', '__return_true' );

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
<style id="yabao-checkout-v045">
.page-checkout #billing_country_field,
.page-checkout #shipping_country_field{display:none!important}

/* Woo inserts validation notices as a direct child of the two-column checkout grid. */
.page-checkout .checkout-layout>.woocommerce-NoticeGroup-checkout{
	grid-column:1/-1;
	min-width:0;
	margin:0
}

/* Coupon: compact toggle + one-row desktop form, stacked mobile form. */
.page-checkout .woocommerce-form-coupon-toggle{margin:0 0 12px}
.page-checkout .woocommerce-form-coupon-toggle .woocommerce-info{
	display:flex;
	align-items:center;
	justify-content:space-between;
	gap:16px;
	margin:0;
	padding:14px 16px;
	border:1px solid var(--color-line);
	border-radius:var(--radius-sm);
	background:rgba(255,255,255,.34)
}
.page-checkout .woocommerce-form-coupon-toggle .showcoupon{
	flex:0 0 auto;
	margin-left:auto;
	color:var(--color-walnut);
	font-weight:700;
	text-decoration:underline;
	text-underline-offset:3px
}
.page-checkout form.checkout_coupon.woocommerce-form-coupon{
	margin:0 0 18px;
	padding:12px;
	border:1px solid var(--color-line);
	border-radius:var(--radius-sm);
	background:rgba(255,255,255,.34)
}
.page-checkout form.checkout_coupon.woocommerce-form-coupon:not([style*="display:none"]):not([style*="display: none"]){
	display:grid!important;
	grid-template-columns:minmax(0,1fr) auto;
	gap:10px;
	align-items:stretch
}
.page-checkout form.checkout_coupon.woocommerce-form-coupon .form-row{
	float:none;
	width:auto;
	margin:0
}
.page-checkout form.checkout_coupon.woocommerce-form-coupon .clear{display:none}
.page-checkout form.checkout_coupon.woocommerce-form-coupon input.input-text{
	height:54px;
	min-height:54px;
	padding:0 14px;
	border:1px solid var(--color-line);
	border-radius:var(--radius-sm);
	background:var(--color-ivory)
}
.page-checkout form.checkout_coupon.woocommerce-form-coupon input.input-text:focus{
	outline:0;
	border-color:var(--color-walnut);
	box-shadow:0 0 0 3px rgba(95,70,48,.10)
}
.page-checkout form.checkout_coupon.woocommerce-form-coupon button.button{
	display:inline-flex;
	align-items:center;
	justify-content:center;
	min-height:54px;
	padding:14px 24px;
	border:1px solid transparent;
	border-radius:var(--radius-sm);
	background:var(--color-walnut);
	color:var(--color-white);
	font:inherit;
	font-size:14px;
	font-weight:750;
	line-height:1.2;
	text-align:center;
	cursor:pointer;
	transition:background var(--duration),color var(--duration),border-color var(--duration),transform var(--duration),box-shadow var(--duration)
}
.page-checkout form.checkout_coupon.woocommerce-form-coupon button.button:hover{
	background:var(--color-walnut-dark)
}

/* Current checkout uses billing address as the delivery address.
   The Woo shipping container is empty here, so keep it out of the visual flow. */
.page-checkout .checkout-panel[aria-labelledby="checkout-shipping-title"] .woocommerce-shipping-fields{display:none}
.page-checkout .checkout-panel[aria-labelledby="checkout-shipping-title"] .woocommerce-additional-fields{margin-top:20px}
.page-checkout .checkout-panel[aria-labelledby="checkout-shipping-title"] .woocommerce-additional-fields>h3{
	margin:0 0 14px
}

@media(max-width:640px){
	.page-checkout .woocommerce-form-coupon-toggle .woocommerce-info{
		align-items:flex-start;
		flex-direction:column;
		gap:7px;
		padding:12px
	}
	.page-checkout .woocommerce-form-coupon-toggle .showcoupon{margin-left:0}
	.page-checkout form.checkout_coupon.woocommerce-form-coupon{
		padding:10px
	}
	.page-checkout form.checkout_coupon.woocommerce-form-coupon:not([style*="display:none"]):not([style*="display: none"]){
		grid-template-columns:1fr
	}
	.page-checkout form.checkout_coupon.woocommerce-form-coupon input.input-text{
		height:46px;
		min-height:46px
	}
	.page-checkout form.checkout_coupon.woocommerce-form-coupon button.button{
		width:100%;
		min-height:46px;
		padding:11px 16px
	}
	.page-checkout .checkout-panel[aria-labelledby="checkout-shipping-title"] .checkout-panel__heading{
		align-items:flex-start;
		flex-wrap:wrap;
		row-gap:7px
	}
	.page-checkout .checkout-panel[aria-labelledby="checkout-shipping-title"] .woocommerce-additional-fields{
		margin-top:16px
	}
	.page-checkout .checkout-panel[aria-labelledby="checkout-shipping-title"] .woocommerce-additional-fields>h3{
		margin-bottom:12px
	}
}
</style>
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
