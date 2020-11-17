<?php
/**
 *
 * @package WPbKash
 * @since   1.0
 * @version 1.0
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-svg">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">

<?php wp_head(); ?>
</head>

<body <?php body_class( 'wpbkash-form-page' ); ?>>
<?php
if ( function_exists( 'wp_body_open' ) ) {
	wp_body_open();
};
?>

<div class="wpbkash--frontend-wrap">
	<div class="wpbkash--frontend-header">
		<div class="wpbkash--frontend-container">
			<div class="wpbkash--frontend-header-wrap">
				<?php
				$logo_id = get_theme_mod( 'custom_logo' );
				if ( ! empty( $logo_id ) ) :
					$image = wp_get_attachment_image_src( $logo_id, 'full' );
					?>
					<a class="wpbkash--logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><img src="<?php echo esc_url( $image[0] ); ?>" /></a>
				<?php else : ?>
					<a class="wpbkash--logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="wpbkash--frontend-main">
		<div class="wpbkash--frontend-container">
			<?php echo do_shortcode( '[wpbkash-form]' ); ?>
		</div>
	</div>

</div>

<?php wp_footer(); ?>

</body>
</html>
