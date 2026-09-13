<?php
/**
 * Template Name: О чайной
 * Template Post Type: page
 *
 * Production template for /about/.
 * Editor-facing content is stored in ACF Local JSON. No editorial fallbacks.
 */

get_header();

$get_text = static function ( string $name ): string {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$value = get_field( $name );
	if ( ! is_string( $value ) ) {
		return '';
	}

	return trim( wp_strip_all_tags( $value ) );
};

$get_wysiwyg = static function ( string $name ): string {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$value = get_field( $name );
	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return '';
	}

	return wp_kses_post( $value );
};

$hero_title       = $get_text( 'about_hero_title' );
$hero_intro       = $get_text( 'about_hero_intro' );
$mission_image_id = function_exists( 'get_field' ) ? absint( get_field( 'about_mission_image' ) ) : 0;
$mission_eyebrow  = $get_text( 'about_mission_eyebrow' );
$mission_title    = $get_text( 'about_mission_title' );
$mission_lead     = $get_text( 'about_mission_lead' );
$mission_content  = $get_wysiwyg( 'about_mission_content' );
$features_eyebrow = $get_text( 'about_features_eyebrow' );
$features_title   = $get_text( 'about_features_title' );
$features_intro   = $get_text( 'about_features_intro' );

$features = array();
if ( function_exists( 'get_field' ) ) {
	$raw_features = get_field( 'about_features' );
	if ( is_array( $raw_features ) ) {
		foreach ( $raw_features as $raw_feature ) {
			if ( ! is_array( $raw_feature ) ) {
				continue;
			}

			$title = isset( $raw_feature['feature_title'] ) && is_string( $raw_feature['feature_title'] )
				? trim( wp_strip_all_tags( $raw_feature['feature_title'] ) )
				: '';
			$text = isset( $raw_feature['feature_text'] ) && is_string( $raw_feature['feature_text'] )
				? trim( wp_strip_all_tags( $raw_feature['feature_text'] ) )
				: '';

			if ( '' === $title || '' === $text ) {
				continue;
			}

			$features[] = array(
				'title' => $title,
				'text'  => $text,
			);
		}
	}
}

$show_mission = $mission_image_id || $mission_eyebrow || $mission_title || $mission_lead || $mission_content;
$show_features = $features_eyebrow || $features_title || $features_intro || ! empty( $features );
?>
<main id="main-content">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php if ( '' !== $hero_title ) : ?>
			<section class="section section--dark section--compact inner-hero">
				<div class="container inner-hero__grid">
					<div>
						<nav aria-label="Хлебные крошки" class="breadcrumbs breadcrumbs--hero">
							<ol>
								<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a></li>
								<li><span aria-current="page"><?php the_title(); ?></span></li>
							</ol>
						</nav>
						<h1><?php echo esc_html( $hero_title ); ?></h1>
					</div>
					<?php if ( '' !== $hero_intro ) : ?>
						<p class="inner-hero__text"><?php echo esc_html( $hero_intro ); ?></p>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_mission ) : ?>
			<section class="section section--paper">
				<div class="container intro-split intro-split--balanced intro-split--media-card">
					<?php if ( $mission_image_id ) : ?>
						<div class="intro-split__image reveal">
							<?php
							echo wp_get_attachment_image(
								$mission_image_id,
								'full',
								false,
								array(
									'loading'  => 'lazy',
									'decoding' => 'async',
								)
							); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>
					<?php endif; ?>

					<?php if ( $mission_eyebrow || $mission_title || $mission_lead || $mission_content ) : ?>
						<div class="intro-split__content reveal">
							<?php if ( '' !== $mission_eyebrow ) : ?>
								<p class="eyebrow"><?php echo esc_html( $mission_eyebrow ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== $mission_title ) : ?>
								<h2><?php echo esc_html( $mission_title ); ?></h2>
							<?php endif; ?>
							<?php if ( '' !== $mission_lead ) : ?>
								<p class="lead"><?php echo esc_html( $mission_lead ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== $mission_content ) : ?>
								<div class="about-mission-content"><?php echo $mission_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_features ) : ?>
			<section class="section section--dark">
				<div class="container">
					<?php if ( $features_eyebrow || $features_title || $features_intro ) : ?>
						<div class="section-heading reveal">
							<?php if ( $features_eyebrow || $features_title ) : ?>
								<div>
									<?php if ( '' !== $features_eyebrow ) : ?>
										<p class="eyebrow"><?php echo esc_html( $features_eyebrow ); ?></p>
									<?php endif; ?>
									<?php if ( '' !== $features_title ) : ?>
										<h2><?php echo esc_html( $features_title ); ?></h2>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<?php if ( '' !== $features_intro ) : ?>
								<p><?php echo esc_html( $features_intro ); ?></p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $features ) ) : ?>
						<div class="cards-grid cards-grid--two">
							<?php foreach ( $features as $feature ) : ?>
								<article class="card feature-panel reveal">
									<div class="card__body">
										<h3><?php echo esc_html( $feature['title'] ); ?></h3>
										<p><?php echo esc_html( $feature['text'] ); ?></p>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
