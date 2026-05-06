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
define('DB_NAME', 'tashadev_WPT9M');

/** MySQL database username */
define('DB_USER', 'tashadev_WPT9M');

/** MySQL database password */
define('DB_PASSWORD', '5iZJT7_.PU]Q$u{S{');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define('AUTH_KEY', '41fd95926714f8ddbd9dc209d46eac4d4e752317922f52509a9d0dc8d08d54d1');
define('SECURE_AUTH_KEY', '72ed6c40a2cd6994e6d6ed370422a69af215bb5ed40c363bd205863a2df2c300');
define('LOGGED_IN_KEY', 'bda0ffd670d423d6bfdd531626fe732e143da34e80ad2219de4ebfe3938b91df');
define('NONCE_KEY', '513bae69371dd1f9b13c9604981ad0a3eccf79f1da4c9133a7c68e230c5f0567');
define('AUTH_SALT', '794cafc0d029d6b7f819b0db28932c4a4d0910ec34d87fcd2df6260bd4634dbc');
define('SECURE_AUTH_SALT', 'd4c1d5d1e29626e1dc6c3051e0ad1bcba3d1dee306ca04e01548950f779ceed7');
define('LOGGED_IN_SALT', 'bb845dc7fc6f696911d5f0c26a19b9ac2d068633da244b4fe01a9f14c2d646b8');
define('NONCE_SALT', 'a5a43ccb60738d43dd6f11e44d2532e02f8b17d7acb011c1c0095e46fc3e48d5');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'N0J_';
define('WP_CRON_LOCK_TIMEOUT', 120);
define('AUTOSAVE_INTERVAL', 300);
define('WP_POST_REVISIONS', 5);
define('EMPTY_TRASH_DAYS', 7);
define('WP_AUTO_UPDATE_CORE', true);

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

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
