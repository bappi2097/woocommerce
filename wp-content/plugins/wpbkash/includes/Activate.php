<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash;

class Activate
{
    /**
     * Activate initialize
     * 
     * @return void
     */
    public static function activate()
    {
        self::create_table();
        self::install();
        flush_rewrite_rules();
    }

    /**
     * Create DB Table
     * 
     * @return void
     */
    public static function create_table()
    {

        global $wpdb;

        $prev_version = get_option( 'wpbkash_version' );

        if ( version_compare( $prev_version, WPBKASH_VERSION, '!=' ) ) {

            $table = $wpdb->prefix . 'wpbkash';
      
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE `{$table}` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `trx_id` varchar(15) DEFAULT NULL,
                `trx_status` varchar(15) DEFAULT NULL,
                `sender` varchar(320) DEFAULT NULL,
                `ref` varchar(100) DEFAULT NULL,
                `ref_id` varchar(100) DEFAULT NULL,
                `invoice` varchar(100) DEFAULT NULL,
                `status` varchar(15) DEFAULT NULL,
                `amount` varchar(10) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                `key_token` varchar(150) DEFAULT NULL,
                `key_created` datetime DEFAULT NULL,
                `data` longtext,
                `form_data` longtext,
                PRIMARY KEY (`id`),
                KEY `trx_id` (`trx_id`)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

        }

    }

    /**
     * Update and store options when activated
     * 
     * @return void
     */
    public static function install()
    {
        set_transient( 'wpbkash_flush', 1, 60 );
        update_option( 'wpbkash_version', WPBKASH_VERSION );
        $installed = get_option('wpbkash_installed');
        if( ! $installed ) {
            update_option( 'wpbkash_installed', time() );
        }
    }
}
