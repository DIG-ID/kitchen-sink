<?php
/**
 * Map Section in the Montela Map Page Template.
 *
 * Intro (title, button, description), the interactive map module and
 * a features bar. Content comes from the ACF group map_section.
 *
 * @package kitchensink
 * @subpackage Section
 * @since 1.2.0
 */

$map_section = function_exists( 'get_field' ) ? get_field( 'map_section' ) : null;

if ( empty( $map_section ) ) {
	return;
}

$map_button   = ! empty( $map_section['button']['url'] ) ? $map_section['button'] : null;
$map_features = ! empty( $map_section['features'] ) ? $map_section['features'] : array();
$map_icons    = get_theme_file_uri( 'assets/images/montela-map/' );
$map_icon_set = array( 'gondola', 'bus', 'parking', 'playground', 'lounge', 'village' );
?>
<section class="section-map bg-montela-cream px-4 py-20 font-montela-sans text-montela-brown md:px-6 xl:py-56" aria-labelledby="section-map-title">
	<div class="mx-auto flex max-w-[1814px] flex-col gap-[30px]">

		<div class="flex flex-col gap-5 xl:px-[150px]">
			<?php if ( ! empty( $map_section['title'] ) ) : ?>
				<h2 id="section-map-title" class="max-w-[934px] font-montela-serif text-5xl font-medium leading-[0.9] tracking-[0.5px] md:text-7xl xl:text-[90px] xl:leading-[80px]">
					<?php echo esc_html( $map_section['title'] ); ?>
				</h2>
			<?php endif; ?>

			<div class="flex flex-col-reverse gap-8 md:flex-row md:items-end md:justify-between">
				<?php if ( $map_button ) : ?>
					<a class="section-map__button group inline-flex items-center gap-[2px] self-start drop-shadow-[0_4px_2px_rgba(0,0,0,0.25)]" href="<?php echo esc_url( $map_button['url'] ); ?>"<?php echo ! empty( $map_button['target'] ) ? ' target="' . esc_attr( $map_button['target'] ) . '" rel="noopener"' : ''; ?>>
						<span class="bg-montela-sage px-[22px] py-[14px] text-sm font-bold uppercase leading-none tracking-[0.84px] text-montela-cream">
							<?php echo esc_html( $map_button['title'] ); ?>
						</span>
						<img src="<?php echo esc_url( $map_icons . 'arrow-button.svg' ); ?>" alt="" width="45" height="44" class="h-[44px] w-[45px]">
					</a>
				<?php endif; ?>

				<?php if ( ! empty( $map_section['description'] ) ) : ?>
					<p class="max-w-[593px] text-lg font-light leading-8 tracking-[0.5px] md:text-xl">
						<?php echo esc_html( $map_section['description'] ); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<div class="mt-8 xl:mt-[100px]">
			<?php
			get_template_part(
				'template-parts/modules/interactive-map',
				null,
				array(
					'image_id' => ! empty( $map_section['map_image'] ) ? $map_section['map_image'] : 0,
					'pins'     => ! empty( $map_section['pins'] ) ? $map_section['pins'] : array(),
				)
			);
			?>
		</div>

		<?php if ( $map_features ) : ?>
			<ul class="grid grid-cols-1 gap-6 border border-montela-sage px-6 py-8 sm:grid-cols-2 lg:flex lg:items-center lg:justify-between lg:gap-4 lg:px-10" role="list">
				<?php foreach ( $map_features as $map_feature ) : ?>
					<li class="flex flex-1 items-center gap-3">
						<?php if ( in_array( $map_feature['icon'], $map_icon_set, true ) ) : ?>
							<img src="<?php echo esc_url( $map_icons . 'feature-' . $map_feature['icon'] . '.svg' ); ?>" alt="" width="64" height="64" class="size-16 shrink-0">
						<?php endif; ?>
						<span class="text-base font-medium leading-5 tracking-[0.5px] text-montela-sage">
							<?php echo esc_html( $map_feature['text'] ); ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

	</div>
</section>
