<?php
/**
 * Core Functionality Plugin
 * 
 * @package    CoreFunctionality
 * @since      1.0.0
 * @copyright  Copyright (c) 2014, Bill Erickson & Jared Atchison
 * @license    GPL-2.0+
 */

/**
 * Shortcut function for get_post_meta();
 *
 * @since 1.2.0
 * @param string $key
 * @param int $id
 * @param boolean $echo
 * @param string $prepend
 * @param string $append
 * @param string $escape
 * @return string
 */
function ea_cf( $key = '', $id = '', $echo = false, $prepend = false, $append = false, $escape = false ) {
	$id    = ( empty( $id ) ? get_the_ID() : $id );
	$value = get_post_meta( $id, $key, true );
	if( $escape )
		$value = call_user_func( $escape, $value );
	if( $value && $prepend )
		$value = $prepend . $value;
	if( $value && $append )
		$value .= $append;
		
	if ( $echo ) {
		echo $value;
	} else {
		return $value;
	}
}

/**
 * Get the first term attached to post
 *
 * @param string $taxonomy
 * @param string/int $field, pass false to return object
 * @param int $post_id
 * @return string/object
 */
function ea_first_term( $taxonomy = 'category', $field = 'name', $post_id = false ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$terms = get_the_terms( $post_id, $taxonomy );
	if( empty( $terms ) || is_wp_error( $terms ) )
		return false;
	
	// Sort by post count
	$list = array();	
	foreach( $terms as $term )
		$list[$term->count] = $term;
	ksort( $list, SORT_NUMERIC );
	
	// Grab first in array
	$list = array_reverse( $list );
	$term = array_shift( $list );
		
	if( $field && isset( $term->$field ) )
		return $term->$field;
	
	else
		return $term;
}

/**
 * Conditional CSS Classes
 *
 * @param string $base_classes, classes applied always applied
 * @param string $optional_class, additional class applied if $conditional is true
 * @param bool $conditional, whether to add $optional_class or not
 * @return string $classes
 */
function ea_class( $base_classes, $optional_class, $conditional ) {
	return $conditional ? $base_classes . ' ' . $optional_class : $base_classes;
}

/**
 * Column Classes
 *
 * @param int $type, number from 2-6
 * @param int $count, current count in the loop
 */
function ea_column_class( $type, $count ) {
	$classes = array( '', '', 'one-half', 'one-third', 'one-fourth', 'one-fifth', 'one-sixth' );
	if( isset( $classes[$type] ) )
		return ea_class( $classes[$type], 'first', 0 == $count % $type );
}

/**
 * Gravity Forms Domain
 *
 * Adds a notice at the end of admin email notifications 
 * specifying the domain from which the email was sent.
 *
 * @param array $notification
 * @param object $form
 * @param object $entry
 * @return array $notification
 */
function ea_gravityforms_domain( $notification, $form, $entry ) {

	if( $notification['name'] == 'Admin Notification' ) {
		$notification['message'] .= 'Sent from ' . home_url();
	}

	return $notification;
}
add_filter( 'gform_notification', 'ea_gravityforms_domain', 10, 3 );

/**
 * Prevent ACF access site-wide for non-developers.
 *
 */
function ea_prevent_acf_access() {
	if ( function_exists( 'ea_is_developer' ) && ea_is_developer() ) {
		return 'manage_options';
	}
	return false;
}
add_filter ('acf/settings/capability', 'ea_prevent_acf_access' );

/**
 * ACF Options Page 
 *
 */
function ea_acf_options_page() {
    if ( function_exists( 'acf_add_options_page' ) ) {
        acf_add_options_page( 'Site Options' );
    }
    if ( function_exists( 'acf_add_options_sub_page' ) ){
 		 acf_add_options_sub_page( array(
			'title'      => 'CPT Settings',
			'parent'     => 'edit.php?post_type=CPT_slug',
			'capability' => 'manage_options'
		) );
 	}
}
//add_action( 'init', 'ea_acf_options_page' );

 /**
 * Dont Update the Plugin
 * If there is a plugin in the repo with the same name, this prevents WP from prompting an update.
 *
 * @since  1.0.0
 * @author Jon Brown
 * @param  array $r Existing request arguments
 * @param  string $url Request URL
 * @return array Amended request arguments
 */
function ea_dont_update_core_func_plugin( $r, $url ) {
  if ( 0 !== strpos( $url, 'https://api.wordpress.org/plugins/update-check/1.1/' ) )
    return $r; // Not a plugin update request. Bail immediately.
    $plugins = json_decode( $r['body']['plugins'], true );
    unset( $plugins['plugins'][plugin_basename( __FILE__ )] );
    $r['body']['plugins'] = json_encode( $plugins );
    return $r;
 }
add_filter( 'http_request_args', 'ea_dont_update_core_func_plugin', 5, 2 );
