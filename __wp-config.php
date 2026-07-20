<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'montenapoleokj01' );

/** Database username */
define( 'DB_USER', 'montenapoleokj01' );

/** Database password */
define( 'DB_PASSWORD', 'jyde-pult-Ay-8lag' );

/** Database hostname */
define( 'DB_HOST', 'montenapoleokj01.mysql.domeneshop.no' );

/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The database collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

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
define( 'AUTH_KEY',         'V$3{WA@t^~-M@9Z;<F929k?E?3>+__0$@9ss|HVGM;`3lE.(w2(Vyqa%)NvLW2![' );
define( 'SECURE_AUTH_KEY',  ':JiDc9tUnGhS7l&)-H]J3~3*)?!z{LV!TuS`Axs:-0g(:y9B_H&yWY&M_)] |t0p' );
define( 'LOGGED_IN_KEY',    ')W[3Lr&WnV_&E|67lf:%/G1XxsM+>OcH{$]Z`jzBneB.kpnJXL.%JHD1{I~1/CHG' );
define( 'NONCE_KEY',        '#:-[^Y;1,FbScj7.lDWm 4wy<MyLi`6Wh#9cd0>n]wE2l$N3#BfHR&wOjqSTOLnb' );
define( 'AUTH_SALT',        'lJH2t^hqo~|@*|f,~pJ}vn;hhsDk7T^pnY.oarT<+iEUprH,}<{`.] !N^d  y=T' );
define( 'SECURE_AUTH_SALT', 'E 90p`q@8`%N`>!tSu|(Vho(A{9CJb^R#ClTbR0o90lg($u/V;e|3g7TF$Q}p~7&' );
define( 'LOGGED_IN_SALT',   '#Sg3<M!@*$Rqi:HpQ6*mP:!4a(]A_VhS<T1VX^ X*2:=+Njp3$]g2JSUOz+zQIE&' );
define( 'NONCE_SALT',       'fVnT7]QlwWN[[dwjxpE.gy,e/LZ]p{kv4IQH@k2c0U#[FlRWPww=(<eus,#c.JnI' );

/**#@-*/

/**
 * WordPress database table prefix.
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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true);

define('WP_CACHE', true);
define('DOMAIN_CURRENT_SITE', 'www.montenapoleoneshirts.com');

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
