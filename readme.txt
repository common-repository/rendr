=== Rendr ===
Contributors: salumguilherme,rendrdelivery
Tags: woocommerce,shipping,rendr
Requires at least: 5.0
Tested up to: 6.6.1
Requires PHP: 5.6
Stable tag: 1.4.2
License: GPL v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Rendr shipping method for WooCommerce.

== Description ==
= About Rendr Delivery Platform =

Enhance your delivery offering, while growing your brand with Rendr!

Offer your consumers the ability to get their favourite products, delivered directly to their doorstep when it suits.

The Rendr Wordpress Plugin requires a valid and active Rendr Delivery account. To use this plugin you must sign up at https://rendr.delivery

= Offer Choice & Flexibility to Your Customers =

Offer our 3 delivery options on checkout to provide your customers with outstanding choice & flexibility

* FAST - Delivered ASAP
* FLEXIBLE - Delivered Same Day
* STANDARD - Delivered Overnight/Interstate

= Key Features: =

Customers are presented with delivery options in real time depending on their location and the products in their cart.

By focusing on industry leading last mile technology, Rendr’s solution intelligently locates the closest driver to your store/distribution centre who has the most suitable vehicle to fulfill your customers delivery.

Be confident your customers will always know where their order is, through our transparent order tracking features. We’ll notify your customer from store to door via SMS and email the status of their order.

Offer Nationwide Delivery to Your Customers no matter where they live. This includes the most rural areas of the country!
Ensure your deliveries are secure with our network of 20,000 professional carriers who are experienced in both express & long haul services.

Ability to dispatch from multiple locations

= We Provide a Dedicated Team here to help! =

Highly responsive customer support team trained to help you succeed

At Rendr, we hold customer service to a high standard, making the experience a reliable and convenient one for both our merchants and customers

Our Support Team is available 7 days a week, Mon-Fri 9am-5pm, Sat & Sun 7am-1pm

== Installation ==
Upload plugin to your wordpress site and activate. Navigate to WooCommerce Shipping Settings and navigate to the \"Rendr Settings\" tab.

For the shipping method to be made available you will need to enter a set of valid Rendr API credentials. Test your credentials by clicking Verify Credentials. Click Save to save your credentials. You can now add the Rendr delivery shipping option to your Shipping Zones in WooCommerce.

For a detailed guide on the plugin options and more visit https://info.rendr.delivery/woocommerce

== Frequently Asked Questions ==
= What is Rendr =

Rendr is a last mile delivery platform offering consumers the ability to have all their favourite products delivered ASAP, same day and even overnight or interstate.

Our driver network consists of 20,000+ drivers nationwide to ensure consumer needs are met, with operation available in all states and territories across Australia.

= How does it work? =

Once your customer has added products to your cart, they will see a Rendr Delivery options available at checkout for applicable products. Rendr will also display accurate delivery fees based on your customers location and the products in their cart

= How do I book the delivery? =

To book the delivery, you will need to navigate to your brand’s Merchant Portal by clicking the link on the order. Once in the portal, click ‘Book Delivery’. Then you will need to print the label and include it on the package for the courier to collect.

= I need to contact Rendr Support. How do I do this? =

Our support team is available 7 days a week to deal with a range of issues from both users and merchants. Visit support.rendr.delivery or call Rendr on 1300 697 363.

= Other info =

Our plugin utilises the third party Rendr Delivery API to retrieve shipping rates based on customers cart items. Customer cart information including but not limited to products and delivery address is sent to Rendr on the API endpoints https://uat.api.rendr.delivery/ and https://api.rendr.delivery/

For our privacy policy visit https://www.rendr.delivery/privacy-policy
For our terms & conditions visit https://www.rendr.delivery/our-terms

== Changelog ==

= 1.4.2 =
* Properly escaped translatable strings
* Updated lightbox JS library
* Updated Input Mask jQuery Library

= 1.4.1 =
* Tested with latest versions of WooCommerce (9.2.2) and Wordpress (6.6.1)

= 1.4.0 =
* New: Added UAT authentication mode
* New: Added Authority to Leave setting in the admin for eligible orders
* New: Added product types for delivery of certain sensitive products
* New: Autobook feature - will automatically book request deliveries with Rendr once orders are placed
* Tweak: Include full delivery address when requesting delivery quote
* Tweak: Include address line 2 in rendr deliveries
* Tweak: Store ID no longer required to authenticate rendr API and request delviery quotes

= 1.3.3 =
* Fix: Ensures quantity is sent as integer. Some instances were sending string causing API to throw error.

= 1.3.0 =
* Multisite compatibility

= 1.2.9 =
* Updated terms and conditions link not included in previous update. We recommend updating your plugin to the latest version to ensure all shop customers have access to the rendr terms and conditions shown when checking out

= 1.2.8 =
* Updated terms and conditions link
* Updated ready for pick up tim to match first available delivery present at API response

= 1.2.7 =
* Updated delivered by date for standard rate

= 1.2.6 =
* Updated API endpoint to use optimised quote retrieval.

= 1.2.5 =
* Updated Rendr brand placement settings and front end

= 1.2.4 =
* NEW: Added debug logger for better insight into possible issues.

= 1.2.3 =
* FIX: Issue when items were configured to be sent as a single package no rates would be returned.

= 1.2.1 =
* NEW: Exclude Rendr as a delivery method based on product category, shipping class or individual products in the customers cart.

= 1.1.9.1 =
* Initial public release