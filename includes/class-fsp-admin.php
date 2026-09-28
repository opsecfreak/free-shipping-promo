<?php
/**
 * Admin settings for Free Shipping Promo for WooCommerce.
 *
 * Adds a "Free Shipping Promo" tab under WooCommerce > Settings with its own
 * render and save handlers, full sanitization, and the quiet tip box.
 *
 * @package FSP
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register admin hooks.
 *
 * @return void
 */
function fsp_admin_init() {
	add_filter( 'woocommerce_settings_tabs_array', 'fsp_add_settings_tab', 50 );
	add_action( 'woocommerce_settings_free_shipping_promo', 'fsp_render_settings_page' );
	add_action( 'woocommerce_settings_save_free_shipping_promo', 'fsp_save_settings' );
}

/**
 * Add the tab to WooCommerce > Settings.
 *
 * @param array $tabs Existing tabs.
 * @return array
 */
function fsp_add_settings_tab( $tabs ) {
	$tabs['free_shipping_promo'] = __( 'Free Shipping Promo', 'free-shipping-promo' );
	return $tabs;
}

/**
 * Render a checkbox field.
 *
 * @param string $key      Setting key.
 * @param string $label    Field label.
 * @param string $desc     Field description.
 * @param array  $settings Settings array.
 * @return void
 */
function fsp_field_checkbox( $key, $label, $desc, $settings ) {
	$checked = isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
	?>
	<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="fsp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td class="forminp">
			<fieldset>
				<label>
					<input type="checkbox" id="fsp_<?php echo esc_attr( $key ); ?>"
						name="fsp_settings[<?php echo esc_attr( $key ); ?>]" value="yes"
						<?php checked( $checked ); ?> />
					<?php echo esc_html( $desc ); ?>
				</label>
			</fieldset>
		</td>
	</tr>
	<?php
}

/**
 * Render a text/number field.
 *
 * @param string $key      Setting key.
 * @param string $label    Field label.
 * @param string $desc     Field description.
 * @param array  $settings Settings array.
 * @param string $type     Input type.
 * @param string $step     Step attribute for number inputs.
 * @return void
 */
function fsp_field_text( $key, $label, $desc, $settings, $type = 'text', $step = '' ) {
	$value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
	?>
	<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="fsp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td class="forminp">
			<input type="<?php echo esc_attr( $type ); ?>" id="fsp_<?php echo esc_attr( $key ); ?>"
				name="fsp_settings[<?php echo esc_attr( $key ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
				<?php echo '' !== $step ? 'step="' . esc_attr( $step ) . '"' : ''; ?>
				class="regular-text" />
			<?php if ( '' !== $desc ) : ?>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render a select field.
 *
 * @param string $key      Setting key.
 * @param string $label    Field label.
 * @param string $desc     Field description.
 * @param array  $settings Settings array.
 * @param array  $options  value => label pairs.
 * @return void
 */
function fsp_field_select( $key, $label, $desc, $settings, $options ) {
	$value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
	?>
	<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="fsp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td class="forminp">
			<select id="fsp_<?php echo esc_attr( $key ); ?>"
				name="fsp_settings[<?php echo esc_attr( $key ); ?>]" class="regular-text">
				<?php foreach ( $options as $opt_value => $opt_label ) : ?>
					<option value="<?php echo esc_attr( $opt_value ); ?>" <?php selected( $value, $opt_value ); ?>>
						<?php echo esc_html( $opt_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php if ( '' !== $desc ) : ?>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render a product category multiselect field.
 *
 * @param string $key      Setting key.
 * @param string $label    Field label.
 * @param string $desc     Field description.
 * @param array  $settings Settings array.
 * @return void
 */
function fsp_field_categories( $key, $label, $desc, $settings ) {
	$selected_ids = isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ? $settings[ $key ] : array();
	$terms        = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		)
	);
	?>
	<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="fsp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td class="forminp">
			<?php if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) : ?>
				<select multiple="multiple" id="fsp_<?php echo esc_attr( $key ); ?>"
					name="fsp_settings[<?php echo esc_attr( $key ); ?>][]"
					style="min-width:300px;min-height:120px;" class="wc-enhanced-select">
					<?php foreach ( $terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->term_id ); ?>"
							<?php echo in_array( (int) $term->term_id, array_map( 'intval', $selected_ids ), true ) ? 'selected="selected"' : ''; ?>>
							<?php echo esc_html( $term->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No product categories found.', 'free-shipping-promo' ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $desc ) : ?>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render a color field.
 *
 * @param string $key      Setting key.
 * @param string $label    Field label.
 * @param string $desc     Field description.
 * @param array  $settings Settings array.
 * @return void
 */
function fsp_field_color( $key, $label, $desc, $settings ) {
	$value = isset( $settings[ $key ] ) ? $settings[ $key ] : '#000000';
	?>
	<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="fsp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td class="forminp">
			<input type="color" id="fsp_<?php echo esc_attr( $key ); ?>"
				name="fsp_settings[<?php echo esc_attr( $key ); ?>]"
				value="<?php echo esc_attr( $value ); ?>" />
			<?php if ( '' !== $desc ) : ?>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render the settings page.
 *
 * @return void
 */
function fsp_render_settings_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage these settings.', 'free-shipping-promo' ) );
	}
	$settings = fsp_get_settings();
	$currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
	?>
	<div class="wrap woocommerce">
		<?php
		if ( function_exists( 'mtsuav_tip_box' ) ) {
			mtsuav_tip_box( 'free-shipping-promo', 'Free Shipping Promo for WooCommerce' );
		}
		?>
		<form method="post" id="mainform" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( 'woocommerce-settings' ); ?>
			<?php wp_nonce_field( 'fsp_save_settings', 'fsp_nonce' ); ?>

			<h2><?php esc_html_e( 'General', 'free-shipping-promo' ); ?></h2>
			<table class="form-table">
				<?php
				fsp_field_checkbox( 'enabled', __( 'Enable promotion', 'free-shipping-promo' ), __( 'Turn the free shipping promotion on or off.', 'free-shipping-promo' ), $settings );
				fsp_field_text(
					'threshold',
					__( 'Free shipping threshold', 'free-shipping-promo' ),
					/* translators: %s: currency symbol */
					sprintf( __( 'Orders at or above this amount (in %s) qualify for free shipping.', 'free-shipping-promo' ), $currency ),
					$settings,
					'number',
					'0.01'
				);
				fsp_field_checkbox(
					'manage_methods',
					__( 'Manage Free Shipping methods automatically', 'free-shipping-promo' ),
					__( 'On save, every shipping zone gets a Free Shipping method with the minimum order amount set to the threshold. Created when missing, updated when present. Methods that require a coupon are never changed.', 'free-shipping-promo' ),
					$settings
				);
				?>
			</table>

			<h2><?php esc_html_e( 'Progress bar', 'free-shipping-promo' ); ?></h2>
			<table class="form-table">
				<?php
				fsp_field_checkbox( 'cart_bar', __( 'Show on cart page', 'free-shipping-promo' ), __( 'Display the progress bar on the cart page.', 'free-shipping-promo' ), $settings );
				fsp_field_select(
					'cart_position',
					__( 'Cart page position', 'free-shipping-promo' ),
					'',
					$settings,
					array(
						'before_table' => __( 'Before the cart table', 'free-shipping-promo' ),
						'after_table'  => __( 'After the cart table', 'free-shipping-promo' ),
						'top'          => __( 'Top of the cart page', 'free-shipping-promo' ),
					)
				);
				fsp_field_checkbox( 'checkout_bar', __( 'Show on checkout page', 'free-shipping-promo' ), __( 'Display the progress bar before the checkout form.', 'free-shipping-promo' ), $settings );
				fsp_field_text(
					'message_progress',
					__( 'Progress message', 'free-shipping-promo' ),
					__( 'Available placeholders: {remaining}, {threshold}, {cart_total}.', 'free-shipping-promo' ),
					$settings
				);
				fsp_field_text(
					'message_unlocked',
					__( 'Unlocked message', 'free-shipping-promo' ),
					__( 'Shown when the cart qualifies. Same placeholders available.', 'free-shipping-promo' ),
					$settings
				);
				fsp_field_color( 'bar_fill', __( 'Bar fill color', 'free-shipping-promo' ), '', $settings );
				fsp_field_color( 'bar_bg', __( 'Bar background color', 'free-shipping-promo' ), '', $settings );
				fsp_field_color( 'bar_text', __( 'Message text color', 'free-shipping-promo' ), '', $settings );
				fsp_field_text( 'bar_height', __( 'Bar height (px)', 'free-shipping-promo' ), '', $settings, 'number', '1' );
				fsp_field_text( 'bar_radius', __( 'Bar corner radius (px)', 'free-shipping-promo' ), '', $settings, 'number', '1' );
				?>
			</table>

			<h2><?php esc_html_e( 'Preview', 'free-shipping-promo' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Sample bar at 65 of 100 using your current message and color settings:', 'free-shipping-promo' ); ?></p>
			<?php echo fsp_render_bar_sample( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<h2><?php esc_html_e( 'Qualification rules', 'free-shipping-promo' ); ?></h2>
			<table class="form-table">
				<?php
				fsp_field_select(
					'subtotal_basis',
					__( 'Subtotal basis', 'free-shipping-promo' ),
					__( 'Whether discounts are subtracted before measuring the cart against the threshold.', 'free-shipping-promo' ),
					$settings,
					array(
						'after_discounts'  => __( 'After discounts', 'free-shipping-promo' ),
						'before_discounts' => __( 'Before discounts', 'free-shipping-promo' ),
					)
				);
				fsp_field_checkbox( 'exclude_sale_items', __( 'Exclude sale items', 'free-shipping-promo' ), __( 'Items on sale do not count toward the threshold.', 'free-shipping-promo' ), $settings );
				fsp_field_categories( 'include_categories', __( 'Only these categories count', 'free-shipping-promo' ), __( 'Leave empty to count every category.', 'free-shipping-promo' ), $settings );
				fsp_field_categories( 'exclude_categories', __( 'These categories never count', 'free-shipping-promo' ), __( 'Items in these categories never count toward the threshold.', 'free-shipping-promo' ), $settings );
				fsp_field_checkbox( 'count_taxes_shipping', __( 'Count taxes and shipping', 'free-shipping-promo' ), __( 'Add estimated taxes and shipping to the qualifying total. Off by default.', 'free-shipping-promo' ), $settings );
				?>
			</table>

			<h2><?php esc_html_e( 'Shortcode', 'free-shipping-promo' ); ?></h2>
			<table class="form-table">
				<tr valign="top">
					<th scope="row" class="titledesc"><?php esc_html_e( 'Usage', 'free-shipping-promo' ); ?></th>
					<td class="forminp">
						<p><code>[fsp_bar]</code></p>
						<p class="description"><?php esc_html_e( 'Renders the same progress bar anywhere: pages, posts, or widgets. Optional threshold override:', 'free-shipping-promo' ); ?> <code>[fsp_bar threshold="100"]</code></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Uninstall', 'free-shipping-promo' ); ?></h2>
			<table class="form-table">
				<?php
				fsp_field_checkbox(
					'remove_methods_on_uninstall',
					__( 'Delete auto-created methods on uninstall', 'free-shipping-promo' ),
					__( 'Also delete the Free Shipping methods this plugin created. Default: methods are left in place and only the plugin settings are removed.', 'free-shipping-promo' ),
					$settings
				);
				?>
			</table>

			<p class="submit">
				<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Save the settings.
 *
 * @return void
 */
function fsp_save_settings() {
	if ( ! isset( $_POST['fsp_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fsp_nonce'] ) ), 'fsp_save_settings' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$raw      = isset( $_POST['fsp_settings'] ) && is_array( $_POST['fsp_settings'] )
		? wp_unslash( $_POST['fsp_settings'] )
		: array();
	$settings = fsp_sanitize_settings( $raw );
	update_option( FSP_OPTION, $settings );

	if ( 'yes' === $settings['manage_methods'] ) {
		$created = fsp_sync_free_shipping_methods( (float) $settings['threshold'] );
		if ( class_exists( 'WC_Admin_Settings' ) ) {
			WC_Admin_Settings::add_message(
				sprintf(
					/* translators: %d: number of shipping methods created */
					__( 'Free Shipping methods synced. %d new method(s) created.', 'free-shipping-promo' ),
					count( $created )
				)
			);
		}
	}
}
