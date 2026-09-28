<?php
/**
 * Uninstall handler for Free Shipping Promo for WooCommerce.
 *
 * Removes the plugin settings option. The auto-created Free Shipping methods
 * are only deleted when the "Delete auto-created methods on uninstall"
 * setting is enabled; otherwise they are left in place.
 *
 * Note: uninstall.php runs without plugins loaded, so WooCommerce classes
 * are unavailable here. Method cleanup uses direct database access.
 *
 * @package FSP
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'fsp_settings', array() );

if ( is_array( $settings )
	&& isset( $settings['remove_methods_on_uninstall'] )
	&& 'yes' === $settings['remove_methods_on_uninstall']
	&& ! empty( $settings['auto_created_instances'] )
	&& is_array( $settings['auto_created_instances'] ) ) {

	global $wpdb;

	foreach ( $settings['auto_created_instances'] as $row ) {
		$instance_id = isset( $row['instance_id'] ) ? absint( $row['instance_id'] ) : 0;
		if ( $instance_id <= 0 ) {
			continue;
		}
		// Remove the method row from the zone methods table.
		$wpdb->delete(
			$wpdb->prefix . 'woocommerce_shipping_zone_methods',
			array( 'instance_id' => $instance_id ),
			array( '%d' )
		);
		// Remove the method's instance settings option.
		delete_option( 'woocommerce_free_shipping_' . $instance_id . '_settings' );
	}
}

delete_option( 'fsp_settings' );
