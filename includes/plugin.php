<?php

	namespace WcRendr;

	if (!defined('ABSPATH') || !defined('WPINC')) {
		exit;
	}

	/**
	 * Class Plugin
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package WcRendr
	 */
	final class Plugin {

		/**
		 * @var null
		 */
		private static $_instance = null;

		/**
		 * @var Admin
		 */
		public $admin;

		/**
		 * @var Admin
		 */
		private $frontend;

		/**
		 * instance function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return Plugin|null
		 */
		public static function instance() {

			if(is_null(self::$_instance)) {
				self::$_instance = new self();
			}

			return self::$_instance;

		}

		/**
		 * __clone function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function __clone() {
			wc_doing_it_wrong(__FUNCTION__, 'Cloning is not allowed');
		}

		/**
		 * __wakeup function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function __wakeup() {
			wc_doing_it_wrong(__FUNCTION__, 'Unserializing instances of this class is not allowed');
		}

		/**
		 * Plugin constructor.
		 */
		private function __construct() {

			// Registers autoloader
			$this->register_autoloader();

			$this->admin = new Admin();

			$this->frontend = new Frontend();
			
			$this->logger = new Rendr_Logger();

			// Register shipping method
			add_filter('woocommerce_shipping_methods', [$this, 'register_shipping_method']);
			
			add_filter('plugin_action_links_rendr/rendr.php', [$this, 'plugin_links']);

		}

		/**
		 * register_autoloader function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		private function register_autoloader() {
			require WCRENDR_DIR.'/includes/autoloader.php';

			Autoloader::run();
		}

		/**
		 * register_shipping_method function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $methods
		 *
		 * @return array
		 */
		public function register_shipping_method($methods) {

			$methods['wcrendr'] = '\WcRendr\Methods\WC_Rendr_Delivery';

			return $methods;
		}


		/**
		 * get_method function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return mixed|\WC_Shipping_Method|null
		 */
		public function get_method() {
			$methods = WC()->shipping()->get_shipping_methods();
			return isset($methods['wcrendr']) ? $methods['wcrendr'] : null;
		}
		


		/**
		 * plugin_links function
		 *
		 * @version 1.2.1
		 * @since   1.2.1
		 * @return array
		 */
		public function plugin_links($links) {
    		
    		$links = array_merge(['<a href="'.esc_url(add_query_arg(['page' => 'wc-settings', 'tab' => 'shipping', 'section' => 'wcrendr'], admin_url('admin.php'))).'">Settings</a>'], $links);
    		
    		return $links;
    		
		}

	}