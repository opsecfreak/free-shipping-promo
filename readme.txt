=== MTSUAV Free Shipping Promotion ===
Contributors: mobiletechspecialists
Tags: woocommerce, free shipping, shipping promotion, cart, checkout
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
WC requires at least: 8.0
WC tested up to: 9.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Offer free shipping on orders over a set amount, with a progress bar that nudges shoppers to the threshold.

== Description ==

MTSUAV Free Shipping Promotion makes "free shipping over $X" effortless:

* Set a threshold in your shop currency and turn the promotion on.
* One click syncs every shipping zone with a Free Shipping method set to your threshold. The plugin creates the method where it is missing and updates it where it exists. Methods that require a coupon are never touched.
* A customizable progress bar on the cart and checkout pages shows shoppers how close they are, with separate messages for progress and for unlocking free shipping.
* Qualification rules: measure the subtotal before or after discounts, exclude sale items, limit to (or exclude) product categories, and optionally count estimated taxes and shipping.
* Shortcode `[mtsuav_fsp_bar]` renders the same bar anywhere: pages, posts, or widgets.
* HPOS compatible. No conflicts with coupons. Works with taxes on or off.

Settings live under WooCommerce > Settings > Free Shipping Promo. No license key, no account, no upsells.

== Installation ==

1. Make sure WooCommerce is installed and active.
2. Upload the plugin folder to `/wp-content/plugins/` or install the zip through Plugins > Add New > Upload Plugin.
3. Activate the plugin.
4. Go to WooCommerce > Settings > Free Shipping Promo, set your threshold, and save. With automatic method management on, your Free Shipping methods are created on that first save.

== Frequently Asked Questions ==

= Does it work without WooCommerce? =
No. WooCommerce must be installed and active. If it is missing, the plugin shows an admin notice and does nothing else.

= Will it conflict with my coupons? =
No. Coupon discounts are reflected in the qualifying total when you use the "After discounts" basis, and free-shipping coupons keep working as before.

= What happens to my existing Free Shipping methods? =
If a zone already has a Free Shipping method, the plugin updates its minimum order amount to your threshold. If the method requires a coupon, it is left alone. Methods are only touched when automatic management is enabled and you save the settings.

= Does the progress bar update when the cart changes? =
Yes. On the cart page the bar refreshes with WooCommerce AJAX cart updates.

= How do updates work? =
The plugin checks the public GitHub releases page for this plugin about twice a day and offers one-click updates from there. See the Privacy section below.

== Screenshots ==

1. Settings page under WooCommerce > Settings > Free Shipping Promo.
2. Progress bar on the cart page with the default styling.
3. Unlocked state when the cart qualifies for free shipping.

== Changelog ==

= 1.0.0 =
* Initial release.

== Privacy ==

This plugin does not collect, store, or transmit any personal data.

Automatic updates: the plugin polls the public GitHub releases API (api.github.com) for the repository opsecfreak/mtsuav-free-shipping-promo, cached for 12 hours (1 hour after a failure), to learn whether a newer version is available. The request carries a generic updater user-agent (MTSUAV-Updater plus your WordPress version). No site URL, license keys, emails, or other personal data are sent. If the check fails, nothing happens and no update is offered.
