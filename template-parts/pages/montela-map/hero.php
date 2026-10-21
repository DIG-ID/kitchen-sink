<?php
/**
 * Hero Section in the Montela Map Page Template.
 *
 * Full-viewport split hero: title on the primary half, secondary half
 * on the right (stacked on mobile). The title comes from the ACF field
 * hero.title, falling back to the default slogan.
 *
 * @package kitchensink
 * @subpackage Section
 * @since 1.2.0
 */

$hero       = function_exists( 'get_field' ) ? get_field( 'hero' ) : null;
$hero_title = ! empty( $hero['title'] ) ? $hero['title'] : __( 'forward thinking marketing solutions', 'kitchensink' );
?>
<section class="section-hero grid min-h-svh grid-rows-2 md:grid-cols-2 md:grid-rows-1" aria-labelledby="hero-title">
	<div class="flex items-center bg-primary px-6 py-16 lg:px-16">
		<h1 id="hero-title" class="max-w-xl text-4xl font-bold leading-tight text-white md:text-5xl xl:text-7xl">
			<?php echo esc_html( $hero_title ); ?>
		</h1>
	</div>
	<div class="bg-secondary" aria-hidden="true"></div>
</section>
