<?php

	namespace WcRendr;

	use WcRendr\Methods\WC_Rendr_Delivery;

	if (!defined('ABSPATH') || !defined('WPINC')) {
		exit;
	}

	/**
	 * Class Admin
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package WcRendr
	 */
	class Admin {

		/**
		 * Admin constructor.
		 */
		public function __construct() {

			// scripts
			add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);

			// Ajax
			add_action('wp_ajax_test_rendr_creds', [$this, 'test_rendr_creds']);

			// Books delivery on order processing
			add_action('woocommerce_order_status_processing', [$this, 'request_delivery']);

			// WC ORder actions
			add_filter('woocommerce_admin_order_actions', [$this, 'order_actions'], 10, 2);

			// Book delivery on demand
			add_action('admin_post_book-rendr-delivery', [$this, 'book_delivery']);

			add_action('admin_post_labels-rendr-delivery', [$this, 'labels_delivery']);

			// Delivery status column on shop_order WP_List
			add_filter( 'manage_edit-shop_order_columns', [$this, 'column_delivery_status'] );

			// Content for delivery status colun
			add_action( 'manage_shop_order_posts_custom_column', [$this, 'column_delivery_status_content'], 2 );

			// Enqueues required scripts
			add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

			// Delivered by rendr branding - only if user has chosen to accept
			add_action('woocommerce_after_add_to_cart_form', [$this, 'delivered_by_rendr'], 45);

			// Gets rendr delivery status
			add_action('wcrendr_fetch_delivery_status', [$this, 'get_delivery_status']);

			// Must be a 10 degit australia phone number
			add_action('woocommerce_checkout_process', [$this, 'enforce_phone_number_length']);

			// Checkl rendr t & c
			add_action('woocommerce_checkout_process', [$this, 'rendr_t_and_c_check']);

			add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'delivery_info_order_page']);

			// Terms & conditions must be accepted when choosing rendr
			add_action('woocommerce_review_order_before_submit', [$this, 'terms_and_conditions'], 1);

			// Ensures that city is enable in shipping calculator - required to fetch details
			add_filter( 'woocommerce_shipping_calculator_enable_city', [$this, 'enable_city_on_shipping_calculator'] );

			add_action('admin_print_footer_scripts', [$this, 'admin_js_target']);
			
			add_action('wp_footer', [$this, 'footer_placement'], 30);
			
			// Auto books orders via scheduled action
			add_action('rendr_request_auto_book', [$this, 'auto_book_order_delivery'], 10, 2);
			
			add_action('woocommerce_checkout_order_processed', [$this, 'store_order_atl_value'], 10, 3);
			
			add_action('woocommerce_checkout_posted_data', [$this, 'include_atl_to_posted_data'], 10, 1);

		}

		/**
		 * terms_and_conditions function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function terms_and_conditions() {

			$has_rendr = false;

			$chosen_method = isset($_POST['shipping_method']) ? $this->sanitize_shipping_method($_POST['shipping_method'])  : wc_get_chosen_shipping_method_ids();

			foreach($chosen_method as $smethoid) {
				if(strpos($smethoid, 'wcrendr') !== false) {
					$has_rendr = true; break;
				}
			}
			
			// Only apply this if ATL is enabled in  backend
			$rendr = new WC_Rendr_Delivery();
			
			if(!$rendr->can_have_authority_to_leave()) {
				$has_rendr = false;
			}

			if($has_rendr) {
				?>

				<p class="form-row validate-required" style="margin-top: 1em">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="terms_wcrendr" <?php checked(isset( $_POST['terms_wcrendr'] ) ); // WPCS: input var ok, csrf ok. ?> id="terms_wcrendr" />
						<span class="woocommerce-terms-and-conditions-checkbox-text"><?php esc_html_e('By proceeding with Rendr Delivery, you grant Rendr an Authority To Leave (ATL) the goods in a safe place and agree Rendr bears no responsibility for any loss or damage that may occur. See', 'rendr'); ?> <a href="https://rendr.delivery/information/terms-and-conditions" target="_blank"><?php esc_html_e('full Terms & Conditions', 'rendr'); ?></a></span>&nbsp;<span class="required">*</span>
					</label>
					<input type="hidden" name="terms-field" value="1" />
				</p>

				<?php
			}

		}

		/**
		 * enable_city_on_shipping_calculator function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return bool
		 */
		public function enable_city_on_shipping_calculator() {
			return true;
		}

		/**
		 * delivery_info_order_page function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $order
		 */
		public function delivery_info_order_page($order) {
			$id = get_post_meta($order->get_id(), 'rendr_delivery_id', true);

			if(empty($id)) {
				return;
			}

			$delivery_id = get_post_meta($order->get_id(), 'rendr_delivery_id', true);
			$delivery_status = ucwords(get_post_meta($order->get_id(), 'rendr_delivery_status', true));

			?>
			<h3><?php esc_html_e('Rendr Delivery', 'rendr'); ?></h3>
			<p>Delivery ID: <?php echo esc_html($delivery_id) ?><br>
				Delivery Status: <?php echo esc_html($delivery_status) ?><br>
				<?php if(!empty(get_post_meta($order->get_id(), 'rendr_delivery_ref', true))) : ?>Consignment Number: <?php echo esc_html(get_post_meta($order->get_id(), 'rendr_delivery_ref', true)) ?><br><?php endif; ?>
				<?php if(get_post_meta($order->get_id(), 'rendr_delivery_status', true) == 'requested') : ?><a href="https://retailer.rendr.delivery/<?php echo esc_attr($this->get_method()->get_option('brand_id')) ?>/deliveries/<?php echo esc_attr(get_post_meta($order->get_id(), 'rendr_delivery_id', true)) ?>" target="_blank"><?php esc_html_e('Book Delivery', 'rendr'); ?></a>
				<?php elseif(!empty(get_post_meta($order->get_id(), 'rendr_delivery_id', true))) : ?>
					<a href="https://retailer.rendr.delivery/<?php echo esc_attr($this->get_method()->get_option('brand_id')) ?>/deliveries/<?php echo esc_attr(get_post_meta($order->get_id(), 'rendr_delivery_id', true)) ?>" target="_blank"><?php esc_html_e('View in Rendr portal', 'rendr'); ?></a><br><?php endif; ?>
			</p>


			<?php

		}

		/**
		 * enforce_phone_number_length function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function enforce_phone_number_length() {

			if(!isset($_POST['shipping_method'])) {
				return;
			}

			$shipping_method = $this->sanitize_shipping_method($_POST['shipping_method']);

			if(((is_array($shipping_method) && strpos(serialize($shipping_method), 'wcrendr') !== false) || (!is_array($shipping_method) && strpos($shipping_method, 'wcrendr') !== false)) && !(preg_match('/^[0-9]{10}$/D', sanitize_text_field($_POST['billing_phone'])))){
				wc_add_notice( __("Please enter a 10 digit phone number. A mobile or landline with area code.", 'rendr')  ,'error' );
			}
		}

		/**
		 * sanitize_shipping_method function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $method
		 *
		 * @return array|string
		 */
		public function sanitize_shipping_method($method) {
			if(is_array($method)) {
				return array_map([$this, 'sanitize_shipping_method'], $method);
			} else {
				return sanitize_text_field($method);
			}
		}

		/**
		 * rendr_t_and_c_check function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function rendr_t_and_c_check() {

			if(!isset($_POST['shipping_method'])) {
				return;
			}
			
			$rendr = new WC_Rendr_Delivery();
			
			if(!$rendr->can_have_authority_to_leave()) {
				return;
			}

			$shipping_method = $this->sanitize_shipping_method($_POST['shipping_method']);
			$checked = isset($_POST['terms_wcrendr']) ? !empty($_POST['terms_wcrendr']) : false;

			if(((is_array($shipping_method) && strpos(serialize($shipping_method), 'wcrendr') !== false) || (!is_array($shipping_method) && strpos($shipping_method, 'wcrendr') !== false)) && !$checked){
				wc_add_notice( "You must check that you have read and agree with the Rendr Delivery terms and conditions."  ,'error' );
			}
		}

		/**
		 * delivered_by_rendr function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function delivered_by_rendr() {
			if(WcRendr()->admin->get_method()->get_option('disable_brand') !== 'yes') {
				echo '<rendr-placement asset-type="cart" asset-theme="'.WcRendr()->admin->get_method()->get_option('cart_brand_theme').'" asset-redirect=“true”></rendr-placement>';
			}
		}

		/**
		 * enqueue_scripts function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function enqueue_scripts() {
			
			if($this->get_method()->get_option('disable_brand') != 'yes' && ($this->get_method()->get_option('disable_banner') != 'yes' || $this->get_method()->get_option('disable_cart_brand') != 'yes')) {
				add_filter('clean_url', [$this, 'load_script_async'], 20, 1);
				wp_enqueue_script('rendr_snpt', 'https://prismic-io.s3.amazonaws.com/rendr-storefront/b1349b2b-8c12-43bd-b714-cac2d7551e12_RendrSnippetV2.js#rendr_loadasync');
			}

			// Only on required pages - move to Frontend
			// TODO move to frontend class
			/*if(is_product() || is_cart() || is_checkout()) {
				wp_enqueue_script('wcrendr_lightbox', WCRENDR_URL.'/assets/js/lightbox.min.js', ['jquery'], '1.0');
				wp_enqueue_style('wcrendr_lightbox-css', WCRENDR_URL.'/assets/css/lightbox.min.css');
			}*/
		}
		
		public function load_script_async($url) {			
			if(strpos($url, '#rendr_loadasync') !== false) {
				return str_replace('#rendr_loadasync', '', $url).(is_admin() ? '' : "' async='async");
			}
			return $url;
		}

		/**
		 * get_method function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return WC_Rendr_Delivery
		 */
		public function get_method() {
			if(!isset($this->method)) {
				$this->method = new WC_Rendr_Delivery();
			}
			return $this->method;
		}

		/**
		 * column_delivery_status_content function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $column
		 */
		public function column_delivery_status_content($column) {
			global $post;
			if($column == 'delivery_status') {
				if(!empty(get_post_meta($post->ID, 'rendr_delivery_id', true))) {
					echo esc_html(ucwords(get_post_meta($post->ID, 'rendr_delivery_status', true)));
				}
				if(!empty(get_post_meta($post->ID, 'rendr_delivery_ref', true))) {
					echo '<br>Consignment No: '.esc_html(get_post_meta($post->ID, 'rendr_delivery_ref', true));
				}
			}
		}

		/**
		 * admin_scripts function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function admin_scripts() {
			$screen = get_current_screen();

			if($screen->base === 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] === 'wcrendr') {
				wp_register_script('jquery-inputmask', WCRENDR_URL.'/assets/lib/inputmask/jquery.inputmask.min.js', ['jquery'], '5.0.9', true);
				wp_register_script('wcrendr-settings', WCRENDR_URL.'/assets/js/wcrendr-settings.js', ['jquery', 'jquery-ui-datepicker', 'jquery-inputmask'], WCRENDR_VERSION, true);
				wp_localize_script('wcrendr-settings', 'wcrendr_settings', [
					'ajax_url' => admin_url('admin-ajax.php'),
					'verify_creds' => wp_create_nonce('verify_creds_'.wp_get_current_user()->ID),
				]);
				wp_enqueue_script('wcrendr-settings');

			}
			wp_enqueue_style('wcrendr-admin', WCRENDR_URL.'/assets/css/admin.css', [], WCRENDR_VERSION);
		}

		/**
		 * column_delivery_status function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $columns
		 *
		 * @return array
		 */
		public function column_delivery_status($columns) {

			$new_columns = [];

			foreach($columns as $id => $col) {
				if($id == 'wc_actions') {
					$new_columns['delivery_status'] = __('Delivery Status', 'rendr');
				}
				$new_columns[$id] = $col;
			}
			return $new_columns;
		}

		/**
		 * test_rendr_creds function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function test_rendr_creds() {

			try {

				if(!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'verify_creds_'.wp_get_current_user()->ID)) {
					throw new Error(__('Invalid or expired security token. Please refresh the page and try again', 'rendr'));
				}
				
				$creds = [
					'brand_id'      => sanitize_text_field($_POST['creds']['brand_id']),
					'client_id'     => sanitize_text_field($_POST['creds']['client_id']),
					'client_secret' => sanitize_text_field($_POST['creds']['client_secret'])
				];
				
				if(!empty($_POST['creds']['store_id'])) {
					$creds['store_id'] = sanitize_text_field($_POST['creds']['store_id']);
				}

				Plugin::instance()->get_method()->verify_credentials($creds);

				wp_send_json_success(['message' => __('Test successful.', 'rendr'),]);

			} catch(\Exception $e) {

				wp_send_json_error(['message' => __('Test failed. Message: ', 'rendr').$e->getMessage()]);

			}

		}

		/**
		 * get_delivery_status function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $order_id
		 *
		 * @throws \Exception
		 */
		public function get_delivery_status($order_id) {

			$order = wc_get_order($order_id);

			if(!$order){
				throw new \Exception('Order ID '.$order_id.'not found.');
			}

			$methods = $order->get_shipping_methods();
			foreach($methods as $method) {
				/** @var \WC_Order_Item_Shipping $method  */
				if( strpos($method->get_method_id(), 'wcrendr') === false && $method->get_method_id() !== 'wcrendr') {
					continue;
				}

				if(!method_exists($method, 'get_instance_id')) {
					$instance_id = explode(':', $method->get_method_id());
					$instance_id = $instance_id[1];
				} else {
					$instance_id = $method->get_instance_id();
				}
				$rendr = new WC_Rendr_Delivery($instance_id);
				$rendr->fetch_delivery_status($order, $method);
			}

		}

		/**
		 * request_delivery function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $order_id
		 */
		public function request_delivery($order_id) {

			$order = wc_get_order($order_id);

			$methods = $order->get_shipping_methods();

			foreach($methods as $method) {
				/** @var \WC_Order_Item_Shipping $method  */
				if( strpos($method->get_method_id(), 'wcrendr') === false && $method->get_method_id() !== 'wcrendr') {
					continue;
				}
				if(!method_exists($method, 'get_instance_id')) {
					$instance_id = explode(':', $method->get_method_id());
					$instance_id = $instance_id[1];
				} else {
					$instance_id = $method->get_instance_id();
				}

				$rendr = new WC_Rendr_Delivery($instance_id);
				$rendr->request_delivery($order, $method->get_name(), $method);
				
			}

		}

		/**
		 * book_delivery function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function book_delivery() {

			if(!current_user_can('manage_options') || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wcrendr-book-delivery')) {
				wp_die('Busted!');
			}

			$rendr = new WC_Rendr_Delivery();
			$order = wc_get_order(sanitize_text_field($_GET['order']));

			try {
				$rendr->book_delivery(get_post_meta($order->get_id(), 'rendr_delivery_id', true), $order);
			} catch(Exception $e) {

			}
			wp_redirect((!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : admin_url()), 302);
			exit;

		}

		/**
		 * auto_book_order_delivery function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function auto_book_order_delivery($order_id, $delivery_id) {
			
			$order = wc_get_order($order_id);
			
			if(!$order) {
				throw new Exception('Invalid order');
			}
			
			$rendr = new WC_Rendr_Delivery();
			
			$rendr->book_delivery($delivery_id, $order);
			
		}

		/**
		 * labels_delivery function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function labels_delivery() {

			if(!current_user_can('manage_options') || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wcrendr-book-delivery')) {
				wp_die('Busted!');
			}

			$rendr = new WC_Rendr_Delivery();
			$order = wc_get_order(sanitize_text_field($_GET['order']));

			try {
				$rendr->labels_delivery(get_post_meta($order->get_id(), 'rendr_delivery_id', true));
			} catch(Exception $e) {

			}

		}

		/**
		 * order_actions function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $actions
		 * @param $order
		 *
		 * @return mixed
		 */
		public function order_actions($actions, $order) {

			if($order->get_status() == 'processing' && !empty(get_post_meta($order->get_id(), 'rendr_delivery_id', true)) && (get_post_meta($order->get_id(), 'rendr_delivery_status', true) == 'requested')) {
				$actions['book'] = [
					'url' => esc_url('https://retailer.rendr.delivery/'.$this->get_method()->get_option('brand_id').'/deliveries/'.get_post_meta($order->ID, 'rendr_delivery_id', true)),
					'name' => 'Book',
					'action' => 'book',
				];
			} else if(!empty(get_post_meta($order->get_id(), 'rendr_delivery_id', true))) {
				$actions['view_in_rendr'] = [
					'url' => esc_url('https://retailer.rendr.delivery/'.$this->get_method()->get_option('brand_id').'/deliveries/'.get_post_meta($order->ID, 'rendr_delivery_id', true)),
					'name' => 'View in Rendr',
					'action' => 'view_in_rendr',
				];
			}
			return $actions;

		}

		/**
		 * admin_js_target function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function admin_js_target() {
			global $pagenow;
			if ( $pagenow == 'edit.php' ) { ?>
				<script type="text/javascript">
					jQuery(document).ready( function () {
						jQuery('.book').attr('target','_blank');
						jQuery('.view_in_render').attr('target','_blank');
					});
				</script>
			<?php }
		}
		
		public function footer_placement() {
			if(WcRendr()->admin->get_method()->get_option('disable_brand') !== 'yes' && WcRendr()->admin->get_method()->get_option('disable_banner') !== 'yes') {
				echo '<div style="width: 100%; margin: 0;"><rendr-placement asset-type="banner" asset-theme="'.WcRendr()->admin->get_method()->get_option('banner_theme').'" asset-redirect=“true”></rendr-placement></div>';
			}
		}
		
		public function rendr_update_checkout_on_atl_change() {
			
			if(!is_checkout()) {
				return;
			}
			
			$rendr = new WC_Rendr_Delivery();
			
			if(!$rendr->can_have_authority_to_leave()) {
				return;
			}
			
			?>
			<script type="text/javascript">
				jQuery(document).on('change', '#terms_wcrendr', function() {
					jQuery('body').trigger('update_checkout');
				});
			</script>
			<?php
			
		}
		
		// Ensures we save the order ATL preference 
		public function store_order_atl_value($order_id, $posted_data, $order) {
			
			// IS it being delivered by rendr?
			$is_rendr_delivery = false;

			$methods = $order->get_shipping_methods();
			foreach($methods as $method) {
				/** @var \WC_Order_Item_Shipping $method  */
				if( strpos($method->get_method_id(), 'wcrendr') === false && $method->get_method_id() !== 'wcrendr') {
					continue;
				}

				if(!method_exists($method, 'get_instance_id')) {
					$instance_id = explode(':', $method->get_method_id());
					$instance_id = $instance_id[1];
				} else {
					$instance_id = $method->get_instance_id();
				}
				$rendr = new WC_Rendr_Delivery($instance_id);
				$is_rendr_delivery = true;
				
			}
			
			if(!$is_rendr_delivery) {
				return;
			}
			
			if($is_rendr_delivery && $rendr->can_have_authority_to_leave() && !empty($posted_data['terms_wcrendr']) && $posted_data['terms_wcrendr'] == 'on') {
				$order->add_meta_data('wcrendr_atl', 'yes', true);
			} else {
				$order->add_meta_data('wcrendr_atl', 'no', true);
			}
			
			$order->save();
			
		}
		
		public function include_atl_to_posted_data($data) {
			
			if(isset($_POST['terms_wcrendr']) && $_POST['terms_wcrendr'] == 'on') {
				$data['terms_wcrendr'] = 'on';
			}
			
			return $data;
			
		}
		
	}