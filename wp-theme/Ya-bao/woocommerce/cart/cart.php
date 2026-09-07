<?php
defined( 'ABSPATH' ) || exit;

// v0.4.4 cart behaviour is loaded under a new handle/file so a browser cannot
// reuse the old v0.4.0/v0.4.3 modules that submitted the form or rendered stale notices.
wp_enqueue_script( 'yabao-wp-cart-v044', yabao_asset_url( 'js/wp-cart-v044.js' ), array(), '0.4.4', true );

do_action( 'woocommerce_before_cart' );
?>
<style id="yabao-cart-v044">
.page-cart .woocommerce-cart-form .product-quantity-picker__control .qty{
	width:100%;min-width:0;min-height:0;padding:0;border:0;border-inline:1px solid var(--color-line);border-radius:0;background:transparent;text-align:center
}
</style>
<form class="woocommerce-cart-form cart-layout" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<section class="cart-items" aria-labelledby="cart-items-title">
		<div class="cart-section-heading"><div><p class="eyebrow">Состав заказа</p><h2 id="cart-items-title">Ваши товары</h2></div><button class="cart-clear" type="submit" name="yabao_clear_cart" value="1">Очистить корзину</button></div>
		<div class="cart-list">
		<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
			if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) { continue; }
			$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
			$variant_text      = yabao_cart_item_variant_text( $cart_item );
			$max_quantity      = $_product->get_max_purchase_quantity();
		?>
			<article class="cart-line">
				<?php if ( $product_permalink ) : ?><a class="cart-line__media" href="<?php echo esc_url( $product_permalink ); ?>"><?php echo $_product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><?php else : ?><div class="cart-line__media"><?php echo $_product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
				<div class="cart-line__body">
					<div class="cart-line__heading"><div><h3><?php if ( $product_permalink ) : ?><a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo esc_html( $_product->get_name() ); ?></a><?php else : echo esc_html( $_product->get_name() ); endif; ?></h3><?php if ( $variant_text ) : ?><p class="cart-line__variant"><?php echo esc_html( $variant_text ); ?></p><?php endif; ?></div><a class="cart-line__remove" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" aria-label="Удалить <?php echo esc_attr( $_product->get_name() ); ?>">Удалить</a></div>
					<div class="cart-line__controls">
						<div class="cart-line__price"><span class="cart-line__label">Цена</span><strong><?php echo WC()->cart->get_product_price( $_product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
						<div class="cart-line__quantity"><span class="cart-line__label">Количество</span><div class="product-quantity-picker__control" data-wc-cart-quantity><button aria-label="Уменьшить количество" data-wc-qty-minus type="button">−</button><?php
						echo woocommerce_quantity_input(
							array(
								'input_name'   => "cart[{$cart_item_key}][qty]",
								'input_value'  => $cart_item['quantity'],
								'max_value'    => $max_quantity > 0 ? $max_quantity : '',
								'min_value'    => '0',
								'product_name' => $_product->get_name(),
							),
							$_product,
							false
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?><button aria-label="Увеличить количество" data-wc-qty-plus type="button">+</button></div></div>
						<div class="cart-line__total"><span class="cart-line__label">Сумма</span><strong><?php echo WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
		</div>
		<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		<noscript><div class="yabao-cart-actions"><button class="button button--outline-walnut" type="submit" name="update_cart" value="1">Обновить корзину</button></div></noscript>
	</section>
	<aside class="cart-summary" aria-labelledby="cart-summary-title">
		<p class="eyebrow">Корзина</p><h2 id="cart-summary-title">Итого</h2>
		<div class="cart-summary__row"><span>Товаров</span><strong><?php echo esc_html( (string) WC()->cart->get_cart_contents_count() ); ?></strong></div>
		<div class="cart-summary__row cart-summary__row--total"><span>Сумма</span><strong><?php echo WC()->cart->get_cart_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
		<div class="cart-summary__actions"><a class="button button--walnut" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">Оформить заказ</a><a class="button button--outline-walnut" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) ); ?>">Продолжить покупки</a></div>
	</aside>
</form>
<?php do_action( 'woocommerce_after_cart' ); ?>
