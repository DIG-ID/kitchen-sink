<?php
/**
 * The template for displaying all single posts.
 *
 * @package kitchensink
 * @subpackage Template
 * @since 1.0.0
 */

get_header();
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		do_action( 'before_main_content' );
			do_action( 'before_post_content' );
				get_template_part( 'template-parts/posts/post-header' );
				get_template_part( 'template-parts/posts/post-intro' );
				get_template_part( 'template-parts/posts/post-content' );
			do_action( 'after_post_content' );
		do_action( 'after_main_content' );
	endwhile;
endif;
get_footer();
