<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'woocommerce' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'wf_YmPu}zdKtAy2wG ;1hf:94Uc=qt{4{TE:nHMVQnPI?!Sot8SJhWf .s~n72]5' );
define( 'SECURE_AUTH_KEY',  '[+<Xs2WL(K+ @d1P*Dw-,!(:-92Rr6QOdo0.|{pm z?#*5s9M+6CaY+Qp;9_5~d|' );
define( 'LOGGED_IN_KEY',    '1 K~NTc?M4Y0xD:5.x#ZaNIKM@P#~L)CpQ,QEl(KLIZd:7oPd(tVe~e $DH:Z#[w' );
define( 'NONCE_KEY',        '}@gOaz+Uf?2~F,+${.jcaY{SAHpi~!]bLM%5+Tefi9!1:$8Hi26jgfvzbtr#QMU^' );
define( 'AUTH_SALT',        'J(>:O~Znz31(u~Q^+8`)COn<P;KpQ.pPpOb>P%#0CX*BrTHe&~>e>[`[p0?>TB>=' );
define( 'SECURE_AUTH_SALT', 'j;(S$exvH]ZZGqf5@L>3g?uv4XF0if2w!;&_6g81aa b}nnEk6BdOG$%!|E)bfE.' );
define( 'LOGGED_IN_SALT',   '4TE?2R40Y4Is_>4-I&(B_SP1IfSc-&Kb?{]NGXdEH/`k2I_y=p>{^#!I/V_ZEmT.' );
define( 'NONCE_SALT',       'g$|-5/Thtw~x`SJ1;Z[KKZI+CKKL%=-Jtm(rM3b~E`@r;u$k41?KY^f<7(>-fuGd' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
