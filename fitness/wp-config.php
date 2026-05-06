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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'tashadev_WPLWX');

/** Database username */
define('DB_USER', 'tashadev_WPLWX');

/** Database password */
define('DB_PASSWORD', 'uWh^Hw^jkre.KyGK/');

/** Database hostname */
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
define('AUTH_KEY', '7483c97038cb7d86d1bba9aa8061f88a1a8940923c9e286a93ee0b1ca7de2ad4');
define('SECURE_AUTH_KEY', '6e4859727889ed0d29a70f6de8df2d397dcdc6544d671b8fba5f74c92c23ae6d');
define('LOGGED_IN_KEY', '8a72d7746f09bc015e81b4f18b7fa383481c60e3ec38d66a6e27284024acb59e');
define('NONCE_KEY', '0bcbb8928c60e1e625a3d484d4fedf32bca30d861f5a87aef74f53dda8765d52');
define('AUTH_SALT', '9f52f59a371b8fa10906a14e8f30b9fe522cbe55440afbd94318eeec25857861');
define('SECURE_AUTH_SALT', '0d6d9bd89a9e4e52cd3806cca8ba8fb5a0dfb14e005d940bca907a2e98ec3e00');
define('LOGGED_IN_SALT', '0f588da277a5d1f7cc5399576194482554c0e4f422563c67260f2356b9244654');
define('NONCE_SALT', '19678f93b221d6fc8bd5ac8b401fc9739e264d45cb7a776f2f4afd4e6a6478a7');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'Edi_';
define('WP_CRON_LOCK_TIMEOUT', 120);
define('AUTOSAVE_INTERVAL', 300);
define('WP_POST_REVISIONS', 20);
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

define(‘DISALLOW_FILE_EDIT’, true);


