<?php
$footer_site_name   = yabao_site_text( 'site_name' );
$footer_tagline     = yabao_site_text( 'site_tagline' );
$footer_description = yabao_site_text( 'footer_description' );

$footer_phone      = yabao_site_text( 'phone' );
$footer_phone_href = yabao_site_phone_href();
$footer_email      = yabao_site_text( 'email' );

$footer_address    = yabao_site_group_text( 'address', 'display' );
$footer_yandex_url = yabao_site_url( 'yandex_maps_url' );
$footer_two_gis    = yabao_site_url( 'two_gis_url' );

$footer_contact_heading = yabao_site_text( 'footer_contact_heading' );
$footer_socials         = yabao_site_social_links( 'footer' );

$legal_name    = yabao_site_text( 'legal_name' );
$inn           = yabao_site_text( 'inn' );
$ogrn          = yabao_site_text( 'ogrn' );
$legal_address = yabao_site_text( 'legal_address' );

$has_footer_brand =
    '' !== $footer_site_name ||
    '' !== $footer_tagline ||
    '' !== $footer_description ||
    '' !== $legal_name ||
    '' !== $inn ||
    '' !== $ogrn ||
    '' !== $legal_address;

$has_footer_visit = has_nav_menu( 'footer_visit' );
$has_footer_info  = has_nav_menu( 'footer_info' );
$has_footer_legal = has_nav_menu( 'footer_legal' );

$has_footer_contacts =
    '' !== $footer_contact_heading ||
    ( '' !== $footer_address && '' !== $footer_yandex_url ) ||
    ( '' !== $footer_phone && '' !== $footer_phone_href ) ||
    '' !== $footer_email ||
    ! empty( $footer_socials );

$footer_columns = array_filter(
    array(
        $has_footer_brand,
        $has_footer_visit,
        $has_footer_info,
        $has_footer_contacts,
    )
);

$footer_column_count = count( $footer_columns );

$has_footer_bottom =
    '' !== $footer_site_name ||
    $has_footer_legal;
?>

<?php if ( $footer_column_count || $has_footer_bottom ) : ?>
<footer class="site-footer">
    <div class="container">

        <?php if ( $footer_column_count ) : ?>
            <div class="footer-grid footer-grid--<?php echo esc_attr( (string) $footer_column_count ); ?>">

                <?php if ( $has_footer_brand ) : ?>
                    <div class="footer-brand">

                        <?php if ( '' !== $footer_site_name || '' !== $footer_tagline ) : ?>
                            <a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <img
                                    alt=""
                                    decoding="async"
                                    height="52"
                                    src="<?php echo esc_url( yabao_asset_url( 'icons/logo-mark.svg' ) ); ?>"
                                    width="52"
                                >

                                <span>
                                    <?php if ( '' !== $footer_site_name ) : ?>
                                        <strong><?php echo esc_html( $footer_site_name ); ?></strong>
                                    <?php endif; ?>

                                    <?php if ( '' !== $footer_tagline ) : ?>
                                        <small><?php echo esc_html( $footer_tagline ); ?></small>
                                    <?php endif; ?>
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if ( '' !== $footer_description ) : ?>
                            <p><?php echo esc_html( $footer_description ); ?></p>
                        <?php endif; ?>

                        <?php if ( '' !== $legal_name || '' !== $inn || '' !== $ogrn || '' !== $legal_address ) : ?>
                            <div class="footer-legal">

                                <?php if ( '' !== $legal_name ) : ?>
                                    <p><strong><?php echo esc_html( $legal_name ); ?></strong></p>
                                <?php endif; ?>

                                <?php if ( '' !== $inn ) : ?>
                                    <p>ИНН <?php echo esc_html( $inn ); ?></p>
                                <?php endif; ?>

                                <?php if ( '' !== $ogrn ) : ?>
                                    <p>ОГРН / ОГРНИП <?php echo esc_html( $ogrn ); ?></p>
                                <?php endif; ?>

                                <?php if ( '' !== $legal_address ) : ?>
                                    <p>Юридический адрес: <?php echo esc_html( $legal_address ); ?></p>
                                <?php endif; ?>

                            </div>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

                <?php if ( $has_footer_visit ) : ?>
                    <?php
                    $visit_locations = get_nav_menu_locations();
                    $visit_menu_id   = isset( $visit_locations['footer_visit'] )
                        ? absint( $visit_locations['footer_visit'] )
                        : 0;
                    $visit_menu = $visit_menu_id ? wp_get_nav_menu_object( $visit_menu_id ) : false;
                    ?>

                    <div>
                        <?php if ( $visit_menu && '' !== trim( $visit_menu->name ) ) : ?>
                            <h3><?php echo esc_html( $visit_menu->name ); ?></h3>
                        <?php endif; ?>

                        <?php
                        wp_nav_menu(
                            array(
                                'theme_location' => 'footer_visit',
                                'container'      => false,
                                'fallback_cb'    => false,
                                'depth'          => 1,
                                'menu_class'     => 'footer-links',
                            )
                        );
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ( $has_footer_info ) : ?>
                    <?php
                    $info_locations = get_nav_menu_locations();
                    $info_menu_id   = isset( $info_locations['footer_info'] )
                        ? absint( $info_locations['footer_info'] )
                        : 0;
                    $info_menu = $info_menu_id ? wp_get_nav_menu_object( $info_menu_id ) : false;
                    ?>

                    <div>
                        <?php if ( $info_menu && '' !== trim( $info_menu->name ) ) : ?>
                            <h3><?php echo esc_html( $info_menu->name ); ?></h3>
                        <?php endif; ?>

                        <?php
                        wp_nav_menu(
                            array(
                                'theme_location' => 'footer_info',
                                'container'      => false,
                                'fallback_cb'    => false,
                                'depth'          => 1,
                                'menu_class'     => 'footer-links',
                            )
                        );
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ( $has_footer_contacts ) : ?>
                    <div>

                        <?php if ( '' !== $footer_contact_heading ) : ?>
                            <h3><?php echo esc_html( $footer_contact_heading ); ?></h3>
                        <?php endif; ?>

                        <?php if (
                            ( '' !== $footer_address && '' !== $footer_yandex_url ) ||
                            ( '' !== $footer_phone && '' !== $footer_phone_href ) ||
                            '' !== $footer_email
                        ) : ?>
                            <ul class="footer-contact-list">

                                <?php if ( '' !== $footer_address && '' !== $footer_yandex_url ) : ?>
                                    <li>
                                        <a href="<?php echo esc_url( $footer_yandex_url ); ?>" rel="noopener" target="_blank">
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <path d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                                                <path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"></path>
                                            </svg>
                                            <span><?php echo esc_html( $footer_address ); ?></span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ( '' !== $footer_phone && '' !== $footer_phone_href ) : ?>
                                    <li>
                                        <a href="<?php echo esc_attr( $footer_phone_href ); ?>">
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <path d="M6.6 10.8a15.4 15.4 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V21c0 .6-.4 1-1 1C10.6 22 2 13.4 2 3c0-.6.4-1 1-1h4.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1l-2.2 2.2Z"></path>
                                            </svg>
                                            <span><?php echo esc_html( $footer_phone ); ?></span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ( '' !== $footer_email ) : ?>
                                    <li>
                                        <a href="mailto:<?php echo esc_attr( antispambot( $footer_email ) ); ?>">
                                            <span><?php echo esc_html( antispambot( $footer_email ) ); ?></span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                            </ul>
                        <?php endif; ?>

                        <?php if ( $footer_socials ) : ?>
                            <div class="footer-socials footer-socials--primary">
                                <?php foreach ( $footer_socials as $social ) : ?>
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

            </div>
        <?php endif; ?>

        <?php if ( $has_footer_bottom ) : ?>
            <div class="footer-bottom">

                <?php if ( '' !== $footer_site_name ) : ?>
                    <span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> «<?php echo esc_html( $footer_site_name ); ?>».</span>
                <?php endif; ?>

                <?php if ( $has_footer_legal ) : ?>
                    <?php
                    wp_nav_menu(
                        array(
                            'theme_location' => 'footer_legal',
                            'container'      => false,
                            'fallback_cb'    => false,
                            'depth'          => 1,
                            'menu_class'     => 'footer-bottom__links',
                        )
                    );
                    ?>
                <?php endif; ?>

                <a
                    class="footer-developer"
                    href="https://limitlesscreators.ru/"
                    rel="noopener"
                    target="_blank"
                >Разработано Abzalov-Lab</a>

            </div>
        <?php endif; ?>

    </div>
</footer>
<?php endif; ?>
<button aria-label="Наверх" class="top-scroll" data-top-scroll type="button"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m6 15 6-6 6 6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path></svg></button>

<dialog aria-labelledby="booking-modal-title" class="modal" id="booking-modal">
	<div class="modal__inner">
		<button aria-label="Закрыть" class="modal__close" data-modal-close type="button">×</button>
		<p class="eyebrow" data-modal-eyebrow>Бронирование</p>
		<h2 data-modal-title id="booking-modal-title">Расскажите, как хотите провести время</h2>
		<p class="modal__intro" data-modal-intro>Оставьте контакты и удобную дату. Отправку формы подключим отдельным интеграционным этапом.</p>
		<form class="booking-form" data-demo-form novalidate>
			<input name="source" type="hidden" value="modal">
			<div class="form-grid">
				<div class="field"><label for="modal-name">Ваше имя *</label><input autocomplete="name" id="modal-name" name="name" required><span class="field__error"></span></div>
				<div class="field"><label for="modal-phone">Телефон *</label><input autocomplete="tel" id="modal-phone" inputmode="tel" name="phone" placeholder="+7 (___) ___-__-__" required type="tel"><span class="field__error"></span></div>
				<div class="field"><label for="modal-date">Дата *</label><input id="modal-date" name="date" required type="date"><span class="field__error"></span></div>
				<div class="field"><label for="modal-time">Время *</label><input id="modal-time" name="time" required type="time"><span class="field__error"></span></div>
				<div class="field"><label for="modal-guests">Количество гостей *</label><select id="modal-guests" name="guests" required><option value="">Выберите</option><option>1 гость</option><option>2 гостя</option><option>3 гостя</option><option>4 гостя</option><option>5-6 гостей</option><option>7-8 гостей</option></select><span class="field__error"></span></div>
				<div class="field"><label for="modal-ceremony">Формат посещения *</label><select id="modal-ceremony" name="ceremony" required><option value="">Выберите формат</option><option value="first">Первое знакомство</option><option value="couple">Встреча вдвоём</option><option value="company">Небольшая компания</option><option value="visit">Свободный визит</option></select><span class="field__error"></span></div>
				<div class="field field--full"><label for="modal-comment">Комментарий</label><textarea id="modal-comment" name="comment" placeholder="Что хотите уточнить"></textarea><span class="field__error"></span></div>
				<label class="checkbox field--full"><input name="consent" required type="checkbox"><span>Согласен на <a href="<?php echo esc_url( yabao_page_url( 'consent' ) ); ?>">обработку персональных данных</a> и ознакомлен с <a href="<?php echo esc_url( yabao_page_url( 'privacy' ) ); ?>">политикой конфиденциальности</a>.</span><span class="field__error"></span></label>
				<div class="field--full form-submit"><button class="button button--walnut" type="submit">Отправить <span aria-hidden="true">→</span></button><div aria-live="polite" class="form-status" data-form-status></div></div>
			</div>
		</form>
	</div>
</dialog>

<?php if ( yabao_woocommerce_active() ) : ?>
<div class="cart-drawer-shell" data-wc-cart-drawer-shell>
	<div class="cart-drawer-backdrop" data-wc-cart-drawer-backdrop aria-hidden="true"></div>
	<aside class="cart-drawer" data-wc-cart-drawer aria-hidden="true" aria-labelledby="cart-drawer-title" role="dialog" aria-modal="true">
		<div class="cart-drawer__header"><div><p class="eyebrow">Быстрый просмотр</p><h2 id="cart-drawer-title">Корзина</h2></div><button class="cart-drawer__close" data-wc-cart-drawer-close aria-label="Закрыть корзину" type="button">×</button></div>
		<div class="cart-drawer__body"><div data-wc-mini-cart-content><?php echo yabao_render_mini_cart_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div>
	</aside>
</div>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
