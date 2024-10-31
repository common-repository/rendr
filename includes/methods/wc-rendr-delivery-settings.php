<?php

	if (!defined('ABSPATH') || !defined('WPINC')) {
		exit;
	}

	return [
		'creds_title' => [
			'title' => 'Rendr Credentials & Settings',
			'type' => 'title',
		],
		'brand_id' => [
			'type' => 'text',
			'title' => __('Brand ID', 'rendr'),
			'description' => __('Your Brand ID. This value is preconfigured by your Rendr implementation partner.', 'rendr'),
			'default' => '',
			'desc_tip' => true,
		],
		'store_id' => [
			'type' => 'text',
			'title' => __('Store ID', 'rendr'),
			'description' => __('Your Store ID. This value is preconfigured by your Rendr implementation partner.', 'rendr'),
			'default' => '',
			'desc_tip' => true,
		],
		'client_id' => [
			'title' => __('Client ID', 'rendr'),
			'type' => 'password',
			'description' => 'Your Rendr Client ID',
			'default' => '',
			'desc_tip' => true,
		],
		'client_secret' => [
			'title' => __('Client Secret', 'rendr'),
			'type' => 'password',
			'description' => 'Your Rendr Client Secret',
			'default' => '',
			'desc_tip' => true,
		],
		'validate' => [
			'title' => 'Test Credentials',
			'type' => 'button',
			'id' => 'wcrendr-test-creds',
		],
		'enable_uat' => [
			'label' => __('Enable UAT Mode', 'rendr'),
			'type' => 'checkbox',
			'description' => 'Enable this setting to use the Rendr UAT endpoint.',
			'default' => 'no',
		],
		/*'openings' => [
			'title' => 'Order Pickup Time Availability',
			'type' => 'title',
			'description' => 'Select the days and hours when Rendr Delivery partners can pickup orders ready for delivery. <br>This information is used to calculate the earliest available delivery options given to your customers.',
			'css' => 'display: none;',
		],*/
		'opening_hours' => [
			'type' => 'opening_hours',
		],
		'blocked_dates' => [
			'type' => 'text',
			'title' => 'Blocked Dates',
			'description' => 'Set specific dates to override the table above where your store will be unavailable for order pick up by the Rendr Delivery partners (i.e Public Holidays)',
			'desc_tip' => true,
			'default' => '',
			'css' => 'display: none;',
		],
		/*'handling_time_title' => [
			'type' => 'title',
			'title' => 'Handling Time',
			'description' => 'The handling time is used in conjunction with your opening hours to calculate the earliest time an order would be ready for pick up once it has been placed.',
			'css' => 'display: none;',
		],*/
		'handling_time_days' => [
			'type' => 'number',
			'title' => 'Days',
			'default' => '',
			'css' => 'max-width: 80px;',
			'custom_attributes' => [
				'min' => 0,
			],
		],
		'handling_time_hours' => [
			'type' => 'number',
			'title' => 'Hours',
			'default' => '',
			'css' => 'max-width: 80px; display: none;',
			'custom_attributes' => [
				'min' => 0,
				'max' => 23,
			],
		],
		'auto_book_title' => [
			'type' => 'title',
			'title' => 'Auto delivery booking',
		],
		'auto_book' => [
			'label' => __('Automatically book Rendr deliveries when orders are placed', 'rendr'),
			'type' => 'checkbox',
			'description' => 'When checked, Rendr deliveries will be automatically booked for orders placed using Rendr as the delivery method. If unchecked, store managers will need to manually book rendr deliveries via the orders page when orders are placed.',
			'default' => 'no',
		],
		'packing_presets_title' => [
			'type' => 'title',
			'title' => 'Order Packing and Shipping Preferences',
			'description' => 'The settings below affect how many shipping labels you receive from Rendr and how many parcels our delivery partners expect to pickup for each given order.',
		],
		'authority_to_leave' => [
			'title' => __('Authority to leave', 'rendr'),
			'label' => __('Enable authority to leave for Rendr deliveries', 'rendr'),
			'type' => 'checkbox',
			'default' => 'no',
		],
		'packing_preference' => [
			'type' => 'select',
			'title' => 'Order Packing',
			'options' => [
				'together' => 'Orders are packed into a single parcel/container',
				'separate' => 'Order items are packed and shipped separately into their own parcel/container',
				'preset' => 'Order items are packed together to fit a set of one or more containers predefined below:',
			],
			'description' => 'Set how orders are usually packed for shipping. This will affect how many shipping labels you receive from Rendr. When selecting a predefined set of containers the plugin will automatically attempt to fit the order items into the given set of containers.',
			'default' => 'preset',
		],
		'packing_presets' => [
			'type' => 'pack_presets',
			'default' => '[{"label":"Small Box","length":"17","width":"17","height":"17"},{"label":"Medium Box","length":"35","width":"22","height":"19"},{"label":"Large Box","length":"35","width":"37","height":"19"},{"label":"XLarge Box","length":"43","width":"38","height":"30"},{"label":"XXLarge Box","length":"46","width":"41","height":"42"}]',
		],
		'default_dimension_title' => [
			'type' => 'title',
			'title' => 'Default Product Dimensions & Weight',
			'description' => ($this->has_product_without_dimensions_or_weight() ? '<div class="notice notice-error"><p>Looks like some of your inventory does not have dimensions and weight attributes set. We highly recommend fixing those or using default values below. Orders with items that do not have their weight and dimension attributes set, cannot be sent via Rendr unless you have entered default dimensions and weight values below.</p></div>' : '').'In order to retrieve an accurate quote and delivery time all products require their dimension and weight attributes set.<br>
In case not all products have their dimension and attributes set you can define a fallback set of dimensions and weight to be used.<br>
However, we recommend setting the dimension and weight on a product level as you may incur additional charges or delays if a product does not fit the dimensions/weight specified it .',
		],
		'default_width' => [
			'type' => 'number',
			'title' => 'Default Width (cm)',
			'default' => '50',
		],
		'default_length' => [
			'type' => 'number',
			'title' => 'Default Length (cm)',
			'default' => '50',
		],
		'default_height' => [
			'type' => 'number',
			'title' => 'Default Height (cm)',
			'default' => '50',
		],
		'default_weight' => [
			'type' => 'number',
			'title' => 'Default Weight (kg)',
			'default' => '5',
			'css' => 'margin-bottom:  40px;',
		],
		'product_types_title' => [
			'type' => 'title',
			'title' => 'Specific Product Types',
			'description' => 'Please check if any products you sell fit within the following categories and select them accordingly.',
		],
		'product_type_tobacco' => [
			'type' => 'checkbox',
			'label' => 'Tobacco products',
			'default' => 'no',
		],
		'product_type_alcohol' => [
			'type' => 'checkbox',
			'label' => 'Alcoholic products',
			'default' => 'no',
		],
		'product_type_high_value' => [
			'type' => 'checkbox',
			'label' => 'High value products',
			'default' => 'no',
		],
		'product_type_secure_documents' => [
			'type' => 'checkbox',
			'label' => 'Secure documents',
			'default' => 'no',
		],
		'product_type_prescription_meds_s4' => [
			'type' => 'checkbox',
			'label' => 'Schedule 4 Prescription Medications',
			'default' => 'no',
		],
		'product_type_prescription_meds_s2' => [
			'type' => 'checkbox',
			'label' => 'Schedule 2 Prescription Medications',
			'default' => 'no',
		],
		'product_type_prescription_meds_s8' => [
			'type' => 'checkbox',
			'label' => 'Schedule 8 Prescription Medications',
			'default' => 'no',
		],
		'disable_heading' => [
    		'type' => 'title',
    		'title' => 'Rendr Delivery Exclusions',
    		'description' => 'Do NOT offer Rendr as a delivery method whenever the customer has one or more products matching at least one of the criteria below:',
		],
		'disable_categories' => [
    		'type' => 'multiselect',
    		'title' => 'in Categories',
    		'options' => $this->get_category_select_options(),
		],
		'disable_shipping_classes' => [
    		'type' => 'multiselect',
    		'title' => 'with Shipping Class',
    		'options' => wp_list_pluck(get_terms( array('taxonomy' => 'product_shipping_class', 'hide_empty' => false ) ), 'name', 'term_id')
		],
		'disable_products' => [
    		'type' => 'multiselect',
    		'title' => 'is Product',
    		'options' => $this->get_product_select_options(),
		],
		'disable_brand' => [
			'type' => 'checkbox',
			'label' => 'Do not display "Delivery On Demand by Rendr" on my product and cart page.',
			'default' => 'yes',
		],
		'disable_banner' => [
			'type' => 'checkbox',
			'label' => 'Disable Rendr banner at the bottom of my website.',
			'default' => 'no',
		],
		'banner_theme' => [
			'type' => 'select',
			'title' => 'Footer Banner Colour Theme',
			'options' => [
				'teal' => 'Teal',
				'lightTeal' => 'Light Teal',
				'lightPink' => 'Light Pink',
				'white' => 'White',
				'darkBlue' => 'Dark Blue',
			],
			'default' => 'teal',
		],
		'disable_cart_brand' => [
			'type' => 'checkbox',
			'label' => 'Do not display Delivery On Demand by Rendr on my Cart page',
			'default' => 'no',
		],
		'cart_brand_theme' => [
			'type' => 'select',
			'title' => 'Delivery On Demand by Rendr Theme',
			'options' => [
				'teal' => 'Teal',
				'lightTeal' => 'Light Teal',
				'white' => 'White',
			],
			'default' => 'teal',
		],
		'enable_debug' => [
			'type' => 'checkbox',
			'label' => 'Enable Rendr debug mode. You can view the rendr logs <a href="'.admin_url('admin.php?page=wc-status&tab=logs').'" target="_blank">here -></a>',
			'default' => '',
		],
	];