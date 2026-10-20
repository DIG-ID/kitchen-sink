<?php
/**
 * Performance optimizations: dequeue unused styles/scripts, clean head, disable unused features.
 *
 * @package kitchensink
 * @subpackage Functionality
 * @since 1.0.0
 */

/**
 * Dequeues styles that are not needed on the frontend.
 * Runs at priority 100 to ensure it fires after all other enqueue hooks.
 */
function kitchensink_dequeue_unused_styles() {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}

add_action( 'wp_enqueue_scripts', 'kitchensink_dequeue_unused_styles', 100 );

/**
 * Dequeues the wp-embed script — embedding WP posts in external sites is rarely needed.
 */
function kitchensink_dequeue_unused_scripts() {
	wp_dequeue_script( 'wp-embed' );
}

add_action( 'wp_footer', 'kitchensink_dequeue_unused_scripts' );

/**
 * Removes unnecessary links and meta tags from wp_head.
 */
function kitchensink_clean_head() {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
}

add_action( 'init', 'kitchensink_clean_head' );

/**
 * Disables the WordPress emoji detection script and styles.
 */
function kitchensink_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'kitchensink_disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', 'kitchensink_disable_emojis_dns_prefetch', 10, 2 );
}

add_action( 'init', 'kitchensink_disable_emojis' );

/**
 * Removes the wpemoji TinyMCE plugin.
 *
 * @param array $plugins Active TinyMCE plugins.
 * @return array
 */
function kitchensink_disable_emojis_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	}
	return array();
}

/**
 * Removes the emoji CDN URL from DNS prefetch hints.
 *
 * @param array  $urls          Resource hint URLs.
 * @param string $relation_type Hint type (dns-prefetch, preconnect, etc.).
 * @return array
 */
function kitchensink_disable_emojis_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
		$urls          = array_diff( $urls, array( $emoji_svg_url ) );
	}
	return $urls;
}

/**
 * Prevents WordPress from pinging its own URLs (self pingbacks).
 *
 * @param array $links List of URLs to ping.
 */
function kitchensink_disable_self_pingbacks( &$links ) {
	foreach ( $links as $l => $link ) {
		if ( str_starts_with( $link, get_option( 'home' ) ) ) {
			unset( $links[ $l ] );
		}
	}
}

add_action( 'pre_ping', 'kitchensink_disable_self_pingbacks' );
