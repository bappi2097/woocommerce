<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\WPCF7;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WPCF7' ) ) {
	return;
}

/**
 * WPCF7 Initialize
 */
class Init {

	/**
	 * class initialize
	 */
	public static function init() {
		new WPCF7bKash();
		new WPCF7Loader();
		new Ajax();
	}
}
