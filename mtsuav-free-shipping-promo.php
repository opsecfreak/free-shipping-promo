<?php
/**
 * Plugin Name:       MTSUAV Free Shipping Promotion
 * Description:       Offer free shipping over a set order amount. Auto-creates Free Shipping methods, shows a progress bar on cart and checkout, and supports category rules and sale-item exclusions.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            MTSUAV
 * Author URI:        https://mtsuav.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mtsuav-free-shipping-promo
 * Update URI:        https://github.com/opsecfreak/mtsuav-free-shipping-promo
 * WC requires at least: 8.0
 * WC tested up to:   9.0
 *
 * @package MTSUAV_Free_Shipping_Promo
 */

defined( 'ABSPATH' ) || exit;

define( 'MTSUAV_FSP_VERSION', '1.0.0' );
define( 'MTSUAV_FSP_FILE', __FILE__ );
define( 'MTSUAV_FSP_PATH', plugin_dir_path( __FILE__ ) );
define( 'MTSUAV_FSP_OPTION', 'mtsuav_fsp_settings' );

require_once MTSUAV_FSP_PATH . 'includes/class-mtsuav-updater.php';
require_once MTSUAV_FSP_PATH . 'includes/class-mtsuav-tip-box.php';

MTSUAV_Updater::register( 'mtsuav-free-shipping-promo', 'opsecfreak/mtsuav-free-shipping-promo', MTSUAV_FSP_VERSION, __FILE__ );
mtsuav_tip_box_init();

/**
 * Declare HPOS compatibility before WooCommerce initializes.
 *
 * @return void
 */
function mtsuav_fsp_declare_hpos_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'mtsuav_fsp_declare_hpos_compatibility' );

/**
 * Load translations.
 *
 * @return void
 */
function mtsuav_fsp_load_textdomain() {
	load_plugin_textdomain( 'mtsuav-free-shipping-promo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'mtsuav_fsp_load_textdomain' );

/**
 * Boot the plugin once WooCommerce is guaranteed to be loaded.
 * If WooCommerce is missing, show an admin notice and stop.
 *
 * @return void
 */
function mtsuav_fsp_boot() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'mtsuav_fsp_missing_wc_notice' );
		return;
	}

	require_once MTSUAV_FSP_PATH . 'includes/class-mtsuav-fsp-core.php';
	require_once MTSUAV_FSP_PATH . 'includes/class-mtsuav-fsp-methods.php';
	require_once MTSUAV_FSP_PATH . 'includes/class-mtsuav-fsp-admin.php';

	mtsuav_fsp_init();
}
add_action( 'plugins_loaded', 'mtsuav_fsp_boot', 20 );

/**
 * Admin notice shown when WooCommerce is not active.
 *
 * @return void
 */
function mtsuav_fsp_missing_wc_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>'
		. esc_html__( 'MTSUAV Free Shipping Promotion requires WooCommerce to be installed and active.', 'mtsuav-free-shipping-promo' )
		. '</p></div>';
}
