<?php
/**
 * Post header: title, date, categories and featured image.
 *
 * @package kitchensink
 * @subpackage Section
 * @since 1.0.0
 */

?>
<header class="post-header">
	<h1 class="post-header__title"><?php the_title(); ?></h1>
	<div class="post-header__meta">
		<time class="post-header__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
			<?php echo esc_html( get_the_date() ); ?>
		</time>
	</div>
</header>
