<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}



/**
 * List table class
 */
class EntryTable extends \WP_List_Table {

	function __construct() {
		parent::__construct(
			[
				'singular' => 'entry',
				'plural'   => 'entries',
				'ajax'     => false,
			]
		);
	}

	function get_table_classes() {
		return [ 'widefat', 'fixed', 'striped', $this->_args['plural'] ];
	}

	/**
	 * Message to show if no designation found
	 *
	 * @return void
	 */
	function no_items() {
		_e( 'No entry found', 'wpbkash' );
	}

	/**
	 * Default column values if no callback found
	 *
	 * @param  object $item
	 * @param  string $column_name
	 *
	 * @return string
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return $item->id;

			case 'trx_id':
				return $item->trx_id;

			case 'invoice':
				return ( property_exists($item, 'invoice') ) ? $item->invoice : '';

			case 'sender':
				return $item->sender;

			case 'status':
				return $item->status;

			case 'ref':
				return $item->ref;

			case 'date':
				return $item->created_at;

			default:
				return isset( $item->$column_name ) ? $item->$column_name : '';
		}
	}

	/**
	 * Get the column names
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'     => '<input type="checkbox" />',
			'id'     => __( 'Entry', 'wpbkash' ),
			'trx_id' => __( 'Transaction ID', 'wpbkash' ),
			'invoice' => __( 'Invoice No', 'wpbkash' ),
			'sender' => __( 'Sender', 'wpbkash' ),
			'status' => __( 'Status', 'wpbkash' ),
			'ref'    => __( 'Type', 'wpbkash' ),
			'date'   => __( 'Date', 'wpbkash' ),

		];

		return $columns;
	}

	/**
	 * Render the designation id column
	 *
	 * @param  object $item
	 *
	 * @return string
	 */
	function column_id( $item ) {

		$actions           = [];
		$actions['edit']   = sprintf(
			'<a href="%s" data-id="%d" title="%s">%s</a>',
			add_query_arg(
				[
					'entry'  => absint( $item->id ),
					'action' => 'edit',
				]
			),
			$item->id,
			__( 'Edit this item', 'wpbkash' ),
			__( 'Edit', 'wpbkash' )
		);
		$actions['view']   = sprintf(
			'<a href="%s" data-id="%d" title="%s">%s</a>',
			add_query_arg(
				[
					'entry'  => absint( $item->id ),
					'action' => 'view',
				]
			),
			$item->id,
			__( 'Edit this item', 'wpbkash' ),
			__( 'View', 'wpbkash' )
		);
		$actions['delete'] = sprintf(
			'<a href="%s" class="submitdelete" data-id="%d" title="%s">%s</a>',
			add_query_arg(
				[
					'entry'  => absint( $item->id ),
					'action' => 'delete',
				]
			),
			$item->id,
			__( 'Delete this item', 'wpbkash' ),
			__( 'Delete', 'wpbkash' )
		);

		return sprintf(
			'<a href="%1$s"><strong>#%2$s Entry</strong></a> %3$s',
			add_query_arg(
				[
					'entry'  => absint( $item->id ),
					'action' => 'view',
				]
			),
			$item->id,
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render the designation date column
	 *
	 * @param  object $item
	 *
	 * @return string
	 */
	function column_date( $item ) {
		echo __( 'Created at', 'wpbkash' ) . '<br />';

		$t_time    = date( 'Y/m/d g:i:s a', time() );
		$time      = strtotime( $item->created_at );
		$time_diff = time() - $time;

		if ( $time && $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			/* translators: %s: Human-readable time difference. */
			$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
		} else {
			$h_time = date( 'Y/m/d', strtotime( $item->created_at ) );
		}

		echo '<span title="' . esc_attr( $item->created_at ) . '">' . esc_html( $h_time ) . '</span>';
	}

	/**
	 * Render the designation sender column
	 *
	 * @param  object $item
	 *
	 * @return string
	 */
	function column_sender( $item ) {

		$sender = $item->sender;
		if ( 'wc_order' === $item->ref && ! is_email( $item->sender ) ) {
			if ( function_exists( 'wc_get_order' ) && ! empty( $item->ref_id ) ) {
				$order = wc_get_order( (int) $item->ref_id );
				if ( is_object( $order ) ) {
					$sender  = '';
					$user_id = $order->get_user_id();
					if ( isset( $user_id ) && wpbkash_user_exist( (int) $user_id ) ) {
						$user_info = get_userdata( (int) $user_id );
						if ( is_object( $user_info ) ) {
							$sender .= __( 'Username: ', 'wpbkash' ) . esc_html( $user_info->user_login ) . '<br />';
						}
					}
					$userid  = ( isset( $user_id ) && wpbkash_user_exist( (int) $user_id ) ) ? $user_id : '';
					$sender .= '<span title="User ID : ' . esc_attr( $userid ) . '">' . esc_html( $order->get_billing_email() ) . '</span>';
				}
			}
		}
		echo wp_kses_post( $sender );
	}

	/**
	 * Get sortable columns
	 *
	 * @return array
	 */
	function get_sortable_columns() {
		$sortable_columns = [
			'id' => [ 'id', true ],
		];

		return $sortable_columns;
	}

	/**
	 * Set the bulk actions
	 *
	 * @return array
	 */
	function get_bulk_actions() {
		$actions = [
			'delete' => __( 'Delete', 'wpbkash' ),
		];
		return $actions;
	}

	public function process_bulk_action() {

		// security check!
		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

			$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( 'Nope! Security check failed!' );
			}
		}

		$action = $this->current_action();
		$count  = 0;

		$deleted_ids = ( isset( $_REQUEST['entry_id'] ) && ! empty( $_REQUEST['entry_id'] ) ) ? (array) $_REQUEST['entry_id'] : '';
		$entry_id    = ( isset( $_GET['entry'] ) && ! empty( $_GET['entry'] ) ) ? (int) $_GET['entry'] : '';
		$delete_id   = [];

		switch ( $action ) {

			case 'delete':
				if ( ! empty( $deleted_ids ) ) {
					foreach ( $deleted_ids as $id ) {
						$delete_id[] = wpbkash_delete_entry( (int) $id );
					}
				} elseif ( ! empty( $entry_id ) ) {
					$delete_id[] = wpbkash_delete_entry( (int) $entry_id );
				}
				if ( ! empty( $delete_id ) ) {
					$class   = 'notice notice-success';
					$message = sprintf( __( '%s Entry has been deleted.', 'wpbkash' ), count( $delete_id ) );
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
				}
				break;
		}

		return;
	}

	 /**
	  * Set the views
	  *
	  * @return array
	  */
	protected function get_views() {
		$status_links = [];
		$base_link    = admin_url( 'admin.php?page=wpbkash' );
		$counts       = [
			'all'       => __( 'All', 'wpbkash' ),
			'completed' => __( 'Completed', 'wpbkash' ),
			'pending'   => __( 'Pending', 'wpbkash' ),
			'failed'    => __( 'Failed', 'wpbkash' ),
		];
		foreach ( $counts as $key => $value ) {
			$number               = wpbkash_get_count( $key );
			$class                = ( $key == $this->page_status ) ? 'current' : 'status-' . $key;
			$status_links[ $key ] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>', add_query_arg( array( 'status' => $key ), $base_link ), $class, $value, $number );
		}

		return $status_links;
	}

	/**
	 * Render the checkbox column
	 *
	 * @param  object $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="entry_id[]" value="%d" />',
			$item->id
		);
	}

	/**
	 * Prepare the class items
	 *
	 * @return void
	 */
	function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();
		$this->process_bulk_action();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$per_page     = $this->get_items_per_page( 'entry_per_page', 20 );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// only ncessary because we have sample data
		$args = [
			'offset' => intval( $offset ),
			'number' => intval( $per_page ),
		];

		if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
			$args['orderby'] = sanitize_key( $_REQUEST['orderby'] );
			$args['order']   = sanitize_key( $_REQUEST['order'] );
		}

		// check if a search was performed.
		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = wp_unslash( trim( $_REQUEST['s'] ) );
		}

		if ( isset( $_GET['status'] ) && ! empty( $_GET['status'] ) ) {
			$args['status'] = trim( $_GET['status'] );
		}

		$this->items = wpbkash_get_all_entry( $args );

		$this->set_pagination_args(
			[
				'total_items' => wpbkash_get_entry_count(),
				'per_page'    => $per_page,
			]
		);
	}
}
