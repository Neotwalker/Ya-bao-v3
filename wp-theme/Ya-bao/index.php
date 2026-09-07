<?php
get_header();
?>
<main id="main-content">
	<section class="section section--paper">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<div class="section-heading"><div><p class="eyebrow">Материалы</p><h1><?php echo esc_html( wp_get_document_title() ); ?></h1></div></div>
				<div class="wp-content-list">
					<?php while ( have_posts() ) : the_post(); ?>
						<article <?php post_class( 'wp-content-card' ); ?>>
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php the_excerpt(); ?>
						</article>
					<?php endwhile; ?>
				</div>
				<?php the_posts_pagination(); ?>
			<?php else : ?>
				<div class="shop-state"><h1>Материалов пока нет</h1><p>Содержимое появится после заполнения WordPress.</p></div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
