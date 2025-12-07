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
define('DB_NAME', getenv('RAILWAY_DB_NAME'));
define('DB_USER', getenv('RAILWAY_DB_USER'));
define('DB_PASSWORD', getenv('RAILWAY_DB_PASSWORD'));
define('DB_HOST', getenv('RAILWAY_DB_HOST'));// . ':' . getenv('RAILWAY_DB_PORT'));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
define('SENTRY_DSN', getenv('SENTRY_DSN'));

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
define( 'AUTH_KEY',         '~dg}5LQI<I5n4o$.,ergE|VyiDc&{$*{8y3hJ.b< OAh;hn/l Ccgdj1H f5S^P#' );
define( 'SECURE_AUTH_KEY',  '*kt]!`qdlCOTxPGsUcT,{X/qIY2jy}I#v7_tG1D_r~{2yOcwms%*2Dr}DVpW,v8h' );
define( 'LOGGED_IN_KEY',    'wjBh(T$AADX319AakpH060<[GKt.$J_(#@1mGWMa=QE}g*1l1F$Fklh|$8(EJ(6W' );
define( 'NONCE_KEY',        '4@Io~[,(<t?TG/7Y[DWEX}>gqq,C9Cg>R)OIX#-4&BdO/`EnzJeG%Mw-5=8nrp_k' );
define( 'AUTH_SALT',        'CCP9Ze~s!Rajt(Vp+TI@(nO5|vYcJphtM%I.aPxAUnHYx`N92Li63r@JfQHy&?Ol' );
define( 'SECURE_AUTH_SALT', '6LGN2[Pt}9 HX.YV5^;_$A<0l2={(0F&jue`p%YO[IoQ*dbT)ErON+GVlq<&j6R!' );
define( 'LOGGED_IN_SALT',   'rd-~LG`ILr&S!c%/j9!^SC(s.>ub(UGuDB~D^86!N{Xl&5!#XJ6)q!*Zips[-kRY' );
define( 'NONCE_SALT',       'I*1ADdA3wbgT$J[L*8{8emcrlO7Yo>kZT0aI*m|rx,zX:B`x<i=L}:&QjaU( /Go' );

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

define('WP_HOME', 'https://wordpress-production-d083.up.railway.app');
define('WP_SITEURL', 'https://wordpress-production-d083.up.railway.app');


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
