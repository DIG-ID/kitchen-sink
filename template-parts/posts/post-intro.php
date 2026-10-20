<?php
/**
 * Post intro: featured image and excerpt.
 *
 * @package kitchensink
 * @subpackage Section
 * @since 1.0.0
 */

?>
<div class="post-intro">
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="post-intro__image">
			<?php the_post_thumbnail( 'large' ); ?>
		</div>
	<?php endif; ?>
	<?php if ( has_excerpt() ) : ?>
		<p class="post-intro__excerpt"><?php the_excerpt(); ?></p>
	<?php endif; ?>
</div>
