<?php

	namespace WcRendr;

	if (!defined('ABSPATH') || !defined('WPINC')) {
		exit;
	}

	/**
	 * Class Autoloader
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package WcRendr
	 */
	class Autoloader {

		/**
		 * @var
		 */
		private static $classes_map;

		/**
		 * run function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function run() {
			spl_autoload_register([__CLASS__, 'autoload']);
		}

		/**
		 * get_classes_map function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return mixed
		 */
		public static function get_classes_map() {
			if(!self::$classes_map) {
				self::init_classes_map();
			}
			return self::$classes_map;
		}

		/**
		 * init_classes_map function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function init_classes_map() {
			self::$classes_map = [
				'Admin' => 'includes/admin.php',
				'Frontend' => 'includes/frontend.php',
				'Rendr_Logger' => 'includes/logger.php',
				'Methods\WC_Rendr_Delivery' => 'includes/methods/wc-rendr-delivery.php'
			];
		}

		/**
		 * load_class function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $relative_class_name
		 */
		private static function load_class($relative_class_name) {
			$classes_map = self::get_classes_map();

			$filename = WCRENDR_DIR.'/'.$classes_map[$relative_class_name];

			if(is_readable($filename)) {
				require $filename;
			}
		}

		/**
		 * autoload function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $class
		 */
		private static function autoload($class) {
			if(0 !== strpos($class, __NAMESPACE__.'\\')) {
				return;
			}

			$relative_class_name = preg_replace('/^'.__NAMESPACE__.'\\\/', '', $class);
			$final_class_name = __NAMESPACE__.'\\'.$relative_class_name;

			if(!class_exists($final_class_name)) {
				self::load_class($relative_class_name);
			}
		}

	}
