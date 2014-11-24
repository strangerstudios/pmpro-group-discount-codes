<?php
/*
Plugin Name: PMPro Group Discount Codes
Plugin URI: http://www.paidmembershipspro.com/pmpro-group-discount-codes/
Description: Adds features to PMPro to better manage grouped discount codes or large numbers of discount codes.
Version: .1.1
Author: Stranger Studios
Author URI: http://www.paidmembershipspro.com
*/

/*
	Setup DB Tables
*/
global $wpdb;
$wpdb->pmpro_group_discount_codes = $wpdb->prefix . "pmpro_group_discount_codes";

if(is_admin())
{
	$db_version = get_option('pmpro_groupcodes_db_version', 0);
	
	if(empty($db_version))
	{
		//setup DB
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		//wp_pmpro_group_discount_codes
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

//show the global settings on the discount code page
function pmpro_groupcodes_pmpro_discount_code_after_settings()
{
	global $wpdb;

	//get group codes
	$code_id = intval($_REQUEST['edit']);
	if($code_id > 0)
		$group_codes = $wpdb->get_col("SELECT code FROM $wpdb->pmpro_group_discount_codes WHERE code_parent = '" . $code_id . "'");
	else
		$group_codes = array();
		
?>
<h3>Group Codes</h3>
<p>Enter additional codes that will use the same settings. Enter one code per line. Codes may only contain letters and numbers. <strong>Keep the main code secret, and leave the uses of the main code blank.</strong> Each code below may only be used once.</p>
<textarea id="group_codes" name="group_codes" cols="50" rows="8"><?php echo esc_attr(implode("\n", $group_codes));?></textarea>
<?php
}
add_action("pmpro_discount_code_after_settings", "pmpro_groupcodes_pmpro_discount_code_after_settings");

//save the global settings
function pmpro_groupcodes_pmpro_save_discount_code($code_id)
{	
	global $wpdb;	
		
	//fix for PMPro 1.7.15.2 and under
	if($code_id == '-1' && !empty($_REQUEST['edit']) && $_REQUEST['edit'] == '-1')
	{
		//assume last code entered is the one being hooked
		$code_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes ORDER BY id DESC LIMIT 1");	
	}
	
	if(!empty($code_id))
	{
		//get old codes
		$old_group_codes = $wpdb->get_col("SELECT code FROM $wpdb->pmpro_group_discount_codes WHERE code_parent = '" . $code_id . "'");
				
		//get new codes
		$group_codes = $_REQUEST['group_codes'];
		$group_codes = str_replace("\r", "", $group_codes);
		$group_codes = explode("\n", str_replace(array(", ", ",", "; ", ";", " "), "\n", $group_codes));

		//figure out which codes to add and delete
		$intersect = array_intersect($old_group_codes, $group_codes);
		$codes_to_add = array_diff($group_codes, $intersect);
		$codes_to_delete = array_diff($old_group_codes, $intersect);

		//add them
		foreach($codes_to_add as $code)
		{
			$sqlQuery = "INSERT IGNORE INTO $wpdb->pmpro_group_discount_codes (id, code, code_parent) VALUES('', '" . esc_sql(trim($code)) . "', '" . $code_id . "')";
			$wpdb->query($sqlQuery);			
		}

		//delete them
		foreach($codes_to_delete as $code)
		{
			$sqlQuery = "DELETE FROM $wpdb->pmpro_group_discount_codes WHERE code = '" . esc_sql($code) . "' LIMIT 1";
			$wpdb->query($sqlQuery);
		}

	}
}
add_action("pmpro_save_discount_code", "pmpro_groupcodes_pmpro_save_discount_code");

//get group code from parent code
function pmpro_groupcodes_getGroupCode($code_parent_code)
{
	global $wpdb;
	return $wpdb->get_row("SELECT * FROM $wpdb->pmpro_group_discount_codes WHERE code = '" . esc_sql(strtolower(trim($code_parent_code))) . "' LIMIT 1");
}

//swap real code for group code
function pmpro_groupcodes_init()
{
	if(!empty($_REQUEST['discount_code']))
	{
		global $wpdb;

		$discount_code = $_REQUEST['discount_code'];
		
		//check if it's a real code first, if so, leave it alone
		$is_real_code = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql(strtolower(trim($discount_code))) . "' LIMIT 1");
		if($is_real_code)
			return;

		//check if it's a group code
		$group_code = pmpro_groupcodes_getGroupCode($discount_code);
		if($group_code)
		{
			//check if this group code was used already
			if($group_code->order_id > 0)		
				return;
			
			//swap with the parent
			$code_parent = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . $group_code->code_parent . "' LIMIT 1");
			if(!empty($code_parent))
			{
				//swap in request
				$_REQUEST['discount_code'] = $code_parent;				
				
				//save to switch back later
				global $group_discount_code;
				$group_discount_code = $discount_code;
			}
		}
	}	
}
add_action('init', 'pmpro_groupcodes_init', 1);

/*
	Make sure checkDiscountCode works for group codes	
*/
function pmpro_groupcodes_pmpro_check_discount_code($okay, $dbcode, $level_id, $code)
{
	//if okay, just return
	if($okay)
		return $okay;
		
	$group_code = pmpro_groupcodes_getGroupCode($code);		
		
	if(!empty($group_code))
	{				
		global $wpdb;
		
		//check if this group code was used already
		if($group_code->order_id > 0)		
			return __("This code has already been used.", "pmpro");
		
		//okay check parent
		$code_parent = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . $group_code->code_parent . "' LIMIT 1");			
		if(!empty($code_parent))		
			return pmpro_checkDiscountCode($code_parent, $level_id);
	}
	
	return $okay;
}
add_filter('pmpro_check_discount_code', 'pmpro_groupcodes_pmpro_check_discount_code', 10, 4);

//fix code level when a group code is used
function pmpro_groupcodes_pmpro_discount_code_level($code_level, $discount_code_id)
{
	if(empty($discount_code_level) && !empty($_REQUEST['code']))
	{
		$group_code = pmpro_groupcodes_getGroupCode($_REQUEST['code']);
		if(!empty($group_code))
		{
			//check if this group code was used already
			if($group_code->order_id > 0)		
				return $code_level;
			
			global $wpdb;
			$code_parent = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . $group_code->code_parent . "' LIMIT 1");
			
			if(!empty($code_parent))
			{
				$sqlQuery = "SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id LEFT JOIN $wpdb->pmpro_discount_codes dc ON dc.id = cl.code_id WHERE dc.code = '" . $code_parent . "' AND cl.level_id = '" . $code_level->id . "' LIMIT 1";			
				$code_level = $wpdb->get_row($sqlQuery);				
			}
		}
	}

	return $code_level;
}
add_filter('pmpro_discount_code_level', 'pmpro_groupcodes_pmpro_discount_code_level', 10, 2);

//hide group codes from discount code page
function pmpro_groupcodes_template_redirect()
{		
	global $discount_code, $group_discount_code;
	if(!empty($group_discount_code))
		$discount_code = $group_discount_code;	
}
add_action('template_redirect', 'pmpro_groupcodes_template_redirect');

//add note RE group code and save order_id in group code table
function pmpro_groupcodes_pmpro_added_order($order)
{
	global $group_discount_code;
		
	if(!empty($group_discount_code))
	{
		global $wpdb;
		
		//add group code to note
		$order->notes .= "\n---\n{GROUPCODE:" . $group_discount_code . "}\n---\n";		
		$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET notes = '" . esc_sql($order->notes) . "' WHERE id = '" . intval($order->id) . "' LIMIT 1";
		$wpdb->query($sqlQuery);
		
		//save order id in group code table
		$sqlQuery = "UPDATE $wpdb->pmpro_group_discount_codes SET order_id = '" . intval($order->id) . "' LIMIT 1";
		$wpdb->query($sqlQuery);		
	}
	
	return $order;
}
add_action('pmpro_added_order', 'pmpro_groupcodes_pmpro_added_order');

//filter discount code when showing invoice
function pmpro_groupcodes_pmpro_order_discount_code($code, $order)
{
	//look for code in notes
	$group_code = pmpro_getMatches("/{GROUPCODE:([^}]*)}/", $order->notes, true);
	
	if(!empty($group_code))
	{
		$code->code = $group_code;
	}

	return $code;
}
add_filter('pmpro_order_discount_code', 'pmpro_groupcodes_pmpro_order_discount_code', 10, 2);