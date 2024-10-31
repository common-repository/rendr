<?php
	/**
	 * Rendr
	 *
	 * @package           Rendr
	 * @author            Rendr
	 * @copyright         2021 Rendr
	 * @license           GPL-2.0-or-later
	 *
	 * @wordpress-plugin
	 * Plugin Name:       Rendr
	 * Plugin URI:        https://rendr.delivery
	 * Description:       Offer Rendr Delivery to your customers within your WooCommerce Store
	 * Version:           1.4.2
	 * Requires at least: 5.0
	 * Requires PHP:      5.6
	 * Author:            Rendr
	 * Author URI:        https://rendr.delivery
	 * Text Domain:       wcrendr
	 * License:           GPL v2 or later
	 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
	 */

	if (!defined('ABSPATH') || !defined('WPINC')) {
		exit;
	}
	/**
	 * Check if WooCommerce is active
	 **/
	 if ( ! is_multisite() ) {
		 $wc_active = in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
	 } else {
		 $plugins = get_site_option( 'active_sitewide_plugins' );
		 $wc_active = isset( $plugins[ 'woocommerce/woocommerce.php' ] );
	 }
	 
	if($wc_active) {

		// Constants
		define('WCRENDR_VERSION', '1.4.2');
		define('WCRENDR_DIR', rtrim(plugin_dir_path(__FILE__), "/"));
		define('WCRENDR_URL', rtrim(plugin_dir_url(__FILE__), "/"));

		require WCRENDR_DIR.'/vendor/autoload.php';
		require WCRENDR_DIR.'/includes/plugin.php';

		if(!function_exists('WcRendr')) {
			function WcRendr() {
				return \WcRendr\Plugin::instance();
			}
		}
		
		if(!function_exists('rendr_logger')) {
			function rendr_logger() {
				return \WcRendr\Plugin::instance()->logger;
			}
		}

		WcRendr();

	}

?>