<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\WPCF7;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPCF7 setup and config for bkash payment
 */
final class WPCF7Loader {

	/**
	 * Initialize
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'rewrite' ] );
		add_filter( 'query_vars', [ $this, 'query_vars' ] );
		add_action( 'template_include', [ $this, 'change_template' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_shortcode( 'wpbkash-form', [ $this, 'shortcode' ] );
	}

	/**
	 * Enqueue script and styles
	 */
	public function enqueue() {

		$options = get_option( 'wpbkash_settings_fields' );

		if ( empty( $options ) || empty( $options['app_key'] ) || empty( $options['app_secret'] ) || empty( $options['username'] ) || empty( $options['password'] ) ) {
			return;
		}

		$mode          = ( isset( $options['testmode'] ) && ! empty( $options['testmode'] ) ) ? 'sandbox' : 'pay';
		$bkash_version = WPBKASH()->bkash_api_version;
		$filename      = ( 'sandbox' === $mode ) ? 'bKash-checkout-sandbox' : 'bKash-checkout';

		wp_register_script( 'wpbkash_wpcf7', WPBKASH_URL . 'assets/js/wpbkash_wpcf7.js', [ 'jquery' ], '0.1', true );
		wp_register_style( 'wpbkash-front', WPBKASH_URL . 'assets/css/wpbkash-frontend.css' );

		wp_localize_script(
			'wpbkash_wpcf7',
			'wpbkash_params',
			[
				'home_url'  => esc_url( home_url() ),
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wpbkash_nonce' ),
				'i18n_error' => sprintf( __( 'Something wen\'t wrong, please try again or contact with <a href="%s">site admin</a>.', 'wpbkash' ), get_bloginfo('admin_email') ),
				'scriptUrl' => "https://scripts.{$mode}.bka.sh/versions/{$bkash_version}/checkout/{$filename}.js",
			]
		);

	}

	/**
	 * Check token is expired or not
	 *
	 * @param string $time
	 */
	public function is_expired( $time ) {
		$dif              = time() - strtotime( $time );
		$token_expiration = apply_filters( 'wpbkash_token_expiration', HOUR_IN_SECONDS );
		if ( $dif > $token_expiration ) {
			return true;
		}
		return false;
	}

	/**
	 * Token validation
	 *
	 * @param object $entry
	 */
	function token_validation( $entry ) {

		$error = [];

		if ( ! isset( $_GET['key'] ) || empty( $_GET['key'] ) ) {
			$error['invalid_key'] = __( 'Key is invalid or expired', 'wpbkash' );
			return $error;
		}

		$token = $_GET['key'];
		if ( $token !== $entry->key_token ) {
			$error['invalid_key'] = __( 'Key is invalid or expired', 'wpbkash' );
			return $error;
		}

		if ( 'completed' === $entry->status ) {
			$error['status_completed'] = __( 'Key is already used', 'wpbkash' );
			return $error;
		}

		if ( ! empty( $entry->key_created ) && $this->is_expired( $entry->key_created ) ) {
			$error['invalid_key'] = __( 'Key is invalid or expired', 'wpbkash' );
			return $error;
		}

		return $error;

	}


	/**
	 * WPbKash Payout form shortcode
	 *
	 * @param $atts
	 */
	public function shortcode( $atts ) {

		extract(
			shortcode_atts(
				array(
					'id' => '',
				),
				$atts
			)
		);

		if ( empty( $id ) ) {
			$id = get_query_var( 'wpbkash_api' );
		}

		if ( empty( $id ) ) {
			return;
		}

		$entry = wpbkash_get_entry( $id );
		if ( ! isset( $entry ) || empty( $entry ) ) {
			return;
		}

		wp_enqueue_script( 'wpbkash_wpcf7' );
		wp_enqueue_style( 'wpbkash-front' );

		ob_start();

		$error = $this->token_validation( $entry );
		if ( isset( $error ) && ! empty( $error ) ) : ?>
			<div class="wpbkash--message-wrapper">
				<?php foreach ( $error as $key => $msg ) : ?>
					<div class="wpbkash--single-message wpbkash--message-<?php echo $key; ?>">
						<h3>
						<?php
						printf( __('%1$s Please go back to the form and try again. <a href="%2$s">Back to Home</a>', 'wpbkash'),
							esc_html( $msg ),
							esc_url( home_url() )
						);?>
						</h3>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
			return ob_get_clean();
		endif;
		?>
		<div class="wpbkash--frontend-notice"></div>
		<div id="wpbkash--frontend-form" class="wpbkash--frontend-main-contant" style="opacity: 0">
			<div class="wpbkash--frontend-inner">
				<div class="wpbkash--author-thumb">
					<h2><?php echo ( isset( $entry->sender ) && isset($entry->sender[0]) ) ? esc_html( $entry->sender[0] ) : __('hi', 'wpbkash'); ?></h2>
				</div>
				<h4><?php echo esc_html( $entry->sender ); ?></h4>
				<div class="wpbkash--frontend-price">
					<span class="wpbkash--frontend-currency">&#2547;</span>
					<span class="wpbkash--frontend-bdt"><?php echo esc_html( number_format( $entry->amount, 2 ) ); ?></span>
				</div>
				<?php wp_nonce_field('wpbkash_security_nonce', 'wpbkash_nonce'); ?>
				<button id="bkash_on_trigger" class="button alt wpbkash--simple-btn" data-id="<?php echo esc_attr( $id ); ?>"><span class="wpbkash--btn-content"><?php esc_html_e( 'Pay With', 'wpbkash' ); ?> <img width="100" src="<?php echo WPBKASH_URL . 'assets/images/bkash-white.png'; ?>" /></span><span class="wpbkash--processing-content"><img src="<?php echo WPBKASH_URL . 'assets/images/bkash.gif'; ?>" /></span></button>
				<button id="bKash_button" disabled="disabled" class="wpbkash--hidden-btn"><?php esc_html_e( 'Pay With bKash', 'wpbkash' ); ?></button>
			</div>
			<div class="wpbkash--sucessfull-response wpbkash--msg-response">
				<div class="wpbkash--msg-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="154px" height="154px">  
						<g fill="none" stroke="#22AE73" stroke-width="2"> 
						<circle cx="77" cy="77" r="72" style="stroke-dasharray:480px, 480px; stroke-dashoffset: 960px;"></circle>
						<circle id="colored" fill="#22AE73" cx="77" cy="77" r="72" style="stroke-dasharray:480px, 480px; stroke-dashoffset: 960px;"></circle>
						<polyline class="st0" stroke="#fff" stroke-width="10" points="43.5,77.8 63.7,97.9 112.2,49.4 " style="stroke-dasharray:100px, 100px; stroke-dashoffset: 200px;"/>   
						</g> 
					</svg>
				</div>
				<div class="wpbkash--msg-text">
					<h3><?php esc_attr_e( 'Your payment has been received successfully.', 'wpbkash' ); ?></h3>
				</div>
			</div>
			<div class="wpbkash--error-response wpbkash--msg-response">
				<div class="wpbkash--msg-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="154px" height="154px">  
						<g fill="none" stroke="#F44812" stroke-width="2"> 
						<circle cx="77" cy="77" r="72" style="stroke-dasharray:480px, 480px; stroke-dashoffset: 960px;"></circle>
						<circle id="colored" fill="#F44812" cx="77" cy="77" r="72" style="stroke-dasharray:480px, 480px; stroke-dashoffset: 960px;"></circle>
						<polyline class="st0" stroke="#fff" stroke-width="10" points="43.5,77.8  112.2,77.8 " style="stroke-dasharray:100px, 100px; stroke-dashoffset: 200px;"/>   
						</g> 
					</svg>
				</div>
				<div class="wpbkash--msg-text">
					<h3><?php esc_attr_e( 'something went wrong please try again later.', 'wpbkash' ); ?></h3>
				</div>
			</div>
		</div>
		<a class="wpbkash--frontend-report-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" style="opacity: 0"><?php esc_html_e( 'Go back to home', 'wpbkash' ); ?></a>
		<?php
		return ob_get_clean();

	}

	/**
	 * Create virtual page for payment
	 */
	public function rewrite() {
		add_rewrite_rule( '^wpbkash-api/([0-9]+)/?', 'index.php?wpbkash_api=$matches[1]', 'top' );

		if ( get_transient( 'wpbkash_flush' ) ) {
			delete_transient( 'wpbkash_flush' );
			flush_rewrite_rules();
		}
	}

	/**
	 * Register custom query vars
	 *
	 * @param array $vars
	 */
	public function query_vars( $vars ) {
		$vars[] = 'wpbkash_api';

		return $vars;
	}

	/**
	 * load template
	 */
	public function change_template( $template ) {

		if ( get_query_var( 'wpbkash_api', false ) !== false ) {

			// Check template exist in theme folder
			$newTemplate = locate_template( [ 'template-wpbkash-api.php' ] );
			if ( '' != $newTemplate ) {
				return $newTemplate;
			}

			// Check plugin directory next
			$newTemplate = WPBKASH_PATH . 'templates/template-wpbkash-api.php';
			if ( file_exists( $newTemplate ) ) {
				return $newTemplate;
			}
		}

		// Fall back to original template
		return $template;

	}

}
