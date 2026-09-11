<?php
/**
 * Events archive.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$today = current_time( 'Y-m-d' );

$events = new WP_Query(
    array(
        'post_type'      => 'event',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'paged'          => max( 1, get_query_var( 'paged' ) ),
        'meta_query'     => array(
            'event_date_clause' => array(
                'key'     => 'event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
        'orderby'        => array(
            'event_date_clause' => 'ASC',
            'date'              => 'ASC',
        ),
    )
);

function yabao_event_archive_date( int $post_id ): string {
    $value = function_exists( 'get_field' )
        ? get_field( 'event_date', $post_id )
        : '';

    if ( ! is_string( $value ) || '' === $value ) {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $value,
        wp_timezone()
    );

    if ( ! $date ) {
        return '';
    }

    return wp_date(
        'j F Y',
        $date->getTimestamp(),
        wp_timezone()
    );
}
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
                    <li>
                        <span aria-current="page">Мероприятия</span>
                    </li>
                </ol>
            </nav>

            <h1>Мероприятия</h1>
        </div>
    </div>
</section>

<?php if ( $events->have_posts() ) : ?>
<section
    class="section section--paper events-list-section"
    id="events-list"
>
    <div class="container">

        <div class="section-heading reveal">
            <div>
                <p class="eyebrow">Афиша</p>
                <h2>События и встречи</h2>
            </div>
        </div>

        <div class="events-list">
            <?php
            $event_index =
                ( max( 1, get_query_var( 'paged' ) ) - 1 ) * 10;

            while ( $events->have_posts() ) :
                $events->the_post();

                $event_index++;

                $event_id = get_the_ID();

                $event_type = function_exists( 'get_field' )
                    ? trim(
                        (string) get_field(
                            'event_type',
                            $event_id
                        )
                    )
                    : '';

                $event_date = yabao_event_archive_date(
                    $event_id
                );

                $event_location = function_exists( 'get_field' )
                    ? trim(
                        (string) get_field(
                            'event_location',
                            $event_id
                        )
                    )
                    : '';

                $excerpt = trim(
                    wp_strip_all_tags(
                        (string) get_post_field(
                            'post_excerpt',
                            $event_id
                        )
                    )
                );
                ?>

                <article class="event-list-card reveal">
                    <a
                        class="event-list-card__link"
                        href="<?php the_permalink(); ?>"
                    >
                        <div
                            aria-hidden="true"
                            class="event-list-card__index"
                        >
                            <?php
                            echo esc_html(
                                str_pad(
                                    (string) $event_index,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                )
                            );
                            ?>
                        </div>

                        <div class="event-list-card__content">

                            <?php if ( $event_type || $event_date || $event_location ) : ?>
                                <div class="event-list-card__meta">

                                    <?php if ( $event_type ) : ?>
                                        <span>
                                            <?php echo esc_html( $event_type ); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ( $event_date ) : ?>
                                        <span>
                                            <?php echo esc_html( $event_date ); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ( $event_location ) : ?>
                                        <span>
                                            <?php echo esc_html( $event_location ); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>
                            <?php endif; ?>

                            <h3><?php the_title(); ?></h3>

                            <?php if ( $excerpt ) : ?>
                                <p><?php echo esc_html( $excerpt ); ?></p>
                            <?php endif; ?>

                            <span class="event-list-card__arrow">
                                Подробнее
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
                'total'     => $events->max_num_pages,
                'current'   => max( 1, get_query_var( 'paged' ) ),
                'type'      => 'array',
                'prev_text' => '←',
                'next_text' => 'Далее →',
            )
        );

        if ( $pagination ) :
            ?>
            <nav
                aria-label="Навигация по страницам афиши"
                class="pagination"
            >
                <?php
                foreach ( $pagination as $link ) {
                    echo wp_kses_post( $link );
                }
                ?>
            </nav>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>

</main>
<?php
wp_reset_postdata();
get_footer();