<?php
/*
  Plugin Name: Auth using Google Authenticator
  Plugin URI: https://github.com/lukaspawlik/auth-using-google-authenticator
  Description: WordPress Login Addon using Google Authenticator
  Author: Lukasz Pawlik
  Version: 1.0
  Author URI: https://github.com/lukaspawlik
 */

require_once 'base32.php';
require_once 'authclass.php';
require_once 'functions.php';

define( 'XLTTOTPAuth', '1.0' );
define( 'TOTP_AUTH_URL', plugin_dir_url( __FILE__ ) );

$functions = new Xlt_TOTP_Auth_Functions();

add_action( 'show_user_profile', array( $functions, 'profile' ) );
add_action( 'edit_user_profile', array( $functions, 'profile' ) );
add_action( 'admin_menu', array( $functions, 'admin_menu' ) );
add_action(
	'personal_options_update',
	array( $functions, 'user_update' )
);
add_action(
	'edit_user_profile_update',
	array( $functions, 'user_update' )
);
add_action(
	'wp_ajax_xlttotpauth_newtoken',
	array( $functions, 'generate_new_token' )
);
add_action(
	'admin_init',
	array( $functions, 'register_my_setting' )
);

if ( is_admin() ) {
	add_action(
		'admin_enqueue_scripts',
		array( $functions, 'enqueue_scripts' )
	);
}

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	array( $functions, 'filter_plugin_actions' ),
	10,
	1
);
$enabled = (bool) get_option( 'xlttotpauth_enabled' );
if ( $enabled ) {
	add_action(
		'login_form',
		array( $functions, 'login_form' )
	);
	add_filter(
		'authenticate',
		array( $functions, 'auth' ),
		30
	);
}