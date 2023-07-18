<?php
/*
Plugin Name: Paid Memberships Pro - Group Discount Codes Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/group-discount-codes/
Description: Adds features to PMPro to better manage grouped discount codes or large numbers of discount codes.
Version: .3.2
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com/
*/

/**
 * Load the textdomain.
 */
function pmpro_groupcodes_load_textdomain() {
	load_plugin_textdomain( 'pmpro-group-discount-codes', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmpro_groupcodes_load_textdomain' );


/*
 * Setup DB Tables
 */
function pmpro_groupcodes_set_up_db() {
	global $wpdb;
	$wpdb->pmpro_group_discount_codes = $wpdb->prefix . "pmpro_group_discount_codes";

	if ( is_admin() ) {
		$db_version = get_option('pmpro_groupcodes_db_version', 0);

		if ( empty( $db_version ) ) {
			// Set up DB.
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			// Create/update wp_pmpro_group_discount_codes table.
			$sqlQuery = "
				CREATE TABLE `" . $wpdb->pmpro_group_discount_codes . "` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`code` varchar(32) NOT NULL,
				`code_parent` bigint(20) unsigned NOT NULL,
				`order_id` bigint(20) unsigned NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `code` (`code`),
				KEY `code_parent` (`code_parent`),
				KEY `order_id` (`order_id`)
				);
			";
			dbDelta($sqlQuery);

			update_option('pmpro_groupcodes_db_version', ".1");
		}
	}
}
pmpro_groupcodes_set_up_db();

/**
 * Add the group code field to the discount code form.
 */
function pmpro_groupcodes_pmpro_discount_code_after_settings() {
	global $wpdb;

	// Get the current group codes.
	$code_id = intval($_REQUEST['edit']);
	if ( $code_id > 0 ) {
		//Do we need to paginate this ?
		$group_codes = $wpdb->get_col( "SELECT code FROM $wpdb->pmpro_group_discount_codes WHERE code_parent = '" . $code_id . "' ORDER BY  code ASC" );
		$used_codes_object = $wpdb->get_results( "SELECT code, order_id FROM $wpdb->pmpro_group_discount_codes WHERE code_parent = '" . $code_id . " AND order_id > 0 '" );
		$used_codes = array();
		foreach ( $used_codes_object as $used_code_object ) {
			if($used_code_object->order_id > 0) {
				$order = new MemberOrder( $used_code_object->order_id );
				$user = get_user_by( 'id', $order->user_id );
				$order_url = esc_url( add_query_arg( array( 'page' => 'pmpro-orders', 'order' =>$used_code_object->order_id ), admin_url('admin.php' ) ) );
				$used_codes_object_class = new stdClass();
				$used_codes_object_class->url = $order_url;
				$used_codes_object_class->code = $used_code_object->code;
				$used_codes_object_class->username = $user->display_name;
				$used_codes_object_array[$used_code_object->code] = $used_codes_object_class;
				$used_codes[] = $used_code_object->code;

			}
		}
	}
	// Show the field.
	?>
	<hr />
	<h3><?php esc_html_e( 'Group Codes', 'pmpro-group-discount-codes' ); ?></h3>
	<p>
		<?php
		echo wp_kses(
			sprintf(
				// translators: %s is a link to a random string generator.
				__( 'Enter additional codes that will use the same settings, one code per line. Codes may only contain letters and numbers (use a <a href="%s" target="_blank">random string generator</a> or a spreadsheet program to create bulk codes). <strong>Keep the main code secret, and leave the uses of the main code blank.</strong> Each code below may only be used once.', 'pmpro-group-discount-codes' ),
				'https://www.random.org/strings/'
			),
			array(
				'strong' => array(),
				'a' => array(
					'href' => array(),
					'target' => array()
				)
			)
		);
		?>
	</p>
	<div class="pmpro-flex-wrapper">
		<div class="text-area-wrapper">
			<textarea id="group_codes" name="group_codes" cols="40" rows="25" placeholder="<?php esc_html_e('Add New Group Codes', 'pmpro-group-discount-codes')?>"></textarea>
		</div>
		<?php
			if ( $code_id > 0 ) {
		?>
		<div class="table-wrapper">
			<div class="filters">
				<span class="filter-label"><?php esc_html_e("Filter:", 'pmpro-group-discount-codes' ); ?></span>
				<span class="filters">
					<div class="radio-wrapper">
						<input type="radio" id="all" value="all" name="filter" checked></input>
						<label for="all"><?php esc_html_e("All", 'pmpro-group-discount-codes' ); ?></label>
					</div>
					<div class="radio-wrapper">
						<input type="radio" id="used" value="used" name="filter"></input>
						<label for="used"><?php esc_html_e("Used", 'pmpro-group-discount-codes' ); ?></label>
					</div>
					<div class="radio-wrapper">
						<input type="radio" id="unused" value="unused" name="filter"></input>
						<label for="unused"><?php esc_html_e("Unused", 'pmpro-group-discount-codes' ); ?></label>
					</div>
				</span>
			</div>
			<div class="search">
				<span class="search"><?php esc_html_e("Search by user or code:", 'pmpro-group-discount-codes' ); ?></span>
				<input type="text" id="code-search" placeholder="<?php esc_html_e('Search', 'pmpro-group-discount-codes')?>"/>
			</div>
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Group Code', 'pmpro-group-discount-codes' ); ?></th>
						<th><?php esc_html_e( 'Used By', 'pmpro-group-discount-codes' ); ?></th>
						<th><?php esc_html_e( 'Delete', 'pmpro-group-discount-codes' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $group_codes as $group_code ) { ?>
						<tr data-code_id="<?php echo esc_attr( $group_code ); ?>" >
							<td class="group-code-td"> <?php echo esc_attr( $group_code );  ?></td>
							<td  <?php if( in_array($group_code, $used_codes) ) { ?>> <a target="_blank" href="<?php echo esc_attr( $used_codes_object_array[$group_code ]->url ) ?>"> <?php echo esc_attr( $used_codes_object_array[$group_code ]->username); ?></a><?php } ?></td>
							<td class="delete-td">
								<?php if(! in_array($group_code, $used_codes) ) { ?> 
									<input type="checkbox" class="delete-check" data-code_id_to_delete="<?php echo esc_attr( $group_code ); ?>">
									<input type="hidden" name="delete_codes_set[]"></input>
								<?php } ?>
							</td>
						</tr>
					<?php } 	?>
				</tbody>
			</table>
		</div>
		<?php
			}
		?>
	</div>
	<hr />
	<?php
}
add_action( 'pmpro_discount_code_after_settings', 'pmpro_groupcodes_pmpro_discount_code_after_settings' );

/**
 * Save the group codes.
 *
 * @param int $code_id The ID of the discount code.
 */
function pmpro_groupcodes_pmpro_save_discount_code( $code_id ) {
	global $wpdb;

	// Make sure we have a code ID.
	if ( empty( $code_id ) ) {
		return;
	}

	// Get old codes.
	$old_group_codes = $wpdb->get_col("SELECT code FROM $wpdb->pmpro_group_discount_codes WHERE code_parent = '" . (int) $code_id . "'");

	// Get new codes.
	$group_codes = $_REQUEST['group_codes'];
	if( ! empty($group_codes)) {
		$group_codes = str_replace( "\r", "", $group_codes );
		$group_codes = explode( "\n", str_replace( array( ", ", ",", "; ", ";", " " ), "\n", $group_codes ) );
		$codes_to_add = array_diff( $group_codes, $old_group_codes );

		// Add new group codes.
		foreach( $codes_to_add as $code ) {
			$sqlQuery = "INSERT IGNORE INTO $wpdb->pmpro_group_discount_codes (id, code, code_parent) VALUES('', '" . esc_sql( trim( $code ) ) . "', '" . $code_id . "')";
			$wpdb->query( $sqlQuery );
		}
	}

	//Get codes to delete.
	if($_REQUEST['delete_codes_set']) {
		$codes_to_delete = array_filter($_REQUEST['delete_codes_set']);
		// Delete old group codes.
		foreach( $codes_to_delete as $code ) {
			//Check if code is used. In case it is, break the loop.
			$used_code_object = $wpdb->get_results("SELECT code FROM $wpdb->pmpro_group_discount_codes WHERE code = '" . esc_sql( $code ) . "' AND order_id > 0");
			if( $used_code_object ) {
				continue;
			}

			$sqlQuery = "DELETE FROM $wpdb->pmpro_group_discount_codes WHERE code = '" . esc_sql( $code ) . "' LIMIT 1";
			$wpdb->query( $sqlQuery );
		}
	}
}

add_action( 'pmpro_save_discount_code', 'pmpro_groupcodes_pmpro_save_discount_code' );

/**
 * Delete group codes when the parent code is deleted.
 *
 * @param int $code_id The ID of the parent discount code.
 */
function pmpro_groupcodes_pmpro_delete_discount_code( $code_id ) {
	global $wpdb;

	$sqlQuery = "DELETE FROM $wpdb->pmpro_group_discount_codes WHERE code_parent = '" . intval( $code_id ) . "'";
	$wpdb->query($sqlQuery);
}
add_action( 'pmpro_delete_discount_code', 'pmpro_groupcodes_pmpro_delete_discount_code' );

/**
 * Get the database entry for a group code.
 *
 * @param string $group_code The group code.
 * @return object|false The database entry for the group code, or false if not found.
 */
function pmpro_groupcodes_getGroupCode( $group_code ) {
	global $wpdb;
	return $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_group_discount_codes WHERE code = '" . esc_sql( strtolower( trim( $group_code ) ) ) . "' LIMIT 1" );
}

/**
 * Get the group code database entry for a particular order.
 *
 * @since TBD
 *
 * @param int $order_id The order ID.
 * @return object|false The database entry for the group code, or false if not found.
 */
function pmpro_groupcodes_get_group_code_for_order( $order_id ) {
	global $wpdb;
	return $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_group_discount_codes WHERE order_id = '" . intval( $order_id ) . "' LIMIT 1" );
}

/*
 * Make sure checkDiscountCode works for group codes.
 *
 * @param bool|string $okay True if okay, false or error message string if not okay
 * @param string $dbcode The discount code.
 * @param int $level_id The level ID.
 * @param string $code The discount code.
 * @return bool|string True if okay, false or error message string if not okay
 */
function pmpro_groupcodes_pmpro_check_discount_code( $okay, $dbcode, $level_id, $code ) {
	// If okay, just return.
	if ( $okay === true ) {
		return $okay;
	}

	$group_code = pmpro_groupcodes_getGroupCode( $code );

	if ( ! empty( $group_code ) ) {
		global $wpdb;

		// Check if this group code was used already.
		if ( $group_code->order_id > 0 ) {
			return esc_html__( 'This code has already been used.', 'pmpro-group-discount-codes' );
		}

		// Okay check parent.
		$code_parent = $wpdb->get_var( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . (int)$group_code->code_parent . "' LIMIT 1" );
		if ( ! empty( $code_parent) ) {
			return pmpro_checkDiscountCode($code_parent, $level_id);
		}
	}

	return $okay;
}
add_filter( 'pmpro_check_discount_code', 'pmpro_groupcodes_pmpro_check_discount_code', 10, 4 );

/**
 * Fix code level when a group code is used.
 *
 * @param object $code_level The level object.
 * @param int $discount_code_id The discount code ID used.
 * @return object The level object.
 */
function pmpro_groupcodes_pmpro_discount_code_level( $code_level, $discount_code_id ) {
	global $wpdb;

	// If we don't have a level, bail.
	if ( empty( $code_level ) || empty( $code_level->id ) ) {
		return $code_level;
	}

	// If a real discount code was used, we don't want to make any futher changes.
	if ( ! empty( $discount_code_id ) ) {
		return $code_level;
	}

	// Check if a group code was used.
	$group_code = false;
	// Check prefixed parameter in PMPro v3.0+.
	if ( ! empty( $_REQUEST['pmpro_discount_code'] ) ) {
		$group_code = pmpro_groupcodes_getGroupCode( $_REQUEST['pmpro_discount_code'] );
	}
	// Check the non-prefixed paramter for PMPro < 3.0.
	if ( empty( $group_code ) && ! empty( $_REQUEST['discount_code'] ) ) {
		$group_code = pmpro_groupcodes_getGroupCode( $_REQUEST['discount_code'] );
	}
	// Check the code parameter for the applydiscountcode.php service.
	if ( empty( $group_code ) && ! empty( $_REQUEST['code'] ) ) {
		$group_code = pmpro_groupcodes_getGroupCode( $_REQUEST['code'] );
	}

	// If we don't have a group code, bail.
	if ( empty( $group_code ) ) {
		return $code_level;
	}

	// If this group code was used already, bail.
	if ( $group_code->order_id > 0 ) {
		return $code_level;
	}

	// Get the parent code.
	$parent_code = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_discount_codes WHERE id = '" . esc_sql( $group_code->code_parent ) . "' LIMIT 1" );
	if ( empty( $parent_code ) ) {
		return $code_level;
	}

	// Unhook this function and get the checkout level with the parent code.
	remove_filter( 'pmpro_discount_code_level', 'pmpro_groupcodes_pmpro_discount_code_level', 10, 2 );
	$code_level = pmpro_getLevelAtCheckout( (int) $code_level->id, $parent_code->code );
	add_filter( 'pmpro_discount_code_level', 'pmpro_groupcodes_pmpro_discount_code_level', 10, 2 );

	// Update the discount_code property on the level to the group code to avoid leaking the parent code.
	$code_level->discount_code = $group_code->code;

	return $code_level;
}
add_filter( 'pmpro_discount_code_level', 'pmpro_groupcodes_pmpro_discount_code_level', 10, 2 );

/**
 * When a group code is used, update the discount code uses, group discount code uses, and order notes.
 *
 * @since TBD
 *
 * @param int $discount_code_id The discount code ID used.
 * @param int $user_id The user ID.
 * @param int $order_id The order ID.
 */
function pmpro_groupcodes_pmpro_discount_code_used( $discount_code_id, $user_id, $order_id ) {
	global $wpdb;

	// If $discount_code_id is not empty, then a legitemate discount code was used. Bail.
	if ( ! empty( $discount_code_id ) ) {
		return;
	}

	// Clean up any discount code uses that were already created for this order.
	$wpdb->query( "DELETE FROM $wpdb->pmpro_discount_codes_uses WHERE order_id = '" . intval( $order_id ) . "'" );

	// Get the group code that was used. If there wasn't a group code used, bail.
	$group_code = false;
	if ( ! empty( $_REQUEST['pmpro_discount_code'] ) ) {
		$group_code = pmpro_groupcodes_getGroupCode( $_REQUEST['pmpro_discount_code'] );
	}
	if ( empty( $group_code ) && ! empty( $_REQUEST['discount_code'] ) ) {
		$group_code = pmpro_groupcodes_getGroupCode( $_REQUEST['discount_code'] );
	}
	if ( empty( $group_code ) ) {
		return;
	}

	// Check that there is a parent code.
	if ( empty( $group_code->code_parent ) ) {
		return;
	}

	// Update the discount code uses.
	$wpdb->query( $wpdb->prepare(
		"INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES(%d, %d, %d, %s)",
		$group_code->code_parent,
		$user_id,
		$order_id,
		current_time( "mysql" )
	) );

	// Update the group discount code uses.
	$sqlQuery = "UPDATE $wpdb->pmpro_group_discount_codes SET order_id = '" . intval( $order_id ) . "'WHERE code='" . esc_sql( $group_code->code ) . "' LIMIT 1";
	$wpdb->query( $sqlQuery );

	// Update the order notes (legacy functionality, the custom table is the "source of truth").
	$order = new MemberOrder( $order_id );
	$order->notes .= "\n---\n{GROUPCODE:" . $group_code->code . "}\n---\n";
	$order->saveOrder();
}
add_action( 'pmpro_discount_code_used', 'pmpro_groupcodes_pmpro_discount_code_used', 10, 3 );

/**
 * Filter discount code when showing invoice.
 *
 * @param object $code The discount code.
 * @param MemberOrder $order The order object.
 * @return object The discount code.
 */
function pmpro_groupcodes_pmpro_order_discount_code( $code, $order ) {
	// Check if this order is part of an entry in the group discount codes table.
	$group_code = pmpro_groupcodes_get_group_code_for_order( $order->id );

	// If so, set the code to the group code.
	if ( ! empty( $group_code->code ) ) {
		$code->code = $group_code->code;
	}

	return $code;
}
add_filter( 'pmpro_order_discount_code', 'pmpro_groupcodes_pmpro_order_discount_code', 10, 2 );

/*
 * Show group discount codes in emails.
 *
 * @param array $data The email data.
 * @param PMProEmail $email The email object.
 * @return array The email data.
 */
function pmpro_groupcodes_pmpro_email_data( $data, $email ) {
	if ( ! empty( $data['invoice_id'] ) ) {
		// Check if this invoice is part of an entry in the group discount codes table.
		$group_code = pmpro_groupcodes_get_group_code_for_order( $data['invoice_id'] );
		if ( ! empty( $group_code ) ) {
			// Set the discount code variable to the group code.
			$data['discount_code'] = $group_code->code;
		}
	}

	return $data;
}
add_filter( 'pmpro_email_data', 'pmpro_groupcodes_pmpro_email_data', 10, 2 );

/**
 * Add group codes column to the discount codes table view.
 */
function pmpro_groupcodes_pmpro_discountcodes_extra_cols_header() {
	?>
	<th><?php esc_html_e( 'Group Code Uses', 'pmpro-group-discount-codes' );?></th>
	<?php
}
add_action( 'pmpro_discountcodes_extra_cols_header', 'pmpro_groupcodes_pmpro_discountcodes_extra_cols_header' );

/**
 * Fill the group codes column in the discount codes table view.
 *
 * @param object $code The discount code.
 */
function pmpro_groupcodes_pmpro_discountcodes_extra_cols_body( $code ) {
	global $wpdb;
	
	// Get number of group codes and number of codes that have been used.
	if ( $code->id > 0 ) {
		$number_total_codes = $wpdb->get_var( "SELECT COUNT(code) FROM $wpdb->pmpro_group_discount_codes WHERE code_parent = '" . esc_sql( $code->id ) . "'" ); 
		$number_used_codes = $wpdb->get_var( "SELECT COUNT(code) FROM $wpdb->pmpro_group_discount_codes WHERE code_parent = '" . esc_sql( $code->id ) . "' AND order_id > 0" ); 
	}
	?>
	<td>
		<?php if ( ! empty( $number_total_codes ) ) {
			echo '<strong>' . esc_html( $number_used_codes ) . '</strong>/' . esc_html( $number_total_codes );
		} else {
			echo '--';
		} ?>
	</td>
	<?php
}
add_action( 'pmpro_discountcodes_extra_cols_body', 'pmpro_groupcodes_pmpro_discountcodes_extra_cols_body' );

/**
 * Adds an extra colum to your Memberships > Orders > Export to CSV file. Displays the group discount code used.
 *
 * @param array $columns The columns to export.
 * @return array The columns to export.
 */
function pmpro_groupcodes_pmpro_orders_csv_extra_columns( $columns ) {
	$columns['group_code'] = 'pmpro_groupcodes_pmpro_orders_csv_extra_columns_group_code';
	return $columns;
}
add_filter( 'pmpro_orders_csv_extra_columns', 'pmpro_groupcodes_pmpro_orders_csv_extra_columns' );

/**
 * Fill the extra colum for the Memberships > Orders > Export to CSV file. Displays the group discount code used.
 *
 * @param MemberOrder $order The order object.
 * @return string The group code.
 */
function pmpro_groupcodes_pmpro_orders_csv_extra_columns_group_code( $order ) {
	// Check if this order is part of an entry in the group discount codes table.
	$group_code = pmpro_groupcodes_get_group_code_for_order( $order->id );

	if ( ! empty( $group_code ) ) {
		return $group_code->code;
	} else {
		return '';
	}
}

// Load deprecated functions.
require_once( dirname( __FILE__ ) . '/includes/deprecated.php' );

/*
 * Function to add links to the plugin action links.
 *
 * @param array $links The links array.
 * @return array The links array.
 */
function pmpro_groupcodes_add_action_links($links) {
	$new_links = array(
			'<a href="' . get_admin_url(NULL, '/admin.php?page=pmpro-discountcodes') . '">' . esc_html__( 'Manage Discount Codes', 'pmpro-group-discount-codes' ) . '</a>',
	);
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'pmpro_groupcodes_add_action_links' );

/*
 * Function to add links to the plugin row meta.
 *
 * @param array $links The links array.
 * @param string $file The plugin file.
 * @return array The links array.
 */
function pmpro_groupcodes_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-group-discount-codes.php') !== false ) {
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/group-discount-codes/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-group-discount-codes' ) ) . '">' . __( 'Docs', 'pmpro-group-discount-codes' ) . '</a>',
			'<a href="' . esc_url('https://www.paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-group-discount-codes' ) ) . '">' . __( 'Support', 'pmpro-group-discount-codes' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpro_groupcodes_plugin_row_meta', 10, 2 );

/**
 * Load CSS and JS.
 * 
 * @since TBD
 * 
 */
function pmpro_group_admin_assets() {
	if(file_exists(get_stylesheet_directory() . "/paid-memberships-pro/group-discount-codes/css/pmpro-group-discount-codes.css"))
		wp_register_style( 'pmpro-group-discount-codes', get_stylesheet_directory_uri()."/paid-memberships-pro/group-discount-codes/css/pmpro-group-discount-codes.css");
	elseif(file_exists(get_template_directory()."/paid-memberships-pro/group-discount-codes/css/pmpro-group-discount-codes.css"))
		wp_register_style( 'pmpro-group-discount-codes', get_template_directory_uri()."/paid-memberships-pro/group-discount-codes/css/pmpro-group-discount-codes.css");
	elseif(function_exists("pmpro_https_filter"))
		wp_register_style( 'pmpro-group-discount-codes', pmpro_https_filter(plugins_url( 'css/pmpro-group-discount-codes.css', __FILE__ ) ), NULL, "");
	else
	wp_register_style( 'pmpro-group-discount-codes', plugins_url( 'css/pmpro-group-discount-codes.css', __FILE__ ) );
	wp_enqueue_style( 'pmpro-group-discount-codes' );
	wp_register_script( 'pmpro-group-discount-codes', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), null, true );
	wp_enqueue_script( 'pmpro-group-discount-codes' );
}

add_action( 'admin_enqueue_scripts', 'pmpro_group_admin_assets' );