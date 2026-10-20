<?php
/**
 * Theme functions and definitions.
 *
 * @package kitchensink
 */

// Theme constants: safe defaults only. Each can be overridden per
// environment in wp-config.php — real API keys belong there (outside the
// repo), never committed to the theme. See SETUP.md.

// Font provider configuration — set per project.
// Options: 'google' | 'adobe' | 'none'.
if ( ! defined( 'DIGID_FONT_PROVIDER' ) ) {
	define( 'DIGID_FONT_PROVIDER', 'none' );
}
if ( ! defined( 'DIGID_GOOGLE_FONTS_URL' ) ) {
	define( 'DIGID_GOOGLE_FONTS_URL', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap' );
}
if ( ! defined( 'DIGID_ADOBE_FONTS_ID' ) ) {
	define( 'DIGID_ADOBE_FONTS_ID', '' ); // Typekit kit ID, e.g. 'abc1234'.
}

// Google Maps API key — define the real key in wp-config.php. Empty = disabled.
if ( ! defined( 'DIGID_GOOGLE_MAPS_API_KEY' ) ) {
	define( 'DIGID_GOOGLE_MAPS_API_KEY', '' );
}

// Theme setup: supports, nav menus, sidebars, plugin filters.
require get_template_directory() . '/inc/theme-setup.php';

// Vite asset loader (manifest in production, dev server with HMR locally).
require get_template_directory() . '/inc/vite.php';

// Scripts and styles.
require get_template_directory() . '/inc/enqueue.php';

// Admin and login customisations.
require get_template_directory() . '/inc/theme-admin-settings.php';

// Template tags and reusable output functions.
require get_template_directory() . '/inc/theme-template-tags.php';

// Helper/utility functions.
require get_template_directory() . '/inc/helpers.php';

// Performance optimizations.
require get_template_directory() . '/inc/performance.php';

// Security hardening.
require get_template_directory() . '/inc/security.php';
