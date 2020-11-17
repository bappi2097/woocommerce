<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Themepaw\bKash\Api\Query;

/**
 * WPbkash Settings class
 */
class Settings {

	/**
	 * The single instance of Settings.
	 *
	 * @var    object
	 * @access private
	 * @since  1.0.0
	 */
	private static $instance = null;

	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * WP_List_Table object
	 */
	public $tabe_obj;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var    string
	 * @access public
	 * @since  1.0.0
	 */
	public $base = '';

	public function __construct() {

		$this->base = 'wpbkash';

		// Register plugin settings
		add_action( 'admin_init', [ $this, 'register_settings' ] );

		// Add settings page to menu
		add_action( 'admin_menu', [ $this, 'add_menu_item' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_action( 'admin_post_update_entry', [ $this, 'form_handler' ] );

		add_filter( 'set-screen-option', [ $this, 'set_screen' ], 10, 3 );

		add_action( 'update_option_wpbkash_settings_fields', [ $this, 'trigger_on_update' ], 10, 2 );
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {
		wp_enqueue_style( 'wpbkash_admin', WPBKASH_URL . 'assets/css/admin-style.css' );
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {

		$hook = add_menu_page(
			__( 'WPbKash', 'wpbkash' ),
			__( 'WPbKash', 'wpbkash' ),
			'administrator',
			'wpbkash',
			[ $this, 'wpbkash_all_orders' ],
			WPBKASH_URL . 'assets/images/bkash.gif'
		);

		add_action( "load-$hook", [ $this, 'add_options' ] );

		add_submenu_page(
			'wpbkash',
			__( 'All Entries', 'wpbkash' ),
			__( 'All Entries', 'wpbkash' ),
			'administrator',
			'wpbkash'
		);

		add_submenu_page(
			'wpbkash',
			__( 'WPbKash Settings', 'wpbkash' ),
			__( 'Settings', 'wpbkash' ),
			'administrator',
			'wpbkash_settings',
			[ $this, 'settings_page' ]
		);

	}

	public function set_screen( $status, $option, $value ) {
		return $value;
	}

	function add_options() {
		$option = 'per_page';
		$args   = [
			'label'   => 'Entry',
			'default' => 10,
			'option'  => 'entry_per_page',
		];
		add_screen_option( $option, $args );

		$this->tabe_obj = new EntryTable();
	}

	/**
	 * Display a custom menu page
	 */
	public function wpbkash_all_orders() {
		$entry = ( isset( $_GET['action'] ) && ! empty( $_GET['action'] ) ) ? sanitize_key( $_GET['action'] ) : '';

		switch ( $entry ) {
			case 'view':
				$template = dirname( __FILE__ ) . '/pages/view.php';
				break;

			case 'edit':
				$template = dirname( __FILE__ ) . '/pages/edit.php';
				break;

			default:
				$template = dirname( __FILE__ ) . '/pages/list.php';
				break;
		}

		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	public function form_handler(){

		if( ! isset( $_POST['update_entry'] ) && ! isset( $_POST['entry_id'] ) ) {
			return;
		}

		if( ! current_user_can('manage_options') ) {
			wp_die( 'Are you cheating?' );
		}

		if ( ! isset( $_POST['wpbkash_edit_nonce'] ) || ! wp_verify_nonce( $_POST['wpbkash_edit_nonce'], 'wpbkash_entry_edit' ) ) {
			wp_die( 'Are you cheating?' );
		}

		$entry_id = isset( $_POST['entry_id'] ) ? intval( $_POST['entry_id'] ) : 0;
		
		$fields = array(
			'trx_id'     => isset( $_POST['trx_id'] ) ? sanitize_key( $_POST['trx_id'] ) : '',
			'trx_status' => isset( $_POST['trx_status'] ) ? sanitize_key( $_POST['trx_status'] ) : 'pending',
			'amount'     => isset( $_POST['amount'] ) ? intval( $_POST['amount'] ) : '',
			'status'     => isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : 'pending',
			'updated_at' => current_time( 'mysql' ),
		);

		$escapes = array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		$fields  = apply_filters( 'wpbkash_entry_update_fields', $fields );
		$escapes = apply_filters( 'wpbkash_entry_update_fields_escape', $escapes );

		$updated = wpbkash_entry_update( $entry_id, $fields, $escapes );

		if ( is_wp_error( $updated ) ) {
            wp_die( $updated->get_error_message() );
		}

		if ( $updated ) {
            $redirected_to = admin_url( 'admin.php?page=wpbkash&entry='.$entry_id.'&action=edit&entry-updated=true' );
        } else {
            $redirected_to = admin_url( 'admin.php?page=wpbkash&entry='.$entry_id.'&action=edit&entry-updated=false' );
        }

        wp_redirect( $redirected_to );
        exit;

	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {

		register_setting(
			'wpbkash_settings_group', // Option group
			'wpbkash_settings_fields', // Option name
			[ $this, 'sanitize' ] // Sanitize
		);

		add_settings_section(
			'setting_section_id',
			__( 'WPbKash Settings', 'wpbkash' ),
			[ $this, 'print_section_info' ],
			'wpbkash_settings'
		);

		add_settings_field(
			'testmode',
			__( 'Test Mode', 'wpbkash' ),
			[ $this, 'testmode' ],
			'wpbkash_settings',
			'setting_section_id'
		);

		add_settings_field(
			'app_key',
			__( 'App Key', 'wpbkash' ),
			[ $this, 'app_key' ],
			'wpbkash_settings',
			'setting_section_id'
		);
		add_settings_field(
			'app_secret',
			__( 'App Secret', 'wpbkash' ),
			[ $this, 'app_secret' ],
			'wpbkash_settings',
			'setting_section_id'
		);
		add_settings_field(
			'username',
			__( 'Username', 'wpbkash' ),
			[ $this, 'username' ],
			'wpbkash_settings',
			'setting_section_id'
		);
		add_settings_field(
			'password',
			__( 'Password', 'wpbkash' ),
			[ $this, 'password' ],
			'wpbkash_settings',
			'setting_section_id'
		);

	}

	/**
	 * Trigger option update just check bkash connection
	 * 
	 * @param string $old_value
	 * @param string $new_value
	 * 
	 * @return void
	 */
	public function trigger_on_update( $old_value, $new_value ) {
		if ( ! empty( $new_value ) && $new_value !== $old_value ) {

			$option = get_option( 'wpbkash_settings_fields' );

			if ( empty( $option ) || empty( $option['app_key'] ) || empty( $option['app_secret'] ) || empty( $option['username'] ) || empty( $option['password'] ) ) {
				update_option( 'wpbkash__connection', 'wrong' );
				return false;
			}

			$api = new Query( $option );
			$token = $api->check_bkash_token();
			if( !empty( $token ) && false !== $token ) {
				update_option( 'wpbkash__connection', 'ok' );
			} else {
				update_option( 'wpbkash__connection', 'wrong' );
			}
            
        }
	}


	/**
	 * Load settings page content
	 *
	 * @return void
	 */
	public function settings_page() {

		// Set class property
		$this->options = get_option( 'wpbkash_settings_fields' );
		?>
		 <div class="wrap">
			 <form method="post" id="wpbkash_settings_form" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields( 'wpbkash_settings_group' );
				do_settings_sections( 'wpbkash_settings' );
				submit_button();
				?>
			 </form>
		 </div>
		<?php
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input ) {
		$new_input = [];
		if ( isset( $input['testmode'] ) ) {
			$new_input['testmode'] = sanitize_text_field( $input['testmode'] );
		}
		
		if ( isset( $input['app_key'] ) ) {
			$new_input['app_key'] = sanitize_text_field( $input['app_key'] );
		}

		if ( isset( $input['app_secret'] ) ) {
			$new_input['app_secret'] = sanitize_text_field( $input['app_secret'] );
		}

		if ( isset( $input['username'] ) ) {
			$new_input['username'] = sanitize_text_field( $input['username'] );
		}

		if ( isset( $input['password'] ) ) {
			$new_input['password'] = sanitize_text_field( $input['password'] );
		}

		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {

		$connection = get_option('wpbkash__connection');
		
		esc_html_e( 'Setup your bKash app info and credentials.', 'wpbkash' );

		echo '<div class="wpbkash--mode">';
		if ( isset( $this->options['testmode'] ) && 1 == $this->options['testmode'] ) {
			echo '<h4>' . __( 'Testmode is enabled', 'wpbkash' ) . '</h4>';
		}
		if( isset( $this->options['app_key'] ) && isset( $this->options['app_secret'] ) ) {
			if( isset( $connection ) && !empty( $connection ) && 'ok' === $connection ) {
				echo '<div class="wpbkash--connection-signal">' . __( 'Connection Ok', 'wpbkash' ) . ' <span class="dashicons dashicons-yes-alt"></span></div>';
			} else {
				echo '<div class="wpbkash--connection-signal connection-failed">' . __( 'Connection Failed', 'wpbkash' ) . ' <span class="dashicons dashicons-dismiss"></span></div>';
			}
		}
		echo '</div>';
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function testmode() {
		?>
		<label for="testmode">
			<input type="checkbox" id="testmode" name="wpbkash_settings_fields[testmode]" value="1" 
			<?php
			if ( isset( $this->options['testmode'] ) && 1 == $this->options['testmode'] ) {
				echo 'checked="checked"';
			}
			?>
			 />
			<?php esc_html_e( 'Enable Test Mode', 'wpbkash' ); ?>
		</label>
		<?php
	}
	
	/**
	 * Get the settings option array and print one of its values
	 */
	public function app_key() {
		printf(
			'<input type="text" size="50" id="app_key" name="wpbkash_settings_fields[app_key]" value="%s" />',
			isset( $this->options['app_key'] ) ? esc_attr( $this->options['app_key'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function app_secret() {
		printf(
			'<input type="text" size="50" id="app_secret" name="wpbkash_settings_fields[app_secret]" value="%s" />',
			isset( $this->options['app_secret'] ) ? esc_attr( $this->options['app_secret'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function username() {
		printf(
			'<input type="text" size="50" id="username" name="wpbkash_settings_fields[username]" value="%s" />',
			isset( $this->options['username'] ) ? esc_attr( $this->options['username'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function password() {
		printf(
			'<input type="password" size="50" id="password" name="wpbkash_settings_fields[password]" value="%s" />',
			isset( $this->options['password'] ) ? esc_attr( $this->options['password'] ) : ''
		);
	}

	/**
	 * Main Settings Instance
	 *
	 * Ensures only one instance of Settings is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @see    WordPress_Plugin_Template()
	 * @return Main Settings instance
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}