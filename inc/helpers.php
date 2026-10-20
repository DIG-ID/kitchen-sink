<?php
/**
 * General-purpose helper functions.
 *
 * @package kitchensink
 * @subpackage Helpers
 * @since 1.0.0
 */

/**
 * Outputs one or more values to the browser console.
 *
 * Development/debugging helper. No-op unless WP_DEBUG is enabled, so stray
 * calls can never leak data (queries, user info, API responses) to visitors
 * in production.
 *
 * @param mixed ...$data Values to log.
 */
function kitchensink_console_log( ...$data ) {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	$json = wp_json_encode( $data );
	add_action(
		'shutdown',
		function () use ( $json ) {
			wp_print_inline_script_tag( "console.log({$json});" );
		}
	);
}
