<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
wp_enqueue_script(
	'yabao-shop-card-cart-v047',
	yabao_asset_url( 'js/wp-shop-card-cart-v047.js' ),
	array( 'jquery', 'wc-cart-fragments' ),
	'0.4.7',
	true
);

global $product;
if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}

$product_id = $product->get_id();
$link       = get_permalink( $product_id );
$out        = ! $product->is_in_stock();
$terms      = get_the_terms( $product_id, 'product_cat' );
$type_slug  = is_array( $terms ) && $terms ? sanitize_html_class( $terms[0]->slug ) : 'product';
$image_ids  = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );
$image_ids  = array_slice( array_values( array_unique( $image_ids ) ), 0, 5 );

$stock_text = trim( wp_strip_all_tags( wc_get_stock_html( $product ) ) );
if ( '' === $stock_text ) {
	$stock_text = $out ? 'Нет в наличии' : 'В наличии';
}

$price_html             = $product->get_price_html();
$card_variations        = array();
$variation_attribute    = '';
$variation_attribute_ui = '';
$selected_variation     = null;
$can_inline_buy         = $product->is_purchasable() && $product->is_in_stock();
$quantity_max           = (int) $product->get_max_purchase_quantity();

$attribute_label = static function ( string $attribute_name, string $value ): string {
	if ( taxonomy_exists( $attribute_name ) ) {
		$term = get_term_by( 'slug', $value, $attribute_name );
		if ( $term instanceof WP_Term ) {
			return $term->name;
		}
	}
	return $value;
};

if ( $product->is_type( 'variable' ) ) {
	$variation_attributes = $product->get_variation_attributes();
	$can_inline_buy        = false;

	// The compact catalog UI is intentionally limited to products with one
	// variation axis (for this project: tea weight). Multi-attribute products
	// keep a safe link to the full product page instead of creating a cramped UI.
	if ( 1 === count( $variation_attributes ) ) {
		$variation_attribute    = (string) array_key_first( $variation_attributes );
		$variation_attribute_ui = wc_attribute_label( $variation_attribute, $product );
		$available_variations   = $product->get_available_variations( 'objects' );

		foreach ( $available_variations as $variation ) {
			if ( ! $variation instanceof WC_Product_Variation || ! $variation->is_purchasable() ) {
				continue;
			}

			$value = (string) $variation->get_attribute( $variation_attribute );
			if ( '' === $value ) {
				continue;
			}

			$label = $attribute_label( $variation_attribute, $value );
			$stock = trim( wp_strip_all_tags( wc_get_stock_html( $variation ) ) );
			if ( '' === $stock ) {
				$stock = $variation->is_in_stock() ? 'В наличии' : 'Нет в наличии';
			}

			$card_variations[] = array(
				'id'         => $variation->get_id(),
				'value'      => $value,
				'label'      => $label,
				'price_html' => $variation->get_price_html(),
				'stock'      => $stock,
				'in_stock'   => $variation->is_in_stock(),
				'max'        => (int) $variation->get_max_purchase_quantity(),
			);
		}

		usort(
			$card_variations,
			static function ( array $left, array $right ): int {
				$left_number  = preg_replace( '/\D+/u', '', (string) $left['label'] );
				$right_number = preg_replace( '/\D+/u', '', (string) $right['label'] );
				$left_sort    = '' !== $left_number ? (int) $left_number : PHP_INT_MAX;
				$right_sort   = '' !== $right_number ? (int) $right_number : PHP_INT_MAX;
				if ( $left_sort === $right_sort ) {
					return strnatcasecmp( (string) $left['label'], (string) $right['label'] );
				}
				return $left_sort <=> $right_sort;
			}
		);

		foreach ( $card_variations as $variation_data ) {
			if ( ! $variation_data['in_stock'] ) {
				continue;
			}
			$selected_variation = $variation_data;
			break;
		}

		if ( $selected_variation ) {
			$can_inline_buy = true;
			$price_html     = $selected_variation['price_html'];
			$stock_text     = $selected_variation['stock'];
			$quantity_max   = (int) $selected_variation['max'];
		}
	}

	if ( ! $selected_variation ) {
		$minimum_price = $product->get_variation_price( 'min', true );
		if ( is_numeric( $minimum_price ) ) {
			$price_html = 'от ' . wc_price( (float) $minimum_price );
		}
	}
}
?>
<?php
if ( ! defined( 'YABAO_STAGE66_V047_SHOP_CARD_STYLE_PRINTED' ) ) :
	define( 'YABAO_STAGE66_V047_SHOP_CARD_STYLE_PRINTED', true );
?>
<style id="yabao-shop-card-cart-v047">
.shop-card{display:flex;min-width:0;flex-direction:column}
.shop-card__media-link{display:block;color:inherit;text-decoration:none}
.shop-card__media-link:focus-visible,.shop-card__title-link:focus-visible{outline:3px solid rgba(139,97,55,.32);outline-offset:3px}
.shop-card__title-link{color:inherit;text-decoration:none}
.shop-card__title-link:hover{text-decoration:underline;text-underline-offset:3px}
.shop-card>.shop-card__body{display:flex;min-width:0;flex:1;flex-direction:column}
.shop-card__cart{display:grid;gap:11px;margin-top:auto;padding-top:14px}
.shop-card__variants{min-width:0;margin:0;padding:0;border:0}
.shop-card__variants legend{margin:0 0 7px;padding:0;color:var(--color-muted);font-size:11px;font-weight:700;line-height:1.2}
.shop-card__variant-list{display:flex;flex-wrap:wrap;gap:6px}
.shop-card__variant{
  display:inline-flex;align-items:center;justify-content:center;min-height:32px;padding:6px 10px;
  border:1px solid var(--color-line);border-radius:999px;background:rgba(255,255,255,.42);
  color:var(--color-muted);font:inherit;font-size:11px;font-weight:700;line-height:1;cursor:pointer;
  transition:background .18s ease,color .18s ease,border-color .18s ease
}
.shop-card__variant:hover:not(:disabled){border-color:rgba(95,70,48,.42);color:var(--color-ink)}
.shop-card__variant[aria-checked="true"]{border-color:var(--color-walnut);background:var(--color-walnut);color:#fff}
.shop-card__variant:disabled{cursor:not-allowed;opacity:.38}
.shop-card__purchase{display:grid;grid-template-columns:auto minmax(0,1fr);gap:8px;align-items:center}
.shop-card__quantity{display:block;min-width:0}
.shop-card .product-quantity-picker__control--card{
  grid-template-columns:32px 40px 32px;width:max-content;border-radius:10px;background:rgba(255,255,255,.42)
}
.shop-card .product-quantity-picker__control--card button,
.shop-card .product-quantity-picker__control--card input.qty{
  width:auto;min-width:0;height:38px;min-height:0;padding:0;border:0;border-radius:0;background:transparent;color:var(--color-ink);font:inherit
}
.shop-card .product-quantity-picker__control--card button{font-size:17px}
.shop-card .product-quantity-picker__control--card input.qty{width:40px;border-inline:1px solid var(--color-line);text-align:center;-moz-appearance:textfield}
.shop-card .product-quantity-picker__control--card input.qty::-webkit-inner-spin-button,
.shop-card .product-quantity-picker__control--card input.qty::-webkit-outer-spin-button{margin:0;-webkit-appearance:none}
.shop-card__add{width:100%;min-height:38px;padding:8px 11px;font-size:12px;line-height:1.1;white-space:nowrap}
.shop-card__cart.is-loading{opacity:.68}
.shop-card__feedback{margin:0;color:#526044;font-size:11px;line-height:1.35}
.shop-card__feedback:empty{display:none}
.shop-card__feedback.is-error{color:var(--color-walnut)}
.shop-card__choose{width:100%;margin-top:auto}
@media(max-width:640px){
  .page-shop .shop-filter-bar .shop-filter{display:inline-flex;align-items:center;justify-content:center;text-align:center;line-height:1.2}
  .shop-card__cart{gap:10px;padding-top:12px}
  .shop-card__variant{min-height:30px;padding:6px 9px;font-size:11px}
  .shop-card .product-quantity-picker__control--card{grid-template-columns:30px 38px 30px}
  .shop-card .product-quantity-picker__control--card button,
  .shop-card .product-quantity-picker__control--card input.qty{height:36px}
  .shop-card .product-quantity-picker__control--card input.qty{width:38px}
  .shop-card__add{min-height:36px}
}
</style>
<?php endif; ?>
<article <?php wc_product_class( 'shop-card' . ( $out ? ' shop-card--out' : '' ), $product ); ?> data-type="<?php echo esc_attr( $type_slug ); ?>">
	<a class="shop-card__media-link" href="<?php echo esc_url( $link ); ?>" aria-label="Открыть <?php echo esc_attr( $product->get_name() ); ?>">
		<div class="shop-card__media"<?php echo count( $image_ids ) > 1 ? ' data-card-gallery' : ''; ?>>
			<span class="shop-card__badge<?php echo $out ? ' shop-card__badge--out' : ''; ?>"><?php echo $out ? 'Нет в наличии' : 'В наличии'; ?></span>
			<?php if ( $image_ids ) : ?>
				<span class="shop-card-gallery__slides">
				<?php foreach ( $image_ids as $index => $image_id ) : ?>
					<span class="shop-card-gallery__slide" data-card-slide<?php echo $index ? ' hidden' : ''; ?>><?php echo wp_get_attachment_image( $image_id, 'woocommerce_single', false, array( 'class' => 'shop-card__image', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width:700px) calc(100vw - 44px), 25vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endforeach; ?>
				</span>
				<?php if ( count( $image_ids ) > 1 ) : ?><span aria-hidden="true" class="shop-card-gallery__progress"><?php foreach ( $image_ids as $index => $_ ) : ?><span class="<?php echo $index ? '' : 'is-active'; ?>" data-card-dot></span><?php endforeach; ?></span><?php endif; ?>
			<?php else : ?>
				<span class="shop-card__symbol">茶</span>
			<?php endif; ?>
		</div>
	</a>
	<div class="shop-card__body">
		<div class="shop-card__meta"><span><?php echo esc_html( yabao_product_terms_text( $product ) ); ?></span><strong class="shop-card__price" data-shop-card-price><?php echo wp_kses_post( $price_html ); ?></strong></div>
		<h3><a class="shop-card__title-link" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
		<div class="shop-card__stock" data-shop-card-stock><?php echo esc_html( $stock_text ); ?></div>

		<?php if ( $can_inline_buy ) : ?>
		<form class="shop-card__cart" action="<?php echo esc_url( $link ); ?>" method="post" data-shop-card-cart data-product-type="<?php echo esc_attr( $product->is_type( 'variable' ) ? 'variable' : 'simple' ); ?>">
			<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( (string) $product_id ); ?>">
			<?php if ( $product->is_type( 'variable' ) && $selected_variation ) : ?>
				<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $product_id ); ?>">
				<input type="hidden" name="variation_id" value="<?php echo esc_attr( (string) $selected_variation['id'] ); ?>" data-shop-card-variation-id>
				<input type="hidden" name="attribute_<?php echo esc_attr( sanitize_title( $variation_attribute ) ); ?>" value="<?php echo esc_attr( $selected_variation['value'] ); ?>" data-shop-card-attribute-input>
				<fieldset class="shop-card__variants">
					<legend><?php echo esc_html( $variation_attribute_ui ?: 'Вариант' ); ?></legend>
					<div class="shop-card__variant-list" role="radiogroup" aria-label="<?php echo esc_attr( $variation_attribute_ui ?: 'Выберите вариант' ); ?>">
					<?php foreach ( $card_variations as $variation_data ) :
						$is_selected = (int) $variation_data['id'] === (int) $selected_variation['id'];
					?>
						<button
							type="button"
							class="shop-card__variant"
							role="radio"
							aria-checked="<?php echo $is_selected ? 'true' : 'false'; ?>"
							data-shop-card-variation
							data-variation-id="<?php echo esc_attr( (string) $variation_data['id'] ); ?>"
							data-attribute-value="<?php echo esc_attr( $variation_data['value'] ); ?>"
							data-price-html="<?php echo esc_attr( $variation_data['price_html'] ); ?>"
							data-stock-text="<?php echo esc_attr( $variation_data['stock'] ); ?>"
							data-max="<?php echo $variation_data['max'] > 0 ? esc_attr( (string) $variation_data['max'] ) : ''; ?>"
							<?php disabled( ! $variation_data['in_stock'] ); ?>
						><?php echo esc_html( $variation_data['label'] ); ?></button>
					<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endif; ?>

			<div class="shop-card__purchase">
				<label class="shop-card__quantity">
					<span class="visually-hidden">Количество, шт.</span>
					<span class="product-quantity-picker__control product-quantity-picker__control--card" data-shop-card-quantity>
						<button type="button" aria-label="Уменьшить количество" data-shop-card-minus disabled>−</button>
						<input class="input-text qty text" type="number" name="quantity" value="1" min="1"<?php echo $quantity_max > 0 ? ' max="' . esc_attr( (string) $quantity_max ) . '"' : ''; ?> step="1" inputmode="numeric" aria-label="Количество товара">
						<button type="button" aria-label="Увеличить количество" data-shop-card-plus<?php disabled( 1 === $quantity_max ); ?>>+</button>
					</span>
				</label>
				<button class="button button--walnut shop-card__add" type="submit" data-shop-card-add>В корзину</button>
			</div>
			<p class="shop-card__feedback" role="status" aria-live="polite" data-shop-card-feedback></p>
		</form>
		<?php elseif ( $product->is_type( 'variable' ) && ! $out ) : ?>
			<a class="button button--outline-walnut shop-card__choose" href="<?php echo esc_url( $link ); ?>">Выбрать вариант</a>
		<?php endif; ?>
	</div>
</article>
