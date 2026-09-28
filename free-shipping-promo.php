<?php
/**
 * Plugin Name:       Free Shipping Promo for WooCommerce
 * Description:       Offer free shipping over a set order amount. Auto-creates Free Shipping methods, shows a progress bar on cart and checkout, and supports category rules and sale-item exclusions.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            MTSUAV
 * Author URI:        https://mtsuav.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       free-shipping-promo
 * Update URI:        https://github.com/opsecfreak/free-shipping-promo
 * WC requires at least: 8.0
 * WC tested up to:   9.0
 *
 * @package FSP
 */

defined( 'ABSPATH' ) || exit;

define( 'FSP_VERSION', '1.0.0' );
define( 'FSP_FILE', __FILE__ );
define( 'FSP_PATH', plugin_dir_path( __FILE__ ) );
define( 'FSP_OPTION', 'fsp_settings' );

require_once FSP_PATH . 'includes/class-mtsuav-updater.php';
require_once FSP_PATH . 'includes/class-mtsuav-tip-box.php';

MTSUAV_Updater::register( 'free-shipping-promo', 'opsecfreak/free-shipping-promo', FSP_VERSION, __FILE__ );
mtsuav_tip_box_init();

/**
 * Declare HPOS compatibility before WooCommerce initializes.
 *
 * @return void
 */
function fsp_declare_hpos_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'fsp_declare_hpos_compatibility' );

/**
 * Load translations.
 *
 * @return void
 */
function fsp_load_textdomain() {
	load_plugin_textdomain( 'free-shipping-promo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'fsp_load_textdomain' );

/**
 * Boot the plugin once WooCommerce is guaranteed to be loaded.
 * If WooCommerce is missing, show an admin notice and stop.
 *
 * @return void
 */
function fsp_boot() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'fsp_missing_wc_notice' );
		return;
	}

	require_once FSP_PATH . 'includes/class-fsp-core.php';
	require_once FSP_PATH . 'includes/class-fsp-methods.php';
	require_once FSP_PATH . 'includes/class-fsp-admin.php';

	fsp_init();
}
add_action( 'plugins_loaded', 'fsp_boot', 20 );

/**
 * Admin notice shown when WooCommerce is not active.
 *
 * @return void
 */
function fsp_missing_wc_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>'
		. esc_html__( 'Free Shipping Promo for WooCommerce requires WooCommerce to be installed and active.', 'free-shipping-promo' )
		. '</p></div>';
}
