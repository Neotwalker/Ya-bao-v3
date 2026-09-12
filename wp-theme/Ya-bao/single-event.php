<?php
/**
 * Single event.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();

    $event_id = get_the_ID();

    $event_date_raw = function_exists( 'get_field' )
        ? get_field( 'event_date', $event_id )
        : '';

    $event_time = function_exists( 'get_field' )
        ? trim(
            (string) get_field(
                'event_time',
                $event_id
            )
        )
        : '';

    $event_location = function_exists( 'get_field' )
        ? trim(
            (string) get_field(
                'event_location',
                $event_id
            )
        )
        : '';


    $event_cta = function_exists( 'get_field' )
        ? get_field(
            'event_cta',
            $event_id
        )
        : array();

    if ( ! is_array( $event_cta ) ) {
        $event_cta = array();
    }

    $event_date = '';

    if (
        is_string( $event_date_raw ) &&
        '' !== $event_date_raw
    ) {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $event_date_raw,
            wp_timezone()
        );

        if ( $date ) {
            $event_date = wp_date(
                'j F Y',
                $date->getTimestamp(),
                wp_timezone()
            );
        }
    }

    $is_event_upcoming = false;

    if (
        is_string( $event_date_raw ) &&
        '' !== $event_date_raw
    ) {
        $event_date_object = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $event_date_raw,
            wp_timezone()
        );

        $today = new DateTimeImmutable(
            'today',
            wp_timezone()
        );

        $is_event_upcoming =
            $event_date_object &&
            $event_date_object >= $today;
    }

    $excerpt = trim(
        wp_strip_all_tags(
            (string) get_post_field(
                'post_excerpt',
                $event_id
            )
        )
    );

    $events_url = get_post_type_archive_link(
        'event'
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

                        <?php if ( $events_url ) : ?>
                            <li>
                                <a href="<?php echo esc_url( $events_url ); ?>">
                                    Мероприятия
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

            </div>

            <?php if ( $excerpt ) : ?>
                <p class="inner-hero__text">
                    <?php echo esc_html( $excerpt ); ?>
                </p>
            <?php endif; ?>

        </div>
    </section>

    <section class="section section--paper event-detail">
        <div class="container">
            <div class="event-detail__grid">

                <article class="event-detail__main reveal">

                    <?php if ( get_the_content() ) : ?>
                        <?php the_content(); ?>
                    <?php endif; ?>

                </article>

                <?php
                $has_facts =
                    '' !== $event_date ||
                    '' !== $event_time ||
                    '' !== $event_location ||
                    ! empty( $event_cta ) ||
                    $events_url;

                if ( $has_facts ) :
                    ?>
                    <aside
                        aria-label="Информация о мероприятии"
                        class="event-detail__aside reveal"
                    >

                        <?php if ( $event_date ) : ?>
                            <div class="event-detail__fact">
                                <span>Дата</span>
                                <strong>
                                    <?php echo esc_html( $event_date ); ?>
                                </strong>
                            </div>
                        <?php endif; ?>

                        <?php if ( $event_time ) : ?>
                            <div class="event-detail__fact">
                                <span>Время</span>
                                <strong>
                                    <?php echo esc_html( $event_time ); ?>
                                </strong>
                            </div>
                        <?php endif; ?>

                        <?php if ( $event_location ) : ?>
                            <div class="event-detail__fact">
                                <span>Место</span>
                                <strong>
                                    <?php echo esc_html( $event_location ); ?>
                                </strong>
                            </div>
                        <?php endif; ?>

                        <div class="event-detail__actions">
                            <?php if ( $is_event_upcoming ) : ?>
                                <button
                                    class="button button--walnut"
                                    data-event="<?php echo esc_attr( get_the_title( $event_id ) ); ?>"
                                    <?php if ( $event_date ) : ?>
                                        data-event-date="<?php echo esc_attr( $event_date ); ?>"
                                    <?php endif; ?>
                                    <?php if ( $event_time ) : ?>
                                        data-event-time="<?php echo esc_attr( $event_time ); ?>"
                                    <?php endif; ?>
                                    data-modal-open
                                    data-source="event-detail"
                                    type="button"
                                >
                                    Записаться
                                </button>
                            <?php endif; ?>

                            <?php if ( ! empty( $event_cta['url'] ) && ! empty( $event_cta['title'] ) ) : ?>
                                <a
                                    class="button button--outline-walnut"
                                    href="<?php echo esc_url( $event_cta['url'] ); ?>"
                                    <?php if ( '_blank' === ( $event_cta['target'] ?? '' ) ) : ?>
                                        target="_blank"
                                        rel="noopener"
                                    <?php endif; ?>
                                >
                                    <?php echo esc_html( $event_cta['title'] ); ?>
                                </a>
                            <?php endif; ?>

                            <?php if ( $events_url ) : ?>
                                <a
                                    class="button button--outline-walnut"
                                    href="<?php echo esc_url( $events_url ); ?>"
                                >
                                    К мероприятиям
                                </a>
                            <?php endif; ?>
                        </div>

                    </aside>
                <?php endif; ?>

            </div>
        </div>
    </section>

    </main>

<?php
endwhile;

get_footer();