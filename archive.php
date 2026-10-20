<?php
/**
 * The template for displaying archive pages.
 *
 * @package kitchensink
 * @subpackage Template
 * @since 1.0.0
 */

get_header();
do_action( 'before_main_content' );
get_template_part( 'template-parts/pages/page-header' );
?>
<section class="section-archive">
	<div class="theme-container">
		<?php if ( have_posts() ) : ?>
			<div class="archive-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article <?php post_class( 'card-post' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" class="card-post__image">
								<?php the_post_thumbnail( 'large' ); ?>
							</a>
						<?php endif; ?>
						<div class="card-post__content">
							<h2 class="card-post__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>
							<p class="card-post__excerpt"><?php the_excerpt(); ?></p>
						</div>
					</article>
					<?php
				endwhile;
				?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Keine Beiträge gefunden.', 'kitchensink' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
do_action( 'after_main_content' );
get_footer();
