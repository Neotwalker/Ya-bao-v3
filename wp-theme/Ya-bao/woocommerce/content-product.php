<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
global $product;
if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}

$product_id = $product->get_id();
$link       = get_permalink( $product_id );
$out        = ! $product->is_in_stock();
$terms      = get_the_terms( $product_id, 'product_cat' );
$type_slug  = is_array( $terms ) && $terms ? sanitize_html_class( $terms[0]->slug ) : 'product';
$image_ids    = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );
$image_ids    = array_slice( array_values( array_unique( $image_ids ) ), 0, 5 );
$stock_detail = trim( wp_strip_all_tags( wc_get_stock_html( $product ) ) );
if ( '' === $stock_detail ) {
	$stock_detail = $out ? 'Нет в наличии' : 'В наличии';
}
$price_html = $product->get_price_html();
if ( $product->is_type( 'variable' ) ) {
	$minimum_price = $product->get_variation_price( 'min', true );
	if ( is_numeric( $minimum_price ) ) {
		$price_html = 'от ' . wc_price( (float) $minimum_price );
	}
}
?>
<?php
if ( ! defined( 'YABAO_STAGE66_V045_SHOP_STYLE_PRINTED' ) ) :
	define( 'YABAO_STAGE66_V045_SHOP_STYLE_PRINTED', true );
?>
<style id="yabao-shop-v045">
@media(max-width:640px){
  .page-shop .shop-filter-bar .shop-filter{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    line-height:1.2
  }
}
</style>
<?php endif; ?>
<article <?php wc_product_class( 'shop-card' . ( $out ? ' shop-card--out' : '' ), $product ); ?> data-type="<?php echo esc_attr( $type_slug ); ?>">
	<a class="shop-card__link" href="<?php echo esc_url( $link ); ?>">
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
		<div class="shop-card__body">
			<div class="shop-card__meta"><span><?php echo esc_html( yabao_product_terms_text( $product ) ); ?></span><strong class="shop-card__price"><?php echo wp_kses_post( $price_html ); ?></strong></div>
			<h3><?php echo esc_html( $product->get_name() ); ?></h3>
			<div class="shop-card__stock"><?php echo esc_html( $stock_detail ); ?></div>
		</div>
	</a>
</article>
