<?php
/**
 * Plugin name: User create domain exception
 * Description: This plugin enables super/site admins to create users outside of white/black list restrictions
 * Version: 0.0.2
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Troy Welch twelch@tru.ca
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: TruUcde
 *
 * @package TruUcde
 */

namespace TruUcde;

use \WP_Error; // need from WordPress to create empty error block below.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

	/**
	 * WP_Error Object error code.
	 *
	 * @const string $target_error_code
	 */
	define( 'TARGET_ERROR_CODE', 'user_email' );

	/**
	 * WP_Error Object message(s) that we are targeting.
	 *
	 * @const string $black_list_msg
	 */
	define( 'BLACK_LIST_MSG', 'You cannot use that email address to signup. We are having problems with them blocking some of our email. Please use another email provider.' );

	/**
	 * WP_Error Object message(s) that we are targeting.
	 *
	 * @const string $black_list_msg
	 */
	define( 'WHITE_LIST_MSG', 'Sorry, that email address is not allowed!' );

	/**
	 * Hooking into WordPress
	 */
function truucde_init() {
	add_filter( 'wpmu_validate_user_signup', 'TruUcde\on_loaded', 10 );
};

	/**
	 * Checks user permission, existence of WP_Error object messages to remove,
	 * builds new array, returns $results object with new array in place of the
	 * original.
	 *
	 * @param result $result Return of wpmu_validate_user_signup.
	 *
	 * @return mixed
	 */
function on_loaded( $result ) {
	// get error object from wpmu_validate_user_signup result.
	$original_error = $result['errors'];

	// return original array if auth conditions not met,
	// or black/white list messages don't exist.
	if ( ! user_can_add() || ! e_needs_processing( $original_error ) ) {
		return $result;
	}
	
	// create a new (empty) WP_Error() object for holding transferred entries.
	$new_error = new WP_Error();
	
	// Run through error codes and messages keep all but white/black list errors.
	foreach ( $original_error->get_error_codes() as $code ) {
		foreach ( $original_error->get_error_messages( $code ) as $message ) {
			if ( TARGET_ERROR_CODE !== $code ) {
				$new_error->add( $code, $message );
			} else {
				// Don't add the white/black list messages.
				if ( BLACK_LIST_MSG !== $message && WHITE_LIST_MSG !== $message ) {
					$new_error->add( $code, $message );
				}
			}
		}
		// add data entries for original to new error object.
		if ( ! is_null( $original_error->get_error_data( $code ) ) ) {
			$new_error->add_data( $original_error->get_error_data( $code ), $code );
		}
	}
	// Put the new error object back in $result and return.
	$result['errors'] = $new_error;
	return $result;
};

	/**
	 * Tests if the current user is logged in and has admin/super admin privileges.
	 *
	 * @return bool
	 */
function user_can_add(): bool {
	if ( is_user_logged_in() && current_user_can( 'promote_users' ) ) {
		return true;
	} else {
		return false;
	}
}

	/**
	 * Tests if an actual error object is passed, if it is empty and
	 * if it contains a black or white list error message. Returns true
	 * if error object needs processing
	 *
	 * @param \WP_Error $original_error Error object passed in the wpmu_validate_user_signup results.
	 *
	 * @return bool
	 */
function e_needs_processing( \WP_Error $original_error ): bool {
	if ( ! empty( $original_error->errors ) // and is not empty.
		&& ( // and contains a black/white list error.
			in_array( BLACK_LIST_MSG, $original_error->get_error_messages( TARGET_ERROR_CODE ), true )
			|| in_array( WHITE_LIST_MSG, $original_error->get_error_messages( TARGET_ERROR_CODE ), true )
		)
	) {
		return true;
	} else {
		return false;
	}
}

truucde_init();


