<?php
defined( 'ABSPATH' ) || exit;

get_header();

$blog_page_id = (int) get_option(
    'page_for_posts'
);

$blog_title = $blog_page_id
    ? trim(
        (string) get_the_title(
            $blog_page_id
        )
    )
    : '';

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

    return is_string( $value )
        ? trim(
            wp_strip_all_tags(
                $value
            )
        )
        : '';
};

$hero_intro = $blog_text(
    'blog_hero_intro'
);

$list_eyebrow = $blog_text(
    'blog_list_eyebrow'
);

$list_title = $blog_text(
    'blog_list_title'
);

$list_intro = $blog_text(
    'blog_list_intro'
);

$has_list_heading =
    '' !== $list_eyebrow ||
    '' !== $list_title ||
    '' !== $list_intro;

$paged = max(
    1,
    (int) get_query_var( 'paged' )
);

$posts_per_page = (int) get_query_var(
    'posts_per_page'
);

if ( $posts_per_page < 1 ) {
    $posts_per_page = (int) get_option(
        'posts_per_page',
        10
    );
}

$card_index = (
    ( $paged - 1 ) *
    $posts_per_page
);
?>

<main id="main-content">

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

                        <?php if ( '' !== $blog_title ) : ?>
                            <li>
                                <span aria-current="page">
                                    <?php echo esc_html( $blog_title ); ?>
                                </span>
                            </li>
                        <?php endif; ?>
                    </ol>
                </nav>

                <?php if ( '' !== $blog_title ) : ?>
                    <h1><?php echo esc_html( $blog_title ); ?></h1>
                <?php endif; ?>
            </div>

            <?php if ( '' !== $hero_intro ) : ?>
                <p class="inner-hero__text">
                    <?php echo esc_html( $hero_intro ); ?>
                </p>
            <?php endif; ?>

        </div>
    </section>

    <?php if ( have_posts() ) : ?>

        <section class="section section--paper blog-list-section">
            <div class="container">

                <?php if ( $has_list_heading ) : ?>
                    <div class="section-heading reveal">

                        <?php if ( '' !== $list_eyebrow || '' !== $list_title ) : ?>
                            <div>

                                <?php if ( '' !== $list_eyebrow ) : ?>
                                    <p class="eyebrow">
                                        <?php echo esc_html( $list_eyebrow ); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ( '' !== $list_title ) : ?>
                                    <h2>
                                        <?php echo esc_html( $list_title ); ?>
                                    </h2>
                                <?php endif; ?>

                            </div>
                        <?php endif; ?>

                        <?php if ( '' !== $list_intro ) : ?>
                            <p><?php echo esc_html( $list_intro ); ?></p>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

                <div class="blog-list">

                    <?php while ( have_posts() ) : ?>
                        <?php
                        the_post();

                        $post_id = get_the_ID();

                        $card_index++;

                        $excerpt = trim(
                            wp_strip_all_tags(
                                (string) get_post_field(
                                    'post_excerpt',
                                    $post_id
                                )
                            )
                        );

                        $categories = get_the_category(
                            $post_id
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

                        <article <?php post_class( 'event-list-card blog-list-card reveal' ); ?>>

                            <a
                                class="event-list-card__link blog-list-card__link"
                                href="<?php the_permalink(); ?>"
                            >
                                <div
                                    aria-hidden="true"
                                    class="event-list-card__index"
                                >
                                    <?php
                                    echo esc_html(
                                        str_pad(
                                            (string) $card_index,
                                            2,
                                            '0',
                                            STR_PAD_LEFT
                                        )
                                    );
                                    ?>
                                </div>

                                <div class="event-list-card__content blog-list-card__content">

                                    <?php if ( '' !== $category_name ) : ?>
                                        <div class="event-list-card__meta">
                                            <span>
                                                <?php echo esc_html( $category_name ); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <h3><?php the_title(); ?></h3>

                                    <?php if ( '' !== $excerpt ) : ?>
                                        <p><?php echo esc_html( $excerpt ); ?></p>
                                    <?php endif; ?>

                                    <div class="article-meta blog-list-card__article-meta">

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

                                    <span class="event-list-card__arrow">
                                        <span>Перейти</span>
                                        <span aria-hidden="true">→</span>
                                    </span>

                                </div>
                            </a>

                        </article>

                    <?php endwhile; ?>

                </div>

                <?php
                $pagination = paginate_links(
                    array(
                        'total'     => (int) $GLOBALS['wp_query']->max_num_pages,
                        'current'   => $paged,
                        'type'      => 'array',
                        'prev_text' => '← Назад',
                        'next_text' => 'Дальше →',
                        'mid_size'  => 1,
                        'end_size'  => 1,
                    )
                );
                ?>

                <?php if ( is_array( $pagination ) && $pagination ) : ?>
                    <nav
                        aria-label="Пагинация блога"
                        class="pagination reveal"
                    >
                        <?php
                        echo wp_kses_post(
                            implode(
                                '',
                                $pagination
                            )
                        );
                        ?>
                    </nav>
                <?php endif; ?>

            </div>
        </section>

    <?php endif; ?>

</main>

<?php
get_footer();