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

	if ( isset( $_REQUEST ) && isset( $_REQUEST['entry-updated'] ) ) {

		$updated = sanitize_key( $_REQUEST['entry-updated'] );

		if ( $updated == 'true' ) {
			echo '<div id="message" class="notice notice-success is-dismissible">
                <p><strong>' . __( 'Entry updated.', 'wpbkash' ) . '</strong></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">' . __( 'Dismiss this notice.', 'wpbkash' ) . '</span></button>
            </div>';
		} else {
			echo '<div id="message" class="notice notice-error is-dismissible">
                <p><strong>' . __( 'ERROR: Something went wrong try again later.', 'wpbkash' ) . '</strong></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">' . __( 'Dismiss this notice.', 'wpbkash' ) . '</span></button>
            </div>';
		}

	}

	?>
	<h1 class="wp-heading-inline">#<?php echo esc_attr( $entry_id ); esc_html_e( ' Entry Edit Details', 'wpbkash' ); ?></h1>

	<a href="
	<?php
	echo add_query_arg(
		array(
			'entry'  => absint( $entry_id ),
			'action' => 'view',
		)
	);
	?>
	" class="page-title-action"><?php esc_html_e( 'View', 'wpbkash' ); ?></a>
	<hr class="wp-header-end">
	<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="trx_id"><?php _e( 'Transaction ID', 'wpbkash' ); ?></label>
					</th>
					<td>
						<input type="text" name="trx_id" id="trx_id" class="regular-text" value="<?php echo esc_attr( $entry->trx_id ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="trx_status"><?php _e( 'Transaction Status', 'wpbkash' ); ?></label>
					</th>
					<td>
						<select name="trx_status" id="trx_status">
							<option <?php selected( $entry->trx_status, 'pending' ); ?> value="pending"><?php esc_html_e( 'Pending', 'wpbkash' ); ?></option>
							<option <?php selected( $entry->trx_status, 'completed' ); ?> value="completed"><?php esc_html_e( 'Completed', 'wpbkash' ); ?></option>
							<option <?php selected( $entry->trx_status, 'failded' ); ?> value="failed"><?php esc_html_e( 'Failed', 'wpbkash' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="amount"><?php _e( 'Amount', 'wpbkash' ); ?></label>
					</th>
					<td>
						<input type="text" name="amount" id="amount" class="regular-text" value="<?php echo esc_attr( $entry->amount ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="status"><?php _e( 'Entry Status', 'wpbkash' ); ?></label>
					</th>
					<td>
						<select name="status" id="status">
							<option <?php selected( $entry->status, 'pending' ); ?> value="pending"><?php esc_html_e( 'Pending', 'wpbkash' ); ?></option>
							<option <?php selected( $entry->status, 'completed' ); ?> value="completed"><?php esc_html_e( 'Completed', 'wpbkash' ); ?></option>
							<option <?php selected( $entry->status, 'failded' ); ?> value="failed"><?php esc_html_e( 'Failed', 'wpbkash' ); ?></option>
						</select>
					</td>
				</tr>
				<?php do_action( 'wpbkash_entry_edit_fields', $entry ); ?>
			</tbody>
		</table>

		<?php wp_nonce_field( 'wpbkash_entry_edit', 'wpbkash_edit_nonce' ); ?>
		<input type="hidden" name="action" value="update_entry">
		<input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry_id ); ?>">
		<?php submit_button( __( 'Update Entry', 'wpbkash' ), 'primary', 'update_entry' ); ?>
	</form>


</div>
