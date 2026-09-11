<?php
defined( 'ABSPATH' ) || exit;

get_header();

$blog_page_id = (int) get_option( 'page_for_posts' );
$blog_url     = $blog_page_id
    ? get_permalink( $blog_page_id )
    : home_url( '/blog/' );

if ( ! is_string( $blog_url ) ) {
    $blog_url = '';
}

$blog_text = static function (
    string $name
) use ( $blog_page_id ): string {
    if (
        ! $blog_page_id ||
        ! function_exists( 'get_field' )
    ) {
        return '';
    }

    $value = get_field(
        $name,
        $blog_page_id
    );

    if ( ! is_scalar( $value ) ) {
        return '';
    }

    return trim(
        wp_strip_all_tags(
            (string) $value
        )
    );
};

$related_eyebrow = $blog_text(
    'blog_related_eyebrow'
);

$related_title = $blog_text(
    'blog_related_title'
);

$related_intro = $blog_text(
    'blog_related_intro'
);

$has_related_heading =
    '' !== $related_eyebrow ||
    '' !== $related_title ||
    '' !== $related_intro;
?>

<main id="main-content">

<?php while ( have_posts() ) : ?>
    <?php
    the_post();

    $post_id = get_the_ID();

    $post_excerpt = trim(
        wp_strip_all_tags(
            (string) get_post_field(
                'post_excerpt',
                $post_id
            )
        )
    );

    /*
     * Build the article TOC from real headings already present
     * in the WordPress post content.
     */
    $toc_items   = array();
    $raw_content = (string) get_post_field(
        'post_content',
        $post_id
    );

    if (
        '' !== $raw_content &&
        class_exists( 'DOMDocument' )
    ) {
        $dom = new DOMDocument();

        $previous_libxml_state = libxml_use_internal_errors(
            true
        );

        $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' .
            '<div id="yabao-article-content">' .
            $raw_content .
            '</div>',
            LIBXML_HTML_NOIMPLIED |
            LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        libxml_use_internal_errors(
            $previous_libxml_state
        );

        $xpath = new DOMXPath( $dom );

        foreach (
            $xpath->query(
                '//*[@id="yabao-article-content"]//h2[@id]'
            ) as $heading
        ) {
            if ( ! $heading instanceof DOMElement ) {
                continue;
            }

            $id = trim(
                $heading->getAttribute( 'id' )
            );

            $label = trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $heading->textContent
                )
            );

            if (
                '' === $id ||
                '' === $label
            ) {
                continue;
            }

            $toc_items[] = array(
                'id'    => $id,
                'label' => $label,
            );
        }
    }

    /*
     * Only real published posts are allowed in related materials.
     */
    $related_query = new WP_Query(
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'post__not_in'        => array( $post_id ),
            'posts_per_page'      => 6,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'orderby'             => 'date',
            'order'               => 'DESC',
        )
    );
    ?>

    <section class="section section--dark section--compact inner-hero">
        <div class="container inner-hero__grid">

            <div>
                <nav
                    aria-label="Хлебные крошки"
                    class="breadcrumbs breadcrumbs--hero"
                >
                    <ol>
                        <li>
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                Главная
                            </a>
                        </li>

                        <?php if ( '' !== $blog_url ) : ?>
                            <li>
                                <a href="<?php echo esc_url( $blog_url ); ?>">
                                    Блог
                                </a>
                            </li>
                        <?php endif; ?>

                        <li>
                            <span aria-current="page">
                                <?php the_title(); ?>
                            </span>
                        </li>
                    </ol>
                </nav>

                <h1><?php the_title(); ?></h1>

                <div class="article-meta article-meta--hero">

                    <span class="article-meta__item article-meta__item--date">
                        <?php echo yabao_article_calendar_icon(); ?>

                        <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
                            <?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?>
                        </time>
                    </span>

                    <span class="article-meta__item article-meta__item--views">
                        <?php echo yabao_article_views_icon(); ?>

                        <span data-article-views>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    yabao_get_post_views(
                                        $post_id
                                    )
                                )
                            );
                            ?>
                        </span>
                    </span>

                </div>
            </div>

            <?php if ( '' !== $post_excerpt ) : ?>
                <p class="inner-hero__text">
                    <?php echo esc_html( $post_excerpt ); ?>
                </p>
            <?php endif; ?>

        </div>
    </section>

    <article class="section section--paper article-page">
        <div class="container article-page__grid">

            <div class="article-page__content">
                <?php the_content(); ?>
            </div>

            <aside class="article-page__aside">

                <?php if ( $toc_items ) : ?>
                    <nav
                        aria-label="Содержание статьи"
                        class="article-page__toc"
                    >
                        <strong>В статье</strong>

                        <ol>
                            <?php foreach ( $toc_items as $toc_item ) : ?>
                                <li>
                                    <a href="#<?php echo esc_attr( $toc_item['id'] ); ?>">
                                        <?php echo esc_html( $toc_item['label'] ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>

                <?php if ( '' !== $blog_url ) : ?>
                    <a
                        class="button button--outline-walnut"
                        href="<?php echo esc_url( $blog_url ); ?>"
                    >
                        Вернуться в блог
                    </a>
                <?php endif; ?>

            </aside>

        </div>
    </article>

    <?php if ( $related_query->have_posts() ) : ?>
        <section class="section section--dark related-articles">
            <div class="container">

                <?php if ( $has_related_heading ) : ?>
                    <div class="section-heading reveal">

                        <?php if ( '' !== $related_eyebrow || '' !== $related_title ) : ?>
                            <div>

                                <?php if ( '' !== $related_eyebrow ) : ?>
                                    <p class="eyebrow">
                                        <?php echo esc_html( $related_eyebrow ); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ( '' !== $related_title ) : ?>
                                    <h2>
                                        <?php echo esc_html( $related_title ); ?>
                                    </h2>
                                <?php endif; ?>

                            </div>
                        <?php endif; ?>

                        <?php if ( '' !== $related_intro ) : ?>
                            <p>
                                <?php echo esc_html( $related_intro ); ?>
                            </p>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

                <div
                    class="related-articles__shell reveal"
                    data-related-articles-shell
                >
                    <div
                        class="related-articles__slider swiper"
                        data-related-articles-swiper
                    >
                        <div class="swiper-wrapper">

                            <?php while ( $related_query->have_posts() ) : ?>
                                <?php
                                $related_query->the_post();

                                $related_id = get_the_ID();

                                $related_excerpt = trim(
                                    wp_strip_all_tags(
                                        (string) get_post_field(
                                            'post_excerpt',
                                            $related_id
                                        )
                                    )
                                );

                                $categories = get_the_category(
                                    $related_id
                                );

                                $category_name = '';

                                if (
                                    is_array( $categories ) &&
                                    ! empty( $categories )
                                ) {
                                    $category_name = trim(
                                        (string) $categories[0]->name
                                    );
                                }
                                ?>

                                <div class="swiper-slide">
                                    <a
                                        class="related-article-card"
                                        href="<?php the_permalink(); ?>"
                                    >
                                        <?php if ( '' !== $category_name ) : ?>
                                            <span>
                                                <?php echo esc_html( $category_name ); ?>
                                            </span>
                                        <?php endif; ?>

                                        <h3><?php the_title(); ?></h3>

                                        <?php if ( '' !== $related_excerpt ) : ?>
                                            <p>
                                                <?php echo esc_html( $related_excerpt ); ?>
                                            </p>
                                        <?php endif; ?>

                                        <strong>
                                            Читать
                                            <span aria-hidden="true">→</span>
                                        </strong>
                                    </a>
                                </div>

                            <?php endwhile; ?>

                        </div>
                    </div>

                    <div
                        aria-label="Навигация по материалам"
                        class="related-articles__controls"
                        role="group"
                    >
                        <button
                            aria-label="Предыдущие материалы"
                            class="related-articles__button related-articles__button--prev"
                            data-related-articles-prev
                            type="button"
                        >
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path
                                    d="m15 18-6-6 6-6"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                ></path>
                            </svg>
                        </button>

                        <div
                            aria-hidden="true"
                            class="related-articles__pagination"
                            data-related-articles-pagination
                        ></div>

                        <button
                            aria-label="Следующие материалы"
                            class="related-articles__button related-articles__button--next"
                            data-related-articles-next
                            type="button"
                        >
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path
                                    d="m9 18 6-6-6-6"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                ></path>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
        </section>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>

<?php endwhile; ?>

</main>

<?php
get_footer();