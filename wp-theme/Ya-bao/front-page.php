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
<section class="section tea-showcase" id="tea">
<div class="container">
<div class="section-heading reveal">
<div><p class="eyebrow">Китайский чай</p><h2>Выбрать чай можно легко</h2></div>
<p>Для первого знакомства достаточно описать вкус, который вам ближе. Ниже - несколько направлений китайского чая.</p>
</div>
<div class="tea-card-grid">
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea' ); ?>">
<div class="tea-card-v4__media"><img alt="Темные улуны" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-dark-oolong-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-dark-oolong-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-dark-oolong-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Темные улуны</h3><p>5 позиций</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea' ); ?>">
<div class="tea-card-v4__media"><img alt="Красный чай" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-red-tea-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-red-tea-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-red-tea-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Красный чай</h3><p>3 позиции</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea' ); ?>">
<div class="tea-card-v4__media"><img alt="Габа" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-gaba-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-gaba-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-gaba-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Габа</h3><p>4 позиции</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea&category=white-tea' ); ?>">
<div class="tea-card-v4__media"><img alt="Белый чай" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-white-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-white-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-white-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Белый чай</h3><p>5 позиций</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea' ); ?>">
<div class="tea-card-v4__media"><img alt="Светлые улуны" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-light-oolong-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-light-oolong-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-light-oolong-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Светлые улуны</h3><p>2 позиции</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea&category=sheng-puer' ); ?>">
<div class="tea-card-v4__media"><img alt="Шэн пуэры" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-sheng-puer-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-sheng-puer-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-sheng-puer-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Шэн пуэры</h3><p>6 позиций</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea&category=shu-puer' ); ?>">
<div class="tea-card-v4__media"><img alt="Шу Пуэры" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-shu-puer-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-shu-puer-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-shu-puer-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Шу Пуэры</h3><p>3 позиции</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea' ); ?>">
<div class="tea-card-v4__media"><img alt="Желтый чай" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-yellow-tea-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-yellow-tea-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-yellow-tea-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Желтый чай</h3><p>1 позиция</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea' ); ?>">
<div class="tea-card-v4__media"><img alt="Хэй Ча" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-hei-cha-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-hei-cha-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-hei-cha-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Хэй Ча</h3><p>1 позиция</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea' ); ?>">
<div class="tea-card-v4__media"><img alt="Лимонады" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-lemonades-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-lemonades-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-lemonades-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Лимонады</h3><p>7 позиций</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea' ); ?>">
<div class="tea-card-v4__media"><img alt="Бабл ти" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-bubble-tea-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-bubble-tea-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-bubble-tea-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Бабл ти</h3><p>7 позиций</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
<a class="tea-card-v4 reveal" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '?type=tea' ); ?>">
<div class="tea-card-v4__media"><img alt="Авторский чай" decoding="async" height="669" loading="lazy" sizes="(max-width: 767px) 46vw, (max-width: 1024px) 31vw, 24vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-category-author-tea-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-category-author-tea-real-640.webp' ) ); ?> 640w, <?php echo esc_url( yabao_asset_url( 'images/tea-category-author-tea-real.webp' ) ); ?> 1074w" width="1074"/></div>
<div class="tea-card-v4__body"><h3>Авторский чай</h3><p>7 позиций</p><b>Перейти <span aria-hidden="true">→</span></b></div>
</a>
</div>
<div class="section-tail reveal"><a class="button button--light" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '' ); ?>">Открыть магазин <span aria-hidden="true">→</span></a></div>
</div>
</section>
<section class="section section--paper ceremony-v4" id="ceremony">
<div class="container ceremony-v4__grid">
<div class="ceremony-v4__media reveal"><img alt="Чайная церемония за чайным столом" decoding="async" height="1086" loading="lazy" sizes="(max-width: 980px) calc(100vw - 44px), 50vw" src="<?php echo esc_url( yabao_asset_url( 'images/ceremony-intro-real.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/ceremony-intro-real-720.webp' ) ); ?> 720w, <?php echo esc_url( yabao_asset_url( 'images/ceremony-intro-real.webp' ) ); ?> 1448w" width="1448"/></div>
<div class="ceremony-v4__copy reveal">
<p class="eyebrow">Чайная церемония</p>
<h2>Чайная церемония в Челябинске</h2>
<p class="lead">Не нужно заранее знать сорта и правила. Мастер помогает выбрать чай и объясняет процесс по ходу встречи.</p>
<ol class="ceremony-steps-v4">
<li><span>1</span><div><strong>Выбираем формат</strong><p>Для первого знакомства, встречи вдвоём или небольшой компании.</p></div></li>
<li><span>2</span><div><strong>Подбираем чай</strong><p>Можно отталкиваться от знакомых вкусов и ароматов.</p></div></li>
<li><span>3</span><div><strong>Садимся за чайный стол</strong><p>Мастер заваривает чай и ведёт церемонию без лекционного тона.</p></div></li>
</ol>
<div class="ceremony-v4__actions">
<button class="button button--primary" data-ceremony="first" data-modal-open="" data-source="ceremony-home" type="button">Забронировать</button>
<a class="button button--light ceremony-v4__secondary" href="#beginner">Я впервые знакомлюсь с китайским чаем <span aria-hidden="true">→</span></a>
</div>
</div>
</div>
</section>
<section class="section beginner-v4" id="beginner">
<div class="container beginner-v4__grid">
<div class="beginner-v4__copy reveal">
<p class="eyebrow">Для первого визита</p>
<h2>Впервые знакомитесь с китайским чаем?</h2>
<p class="lead">Не нужно отличать Шэн от Шу и вспоминать названия посуды. В начале достаточно сказать, что вы обычно любите по вкусу.</p>
<ul class="plain-checks">
<li>Не нужно знать сорта заранее.</li>
<li>Можно попросить объяснить каждый шаг простыми словами.</li>
<li>Можно просто прийти на чай без церемонии.</li>
</ul>
<a class="button button--walnut" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) . '' ); ?>">Перейти в магазин</a>
</div>
<div class="beginner-v4__media reveal"><img alt="Знакомство с китайским чаем" decoding="async" height="1086" loading="lazy" sizes="(max-width: 980px) calc(100vw - 44px), 50vw" src="<?php echo esc_url( yabao_asset_url( 'images/tea-beginner.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/tea-beginner-720.webp' ) ); ?> 720w, <?php echo esc_url( yabao_asset_url( 'images/tea-beginner.webp' ) ); ?> 1448w" width="1448"/></div>
</div>
</section>
<section class="section section--dark space-v4" id="space">
<div class="container">
<div class="section-heading reveal">
<div><p class="eyebrow">Пространство</p><h2>Как выглядит Я Бао Завари</h2></div>
<p>Тёмное дерево, детали чайного стола, живое общение и китайский андеграунд. Здесь одинаково уместны первый визит и привычная встреча за чаем.</p>
</div>
<div class="space-gallery-v4">
<figure class="space-gallery-v4__large reveal">
<a aria-label="Открыть фото интерьера чайной" data-fancybox="space-gallery" href="<?php echo esc_url( yabao_asset_url( 'images/real-hall.webp' ) ); ?>">
<span class="space-gallery-v4__hint">Открыть галерею</span>
<img alt="Интерьер чайной Я Бао Завари" decoding="async" height="1086" loading="lazy" sizes="(max-width: 767px) 60vw, 50vw" src="<?php echo esc_url( yabao_asset_url( 'images/real-hall.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/real-hall-720.webp' ) ); ?> 720w, <?php echo esc_url( yabao_asset_url( 'images/real-hall.webp' ) ); ?> 1448w" width="1448"/>
</a>
</figure>
<figure class="reveal">
<a aria-label="Открыть фото деталей интерьера" data-fancybox="space-gallery" href="<?php echo esc_url( yabao_asset_url( 'images/real-corner.webp' ) ); ?>">
<img alt="Детали интерьера Я Бао Завари" decoding="async" height="1086" loading="lazy" sizes="(max-width: 767px) 60vw, 50vw" src="<?php echo esc_url( yabao_asset_url( 'images/real-corner.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/real-corner-720.webp' ) ); ?> 720w, <?php echo esc_url( yabao_asset_url( 'images/real-corner.webp' ) ); ?> 1448w" width="1448"/>
</a>
</figure>
<figure class="reveal">
<a aria-label="Открыть фото чайного стола" data-fancybox="space-gallery" href="<?php echo esc_url( yabao_asset_url( 'images/gallery-2.webp' ) ); ?>">
<img alt="Чайный стол" decoding="async" height="800" loading="lazy" sizes="(max-width: 767px) 40vw, 33vw" src="<?php echo esc_url( yabao_asset_url( 'images/gallery-2.webp' ) ); ?>" srcset="<?php echo esc_url( yabao_asset_url( 'images/gallery-2-720.webp' ) ); ?> 720w, <?php echo esc_url( yabao_asset_url( 'images/gallery-2.webp' ) ); ?> 1100w" width="1100"/>
</a>
</figure>
</div>
<div class="section-tail reveal"><a class="button button--ghost" href="<?php echo esc_url( yabao_page_url( 'about' ) ); ?>">О чайной <span aria-hidden="true">→</span></a></div>
</div>
</section>
<section class="section section--paper local-v4" id="location">
<div class="container local-v4__grid">
<div class="local-v4__copy reveal">
<p class="eyebrow">Кировка</p>
<h2>Чайная на Кировке, в центре Челябинска</h2>
<p class="lead">Я Бао Завари находится на улице Кирова, 94. Можно зайти во время прогулки по центру или приехать специально на чайную церемонию.</p>
<div class="local-v4__actions">
<a class="button button--walnut" href="https://2gis.ru/chelyabinsk/firm/70000001110715460" rel="noopener" target="_blank">Открыть в 2ГИС</a>
<a class="button button--walnut" href="https://yandex.ru/maps/org/ya_bao_zavari/112754832500/" rel="noopener" target="_blank">Яндекс Карты</a>
</div>
</div>
<div class="local-v4__visual local-v4__map">
<iframe allowfullscreen="" loading="eager" referrerpolicy="no-referrer-when-downgrade" src="https://yandex.ru/map-widget/v1/?ll=61.402655%2C55.164081&mode=search&oid=112754832500&ol=biz&z=17" title="Я Бао Завари на Яндекс Картах"></iframe>
</div>
</div>
</section>
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
