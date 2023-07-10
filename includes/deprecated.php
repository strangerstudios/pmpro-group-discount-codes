<?php

/**
 * Swap real code for group code.
 *
 * @deprecated TBD
 */
function pmpro_groupcodes_init() {
	_deprecated_function( __FUNCTION__, 'TBD' );
	if ( ! empty( $_REQUEST['discount_code'] ) ) {
		global $wpdb;

		$discount_code = $_REQUEST['discount_code'];

		// Check if it's a real code first, if so, leave it alone.
		$is_real_code = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql(strtolower(trim($discount_code))) . "' LIMIT 1" );
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
			$code_parent = $wpdb->get_var( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . $group_code->code_parent . "' LIMIT 1" );
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
 * @deprecated TBD
 */
function pmpro_groupcodes_template_redirect() {
	_deprecated_function( __FUNCTION__, 'TBD' );

	global $discount_code, $group_discount_code;
	if ( ! empty( $group_discount_code ) ) {
		$discount_code = $group_discount_code;
	}
}
// add_action( 'template_redirect', 'pmpro_groupcodes_template_redirect' );

/**
 * Add note RE group code and save order_id in group code table.
 *
 * @deprecated TBD
 *
 * @param MemberOrder $order The order object.
 * @return MemberOrder The order object.
 */
function pmpro_groupcodes_pmpro_added_order( $order ) {
	_deprecated_function( __FUNCTION__, 'TBD' );
	global $group_discount_code;

	if ( ! empty( $group_discount_code ) ) {
		global $wpdb;

		// Add group code to note (legacy functionality, the custom table is the "source of truth").
		$order->notes .= "\n---\n{GROUPCODE:" . $group_discount_code . "}\n---\n";
		$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET notes = '" . esc_sql( $order->notes ) . "' WHERE id = '" . intval( $order->id ) . "' LIMIT 1";
		$wpdb->query( $sqlQuery );

		// Save order id in group code table.
		$sqlQuery = "UPDATE $wpdb->pmpro_group_discount_codes SET order_id = '" . intval( $order->id ) . "'WHERE code='" . $group_discount_code . "' LIMIT 1";
		$wpdb->query( $sqlQuery );
	}

	return $order;
}
// add_action( 'pmpro_added_order', 'pmpro_groupcodes_pmpro_added_order' );