<?php
/**
 * @package WPbKash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Themepaw\bKash\Admin\EntryTable;

?>

<div class="wrap">
    <h2><?php _e( 'Entries List', 'wpbkash' ); ?></h2>

    <form method="post">
        <input type="hidden" name="page" value="wpbkash_list_table">
        <?php
        $this->tabe_obj->prepare_items();
        $this->tabe_obj->views();
        $this->tabe_obj->search_box( 'search', 'search_id' );
        $this->tabe_obj->display();
        ?>
    </form>
</div>
