<?php
/**
 * Short description for file
 *
 * @package    WPbKash
 * @author     themepaw <themepaw@gmail.com>
 * @author     mlimon <mlimonbd@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GPLv2 Or Later
 * @version    0.1
 */

/**
 * Get all entry
 *
 * @param array $args
 * @return object
 */
function wpbkash_get_all_entry( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'number'  => 20,
		'offset'  => 0,
		'orderby' => 'id',
		'status'  => '',
		's'       => '',
		'order'   => 'DESC',
	);

	$table = $wpdb->prefix . 'wpbkash';

	$args      = wp_parse_args( $args, $defaults );
	$cache_key = 'entry-all';
	$items     = wp_cache_get( $cache_key, 'wpbkash' );

	if ( false === $items ) {

		$sql = "SELECT * FROM $table";

		if ( ! empty( $args['s'] ) ) {
			$search = esc_sql( $args['s'] );
			$sql   .= " WHERE trx_id LIKE '%{$search}%'";
			$sql   .= " OR sender = '{$search}'";
			$sql   .= " OR status = '{$search}'";
			$sql   .= " OR invoice = '{$search}'";
			$sql   .= " OR ref = '{$search}'";
		}

		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$status = esc_sql( $args['status'] );
			$sql   .= " WHERE status = '{$status}'";
		}

		if ( ! empty( $args['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $args['orderby'] );
			$sql .= ! empty( $args['order'] ) ? ' ' . esc_sql( $args['order'] ) : ' ASC';
		}

		$sql .= ' LIMIT ' . esc_sql( $args['offset'] ) . '';
		$sql .= ', ' . esc_sql( $args['number'] ) . '';

		$items = $wpdb->get_results( $sql );

		wp_cache_set( $cache_key, $items, 'wpbkash' );
	}

	return $items;
}

/**
 * Get entry count
 *
 * @param string $status
 *
 * @return int
 */
function wpbkash_get_count( $status = '' ) {

	global $wpdb;

	$table = $wpdb->prefix . 'wpbkash';

	$sql = "SELECT count(id) FROM $table";

	if ( ! empty( $status ) && 'all' !== $status ) {
		$status = esc_sql( $status );
		$sql   .= " WHERE status = '{$status}'";
	}

	$count = $wpdb->get_var( $sql );

	return $count;
}

/**
 * Fetch all entry from database
 *
 * @return array
 */
function wpbkash_get_entry_count() {
	global $wpdb;

	return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wpbkash' );
}

/**
 * Fetch a single entry from database
 *
 * @param int $id
 *
 * @return array
 */
function wpbkash_get_entry( $id = 0 ) {
	global $wpdb;

	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'wpbkash WHERE id = %d', $id ) );
}


/**
 * Does this user exist?
 *
 * @param  int|string|WP_User $user_id User ID or object.
 * @return bool                        Whether the user exists.
 */
function wpbkash_user_exist( $user_id = '' ) {
	if ( $user_id instanceof WP_User ) {
		$user_id = $user_id->ID;
	}
	return (bool) get_user_by( 'id', $user_id );
}

/**
 * Delete Entry
 *
 * @param  int $entry_id
 * @return int|false The number of rows updated, or false on error.
 */
function wpbkash_delete_entry( $entry_id ) {
	global $wpdb;

	return $wpdb->delete( $wpdb->prefix . 'wpbkash', [ 'id' => absint( $entry_id ) ], [ '%d' ] );
}

/**
 * Get entry by order as referenace type
 *
 * @param int    $order_id
 * @param string $type reference type
 *
 * @return NULL|int
 */
function wpbkash_get_id( $order_id, $type = 'wc_order' ) {
	global $wpdb;

	$result = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}wpbkash WHERE ref='%s' AND ref_id='%d'", sanitize_text_field( $type ), absint( $order_id ) ) );
	if ( isset( $result ) && isset( $result->id ) ) {
		return $result->id;
	}
	return $result;
}

/**
 * Generat uniq key
 */
function wpbkash_get_payout_key()
{
    $str = rand(); 
    $hashed = md5($str);
    return $hashed;
}

/**
 * Generate unique payour url
 * 
 * @param string $email
 * @param string $key
 * @param int    $id
 * 
 * @return string
 */
function wpbkash_get_payout_url($email, $key, $id)
{
    $url = add_query_arg(array( 'key' => $key, 'email' => $email ), home_url("wpbkash-api/{$id}/"));
    return $url;
}

/**
 * Entry Update
 *
 * @param int $entry_id
 * @param array $fields
 * @param array $escapes
 * 
 * @return int|false
 */
function wpbkash_entry_update( $entry_id, $fields, $escapes ) {
	global $wpdb;

	$table = $wpdb->prefix . 'wpbkash';

	$updated = $wpdb->update(
		$table,
		$fields,
		[ 'id' => absint( $entry_id ) ],
		$escapes
	);

	return $updated;
}


/**
 * Default email text
 */
function wpbkash_pay_default_template()
{
    /* translators: Do not translate Shortcode like [wpbkash-sitename], [wpbkash-paymenturl], [wpbkash-siteurl]; those are placeholders. */
    $email_text = __('Dear [your-name],

Please click on the following link to verify your email address and to proceed with the payment. Note that without the email verification and payment – your registration will not be completed for the event. You will need to verify your email address within 10 minutes.

Click on the link bellow to verify your email address and pay:

[wpbkash-paymenturl]
Amount: [wpbkash-amount]

This is an auto generated email and please do not reply to this email. If you have any question, please write to [wpbkass-admin]

Regards,
All at [wpbkash-sitename]
Tel: +880 0000-00000, 
[wpbkash-siteurl]', 'wpbkash' 
    );

    $email_text = apply_filters('wpbkash_pay_use_html', $email_text);
    return $email_text;
}


/**
 * Default email text
 */
function wpbkash_confirmation_default_template()
{
    /* translators: Do not translate Shortcode like [wpbkash-sitename], [wpbkash-paymenturl], [wpbkash-siteurl]; those are placeholders. */
    $email_text = __( 'Dear [your-name],

Congratulations!

You are now successfully registered for the Dhaka Half Marathon 2020. Please take a print of this email and keep it for collecting your t-shirt and running bib. Your registration details are as follows:

Registration Details:

Full Name: [your-name]
Email: [your-email]
Transaction ID: [wpbkash-amount]
Registration ID:
Blah blah .....

We wish you all the best for the event.

Regards,
All at [wpbkash-sitename]
Tel: +880 0000-00000, 
[wpbkash-siteurl]

This is an auto generated email and please do not reply to this email. If you have any question, please write to contact@example.com', 'wpbkash' 
    );

    $email_text = apply_filters('wpbkash_confirm_use_html', $email_text);
    return $email_text;
}

/**
 * Generate unique merchant invoice id.
 */
function wpbkash_get_invoice() {
	$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
	$invoice = [];
	$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
	for ($i = 0; $i < 11; $i++) {
		$n = rand(0, $alphaLength);
		$invoice[] = $alphabet[$n];
	}
	$invoice = implode($invoice); //turn the array into a string

	// make user_login unique so WP will not return error
    $check = wpbkash_invoice_exists($invoice);
    if (!empty($check)) {
        $suffix = 1;
        while (!empty($check)) {
            $unique_invoice = $invoice . $suffix;
            $check = wpbkash_invoice_exists($unique_invoice);
            $suffix++;
        }
        $invoice = $unique_invoice;
    }

    return $invoice;
}

/**
 * Check invoice exists or not
 */
function wpbkash_invoice_exists($invoice) {

	$iargs = array(
		'post_type'  => 'shop_order',
		'meta_query' => array(
			array(
				'key'     => 'wpbkash_invoice',
				'compare' => $invoice
			),
		)
	);

	$order_list = new WP_Query( $iargs );
	if ( $order_list->have_posts() ) {
		return true;
	}

	return false;
}