<?php
/**
 * Plugin Name: Ya Bao Commerce Core
 * Description: Theme-independent WooCommerce order and delivery-payment lifecycle for Ya Bao Zavari.
 * Version: 0.1.2
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Text Domain: yabao-commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_COMMERCE_CORE_VERSION = '0.1.2';

/**
 * The current ApiShip integration is classic-checkout only. HPOS is supported,
 * while Cart/Checkout Blocks are deliberately declared incompatible.
 */
function yabao_commerce_declare_compatibility(): void {
	$features_util = '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil';
	if ( ! class_exists( $features_util ) ) {
		return;
	}

	$features_util::declare_compatibility( 'custom_order_tables', __FILE__, true );
	$features_util::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
}
add_action( 'before_woocommerce_init', 'yabao_commerce_declare_compatibility' );

function yabao_commerce_threshold(): float {
	return defined( 'YABAO_FREE_SHIPPING_THRESHOLD' ) ? (float) YABAO_FREE_SHIPPING_THRESHOLD : 5000.0;
}

function yabao_commerce_cart_goods_total(): float {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}
	return max( 0.0, (float) WC()->cart->get_cart_contents_total() );
}

function yabao_commerce_is_pickup_method( string $method_id ): bool {
	return str_starts_with( $method_id, 'yabao_pickup' ) || str_contains( $method_id, 'local_pickup' );
}

function yabao_commerce_is_manual_carrier_method( string $method_id ): bool {
	return str_starts_with( $method_id, 'yabao_delivery_' );
}

function yabao_commerce_selected_method_id(): string {
	if ( isset( $_POST['shipping_method'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$methods = (array) wp_unslash( $_POST['shipping_method'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$first   = reset( $methods );
		if ( is_string( $first ) && '' !== $first ) {
			return wc_clean( $first );
		}
	}

	if ( isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted = array();
		parse_str( wp_unslash( $_POST['post_data'] ), $posted ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$methods = isset( $posted['shipping_method'] ) ? (array) $posted['shipping_method'] : array();
		$first   = reset( $methods );
		if ( is_string( $first ) && '' !== $first ) {
			return wc_clean( $first );
		}
	}

	if ( function_exists( 'WC' ) && WC()->session ) {
		$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
		if ( isset( $chosen[0] ) ) {
			return wc_clean( (string) $chosen[0] );
		}
	}

	return '';
}

function yabao_commerce_method_needs_quote( string $method_id, ?float $goods_total = null ): bool {
	$total = null === $goods_total ? yabao_commerce_cart_goods_total() : $goods_total;
	return yabao_commerce_is_manual_carrier_method( $method_id ) && $total < yabao_commerce_threshold();
}

function yabao_commerce_method_title( string $method_id ): string {
	$labels = array(
		'yabao_pickup'                => 'Самовывоз — Кирова, 94',
		'yabao_delivery_avito'        => 'Авито Доставка',
		'yabao_delivery_cdek'         => 'СДЭК',
		'yabao_delivery_5post'        => '5Post',
		'yabao_delivery_russian_post' => 'Почта России',
	);
	return $labels[ $method_id ] ?? 'Доставка';
}

/**
 * Disable the Stage 68 theme-owned commercial lifecycle once this permanent
 * plugin is active. Shipping presentation/fallback remains in the theme until
 * the compatibility cleanup stage.
 */
function yabao_commerce_disable_theme_lifecycle(): void {
	remove_action( 'woocommerce_checkout_create_order', 'yabao_delivery_mark_order', 30 );
	remove_filter( 'woocommerce_payment_gateways', 'yabao_delivery_register_quote_gateway' );
	remove_filter( 'woocommerce_available_payment_gateways', 'yabao_delivery_guard_payment_gateways', 100 );
	remove_action( 'woocommerce_admin_order_data_after_shipping_address', 'yabao_delivery_admin_quote_notice' );
	remove_action( 'woocommerce_thankyou', 'yabao_delivery_thankyou_quote_notice', 8 );
}
add_action( 'after_setup_theme', 'yabao_commerce_disable_theme_lifecycle', 100 );

function yabao_commerce_mark_order( WC_Order $order, array $data ): void {
	$method      = yabao_commerce_selected_method_id();
	$goods_total = yabao_commerce_cart_goods_total();
	$pending     = yabao_commerce_method_needs_quote( $method, $goods_total );

	$order->update_meta_data( '_yabao_delivery_method', $method );
	$order->update_meta_data( '_yabao_delivery_goods_total', wc_format_decimal( $goods_total, wc_get_price_decimals() ) );
	$order->update_meta_data( '_yabao_delivery_quote_pending', $pending ? 'yes' : 'no' );

	if ( $pending ) {
		$order->update_meta_data( '_yabao_delivery_quote_threshold', wc_format_decimal( yabao_commerce_threshold(), 0 ) );
	}

	if (
		'' !== $method &&
		( yabao_commerce_is_pickup_method( $method ) || yabao_commerce_is_manual_carrier_method( $method ) ) &&
		empty( $order->get_items( 'shipping' ) ) &&
		class_exists( 'WC_Order_Item_Shipping' )
	) {
		$item = new WC_Order_Item_Shipping();
		$item->set_method_title( yabao_commerce_method_title( $method ) );
		$item->set_method_id( $method );
		$item->set_total( 0 );
		$order->add_item( $item );
	}

	if ( '' !== $method && ! yabao_commerce_is_pickup_method( $method ) ) {
		$order->set_shipping_first_name( $order->get_billing_first_name() );
		$order->set_shipping_last_name( $order->get_billing_last_name() );
		$order->set_shipping_address_1( $order->get_billing_address_1() );
		$order->set_shipping_address_2( $order->get_billing_address_2() );
		$order->set_shipping_city( $order->get_billing_city() );
		$order->set_shipping_state( $order->get_billing_state() );
		$order->set_shipping_postcode( $order->get_billing_postcode() );
		$order->set_shipping_country( 'RU' );
	}
}
add_action( 'woocommerce_checkout_create_order', 'yabao_commerce_mark_order', 30, 2 );

function yabao_commerce_load_quote_gateway(): void {
	if ( ! class_exists( 'WC_Payment_Gateway' ) || class_exists( 'Yabao_Commerce_Quote_Gateway' ) ) {
		return;
	}

	class Yabao_Commerce_Quote_Gateway extends WC_Payment_Gateway {
		public function __construct() {
			$this->id                 = 'yabao_delivery_quote';
			$this->method_title       = 'Расчёт доставки перед оплатой';
			$this->method_description = 'Служебный сценарий: заказ ставится на удержание до подтверждения тарифа доставки.';
			$this->has_fields         = false;
			$this->enabled            = 'yes';
			$this->title              = 'Оплата после расчёта доставки';
			$this->description        = 'Сначала подтвердим стоимость доставки выбранной службой. Оплата станет доступна после подтверждения полной суммы.';
		}

		public function is_available(): bool {
			if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
				return false;
			}
			return yabao_commerce_method_needs_quote( yabao_commerce_selected_method_id() );
		}

		public function process_payment( $order_id ): array {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				wc_add_notice( 'Не удалось создать заказ. Попробуйте ещё раз.', 'error' );
				return array( 'result' => 'failure' );
			}

			$order->update_meta_data( '_yabao_delivery_quote_pending', 'yes' );
			$order->update_status( 'on-hold', 'Ожидается расчёт стоимости доставки выбранной службой.' );
			$order->save();

			if ( function_exists( 'WC' ) && WC()->cart ) {
				WC()->cart->empty_cart();
			}

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
	}
}
add_action( 'woocommerce_loaded', 'yabao_commerce_load_quote_gateway', 20 );
if ( class_exists( 'WC_Payment_Gateway' ) ) {
	yabao_commerce_load_quote_gateway();
}

function yabao_commerce_register_quote_gateway( array $gateways ): array {
	if ( class_exists( 'Yabao_Commerce_Quote_Gateway' ) ) {
		$gateways[] = 'Yabao_Commerce_Quote_Gateway';
	}
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'yabao_commerce_register_quote_gateway', 20 );

function yabao_commerce_pay_order(): ?WC_Order {
	if ( ! function_exists( 'is_checkout_pay_page' ) || ! is_checkout_pay_page() ) {
		return null;
	}
	$order_id = absint( get_query_var( 'order-pay' ) );
	$order    = $order_id ? wc_get_order( $order_id ) : false;
	return $order instanceof WC_Order ? $order : null;
}

function yabao_commerce_guard_payment_gateways( array $gateways ): array {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $gateways;
	}

	$pay_order = yabao_commerce_pay_order();
	if ( $pay_order ) {
		if ( 'yes' === $pay_order->get_meta( '_yabao_delivery_quote_pending', true ) ) {
			return array();
		}
		unset( $gateways['yabao_delivery_quote'] );
		return $gateways;
	}

	$pending = yabao_commerce_method_needs_quote( yabao_commerce_selected_method_id() );
	foreach ( array_keys( $gateways ) as $gateway_id ) {
		if ( $pending && 'yabao_delivery_quote' !== $gateway_id ) {
			unset( $gateways[ $gateway_id ] );
		} elseif ( ! $pending && 'yabao_delivery_quote' === $gateway_id ) {
			unset( $gateways[ $gateway_id ] );
		}
	}
	return $gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'yabao_commerce_guard_payment_gateways', 100 );

function yabao_commerce_quote_admin_url( WC_Order $order ): string {
	return wp_nonce_url(
		add_query_arg(
			array(
				'page'     => 'yabao-delivery-quote',
				'order_id' => $order->get_id(),
			),
			admin_url( 'admin.php' )
		),
		'yabao_delivery_quote_page_' . $order->get_id()
	);
}

function yabao_commerce_admin_quote_notice( WC_Order $order ): void {
	$pending   = 'yes' === $order->get_meta( '_yabao_delivery_quote_pending', true );
	$confirmed = (string) $order->get_meta( '_yabao_delivery_quote_confirmed_at', true );

	if ( $pending ) {
		echo '<p class="form-field form-field-wide"><strong>Доставка:</strong> стоимость ещё не подтверждена. Не принимать оплату до фиксации фактического тарифа.</p>';
		printf(
			'<p class="form-field form-field-wide"><a class="button button-primary" href="%s">Указать стоимость доставки</a></p>',
			esc_url( yabao_commerce_quote_admin_url( $order ) )
		);
		return;
	}

	if ( '' !== $confirmed && $order->needs_payment() ) {
		$amount = (float) $order->get_meta( '_yabao_delivery_quote_amount', true );
		printf(
			'<p class="form-field form-field-wide"><strong>Доставка подтверждена:</strong> %1$s<br><a href="%2$s" target="_blank" rel="noopener">Открыть ссылку на оплату</a></p>',
			wp_kses_post( wc_price( $amount, array( 'currency' => $order->get_currency() ) ) ),
			esc_url( $order->get_checkout_payment_url() )
		);
	}
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'yabao_commerce_admin_quote_notice' );

function yabao_commerce_register_quote_admin_page(): void {
	add_submenu_page(
		null,
		'Подтверждение доставки',
		'Подтверждение доставки',
		'manage_woocommerce',
		'yabao-delivery-quote',
		'yabao_commerce_render_quote_admin_page'
	);
}
add_action( 'admin_menu', 'yabao_commerce_register_quote_admin_page' );

function yabao_commerce_render_quote_admin_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Недостаточно прав для этого действия.', 'yabao-commerce' ) );
	}

	$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$order    = $order_id ? wc_get_order( $order_id ) : false;
	if ( ! $order instanceof WC_Order ) {
		wp_die( esc_html__( 'Заказ не найден.', 'yabao-commerce' ) );
	}
	check_admin_referer( 'yabao_delivery_quote_page_' . $order_id );

	$pending = 'yes' === $order->get_meta( '_yabao_delivery_quote_pending', true );
	$method  = (string) $order->get_meta( '_yabao_delivery_method', true );

	echo '<div class="wrap"><h1>Подтверждение стоимости доставки</h1>';
	printf(
		'<p>Заказ <a href="%1$s">#%2$s</a> · способ: <strong>%3$s</strong></p>',
		esc_url( $order->get_edit_order_url() ),
		esc_html( $order->get_order_number() ),
		esc_html( yabao_commerce_method_title( $method ) )
	);

	if ( ! $pending ) {
		echo '<div class="notice notice-info inline"><p>Стоимость доставки для этого заказа уже подтверждена.</p></div>';
		if ( $order->needs_payment() ) {
			printf( '<p><a class="button button-primary" href="%s" target="_blank" rel="noopener">Открыть ссылку на оплату</a></p>', esc_url( $order->get_checkout_payment_url() ) );
		}
		echo '</div>';
		return;
	}

	echo '<p>Введите фактическую стоимость доставки. После подтверждения итог заказа будет пересчитан, а заказ станет доступен для оплаты.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="yabao_confirm_delivery_quote">';
	echo '<input type="hidden" name="order_id" value="' . esc_attr( (string) $order_id ) . '">';
	wp_nonce_field( 'yabao_confirm_delivery_quote_' . $order_id, '_yabao_quote_nonce' );
	echo '<table class="form-table"><tbody><tr><th scope="row"><label for="yabao_delivery_cost">Стоимость доставки, ₽</label></th>';
	echo '<td><input id="yabao_delivery_cost" name="delivery_cost" type="number" min="0" step="0.01" required class="regular-text" inputmode="decimal"></td></tr></tbody></table>';
	submit_button( 'Подтвердить стоимость и открыть оплату', 'primary' );
	echo '</form></div>';
}

/**
 * Find the shipping line WooCommerce created for a manual fallback rate.
 * Stage 68 originally used a generic method_id (yabao_delivery) while the
 * selected rate id remained carrier-specific (for example yabao_delivery_cdek).
 * Match that legacy line by its copied rate metadata so quote confirmation
 * updates the existing line instead of creating a duplicate shipping item.
 */
function yabao_commerce_find_manual_shipping_item( WC_Order $order, string $method ): ?WC_Order_Item_Shipping {
	$expected_carrier = yabao_commerce_method_title( $method );

	foreach ( $order->get_items( 'shipping' ) as $item ) {
		if ( ! $item instanceof WC_Order_Item_Shipping ) {
			continue;
		}

		$item_method = (string) $item->get_method_id();
		if ( $method === $item_method || yabao_commerce_is_manual_carrier_method( $item_method ) ) {
			return $item;
		}

		$kind    = (string) $item->get_meta( 'yabao_kind', true );
		$carrier = (string) $item->get_meta( 'yabao_carrier', true );
		if (
			'yabao_delivery' === $item_method &&
			'delivery' === $kind &&
			( '' === $carrier || $expected_carrier === $carrier )
		) {
			return $item;
		}
	}

	return null;
}

function yabao_commerce_confirm_delivery_quote(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Недостаточно прав для этого действия.', 'yabao-commerce' ) );
	}

	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	check_admin_referer( 'yabao_confirm_delivery_quote_' . $order_id, '_yabao_quote_nonce' );

	$order = $order_id ? wc_get_order( $order_id ) : false;
	if ( ! $order instanceof WC_Order ) {
		wp_die( esc_html__( 'Заказ не найден.', 'yabao-commerce' ) );
	}
	if ( $order->is_paid() ) {
		wp_die( esc_html__( 'Заказ уже оплачен. Этот сценарий изменения доставки недоступен.', 'yabao-commerce' ) );
	}
	if ( 'yes' !== $order->get_meta( '_yabao_delivery_quote_pending', true ) ) {
		wp_die( esc_html__( 'Для заказа уже нет ожидающего расчёта доставки.', 'yabao-commerce' ) );
	}

	$method = (string) $order->get_meta( '_yabao_delivery_method', true );
	if ( ! yabao_commerce_is_manual_carrier_method( $method ) ) {
		wp_die( esc_html__( 'Заказ не использует ручной тариф доставки.', 'yabao-commerce' ) );
	}

	$raw_amount = isset( $_POST['delivery_cost'] ) ? wc_clean( wp_unslash( $_POST['delivery_cost'] ) ) : '';
	$decimal    = wc_format_decimal( $raw_amount, wc_get_price_decimals() );
	if ( '' === $decimal || ! is_numeric( $decimal ) || (float) $decimal < 0 ) {
		wp_die( esc_html__( 'Укажите корректную стоимость доставки.', 'yabao-commerce' ) );
	}
	$amount = (float) $decimal;

	$shipping_item = yabao_commerce_find_manual_shipping_item( $order, $method );
	if ( ! $shipping_item ) {
		if ( ! class_exists( 'WC_Order_Item_Shipping' ) ) {
			wp_die( esc_html__( 'WooCommerce не может создать строку доставки.', 'yabao-commerce' ) );
		}
		$shipping_item = new WC_Order_Item_Shipping();
		$order->add_item( $shipping_item );
	}

	$shipping_item->set_method_id( $method );
	$shipping_item->set_method_title( yabao_commerce_method_title( $method ) );
	$shipping_item->set_total( $amount );
	$shipping_item->save();

	$order->set_payment_method( '' );
	$order->set_payment_method_title( '' );
	$order->update_meta_data( '_yabao_delivery_quote_pending', 'no' );
	$order->update_meta_data( '_yabao_delivery_quote_amount', wc_format_decimal( $amount, wc_get_price_decimals() ) );
	$order->update_meta_data( '_yabao_delivery_quote_confirmed_at', gmdate( 'c' ) );
	$order->update_meta_data( '_yabao_delivery_quote_confirmed_by', get_current_user_id() );
	$order->calculate_totals( false );

	if ( 'on-hold' === $order->get_status() ) {
		$order->set_status( 'pending' );
	}
	$order->add_order_note( sprintf( 'Стоимость доставки подтверждена: %s. Заказ открыт для оплаты.', wp_strip_all_tags( wc_price( $amount, array( 'currency' => $order->get_currency() ) ) ) ) );
	$order->save();

	wp_safe_redirect( add_query_arg( 'yabao_quote_confirmed', '1', $order->get_edit_order_url() ) );
	exit;
}
add_action( 'admin_post_yabao_confirm_delivery_quote', 'yabao_commerce_confirm_delivery_quote' );

function yabao_commerce_quote_confirmed_admin_notice(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) || empty( $_GET['yabao_quote_confirmed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	echo '<div class="notice notice-success is-dismissible"><p>Стоимость доставки подтверждена. Итог заказа пересчитан, заказ открыт для оплаты.</p></div>';
}
add_action( 'admin_notices', 'yabao_commerce_quote_confirmed_admin_notice' );

function yabao_commerce_thankyou_quote_notice( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order || 'yes' !== $order->get_meta( '_yabao_delivery_quote_pending', true ) ) {
		return;
	}
	echo '<div class="woocommerce-info">Заказ принят. Стоимость доставки будет рассчитана по тарифу выбранной службы и подтверждена до оплаты.</div>';
}
add_action( 'woocommerce_thankyou', 'yabao_commerce_thankyou_quote_notice', 8 );