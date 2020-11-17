<?php
/**
 * @package WPbKash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$entry_id = (int) $_GET['entry'];
$entry    = wpbkash_get_entry( $entry_id );
?>
<div class="wrap">
	<?php

	if ( ! isset( $entry ) || empty( $entry ) ) {
		echo '<h1>' . esc_html__( 'Not Entry Found', 'wpbkash' ) . '</h1>';
		return;
	}
	$sender  = $entry->sender;
	$user_id = '';
	if ( 'wc_order' === $entry->ref && ! is_email( $entry->sender ) ) {
		$user_info = get_userdata( (int) $entry->sender );
		$sender    = $user_info->user_email;
		$user_id   = $user_info->ID;
	}

	$created_date = $entry->created_at;
	$updated_date = $entry->updated_at;

	if ( isset( $created_date ) && ! empty( $created_date ) ) {
		$created_date = date( 'Y/m/d - g:i A', strtotime( $created_date ) );
	}
	if ( isset( $updated_date ) && ! empty( $updated_date ) ) {
		$updated_date = date( 'Y/m/d - g:i A', strtotime( $updated_date ) );
	}
	?>
	<h1 class="wp-heading-inline"><?php esc_html_e( 'View Entry Details', 'wpbkash' ); ?></h1>

	<a href="
	<?php
	echo add_query_arg(
		array(
			'entry'  => absint( $entry_id ),
			'action' => 'edit',
		)
	);
	?>
	" class="page-title-action"><?php esc_html_e( 'Edit', 'wpbkash' ); ?></a>
	<hr class="wp-header-end">

	<table class="form-table view-entry-table">
		<tbody>
			<tr>
				<th scope="row">
					<label><?php _e( 'Transaction ID', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo esc_html( strtoupper( $entry->trx_id ) ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( 'Merchant Invoice Number', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo ( property_exists($entry, 'invoice') ) ? esc_html( strtoupper( $entry->invoice ) ) : ''; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( 'Amount', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo esc_html( $entry->amount ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( 'Transaction Status', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo esc_html( $entry->trx_status ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( 'Entry Status', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo esc_html( $entry->status ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( 'Sender', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo esc_html( $sender ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( 'Sender User ID', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo esc_html( $user_id ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( 'Reference/Type', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo esc_html( $entry->ref ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( 'Reference ID', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo esc_html( $entry->ref_id ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( 'Created Date', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo esc_html( $created_date ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( 'Updated Date', 'wpbkash' ); ?></label>
				</th>
				<td>
					<?php echo esc_html( $updated_date ); ?>
				</td>
			</tr>
		</tbody>
	</table>

</div>
