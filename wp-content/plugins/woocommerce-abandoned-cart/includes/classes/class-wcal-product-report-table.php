<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will handle the Product Report Table.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/List-Class
 * @since 2.5.3
 */

if ( session_id() === '' ) {
	// session has not started.
	session_start();
}
// Load WP_List_Table if not loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Product Report Table Class.
 */
class WCAL_Product_Report_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.5.3
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.5.3
	 */
	public $base_url;

	/**
	 * Total number of products
	 *
	 * @var int
	 * @since 2.5.3
	 */
	public $total_count;

	/**
	 *  It will add the variable needed for the class.
	 *
	 * @see WP_List_Table::__construct()
	 * @since 2.5.3
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => __( 'product_id', 'woocommerce-abandoned-cart' ), // singular name of the listed records.
				'plural'   => __( 'product_ids', 'woocommerce-abandoned-cart' ), // plural name of the listed records.
				'ajax'     => false,                        // Does this table support ajax?
			)
		);
		$this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=stats' );
	}

	/**
	 * It will prepare the list of the Product reports, like columns, pagination, sortable column, all data
	 *
	 * @since 2.5.3
	 */
	public function wcal_product_report_prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns.
		$data                  = $this->wcal_product_report_data();
		$total_items           = $this->total_count;
		$this->items           = $data;
		$this->_column_headers = array( $columns, $hidden );
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,                     // WE have to calculate the total number of items.
				'per_page'    => $this->per_page,                      // WE have to determine how many items to show on a page.
				'total_pages' => ceil( $total_items / $this->per_page ),   // WE have to calculate the total number of pages.
			)
		);
	}

	/**
	 * It will add the columns product report list.
	 *
	 * @return array $columns All columns name.
	 * @since 2.5.3
	 */
	public function get_columns() {
		$columns = array(
			'product_name'     => __( 'Product Name', 'woocommerce-abandoned-cart' ),
			'abandoned_number' => __( 'Number of Times Abandoned', 'woocommerce-abandoned-cart' ),
			'recover_number'   => __( 'Number of Times Recovered', 'woocommerce-abandoned-cart' ),
		);
		return apply_filters( 'wcal_product_report_columns', $columns );
	}

	/**
	 * It will generate the product list data.
	 *
	 * @globals mixed $wpdb
	 * @return array $return_product_report_display Key and value of all the columns
	 * @since 2.5.3
	 */
	public function wcal_product_report_data() {
		global $wpdb;
		$wcal_class            = new woocommerce_abandon_cart_lite();
		$per_page              = $this->per_page;
		$i                     = 0;
		$order                 = 'desc';
		$blank_cart_info       = '{"cart":[]}';
		$blank_cart_info_guest = '[]';
		$blank_cart            = '""';

		$recover_query = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT abandoned_cart_time, abandoned_cart_info, recovered_cart FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s ORDER BY recovered_cart DESC', // phpcs:ignore
				"%$blank_cart_info%",
				$blank_cart_info_guest,
				$blank_cart
			)
		);
		$rec_carts_array       = array();
		$recover_product_array = array();
		$return_product_report = array();

		foreach ( $recover_query as $recovered_cart_key => $recovered_cart_value ) {
			$recovered_cart_info = json_decode( $recovered_cart_value->abandoned_cart_info );
			$recovered_cart_dat  = json_decode( $recovered_cart_value->recovered_cart );
			$cart_update_time    = $recovered_cart_value->abandoned_cart_time;
			$quantity_total      = 0;
			$cart_details        = new stdClass();
			if ( isset( $recovered_cart_info->cart ) ) {
				$cart_details = $recovered_cart_info->cart;
			}
			if ( count( get_object_vars( $cart_details ) ) > 0 ) {
				foreach ( $cart_details as $k => $v ) {
					$quantity_total = $quantity_total + $v->quantity;
				}
			}

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cart_update_time;
			if ( is_array( $recovered_cart_info ) || is_object( $recovered_cart_info ) ) {
				foreach ( $recovered_cart_info as $rec_cart_key => $rec_cart_value ) {
					foreach ( $rec_cart_value as $rec_product_id_key => $rec_product_id_value ) {
						$product_id = $rec_product_id_value->product_id;
						if ( $compare_time > $cut_off_time ) {
							$rec_carts_array [] = $product_id;
						}
						if ( 0 != $recovered_cart_dat ) { // phpcs:ignore
							$recover_product_array[] = $product_id;
						}
					}
				}
			}
		}

		$count              = array_count_values( $rec_carts_array );
		$count1             = $count;
		$count_new          = $wcal_class->bubble_sort_function( $count1, $order );
		$recover_cart       = '0';
		$count_css          = 0;
		$chunck_array       = array_chunk( $count_new, 10, true );  // keep True for retaing the Array Index number which is product ids in our case.
		$chunck_array_value = array();

		foreach ( $chunck_array as $chunck_array_key => $chunck_array_value ) {
			foreach ( $chunck_array_value as $k => $v ) {
				$return_product_report[ $i ] = new stdClass();
				$prod_name                   = get_post( $k );
				if ( null !== $prod_name || '' !== $prod_name ) {
					$product_name    = $prod_name->post_title;
					$abandoned_count = $v;
					$recover         = array_count_values( $recover_product_array );
					foreach ( $recover as $ke => $ve ) {
						if ( array_key_exists( $ke, $count ) ) {
							if ( $ke == $k ) { // phpcs:ignore
								$recover_cart = $ve;
							}
						}
						if ( ! array_key_exists( $k, $recover ) ) {
							$recover_cart = '0';
						}
					}

					$return_product_report[ $i ]->product_name     = $product_name;
					$return_product_report[ $i ]->abandoned_number = $abandoned_count;
					$return_product_report[ $i ]->recover_number   = $recover_cart;
					$return_product_report[ $i ]->product_id       = $k;
					$i++;
				}
			}
		}
		$this->total_count = count( $return_product_report ) > 0 ? count( $return_product_report ) : 0;

		// Pagination per page.
		if ( isset( $_GET['paged'] ) && sanitize_text_field( wp_unslash( $_GET['paged'] ) ) > 1 ) { // phpcs:ignore WordPress.Security.NonceVerification
			$page_number = sanitize_text_field( wp_unslash( $_GET['paged'] ) ) - 1; // phpcs:ignore WordPress.Security.NonceVerification
			$k           = $per_page * $page_number;
		} else {
			$k = 0;
		}
		$return_product_report_display = array();
		for ( $j = $k; $j < ( $k + $per_page ); $j++ ) {
			if ( isset( $return_product_report[ $j ] ) ) {
				$return_product_report_display[ $j ] = $return_product_report[ $j ];
			} else {
				break;
			}
		}
		return apply_filters( 'wcal_product_report_table_data', $return_product_report_display );
	}

	/**
	 * It will display the data for product column
	 *
	 * @param array | object $wcal_sent_emails All data of the list.
	 * @param stirng         $column_name Name of the column.
	 * @return string $value Data of the column.
	 * @since 2.5.3
	 */
	public function column_default( $wcal_sent_emails, $column_name ) {
		$value = '';
		switch ( $column_name ) {

			case 'product_name':
				if ( isset( $wcal_sent_emails->product_name ) ) {
					$value = "<a href= post.php?post=$wcal_sent_emails->product_id&action=edit title = product name > $wcal_sent_emails->product_name </a>";
				}
				break;

			case 'abandoned_number':
				if ( isset( $wcal_sent_emails->abandoned_number ) ) {
					$value = $wcal_sent_emails->abandoned_number;
				}
				break;

			case 'recover_number':
				if ( isset( $wcal_sent_emails->recover_number ) ) {
					$value = $wcal_sent_emails->recover_number;
				}
				break;
			default:
				$value = isset( $wcal_sent_emails->$column_name ) ? $wcal_sent_emails->$column_name : '';
				break;
		}

		return apply_filters( 'wcal_product_report_column_default', $value, $wcal_sent_emails, $column_name );
	}
}
