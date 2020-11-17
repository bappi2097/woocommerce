<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash;

class Deactivate
{
    /**
     * Deactivate initialize
     * 
     * @return void
     */
    public static function deactivate()
    {
        delete_transient('wpbkash_flush');
        flush_rewrite_rules();
    }

}
