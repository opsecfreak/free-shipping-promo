<?php
/**
 * Core logic for MTSUAV Free Shipping Promotion: settings storage,
 * qualifying-subtotal calculation, progress bar rendering, shortcode,
 * and frontend hooks.
 *
 * @package MTSUAV_Free_Shipping_Promo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default settings.
 *
 * @return array
 */
function mtsuav_fsp_default_settings() {
	return array(
		'enabled'                    => 'yes',
		'threshold'                  => '75',
		'manage_methods'             => 'yes',
		'cart_bar'                   => 'yes',
		'cart_position'              => 'before_table',
		'checkout_bar'               => 'yes',
		'message_progress'           => 'You are {remaining} away from FREE shipping!',
		'message_unlocked'           => 'You have unlocked FREE shipping on this order!',
		'bar_fill'                   => '#2271b1',
		'bar_bg'                     => '#e5e5e5',
		'bar_text'                   => '#1d2327',
		'bar_height'                 => '14',
		'bar_radius'                 => '7',
		'subtotal_basis'             => 'after_discounts',
		'exclude_sale_items'         => 'no',
		'include_categories'         => array(),
		'exclude_categories'         => array(),
		'count_taxes_shipping'       => 'no',
		'remove_methods_on_uninstall' => 'no',
		'auto_created_instances'     => array(),
	);
}

/**
 * Get the current settings merged over defaults.
 *
 * @return array
 */
function mtsuav_fsp_get_settings() {
	$saved = get_option( MTSUAV_FSP_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, mtsuav_fsp_default_settings() );
}

/**
 * Sanitize a list of term IDs.
 *
 * @param mixed $value Raw value.
 * @return int[]
 */
function mtsuav_fsp_sanitize_id_list( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}
	$ids = array();
	foreach ( $value as $id ) {
		$id = absint( $id );
		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}
	return array_values( array_unique( $ids ) );
}

/**
 * Sanitize the full settings array from a form submission.
 * Checkboxes are derived from presence, everything else is whitelisted.
 *
 * @param array $raw Raw submitted values (already unslashed).
 * @return array
 */
function mtsuav_fsp_sanitize_settings( $raw ) {
	if ( ! is_array( $raw ) ) {
		$raw = array();
	}
	$defaults = mtsuav_fsp_default_settings();
	$s        = array();

	$s['enabled']          = isset( $raw['enabled'] ) ? 'yes' : 'no';
	$s['manage_methods']    = isset( $raw['manage_methods'] ) ? 'yes' : 'no';
	$s['cart_bar']          = isset( $raw['cart_bar'] ) ? 'yes' : 'no';
	$s['checkout_bar']      = isset( $raw['checkout_bar'] ) ? 'yes' : 'no';
	$s['exclude_sale_items']       = isset( $raw['exclude_sale_items'] ) ? 'yes' : 'no';
	$s['count_taxes_shipping']     = isset( $raw['count_taxes_shipping'] ) ? 'yes' : 'no';
	$s['remove_methods_on_uninstall'] = isset( $raw['remove_methods_on_uninstall'] ) ? 'yes' : 'no';

	$s['threshold'] = isset( $raw['threshold'] ) ? max( 0.0, (float) $raw['threshold'] ) : (float) $defaults['threshold'];

	$cart_positions = array( 'before_table', 'after_table', 'top' );
	$s['cart_position'] = ( isset( $raw['cart_position'] ) && in_array( $raw['cart_position'], $cart_positions, true ) )
		? $raw['cart_position']
		: $defaults['cart_position'];

	$s['message_progress'] = isset( $raw['message_progress'] ) && '' !== trim( (string) $raw['message_progress'] )
		? wp_kses_post( $raw['message_progress'] )
		: $defaults['message_progress'];
	$s['message_unlocked'] = isset( $raw['message_unlocked'] ) && '' !== trim( (string) $raw['message_unlocked'] )
		? wp_kses_post( $raw['message_unlocked'] )
		: $defaults['message_unlocked'];

	foreach ( array( 'bar_fill', 'bar_bg', 'bar_text' ) as $color_key ) {
		$color = isset( $raw[ $color_key ] ) ? sanitize_hex_color( (string) $raw[ $color_key ] ) : '';
		$s[ $color_key ] = $color ? $color : $defaults[ $color_key ];
	}

	$s['bar_height'] = isset( $raw['bar_height'] )
		? (string) min( 100, max( 4, absint( $raw['bar_height'] ) ) )
		: $defaults['bar_height'];
	$s['bar_radius'] = isset( $raw['bar_radius'] )
		? (string) min( 100, max( 0, absint( $raw['bar_radius'] ) ) )
		: $defaults['bar_radius'];

	$basis = array( 'after_discounts', 'before_discounts' );
	$s['subtotal_basis'] = ( isset( $raw['subtotal_basis'] ) && in_array( $raw['subtotal_basis'], $basis, true ) )
		? $raw['subtotal_basis']
		: $defaults['subtotal_basis'];

	$s['include_categories'] = mtsuav_fsp_sanitize_id_list( isset( $raw['include_categories'] ) ? $raw['include_categories'] : array() );
	$s['exclude_categories'] = mtsuav_fsp_sanitize_id_list( isset( $raw['exclude_categories'] ) ? $raw['exclude_categories'] : array() );

	// auto_created_instances is managed internally, never from the form.
	$old = mtsuav_fsp_get_settings();
	$s['auto_created_instances'] = isset( $old['auto_created_instances'] ) && is_array( $old['auto_created_instances'] )
		? $old['auto_created_instances']
		: array();

	return $s;
}

/**
 * Category IDs for a cart product, resolving variations to their parent.
 *
 * @param WC_Product $product Product object.
 * @return int[]
 */
function mtsuav_fsp_product_category_ids( $product ) {
	$cat_ids = $product->get_category_ids();
	if ( empty( $cat_ids ) && $product->is_type( 'variation' ) ) {
		$parent = wc_get_product( $product->get_parent_id() );
		if ( $parent ) {
			$cat_ids = $parent->get_category_ids();
		}
	}
	return array_map( 'absint', (array) $cat_ids );
}

/**
 * Whether a cart line item counts toward the threshold.
 *
 * @param WC_Product $product  Product object.
 * @param array      $settings Settings array.
 * @return bool
 */
function mtsuav_fsp_item_qualifies( $product, $settings ) {
	if ( 'yes' === $settings['exclude_sale_items'] && $product->is_on_sale() ) {
		return false;
	}
	$cat_ids = mtsuav_fsp_product_category_ids( $product );
	if ( ! empty( $settings['include_categories'] ) && empty( array_intersect( $cat_ids, $settings['include_categories'] ) ) ) {
		return false;
	}
	if ( ! empty( $settings['exclude_categories'] ) && ! empty( array_intersect( $cat_ids, $settings['exclude_categories'] ) ) ) {
		return false;
	}
	return true;
}

/**
 * Qualifying cart subtotal for the free shipping threshold.
 *
 * Respects the subtotal basis (after or before discounts), sale-item
 * exclusion, category rules, and the taxes/shipping toggle.
 *
 * @param array|null $settings Optional settings array.
 * @return float
 */
function mtsuav_fsp_get_qualifying_total( $settings = null ) {
	if ( null === $settings ) {
		$settings = mtsuav_fsp_get_settings();
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}
	$cart  = WC()->cart;
	$total = 0.0;

	foreach ( $cart->get_cart() as $item ) {
		if ( empty( $item['data'] ) || ! $item['data'] instanceof WC_Product ) {
			continue;
		}
		if ( ! mtsuav_fsp_item_qualifies( $item['data'], $settings ) ) {
			continue;
		}
		$line = ( 'before_discounts' === $settings['subtotal_basis'] )
			? (float) $item['line_subtotal']
			: (float) $item['line_total'];
		$total += $line;
	}

	if ( 'yes' === $settings['count_taxes_shipping'] ) {
		$total += (float) $cart->get_cart_tax();
		$total += (float) $cart->get_shipping_tax();
		$total += (float) $cart->get_shipping_total();
	}

	return max( 0.0, $total );
}

/**
 * Build the progress bar HTML for a given total and threshold.
 *
 * @param float $total     Qualifying cart total.
 * @param float $threshold Free shipping threshold.
 * @param array $settings  Settings array.
 * @return string
 */
function mtsuav_fsp_bar_html( $total, $threshold, $settings ) {
	$total     = (float) $total;
	$threshold = (float) $threshold;
	if ( $threshold <= 0 ) {
		return '';
	}

	$remaining = max( 0.0, $threshold - $total );
	$pct       = min( 100.0, ( $total / $threshold ) * 100 );

	$template = $remaining <= 0 ? $settings['message_unlocked'] : $settings['message_progress'];
	$message  = str_replace(
		array( '{remaining}', '{threshold}', '{cart_total}' ),
		array( wc_price( $remaining ), wc_price( $threshold ), wc_price( $total ) ),
		$template
	);

	$fill   = esc_attr( $settings['bar_fill'] );
	$bg     = esc_attr( $settings['bar_bg'] );
	$text   = esc_attr( $settings['bar_text'] );
	$height = absint( $settings['bar_height'] );
	$radius = absint( $settings['bar_radius'] );
	$pct_s  = esc_attr( number_format( $pct, 1 ) );

	// $message was sanitized with wp_kses_post on save; wc_price() output is safe.
	return '<div class="mtsuav-fsp-bar-wrap" style="margin:1em 0;">'
		. '<p class="mtsuav-fsp-bar-message" style="margin:0 0 0.5em;color:' . $text . ';">' . $message . '</p>'
		. '<div class="mtsuav-fsp-bar-track" role="progressbar" aria-valuenow="' . $pct_s . '" aria-valuemin="0" aria-valuemax="100"'
		. ' style="background:' . $bg . ';height:' . $height . 'px;border-radius:' . $radius . 'px;overflow:hidden;">'
		. '<div class="mtsuav-fsp-bar-fill" style="width:' . $pct_s . '%;background:' . $fill . ';height:100%;border-radius:' . $radius . 'px;"></div>'
		. '</div></div>';
}

/**
 * Render the progress bar for the current cart.
 *
 * @param float|null $threshold_override Optional threshold override (shortcode).
 * @return string
 */
function mtsuav_fsp_render_bar( $threshold_override = null ) {
	$settings = mtsuav_fsp_get_settings();
	if ( 'yes' !== $settings['enabled'] ) {
		return '';
	}
	$threshold = null !== $threshold_override ? (float) $threshold_override : (float) $settings['threshold'];
	if ( $threshold <= 0 ) {
		return '';
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}
	if ( WC()->cart->get_cart_contents_count() <= 0 ) {
		return '';
	}
	return mtsuav_fsp_bar_html( mtsuav_fsp_get_qualifying_total( $settings ), $threshold, $settings );
}

/**
 * Render a static sample bar for the settings page preview.
 *
 * @param array $settings Settings array.
 * @return string
 */
function mtsuav_fsp_render_bar_sample( $settings ) {
	return mtsuav_fsp_bar_html( 65.0, 100.0, $settings );
}

/**
 * Shortcode: [mtsuav_fsp_bar] or [mtsuav_fsp_bar threshold="100"].
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function mtsuav_fsp_shortcode( $atts ) {
	$atts = shortcode_atts(
		array( 'threshold' => '' ),
		$atts,
		'mtsuav_fsp_bar'
	);
	$override = '' !== trim( (string) $atts['threshold'] ) ? max( 0.0, (float) $atts['threshold'] ) : null;
	return mtsuav_fsp_render_bar( $override );
}

/**
 * Cart page bar, before the cart table.
 *
 * @return void
 */
function mtsuav_fsp_cart_bar_before_table() {
	$settings = mtsuav_fsp_get_settings();
	if ( 'yes' !== $settings['enabled'] || 'yes' !== $settings['cart_bar'] || 'before_table' !== $settings['cart_position'] ) {
		return;
	}
	echo mtsuav_fsp_render_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Cart page bar, after the cart table.
 *
 * @return void
 */
function mtsuav_fsp_cart_bar_after_table() {
	$settings = mtsuav_fsp_get_settings();
	if ( 'yes' !== $settings['enabled'] || 'yes' !== $settings['cart_bar'] || 'after_table' !== $settings['cart_position'] ) {
		return;
	}
	echo mtsuav_fsp_render_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Cart page bar, top of the cart page.
 *
 * @return void
 */
function mtsuav_fsp_cart_bar_top() {
	$settings = mtsuav_fsp_get_settings();
	if ( 'yes' !== $settings['enabled'] || 'yes' !== $settings['cart_bar'] || 'top' !== $settings['cart_position'] ) {
		return;
	}
	echo mtsuav_fsp_render_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Checkout page bar, before the checkout form.
 *
 * @return void
 */
function mtsuav_fsp_checkout_bar() {
	$settings = mtsuav_fsp_get_settings();
	if ( 'yes' !== $settings['enabled'] || 'yes' !== $settings['checkout_bar'] ) {
		return;
	}
	echo mtsuav_fsp_render_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Refresh the cart page bar during AJAX cart updates.
 *
 * @param array $fragments Fragments array.
 * @return array
 */
function mtsuav_fsp_cart_fragments( $fragments ) {
	$settings = mtsuav_fsp_get_settings();
	if ( 'yes' === $settings['enabled'] && 'yes' === $settings['cart_bar'] ) {
		$fragments['div.mtsuav-fsp-bar-wrap'] = mtsuav_fsp_render_bar();
	}
	return $fragments;
}

/**
 * Register frontend hooks: bars, shortcode, AJAX fragments.
 *
 * @return void
 */
function mtsuav_fsp_init() {
	add_action( 'woocommerce_before_cart_table', 'mtsuav_fsp_cart_bar_before_table' );
	add_action( 'woocommerce_after_cart_table', 'mtsuav_fsp_cart_bar_after_table' );
	add_action( 'woocommerce_before_cart', 'mtsuav_fsp_cart_bar_top' );
	add_action( 'woocommerce_before_checkout_form', 'mtsuav_fsp_checkout_bar' );
	add_filter( 'woocommerce_add_to_cart_fragments', 'mtsuav_fsp_cart_fragments' );
	add_shortcode( 'mtsuav_fsp_bar', 'mtsuav_fsp_shortcode' );

	if ( is_admin() ) {
		mtsuav_fsp_admin_init();
	}
}
