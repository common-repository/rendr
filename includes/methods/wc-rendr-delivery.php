<?php

namespace WcRendr\Methods;

use DVDoug\BoxPacker\Packer;
use DVDoug\BoxPacker\Test\TestBox;
use DVDoug\BoxPacker\Test\TestItem;
use WcRendr\Plugin;

if (!defined('ABSPATH') || !defined('WPINC')) {
	exit;
}

/**
 * Class WC_Rendr_Delivery
 *
 * @version 1.0.0
 * @since   1.0.0
 * @package WcRendr\Methods
 */
class WC_Rendr_Delivery extends \WC_Shipping_Method {

	/**
	 * Standard API endpoint
	 *
	 * @var string
	 */
	protected $api_endpoint = 'https://api.rendr.delivery/';

	/**
	 * UAT API endpoint
	 *
	 * @var string
	 */
	protected $api_uat_endpoint = 'https://uat.api.rendr.delivery/';
	
	/**
	 * Product types artray
	 *
	 * @var array
	 */
	private $product_types = [
		'alcohol',
		'tobacco',
		'high_value',
		'secure_documents',
		'prescription_meds_s4',
		'prescription_meds_s2',
		'prescription_meds_s8',
	];
	

	/**
	 * WC_Rendr_Delivery constructor.
	 *
	 * @param int $instance_id
	 */
	public function __construct($instance_id = 0) {

		$this->id = 'wcrendr';
		$this->instance_id = absint($instance_id);
		$this->method_title = __('Rendr Delivery', 'rendr');
		$this->method_description = __('Let customers choose Rendr as their delivery method.', 'rendr');
		$this->supports = array(
			'shipping-zones',
			'settings',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init();

		add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
		add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_opening_hours'], 20);

	}

	/**
	 * init function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function init() {
		$this->instance_form_fields = [
/*				'label_fast' => [
				'type' => 'text',
				'title' => 'Fast Delivery Title',
				'default' => 'Fastest',
				'description' => 'Label shown to user for the fast rendr delivery type',
				'desc_tip' => true,
			],*/
			'disable_fast' => [
				'type' => 'checkbox',
				'title' => 'Disable Fast Delivery',
				'label' => 'Do not display "fast" delivery option to customers.',
				'description' => 'Check this to ignore any delivery options of type fast.',
				'desc_tip' => true,
			],
/*				'label_flexible' => [
				'type' => 'text',
				'title' => 'Flex Delivery Title',
				'default' => 'Flex',
				'description' => 'Label shown to user for the flex rendr delivery type',
				'desc_tip' => true,
			],*/
			'disable_flexible' => [
				'type' => 'checkbox',
				'title' => 'Disable Flex Deliveries',
				'label' => 'Do not display "flex" delivery options  cto customers.',
				'description' => 'Check this to ignore any delivery options of type flex.',
				'desc_tip' => true,
			],
/*				'label_standard' => [
				'type' => 'text',
				'title' => 'Standard Delivery Title',
				'default' => 'Standard',
				'description' => 'Label shown to user for the fast standard delivery type',
				'desc_tip' => true,
			],*/
			'disable_standard' => [
				'type' => 'checkbox',
				'title' => 'Disable Standard Delivery',
				'label' => 'Do not display "standard" delivery options to customers.',
				'description' => 'Check this to ignore any delivery options of type standard.',
				'desc_tip' => true,
			],
		];
		$this->form_fields = include(WCRENDR_DIR.'/includes/methods/wc-rendr-delivery-settings.php');
		$this->title = __('Rendr Delivery', 'rendr');
		$this->tax_status = 'taxable';
		$this->label_fast = 'Rendr Fast';
		$this->label_flexible = 'Rendr Flexible';
		$this->label_standard = 'Rendr Standard';
		

	}
	
	private function get_category_select_options() {
		$terms = [];
		foreach(get_terms([
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC',
			'parent' => 0
		]) as $term) {
			$terms = $this->get_term_select_options_rec($terms, $term, '');
		}
		
		return $terms;
	}
	
	private function get_term_select_options_rec($terms, $term, $prefix) {
		
		$terms[$term->term_id] = $prefix.' '.$term->name;
		
		foreach(get_terms([
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC',
			'parent' => $term->term_taxonomy_id
		]) as $child_term) {
			$terms = $this->get_term_select_options_rec($terms, $child_term, '&mdash;'.$prefix);
		}
		
		return $terms;
		
	}
	
	public function filter_variations_type($var) {
		return $var !== 'variation';
	}
	
	public function include_attributes_in_variation_title()		 {
		return true;
	}
	
	
	private function get_product_select_options() {
		$options = [];
		$datastore = \WC_Data_Store::load('product-variation');
		foreach(wc_get_products([
			'type' => array_filter(array_merge( array_keys( wc_get_product_types() ) ), [$this, 'filter_variations_type']),
			'limit' => -1,
			'visibility' => 'visible',
		]) as $product) {
			$options[$product->get_id()] = $product->get_title();
			if($product->get_type() == 'variable') {
				$options[$product->get_id()] .= ' - All Variations';
				add_filter('woocommerce_product_variation_title_include_attributes', [$this, 'include_attributes_in_variation_title']);
				foreach(wc_get_products([
					'limit' => -1,
					'parent' => $product->get_id(),
					'visibility' => 'inherit',
					'type' => ['variation'],
				]) as $variation) {
					$datastore->read($variation);
					$options[$variation->get_id()] = '&mdash; '.$variation->get_name();
				}
				remove_filter('woocommerce_product_variation_title_include_attributes', [$this, 'include_attributes_in_variation_title']);
			}
		}

		return $options;
	}

	/**
	 * generate_opening_hours_html function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param $key
	 * @param $data
	 *
	 * @return false|string
	 */
	public function generate_opening_hours_html($key, $data) {
		$field_key = $this->get_field_key($key);
		$opening_hours = $this->settings['opening_hours'];
		ob_start();
		?>
		<tr valign="top" style="display: none !important;">
			<td colspan="2" style="padding: 0">
				<div class="wcrendr-oh-table">
					<table cellpadding="0" cellspacing="0">
						<thead>
							<tr>
								<th><?php esc_html_e('Available?', 'rendr'); ?></th>
								<th><?php esc_html_e('Day', 'rendr'); ?></th>
								<th><?php esc_html_e('From', 'rendr'); ?></th>
								<th><?php esc_html_e('To', 'rendr'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) :
							?>
								<tr>
									<td>
										<input <?php if(is_array($opening_hours) && !empty($opening_hours[$day])) { echo 'checked="checked"'; } ?> type="checkbox" name="<?php echo esc_attr( $field_key ); ?>_<?php echo esc_attr($day) ?>" />
									</td>
									<td><?php echo esc_html(ucwords($day)) ?></td>
									<td>
										<input type="text" name="<?php echo esc_attr( $field_key ); ?>_<?php echo esc_attr($day) ?>_from" placeholder="09:00" value="<?php if(is_array($opening_hours) && !empty($opening_hours[$day]) && !empty($opening_hours[$day]['from'])) { echo esc_attr($opening_hours[$day]['from']); } ?>" />
									</td>
									<td>
										<input type="text" name="<?php echo esc_attr( $field_key ); ?>_<?php echo esc_attr($day) ?>_to" placeholder="17:00" value="<?php if(is_array($opening_hours) && !empty($opening_hours[$day]) && !empty($opening_hours[$day]['to'])) { echo esc_attr($opening_hours[$day]['to']); } ?>" />
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</td>
		</tr>
		</table>
		<table class="form-table">
		<?php
		return ob_get_clean();
	}

	/**
	 * generate_pack_presets_html function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param $key
	 * @param $data
	 *
	 * @return false|string
	 */
	public function generate_pack_presets_html($key, $data) {
		$field_key = $this->get_field_key($key);
		try {
			if(!empty($this->settings['packing_presets'])) {
				$presets = json_decode($this->settings['packing_presets'], true);
				if(!is_array($presets)) {
					$presets = [];
				}
			} else {
				$presets = [];
			}
		} catch(\Exception $e) {
			$presets = [];
		}
		ob_start();
		include(WCRENDR_DIR.'/includes/methods/templates/pack-presets.php');
		return ob_get_clean();
	}

	/**
	 * get_post_data function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return array
	 */
	public function get_post_data() {
		$data = parent::get_post_data();
		if(isset($data['woocommerce_wcrendr_packing_presets_clone'])) {
			unset($data['woocommerce_wcrendr_packing_presets_clone']);
		}
		if(isset($data['woocommerce_wcrendr_packing_presets']) && is_array($data['woocommerce_wcrendr_packing_presets'])) {
			$data['woocommerce_wcrendr_packing_presets'] = json_encode($data['woocommerce_wcrendr_packing_presets']);
		}
		return $data;
	}

	/**
	 * generate_button_html function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param $key
	 * @param $data
	 *
	 * @return false|string
	 */
	public function generate_button_html( $key, $data ) {

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
			</th>
			<td class="forminp">
				<fieldset><button type="button" class="button" id="<?php echo esc_attr($data['id']); ?>"><?php echo esc_html($data['title']); ?></button>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * has_valid_credentials function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return false
	 */
	public function has_valid_credentials() {
		return false;
	}

	/**
	 * process_opening_hours function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return bool|void
	 */
	public function process_opening_hours() {

		if($this->instance_id) {
			return;
		}

		$post_data = $this->get_post_data();
		
		$opening_hours = [];
		
		foreach($post_data as $index => $value) {
			if(strpos($index, 'woocommerce_wcrendr_opening_hours') === 0) {
				$day = str_replace(['woocommerce_wcrendr_opening_hours_', '_from', '_to'], '', $index);
				if($index == 'woocommerce_wcrendr_opening_hours_'.$day.'_from') {
					$field = 'from';
				} elseif($index == 'woocommerce_wcrendr_opening_hours_'.$day.'_to') {
					$field = 'to';
				} else {
					$field = 'on';
				}
				if(!isset($opening_hours[$day])) {
					$opening_hours[$day] = [];
				}
				$opening_hours[$day][$field] = sanitize_text_field($value);
			}
		}

		foreach($opening_hours as $day => $values) {
			if(empty($values['from'])) {
				$opening_hours[$day]['from'] = '00:00';
			}
			if(empty($values['to'])) {
				$opening_hours[$day]['to'] = '23:59';
			}
		}

		$this->settings['opening_hours'] = $opening_hours;

		return update_option($this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
		
	}

	/**
	 * verify_credentials function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param array $args
	 *
	 * @throws \Exception
	 */
	public function verify_credentials(array $args) {
		
		// Reset auth toke n
		delete_transient('wcvrendr_auth_token');

		if(empty($args['brand_id'])) {
			throw new \Exception(__('Brand ID is required.', 'rendr'));
		}

		if(empty($args['store_id'])) {
			//throw new \Exception(__('Store ID is required.', 'rendr'));
		}

		if(empty($args['client_id'])) {
			throw new \Exception(__('Client ID is required.', 'rendr'));
		}

		if(empty($args['client_secret'])) {
			throw new \Exception(__('Client secret is required.', 'rendr'));
		}
		
		$request = wp_remote_post($this->get_api_endpoint().sanitize_text_field($args['brand_id']).'/auth/token', [
			'headers' => [
				'content-type' => 'application/json',
			],
			'body' => json_encode([
				'grant_type' => 'client_credentials',
				'client_id' => sanitize_text_field($args['client_id']),
				'client_secret' => sanitize_text_field($args['client_secret']),
			]),
			'timeout' => 10000,
		]);

		if(wp_remote_retrieve_response_code($request) !== 200) {
			
			rendr_logger()->add("Unable to retrieve credentials when verifying them");
			rendr_logger()->add($this->get_api_endpoint().sanitize_text_field($args['brand_id']).'/auth/token');
			throw new \Exception(wp_remote_retrieve_response_message($request).' - Status code: '.wp_remote_retrieve_response_code($request).'<br>Please contact <a href="mailto:support@rendr.delivery">support@rendr.delivery</a> to confirm Rendr Credentials');
		}

		return true;
		
	}

	/**
	 * get_ready_pickup_date function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @throws \Exception
	 */
	private function get_ready_pickup_date($day = false) {
		
		$now = new \DateTime('now', new \DateTimeZone(get_option('timezone_string')));

		if(!empty($this->get_option('handling_time_hours'))) {
			$now->add(new \DateInterval('PT'.$this->get_option('handling_time_hours').'H'));
		}

		if(!empty($this->get_option('handling_time_days'))) {
			$now->add(new \DateInterval('P'.$this->get_option('handling_time_days').'D'));
		}

		if($day) {
			// Next business day
			$now->modify('+1 day');
			while($now->format('N') >= 6) {
				$now->modify('+1 day');
			}
			$now = new \DateTime($now->format('Y-m-d').' 10:00:00', new \DateTimeZone(get_option('timezone_string')));
			$same_day = false;
		} else {
			$same_day = true;
		}

		$now->modify('+30 minutes');

		return $now;

		$c = 0;
		// while day is not available or if day is available but we are past the closing time or  -> try the following day
		while(empty($this->settings['opening_hours'][strtolower($now->format('l'))])
			||
			(
				!empty($this->settings['opening_hours'][strtolower($now->format('l'))])
				&&
				$same_day
				&&
				$now->format('Gi') >= ltrim(str_replace(':', '', $this->settings['opening_hours'][strtolower($now->format('l'))]['to']), '0')
			)
		) {
			$now->add(new \DateInterval('P1D'));
			$same_day = false;
		}

		// If its aday in the future ensure pick up time is earliest in the day as we have already applied our handling time.
		if(!$same_day || $now->format('Hi') < $this->settings['opening_hours'][strtolower($now->format('l'))]['from']) {
			$now = new \DateTime($now->format('Y-m-d').' '.$this->settings['opening_hours'][strtolower($now->format('l'))]['from'].':00', new \DateTimeZone(get_option('timezone_string')));
		}

		return $now->format('c');

	}

	/**
	 * get_box_presets function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return array|mixed
	 */
	public function get_box_presets() {

		try {
			if(!empty($this->settings['packing_presets'])) {
				$presets = json_decode($this->settings['packing_presets'], true);
				if(!is_array($presets)) {
					$presets = [];
				}
			} else {
				$presets = [];
			}
		} catch(\Exception $e) {
			$presets = [];
		}

		return $presets;

	}

	/**
	 * get_endpoint function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param $endpoint
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function get_endpoint($endpoint) {
		if(empty($this->get_option('brand_id'))) {
			throw new \Exception('Empty credentials.');
		}
		return $this->get_api_endpoint().$this->settings['brand_id'].'/'.ltrim($endpoint, "/");
	}

	/**
	 * get_api_endpoint function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @return string
	 */
	private function get_api_endpoint() {
		
		if($this->get_option('enable_uat') == 'yes') {
			return $this->api_uat_endpoint;
		}
		
		return $this->api_endpoint;
		
	}

	/**
	 * get_auth_token function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return mixed
	 * @throws \Exception
	 */
	private function get_auth_token() {


		if(($token = get_transient('wcvrendr_auth_token')) != false) {
			return $token;
		}
		if(empty($this->get_option('client_id')) || empty($this->get_option('client_secret'))) {
			throw new \Exception('Empty or invalid credentials.');
		}

		$request = wp_remote_post($this->get_endpoint('/auth/token'), [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => json_encode([
				'grant_type' => 'client_credentials',
				'client_id' => $this->get_option('client_id'),
				'client_secret' => $this->get_option('client_secret'),
			]),
			'timeout' => 10000,
		]);

		if(wp_remote_retrieve_response_code($request) != 200) {
			throw new \Exception('Error retrieving authorization code. Message: '.wp_remote_retrieve_response_message($request));
		}

		$body = json_decode(wp_remote_retrieve_body($request), true);

		if(empty($body['data']['access_token'])) {
			throw new \Exception('Error retrieving authorization code.');
		}

		set_transient('wcvrendr_auth_token', $body['data']['access_token'], HOUR_IN_SECONDS);

		return $body['data']['access_token'];

	}

	/**
	 * get_package_line_items function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param $package
	 *
	 * @return array
	 */
	private function get_package_line_items($package) {

		$items = [];

		foreach($package['contents'] as $key => $item) {

			$items[] = [
				'code' => empty($item['data']->get_sku()) ? 'DEFAULTSKU'.substr(md5(time()), 0, 8) : $item['data']->get_sku(),
				'name' => $item['data']->get_name(),
				'price_cents' => round((($item['line_total']+$item['line_tax'])/$item['quantity'])*100),
				'quantity' => (int)$item['quantity'],
			];

		}

		return $items;

	}

	/**
	 * get_item_shipping_attribute function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param $item
	 * @param $attr
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	private function get_item_shipping_attribute($item, $attr) {

		$func = "get_{$attr}";
		$value = $item['data']->$func();

		if(empty($value)) {
			$value = $this->get_option('default_'.$attr);
		}

		if(empty($value)) {
			throw new \Exception('Cannot calculate parcels. '.$attr.' is invalid.');
		}

		if($attr == 'weight') {
			return wc_get_weight($value, 'kg');
		}

		return wc_get_dimension($value, 'cm');

	}

	/**
	 * get_package_parcels function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param $package
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function get_package_parcels($package) {

		$parcels = [];

		if($this->get_option('packing_preference') == 'together') {

			$parcel_w = 0;
			$parcel_h = 0;
			$parcel_l = 0;
			$parcel_weight = 0;
			
			$counter = 0;

			foreach($package['contents'] as $item_key => $lineitem) {

				$height = (int)$this->get_item_shipping_attribute($lineitem, 'height');
				$width = (int)$this->get_item_shipping_attribute($lineitem, 'width');
				$length = (int)$this->get_item_shipping_attribute($lineitem, 'length');
				$weight = (float)$this->get_item_shipping_attribute($lineitem, 'weight');

				if($counter === 0) {
					$parcel_h = $height;
					$parcel_l = $length;
					$parcel_w = $width;
					$parcel_weight = $weight;
				} else {
					if($height <= $width && $height <= $length) {
						$parcel_h += $height;
					} else if($width <= $height && $width <= $length) {
						$parcel_w += $width;
					} else {
						$parcel_l += $length;
					}
					$parcel_weight += $weight;
				}
				
				$counter++;

			}

			if($parcel_weight < 1) {
				$parcel_weight = 1;
			}

			$parcels[] = [
				'reference' => "Parcel #0001",
				'description' => '',
				'length_cm' => (int)$parcel_l,
				'height_cm' => (int)$parcel_h,
				'width_cm' => (int)$parcel_w,
				'weight_kg' => (int)$parcel_weight,
				'quantity' => 1,
			];

		} else if($this->get_option('packing_preference') == 'separate') {
			$i2 = 1;
			foreach($package['contents'] as $i => $lineitem) {

				$height = (int)$this->get_item_shipping_attribute($lineitem, 'height');
				$width = (int)$this->get_item_shipping_attribute($lineitem, 'width');
				$length = (int)$this->get_item_shipping_attribute($lineitem, 'length');
				$weight = (float)$this->get_item_shipping_attribute($lineitem, 'weight') < 1 ? 1 : (float)$this->get_item_shipping_attribute($lineitem, 'weight');


				$parcels[] = [
					'reference' => "Parcel #".str_pad($i2, 4, '0', STR_PAD_LEFT),
					'description' => 'Contents: '.$lineitem['data']->get_name(),
					'length_cm' => (int)$length,
					'height_cm' => (int)$height,
					'width_cm' => (int)$width,
					'weight_kg' => (int)$weight,
					'quantity' => 1,
				];
				$i2++;
			}

		} else {

			$packer = new Packer();

			if(empty($this->get_box_presets())) {
				throw new \Exception('Preset of box sizes not defined. Cannot calculate order parcels.');
			}

			$max_height = 0;
			$max_width = 0;
			$max_length = 0;
			$box_index = 1;

			// Adds box sizes
			foreach($this->get_box_presets() as $box) {

				$max_width = $box['width'] > $max_width ? $box['width'] : $max_width;
				$max_height = $box['height'] > $max_height ? $box['height'] : $max_height;
				$max_length = $box['length'] > $max_length ? $box['length'] : $max_length;

				$packer->addBox(new TestBox($box['label'], $box['width'], $box['length'], $box['height'], 0, $box['width']-4, $box['length']-4, $box['height']-4, 100000));
			}

			// Adds our line items
			foreach($package['contents'] as $lineitem) {

				$height = (int)$this->get_item_shipping_attribute($lineitem, 'height');
				$width = (int)$this->get_item_shipping_attribute($lineitem, 'width');
				$length = (int)$this->get_item_shipping_attribute($lineitem, 'length');
				$weight = (float)$this->get_item_shipping_attribute($lineitem, 'weight') < 1 ? 1 : $this->get_item_shipping_attribute($lineitem, 'weight');

				if($height <= $max_height && $length < $max_length && $width < $max_width) {

					$packer->addItem(new TestItem($lineitem['data']->get_name(), $width, $length, $height, $weight, false), $lineitem['quantity']);

				} else {

					$parcels[] = [
						'reference' => "Parcel #".str_pad($box_index, 4, '0', STR_PAD_LEFT),
						'description' => 'Type: '."Parcel #".str_pad($box_index, 4, '0', STR_PAD_LEFT).' | Item: '.$lineitem['data']->get_name(),
						'length_cm' => (int)$length,
						'height_cm' => (int)$height,
						'width_cm' => (int)$width,
						'weight_kg' => (int)$this->get_item_shipping_attribute($lineitem, 'weight'),
						'quantity' => (int)$lineitem['quantity'],
					];

				}

			}

			$packedBoxes = $packer->pack();

			foreach ($packedBoxes as $packedBox) {

				$_packedItems = $packedBox->getItems();
				$packedItems = [];
				foreach($_packedItems as $packedItem) {
					if(!isset($packedItems[$packedItem->getDescription()])) {
						$packedItems[$packedItem->getDescription()] = 1;
					} else {
						$packedItems[$packedItem->getDescription()]++;
					}
				}
				$packedItemsTitle = [];
				foreach($packedItems as $item => $qty) {
					$packedItemsTitle[] = $qty.'x '.$item;
				}

				$parcels[] = [
					'reference' => "Parcel #".str_pad($box_index, 4, '0', STR_PAD_LEFT),
					'description' => 'Type: '.$packedBox->getBox()->getReference().' | Items: '.implode(', ', $packedItemsTitle),
					'length_cm' => (int)$packedBox->getBox()->getOuterLength(),
					'height_cm' => (int)$packedBox->getBox()->getOuterDepth(),
					'width_cm' => (int)$packedBox->getBox()->getOuterWidth(),
					'weight_kg' => (int)($packedBox->getWeight()) < 1 ? 1 : (int)($packedBox->getWeight()),
					'quantity' => 1,
				];

				$box_index++;
			}

		}
		rendr_logger()->add("Parcels calculated:");
		rendr_logger()->add($parcels);
		rendr_logger()->separator();
			return $parcels;

	}

	/**
	 * can_calculate_shipping function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param $package
	 *
	 * @return bool
	 * @throws \Exception
	 */
	private function can_calculate_shipping($package) {
		

		if(empty($package['destination'])) {
			throw new \Exception('Cannot calculate postage. destination empty.');
		}

		if(empty($package['destination']['city'])) {
			throw new \Exception('Cannot calculate postage. city empty.');
		}

		if(empty($package['destination']['state'])) {
			throw new \Exception('Cannot calculate postage. state empty.');
		}

		if(empty($package['destination']['postcode'])) {
			throw new \Exception('Cannot calculate postage. postcode empty.');
		}
    	
    	if(!empty($this->get_option('disable_products'))) {
			foreach($package['contents'] as $item) {
            	if($item['data']->get_type() == 'variation') {
                	if(in_array($item['variation_id'], $this->get_option('disable_products')) || in_array($item['data']->get_parent_id(), $this->get_option('disable_products'))) {
                    	throw new \Exception('Matched excluded shipping class');
                	}
            	} else {
                	if(in_array($item['product_id'], $this->get_option('disable_products'))) {
                    	throw new \Exception('Matched excluded shipping class');
                	}
            	}
        	}
		}
    	
    	if(!empty($this->get_option('disable_shipping_classes'))) {
			foreach($package['contents'] as $item) {

    			if(in_array($item['data']->get_shipping_class_id(), $this->get_option('disable_shipping_classes'))) {
    				throw new \Exception('Matched excluded shipping class');
    			}
			}
		}
		
		if(!empty($this->get_option('disable_categories'))) {
			foreach($package['contents'] as $item) {
				foreach($this->get_option('disable_categories') as $term_id) {
					if(in_array($item['product_id'], get_posts([
						'post_type' => 'product',
						'posts_per_page' => -1,
						'post_status' => 'any',
						'tax_query' => [
							[
								'terms' => [$term_id],
								'taxonomy' => 'product_cat',
								'field' => 'term_id',
								'include_children' => true,
							]
						],
						'fields' => 'ids',
					]))) {
						throw new \Exception('Matched excluded category');
					}
				}
			}
		}
		
		

		return true;

	}
	
	private function get_product_types() {
		
		$types = [];
		
		foreach($this->product_types as $product_type) {
			if($this->get_option('product_type_'.$product_type) == 'yes') {
				$types[$product_type] = true;
			} else {
				$types[$product_type] = false;
			}
		}
		
		return $types;
		
	}
	

	/**
	 * can_have_authority_to_leave function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @return bool
	 */
	public function can_have_authority_to_leave() {
		
		if(empty($this->get_option('authority_to_leave')) || $this->get_option('authority_to_leave') != 'yes') {
			return false;
		}
		
		// tobacco products enabled?
		if($this->get_product_types()['tobacco']) {
			return false;
		}
		
		return true;
		
	}

			/**
	 * get_package_rates_for_day function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	*
	* @param $package
	* @param false $day
	 *
	 * @return mixed
	* @throws \Exception
	*/
	private function get_package_rates_for_day($package, $day = false) {
		
		$body = [
			'ready_for_pickup_at' => $this->get_ready_pickup_date($day)->format('c'),
			'address' => [
				'city' => $package['destination']['city'],
				'state' => $package['destination']['state'],
				'post_code' => $package['destination']['postcode'],
				'address'   => $package['destination']['address_1'],
				'address_2'   => $package['destination']['address_2'],
			],
			'product_types' => $this->get_product_types(),
			'line_items' => $this->get_package_line_items($package),
			'parcels' => $this->get_package_parcels($package),
		];

		if(!empty($this->get_option('store_id'))) {
			$body['store_id'] = $this->get_option('store_id');
		}
		
		if($this->can_have_authority_to_leave()) {
			$body['authority_to_leave'] = true;
		} else {
			$body['authority_to_leave'] = false;
		}

		$request = wp_remote_post($this->get_endpoint('/deliveries/quote2'), [
			'headers' => [
				'Authorization' => 'Bearer '.$this->get_auth_token(),
				'Content-Type' => 'application/json',
			],
			'body' => json_encode($body),
			'timeout' => 10000,
		]);
		
		if(wp_remote_retrieve_response_code($request) != 200) {
			rendr_logger()->add("Invalid response received from package rates request.");
			rendr_logger()->add($request);
			rendr_logger()->add($body);
			rendr_logger()->separator();
			throw new \Exception(sprintf('Invalid response code when fetching available rates. [Response code: %s] [Response Message: %s]', wp_remote_retrieve_response_code($request), wp_remote_retrieve_response_message($request)));
		}

		$data = json_decode(wp_remote_retrieve_body($request), true);

		if(empty($data) || empty($data['data'])) {
			rendr_logger()->add("Empty data received from rendr when gfetting package rates.");
			rendr_logger()->add($request);
			rendr_logger()->add($body);
			rendr_logger()->separator();
			return [];
		} else {
			rendr_logger()->add("Rates for parcel:");
			rendr_logger()->add($data);
			rendr_logger()->add($body);
			rendr_logger()->separator();
		}

		return $data;

	}

	/**
	 * get_package_rates function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param $package
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function get_package_rates($package) {

		$data = $this->get_package_rates_for_day($package);
		$rates = [];

		if(!empty($data['data'])) {
			foreach($data['data'] as $delivery_type => $rate) {
				if($this->get_instance_option('disable_'.$delivery_type) == 'yes') {
					continue;
				}
				$rates[$delivery_type] = $rate;
				$rates[$delivery_type]['ready_for_pickup'] = isset($rate['from_datetime']) ? $rate['from_datetime'] : $this->get_ready_pickup_date()->format('c');
			}
		}

		return $rates;

	}

	public function request_delivery($order, $method_name, $method) {

		/** @var \WC_Order_Item_Shipping $method */

		/** @var \WC_Order $order */
		$package = array(
			'contents'        => [],
			'destination'     => array(
				'country'   => $order->get_shipping_country(),
				'state'     => $order->get_shipping_state(),
				'postcode'  => $order->get_shipping_postcode(),
				'city'      => $order->get_shipping_city(),
				'address'   => $order->get_shipping_address_1(),
				'address_2' => $order->get_shipping_address_2(),
			)
		);

		foreach($order->get_items() as $item) {
		/** @var \WC_Order_Item_Product $item */
			$package['contents'][] = [
				'data' => ($item->get_variation_id() > 0 ? wc_get_product($item->get_variation_id()) : wc_get_product($item->get_product_id())),
				'quantity' => (int)($item->get_quantity()),
				'line_total' => (($item->get_total()+$item->get_total_tax())/$item->get_quantity()),

			];
		}

		$type = '';
		if($method_name == $this->label_fast) {
			$type = 'fast';
		} else if($method_name == $this->label_flexible) {
			$type = 'flexible';
		} else {
			$type = 'standard';
		}

		try {
			
			// Request body
			$body = [
				'ready_for_pickup_at' => !empty($method->get_meta('ready_for_pickup')) ? $method->get_meta('ready_for_pickup') :  $this->get_ready_pickup_date()->format('c'),
				'delivery_type' => $type,
				'reference' => 'Order #'.$order->get_id(),
				'reference_origin' => 'woocommerce',
				'woocommerce_version' => WCRENDR_VERSION,
				'address' => [
					'business' => false,
					'address' => $order->get_shipping_address_1(),
					'city' => $order->get_shipping_city(),
					'state' => $order->get_shipping_state(),
					'post_code' => $order->get_shipping_postcode(),
				],
				'customer' => [
					'first_name' => $order->get_shipping_first_name(),
					'last_name' => $order->get_shipping_last_name(),
					'phone' => $order->get_billing_phone(),
					'email' => $order->get_billing_email(),
				],
				'line_items' => $this->get_package_line_items($package),
				'parcels' => $this->get_package_parcels($package),
				'product_types' => $this->get_product_types(),
			];
			
			// Only if our store id is set
			if(!empty($this->get_option('store_id'))) {
				$body['store_id'] = $this->get_option('store_id');
			}
		
			if(!empty($order->get_meta('wcrendr_atl')) && $order->get_meta('wcrendr_atl') == 'yes') {
				$body['authority_to_leave'] = true;
			} else {
				$body['authority_to_leave'] = false;
			}
			
			if(!empty($order->get_customer_note())) {
				$body['special_instructions'] = $order->get_customer_note();
			}
			
			if(!empty($order->get_shipping_address_2())) {
				$body['address']['address2'] = $order->get_shipping_address_2();
			}
			
			if($this->get_option('auto_book') == 'yes') {
				$body['book_delivery_now'] = true;
			}

			// Performs request
			$request = wp_remote_post($this->get_endpoint('/deliveries'), [
				'headers' => [
					'Authorization' => 'Bearer '.$this->get_auth_token(),
					'Content-Type' => 'application/json',
				],
				'body' => json_encode($body),
				'timeout' => 10000,
			]);
			
			rendr_logger()->add("Delivery requested:");
			rendr_logger()->add($body);

			$body = json_decode(wp_remote_retrieve_body($request), true);
			
			rendr_logger()->add($body);
			rendr_logger()->separator();

			if(!empty($body['data']['id'])) {
				update_post_meta($order->get_id(), 'rendr_delivery_id', $body['data']['id']);
				update_post_meta($order->get_id(), 'rendr_delivery_status', 'requested');
			}
			
			// If auto order -> add action to book it
			if($this->get_option('auto_book') == 'yes') {
				as_schedule_single_action(time()+30, 'rendr_request_auto_book', [$order->get_id(), $body['data']['id']]);
				//$rendr->book_delivery(get_post_meta($order->get_id(), 'rendr_delivery_id', true), $order);
			}			
		
		} catch(\Exception $e) {
			rendr_logger()->add("Unable to request delivery");
			rendr_logger()->add($e->getMessage());
			rendr_logger()->add($request);
			rendr_logger()->separator();
		}
		
	}

	public function fetch_delivery_status($order, $method) {
		$request = wp_remote_get($this->get_endpoint('/deliveries/'.get_post_meta($order->get_id(), 'rendr_delivery_id', true)), [
			'headers' => [
				'Authorization' => 'Bearer '.$this->get_auth_token(),
			]
]);

		try {
			$body = json_decode(wp_remote_retrieve_body($request), true);
			if(!empty($body['data'])) {
				if(!empty($body['data']['consignment_number'])) {
					update_post_meta($order->get_id(), 'rendr_delivery_ref', sanitize_text_field($body['data']['consignment_number']));
				}
			}
		} catch(\Exception $e) {
		}
		if(in_array(get_post_meta($order->get_id(), 'rendr_delivery_status', true), ['booked', 'in_transit', 'cancelled'])) {
			as_schedule_single_action((time()+600), 'wcrendr_fetch_delivery_status', [$order->get_id()]);
		}
	}
	
	public function book_delivery($ref_id, $order) {
		
	   $request = wp_remote_request($this->get_endpoint('/deliveries/'.$ref_id.'/book'), [
			'headers' => [
					'Authorization' => 'Bearer '.$this->get_auth_token(),
					'Content-Type' => 'application/json',
				],
				'method' => 'PATCH',
				'timeout' => 20000,
		]);
		update_post_meta($order->get_id(), 'rendr_delivery_status', 'booked');
		as_schedule_single_action(time(), 'wcrendr_fetch_delivery_status', [$order->get_id()]);
		
	}

	/**
	 * calculate_shipping function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param array $package
	 */
	public function calculate_shipping($package = array()) {

		$rates = [];

		try {
			
			$this->can_calculate_shipping($package);

			foreach($this->get_package_rates($package) as $type => $rate) {

				$attr = "label_{$type}";
				$label = $this->$attr;

				if(empty($label)) {
					$label = ucwords($type);
				}

				$_rate = [
					'id' => $this->get_rate_id().':'.$type,
					'label' => $label,
					'cost' => $rate['price_cents']/100,
					'package' => $package,
					'meta_data' => [
						'delivery_from' => $rate['from_datetime'],
						'delivery_to' => $rate['to_datetime'],
						'num_days' => $rate['num_days'],
						'type' => $type,
						'ready_for_pickup' => $rate['ready_for_pickup'],
					],
				];

				if($this->is_taxable()) {
					$_rate['taxes'] = \WC_Tax::calc_tax(($rate['price_cents']/100), \WC_Tax::get_shipping_tax_rates(), get_option('woocommerce_prices_include_tax') == 'yes');
					foreach($_rate['taxes'] as $taxrate) {
						$_rate['cost'] -= $taxrate;
					}
				}

				$rates[] = $_rate;
				
			}
			
		} catch(\Exception $e) {
			rendr_logger()->add('Exception when calculating shipping. '.$e->getMessage());
			rendr_logger()->add($package);
			rendr_logger()->separator();
		}
		foreach($rates as $r) {
			$this->add_rate($r);
		}
		
	}

	/**
	 * has_product_without_dimensions_or_weight function
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return mixed
	 */
	private function has_product_without_dimensions_or_weight() {

		if(get_transient('wcrendr_prods_with_no_dimensions')) {
			return get_transient('wcrendr_prods_with_no_dimensions');
		} else {
			if(count(get_posts([
				'post_type' => ['product', 'product_variation'],
				'posts_per_page' => -1,
				'fields' => 'ids',
				'post_status' => 'publish',
				'meta_query' => [
					'relation' => 'OR',
					[
						'key' => '_weight',
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => '_weight',
						'compare' => '=',
						'value' => '',
					],
					[
						'key' => '_length',
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => '_length',
						'compare' => '=',
						'value' => '',
					],
					[
						'key' => '_width',
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => '_width',
						'compare' => '=',
						'value' => '',
					],
					[
						'key' => '_height',
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => '_height',
						'compare' => '=',
						'value' => '',
					],
				]
			])) > 0) {
				set_transient('wcrendr_prods_with_no_dimensions', true, 0);
			} else {
				set_transient('wcrendr_prods_with_no_dimensions', false, 0);
			}
		}
	}
}