<?php
/** Single product template using the approved product-page visual language. */
defined( 'ABSPATH' ) || exit;

get_header();

global $product;
if ( ! $product instanceof WC_Product ) {
	$product = wc_get_product( get_the_ID() );
}
if ( ! $product ) {
	get_footer();
	return;
}

$image_ids = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );
$image_ids = array_values( array_unique( $image_ids ) );
$category                = yabao_product_terms_text( $product );
$breadcrumb_category     = null;
$breadcrumb_category_url = '';

$product_terms = get_the_terms(
    $product->get_id(),
    'product_cat'
);

if ( is_array( $product_terms ) && $product_terms ) {
    $product_path = (string) wp_parse_url(
        get_permalink( $product->get_id() ),
        PHP_URL_PATH
    );

    $product_path_segments = array_filter(
        explode(
            '/',
            trim( $product_path, '/' )
        )
    );

    foreach ( $product_path_segments as $path_segment ) {
        foreach ( $product_terms as $term ) {
            if (
                $term instanceof WP_Term &&
                $term->slug === $path_segment
            ) {
                $breadcrumb_category = $term;
            }
        }
    }

    if ( $breadcrumb_category instanceof WP_Term ) {
        $term_link = get_term_link(
            $breadcrumb_category
        );

        if ( ! is_wp_error( $term_link ) ) {
            $breadcrumb_category_url = $term_link;
        }
    }
}
$stock_detail = trim( wp_strip_all_tags( wc_get_stock_html( $product ) ) );
if ( '' === $stock_detail ) {
	$stock_detail = $product->is_in_stock() ? 'В наличии' : 'Нет в наличии';
}

// A variable product normally renders a price range until Woo JS resolves the
// selected variation. The approved static page starts on the first available
// weight, so render that same price server-side to remove the reload flash.
$display_price_html = $product->get_price_html();
if ( $product->is_type( 'variable' ) ) {
	$available_variations = $product->get_available_variations( 'objects' );
	if ( $available_variations ) {
		$variation_weight = static function ( WC_Product_Variation $variation ): int {
			$value  = (string) $variation->get_attribute( 'pa_weight' );
			$digits = preg_replace( '/\D+/', '', $value );
			return $digits ? (int) $digits : PHP_INT_MAX;
		};
		usort(
			$available_variations,
			static function ( WC_Product_Variation $left, WC_Product_Variation $right ) use ( $variation_weight ): int {
				return $variation_weight( $left ) <=> $variation_weight( $right );
			}
		);
		foreach ( $available_variations as $variation ) {
			if ( ! $variation->is_purchasable() || ! $variation->is_in_stock() ) {
				continue;
			}
			$variation_price_html = $variation->get_price_html();
			if ( $variation_price_html ) {
				$display_price_html = $variation_price_html;
				break;
			}
		}
	}
}
?>
<main id="main-content">
	<section class="section section--dark section--compact inner-hero">
		<div class="container inner-hero__grid">
			<div><nav aria-label="Хлебные крошки" class="breadcrumbs breadcrumbs--hero"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a></li><li><a href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) ); ?>">Магазин</a></li><?php if ( $breadcrumb_category instanceof WP_Term && $breadcrumb_category_url ) : ?><li><a href="<?php echo esc_url( $breadcrumb_category_url ); ?>"><?php echo esc_html( $breadcrumb_category->name ); ?></a></li><?php endif; ?><li><span aria-current="page"><?php echo esc_html( $product->get_name() ); ?></span></li></ol></nav><h1><?php echo esc_html( $product->get_name() ); ?></h1></div>
			<p class="inner-hero__text"><?php echo esc_html( $product->get_short_description() ? wp_strip_all_tags( $product->get_short_description() ) : 'Карточка товара работает на данных WooCommerce.' ); ?></p>
		</div>
	</section>

	<section class="section section--paper product-detail">
		<div class="container">
			<?php woocommerce_output_all_notices(); ?>
			<div class="product-detail__grid">
				<div aria-label="Галерея товара" class="product-gallery" data-product-gallery>
					<?php if ( $image_ids ) : ?>
					<div aria-label="Миниатюры товара" class="product-gallery__thumbs" role="group">
						<?php foreach ( $image_ids as $index => $image_id ) : ?>
						<button aria-current="<?php echo $index ? 'false' : 'true'; ?>" aria-label="Показать изображение <?php echo esc_attr( (string) ( $index + 1 ) ); ?>" class="product-gallery__thumb<?php echo $index ? '' : ' is-active'; ?>" data-product-thumb type="button"><?php echo wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'alt' => '', 'aria-hidden' => 'true', 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						<?php endforeach; ?>
					</div>
					<div class="product-gallery__stage" data-product-stage>
						<?php foreach ( $image_ids as $index => $image_id ) : ?>
						<div class="product-gallery__slide<?php echo $index ? '' : ' is-active'; ?>" data-product-slide<?php echo $index ? ' hidden' : ''; ?>><?php echo wp_get_attachment_image( $image_id, 'full', false, array( 'alt' => $product->get_name(), 'loading' => $index ? 'lazy' : 'eager', 'decoding' => 'async', 'sizes' => '(max-width:640px) calc(100vw - 44px), 50vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<?php endforeach; ?>
					</div>
					<?php else : ?>
					<div class="product-gallery__stage"><div class="product-gallery__slide is-active"><span class="yabao-product-placeholder">茶</span></div></div>
					<?php endif; ?>
				</div>

				<div class="product-summary">
					<div class="product-summary__top"><p class="eyebrow"><?php echo esc_html( $category ); ?></p><p class="product-summary__price"><?php echo wp_kses_post( $display_price_html ); ?></p><p class="product-summary__stock"><?php echo $product->is_in_stock() ? 'В наличии' : 'Нет в наличии'; ?></p></div>
					<?php if ( $product->get_short_description() ) : ?><div class="lead"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div><?php endif; ?>
					<dl class="product-facts">
						<?php if ( $product->get_sku() ) : ?><div><dt>Артикул</dt><dd><?php echo esc_html( $product->get_sku() ); ?></dd></div><?php endif; ?>
						<div><dt>Категория</dt><dd><?php echo esc_html( $category ); ?></dd></div>
						<div><dt>Наличие</dt><dd><?php echo esc_html( $stock_detail ); ?></dd></div>
					</dl>
					<div class="yabao-wc-add-to-cart"><?php woocommerce_template_single_add_to_cart(); ?></div>
					<div class="product-summary__actions"><a class="button button--outline-walnut" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) ); ?>">Вернуться в магазин</a></div>
				</div>
			</div>

			<div class="product-description"><div class="section-heading"><div><p class="eyebrow">Описание</p><h2>О товаре</h2></div></div><?php echo $product->get_description() ? wp_kses_post( wpautop( $product->get_description() ) ) : '<p>Описание будет заполнено из WordPress после подготовки контентных полей.</p>'; ?></div>
		</div>
	</section>

	<?php
	$related_ids = wc_get_related_products( $product->get_id(), 4 );
	if ( $related_ids ) :
	?>
	<section class="section section--sand"><div class="container"><div class="section-heading"><div><p class="eyebrow">Ещё в магазине</p><h2>Похожие товары</h2></div></div><div class="shop-grid">
		<?php foreach ( $related_ids as $related_id ) : $post_object = get_post( $related_id ); if ( ! $post_object ) { continue; } setup_postdata( $GLOBALS['post'] =& $post_object ); wc_get_template_part( 'content', 'product' ); endforeach; wp_reset_postdata(); ?>
	</div></div></section>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
