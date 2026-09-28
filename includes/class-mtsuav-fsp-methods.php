<?php
/**
 * Automatic Free Shipping method management for MTSUAV Free Shipping Promotion.
 *
 * When the "Manage Free Shipping methods automatically" setting is on, saving
 * settings ensures every shipping zone has a Free Shipping method whose
 * minimum order amount equals the threshold: created when missing, updated
 * when present. Pre-existing methods that require a coupon are left alone.
 *
 * @package MTSUAV_Free_Shipping_Promo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sync Free Shipping methods across all zones to the given threshold.
 *
 * @param float $threshold Free shipping threshold.
 * @return int[] List of newly created instance IDs, keyed by zone ID.
 */
function mtsuav_fsp_sync_free_shipping_methods( $threshold ) {
	if ( ! class_exists( 'WC_Shipping_Zones' ) || ! class_exists( 'WC_Shipping_Zone' ) ) {
		return array();
	}
	$threshold = max( 0.0, (float) $threshold );
	$created   = array();

	$zones   = array();
	$zones[] = new WC_Shipping_Zone( 0 ); // "Rest of the world" zone.
	foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
		if ( isset( $zone_data['zone_id'] ) ) {
			$zones[] = new WC_Shipping_Zone( (int) $zone_data['zone_id'] );
		}
	}

	foreach ( $zones as $zone ) {
		$found = false;
		foreach ( $zone->get_shipping_methods() as $instance_id => $method ) {
			if ( ! is_object( $method ) || 'free_shipping' !== $method->id ) {
				continue;
			}
			$found    = true;
			$requires = $method->get_option( 'requires' );
			if ( in_array( $requires, array( '', 'min_amount' ), true ) ) {
				$method->update_option( 'requires', 'min_amount' );
				$method->update_option( 'min_amount', (string) $threshold );
			} elseif ( in_array( $requires, array( 'either', 'both' ), true ) ) {
				// Coupon-based methods that also honor a minimum amount.
				$method->update_option( 'min_amount', (string) $threshold );
			}
			// requires === 'coupon': leave untouched.
		}

		if ( ! $found ) {
			$instance_id = $zone->add_shipping_method( 'free_shipping' );
			if ( $instance_id ) {
				$method = WC_Shipping_Zones::get_shipping_method( (int) $instance_id );
				if ( $method && is_object( $method ) ) {
					$method->update_option( 'title', __( 'Free Shipping', 'mtsuav-free-shipping-promo' ) );
					$method->update_option( 'requires', 'min_amount' );
					$method->update_option( 'min_amount', (string) $threshold );
					$created[ (int) $zone->get_id() ] = (int) $instance_id;
				}
			}
		}
	}

	if ( ! empty( $created ) ) {
		$settings  = mtsuav_fsp_get_settings();
		$existing  = isset( $settings['auto_created_instances'] ) && is_array( $settings['auto_created_instances'] )
			? $settings['auto_created_instances']
			: array();
		foreach ( $created as $zone_id => $instance_id ) {
			$existing[] = array(
				'zone_id'     => $zone_id,
				'instance_id' => $instance_id,
			);
		}
		$settings['auto_created_instances'] = array_values( $existing );
		update_option( MTSUAV_FSP_OPTION, $settings );
	}

	return $created;
}
