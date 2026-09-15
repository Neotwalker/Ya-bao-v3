<?php
/**
 * Checkout legal consent integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve one published legal page without inventing a fallback URL.
 */
function yabao_checkout_legal_page_url( string $slug ): string {
	$page = get_page_by_path( sanitize_title( $slug ), OBJECT, 'page' );

	if ( ! $page || 'publish' !== $page->post_status ) {
		return '';
	}

	$url = get_permalink( $page->ID );

	return is_string( $url ) ? $url : '';
}

/**
 * The approved checkout uses one explicit required consent checkbox instead of
 * WooCommerce's generic checkout privacy paragraph.
 */
function yabao_checkout_privacy_policy_text( string $text, string $type ): string {
	return 'checkout' === $type ? '' : $text;
}
add_filter( 'woocommerce_get_privacy_policy_text', 'yabao_checkout_privacy_policy_text', 10, 2 );

/**
 * Render the consent in the existing "Доставка и комментарий" checkout panel.
 * Links open separately so the customer does not lose the filled checkout form.
 */
function yabao_checkout_render_legal_consent(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}

	$consent_url = yabao_checkout_legal_page_url( 'consent' );
	$privacy_url = yabao_checkout_legal_page_url( 'privacy' );

	if ( '' === $consent_url || '' === $privacy_url ) {
		return;
	}

	$checked = isset( $_POST['yabao_personal_data_consent'] )
		&& '1' === wc_clean( wp_unslash( $_POST['yabao_personal_data_consent'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	?>
	<label class="checkbox checkout-consent">
		<input
			aria-required="true"
			name="yabao_personal_data_consent"
			required
			type="checkbox"
			value="1"
			<?php checked( $checked ); ?>
		>
		<span>
			Согласен на
			<a href="<?php echo esc_url( $consent_url ); ?>" rel="noopener" target="_blank">обработку персональных данных</a>
			и ознакомлен с
			<a href="<?php echo esc_url( $privacy_url ); ?>" rel="noopener" target="_blank">политикой конфиденциальности</a>.
		</span>
		<span class="field__error"></span>
	</label>
	<?php
}
add_action( 'woocommerce_checkout_shipping', 'yabao_checkout_render_legal_consent', 20 );

/**
 * Server-side enforcement: an order must not be created without consent.
 */
function yabao_checkout_validate_legal_consent( array $data, WP_Error $errors ): void {
	unset( $data );

	$consent_url = yabao_checkout_legal_page_url( 'consent' );
	$privacy_url = yabao_checkout_legal_page_url( 'privacy' );

	if ( '' === $consent_url || '' === $privacy_url ) {
		$errors->add(
			'yabao_checkout_legal_pages_missing',
			'Оформление заказа временно недоступно: юридические страницы не настроены.'
		);
		return;
	}

	$accepted = isset( $_POST['yabao_personal_data_consent'] )
		&& '1' === wc_clean( wp_unslash( $_POST['yabao_personal_data_consent'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( ! $accepted ) {
		$errors->add(
			'yabao_personal_data_consent_required',
			'Подтвердите согласие на обработку персональных данных и ознакомление с политикой конфиденциальности.'
		);
	}
}
add_action( 'woocommerce_after_checkout_validation', 'yabao_checkout_validate_legal_consent', 10, 2 );
