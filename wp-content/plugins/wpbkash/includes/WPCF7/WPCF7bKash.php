<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\WPCF7;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPCF7bKash - Setup WPCF7
 *
 * @author themepaw
 */
final class WPCF7bKash {

	/**
	 * Initialize
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_filter( 'wpcf7_editor_panels', [ $this, 'show_metabox' ] );
			add_action( 'wpcf7_after_save', [ $this, 'wpcf7_save_field' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'wpcfy_styles' ] );
		} else {
			add_action( 'wpcf7_mail_sent', [ $this, 'wpcf7_callback' ] );
		}
	}

	/**
	 * Send payout url email
	 *
	 * @param int    $form_id
	 * @param array  $form_data
	 * @param string $recipient
	 */
	public function sendPayUrl( $form_id, $form_data, $recipient ) {

		$wpbkash_amount       = get_post_meta( $form_id, '_wpbkash_amount', true );
		$wpbkash_pay_mail     = get_post_meta( $form_id, '_wpbkash_pay_mail', true );
		$wpbkash_pay_use_html = get_post_meta( $form_id, '_wpbkash_pay_use_html', true );

		$wpbkash_confirm_mail     = get_post_meta( $form_id, '_wpbkash_confirm_mail', true );
		$wpbkash_confirm_use_html = get_post_meta( $form_id, '_wpbkash_confirm_use_html', true );

		$wpbkash_pay_use_html     = ( ! empty( $wpbkash_confirm_use_html ) ) ? true : false;
		$wpbkash_confirm_use_html = ( ! empty( $wpbkash_confirm_use_html ) ) ? true : false;

		$email_data = [
			'privacy_policy_url' => get_privacy_policy_url(),
			'user_email'         => $recipient,
			'sitename'           => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
			'siteurl'            => esc_url( home_url() ),
			'amount'             => $wpbkash_amount,
			'form_id'            => $form_id,
			'admin_email'        => get_option( 'admin_email' ),
		];

		$email_text = wpbkash_pay_default_template();

		if ( ! empty( $wpbkash_pay_mail ) ) {
			$email_text = esc_textarea( $wpbkash_pay_mail );
		}

		/**
		 * Filters the body of the user request payout email.
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
		$content = apply_filters( 'wpbkash_pay_email_content', $email_text, $email_data );

		$key = wpbkash_get_payout_key();

		$response = [
			'sender'      => sanitize_email( $email_data['user_email'] ),
			'amount'      => intval( $email_data['amount'] ),
			'ref'         => 'wpcf7',
			'ref_id'      => intval( $email_data['form_id'] ),
			'key_token'   => sanitize_key( $key ),
			'status'      => 'pending',
			'form_data'   => maybe_serialize($form_data),
			'key_created' => current_time( 'mysql' ),
		];

		$payout_id = $this->create_payout( $response );

		$payout_url = wpbkash_get_payout_url( $email_data['user_email'], $response['key_token'], $payout_id );

		$tag_register = [
			'wpbkash-sitename'   => $email_data['sitename'],
			'wpbkash-siteurl'    => esc_url_raw( $email_data['siteurl'] ),
			'wpbkash-admin'      => $email_data['admin_email'],
			'wpbkash-paymenturl' => esc_url_raw( $payout_url ),
			'wpbkash-amount'     => (int) $email_data['amount'],
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

		preg_match_all( '/\[([^\]]*)\]/', $content, $content_tags );
		if ( ! empty( $content_tags ) && isset( $content_tags[1] ) && ! empty( $content_tags[1] ) ) {
			foreach ( (array) $content_tags[1] as $tag ) {
				if ( isset( $form_data[ $tag ] ) ) {
					$content = str_replace( "[{$tag}]", $form_data[ $tag ], $content );
				}
			}
		}

		$subject = sprintf(
			/* translators: Privacy data request confirmed notification email subject. 1: Site title, 2: Name of the confirmed action. */
			__( '[%1$s] Verify your email' ),
			$email_data['sitename']
		);

		/**
		 * Filters the subject of the user payout email.
		 *
		 * @since 0.1
		 *
		 * @param string $subject    The email subject.
		 * @param string $sitename   The name of the site.
		 * @param array  $email_data
		 */
		$subject = apply_filters( 'wpbkash_wpcf7_mail_subject', $subject, $email_data['sitename'], $email_data );

		$headers[] = "From: {$email_data['sitename']} <{$email_data['admin_email']}>";

		if ( $wpbkash_pay_use_html ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		/**
		 * Filters the headers of the user payout email.
		 *
		 * @since 0.1
		 *
		 * @param string $headers The email header object.
		 * @param array  $email_data
		 */
		$headers = apply_filters( 'wpbkash_wpcf7_mail_headers', $headers, $email_data );

		$email_sent = wp_mail( $email_data['user_email'], $subject, $content, $headers );

		if ( $email_sent ) {
			do_action( 'wpbkash_after_url_sent', $form_id, $form_data );
		}

	}

	/**
	 * Create payout
	 *
	 * @param object $response
	 *
	 * @return int $payout_id
	 */
	function create_payout( $response ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wpbkash',
			$response,
			[
				'%s',
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
			]
		);

		$payout_id = (int) $wpdb->insert_id;

		return $payout_id;
	}

	/**
	 * Callback after WPCF7 submitted
	 *
	 * @param object $WPCF7_ContactForm
	 */
	function wpcf7_callback( $WPCF7_ContactForm ) {

		 // Get the form ID
		 $form_id = $WPCF7_ContactForm->id();

		 $wpbkash_enable = get_post_meta( $form_id, '_wpbkash_enable', true );
		 $wpbkash_amount = get_post_meta( $form_id, '_wpbkash_amount', true );
		 $wpbkash_tag    = get_post_meta( $form_id, '_wpbkash_email', true );

		 $enable = ( ! empty( $wpbkash_enable ) ) ? true : false;

		 // get current SUBMISSION instance
		 $submission = \WPCF7_Submission::get_instance();

		 // Do something specifically for form with the ID "123"
		if ( $enable && $submission && ! empty( $wpbkash_tag ) ) {

			 // get submission data
			 $data = $submission->get_posted_data();

			 // nothing's here... do nothing...
			if ( empty( $data ) ) {
				return;
			}

			 $wpbkash_tag = preg_replace( '/(\[|\])/', '', $wpbkash_tag );

			 // nothing's here... do nothing...
			if ( ! isset( $data[ $wpbkash_tag ] ) ) {
				return;
			}

			 // get mail property
			 $recipient = $data[ $wpbkash_tag ]; // returns array

			if ( empty( $recipient ) ) {
				return;
			}

			 $this->sendPayUrl( $form_id, $data, $recipient );

			 return true;

		}

	}

	/**
	 * Add scripts and styles on wpcf7 panel
	 *
	 * @param string $hook
	 */
	function wpcfy_styles( $hook ) {

		if ( 'toplevel_page_wpcf7' != $hook && 'contact_page_wpcf7-new' != $hook ) {
			return;
		}

		wp_enqueue_style( 'wpbkash_wpcfy_style', WPBKASH_URL . 'assets/css/wpcf7.css' );
		wp_enqueue_script( 'wpbkash_wpcfy_script', WPBKASH_URL . 'assets/js/wpcf7.js', [ 'jquery' ], '0.1', true );

		wp_localize_script(
			'wpbkash_wpcfy_script',
			'wpbkash_wpcf7_params',
			[
				'amount_error'  => __( 'Amount can\'t be empty.', 'wpbkash' ),
				'email_error'   => __( 'Email tag field can\'t be empty.', 'wpbkash' ),
				'message_error' => __( 'Message box can\'t be empty.', 'wpbkash' ),
				'valid_tag'     => __( 'Please input a valid tag.', 'wpbkash' ),
				'url_error'     => __( 'You need to input [wpbkash-paymenturl] tag in mail box.', 'wpbkash' ),
			]
		);
	}

	/**
	 * Register new tab on contact form 7 editor panel
	 *
	 * @param array $panels
	 */
	function show_metabox( $panels ) {

		$new_page = [
			'WPbKash-bKash' => [
				'title'    => __( 'bKash', 'wpbkash' ),
				'callback' => [ $this, 'wpcf7_add_wpbkash_bkash' ],
			],
		];

		$panels = array_merge( $panels, $new_page );

		return $panels;
	}

	/**
	 * Content for contact form 7 custom tab panel
	 *
	 * @param object $post
	 */
	function wpcf7_add_wpbkash_bkash( $post ) {
		$desc_link   = wpcf7_link(
			__( 'https://github.com/mlbd/wpbkash/', 'wpbkash' ),
			__( 'WPbKash bKash Settings', 'wpbkash' )
		);
		$description = __( 'Use listed shortcode for proper email body. For details, see %s.', 'wpbkash' );
		$description = sprintf( esc_html( $description ), $desc_link );

		$wpbkash_enable = get_post_meta( $post->id(), '_wpbkash_enable', true );
		$wpbkash_amount = get_post_meta( $post->id(), '_wpbkash_amount', true );
		$wpbkash_email  = get_post_meta( $post->id(), '_wpbkash_email', true );

		$wpbkash_pay_mail     = get_post_meta( $post->id(), '_wpbkash_pay_mail', true );
		$wpbkash_pay_use_html = get_post_meta( $post->id(), '_wpbkash_pay_use_html', true );

		$wpbkash_confirm_mail     = get_post_meta( $post->id(), '_wpbkash_confirm_mail', true );
		$wpbkash_confirm_use_html = get_post_meta( $post->id(), '_wpbkash_confirm_use_html', true );
		$wpbkash_confirm_disabled = get_post_meta( $post->id(), '_wpbkash_confirm_disabled', true );

	?>

	<h2><?php echo esc_html( __( 'Additional Settings', 'wpbkash' ) ); ?></h2>
	<fieldset>
		<legend><?php echo wp_kses_post( $description ); ?></legend>
		<div class="wpbkash--suggested-tags">
			<?php echo $post->suggest_mail_tags( 'mail' ); ?>
		</div>
		<div class="wpbkash--suggested-tags">
			<h4><?php esc_html_e( 'WPbKash Mail Tags', 'wpbkash' ); ?></h4>
			<?php
			$tags = [
				'wpbkash-amount',
				'wpbkash-paymenturl',
				'wpbkass-sitename',
				'wpbkass-url',
				'wpbkass-admin',
				'wpbkash-trx_id',
				'wpbkash-paymentid'
			];
			$tags = apply_filters( 'wpbkash_wpcf7_editor_panels_tag', $tags, $post );
			foreach ( $tags as $tag ) {
				echo '<span class="mailtag code used">[' . $tag . ']</span>';
			}
			?>
		</div>
		<table class="form-table wpbkash--form-table">
			<tbody>
				<tr>
					<th>
						<label for="wpcf7-wpbkash-enable"><?php esc_html_e( 'Enable bKash', 'wpbkash' ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="wpcf7-wpbkash-enable" name="wpcf7-wpbkash-enable"  value="1" <?php checked( $wpbkash_enable, 1 ); ?>>
					</td>
				</tr>
				<tr>
					<th>
						<label for="wpcf7-wpbkash-amount"><?php esc_html_e( 'Payment Amount', 'wpbkash' ); ?></label>
					</th>
					<td>
						<input type="text" id="wpcf7-wpbkash-amount" size="5" name="wpcf7-wpbkash-amount"  placeholder="150" value="<?php echo esc_attr( $wpbkash_amount ); ?>">
						<p class="description"><?php esc_html_e( 'bKash Payment Amount (BDT) in number. eg: 150', 'wpbkash' ); ?></p>
					</td>
				</tr>
				<tr>
					<th>
						<label for="wpcf7-wpbkash-email"><?php esc_html_e( 'Customer Email Tag', 'wpbkash' ); ?></label>
					</th>
					<td>
						<input type="text" id="wpcf7-wpbkash-email" size="10" name="wpcf7-wpbkash-email" placeholder="[your-email]" data-config-field="mail.subject"  value="<?php echo esc_attr( $wpbkash_email ); ?>">
						<p class="description"><?php esc_html_e( 'Add contact form 7 user email input tag. eg: [your-email]. NOTE: WPbKash will be send mail to this tag value as recipient.', 'wpbkash' ); ?></p>
					</td>
				</tr>
				<tr>
					<th>
						<label for="wpcf7-wpbkash-pay"><?php esc_html_e( 'Payment Email Body', 'wpbkash' ); ?></label>
					</th>
					<td>
						<?php
						$payment_body = wpbkash_pay_default_template();
						if( ! empty( $wpbkash_pay_mail ) ) {
							$payment_body = $wpbkash_pay_mail;
						} ?>
						<textarea id="wpcf7-wpbkash-pay" name="wpcf7-wpbkash-pay" cols="100" rows="18" class="large-text code"><?php echo esc_textarea( $payment_body ); ?></textarea>
						<p><label for="wpcf7-wpbkash-pay-use-html"><input type="checkbox" id="wpcf7-wpbkash-pay-use-html" name="wpcf7-wpbkash-pay-use-html" value="1"<?php echo ( $wpbkash_pay_use_html ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Use HTML content type', 'wpbkash' ) ); ?></label></p>
					</td>
				</tr>
				<tr>
					<th>
						<label for="wpcf7-wpbkash-confirm"><?php esc_html_e( 'Payment Confirmation Body', 'wpbkash' ); ?></label>
					</th>
					<td>
						<?php
						$confirmed_body = wpbkash_confirmation_default_template();
						if( ! empty( $wpbkash_confirm_mail ) ) {
							$confirmed_body = $wpbkash_confirm_mail;
						} ?>
						<textarea id="wpcf7-wpbkash-confirm" name="wpcf7-wpbkash-confirm" cols="100" rows="18" class="large-text code" <?php echo ! empty( $wpbkash_confirm_disabled ) ? 'disabled="disabled"' : ''; ?>><?php echo esc_textarea( $confirmed_body ); ?></textarea>
						<p><label for="wpcf7-wpbkash-confirm-use-html"><input type="checkbox" id="wpcf7-wpbkash-confirm-use-html" name="wpcf7-wpbkash-confirm-use-html" value="1"<?php echo ( $wpbkash_confirm_use_html ) ? ' checked="checked"' : ''; ?> /> <?php esc_html_e( 'Use HTML content type', 'wpbkash' ); ?></label></p>
						<p><label for="wpcf7-wpbkash-confirm-disabled"><input type="checkbox" id="wpcf7-wpbkash-confirm-disabled" name="wpcf7-wpbkash-confirm-disabled" value="1"<?php echo ( $wpbkash_confirm_disabled ) ? ' checked="checked"' : ''; ?> /> <?php esc_html_e( 'Turn off confirmation mail', 'wpbkash' ); ?></label></p>
					</td>
				</tr>
			</tbody>
		</table>
	</fieldset>
		<?php
	}

	/**
	 * Validates and stores the field value.
	 *
	 * @param WPCF7_ContactForm $form
	 */
	function wpcf7_save_field( $form ) {
		if ( ! empty( $_POST['wpcf7-wpbkash-enable'] ) ) {
			update_post_meta( $form->id(), '_wpbkash_enable', sanitize_text_field( $_POST['wpcf7-wpbkash-enable'] ) );
		} else {
			delete_post_meta( $form->id(), '_wpbkash_enable' );
		}

		if ( ! empty( $_POST['wpcf7-wpbkash-amount'] ) ) {
			update_post_meta( $form->id(), '_wpbkash_amount', sanitize_text_field( $_POST['wpcf7-wpbkash-amount'] ) );
		} else {
			delete_post_meta( $form->id(), '_wpbkash_amount' );
		}

		if ( ! empty( $_POST['wpcf7-wpbkash-email'] ) ) {
			update_post_meta( $form->id(), '_wpbkash_email', sanitize_text_field( $_POST['wpcf7-wpbkash-email'] ) );
		} else {
			delete_post_meta( $form->id(), '_wpbkash_email' );
		}

		if ( ! empty( $_POST['wpcf7-wpbkash-pay'] ) ) {
			update_post_meta( $form->id(), '_wpbkash_pay_mail', sanitize_textarea_field( $_POST['wpcf7-wpbkash-pay'] ) );
		} else {
			delete_post_meta( $form->id(), '_wpbkash_pay_mail' );
		}

		if ( ! empty( $_POST['wpcf7-wpbkash-confirm'] ) ) {
			update_post_meta( $form->id(), '_wpbkash_confirm_mail', sanitize_textarea_field( $_POST['wpcf7-wpbkash-confirm'] ) );
		} else {
			delete_post_meta( $form->id(), '_wpbkash_confirm_mail' );
		}

		if ( ! empty( $_POST['wpcf7-wpbkash-pay-use-html'] ) ) {
			update_post_meta( $form->id(), '_wpbkash_pay_use_html', sanitize_text_field( $_POST['wpcf7-wpbkash-pay-use-html'] ) );
		} else {
			delete_post_meta( $form->id(), '_wpbkash_pay_use_html' );
		}

		if ( ! empty( $_POST['wpcf7-wpbkash-confirm-use-html'] ) ) {
			update_post_meta( $form->id(), '_wpbkash_confirm_use_html', sanitize_key( $_POST['wpcf7-wpbkash-confirm-use-html'] ) );
		} else {
			delete_post_meta( $form->id(), '_wpbkash_confirm_use_html' );
		}
		if ( ! empty( $_POST['wpcf7-wpbkash-confirm-disabled'] ) ) {
			update_post_meta( $form->id(), '_wpbkash_confirm_disabled', sanitize_key( $_POST['wpcf7-wpbkash-confirm-disabled'] ) );
		} else {
			delete_post_meta( $form->id(), '_wpbkash_confirm_disabled' );
		}

	}

}
