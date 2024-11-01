<?php
/*
 * Plugin Name: WooBought - WooCommerce Live Sales Notifications
 * Plugin URI: https://www.studiowombat.com/plugin/woobought/
 * Description: Notify visitors of recent purchases in your store with non-intrusive notifications. Add social proof, trust & product visibility.
 * Version: 1.3.7
 * Author: Maarten Belmans
 * Author URI: http://maartenbelmans.com/
 * Text Domain: mabel-wc-recently-purchased-notifications-lite
 * WC requires at least: 3.0
 * WC tested up to: 6.5.1
*/

if(!defined('ABSPATH')){die;}

function run_mabel_rpnlite(){
	if (!defined('MABEL_RPN_LITE_VERSION'))
		define('MABEL_RPN_LITE_VERSION', '1.3.7');
	
	if (!defined('MABEL_RPN_LITE_NAME'))
		define('MABEL_RPN_LITE_NAME', 'WooBought - Live Sales Notifications');
	
	if (!defined('MABEL_RPN_LITE_DIR'))
		define('MABEL_RPN_LITE_DIR', plugin_dir_path( __FILE__ ));
	
	if (!defined('MABEL_RPN_LITE_SLUG'))
		define('MABEL_RPN_LITE_SLUG', trim(dirname(plugin_basename(__FILE__)), '/'));
	
	if (!defined('MABEL_RPN_LITE_URL'))
        define('MABEL_RPN_LITE_URL', plugin_dir_url(__FILE__));
	
	require MABEL_RPN_LITE_DIR . 'includes/class-mabel-rpnlite.php';

	$plugin = new Mabel_WC_RecentlyPurchasedLite();
}

run_mabel_rpnlite();