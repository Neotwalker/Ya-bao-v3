<?php
get_header();
?>
<main id="main-content">
	<?php while ( have_posts() ) : the_post(); ?>
	<section class="section section--dark section--compact inner-hero">
		<div class="container inner-hero__grid">
			<div><nav aria-label="Хлебные крошки" class="breadcrumbs breadcrumbs--hero"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a></li><li><span aria-current="page"><?php the_title(); ?></span></li></ol></nav><h1><?php the_title(); ?></h1></div>
		</div>
	</section>
	<section class="section section--paper"><div class="container wp-entry-content"><?php the_content(); ?></div></section>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
