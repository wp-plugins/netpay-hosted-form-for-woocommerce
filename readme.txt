=== Plugin Name ===
Netpay Payment Gateway For WooCommerce
Contributors: NetPay
Donate link: 
Tags: woocommerce netpay, netpay.co.uk, payment gateway, woocommerce, woocommerce payment gateway
Requires at least: 3.0.1
Tested up to: 4.2.1
Stable tag: 1.0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This Payment Gateway For WooCommerce extends the functionality of WooCommerce to accept payments from credit/debit cards using netpay.co.uk Gateway

== Description ==

<h3>NetPay Hosted Payment Form for WooCommerce</h3> makes your website ready to use NetPay Hosted Form Method to accept credit/debit cards on your ecommerce store in safe way. 

NetPay is most widely used payment gateway to process payments online and accepts Visa, MasterCard, Discover and other variants of cards.

With NetPay's Hosted Form Method the payer will enter the card information on NetPay’s Secure Payment page. On checkout page the user will be redirected to NetPay’s Secure Server. NetPay Hosted Form also supports 3D Secure Card Validation and when payment completed successfully, the user will redirect back to your ecommerce web site.

NetPay Hosted Form is fully customizable. The Merchant can able to upload the logo and also can create its own interface colours or can select one of pre-created templates.

All credit/debit cards are processed and transmitted on NetPay’s Secure Server, ensuring maximum security and reducing the risk of card fraud.

Merchant does not need to obtain SSL Certificate and PCI DSS Certification.

= Features =
Few features of this plugin:

1. No SSL Certificate required
2. No PCI Certification required
3. Easy to install and configure
4. Safe way to process credit/debit cards on WooCommerce using NetPay Hosted Form
5. This plugin use hosted solution provided by NetPay and payment is processed on secured servers of NetPay
6. Ability to choose STANDARD or SWIFT payment template
7. Server side communication with NetPay Payment Gateway (requires cURL) 

== Installation ==
Easy steps to install the plugin:

1. Requires cURL. Enable cURL if it is not enabled.
2. Requires Mcrypt library. If it is not enabled or installed, install Mcrypt library and enable.
3. Upload `netpay-payment-gateway-for-woocommerce` folder/directory to the `/wp-content/plugins/` directory.
4. Activate the plugin through the 'Plugins' menu in WordPress.
5. Go to WooCommerce => Settings
6. On the "Settings" page, select "Payment Gateways" tab.
7. Under "Payment Gateways" you will find all the available gateways, select "NetPay Hosted Form" option
8. On this page you will find options to configure the plugin for use with WooCommerce
9. Enter the integration details (Merchant Id, Merchant Login, Password etc)


== Frequently Asked Questions ==
= Is SSL Certificate required to use this plugin? =
* SSL Certificate is not required. The user will enter card information on NetPay's Hosted Form. NetPay take care of all card handling and processing security.
= Is PCI DSS Certification required to use this plugin? =
* PCI DSS Certification is not required. 
= What version of Wordpress and Woocommerce does this work with? =
* Tested with Wordpress 3.8.2 through to 4.1 , WooCommerce version 2.0.20 through to 2.2.10

== Screenshots ==
1. assets/netpay_logo.jpg
2. assets/checkout.jpg
3. assets/woocommerce_settings_hosted.jpg
4. assets/pluging_configuration_hosted.jpg

== Changelog ==
= 1.0.5 =
* Tested with WordParess 4.1.1 and WooCommerce 2.3.7
* Upraged to use NetPay SPM (Server Post Method) 
* Option added to choose STANDARD or SWIFT payment template
* It requires cURL to communicate with NetPay Payment Gateway

= 1.0.4 =
* Fixes item price rounding

= 1.0.3 =
* Tested with WP4.1 and Woocommerce 2.2.10

= 1.0.2 =
* Use billing address as default if shipping is missing
* Stricter string checks
* Fixes missing price on proceed to checkout vs pay
* Fixed redirect issue
* Fixed issue with invalid URL

= 1.0.1 =
* Updated for Woocommerce 2.1 and higher

= 1.0 =
* First Version

== Upgrade Notice ==
* Upgrade to 1.0.2 for full functionality

== Arbitrary section ==