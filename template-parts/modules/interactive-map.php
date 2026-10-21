<?php
/**
 * Interactive map module: zoomable/pannable illustrated map with pins,
 * a detail popup per pin and an optional season switch.
 *
 * Each season is a layer (image + pins) stacked on the same canvas; the
 * switch cross-fades between them and keeps the current zoom/position.
 * The pins are printed in the artwork, so each pin is a transparent
 * hit-area positioned in percentages on top of its printed marker.
 * Behaviour lives in assets/js/interactive-map.js.
 *
 * Expected $args:
 * - seasons (array) Rows with key, label, image_id (int) and pins
 *                   (rows with label, x, y, title, description, button).
 *                   Seasons without an image are skipped; the first one
 *                   is shown by default.
 *
 * @package kitchensink
 * @subpackage Module
 * @since 1.2.0
 */

$map_seasons = array();
if ( ! empty( $args['seasons'] ) && is_array( $args['seasons'] ) ) {
	foreach ( $args['seasons'] as $map_season ) {
		if ( ! empty( $map_season['image_id'] ) ) {
			$map_seasons[] = $map_season;
		}
	}
}

if ( ! $map_seasons ) {
	return;
}

$map_id      = wp_unique_id( 'interactive-map-' );
$map_card_id = $map_id . '-card';
$map_icons   = get_theme_file_uri( 'assets/images/montela-map/' );

/**
 * Builds the image attributes for a season layer. WordPress downscales
 * big uploads (big image threshold), so the original file is offered as
 * the largest srcset candidate to keep deep zoom sharp.
 */
$map_image_attr = static function ( $image_id ) {
	$attr = array(
		'class'     => 'interactive-map__image',
		'loading'   => 'lazy',
		'draggable' => 'false',
		'sizes'     => '100vw',
	);

	$original = wp_get_original_image_path( $image_id );
	$srcset   = wp_get_attachment_image_srcset( $image_id, 'full' );
	if ( $original && $srcset && wp_get_attachment_image_url( $image_id, 'full' ) !== wp_get_original_image_url( $image_id ) ) {
		$size = wp_getimagesize( $original );
		if ( $size ) {
			$attr['srcset'] = $srcset . ', ' . esc_url( wp_get_original_image_url( $image_id ) ) . ' ' . (int) $size[0] . 'w';
		}
	}

	return $attr;
};
?>
<div class="interactive-map" id="<?php echo esc_attr( $map_id ); ?>" data-map>
	<div class="interactive-map__viewport" data-map-viewport role="group" tabindex="0" aria-label="<?php esc_attr_e( 'Interactive map. Use the zoom buttons and drag or scroll to explore.', 'kitchensink' ); ?>">
		<div class="interactive-map__canvas" data-map-canvas>
			<?php foreach ( $map_seasons as $season_index => $map_season ) : ?>
				<div class="interactive-map__layer<?php echo 0 === $season_index ? ' is-active' : ''; ?>" data-map-layer="<?php echo esc_attr( $map_season['key'] ); ?>"<?php echo 0 === $season_index ? '' : ' inert'; ?>>
					<?php echo wp_get_attachment_image( $map_season['image_id'], 'full', false, $map_image_attr( $map_season['image_id'] ) ); ?>

					<?php
					foreach ( $map_season['pins'] as $index => $map_pin ) :
						if ( '' === (string) $map_pin['x'] || '' === (string) $map_pin['y'] ) {
							continue;
						}
						?>
						<button class="interactive-map__pin" type="button" data-map-pin data-content="<?php echo esc_attr( $map_id . '-' . $map_season['key'] . '-pin-' . $index ); ?>" style="<?php echo esc_attr( '--x: ' . floatval( $map_pin['x'] ) . '%; --y: ' . floatval( $map_pin['y'] ) . '%;' ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $map_card_id ); ?>">
							<span class="sr-only"><?php echo esc_html( trim( $map_pin['label'] . '. ' . $map_pin['title'], '. ' ) ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="interactive-map__hint" data-map-hint aria-hidden="true">
		<?php esc_html_e( 'Click the map to zoom with the scroll wheel', 'kitchensink' ); ?>
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

	<?php if ( count( $map_seasons ) > 1 ) : ?>
		<div class="interactive-map__seasons" role="group" aria-label="<?php esc_attr_e( 'Season', 'kitchensink' ); ?>">
			<?php foreach ( $map_seasons as $season_index => $map_season ) : ?>
				<button class="interactive-map__season<?php echo 0 === $season_index ? ' is-active' : ''; ?>" type="button" data-map-season="<?php echo esc_attr( $map_season['key'] ); ?>" aria-pressed="<?php echo 0 === $season_index ? 'true' : 'false'; ?>">
					<span class="interactive-map__season-icon" style="<?php echo esc_attr( '--icon: url(' . $map_icons . 'season-' . sanitize_key( $map_season['key'] ) . '.svg)' ); ?>" aria-hidden="true"></span>
					<?php echo esc_html( $map_season['label'] ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="interactive-map__card" id="<?php echo esc_attr( $map_card_id ); ?>" data-map-card role="dialog" aria-modal="false" aria-label="<?php esc_attr_e( 'Location details', 'kitchensink' ); ?>" hidden>
		<button class="interactive-map__card-close" type="button" data-map-card-close aria-label="<?php esc_attr_e( 'Close details', 'kitchensink' ); ?>">&times;</button>
		<div class="interactive-map__card-body" data-map-card-body></div>
	</div>

	<?php // Popup contents as real (hidden) HTML: translatable and crawlable. ?>
	<div hidden>
		<?php foreach ( $map_seasons as $map_season ) : ?>
			<?php foreach ( $map_season['pins'] as $index => $map_pin ) : ?>
				<div id="<?php echo esc_attr( $map_id . '-' . $map_season['key'] . '-pin-' . $index ); ?>">
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
		<?php endforeach; ?>
	</div>
</div>
