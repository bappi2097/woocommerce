<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPbKash_Process
 *
 * @author themepaw
 */
class Process {

	/**
	 * bKash payment button
	 *
	 * @param int $order_id
	 */
	public static function bkash_trigger( $order_id ) {
		?>
		<div class="wc-wpbkash-wrap">
			<button id="bkash_on_trigger" class="button alt wpbkash--simple-btn" data-id="<?php echo $order_id; ?>"><span class="wpbkash--btn-content"><?php esc_html_e( 'Pay With', 'wpbkash' ); ?> <img src="<?php echo WPBKASH_URL . 'assets/images/bkash-white.png'; ?>" /></span><span class="wpbkash--processing-content"><img src="<?php echo WPBKASH_URL . 'assets/images/bkash.gif'; ?>" /></span></button>
			<button id="bKash_button" disabled="disabled" class="wpbkash--hidden-btn"><?php esc_html_e( 'Pay With bKash', 'wpbkash' ); ?></button>
		</div>
		<?php
	}

}
