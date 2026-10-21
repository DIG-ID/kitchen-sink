<?php
/**
 * Interactive map module: zoomable/pannable illustrated map with pins
 * and a detail popup per pin.
 *
 * The pins are printed in the artwork, so each pin is a transparent
 * hit-area positioned in percentages on top of its printed marker.
 * Behaviour lives in assets/js/interactive-map.js.
 *
 * Expected $args:
 * - image_id (int)   Map image attachment ID.
 * - pins     (array) Rows with label, x, y, title, description, button.
 *
 * @package kitchensink
 * @subpackage Module
 * @since 1.2.0
 */

$map_image_id = ! empty( $args['image_id'] ) ? (int) $args['image_id'] : 0;
$map_pins     = ! empty( $args['pins'] ) && is_array( $args['pins'] ) ? $args['pins'] : array();

if ( ! $map_image_id ) {
	return;
}

$map_id      = wp_unique_id( 'interactive-map-' );
$map_card_id = $map_id . '-card';
$map_icons   = get_theme_file_uri( 'assets/images/montela-map/' );

// The upload is downscaled by WordPress (big image threshold); offer the
// original file as the largest srcset candidate so deep zoom stays sharp.
$map_image_attr = array(
	'class'     => 'interactive-map__image',
	'loading'   => 'lazy',
	'draggable' => 'false',
	'sizes'     => '100vw',
);
$map_original   = wp_get_original_image_path( $map_image_id );
$map_srcset     = wp_get_attachment_image_srcset( $map_image_id, 'full' );
if ( $map_original && $map_srcset && wp_get_attachment_image_url( $map_image_id, 'full' ) !== wp_get_original_image_url( $map_image_id ) ) {
	$map_original_size = wp_getimagesize( $map_original );
	if ( $map_original_size ) {
		$map_image_attr['srcset'] = $map_srcset . ', ' . esc_url( wp_get_original_image_url( $map_image_id ) ) . ' ' . (int) $map_original_size[0] . 'w';
	}
}
?>
<div class="interactive-map" id="<?php echo esc_attr( $map_id ); ?>" data-map>
	<div class="interactive-map__viewport" data-map-viewport role="group" tabindex="0" aria-label="<?php esc_attr_e( 'Interactive map. Use the zoom buttons and drag or scroll to explore.', 'kitchensink' ); ?>">
		<div class="interactive-map__canvas" data-map-canvas>
			<?php echo wp_get_attachment_image( $map_image_id, 'full', false, $map_image_attr ); ?>

			<?php
			foreach ( $map_pins as $index => $map_pin ) :
				if ( '' === (string) $map_pin['x'] || '' === (string) $map_pin['y'] ) {
					continue;
				}
				$pin_label = trim( $map_pin['label'] . '. ' . $map_pin['title'], '. ' );
				?>
				<button class="interactive-map__pin" type="button" data-map-pin data-content="<?php echo esc_attr( $map_id . '-pin-' . $index ); ?>" style="<?php echo esc_attr( '--x: ' . floatval( $map_pin['x'] ) . '%; --y: ' . floatval( $map_pin['y'] ) . '%;' ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $map_card_id ); ?>">
					<span class="sr-only"><?php echo esc_html( $pin_label ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="interactive-map__controls" role="group" aria-label="<?php esc_attr_e( 'Map controls', 'kitchensink' ); ?>">
		<button class="interactive-map__control" type="button" data-map-zoom-in aria-label="<?php esc_attr_e( 'Zoom in', 'kitchensink' ); ?>">
			<img src="<?php echo esc_url( $map_icons . 'zoom-in.svg' ); ?>" alt="" width="24" height="24">
		</button>
		<button class="interactive-map__control" type="button" data-map-zoom-out aria-label="<?php esc_attr_e( 'Zoom out', 'kitchensink' ); ?>">
			<img src="<?php echo esc_url( $map_icons . 'zoom-out.svg' ); ?>" alt="" width="23" height="2">
		</button>
		<button class="interactive-map__control" type="button" data-map-reset aria-label="<?php esc_attr_e( 'Reset map', 'kitchensink' ); ?>">
			<img src="<?php echo esc_url( $map_icons . 'reset.svg' ); ?>" alt="" width="42" height="42">
		</button>
		<button class="interactive-map__control" type="button" data-map-fullscreen aria-label="<?php esc_attr_e( 'Toggle fullscreen', 'kitchensink' ); ?>" aria-pressed="false" hidden>
			<img src="<?php echo esc_url( $map_icons . 'fullscreen.svg' ); ?>" alt="" width="42" height="42">
		</button>
	</div>

	<div class="interactive-map__card" id="<?php echo esc_attr( $map_card_id ); ?>" data-map-card role="dialog" aria-modal="false" aria-label="<?php esc_attr_e( 'Location details', 'kitchensink' ); ?>" hidden>
		<button class="interactive-map__card-close" type="button" data-map-card-close aria-label="<?php esc_attr_e( 'Close details', 'kitchensink' ); ?>">&times;</button>
		<div class="interactive-map__card-body" data-map-card-body></div>
	</div>

	<?php // Popup contents as real (hidden) HTML: translatable and crawlable. ?>
	<div hidden>
		<?php foreach ( $map_pins as $index => $map_pin ) : ?>
			<div id="<?php echo esc_attr( $map_id . '-pin-' . $index ); ?>">
				<h3 class="interactive-map__card-title">
					<?php echo esc_html( trim( $map_pin['label'] . '. ' . $map_pin['title'], '. ' ) ); ?>
				</h3>
				<?php if ( ! empty( $map_pin['description'] ) ) : ?>
					<p class="interactive-map__card-text"><?php echo esc_html( $map_pin['description'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $map_pin['button']['url'] ) ) : ?>
					<a class="interactive-map__card-button" href="<?php echo esc_url( $map_pin['button']['url'] ); ?>"<?php echo ! empty( $map_pin['button']['target'] ) ? ' target="' . esc_attr( $map_pin['button']['target'] ) . '" rel="noopener"' : ''; ?>>
						<?php echo esc_html( $map_pin['button']['title'] ? $map_pin['button']['title'] : __( 'Learn more', 'kitchensink' ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
