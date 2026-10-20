<?php
/**
 * Customise the WordPress admin and login pages.
 *
 * @package kitchensink
 * @subpackage Functionality
 * @since 1.0.0
 */

/**
 * Removes default dashboard widgets to keep the admin clean.
 */
function kitchensink_disable_default_dashboard_widgets() {
	global $wp_meta_boxes;
	unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health'] );
	unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] );
	unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'] );
	unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments'] );
	unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links'] );
	unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins'] );
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'] );
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary'] );
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press'] );
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts'] );
}

add_action( 'wp_dashboard_setup', 'kitchensink_disable_default_dashboard_widgets', 999 );

/**
 * Registers the dig.id welcome widget as the first dashboard box.
 *
 * Shown to every role (unlike the core welcome panel, which is
 * admin-only), so clients get onboarding guidance too.
 *
 * @since 1.12.0
 */
function kitchensink_add_welcome_dashboard_widget() {
	wp_add_dashboard_widget(
		'kitchensink_welcome',
		sprintf(
			/* translators: %s: site name. */
			esc_html__( 'Welcome to %s', 'kitchensink' ),
			get_bloginfo( 'name' )
		),
		'kitchensink_render_welcome_widget',
		null,
		null,
		'normal',
		'high'
	);
}

add_action( 'wp_dashboard_setup', 'kitchensink_add_welcome_dashboard_widget' );

/**
 * Renders the dig.id welcome widget content.
 *
 * The support button links to DIGID_SUPPORT_EMAIL (define it in
 * wp-config.php or functions.php to override per project). The
 * knowledgebase button is a disabled placeholder until the dig.id
 * knowledge base exists.
 *
 * @since 1.12.0
 */
function kitchensink_render_welcome_widget() {
	$user          = wp_get_current_user();
	$support_email = defined( 'DIGID_SUPPORT_EMAIL' ) && DIGID_SUPPORT_EMAIL ? DIGID_SUPPORT_EMAIL : 'hello@dig.id';
	?>
	<div class="kitchensink-welcome">
		<div class="kitchensink-welcome__content">
			<img class="kitchensink-welcome__logo" src="<?php echo esc_url( get_theme_file_uri( '/assets/svg/logo.svg' ) ); ?>" alt="" width="50" height="60" />
			<div class="kitchensink-welcome__text">
				<p class="kitchensink-welcome__greeting">
					<?php
					printf(
						/* translators: %s: current user display name. */
						esc_html__( 'Hi %s, welcome to your website dashboard.', 'kitchensink' ),
						esc_html( $user->display_name )
					);
					?>
				</p>
				<p><?php esc_html_e( 'This website is built and maintained by dig.id. If you need help managing pages, products or translations, our team is one click away.', 'kitchensink' ); ?></p>
			</div>

		</div>
		<p class="kitchensink-welcome__actions">
			<a class="button button-primary" href="<?php echo esc_url( 'mailto:' . $support_email ); ?>">
				<?php esc_html_e( 'Contact support', 'kitchensink' ); ?>
			</a>
			<span class="button button-secondary kitchensink-welcome__soon" aria-disabled="true" data-tooltip="<?php esc_attr_e( 'Coming soon', 'kitchensink' ); ?>">
				<?php esc_html_e( 'Knowledgebase', 'kitchensink' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(coming soon)', 'kitchensink' ); ?></span>
			</span>
		</p>
	</div>
	<?php
}

/**
 * Removes the default WordPress welcome panel from the dashboard.
 *
 * Unhooking wp_welcome_panel() removes the panel content and frees the
 * welcome_panel hook for a future dig.id branded welcome widget.
 *
 * @since 1.12.0
 */
function kitchensink_remove_welcome_panel() {
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
}

add_action( 'admin_init', 'kitchensink_remove_welcome_panel' );

/**
 * Keeps the welcome panel dismissed so its empty container never renders.
 *
 * Core prints the #welcome-panel wrapper whenever the show_welcome_panel
 * user meta is 1 (the default for new users), even with the content
 * unhooked — forcing it to 0 on the dashboard hides the empty shell.
 *
 * @since 1.12.0
 */
function kitchensink_dismiss_welcome_panel() {
	$user_id = get_current_user_id();

	if ( 1 === (int) get_user_meta( $user_id, 'show_welcome_panel', true ) ) {
		update_user_meta( $user_id, 'show_welcome_panel', 0 );
	}
}

add_action( 'load-index.php', 'kitchensink_dismiss_welcome_panel' );

/**
 * Enqueues the custom login page stylesheet (wp-login.php screens only).
 */
function kitchensink_login_css() {
	kitchensink_vite_enqueue_style( 'admin-login-css', 'assets/sass/admin-login.sass' );
}

add_action( 'login_enqueue_scripts', 'kitchensink_login_css', 10 );

/**
 * Enqueues the custom admin stylesheet (wp-admin screens only).
 *
 * @since 1.9.0
 */
function kitchensink_admin_css() {
	kitchensink_vite_enqueue_style( 'admin-dashboard-css', 'assets/sass/admin-dashboard.sass' );
}

add_action( 'admin_enqueue_scripts', 'kitchensink_admin_css', 10 );

/**
 * Enqueues the admin bar stylesheet wherever the bar is rendered.
 *
 * The admin bar markup is identical on wp-admin and on the front end,
 * so a single small bundle is loaded in both contexts (only when the
 * bar is actually showing).
 *
 * @since 1.13.0
 */
function kitchensink_admin_bar_css() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	kitchensink_vite_enqueue_style( 'kitchensink-admin-bar-css', 'assets/sass/admin-bar.sass', array( 'admin-bar' ) );
}

add_action( 'admin_enqueue_scripts', 'kitchensink_admin_bar_css', 10 );
add_action( 'wp_enqueue_scripts', 'kitchensink_admin_bar_css', 10 );

/**
 * Changes the login logo link to point to the site homepage.
 *
 * @return string
 */
function kitchensink_login_url() {
	return home_url();
}

add_filter( 'login_headerurl', 'kitchensink_login_url' );

/**
 * Changes the login logo alt text to the site name.
 *
 * @return string
 */
function kitchensink_login_title() {
	return get_option( 'blogname' );
}

add_filter( 'login_headertext', 'kitchensink_login_title' );

/**
 * Replaces the admin footer text with the agency credit.
 */
function kitchensink_custom_admin_footer() {
	echo wp_kses_post( __( '<span id="footer-thankyou">Developed by <a href="https://dig.id" target="_blank">dig.id</a></span>.', 'kitchensink' ) );
}

add_filter( 'admin_footer_text', 'kitchensink_custom_admin_footer' );

/**
 * Removes the WordPress logo from the admin toolbar.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 */
function kitchensink_remove_wp_logo( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'wp-logo' );
}

add_action( 'admin_bar_menu', 'kitchensink_remove_wp_logo', 999 );

/**
 * Allows SVG uploads for administrator and editor users.
 *
 * @param array $upload_mimes Allowed mime types.
 * @return array
 */
add_filter(
	'upload_mimes',
	function ( $upload_mimes ) {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return $upload_mimes;
		}

		$upload_mimes['svg']  = 'image/svg+xml';
		$upload_mimes['svgz'] = 'image/svg+xml';

		return $upload_mimes;
	}
);

/**
 * Fixes SVG mime type detection on upload.
 *
 * @param array        $wp_check_filetype_and_ext Filetype check result.
 * @param string       $file                      Full path to the file.
 * @param string       $filename                  The file name.
 * @param string[]     $mimes                     Allowed mime types.
 * @param string|false $real_mime                 Detected mime type.
 * @return array
 */
add_filter(
	'wp_check_filetype_and_ext',
	function ( $wp_check_filetype_and_ext, $file, $filename, $mimes, $real_mime ) {

		if ( ! $wp_check_filetype_and_ext['type'] ) {
			$check_filetype  = wp_check_filetype( $filename, $mimes );
			$ext             = $check_filetype['ext'];
			$type            = $check_filetype['type'];
			$proper_filename = $filename;

			if ( $type && str_starts_with( $type, 'image/' ) && 'svg' !== $ext ) {
				$ext  = false;
				$type = false;
			}

			$wp_check_filetype_and_ext = compact( 'ext', 'type', 'proper_filename' );
		}

		return $wp_check_filetype_and_ext;
	},
	10,
	5
);

// Disable comments completely.
add_action(
	'admin_init',
	function () {
		global $pagenow;

		if ( 'edit-comments.php' === $pagenow ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );

		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}
);

add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

add_action(
	'admin_menu',
	function () {
		remove_menu_page( 'edit-comments.php' );
	}
);

add_action(
	'init',
	function () {
		if ( is_admin_bar_showing() ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		}
	}
);

// Redirect attachment pages to the parent post or homepage.
add_action(
	'template_redirect',
	function () {
		global $post;
		if ( ! is_attachment() || ! isset( $post->post_parent ) || ! is_numeric( $post->post_parent ) ) {
			return;
		}

		if ( 0 !== $post->post_parent && 'trash' !== get_post_status( $post->post_parent ) ) {
			wp_safe_redirect( get_permalink( $post->post_parent ), 301 );
		} else {
			wp_safe_redirect( get_bloginfo( 'wpurl' ), 302 );
		}
		exit;
	},
	1
);
