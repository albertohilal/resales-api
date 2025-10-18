<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dbypu6n7ps78gy' );

/** Database username */
define( 'DB_USER', 'ujijie4ez0ydh' );

/** Database password */
define( 'DB_PASSWORD', 'elgeneral2018' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
define('RESALES_API_P1',    '1035049');
define('RESALES_API_P2',    '5df9cb0f7ad59c80f11ec3c2d4c17f105aaf8918');
define('RESALES_API_APIID', '65503'); // o usa AGENCY_FILTERID si trabajas con alias
define('RESALES_API_LANG',  '1');     // EN


/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'nIh/F5?VgFKb@lj:!lcgdou/@L58GaA=i>heL+Pp9i-GmPtNMA,o=zg(*;L?%rLQ' );
define( 'SECURE_AUTH_KEY',  'U}ulXRi`96UnO@,CGP}|^Iz )P}N1anL>5Yw*2qKP|>=85(.8Wyy>>1#dhwo3Rna' );
define( 'LOGGED_IN_KEY',    '*q`L)^Um2 6C(ZxXlP6Ea=(0Yl@]a>94aVsGY*lBB$dnl|{Jt7Cw`$!?b]_<j:=S' );
define( 'NONCE_KEY',        '~3-q_-u^Pt`2nsh{s5;?NR`evjE[]=E*jwasXfixM<$u0dG3xukes]x!pkwA5V(j' );
define( 'AUTH_SALT',        '6z$bOkCMD5EyzkR$ptlOdOuaVq~L(?DZ7*{|?WNi0K:K22j5Q4I;K2gKMZrX1nQX' );
define( 'SECURE_AUTH_SALT', 'fDST?Ha gU;ML_s=:LM*3$l.^5@cfFa4Hx4z(f}f)zwkteoj^aea%WiU$o)LssR[' );
define( 'LOGGED_IN_SALT',   '@&PN<MaVeG+_/T!52hJ|>FMI8P6f3crn>kLyVc/H*rb5qUu21/LOwNWZ>:<vq&P-' );
define( 'NONCE_SALT',       ']a-J*4/wWLoruYiw}1=BJ[LcZEd5k_1==x9`C]Do5TuBM/>4Pq_ZfB__zJfBL}3^' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'lg_';

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', true );

/* Add any custom values between this line and the "stop editing" line. */


define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
define('DISABLE_WP_CRON', true);


/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
