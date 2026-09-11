<?php
defined( 'ABSPATH' ) || exit;

get_header();

$terms = get_terms(
    array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
    )
);

$shop_url = yabao_wc_page_url( 'shop' );
?>
<main id="main-content">
    <section class="section section--dark section--compact inner-hero">
        <div class="container inner-hero__grid">
            <div>
                <nav aria-label="Хлебные крошки" class="breadcrumbs breadcrumbs--hero">
                    <ol>
                        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a></li>
                        <li><a href="<?php echo esc_url( $shop_url ); ?>">Магазин</a></li>
                        <li><span aria-current="page"><?php the_title(); ?></span></li>
                    </ol>
                </nav>
                <h1><?php the_title(); ?></h1>
            </div>
        </div>
    </section>

    <section class="section section--paper shop-catalog">
        <div class="container">
            <div class="shop-catalog__toolbar">
                <div class="shop-filter-bar" aria-label="Категории магазина">
                    <a class="shop-filter" href="<?php echo esc_url( $shop_url ); ?>">Все товары</a>

                    <?php if ( ! is_wp_error( $terms ) ) : ?>
                        <?php foreach ( $terms as $term ) : ?>
                            <?php
                            if ( 'bez-kategorii' === $term->slug ) {
                                continue;
                            }

                            $term_link = get_term_link( $term );

                            if ( is_wp_error( $term_link ) ) {
                                continue;
                            }
                            ?>
                            <a
                                class="shop-filter"
                                href="<?php echo esc_url( $term_link ); ?>"
                            ><?php echo esc_html( $term->name ); ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
get_footer();