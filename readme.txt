=== Pepperjam Pixel ===
Contributors: pepperjam2016, dbright52
Tags: WooCommerce, Pepperjam, Pixel, Woo, Commerce
Requires at least: 3.0.1
Tested up to:  6.3.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Official Pepperjam Plugin.  Extends the WooCommerce platform with a tracking pixel for Pepperjam.

== Description ==

Pepperjam's Ascend™ plugin creates a small HTML snippet placed on the WooCommerce order confirmation/"thank you" page after
a successful transaction.  The HTML snippet sends the information into the dynamic commissioning system.

To use Pepperjam's Ascend™ plugin, ensure WooCommerce is installed and up to date.

**About Pepperjam**
Pepperjam’s cloud-based Ascend™ affiliate lifecycle technology platform delivers the growth marketers need. Ascend™ is the only platform that delivers a fully integrated, comprehensive suite of discovery, recruitment, optimization, payment and brand safety capabilities for marketers seeking a high-quality, scalable subsidy to their primary sales and marketing channels.

Supported by best-in-class service including the category’s only in-sourcing support program, with Pepperjam Ascend™, you are in control of the entire affiliate partnership marketing lifecycle—on a single platform. We do what no other traditional affiliate network can: We provide a combination of technology and expertise to deliver better results. We elevate your affiliate program to become the most effective versions of themselves. Learn more at [www.Pepperjam.com](www.Pepperjam.com).


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the WooCommerce->Settings->Integration->Pepperjam Pixel screen to configure the plugin.
4. Enter the 'Program ID' as provided by your Pepperjam Account manager.
5. Select the 'Integration Type' as provided by your Pepperjam Account manager.
6. Select the 'Tracking Url' as provided by your Pepperjam Account manager.
7. Set the lookback period as provided by your Pepperjam Account manager.
8. (optional) Set the 'Program Implementation Date'.  If you are unsure of what date to use, consult with your Pepperjam Account manager.

== Changelog ==

= 1.1 =
* Add tag container support

= 1.0.9 =
* Added first party cookie support for Safari ITP 2.0

= 1.0.8 =
* Compatibility fix to apply fix introduced in 1.0.7 to the coupon type 'percent' which is used in lieu of 'percent_product' in WC 3.2

= 1.0.7 =
* Fixed bug that caused discounts limited to x items to be incorrectly reported in item price

= 1.0.6 =
* Fixed bug that caused cart percent discounts to be double counted in WooCommerce 3.x versions.

= 1.0.5 =
* Updated readme.txt to reflect tested up to WordPress 4.8.

= 1.0.4rc =
* Initial Public Release.
