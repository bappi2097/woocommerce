<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

use Themepaw\bKash\Api\Query;

/**
 * WooCommerce init for bkash payment
 *
 * @author themepaw
 */
class Init {

	/**
	 * class initialize
	 */
	public function __construct() {
		new Ajax();
		add_action( 'admin_notices', [ $this, 'admin_notice' ] );
		add_action( 'woocommerce_checkout_create_order', [ $this, 'before_order_save' ], 10, 2 );
		add_action('woocommerce_after_checkout_validation', [ $this, 'add_bkash_valid_error' ], 10, 2 );
	}

	public function add_bkash_valid_error( $fields, $errors ) {
		if ( 
			!isset( $_POST['woocommerce_pay'] ) &&
			isset( $_POST['payment_method'] ) &&
			'wpbkash' === $_POST['payment_method'] &&
			isset( $_POST['bkash_checkout_valid'] ) && 
			$_POST['bkash_checkout_valid'] == "1"
		) {
			$errors->add( 'bkash_payment_required', '<strong>bKash Payment Required</strong>', array( 'id' => 'bkash-payment-required' ) );
		} 
	}

	/**
	 * Check and validate bkash response before order created
	 *
	 * @param $order
	 * @param array $data
	 *
	 * @return void
	 */
	public function before_order_save( $order, $data ) {
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		if ( ! isset( $available_gateways[ $data['payment_method'] ] ) || 'wpbkash' !== $data['payment_method'] ) {
			return;
		}

		$option = get_option( 'wpbkash_settings_fields' );

		if ( empty( $option['app_key'] ) || empty( $option['app_secret'] ) || empty( $option['username'] ) || empty( $option['password'] ) ) {
			throw new \Exception( __( 'WooCommerce bKash credentials are incorrect or missing any required field.', 'wpbkash' ) );
		}

		$api = new Query( $option );

		$paymentData = $api->get_bkash_token();

		if ( ! $paymentData ) {
			throw new \Exception( sprintf( __( "bKash server response is incorrect, please contact with <a href='mailto:%s'>site admin</a> or try later.", 'wpbkash' ), esc_attr( get_option( 'admin_email' ) ) ) );
		}
	}

	/**
	 * Display Admin notice
	 *
	 * @return void
	 */
	public function admin_notice() {
		$wcoption = get_option( 'woocommerce_wpbkash_settings', [] );
		if ( ! isset( $wcoption ) || ! isset( $wcoption['enabled'] ) || 'yes' !== $wcoption['enabled'] ) {
			return;
		}
		if ( 'BDT' !== get_woocommerce_currency() ) {
			?>
		<div class="notice notice-warning wpbkash--notice is-dismissible">
			<p><?php _e( 'bKash payment supports Bangladeshi taka (&#2547;) currency only. Please select Bangladeshi taka (BDT) from WooCommerce Currency options', 'wpbkash' ); ?></p>
		</div>
			<?php
		}
		$option = get_option( 'wpbkash_settings_fields' );
		if ( ( isset( $wcoption['enabled'] ) && 'yes' === $wcoption['enabled'] ) && ( ! isset( $option ) || empty( $option['app_key'] ) || empty( $option['app_secret'] ) || empty( $option['username'] ) || empty( $option['password'] ) ) ) {
			?>
		<div class="notice notice-warning wpbkash--notice is-dismissible">
			<p><?php _e( 'WooCommerce bKash Payment is enabled, but Merchant credentials are missing', 'wpbkash' ); ?></p>
		</div>
			<?php
		}
	}
}
