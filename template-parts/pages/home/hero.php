<?php
/**
 * Hero Section in the Home Page Template.
 *
 * Full-viewport split hero: slogan on the primary half, secondary half
 * on the right (stacked on mobile).
 *
 * @package kitchensink
 * @subpackage Section
 * @since 1.0.0
 */

?>
<section class="section-hero grid min-h-svh grid-rows-2 md:grid-cols-2 md:grid-rows-1" aria-labelledby="hero-title">
	<div class="flex items-center bg-primary px-6 py-16 lg:px-16">
		<h1 id="hero-title" class="max-w-xl text-4xl font-bold leading-tight text-white md:text-5xl xl:text-7xl">
			<?php esc_html_e( 'forward thinking marketing solutions', 'kitchensink' ); ?>
		</h1>
	</div>
	<div class="bg-secondary" aria-hidden="true"></div>
</section>
