<?php
/**
 * Thanks goes to CodeTab for these functions:
 * https://www.codetab.org/tutorial/wordpress-plugin-development/unit-test/scripts-actions-filters/
 */

namespace TruUcdeBlog;

class Util {

	public static function has_value( $value, $array ) {
		$has_value = false;

		$callback = function ( $v, $k ) use ( $value, &$has_value ) {
			if ( $value == $v ) {
				$has_value = true;
			}
		};
		array_walk_recursive( $array, $callback );
		return $has_value;
	}

	public static function has_obj( $type, $array ) {
		$has_obj  = false;
		$callback = function ( $v, $k ) use ( $type, &$has_obj ) {
			if ( is_object( $v ) ) {
				if ( $type == get_class( $v ) ) {
					$has_obj = true;
				}
			}
		};
		array_walk_recursive( $array, $callback );
		return $has_obj;
	}

	// wrapper for wp has_action() - for easy coding
	public static function has_action( $action, $obj, $function ) {
		$registered = has_action(
			$action,
			array(
				$obj,
				$function,
			)
		);
		if ( $registered ) {
			return true;
		} else {
			return false;
		}
	}

	// wrapper for wp has_filter()
	public static function has_filter( $filter, $obj, $function ) {
		$registered = has_filter(
			$filter,
			array(
				$obj,
				$function,
			)
		);
		if ( $registered ) {
			return true;
		} else {
			return false;
		}
	}

	// use this function to get action when function object hash is not known
	public static function get_action( $action ) {
		global $wp_filter;
		if ( isset( $wp_filter[ $action ] ) ) {
			return $wp_filter[ $action ];
		} else {
			return null;
		}
	}

	static function get_post_id( $post_type, $meta_key, $meta_value ) {
		$post_id = null;
		global $wpdb;
		$sql     = $wpdb->prepare(
			"select p.id from $wpdb->postmeta m, $wpdb->posts p " .
						 'where m.post_id = p.id and p.post_type = %s ' .
						 'and meta_key = %s and meta_value = %s ',
			$post_type,
			$meta_key,
			$meta_value
		);
		$results = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $results as $result ) {
			if ( isset( $result['id'] ) ) {
				$post_id = $result['id'];
			}
		}
		return $post_id;
	}

	static function count_posts( $post_type ) {
		$args  = array(
			'post_type' => $post_type,
		);
		$query = new WP_Query( $args );
		$count = $query->found_posts;
		wp_reset_postdata();
		return $count;
	}

	static function set_cap( $cap, $enable ) {
		global $current_user;
		self::set_admin_role( false );
		if ( $enable ) {
			$current_user->add_cap( $cap );
			$current_user->get_role_caps();
		} else {
			$current_user->remove_cap( $cap );
			$current_user->get_role_caps();
		}
	}

	static function set_activate_plugins_cap( $enable ) {
		self::set_cap( 'activate_plugins', $enable );
		if ( is_multisite() ) {
			self::set_cap( 'manage_network_plugins', $enable );
		}
	}

	static function set_admin_role( $enable ) {
		global $current_user;
		if ( $enable ) {
			$current_user->add_role( 'administrator' );
			$current_user->get_role_caps();
		} else {
			$current_user->remove_role( 'administrator' );
			$current_user->get_role_caps();
		}
	}

	static function change_locale( $lang ) {
		global $locale;
		$locale = $lang;
		load_plugin_textdomain(
			'sos-domain',
			false,
			'share-on-social/tests/phpunit/langs'
		);
	}

	/*
	 * remove tab and spaces from the start and end of each line
	 * [spaces]\n[spaces] is replaced with \n
	 */
	static function trim_whitespaces( $in ) {
		$out = preg_replace( '/[\t ]*\n[\t ]*/', PHP_EOL, $in );
		return trim( $out );
	}
}
