<?php
$site_name     = yabao_site_text( 'site_name' );
$site_tagline  = yabao_site_text( 'site_tagline' );
$phone         = yabao_site_text( 'phone' );
$phone_href    = yabao_site_phone_href();
$booking_label = yabao_site_text( 'booking_cta_label' );

$header_socials = yabao_site_social_links( 'header' );
$mobile_socials = yabao_site_social_links( 'mobile' );

$address_display = yabao_site_group_text( 'address', 'display' );
$yandex_maps_url = yabao_site_url( 'yandex_maps_url' );
$two_gis_url     = yabao_site_url( 'two_gis_url' );

$has_primary_menu = has_nav_menu( 'primary' );

$has_brand = '' !== $site_name || '' !== $site_tagline;

$has_header_meta =
    ( '' !== $phone && '' !== $phone_href ) ||
    ! empty( $header_socials );

$has_mobile_contacts =
    ( '' !== $phone && '' !== $phone_href ) ||
    ! empty( $mobile_socials );

$has_mobile_meta =
    '' !== $address_display ||
    '' !== $yandex_maps_url ||
    '' !== $two_gis_url;

$has_mobile_navigation =
    $has_primary_menu ||
    '' !== $booking_label ||
    $has_mobile_contacts ||
    $has_mobile_meta;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#17130f">
    <link rel="icon" href="<?php echo esc_url( yabao_asset_url( 'icons/favicon.svg' ) ); ?>" type="image/svg+xml">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content">К основному содержанию</a>

<noscript>
    <div class="no-js-note">Для работы меню и интерактивных элементов включите JavaScript.</div>
</noscript>

<header class="site-header<?php echo is_front_page() ? ' home-header' : ''; ?>" data-header>
    <div class="container header-inner">

        <?php if ( $has_brand ) : ?>
            <a
                aria-label="<?php echo esc_attr( $site_name ?: $site_tagline ); ?>"
                class="brand"
                href="<?php echo esc_url( home_url( '/' ) ); ?>"
            >
                <img
                    alt=""
                    decoding="async"
                    height="56"
                    src="<?php echo esc_url( yabao_asset_url( 'icons/logo-mark.svg' ) ); ?>"
                    width="56"
                >

                <span>
                    <?php if ( '' !== $site_name ) : ?>
                        <strong><?php echo esc_html( $site_name ); ?></strong>
                    <?php endif; ?>

                    <?php if ( '' !== $site_tagline ) : ?>
                        <small><?php echo esc_html( $site_tagline ); ?></small>
                    <?php endif; ?>
                </span>
            </a>
        <?php endif; ?>

        <?php if ( $has_primary_menu ) : ?>
            <nav aria-label="Основная навигация" class="site-nav">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'fallback_cb'    => false,
                        'depth'          => 1,
                    )
                );
                ?>
            </nav>
        <?php endif; ?>

        <div class="header-actions">

            <?php if ( $has_header_meta ) : ?>
                <div class="header-meta">

                    <?php if ( '' !== $phone && '' !== $phone_href ) : ?>
                        <a class="header-phone" href="<?php echo esc_attr( $phone_href ); ?>">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path
                                    d="M6.6 10.8a15.4 15.4 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V21c0 .6-.4 1-1 1C10.6 22 2 13.4 2 3c0-.6.4-1 1-1h4.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1l-2.2 2.2Z"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                ></path>
                            </svg>
                            <span><?php echo esc_html( $phone ); ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ( $header_socials ) : ?>
                        <div class="header-socials">
                            <?php foreach ( $header_socials as $social ) : ?>
                                <?php $icon = yabao_site_social_icon_html( $social ); ?>

                                <?php if ( '' !== $icon ) : ?>
                                    <a
                                        aria-label="<?php echo esc_attr( $social['label'] ); ?>"
                                        class="social-link"
                                        href="<?php echo esc_url( $social['url'] ); ?>"
                                        rel="noopener"
                                        target="_blank"
                                    ><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

            <?php if ( yabao_woocommerce_active() ) : ?>
                <a
                    aria-label="Корзина: <?php echo esc_attr( (string) yabao_cart_count() ); ?> товаров"
                    class="cart-indicator"
                    data-cart-indicator
                    href="<?php echo esc_url( yabao_wc_page_url( 'cart' ) ); ?>"
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M3.5 5h2l1.7 9.1a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"></path>
                        <circle cx="9.5" cy="19" fill="currentColor" r="1"></circle>
                        <circle cx="17.5" cy="19" fill="currentColor" r="1"></circle>
                    </svg>

                    <span
                        aria-hidden="true"
                        class="cart-indicator__count"
                        data-cart-count
                    ><?php echo esc_html( (string) yabao_cart_count() ); ?></span>
                </a>
            <?php endif; ?>

            <?php if ( '' !== $booking_label ) : ?>
                <button
                    class="button button--primary"
                    data-modal-open
                    data-source="header"
                    type="button"
                ><?php echo esc_html( $booking_label ); ?></button>
            <?php endif; ?>

            <?php if ( $has_mobile_navigation ) : ?>
                <button
                    aria-controls="mobile-navigation"
                    aria-expanded="false"
                    class="menu-toggle"
                    data-menu-toggle
                    type="button"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                    <span class="visually-hidden">Открыть меню</span>
                </button>
            <?php endif; ?>

        </div>
    </div>
</header>

<?php if ( $has_mobile_navigation ) : ?>
    <div
        aria-hidden="true"
        class="mobile-nav"
        data-mobile-nav
        id="mobile-navigation"
    >

        <?php if ( $has_primary_menu ) : ?>
            <nav aria-label="Мобильная навигация">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'fallback_cb'    => false,
                        'depth'          => 1,
                    )
                );
                ?>
            </nav>
        <?php endif; ?>

        <?php if ( '' !== $booking_label ) : ?>
            <button
                class="button button--primary mobile-nav__booking"
                data-modal-open
                data-source="mobile-nav"
                type="button"
            ><?php echo esc_html( $booking_label ); ?></button>
        <?php endif; ?>

        <?php if ( $has_mobile_contacts ) : ?>
            <div class="mobile-nav__contacts">

                <?php if ( '' !== $phone && '' !== $phone_href ) : ?>
                    <a class="mobile-nav__phone" href="<?php echo esc_attr( $phone_href ); ?>">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path
                                d="M6.6 10.8a15.4 15.4 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V21c0 .6-.4 1-1 1C10.6 22 2 13.4 2 3c0-.6.4-1 1-1h4.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1l-2.2 2.2Z"
                                fill="none"
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                            ></path>
                        </svg>

                        <span><?php echo esc_html( $phone ); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ( $mobile_socials ) : ?>
                    <div class="mobile-nav__socials">
                        <?php foreach ( $mobile_socials as $social ) : ?>
                            <?php $icon = yabao_site_social_icon_html( $social ); ?>

                            <?php if ( '' !== $icon ) : ?>
                                <a
                                    aria-label="<?php echo esc_attr( $social['label'] ); ?>"
                                    class="social-link"
                                    href="<?php echo esc_url( $social['url'] ); ?>"
                                    rel="noopener"
                                    target="_blank"
                                ><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <?php if ( $has_mobile_meta ) : ?>
            <div class="mobile-nav__meta">

                <?php if ( '' !== $address_display ) : ?>
                    <strong><?php echo esc_html( $address_display ); ?></strong>
                <?php endif; ?>

                <?php if ( '' !== $two_gis_url ) : ?>
                    <a href="<?php echo esc_url( $two_gis_url ); ?>" rel="noopener" target="_blank">Открыть в 2ГИС</a>
                <?php endif; ?>

                <?php if ( '' !== $yandex_maps_url ) : ?>
                    <a href="<?php echo esc_url( $yandex_maps_url ); ?>" rel="noopener" target="_blank">Открыть в Яндекс Картах</a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
<?php endif; ?>