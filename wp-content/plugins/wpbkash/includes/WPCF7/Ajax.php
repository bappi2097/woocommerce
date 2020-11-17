<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\WPCF7;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Themepaw\bKash\Api\Query;

/**
 * Ajax class file.
 *
 * @package WpbKash
 */
final class Ajax {


	/**
	 * Store api class
	 */
	public $api;

	/**
	 * Initialize
	 */
	public function __construct() {

		$option = get_option( 'wpbkash_settings_fields' );

		if ( empty( $option ) || empty( $option['app_key'] ) || empty( $option['app_secret'] ) || empty( $option['username'] ) || empty( $option['password'] ) ) {
			return false;
		}

		$this->api = new Query( $option, 'wpcf7' );

		add_action( 'wp_ajax_wpbkash_form_createpayment', [ $this, 'wpbkash_form_createpayment' ] );
		add_action( 'wp_ajax_nopriv_wpbkash_form_createpayment', [ $this, 'wpbkash_form_createpayment' ] );
		add_action( 'wp_ajax_wpbkash_form_executepayment', [ $this, 'wpbkash_form_executepayment' ] );
		add_action( 'wp_ajax_nopriv_wpbkash_form_executepayment', [ $this, 'wpbkash_form_executepayment' ] );
	}

	/**
	 * bkash createpayment ajax request
	 */
	public function wpbkash_form_createpayment() {

		check_ajax_referer( 'wpbkash_security_nonce', 'nonce' );

		$entry_id = ( isset( $_POST['entry_id'] ) && ! empty( $_POST['entry_id'] ) ) ? absint( $_POST['entry_id'] ) : '';

		$entry = wpbkash_get_entry( $entry_id );

		if ( ! is_object( $entry ) || ! isset( $entry ) || empty( $entry ) || ! isset( $entry->amount ) ) {
			wp_send_json_error( __( 'Wrong or invalid entry ID', 'wpbkash' ) );
			wp_die();
		}

		$paymentData = $this->api->createPayment( $entry->amount );

		echo $paymentData;

		wp_die();
	}

	/**
	 * bkash executepayment ajax request
	 */
	public function wpbkash_form_executepayment() {
		check_ajax_referer( 'wpbkash_security_nonce', 'nonce' );

		$paymentid = ( isset( $_POST['paymentid'] ) && ! empty( $_POST['paymentid'] ) ) ? sanitize_text_field( $_POST['paymentid'] ) : '';
		$entry_id  = ( isset( $_POST['entry_id'] ) && ! empty( $_POST['entry_id'] ) ) ? absint( $_POST['entry_id'] ) : '';

		if ( empty( $paymentid ) ) {
			wp_send_json_error( __( 'Invalid token or expired', 'wpbkash' ) );
			wp_die();
		}

		$entry = wpbkash_get_entry( $entry_id );

		if ( ! is_object( $entry ) || ! isset( $entry ) || empty( $entry ) ) {
			wp_send_json_error( __( 'Wrong or invalid entry ID', 'wpbkash' ) );
			wp_die();
		}

		$data = $this->api->executePayment( $paymentid );

		$entry_redirect_url = apply_filters( 'wpbkash_wc_order_redirect_redirect', '' );

		if ( ! isset( $data ) || empty( $data ) ) {
			wp_send_json_error(
				[
					'order_url' => $entry_redirect_url,
					'message'   => __(
						'Something wen\'t wrong please try again.',
						'wpbkash'
					)
				]
			);
			wp_die();
		}

		$data = json_decode( $data );

		if ( ! isset( $data->trxID ) || ! isset( $data->paymentID ) ) {
			wp_send_json_error(
				[
					'order_url' => $entry_redirect_url,
					'message'   => __(
						'We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.',
						'wpbkash'
					)
				]
			);
			wp_die();
		}

		do_action( 'wpbkash_after_bkash_payment_recieved', $entry_id, $paymentid );

		$data->entry_id = $entry_id;

		$updated = $this->update_transaction( $data );

		$this->confirmation_mail( $entry_id, $data->trxID, $data->paymentID );

		wp_send_json_success(
			[
				'transactionStatus' => 'completed',
				'order_url'         => $entry_redirect_url
			]
		);

		wp_die();

	}

	/**
	 * Update entry data
	 *
	 * @param object $response
	 */
	public function update_transaction( $response ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wpbkash';

		$updated = $wpdb->update(
			$table,
			[
				'trx_id'     => sanitize_key( $response->trxID ),
				'trx_status' => sanitize_key( $response->transactionStatus ),
				'invoice' => sanitize_key( $response->merchantInvoiceNumber ),
				'created_at' => current_time( 'mysql' ),
				'status'     => 'completed',
				'data'       => maybe_serialize( $response )
			],
			[
				'id' => absint( $response->entry_id )
			],
			[
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
			]
		);

		if ( false !== $updated ) {
			do_action( 'wpbkash_after_bkash_entry_updated', $response );
		}

		return $updated;
	}

	/**
	 * Confirmation mail
	 *
	 * @param int $entry_id
	 */
	public function confirmation_mail( $entry_id, $trx_id, $paymentid ) {

		$entry_id = (int) $entry_id;

		$entry = wpbkash_get_entry( $entry_id );

		if ( ! is_object( $entry ) || ! isset( $entry ) || empty( $entry ) || 'wc_order' === $entry->ref ) {
			return;
		}

		if ( ! is_email( $entry->sender ) ) {
			return;
		}

		$confirm_disabled = get_post_meta( (int) $entry->ref_id, '_wpbkash_confirm_disabled', true );
		if ( ! empty( $confirm_disabled ) ) {
			return;
		}

		$wpbkash_confirm_mail     = get_post_meta( $entry->ref_id, '_wpbkash_confirm_mail', true );
		$wpbkash_confirm_use_html = get_post_meta( $entry->ref_id, '_wpbkash_confirm_use_html', true );

		$wpbkash_confirm_use_html = ( ! empty( $wpbkash_confirm_use_html ) ) ? true : false;

		$email_data = [
			'privacy_policy_url' => get_privacy_policy_url(),
			'user_email'         => $entry->sender,
			'sitename'           => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
			'siteurl'            => home_url(),
			'amount'             => $entry->amount,
			'trx_id'             => $trx_id,
			'paymentid'          => $paymentid,
			'entry_id'           => $entry_id,
			'form_id'            => $entry->ref_id,
			'admin_email'        => get_option( 'admin_email' )
		];

		$email_text = wpbkash_confirmation_default_template();

		if ( ! empty( $wpbkash_confirm_mail ) ) {
			$email_text = esc_textarea( $wpbkash_confirm_mail );
		}

		/**
		 * Filters the body of the user confirmation email.
		 *
		 * The email is sent to an the user when an user successfully submited the wpcf7.
		 * The following strings have a special meaning and will get replaced dynamically:
		 *
		 * [wpbkash-sitename]    The name of the site.
		 * [wpbkash-admin]       The admin email address of the site.
		 * [wpbkash-siteurl]     The URL to the site.
		 * [wpbkash-amount]      The amount of the payment.
		 * [wpbkash-paymenturl]  The payment url.
		 *
		 * @since 0.1
		 *
		 * @param string $email_text Text in the email.
		 * @param array  $email_data
		 */
		$content = apply_filters( 'wpbkash_confirmed_email_content', $email_text, $email_data );

		$tag_register = [
			'wpbkash-sitename'  => $email_data['sitename'],
			'wpbkash-siteurl'   => esc_url_raw( $email_data['siteurl'] ),
			'wpbkash-admin'     => $email_data['admin_email'],
			'wpbkash-trx_id'    => $email_data['trx_id'],
			'wpbkash-paymentid' => $email_data['paymentid'],
			'wpbkash-amount'    => (int) $email_data['amount']
		];

		/**
		 * Filters for body special tag register.
		 *
		 * The following strings have a special meaning and will get replaced dynamically:
		 *
		 * [wpbkash-sitename]    The name of the site.
		 * [wpbkash-admin]       The admin email address of the site.
		 * [wpbkash-siteurl]     The URL to the site.
		 * [wpbkash-amount]      The amount of the payment.
		 * [wpbkash-paymenturl]  The payment url.
		 *
		 * @since 0.1
		 *
		 * @param string $email_text Text in the email.
		 * @param array  $email_data
		 */
		$tags = apply_filters( 'wpbkash_wpcf7_mail_tags', $tag_register );

		foreach ( (array) $tags as $tag => $value ) {
			$content = str_replace( "[{$tag}]", $value, $content );
		}

		if ( isset( $entry->form_data ) ) :
			$form_data = maybe_unserialize( $entry->form_data );

			preg_match_all( '/\[([^\]]*)\]/', $content, $content_tags );
			if ( ! empty( $content_tags ) && isset( $content_tags[1] ) && ! empty( $content_tags[1] ) ) {
				foreach ( (array) $content_tags[1] as $tag ) {
					if ( isset( $form_data[ $tag ] ) ) {
						$content = str_replace( "[{$tag}]", $form_data[ $tag ], $content );
					}
				}
			}
		endif;

		$subject = sprintf(
			/* translators: Privacy data request confirmed notification email subject. 1: Site title, 2: Name of the confirmed action. */
			__( '[%1$s] Payment Confirmed' ),
			$email_data['sitename']
		);

		/**
		 * Filters the subject of the user confirmation email.
		 *
		 * @since 0.1
		 *
		 * @param string $subject    The email subject.
		 * @param string $sitename   The name of the site.
		 * @param array  $email_data
		 */
		$subject = apply_filters( 'wpbkash_wpcf7_confirmed_mail_subject', $subject, $email_data['sitename'], $email_data );

		$headers[] = "From: {$email_data['sitename']} <{$email_data['admin_email']}>";

		if ( $wpbkash_confirm_use_html ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		/**
		 * Filters the headers of the user confirmation email.
		 *
		 * @since 0.1
		 *
		 * @param string $headers The email header object.
		 * @param array  $email_data
		 */
		$headers = apply_filters( 'wpbkash_wpcf7_confirmed_mail_headers', $headers, $email_data );

		$email_sent = wp_mail( $email_data['user_email'], $subject, $content, $headers );

		if ( $email_sent ) {
			do_action( 'wpbkash_after_confirmed_sent', $entry_id, $email_data );
		}

	}

}
