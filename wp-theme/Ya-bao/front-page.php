<?php
/** Front page. Static content is the approved v4.57 reference; ACF migration comes later. */
get_header();
?>
<?php
$home_id = get_queried_object_id();

$home_text = static function ( string $name ) use ( $home_id ): string {
    if ( ! function_exists( 'get_field' ) ) {
        return '';
    }

    $value = get_field( $name, $home_id );

    if ( ! is_scalar( $value ) ) {
        return '';
    }

    return trim( wp_strip_all_tags( (string) $value ) );
};

$home_link = static function ( string $name ) use ( $home_id ): array {
    if ( ! function_exists( 'get_field' ) ) {
        return array();
    }

    $value = get_field( $name, $home_id );

    if ( ! is_array( $value ) ) {
        return array();
    }

    $url = isset( $value['url'] ) && is_scalar( $value['url'] )
        ? esc_url_raw( trim( (string) $value['url'] ) )
        : '';

    $title = isset( $value['title'] ) && is_scalar( $value['title'] )
        ? trim( wp_strip_all_tags( (string) $value['title'] ) )
        : '';

    $target = isset( $value['target'] ) && '_blank' === $value['target']
        ? '_blank'
        : '';

    if ( '' === $url || '' === $title ) {
        return array();
    }

    return array(
        'url'    => $url,
        'title'  => $title,
        'target' => $target,
    );
};

$home_attachment_id = static function ( string $name ) use ( $home_id ): int {
    if ( ! function_exists( 'get_field' ) ) {
        return 0;
    }

    return absint( get_field( $name, $home_id, false ) );
};

/*
 * Hero.
 */
$hero_eyebrow       = $home_text( 'home_hero_eyebrow' );
$hero_title         = $home_text( 'home_hero_title' );
$hero_lead          = $home_text( 'home_hero_lead' );
$hero_secondary     = $home_link( 'home_hero_secondary_link' );
$hero_note          = $home_text( 'home_hero_note' );
$hero_video_label   = $home_text( 'home_hero_video_label' );
$hero_poster_id     = $home_attachment_id( 'home_hero_poster' );
$hero_video_id      = $home_attachment_id( 'home_hero_video' );
$hero_booking_label = function_exists( 'yabao_site_text' )
    ? yabao_site_text( 'booking_cta_label' )
    : '';

$hero_poster_url = $hero_poster_id
    ? wp_get_attachment_url( $hero_poster_id )
    : '';

$hero_video_url = $hero_video_id
    ? wp_get_attachment_url( $hero_video_id )
    : '';

$hero_video_mime = $hero_video_id
    ? get_post_mime_type( $hero_video_id )
    : '';

if (
    ! $hero_video_url ||
    ! is_string( $hero_video_mime ) ||
    0 !== strpos( $hero_video_mime, 'video/' )
) {
    $hero_video_url  = '';
    $hero_video_mime = '';
}

$has_hero_actions =
    '' !== $hero_booking_label ||
    ! empty( $hero_secondary );

$has_hero_copy =
    '' !== $hero_eyebrow ||
    '' !== $hero_title ||
    '' !== $hero_lead ||
    $has_hero_actions ||
    '' !== $hero_note;

$has_hero =
    $hero_poster_id ||
    '' !== $hero_video_url ||
    $has_hero_copy;

/*
 * First visit / reviews.
 */
$trust_eyebrow = $home_text( 'home_trust_eyebrow' );

$trust_text = '';

if ( function_exists( 'get_field' ) ) {
    $trust_raw = get_field( 'home_trust_text', $home_id );

    if ( is_string( $trust_raw ) ) {
        $trust_text = trim( $trust_raw );
    }
}

$yandex_reviews_value = $home_text( 'home_yandex_reviews_value' );
$yandex_reviews_label = $home_text( 'home_yandex_reviews_label' );
$two_gis_reviews_value = $home_text( 'home_2gis_reviews_value' );
$two_gis_reviews_label = $home_text( 'home_2gis_reviews_label' );

$yandex_maps_url = function_exists( 'yabao_site_url' )
    ? yabao_site_url( 'yandex_maps_url' )
    : '';

$two_gis_url = function_exists( 'yabao_site_url' )
    ? yabao_site_url( 'two_gis_url' )
    : '';

$has_yandex_review =
    '' !== $yandex_maps_url &&
    (
        '' !== $yandex_reviews_value ||
        '' !== $yandex_reviews_label
    );

$has_two_gis_review =
    '' !== $two_gis_url &&
    (
        '' !== $two_gis_reviews_value ||
        '' !== $two_gis_reviews_label
    );

$has_trust_intro =
    '' !== $trust_eyebrow ||
    '' !== $trust_text;

$has_trust =
    $has_trust_intro ||
    $has_yandex_review ||
    $has_two_gis_review;

/*
 * Visit formats.
 */
$formats_eyebrow = $home_text( 'home_formats_eyebrow' );
$formats_title    = $home_text( 'home_formats_title' );
$formats_intro    = $home_text( 'home_formats_intro' );

$format_rows = function_exists( 'get_field' )
    ? get_field( 'home_formats', $home_id )
    : array();

if ( ! is_array( $format_rows ) ) {
    $format_rows = array();
}

$format_cards = array();

foreach ( $format_rows as $row ) {
    if ( ! is_array( $row ) ) {
        continue;
    }

    $index = isset( $row['format_index'] ) && is_scalar( $row['format_index'] )
        ? trim( wp_strip_all_tags( (string) $row['format_index'] ) )
        : '';

    $title = isset( $row['format_title'] ) && is_scalar( $row['format_title'] )
        ? trim( wp_strip_all_tags( (string) $row['format_title'] ) )
        : '';

    $text = isset( $row['format_text'] ) && is_scalar( $row['format_text'] )
        ? trim( wp_strip_all_tags( (string) $row['format_text'] ) )
        : '';

    $action_type = isset( $row['format_action_type'] ) && is_scalar( $row['format_action_type'] )
        ? sanitize_key( (string) $row['format_action_type'] )
        : '';

    $featured = ! empty( $row['format_featured'] );

    $card = array(
        'index'           => $index,
        'title'           => $title,
        'text'            => $text,
        'featured'        => $featured,
        'action_type'     => $action_type,
        'action_url'      => '',
        'action_label'    => '',
        'action_target'   => '',
        'booking_context' => '',
    );

    if ( '' === $title ) {
        continue;
    }

    if ( 'link' === $action_type ) {
        $link = isset( $row['format_link'] ) && is_array( $row['format_link'] )
            ? $row['format_link']
            : array();

        $url = isset( $link['url'] ) && is_scalar( $link['url'] )
            ? esc_url_raw( trim( (string) $link['url'] ) )
            : '';

        $label = isset( $link['title'] ) && is_scalar( $link['title'] )
            ? trim( wp_strip_all_tags( (string) $link['title'] ) )
            : '';

        $target = isset( $link['target'] ) && '_blank' === $link['target']
            ? '_blank'
            : '';

        if ( '' === $url || '' === $label ) {
            continue;
        }

        $card['action_url']    = $url;
        $card['action_label']  = $label;
        $card['action_target'] = $target;
    } elseif ( 'booking' === $action_type ) {
        $label = isset( $row['format_booking_label'] ) && is_scalar( $row['format_booking_label'] )
            ? trim( wp_strip_all_tags( (string) $row['format_booking_label'] ) )
            : '';

        $context = isset( $row['format_booking_context'] ) && is_scalar( $row['format_booking_context'] )
            ? sanitize_key( (string) $row['format_booking_context'] )
            : '';

        if ( '' === $label ) {
            continue;
        }

        $card['action_label']    = $label;
        $card['booking_context'] = $context;
    } else {
        continue;
    }

    $format_cards[] = $card;
}

$has_formats_heading =
    '' !== $formats_eyebrow ||
    '' !== $formats_title ||
    '' !== $formats_intro;

$has_formats =
    $has_formats_heading ||
    ! empty( $format_cards );
?>

<main id="main-content">

<?php if ( $has_hero ) : ?>
<section<?php if ( '' !== $hero_title ) : ?> aria-labelledby="hero-title"<?php endif; ?> class="hero-v4" id="top">
    <div class="container hero-v4__shell">
        <div class="hero-v4__media reveal is-visible" data-hero-media>

            <?php if ( $hero_poster_id ) : ?>
                <?php
                echo wp_get_attachment_image(
                    $hero_poster_id,
                    'full',
                    false,
                    array(
                        'alt'           => '',
                        'class'         => 'hero-v4__poster',
                        'fetchpriority' => 'high',
                        'loading'       => 'eager',
                        'decoding'      => 'async',
                        'sizes'         => '100vw',
                    )
                );
                ?>
            <?php endif; ?>

            <?php if ( '' !== $hero_video_url ) : ?>
                <video
                    <?php if ( '' !== $hero_video_label ) : ?>
                        aria-label="<?php echo esc_attr( $hero_video_label ); ?>"
                    <?php else : ?>
                        aria-hidden="true"
                    <?php endif; ?>
                    autoplay
                    loop
                    muted
                    playsinline
                    <?php if ( $hero_poster_url ) : ?>
                        poster="<?php echo esc_url( $hero_poster_url ); ?>"
                    <?php endif; ?>
                    preload="none"
                >
                    <source
                        data-src="<?php echo esc_url( $hero_video_url ); ?>"
                        type="<?php echo esc_attr( $hero_video_mime ); ?>"
                    >
                </video>
            <?php endif; ?>

            <div aria-hidden="true" class="hero-v4__shade"></div>

            <?php if ( $has_hero_copy ) : ?>
                <div class="hero-v4__copy">

                    <?php if ( '' !== $hero_eyebrow ) : ?>
                        <p class="hero-v4__eyebrow"><?php echo esc_html( $hero_eyebrow ); ?></p>
                    <?php endif; ?>

                    <?php if ( '' !== $hero_title ) : ?>
                        <h1 id="hero-title"><?php echo nl2br( esc_html( $hero_title ) ); ?></h1>
                    <?php endif; ?>

                    <?php if ( '' !== $hero_lead ) : ?>
                        <p class="hero-v4__lead"><?php echo esc_html( $hero_lead ); ?></p>
                    <?php endif; ?>

                    <?php if ( $has_hero_actions ) : ?>
                        <div class="hero-v4__actions">

                            <?php if ( '' !== $hero_booking_label ) : ?>
                                <button
                                    class="button button--primary button--large"
                                    data-modal-open
                                    data-source="hero"
                                    type="button"
                                >
                                    <?php echo esc_html( $hero_booking_label ); ?>
                                    <span aria-hidden="true">→</span>
                                </button>
                            <?php endif; ?>

                            <?php if ( $hero_secondary ) : ?>
                                <a
                                    class="button button--hero-ghost button--large"
                                    href="<?php echo esc_url( $hero_secondary['url'] ); ?>"
                                    <?php if ( '_blank' === $hero_secondary['target'] ) : ?>
                                        target="_blank"
                                        rel="noopener"
                                    <?php endif; ?>
                                ><?php echo esc_html( $hero_secondary['title'] ); ?></a>
                            <?php endif; ?>

                        </div>
                    <?php endif; ?>

                    <?php if ( '' !== $hero_note ) : ?>
                        <p class="hero-v4__note"><?php echo esc_html( $hero_note ); ?></p>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        </div>
    </div>
</section>
<?php endif; ?>

<?php if ( $has_trust ) : ?>
<section aria-label="О первом визите и отзывах" class="quick-trust">
    <div class="container quick-trust__layout">

        <?php if ( $has_trust_intro ) : ?>
            <div class="quick-trust__intro reveal">

                <?php if ( '' !== $trust_eyebrow ) : ?>
                    <p class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></p>
                <?php endif; ?>

                <?php if ( '' !== $trust_text ) : ?>
                    <?php echo wp_kses_post( $trust_text ); ?>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <?php if ( $has_yandex_review || $has_two_gis_review ) : ?>
            <div aria-label="Отзывы на картах" class="quick-trust__reviews" role="group">

                <?php if ( $has_yandex_review ) : ?>
                    <a
                        aria-label="Отзывы Я Бао Завари в Яндекс Картах"
                        class="review-stat reveal"
                        href="<?php echo esc_url( $yandex_maps_url ); ?>"
                        rel="noopener"
                        target="_blank"
                    >
                        <span class="review-stat__top">
                            <span>Яндекс Карты</span>
                            <span aria-hidden="true" class="review-stat__arrow">
                                <svg viewBox="0 0 24 24">
                                    <path d="M8 16 16 8M10 8h6v6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
                                </svg>
                            </span>
                        </span>

                        <?php if ( '' !== $yandex_reviews_value ) : ?>
                            <span class="review-stat__value"><?php echo esc_html( $yandex_reviews_value ); ?></span>
                        <?php endif; ?>

                        <?php if ( '' !== $yandex_reviews_label ) : ?>
                            <span class="review-stat__label"><?php echo esc_html( $yandex_reviews_label ); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <?php if ( $has_two_gis_review ) : ?>
                    <a
                        aria-label="Отзывы Я Бао Завари в 2ГИС"
                        class="review-stat review-stat--accent reveal"
                        href="<?php echo esc_url( $two_gis_url ); ?>"
                        rel="noopener"
                        target="_blank"
                    >
                        <span class="review-stat__top">
                            <span>2ГИС</span>
                            <span aria-hidden="true" class="review-stat__arrow">
                                <svg viewBox="0 0 24 24">
                                    <path d="M8 16 16 8M10 8h6v6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
                                </svg>
                            </span>
                        </span>

                        <?php if ( '' !== $two_gis_reviews_value ) : ?>
                            <span class="review-stat__value"><?php echo esc_html( $two_gis_reviews_value ); ?></span>
                        <?php endif; ?>

                        <?php if ( '' !== $two_gis_reviews_label ) : ?>
                            <span class="review-stat__label"><?php echo esc_html( $two_gis_reviews_label ); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>

<?php if ( $has_formats ) : ?>
<section class="section section--paper" id="formats">
    <div class="container">

        <?php if ( $has_formats_heading ) : ?>
            <div class="section-heading reveal">

                <?php if ( '' !== $formats_eyebrow || '' !== $formats_title ) : ?>
                    <div>
                        <?php if ( '' !== $formats_eyebrow ) : ?>
                            <p class="eyebrow"><?php echo esc_html( $formats_eyebrow ); ?></p>
                        <?php endif; ?>

                        <?php if ( '' !== $formats_title ) : ?>
                            <h2><?php echo nl2br( esc_html( $formats_title ) ); ?></h2>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ( '' !== $formats_intro ) : ?>
                    <p><?php echo esc_html( $formats_intro ); ?></p>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <?php if ( $format_cards ) : ?>
            <div class="format-grid-v4">

                <?php foreach ( $format_cards as $card ) : ?>
                    <?php
                    $card_class = 'format-card-v4 reveal';

                    if ( $card['featured'] ) {
                        $card_class .= ' format-card-v4--featured';
                    }
                    ?>

                    <?php if ( 'link' === $card['action_type'] ) : ?>
                        <a
                            class="<?php echo esc_attr( $card_class ); ?>"
                            href="<?php echo esc_url( $card['action_url'] ); ?>"
                            <?php if ( '_blank' === $card['action_target'] ) : ?>
                                target="_blank"
                                rel="noopener"
                            <?php endif; ?>
                        >
                    <?php else : ?>
                        <a
                            class="<?php echo esc_attr( $card_class ); ?>"
                            <?php if ( '' !== $card['booking_context'] ) : ?>
                                data-ceremony="<?php echo esc_attr( $card['booking_context'] ); ?>"
                            <?php endif; ?>
                            data-modal-open
                            data-source="format-meeting"
                            href="#booking-modal"
                        >
                    <?php endif; ?>

                        <?php if ( '' !== $card['index'] ) : ?>
                            <span class="format-card-v4__index"><?php echo esc_html( $card['index'] ); ?></span>
                        <?php endif; ?>

                        <h3><?php echo nl2br( esc_html( $card['title'] ) ); ?></h3>

                        <?php if ( '' !== $card['text'] ) : ?>
                            <p><?php echo esc_html( $card['text'] ); ?></p>
                        <?php endif; ?>

                        <span class="format-card-v4__link">
                            <span><?php echo esc_html( $card['action_label'] ); ?></span>
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path d="M5 12h14M14 7l5 5-5 5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
                            </svg>
                        </span>

                    </a>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>
<?php
/*
 * Stage 68.2.2c.
 * Editorial copy/order comes from ACF.
 * Category identity, URL, image and product count come from WooCommerce.
 */
$tea_eyebrow = $home_text(
    'home_tea_eyebrow'
);

$tea_title = $home_text(
    'home_tea_title'
);

$tea_intro = $home_text(
    'home_tea_intro'
);

$tea_cta_link = $home_link(
    'home_tea_cta_link'
);

$tea_category_rows = function_exists( 'get_field' )
    ? get_field(
        'home_tea_categories',
        $home_id
    )
    : array();

$tea_categories = array();
$tea_seen_terms = array();

if (
    is_array( $tea_category_rows ) &&
    taxonomy_exists( 'product_cat' )
) {
    foreach ( $tea_category_rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $term_id = isset( $row['category'] )
            ? absint( $row['category'] )
            : 0;

        if (
            ! $term_id ||
            isset( $tea_seen_terms[ $term_id ] )
        ) {
            continue;
        }

        $term = get_term(
            $term_id,
            'product_cat'
        );

        if (
            ! $term ||
            is_wp_error( $term ) ||
            (int) $term->count < 1
        ) {
            continue;
        }

        $thumbnail_id = absint(
            get_term_meta(
                $term_id,
                'thumbnail_id',
                true
            )
        );

        if (
            ! $thumbnail_id ||
            ! wp_attachment_is_image(
                $thumbnail_id
            )
        ) {
            continue;
        }

        $term_link = get_term_link(
            $term,
            'product_cat'
        );

        if (
            is_wp_error( $term_link ) ||
            ! is_string( $term_link ) ||
            '' === $term_link
        ) {
            continue;
        }

        $tea_seen_terms[ $term_id ] = true;

        $tea_categories[] = array(
            'term'         => $term,
            'url'          => $term_link,
            'thumbnail_id' => $thumbnail_id,
            'count'        => (int) $term->count,
        );
    }
}

$tea_position_label = static function ( int $count ): string {
    $mod100 = $count % 100;
    $mod10  = $count % 10;

    if (
        $mod100 >= 11 &&
        $mod100 <= 14
    ) {
        return 'позиций';
    }

    if ( 1 === $mod10 ) {
        return 'позиция';
    }

    if (
        $mod10 >= 2 &&
        $mod10 <= 4
    ) {
        return 'позиции';
    }

    return 'позиций';
};

$has_tea_heading =
    '' !== $tea_eyebrow ||
    '' !== $tea_title ||
    '' !== $tea_intro;

$has_tea =
    $has_tea_heading ||
    ! empty( $tea_categories ) ||
    ! empty( $tea_cta_link );
?>

<?php if ( $has_tea ) : ?>
<section class="section tea-showcase" id="tea">
    <div class="container">

        <?php if ( $has_tea_heading ) : ?>
            <div class="section-heading reveal">

                <?php if ( '' !== $tea_eyebrow || '' !== $tea_title ) : ?>
                    <div>

                        <?php if ( '' !== $tea_eyebrow ) : ?>
                            <p class="eyebrow"><?php echo esc_html( $tea_eyebrow ); ?></p>
                        <?php endif; ?>

                        <?php if ( '' !== $tea_title ) : ?>
                            <h2><?php echo nl2br( esc_html( $tea_title ) ); ?></h2>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

                <?php if ( '' !== $tea_intro ) : ?>
                    <p><?php echo esc_html( $tea_intro ); ?></p>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <?php if ( $tea_categories ) : ?>
            <div class="tea-card-grid">

                <?php foreach ( $tea_categories as $tea_category ) : ?>
                    <?php
                    $term = $tea_category['term'];
                    $count = $tea_category['count'];
                    ?>

                    <a
                        class="tea-card-v4 reveal"
                        href="<?php echo esc_url( $tea_category['url'] ); ?>"
                    >
                        <div class="tea-card-v4__media">
                            <?php
                            echo wp_get_attachment_image(
                                $tea_category['thumbnail_id'],
                                'full',
                                false,
                                array(
                                    'alt'      => $term->name,
                                    'loading'  => 'lazy',
                                    'decoding' => 'async',
                                    'sizes'    => '(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw',
                                )
                            );
                            ?>
                        </div>

                        <div class="tea-card-v4__body">
                            <h3><?php echo esc_html( $term->name ); ?></h3>

                            <p>
                                <?php
                                echo esc_html(
                                    $count .
                                    ' ' .
                                    $tea_position_label( $count )
                                );
                                ?>
                            </p>

                            <b>
                                Перейти
                                <span aria-hidden="true">→</span>
                            </b>
                        </div>
                    </a>

                <?php endforeach; ?>

            </div>
        <?php endif; ?>

        <?php if ( $tea_cta_link ) : ?>
            <div class="section-tail reveal">
                <a
                    class="button button--light"
                    href="<?php echo esc_url( $tea_cta_link['url'] ); ?>"
                    <?php if ( '_blank' === $tea_cta_link['target'] ) : ?>
                        target="_blank"
                        rel="noopener"
                    <?php endif; ?>
                >
                    <?php echo esc_html( $tea_cta_link['title'] ); ?>
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>
<?php
/*
 * Stage 68.2.2b.
 * Ceremony, beginner, space and location use ACF without editorial fallbacks.
 */

/*
 * Resolve the real WooCommerce shop page.
 * No synthetic /shop/ fallback here.
 */
$home_shop_url = '';

if ( function_exists( 'wc_get_page_id' ) ) {
    $home_shop_id = absint( wc_get_page_id( 'shop' ) );

    if (
        $home_shop_id &&
        'publish' === get_post_status( $home_shop_id )
    ) {
        $resolved_shop_url = get_permalink( $home_shop_id );

        if ( is_string( $resolved_shop_url ) ) {
            $home_shop_url = $resolved_shop_url;
        }
    }
}

/*
 * Beginner.
 */
$beginner_eyebrow   = $home_text( 'home_beginner_eyebrow' );
$beginner_title     = $home_text( 'home_beginner_title' );
$beginner_lead      = $home_text( 'home_beginner_lead' );
$beginner_cta_link = $home_link( 'home_beginner_cta_link' );

$beginner_image_id = $home_attachment_id(
    'home_beginner_image'
);

if (
    $beginner_image_id &&
    ! wp_attachment_is_image( $beginner_image_id )
) {
    $beginner_image_id = 0;
}

$beginner_points_raw = function_exists( 'get_field' )
    ? get_field( 'home_beginner_points', $home_id )
    : array();

$beginner_points = array();

if ( is_array( $beginner_points_raw ) ) {
    foreach ( $beginner_points_raw as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $text = isset( $row['point_text'] ) &&
            is_scalar( $row['point_text'] )
                ? trim(
                    wp_strip_all_tags(
                        (string) $row['point_text']
                    )
                )
                : '';

        if ( '' !== $text ) {
            $beginner_points[] = $text;
        }
    }
}

$has_beginner_cta =
    ! empty( $beginner_cta_link );

$has_beginner_copy =
    '' !== $beginner_eyebrow ||
    '' !== $beginner_title ||
    '' !== $beginner_lead ||
    ! empty( $beginner_points ) ||
    $has_beginner_cta;

$has_beginner =
    $has_beginner_copy ||
    $beginner_image_id;

/*
 * Ceremony.
 */
$ceremony_eyebrow = $home_text(
    'home_ceremony_eyebrow'
);

$ceremony_title = $home_text(
    'home_ceremony_title'
);

$ceremony_lead = $home_text(
    'home_ceremony_lead'
);

$ceremony_booking_context = sanitize_key(
    $home_text(
        'home_ceremony_booking_context'
    )
);

$ceremony_secondary_label = $home_text(
    'home_ceremony_secondary_label'
);

$ceremony_booking_label =
    isset( $hero_booking_label ) &&
    is_string( $hero_booking_label )
        ? $hero_booking_label
        : '';

$ceremony_image_id = $home_attachment_id(
    'home_ceremony_image'
);

if (
    $ceremony_image_id &&
    ! wp_attachment_is_image( $ceremony_image_id )
) {
    $ceremony_image_id = 0;
}

$ceremony_steps_raw = function_exists( 'get_field' )
    ? get_field( 'home_ceremony_steps', $home_id )
    : array();

$ceremony_steps = array();

if ( is_array( $ceremony_steps_raw ) ) {
    foreach ( $ceremony_steps_raw as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $number = isset( $row['step_number'] ) &&
            is_scalar( $row['step_number'] )
                ? trim(
                    wp_strip_all_tags(
                        (string) $row['step_number']
                    )
                )
                : '';

        $title = isset( $row['step_title'] ) &&
            is_scalar( $row['step_title'] )
                ? trim(
                    wp_strip_all_tags(
                        (string) $row['step_title']
                    )
                )
                : '';

        $text = isset( $row['step_text'] ) &&
            is_scalar( $row['step_text'] )
                ? trim(
                    wp_strip_all_tags(
                        (string) $row['step_text']
                    )
                )
                : '';

        if (
            '' === $number &&
            '' === $title &&
            '' === $text
        ) {
            continue;
        }

        $ceremony_steps[] = array(
            'number' => $number,
            'title'  => $title,
            'text'   => $text,
        );
    }
}

$has_ceremony_secondary =
    '' !== $ceremony_secondary_label &&
    $has_beginner;

$has_ceremony_actions =
    '' !== $ceremony_booking_label ||
    $has_ceremony_secondary;

$has_ceremony_copy =
    '' !== $ceremony_eyebrow ||
    '' !== $ceremony_title ||
    '' !== $ceremony_lead ||
    ! empty( $ceremony_steps ) ||
    $has_ceremony_actions;

$has_ceremony =
    $ceremony_image_id ||
    $has_ceremony_copy;

/*
 * Space / mixed media gallery.
 */
$space_eyebrow = $home_text(
    'home_space_eyebrow'
);

$space_title = $home_text(
    'home_space_title'
);

$space_intro = $home_text(
    'home_space_intro'
);

$space_about_link = $home_link(
    'home_space_about_link'
);

$space_gallery_raw = function_exists( 'get_field' )
    ? get_field( 'home_space_gallery', $home_id )
    : array();

$space_gallery_items = array();

if ( is_array( $space_gallery_raw ) ) {
    foreach ( $space_gallery_raw as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $image_id = isset( $row['image'] )
            ? absint( $row['image'] )
            : 0;

        if (
            $image_id &&
            ! wp_attachment_is_image( $image_id )
        ) {
            $image_id = 0;
        }

        $video_id = isset( $row['video'] )
            ? absint( $row['video'] )
            : 0;

        $video_url  = '';
        $video_mime = '';

        if ( $video_id ) {
            $possible_video_url = wp_get_attachment_url(
                $video_id
            );

            $possible_video_mime = get_post_mime_type(
                $video_id
            );

            if (
                is_string( $possible_video_url ) &&
                $possible_video_url &&
                in_array(
                    $possible_video_mime,
                    array(
                        'video/mp4',
                        'video/webm',
                    ),
                    true
                )
            ) {
                $video_url  = $possible_video_url;
                $video_mime = $possible_video_mime;
            }
        }

        $image_url = $image_id
            ? wp_get_attachment_url( $image_id )
            : '';

        $image_thumb_url = $image_id
            ? wp_get_attachment_image_url(
                $image_id,
                'medium'
            )
            : '';

        $image_alt = $image_id
            ? trim(
                (string) get_post_meta(
                    $image_id,
                    '_wp_attachment_image_alt',
                    true
                )
            )
            : '';

        /*
         * A valid video takes precedence.
         * Its image, when present, becomes poster/thumbnail.
         */
        if ( '' !== $video_url ) {
            $space_gallery_items[] = array(
                'type'       => 'video',
                'src'        => $video_url,
                'mime'       => $video_mime,
                'image_id'   => $image_id,
                'poster'     => is_string( $image_url )
                    ? $image_url
                    : '',
                'thumb'      => is_string( $image_thumb_url )
                    ? $image_thumb_url
                    : '',
                'alt'        => $image_alt,
            );

            continue;
        }

        if (
            $image_id &&
            is_string( $image_url ) &&
            '' !== $image_url
        ) {
            $space_gallery_items[] = array(
                'type'       => 'image',
                'src'        => $image_url,
                'mime'       => '',
                'image_id'   => $image_id,
                'poster'     => '',
                'thumb'      => is_string( $image_thumb_url )
                    ? $image_thumb_url
                    : '',
                'alt'        => $image_alt,
            );
        }
    }
}

$space_preview_items = array_slice(
    $space_gallery_items,
    0,
    3
);

$space_hidden_items = array_slice(
    $space_gallery_items,
    3
);

$space_hidden_count = count(
    $space_hidden_items
);

$has_space_heading =
    '' !== $space_eyebrow ||
    '' !== $space_title ||
    '' !== $space_intro;

$has_space =
    $has_space_heading ||
    ! empty( $space_gallery_items ) ||
    ! empty( $space_about_link );

/*
 * Location.
 */
$location_eyebrow = $home_text(
    'home_location_eyebrow'
);

$location_title = $home_text(
    'home_location_title'
);

$location_lead = $home_text(
    'home_location_lead'
);

$location_two_gis_label = $home_text(
    'home_location_2gis_label'
);

$location_yandex_label = $home_text(
    'home_location_yandex_label'
);

$location_map_title = $home_text(
    'home_location_map_title'
);

$location_map_url = esc_url_raw(
    $home_text(
        'home_location_map_embed_url'
    )
);

if (
    $location_map_url &&
    ! preg_match(
        '#^https?://#i',
        $location_map_url
    )
) {
    $location_map_url = '';
}

$location_two_gis_url =
    function_exists( 'yabao_site_url' )
        ? yabao_site_url( 'two_gis_url' )
        : '';

$location_yandex_url =
    function_exists( 'yabao_site_url' )
        ? yabao_site_url( 'yandex_maps_url' )
        : '';

$has_location_two_gis =
    '' !== $location_two_gis_label &&
    '' !== $location_two_gis_url;

$has_location_yandex =
    '' !== $location_yandex_label &&
    '' !== $location_yandex_url;

$has_location_actions =
    $has_location_two_gis ||
    $has_location_yandex;

$has_location_copy =
    '' !== $location_eyebrow ||
    '' !== $location_title ||
    '' !== $location_lead ||
    $has_location_actions;

$has_location_map =
    '' !== $location_map_url &&
    '' !== $location_map_title;

$has_location =
    $has_location_copy ||
    $has_location_map;
?>

<?php if ( $has_ceremony ) : ?>
<section class="section section--paper ceremony-v4" id="ceremony">
    <div class="container ceremony-v4__grid">

        <?php if ( $ceremony_image_id ) : ?>
            <div class="ceremony-v4__media reveal">
                <?php
                echo wp_get_attachment_image(
                    $ceremony_image_id,
                    'full',
                    false,
                    array(
                        'loading'  => 'lazy',
                        'decoding' => 'async',
                        'sizes'    => '(max-width: 980px) calc(100vw - 44px), 50vw',
                    )
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if ( $has_ceremony_copy ) : ?>
            <div class="ceremony-v4__copy reveal">

                <?php if ( '' !== $ceremony_eyebrow ) : ?>
                    <p class="eyebrow"><?php echo esc_html( $ceremony_eyebrow ); ?></p>
                <?php endif; ?>

                <?php if ( '' !== $ceremony_title ) : ?>
                    <h2><?php echo esc_html( $ceremony_title ); ?></h2>
                <?php endif; ?>

                <?php if ( '' !== $ceremony_lead ) : ?>
                    <p class="lead"><?php echo esc_html( $ceremony_lead ); ?></p>
                <?php endif; ?>

                <?php if ( $ceremony_steps ) : ?>
                    <ol class="ceremony-steps-v4">
                        <?php foreach ( $ceremony_steps as $step ) : ?>
                            <li>

                                <?php if ( '' !== $step['number'] ) : ?>
                                    <span><?php echo esc_html( $step['number'] ); ?></span>
                                <?php endif; ?>

                                <?php if ( '' !== $step['title'] || '' !== $step['text'] ) : ?>
                                    <div>

                                        <?php if ( '' !== $step['title'] ) : ?>
                                            <strong><?php echo esc_html( $step['title'] ); ?></strong>
                                        <?php endif; ?>

                                        <?php if ( '' !== $step['text'] ) : ?>
                                            <p><?php echo esc_html( $step['text'] ); ?></p>
                                        <?php endif; ?>

                                    </div>
                                <?php endif; ?>

                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <?php if ( $has_ceremony_actions ) : ?>
                    <div class="ceremony-v4__actions">

                        <?php if ( '' !== $ceremony_booking_label ) : ?>
                            <button
                                class="button button--primary"
                                <?php if ( '' !== $ceremony_booking_context ) : ?>
                                    data-ceremony="<?php echo esc_attr( $ceremony_booking_context ); ?>"
                                <?php endif; ?>
                                data-modal-open
                                data-source="ceremony-home"
                                type="button"
                            ><?php echo esc_html( $ceremony_booking_label ); ?></button>
                        <?php endif; ?>

                        <?php if ( $has_ceremony_secondary ) : ?>
                            <a
                                class="button button--light ceremony-v4__secondary"
                                href="#beginner"
                            >
                                <?php echo esc_html( $ceremony_secondary_label ); ?>
                                <span aria-hidden="true">→</span>
                            </a>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>

<?php if ( $has_beginner ) : ?>
<section class="section beginner-v4" id="beginner">
    <div class="container beginner-v4__grid">

        <?php if ( $has_beginner_copy ) : ?>
            <div class="beginner-v4__copy reveal">

                <?php if ( '' !== $beginner_eyebrow ) : ?>
                    <p class="eyebrow"><?php echo esc_html( $beginner_eyebrow ); ?></p>
                <?php endif; ?>

                <?php if ( '' !== $beginner_title ) : ?>
                    <h2><?php echo esc_html( $beginner_title ); ?></h2>
                <?php endif; ?>

                <?php if ( '' !== $beginner_lead ) : ?>
                    <p class="lead"><?php echo esc_html( $beginner_lead ); ?></p>
                <?php endif; ?>

                <?php if ( $beginner_points ) : ?>
                    <ul class="plain-checks">
                        <?php foreach ( $beginner_points as $point ) : ?>
                            <li><?php echo esc_html( $point ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ( $has_beginner_cta ) : ?>
                    <a
                        class="button button--walnut"
                        href="<?php echo esc_url( $beginner_cta_link['url'] ); ?>"
                        <?php if ( '_blank' === $beginner_cta_link['target'] ) : ?>
                            target="_blank"
                            rel="noopener"
                        <?php endif; ?>
                    ><?php echo esc_html( $beginner_cta_link['title'] ); ?></a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <?php if ( $beginner_image_id ) : ?>
            <div class="beginner-v4__media reveal">
                <?php
                echo wp_get_attachment_image(
                    $beginner_image_id,
                    'full',
                    false,
                    array(
                        'loading'  => 'lazy',
                        'decoding' => 'async',
                        'sizes'    => '(max-width: 980px) calc(100vw - 44px), 50vw',
                    )
                );
                ?>
            </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>

<?php if ( $has_space ) : ?>
<section class="section section--dark space-v4" id="space">
    <div class="container">

        <?php if ( $has_space_heading ) : ?>
            <div class="section-heading reveal">

                <?php if ( '' !== $space_eyebrow || '' !== $space_title ) : ?>
                    <div>

                        <?php if ( '' !== $space_eyebrow ) : ?>
                            <p class="eyebrow"><?php echo esc_html( $space_eyebrow ); ?></p>
                        <?php endif; ?>

                        <?php if ( '' !== $space_title ) : ?>
                            <h2><?php echo esc_html( $space_title ); ?></h2>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

                <?php if ( '' !== $space_intro ) : ?>
                    <p><?php echo esc_html( $space_intro ); ?></p>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <?php if ( $space_preview_items ) : ?>
            <div class="space-gallery-v4">

                <?php foreach ( $space_preview_items as $space_index => $item ) : ?>
                    <figure class="<?php echo esc_attr( 0 === $space_index ? 'space-gallery-v4__large reveal' : 'reveal' ); ?>">

                        <a
                            aria-label="<?php echo esc_attr( 'video' === $item['type'] ? 'Открыть видео галереи' : 'Открыть фото галереи' ); ?>"
                            data-fancybox="space-gallery"
                            href="<?php echo esc_url( $item['src'] ); ?>"
                            <?php if ( '' !== $item['thumb'] ) : ?>
                                data-thumb-src="<?php echo esc_url( $item['thumb'] ); ?>"
                            <?php endif; ?>
                            <?php if ( 'video' === $item['type'] ) : ?>
                                data-type="html5video"
                                data-video-format="<?php echo esc_attr( $item['mime'] ); ?>"
                                <?php if ( '' !== $item['poster'] ) : ?>
                                    data-poster="<?php echo esc_url( $item['poster'] ); ?>"
                                <?php endif; ?>
                            <?php endif; ?>
                        >

                            <?php if ( $item['image_id'] ) : ?>
                                <?php
                                echo wp_get_attachment_image(
                                    $item['image_id'],
                                    'full',
                                    false,
                                    array(
                                        'loading'  => 'lazy',
                                        'decoding' => 'async',
                                        'sizes'    => '(max-width: 767px) 60vw, 50vw',
                                    )
                                );
                                ?>
                            <?php elseif ( 'video' === $item['type'] ) : ?>
                                <span
                                    aria-hidden="true"
                                    class="space-gallery-v4__video-placeholder"
                                ></span>
                            <?php endif; ?>

                            <?php if ( 'video' === $item['type'] ) : ?>
                                <span aria-hidden="true" class="space-gallery-v4__play">
                                    <svg viewBox="0 0 24 24">
                                        <path
                                            d="M9 7.5 17 12l-8 4.5z"
                                            fill="currentColor"
                                        ></path>
                                    </svg>
                                </span>
                            <?php endif; ?>

                            <?php if ( 0 === $space_index && $space_hidden_count > 0 ) : ?>
                                <span class="space-gallery-v4__hint">
                                    Показать ещё <?php echo esc_html( (string) $space_hidden_count ); ?>
                                </span>
                            <?php endif; ?>

                        </a>
                    </figure>
                <?php endforeach; ?>

                <?php foreach ( $space_hidden_items as $item ) : ?>
                    <a
                        aria-hidden="true"
                        class="space-gallery-v4__hidden"
                        data-fancybox="space-gallery"
                        href="<?php echo esc_url( $item['src'] ); ?>"
                        tabindex="-1"
                        <?php if ( '' !== $item['thumb'] ) : ?>
                            data-thumb-src="<?php echo esc_url( $item['thumb'] ); ?>"
                        <?php endif; ?>
                        <?php if ( 'video' === $item['type'] ) : ?>
                            data-type="html5video"
                            data-video-format="<?php echo esc_attr( $item['mime'] ); ?>"
                            <?php if ( '' !== $item['poster'] ) : ?>
                                data-poster="<?php echo esc_url( $item['poster'] ); ?>"
                            <?php endif; ?>
                        <?php endif; ?>
                    ></a>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

        <?php if ( $space_about_link ) : ?>
            <div class="section-tail reveal">
                <a
                    class="button button--ghost"
                    href="<?php echo esc_url( $space_about_link['url'] ); ?>"
                    <?php if ( '_blank' === $space_about_link['target'] ) : ?>
                        target="_blank"
                        rel="noopener"
                    <?php endif; ?>
                >
                    <?php echo esc_html( $space_about_link['title'] ); ?>
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>

<?php if ( $has_location ) : ?>
<section class="section section--paper local-v4" id="location">
    <div class="container local-v4__grid">

        <?php if ( $has_location_copy ) : ?>
            <div class="local-v4__copy reveal">

                <?php if ( '' !== $location_eyebrow ) : ?>
                    <p class="eyebrow"><?php echo esc_html( $location_eyebrow ); ?></p>
                <?php endif; ?>

                <?php if ( '' !== $location_title ) : ?>
                    <h2><?php echo esc_html( $location_title ); ?></h2>
                <?php endif; ?>

                <?php if ( '' !== $location_lead ) : ?>
                    <p class="lead"><?php echo esc_html( $location_lead ); ?></p>
                <?php endif; ?>

                <?php if ( $has_location_actions ) : ?>
                    <div class="local-v4__actions">

                        <?php if ( $has_location_two_gis ) : ?>
                            <a
                                class="button button--walnut"
                                href="<?php echo esc_url( $location_two_gis_url ); ?>"
                                rel="noopener"
                                target="_blank"
                            ><?php echo esc_html( $location_two_gis_label ); ?></a>
                        <?php endif; ?>

                        <?php if ( $has_location_yandex ) : ?>
                            <a
                                class="button button--walnut"
                                href="<?php echo esc_url( $location_yandex_url ); ?>"
                                rel="noopener"
                                target="_blank"
                            ><?php echo esc_html( $location_yandex_label ); ?></a>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <?php if ( $has_location_map ) : ?>
            <div class="local-v4__visual local-v4__map">
                <iframe
                    allowfullscreen
                    loading="eager"
                    referrerpolicy="no-referrer-when-downgrade"
                    src="<?php echo esc_url( $location_map_url ); ?>"
                    title="<?php echo esc_attr( $location_map_title ); ?>"
                ></iframe>
            </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>
<section class="section section--dark events-home-v4" id="events">
<div class="container">
<div class="section-heading reveal">
<div>
<p class="eyebrow">Афиша</p>
<h2>События и встречи</h2>
</div>
<p>На сайте уже заявлены несколько форматов встреч. Конкретные даты и программы добавляем только после подтверждения.</p>
</div>
<div class="events-slider-shell reveal" data-events-slider-shell="">
<div class="events-slider swiper" data-events-swiper="">
<div class="swiper-wrapper">
<div class="swiper-slide">
<a class="event-card event-card--link" href="<?php echo esc_url( yabao_page_url( 'events' ) ); ?>">
<div class="event-card__top">
<span class="event-card__type">Формат</span>
<span aria-hidden="true" class="event-card__index">01</span>
</div>
<h3>Музыкальные вечера</h3>
<p>Дату и программу добавим в афишу после подтверждения.</p>
<span class="event-card__more">Подробнее <span aria-hidden="true">→</span></span></a>
</div><div class="swiper-slide">
<a class="event-card event-card--link" href="<?php echo esc_url( yabao_page_url( 'events' ) ); ?>">
<div class="event-card__top">
<span class="event-card__type">Формат</span>
<span aria-hidden="true" class="event-card__index">02</span>
</div>
<h3>Английский клуб</h3>
<p>Дату и программу добавим в афишу после подтверждения.</p>
<span class="event-card__more">Подробнее <span aria-hidden="true">→</span></span></a>
</div><div class="swiper-slide">
<a class="event-card event-card--link" href="<?php echo esc_url( yabao_page_url( 'events' ) ); ?>">
<div class="event-card__top">
<span class="event-card__type">Формат</span>
<span aria-hidden="true" class="event-card__index">03</span>
</div>
<h3>Настольные игры</h3>
<p>Дату и программу добавим в афишу после подтверждения.</p>
<span class="event-card__more">Подробнее <span aria-hidden="true">→</span></span></a>
</div><div class="swiper-slide">
<a class="event-card event-card--link" href="<?php echo esc_url( yabao_page_url( 'events' ) ); ?>">
<div class="event-card__top">
<span class="event-card__type">Формат</span>
<span aria-hidden="true" class="event-card__index">04</span>
</div>
<h3>Чайные клубы</h3>
<p>Дату и программу добавим в афишу после подтверждения.</p>
<span class="event-card__more">Подробнее <span aria-hidden="true">→</span></span></a>
</div><div class="swiper-slide">
<a class="event-card event-card--link" href="<?php echo esc_url( yabao_page_url( 'events' ) ); ?>">
<div class="event-card__top">
<span class="event-card__type">Формат</span>
<span aria-hidden="true" class="event-card__index">05</span>
</div>
<h3>Камерные мастер-классы</h3>
<p>Дату и программу добавим в афишу после подтверждения.</p>
<span class="event-card__more">Подробнее <span aria-hidden="true">→</span></span></a>
</div>
</div>
</div>
<div aria-label="Навигация по мероприятиям" class="events-slider__controls" role="group">
<button aria-label="Предыдущие мероприятия" class="events-slider__button events-slider__button--prev" data-events-prev="" type="button"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="m15 18-6-6 6-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path></svg></button>
<div aria-hidden="true" class="events-slider__pagination" data-events-pagination=""></div>
<button aria-label="Следующие мероприятия" class="events-slider__button events-slider__button--next" data-events-next="" type="button"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="m9 18 6-6-6-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path></svg></button>
</div>
</div>
<div class="section-tail reveal">
<a class="button button--ghost" href="<?php echo esc_url( yabao_page_url( 'events' ) ); ?>">Открыть афишу <span aria-hidden="true">→</span></a>
</div>
</div>
</section><section class="section guides-v4" id="guides">
<div class="container">
<div class="section-heading reveal">
<div><p class="eyebrow">Полезно</p><h2>Разобраться в чае без лекции на три часа</h2></div>
<p>Практические материалы о выборе чая, пуэре, чайной церемонии и маршрутах по центру Челябинска.</p>
</div>
<div class="guide-grid-v4 swiper" data-guides-swiper="">
<div class="swiper-wrapper">
<div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/kak-vybrat-kitayskiy-chay' ) ); ?>"><span>Выбор чая</span><h3>Как выбрать китайский чай</h3><p>С чего начать новичку и как описать мастеру вкусы, которые уже нравятся.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>05.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div>
<div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/chto-takoe-puer' ) ); ?>"><span>Пуэр</span><h3>Что такое пуэр</h3><p>Шэн и шу простыми словами: обработка, вкус, возраст и с чего начать знакомство.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><time datetime="2026-09-05">05.09.2026</time></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div>
<div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/kuda-shodit-na-svidanie-v-chelyabinske' ) ); ?>"><span>Челябинск</span><h3>Куда сходить на свидание</h3><p>Сценарии встречи в центре города, где чайная может стать частью маршрута.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><time datetime="2026-09-05">05.09.2026</time></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span data-article-views="">—</span></span></div></a></div>
<div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/kak-zavarivat-puer' ) ); ?>"><span>Заваривание</span><h3>Как заваривать пуэр</h3><p>Общий алгоритм заваривания и переход к отдельным схемам для Шу и Шэна.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>05.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div>
<div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/kuda-shodit-na-kirovke-chelyabinsk' ) ); ?>"><span>Кировка</span><h3>Куда сходить на Кировке</h3><p>Пешеходный маршрут по центру: Нулевая верста, площадь Искусств, музей и набережная.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>05.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div>
<div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/chaynaya-ceremoniya' ) ); ?>"><span>Церемония</span><h3>Как проходит чайная церемония</h3><p>Что происходит за чайным столом, как работают проливы и что нужно знать перед первым визитом.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>05.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div>
<div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/chto-takoe-gaba' ) ); ?>"><span>Габа</span><h3>Что такое Габа чай</h3><p>Что означает GABA, как обработка без кислорода влияет на чай и почему вкус у Габа бывает разным.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>05.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div><div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/kak-zavarivat-gaba' ) ); ?>"><span>Габа · заваривание</span><h3>Как заваривать Габа</h3><p>Стартовые пропорции, температура и понятная логика проливов для разных стилей Габа.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div><div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/chto-takoe-ulun' ) ); ?>"><span>Улун</span><h3>Что такое улун</h3><p>Почему улуны бывают светлыми и тёмными, как их делают и что на самом деле значит «молочный улун».</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div><div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/kak-zavarivat-ulun' ) ); ?>"><span>Улун · заваривание</span><h3>Как заваривать улун</h3><p>Температура, пропорции и проливы для светлых и тёмных улунов без одной универсальной формулы.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div><div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/smola-puera' ) ); ?>"><span>Пуэр · ча гао</span><h3>Смола пуэра</h3><p>Что такое ча гао, чем концентрат отличается от листового пуэра и как его приготовить.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div><div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/kak-zavarivat-shu-puer' ) ); ?>"><span>Пуэр · Шу</span><h3>Как заваривать Шу пуэр</h3><p>Кипяток, короткие проливы и отдельные схемы для чайника и термоса.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div>
<div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/kak-zavarivat-shen-puer' ) ); ?>"><span>Пуэр · Шэн</span><h3>Как заваривать Шэн пуэр</h3><p>Температура по возрасту, короткие проливы и контроль горечи.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div><div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/shen-i-shu-puer-raznitsa' ) ); ?>"><span>Пуэр · сравнение</span><h3>Шэн и Шу: в чём разница</h3><p>Обработка, вкус, возраст и как выбрать направление для первого знакомства.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div><div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/molochnyy-ulun' ) ); ?>"><span>Улун · молочный</span><h3>Молочный улун</h3><p>Сливочный аромат, ароматизация и отдельная схема заваривания.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div><div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/kak-zavarivat-da-hun-pao' ) ); ?>"><span>Улун · Да Хун Пао</span><h3>Как заваривать Да Хун Пао</h3><p>Горячая вода, короткие проливы и способы настроить вкус утёсного улуна.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div><div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/belyy-chay' ) ); ?>"><span>Белый чай</span><h3>Белый чай</h3><p>Что это за категория, чем отличаются стили и как настроить температуру и проливы.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div><div class="swiper-slide"><a class="guide-card-v4 guide-card-v4--link" href="<?php echo esc_url( yabao_page_url( 'blog/kak-zavarivat-chay-prolivami' ) ); ?>"><span>Заваривание</span><h3>Как заваривать чай проливами</h3><p>Гайвань, короткие проливы и понятная логика настройки температуры, времени и дозировки.</p><div class="guide-card-v4__meta"><span class="guide-card-v4__date"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 20 18" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M3.23183 18H16.7584C18.89 18 20 16.9213 20 14.8604V3.13965C20 1.07865 18.89 0 16.7584 0H3.23183C1.11002 0 0 1.06902 0 3.13965V14.8604C0 16.9213 1.11002 18 3.23183 18ZM3.222 16.0835C2.40668 16.0835 1.95481 15.6693 1.95481 14.8218V5.96148C1.95481 5.11396 2.40668 4.69984 3.222 4.69984H16.7682C17.5835 4.69984 18.0354 5.11396 18.0354 5.96148V14.8218C18.0354 15.6693 17.5835 16.0835 16.7682 16.0835H3.222ZM8.10413 8.02247H8.68369C9.03733 8.02247 9.15521 7.91653 9.15521 7.57945V7.01124C9.15521 6.66453 9.03733 6.55859 8.68369 6.55859H8.10413C7.75049 6.55859 7.63261 6.66453 7.63261 7.01124V7.57945C7.63261 7.91653 7.75049 8.02247 8.10413 8.02247ZM11.3163 8.02247H11.8959C12.2397 8.02247 12.3576 7.91653 12.3576 7.57945V7.01124C12.3576 6.66453 12.2397 6.55859 11.8959 6.55859H11.3163C10.9627 6.55859 10.8448 6.66453 10.8448 7.01124V7.57945C10.8448 7.91653 10.9627 8.02247 11.3163 8.02247ZM14.5187 8.02247H15.0982C15.4519 8.02247 15.5697 7.91653 15.5697 7.57945V7.01124C15.5697 6.66453 15.4519 6.55859 15.0982 6.55859H14.5187C14.1749 6.55859 14.057 6.66453 14.057 7.01124V7.57945C14.057 7.91653 14.1749 8.02247 14.5187 8.02247ZM4.90177 11.1236H5.47151C5.82515 11.1236 5.94303 11.0177 5.94303 10.6709V10.1027C5.94303 9.76565 5.82515 9.65971 5.47151 9.65971H4.90177C4.54813 9.65971 4.43026 9.76565 4.43026 10.1027V10.6709C4.43026 11.0177 4.54813 11.1236 4.90177 11.1236ZM8.10413 11.1236H8.68369C9.03733 11.1236 9.15521 11.0177 9.15521 10.6709V10.1027C9.15521 9.76565 9.03733 9.65971 8.68369 9.65971H8.10413C7.75049 9.65971 7.63261 9.76565 7.63261 10.1027V10.6709C7.63261 11.0177 7.75049 11.1236 8.10413 11.1236ZM11.3163 11.1236H11.8959C12.2397 11.1236 12.3576 11.0177 12.3576 10.6709V10.1027C12.3576 9.76565 12.2397 9.65971 11.8959 9.65971H11.3163C10.9627 9.65971 10.8448 9.76565 10.8448 10.1027V10.6709C10.8448 11.0177 10.9627 11.1236 11.3163 11.1236ZM14.5187 11.1236H15.0982C15.4519 11.1236 15.5697 11.0177 15.5697 10.6709V10.1027C15.5697 9.76565 15.4519 9.65971 15.0982 9.65971H14.5187C14.1749 9.65971 14.057 9.76565 14.057 10.1027V10.6709C14.057 11.0177 14.1749 11.1236 14.5187 11.1236ZM4.90177 14.2151H5.47151C5.82515 14.2151 5.94303 14.1091 5.94303 13.7721V13.2039C5.94303 12.8571 5.82515 12.7512 5.47151 12.7512H4.90177C4.54813 12.7512 4.43026 12.8571 4.43026 13.2039V13.7721C4.43026 14.1091 4.54813 14.2151 4.90177 14.2151ZM8.10413 14.2151H8.68369C9.03733 14.2151 9.15521 14.1091 9.15521 13.7721V13.2039C9.15521 12.8571 9.03733 12.7512 8.68369 12.7512H8.10413C7.75049 12.7512 7.63261 12.8571 7.63261 13.2039V13.7721C7.63261 14.1091 7.75049 14.2151 8.10413 14.2151ZM11.3163 14.2151H11.8959C12.2397 14.2151 12.3576 14.1091 12.3576 13.7721V13.2039C12.3576 12.8571 12.2397 12.7512 11.8959 12.7512H11.3163C10.9627 12.7512 10.8448 12.8571 10.8448 13.2039V13.7721C10.8448 14.1091 10.9627 14.2151 11.3163 14.2151Z" fill="currentColor"></path></svg><span>06.09.2026</span></span><span class="guide-card-v4__views"><svg aria-hidden="true" fill="none" height="20" viewbox="0 0 16 16" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M8.00173 2C11.5964 2 14.5871 4.58667 15.2144 8C14.5877 11.4133 11.5964 14 8.00173 14C4.40706 14 1.4164 11.4133 0.789062 8C1.41573 4.58667 4.40706 2 8.00173 2ZM8.00173 12.6667C9.36138 12.6664 10.6807 12.2045 11.7436 11.3568C12.8066 10.509 13.5503 9.32552 13.8531 8C13.5492 6.67554 12.805 5.49334 11.7421 4.64668C10.6793 3.80003 9.3606 3.33902 8.00173 3.33902C6.64286 3.33902 5.32419 3.80003 4.26131 4.64668C3.19844 5.49334 2.45424 6.67554 2.1504 8C2.45313 9.32552 3.19685 10.509 4.25983 11.3568C5.32281 12.2045 6.64208 12.6664 8.00173 12.6667ZM8.00173 11C7.20608 11 6.44302 10.6839 5.88041 10.1213C5.3178 9.55871 5.00173 8.79565 5.00173 8C5.00173 7.20435 5.3178 6.44129 5.88041 5.87868C6.44302 5.31607 7.20608 5 8.00173 5C8.79738 5 9.56044 5.31607 10.123 5.87868C10.6857 6.44129 11.0017 7.20435 11.0017 8C11.0017 8.79565 10.6857 9.55871 10.123 10.1213C9.56044 10.6839 8.79738 11 8.00173 11ZM8.00173 9.66667C8.44376 9.66667 8.86768 9.49107 9.18024 9.17851C9.4928 8.86595 9.6684 8.44203 9.6684 8C9.6684 7.55797 9.4928 7.13405 9.18024 6.82149C8.86768 6.50893 8.44376 6.33333 8.00173 6.33333C7.5597 6.33333 7.13578 6.50893 6.82322 6.82149C6.51066 7.13405 6.33506 7.55797 6.33506 8C6.33506 8.44203 6.51066 8.86595 6.82322 9.17851C7.13578 9.49107 7.5597 9.66667 8.00173 9.66667Z" fill="currentColor"></path></svg><span>—</span></span></div></a></div></div>
</div>
<div aria-label="Навигация по материалам" class="guide-slider-v4__controls reveal" role="group">
<button aria-label="Предыдущие статьи" class="guide-slider-v4__button guide-slider-v4__button--prev" type="button"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="m15 18-6-6 6-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path></svg></button>
<div aria-hidden="true" class="guide-slider-v4__pagination"></div>
<button aria-label="Следующие статьи" class="guide-slider-v4__button guide-slider-v4__button--next" type="button"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="m9 18 6-6-6-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path></svg></button>
</div>
<div class="section-tail guides-v4__tail"><a class="button button--ghost guides-v4__blog-link" href="<?php echo esc_url( yabao_page_url( 'blog' ) ); ?>">Перейти в блог <span aria-hidden="true">→</span></a></div></div>
</section>
<section class="section section--paper faq-home-v4" id="faq">
<div class="container faq-layout">
<div class="reveal"><p class="eyebrow">Перед первым визитом</p><h2>Короткие ответы</h2><p class="lead">Пять вопросов, которые полезнее отдельной страницы FAQ.</p></div>
<div class="accordion reveal" data-accordion="">
<div class="accordion__item">
<button aria-expanded="false" type="button">Нужно ли разбираться в китайском чае?<i></i></button>
<div class="accordion__panel"><div><p>Нет. Можно начать с привычных вкусов и попросить мастера помочь с выбором.</p></div></div>
</div>
<div class="accordion__item">
<button aria-expanded="false" type="button">Можно ли просто зайти на чай?<i></i></button>
<div class="accordion__panel"><div><p>Да. На главной мы отдельно показываем обычный визит и чайную церемонию как разные сценарии.</p></div></div>
</div>
<div class="accordion__item">
<button aria-expanded="false" type="button">Как проходит чайная церемония?<i></i></button>
<div class="accordion__panel"><div><p>Мастер помогает выбрать чай, заваривает его за чайным столом и объясняет процесс по ходу встречи.</p></div></div>
</div>
<div class="accordion__item">
<button aria-expanded="false" type="button">Можно прийти вдвоём или компанией?<i></i></button>
<div class="accordion__panel"><div><p>Да, но точный формат и доступное количество гостей лучше уточнить при бронировании.</p></div></div>
</div>
<div class="accordion__item">
<button aria-expanded="false" type="button">Можно купить чай с собой?<i></i></button>
<div class="accordion__panel"><div><p>В проекте предусмотрен каталог чая. Актуальное наличие конкретных позиций нужно подтягивать из источника данных.</p></div></div>
</div>
</div>
</div>
</section>
<section class="section final-cta-v4" id="contacts">
<div class="container final-cta-v4__panel reveal">
<div>
<p class="eyebrow">Кирова, 94</p>
<h2>Зайти на чай или выбрать церемонию</h2>
<p>Оставьте заявку, чтобы уточнить свободное время и формат встречи. Для маршрута используйте официальные карточки на картах.</p>
</div>
<div class="final-cta-v4__actions">
<button class="button button--light button--large" data-modal-open="" data-source="final-cta" type="button">Забронировать</button>
<a class="button button--ghost button--large" href="<?php echo esc_url( yabao_page_url( 'contacts' ) ); ?>">Контакты</a>
</div>
</div>
</section>
</main>
<?php get_footer(); ?>
