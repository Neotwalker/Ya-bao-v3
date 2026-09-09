<?php
/**
 * Stage 68: delivery and pickup rules.
 *
 * Real commercial terms supplied by the store owner:
 * - pickup: Chelyabinsk, Kirova 94, 12:00-01:00;
 * - carriers: Avito Delivery, CDEK, 5Post, Russian Post;
 * - free carrier delivery from 5,000 RUB;
 * - delivery time: usually 2-10 days;
 * - below 5,000 RUB the carrier tariff is confirmed before payment.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_FREE_SHIPPING_THRESHOLD = 5000.0;

/**
 * Reuse the approved Stage 60 delivery styles on the WordPress delivery page,
 * and load the small checkout integration layer only where it is needed.
 */
function yabao_delivery_enqueue_assets(): void {
	if ( is_page( 'delivery' ) ) {
		wp_enqueue_style(
			'yabao-shop',
			yabao_asset_url( 'css/shop.css' ),
			array( 'yabao-pages' ),
			yabao_asset_version( 'css/shop.css' )
		);
	}

	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}

	wp_enqueue_style(
		'yabao-delivery',
		yabao_asset_url( 'css/wp-delivery.css' ),
		array( 'yabao-shop', 'yabao-wp' ),
		yabao_asset_version( 'css/wp-delivery.css' )
	);
	wp_enqueue_script(
		'yabao-delivery',
		yabao_asset_url( 'js/wp-delivery.js' ),
		array( 'jquery', 'wc-checkout' ),
		yabao_asset_version( 'js/wp-delivery.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'yabao_delivery_enqueue_assets', 40 );

function yabao_delivery_body_classes( array $classes ): array {
	if ( is_page( 'delivery' ) ) {
		$classes[] = 'page-shop';
		$classes[] = 'page-delivery';
		$classes[] = 'page-inner';
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'yabao_delivery_body_classes', 30 );

function yabao_delivery_package_goods_total( array $package ): float {
	$total = 0.0;
	foreach ( (array) ( $package['contents'] ?? array() ) as $item ) {
		$total += isset( $item['line_total'] ) ? (float) $item['line_total'] : 0.0;
	}
	return max( 0.0, $total );
}

function yabao_delivery_cart_goods_total(): float {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}
	return max( 0.0, (float) WC()->cart->get_cart_contents_total() );
}

function yabao_delivery_is_pickup_method( string $method_id ): bool {
	return str_starts_with( $method_id, 'yabao_pickup' ) || str_contains( $method_id, 'local_pickup' );
}

function yabao_delivery_is_manual_carrier_method( string $method_id ): bool {
	return str_starts_with( $method_id, 'yabao_delivery_' );
}

function yabao_delivery_method_needs_quote( string $method_id, ?float $goods_total = null ): bool {
	$total = null === $goods_total ? yabao_delivery_cart_goods_total() : $goods_total;
	return yabao_delivery_is_manual_carrier_method( $method_id ) && $total < YABAO_FREE_SHIPPING_THRESHOLD;
}

function yabao_delivery_selected_method_id(): string {
	if ( isset( $_POST['shipping_method'] ) ) {
		$posted = (array) wp_unslash( $_POST['shipping_method'] );
		$first  = reset( $posted );
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

/**
 * Temporary built-in fallback for Stage 68.
 *
 * Once a real carrier plugin or configured WooCommerce shipping zone returns
 * rates, those rates win and this fallback deliberately adds nothing.
 */
function yabao_delivery_add_fallback_rates( array $rates, array $package ): array {
	if ( ! empty( $rates ) || ! class_exists( 'WC_Shipping_Rate' ) ) {
		return $rates;
	}

	$country = strtoupper( (string) ( $package['destination']['country'] ?? 'RU' ) );
	if ( 'RU' !== $country ) {
		return $rates;
	}

	$goods_total = yabao_delivery_package_goods_total( $package );
	$is_free     = $goods_total >= YABAO_FREE_SHIPPING_THRESHOLD;

	$pickup = new WC_Shipping_Rate(
		'yabao_pickup',
		'Самовывоз — Кирова, 94',
		0,
		array(),
		'yabao_pickup',
		0
	);
	$pickup->add_meta_data( 'yabao_kind', 'pickup' );
	$rates['yabao_pickup'] = $pickup;

	$carriers = array(
		'avito'        => 'Авито Доставка',
		'cdek'         => 'СДЭК',
		'5post'        => '5Post',
		'russian_post' => 'Почта России',
	);

	foreach ( $carriers as $slug => $name ) {
		$id    = 'yabao_delivery_' . $slug;
		$label = $is_free ? $name . ' — бесплатно' : $name . ' — тариф после оформления';
		$rate  = new WC_Shipping_Rate( $id, $label, 0, array(), 'yabao_delivery', 0 );
		$rate->add_meta_data( 'yabao_kind', 'delivery' );
		$rate->add_meta_data( 'yabao_carrier', $name );
		$rate->add_meta_data( 'yabao_quote_pending', $is_free ? 'no' : 'yes' );
		$rates[ $id ] = $rate;
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_delivery_add_fallback_rates', 100, 2 );

function yabao_delivery_rate_detail( WC_Shipping_Rate $rate, float $goods_total ): string {
	$id = (string) $rate->get_id();

	if ( yabao_delivery_is_pickup_method( $id ) ) {
		return 'Челябинск, ул. Кирова, 94 · ежедневно 12:00–01:00';
	}

	if ( yabao_delivery_is_manual_carrier_method( $id ) ) {
		if ( $goods_total >= YABAO_FREE_SHIPPING_THRESHOLD ) {
			return 'Обычно 2–10 дней · доставка бесплатная при сумме товаров от 5 000 ₽.';
		}
		return 'Обычно 2–10 дней · стоимость по тарифу службы подтвердим до оплаты.';
	}

	return 'Стоимость и срок рассчитаны выбранной службой доставки.';
}

/**
 * Render shipping methods inside the custom order-review template.
 */
function yabao_delivery_render_checkout_shipping(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return;
	}

	$packages = WC()->shipping()->get_packages();
	if ( empty( $packages ) ) {
		return;
	}

	$chosen_methods = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods', array() ) : array();
	$goods_total    = yabao_delivery_cart_goods_total();

	echo '<section class="checkout-summary__shipping" aria-labelledby="yabao-shipping-title">';
	echo '<div class="checkout-summary__shipping-heading"><span class="eyebrow">Получение</span><strong id="yabao-shipping-title">Способ получения</strong></div>';

	foreach ( $packages as $index => $package ) {
		$rates = (array) ( $package['rates'] ?? array() );
		if ( empty( $rates ) ) {
			echo '<p class="checkout-summary__delivery-note">Для текущего адреса способы доставки пока недоступны.</p>';
			continue;
		}

		$chosen = isset( $chosen_methods[ $index ] ) ? (string) $chosen_methods[ $index ] : '';
		if ( '' === $chosen || ! isset( $rates[ $chosen ] ) ) {
			$chosen = (string) array_key_first( $rates );
		}

		echo '<div class="checkout-choices checkout-choices--shipping">';
		foreach ( $rates as $rate ) {
			if ( ! $rate instanceof WC_Shipping_Rate ) {
				continue;
			}

			$rate_id  = (string) $rate->get_id();
			$input_id = 'shipping_method_' . absint( $index ) . '_' . sanitize_title( $rate_id );
			$detail   = yabao_delivery_rate_detail( $rate, $goods_total );

			printf(
				'<label class="checkout-choice" for="%1$s"><input class="shipping_method" type="radio" name="shipping_method[%2$d]" data-index="%2$d" id="%1$s" value="%3$s"%4$s><span><strong>%5$s</strong><small>%6$s</small></span></label>',
				esc_attr( $input_id ),
				absint( $index ),
				esc_attr( $rate_id ),
				checked( $rate_id, $chosen, false ),
				esc_html( $rate->get_label() ),
				esc_html( $detail )
			);
		}
		echo '</div>';
	}

	$selected = yabao_delivery_selected_method_id();
	if ( '' === $selected && ! empty( $chosen_methods[0] ) ) {
		$selected = (string) $chosen_methods[0];
	}

	if ( yabao_delivery_method_needs_quote( $selected, $goods_total ) ) {
		echo '<p class="checkout-shipping-quote"><strong>Стоимость доставки рассчитывается отдельно.</strong> Заказ будет сохранён без списания денег. После расчёта тарифа магазин подтвердит полную сумму до оплаты.</p>';
	} elseif ( ! yabao_delivery_is_pickup_method( $selected ) && $goods_total >= YABAO_FREE_SHIPPING_THRESHOLD ) {
		echo '<p class="checkout-shipping-quote checkout-shipping-quote--free"><strong>Бесплатная доставка.</strong> Порог 5 000 ₽ проверяется сервером по стоимости товаров после скидок.</p>';
	}

	echo '</section>';
}

/**
 * Pickup does not require a delivery address. For carrier delivery the server
 * validation below is authoritative; JavaScript only controls presentation.
 */
function yabao_delivery_checkout_fields( array $fields ): array {
	foreach ( array( 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode' ) as $key ) {
		if ( isset( $fields['billing'][ $key ] ) ) {
			$fields['billing'][ $key ]['required'] = false;
		}
	}
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'yabao_delivery_checkout_fields', 30 );

function yabao_delivery_validate_checkout( array $data, WP_Error $errors ): void {
	$method = yabao_delivery_selected_method_id();
	if ( '' === $method || yabao_delivery_is_pickup_method( $method ) ) {
		return;
	}

	$required = array(
		'billing_address_1' => 'Укажите адрес доставки.',
		'billing_city'      => 'Укажите город доставки.',
		'billing_postcode'  => 'Укажите почтовый индекс.',
	);

	foreach ( $required as $key => $message ) {
		if ( empty( trim( (string) ( $data[ $key ] ?? '' ) ) ) ) {
			$errors->add( 'yabao_' . $key, $message );
		}
	}
}
add_action( 'woocommerce_after_checkout_validation', 'yabao_delivery_validate_checkout', 30, 2 );

function yabao_delivery_mark_order( WC_Order $order, array $data ): void {
	$method      = yabao_delivery_selected_method_id();
	$goods_total = yabao_delivery_cart_goods_total();
	$pending     = yabao_delivery_method_needs_quote( $method, $goods_total );

	$order->update_meta_data( '_yabao_delivery_method', $method );
	$order->update_meta_data( '_yabao_delivery_goods_total', wc_format_decimal( $goods_total, wc_get_price_decimals() ) );
	$order->update_meta_data( '_yabao_delivery_quote_pending', $pending ? 'yes' : 'no' );

	if ( $pending ) {
		$order->update_meta_data( '_yabao_delivery_quote_threshold', wc_format_decimal( YABAO_FREE_SHIPPING_THRESHOLD, 0 ) );
	}
}
add_action( 'woocommerce_checkout_create_order', 'yabao_delivery_mark_order', 30, 2 );

/**
 * Non-charging gateway used only while a below-threshold delivery tariff is
 * unknown. This prevents an incomplete product-only total from being paid.
 */
function yabao_delivery_register_quote_gateway( array $gateways ): array {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return $gateways;
	}

	if ( ! class_exists( 'Yabao_Delivery_Quote_Gateway' ) ) {
		class Yabao_Delivery_Quote_Gateway extends WC_Payment_Gateway {
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
				return yabao_delivery_method_needs_quote( yabao_delivery_selected_method_id() );
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

	$gateways[] = 'Yabao_Delivery_Quote_Gateway';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'yabao_delivery_register_quote_gateway' );

function yabao_delivery_guard_payment_gateways( array $gateways ): array {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $gateways;
	}

	if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
		$order_id = absint( get_query_var( 'order-pay' ) );
		$order    = $order_id ? wc_get_order( $order_id ) : false;
		if ( $order && 'yes' === $order->get_meta( '_yabao_delivery_quote_pending', true ) ) {
			return array();
		}
	}

	$pending = yabao_delivery_method_needs_quote( yabao_delivery_selected_method_id() );
	foreach ( array_keys( $gateways ) as $gateway_id ) {
		if ( $pending && 'yabao_delivery_quote' !== $gateway_id ) {
			unset( $gateways[ $gateway_id ] );
		} elseif ( ! $pending && 'yabao_delivery_quote' === $gateway_id ) {
			unset( $gateways[ $gateway_id ] );
		}
	}

	return $gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'yabao_delivery_guard_payment_gateways', 100 );

function yabao_delivery_admin_quote_notice( WC_Order $order ): void {
	if ( 'yes' !== $order->get_meta( '_yabao_delivery_quote_pending', true ) ) {
		return;
	}

	echo '<p class="form-field form-field-wide"><strong>Доставка:</strong> стоимость ещё не подтверждена. Не принимать оплату по этому заказу до добавления фактического тарифа и снятия флага ожидания.</p>';
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'yabao_delivery_admin_quote_notice' );

function yabao_delivery_thankyou_quote_notice( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order || 'yes' !== $order->get_meta( '_yabao_delivery_quote_pending', true ) ) {
		return;
	}

	echo '<div class="woocommerce-info">Заказ принят. Стоимость доставки будет рассчитана по тарифу выбранной службы и подтверждена до оплаты.</div>';
}
add_action( 'woocommerce_thankyou', 'yabao_delivery_thankyou_quote_notice', 8 );
