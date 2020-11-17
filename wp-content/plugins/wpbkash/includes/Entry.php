<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Entry class for fetch entry Data
 */
class Entry {

	/**
	 * setup ID
	 */
	private $id;

	/**
	 * Table name
	 */
	public $db_table;

	/**
	 * store $wpdb
	 */
	public $wp_db;

	/**
	 * initialize
	 */
	public function __construct( $id ) {
		global $wpdb;

		$this->db_table = $wpdb->prefix . 'wpbkash';
		$this->wp_db    = $wpdb;

		$this->id = (int) $id;
	}

	/**
	 * Get the id
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Check if id is exists or valid
	 */
	public function is_exists() {
		$result = $this->wp_db->get_row( $this->wp_db->prepare( "SELECT id FROM $this->db_table WHERE id='%d'", absint( $this->id ) ) );
		if ( isset( $result ) && isset( $result->id ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get meta value by meta name
	 *
	 * @param String $meta column name for wpbkash table
	 */
	public function get_meta( $meta ) {

		if ( ! $this->is_exists() ) {
			return;
		}

		$result = $this->wp_db->get_row( $this->wp_db->prepare( "SELECT $meta FROM $this->db_table WHERE id='%d'", absint( $this->id ) ) );
		return $result->$meta;

	}

	/**
	 * Get std class object for single entry
	 */
	function get_details() {

		if ( ! $this->is_exists() ) {
			return;
		}

		$result = $this->wp_db->get_row( $this->wp_db->prepare( "SELECT * FROM $this->db_table WHERE id='%d'", absint( $this->id ) ) );
		return $result;
	}

	/**
	 * Get entry status
	 */
	public function get_status() {
		return $this->get_meta( 'status' );
	}

	/**
	 * Get entry amount
	 */
	public function get_amount() {
		return $this->get_meta( 'amount' );
	}

	/**
	 * Get entry Transection ID
	 */
	public function get_trx_id() {
		return $this->get_meta( 'trx_id' );
	}
	
	/**
	 * Get entry Transection ID
	 */
	public function get_invoice() {
		return $this->get_meta( 'invoice' );
	}

	/**
	 * Get entry Transection Status
	 */
	public function get_trx_status() {
		return $this->get_meta( 'trx_status' );
	}

	/**
	 * Get entry sender
	 */
	public function get_sender() {
		return $this->get_meta( 'sender' );
	}

	/**
	 * Get entry referral
	 */
	public function get_ref() {
		return $this->get_meta( 'ref' );
	}

	/**
	 * Get entry referral id
	 */
	public function get_ref_id() {
		return $this->get_meta( 'ref_id' );
	}

	/**
	 * Get entry created date and time
	 */
	public function get_created_at() {
		return $this->get_meta( 'created_at' );
	}

	/**
	 * Get entry updated date and time
	 */
	public function get_updated_at() {
		return $this->get_meta( 'updated_at' );
	}

	/**
	 * Get entry key token
	 */
	public function get_key_token() {
		return $this->get_meta( 'key_token' );
	}

	/**
	 * Get entry key token created date and time
	 */
	public function get_key_created() {
		return $this->get_meta( 'key_created' );
	}

	/**
	 * Get entry serialize data that return from bkash
	 */
	public function get_data() {
		return $this->get_meta( 'data' );
	}

	/**
	 * Get entry serialize data that return from bkash
	 */
	public function ge_redirect_url() {
		return '';
	}

	/**
	 * Update entry data
	 */
	public static function update( $entry_id, $fields, $escapes ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wpbkash';

		$updated = $wpdb->update(
			$table,
			$fields,
			array(
				'id' => absint( $entry_id ),
			),
			$escapes
		);

		return $updated;
	}
}