<?php
defined( 'ABSPATH' ) || exit;

$items = WC()->cart ? WC()->cart->get_cart() : array();
?>
<?php if ( $items ) : ?>
	<div class="mini-cart-list">
	<?php foreach ( $items as $cart_item_key => $cart_item ) :
		$_product = $cart_item['data'];
		if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) { continue; }
		$link         = $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '';
		$variant_text = yabao_cart_item_variant_text( $cart_item );
		$max          = (int) $_product->get_max_purchase_quantity();
		$qty          = (int) $cart_item['quantity'];
	?>
		<article class="mini-cart-item" data-mini-cart-item data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>">
			<?php if ( $link ) : ?><a class="mini-cart-item__media" href="<?php echo esc_url( $link ); ?>"><?php echo $_product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><?php else : ?><div class="mini-cart-item__media"><?php echo $_product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			<div class="mini-cart-item__body">
				<div class="mini-cart-item__heading"><h3><?php if ( $link ) : ?><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $_product->get_name() ); ?></a><?php else : echo esc_html( $_product->get_name() ); endif; ?></h3><a class="mini-cart-item__remove remove_from_cart_button" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" aria-label="Удалить <?php echo esc_attr( $_product->get_name() ); ?>" data-product_id="<?php echo esc_attr( (string) $_product->get_id() ); ?>" data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>" data-product_sku="<?php echo esc_attr( $_product->get_sku() ); ?>">×</a></div>
				<?php if ( $variant_text ) : ?><p class="mini-cart-item__variant"><?php echo esc_html( $variant_text ); ?></p><?php endif; ?>
				<div class="mini-cart-item__bottom"><div class="mini-cart-qty" aria-label="Количество"><button aria-label="Уменьшить количество" data-mini-cart-minus type="button"<?php disabled( $qty <= 1 ); ?>>−</button><span aria-live="polite"><?php echo esc_html( (string) $qty ); ?></span><button aria-label="Увеличить количество" data-mini-cart-plus type="button"<?php disabled( $max > 0 && $qty >= $max ); ?>>+</button></div><strong class="mini-cart-item__price"><?php echo WC()->cart->get_product_subtotal( $_product, $qty ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
			</div>
		</article>
	<?php endforeach; ?>
	</div>
	<div class="cart-drawer__footer"><div class="cart-drawer__total"><span>Итого</span><strong><?php echo WC()->cart->get_cart_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div><a class="button button--outline-walnut" href="<?php echo esc_url( wc_get_cart_url() ); ?>">Корзина</a><a class="button button--walnut" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">Оформить заказ</a><button class="cart-drawer__continue" data-wc-cart-drawer-close type="button">Продолжить покупки</button></div>
<?php else : ?>
	<div class="cart-drawer__empty"><p>Корзина пока пуста.</p><a class="button button--outline-walnut" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) ); ?>">Перейти в магазин</a></div>
<?php endif; ?>
