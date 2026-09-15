<?php
/**
 * Theme content types.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register events / Афиша.
 */
function yabao_register_event_post_type(): void {
    $labels = array(
        'name'               => 'Афиша',
        'singular_name'      => 'Событие',
        'menu_name'          => 'Афиша',
        'name_admin_bar'     => 'Событие',
        'add_new'            => 'Добавить событие',
        'add_new_item'       => 'Добавить событие',
        'new_item'           => 'Новое событие',
        'edit_item'          => 'Редактировать событие',
        'view_item'          => 'Посмотреть событие',
        'all_items'          => 'Все события',
        'search_items'       => 'Найти события',
        'not_found'          => 'События не найдены',
        'not_found_in_trash' => 'В корзине событий нет',
        'archives'           => 'Афиша',
    );

    register_post_type(
        'event',
        array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-calendar-alt',
            'menu_position'      => 6,
            'hierarchical'       => false,
            'has_archive'        => 'events',
            'rewrite'            => array(
                'slug'       => 'events',
                'with_front' => false,
            ),
            'supports'           => array(
                'title',
                'editor',
                'excerpt',
                'thumbnail',
                'revisions',
            ),
            'query_var'          => true,
        )
    );
}
add_action( 'init', 'yabao_register_event_post_type' );

/**
 * Event archive editorial settings live in a dedicated ACF options subpage.
 * Event post data remains in the existing per-event ACF group.
 */
function yabao_register_event_settings_page(): void {
    if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
        return;
    }

    acf_add_options_sub_page(
        array(
            'page_title'  => 'Настройки афиши',
            'menu_title'  => 'Афиша',
            'menu_slug'   => 'yabao-events-settings',
            'parent_slug' => 'yabao-global-settings',
            'capability'  => 'edit_theme_options',
            'redirect'    => false,
            'post_id'     => 'option',
        )
    );
}
add_action( 'acf/init', 'yabao_register_event_settings_page', 10 );

require_once get_template_directory() . '/inc/shop-seo.php';
