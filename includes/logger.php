<?php

namespace WcRendr;

if (!defined('ABSPATH') || !defined('WPINC')) {
	exit;
}

class Rendr_Logger {

	/* The domain handler used to name the log */
	private $_domain = 'rendr';

	/* The WC_Logger instance */
	private $_logger;
	
	private $_debug;

	/**
	 * __construct.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		if(class_exists('\WC_Logger')) {
			$this->_logger = new \WC_Logger();
		} else {
			add_action('init', [$this, 'set_logger'], 20);
		}
	}
	
	public function set_logger() {
		if(class_exists('\WC_Logger')) {
			$this->_logger = new \WC_Logger();
		}
	}


	/**
	 * add function.
	 *
	 * Uses the build in logging method in WooCommerce.
	 * Logs are available inside the System status tab
	 *
	 * @access public
	 * @param  string|array|object
	 * @return void
	 */
	public function add( $param )
	{
		
		// Ensures log is active
		if(Plugin::instance()->get_method()->get_option('enable_debug') != 'yes' || empty($this->_logger)) {
			return;
		}
		
		if( is_array( $param ) ) {
			$param = print_r( $param, TRUE );
		} else if(is_object( $param )) {
			$param = print_r( get_object_vars($param), TRUE );
		}

		$this->_logger->add( $this->_domain, $param );
	}


	/**
	 * clear function.
	 *
	 * Clears the entire log file
	 *
	 * @access public
	 * @return void
	 */
	public function clear()
	{
		$this->_logger->clear( $this->_domain );
	}


	/**
	 * separator function.
	 *
	 * Inserts a separation line for better overview in the logs.
	 *
	 * @access public
	 * @return void
	 */
	public function info($file, $line, $method)
	{
		$this->add( '--- File: ' . $file . ' (' . $line . ') ---' );
		$this->add( '--- Method: ' . $method . ' ---' );
	}

	/**
	 * separator function.
	 *
	 * Inserts a separation line for better overview in the logs.
	 *
	 * @access public
	 * @return void
	 */
	public function separator()
	{
		$this->add( '----------------------------------' );
	}


	/**
	 * get_domain function.
	 *
	 * Returns the log text domain
	 *
	 * @access public
	 * @return string
	 */
	public function get_domain()
	{
		return $this->_domain;
	}
	
}