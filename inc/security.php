<?php
/**
 * Theme security hardening.
 *
 * Complements the SG Security (Security Optimizer) plugin — which is
 * installed on every dig.id site — by covering vulnerabilities the plugin
 * does not address: REST API user enumeration, author archive enumeration,
 * login error information disclosure, XML-RPC/pingback abuse, front-end
 * search abuse and missing HTTP security headers.
 *
 * @package kitchensink
 * @subpackage Functionality
 * @since 1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Disable XML-RPC.
 *
 * Not used by our standard stack (no Jetpack, no remote-publishing apps).
 * XML-RPC is a known attack surface: pingback.ping can be abused for
 * reflected DDoS and enumeration, and system.multicall amplifies
 * credential-stuffing attempts. Disables the authenticated methods, strips
 * the pingback methods (which work without authentication) and removes the
 * X-Pingback header advertising the endpoint.
 *
 * Remove these filters on projects that need XML-RPC integrations.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Strip the pingback methods from the XML-RPC server.
 *
 * @param array $methods Registered XML-RPC methods.
 * @return array Filtered methods.
 */
function kitchensink_remove_xmlrpc_pingback_methods( $methods ) {
	unset( $methods['pingback.ping'] );
	unset( $methods['pingback.extensions.getPingbacks'] );

	return $methods;
}
add_filter( 'xmlrpc_methods', 'kitchensink_remove_xmlrpc_pingback_methods' );

/**
 * Remove the X-Pingback header from HTTP responses.
 *
 * @param array $headers HTTP response headers.
 * @return array Filtered headers.
 */
function kitchensink_remove_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );

	return $headers;
}
add_filter( 'wp_headers', 'kitchensink_remove_pingback_header' );

/**
 * Block public access to the REST API users endpoints.
 *
 * By default WordPress exposes /wp-json/wp/v2/users to unauthenticated
 * visitors, leaking real usernames (and sometimes login emails) that can
 * be used in brute-force attacks.
 *
 * @param array $endpoints Registered REST API endpoints.
 * @return array Filtered endpoints.
 */
function kitchensink_disable_rest_user_endpoints( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'] );
		unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}

	return $endpoints;
}
add_filter( 'rest_endpoints', 'kitchensink_disable_rest_user_endpoints' );

/**
 * Block author archive enumeration.
 *
 * Requests like ?author=1 or /author/username/ redirect to a URL containing
 * the real username. Redirect unauthenticated visitors to the homepage.
 *
 * @return void
 */
function kitchensink_disable_author_archives() {
	if ( is_author() && ! is_user_logged_in() ) {
		wp_safe_redirect( home_url(), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'kitchensink_disable_author_archives' );

/**
 * Disable front-end search by default.
 *
 * The starter ships without a search UI, but WordPress still answers ?s=
 * requests: each one runs an expensive LIKE query over the whole posts
 * table, and internal search result URLs are a common spam-crawling
 * target. Serve a clean 404 instead of redirecting (redirects to the
 * homepage would create soft-404s and point spam URLs at it).
 *
 * On projects that need search: remove this filter and build search.php
 * (plus searchform.php) designed for that project. Note that WooCommerce
 * product search also uses ?s=, so shops with search need this removed.
 *
 * @return void
 */
function kitchensink_disable_search() {
	if ( is_search() && ! is_admin() ) {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'kitchensink_disable_search' );

/**
 * Remove author data from oEmbed responses.
 *
 * The oEmbed responses (/wp-json/oembed/1.0/embed) include author_name and
 * author_url, which leak usernames even when author archives are blocked.
 *
 * @param array $data oEmbed response data.
 * @return array Filtered response data.
 */
function kitchensink_remove_oembed_author( $data ) {
	unset( $data['author_name'] );
	unset( $data['author_url'] );

	return $data;
}
add_filter( 'oembed_response_data', 'kitchensink_remove_oembed_author' );

/**
 * Remove users from core XML sitemaps.
 *
 * WordPress core sitemaps include a users provider that lists all authors.
 * Yoast SEO replaces core sitemaps on our stack, but this acts as a safety
 * net for sites where Yoast is disabled or misconfigured.
 *
 * @param WP_Sitemaps_Provider|false $provider Sitemap provider instance.
 * @param string                     $name     Provider name.
 * @return WP_Sitemaps_Provider|false Provider, or false to remove it.
 */
function kitchensink_remove_users_sitemap( $provider, $name ) {
	if ( 'users' === $name ) {
		return false;
	}

	return $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'kitchensink_remove_users_sitemap', 10, 2 );

/**
 * Use a generic login error message.
 *
 * Default login errors reveal whether a username exists ("incorrect
 * password" vs "unknown username"), letting attackers confirm valid
 * accounts. Return the same message for every failure.
 *
 * @return string Generic error message.
 */
function kitchensink_generic_login_error() {
	return esc_html__( 'Login failed: invalid credentials.', 'kitchensink' );
}
add_filter( 'login_errors', 'kitchensink_generic_login_error' );

/**
 * Disable application passwords.
 *
 * Not used by our standard stack. Remove this filter on projects that need
 * authenticated REST API integrations via application passwords.
 */
add_filter( 'wp_is_application_passwords_available', '__return_false' );

/**
 * Send standard HTTP security headers.
 *
 * Covers headers the SG Security plugin does not reliably send: HSTS,
 * X-Frame-Options, X-Content-Type-Options, Referrer-Policy and
 * Permissions-Policy.
 *
 * Content-Security-Policy is intentionally left out — it must be built per
 * project (GTM, embeds, payment scripts) and always tested first in
 * Report-Only mode before enforcing.
 *
 * @return void
 */
function kitchensink_security_headers() {
	if ( headers_sent() ) {
		return;
	}

	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );

	// Adjust per project if the site itself needs any of these features
	// (e.g. geolocation for a store locator / map "locate me" button).
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );

	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}
}
add_action( 'send_headers', 'kitchensink_security_headers' );
