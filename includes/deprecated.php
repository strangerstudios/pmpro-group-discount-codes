<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Queries run against PMPro custom tables; no WP API or cache layer exists for them.

/**
 * Swap real code for group code.
 *
 * @deprecated 0.4
 */
function pmpro_groupcodes_init() {
	_deprecated_function( __FUNCTION__, '0.4' );
	if ( ! empty( $_REQUEST['discount_code'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Deprecated, unhooked read-only lookup of the checkout discount code.
		global $wpdb;

		$discount_code = sanitize_text_field( wp_unslash( $_REQUEST['discount_code'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Deprecated, unhooked read-only lookup of the checkout discount code.

		// Check if it's a real code first, if so, leave it alone.
		$is_real_code = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = %s LIMIT 1", strtolower( trim( $discount_code ) ) ) );
		if ( $is_real_code ) {
			return;
		}

		// Check if it's a group code.
		$group_code = pmpro_groupcodes_getGroupCode( $discount_code );
		if ( $group_code ) {
			// Check if this group code was used already.
			if ( $group_code->order_id > 0 ) {
				return;
			}

			// Swap with the parent.
			$code_parent = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = %d LIMIT 1", $group_code->code_parent ) );
			if ( ! empty( $code_parent ) ) {
				// Swap in request.
				$_REQUEST['discount_code'] = $code_parent;

				// Save to switch back later.
				global $group_discount_code;
				$group_discount_code = $discount_code;
			}
		}
	}
}
// add_action( 'init', 'pmpro_groupcodes_init', 1 );

/**
 * Hide group codes from discount code field on checkout page.
 *
 * @deprecated 0.4
 */
function pmpro_groupcodes_template_redirect() {
	_deprecated_function( __FUNCTION__, '0.4' );

	global $discount_code, $group_discount_code;
	if ( ! empty( $group_discount_code ) ) {
		$discount_code = $group_discount_code;
	}
}
// add_action( 'template_redirect', 'pmpro_groupcodes_template_redirect' );

/**
 * Add note RE group code and save order_id in group code table.
 *
 * @deprecated 0.4
 *
 * @param MemberOrder $order The order object.
 * @return MemberOrder The order object.
 */
function pmpro_groupcodes_pmpro_added_order( $order ) {
	_deprecated_function( __FUNCTION__, '0.4' );
	global $group_discount_code;

	if ( ! empty( $group_discount_code ) ) {
		global $wpdb;

		// Add group code to note (legacy functionality, the custom table is the "source of truth").
		$order->notes .= "\n---\n{GROUPCODE:" . $group_discount_code . "}\n---\n";
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->pmpro_membership_orders SET notes = %s WHERE id = %d LIMIT 1", $order->notes, $order->id ) );

		// Save order id in group code table.
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->pmpro_group_discount_codes SET order_id = %d WHERE code = %s LIMIT 1", $order->id, $group_discount_code ) );
	}

	return $order;
}
// add_action( 'pmpro_added_order', 'pmpro_groupcodes_pmpro_added_order' );