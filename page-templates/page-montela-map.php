<?php
/**
 *
 * Template Name: Montela Map
 *
 * @package kitchensink
 * @subpackage Template
 * @since 1.2.0
 */

get_header();
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		do_action( 'before_main_content' );
			get_template_part( 'template-parts/pages/montela-map/hero' );
			get_template_part( 'template-parts/pages/montela-map/map' );
		do_action( 'after_main_content' );
	endwhile;
endif;
get_footer();
