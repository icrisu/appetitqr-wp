<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Plugin Name: AppetitQR - Digital QR Menus & Commission-Free Ordering for Restaurants
 * Description: Embed your AppetitQR digital menu on any WordPress page with a shortcode. Pulls your live menu, theme and colors straight from your AppetitQR account.
 * Version: 1.0.0
 * Plugin URI: https://appetitqr.com
 * Author URI: https://appetitqr.com/contact
 * Author: sakurapixel
 * License: GPL-3.0-only
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: appetitqr
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.2
 */

define( 'APPETITQR_APP_PUBLIC_URL', plugins_url( '', __FILE__ ) );
define( 'APPETITQR_APP_PATH', plugin_dir_path( __FILE__ ) );
define( 'APPETITQR_VERSION', '1.0.0' );

/**
 * Composer is optional here: the plugin ships no third-party PHP dependencies, so a
 * plain PSR-4 autoloader over app/ is enough. The vendor/ branch stays first so a
 * `composer install` (or a dist build that bundles one) keeps working unchanged.
 */
if ( file_exists( APPETITQR_APP_PATH . 'vendor/autoload.php' ) ) {
    require APPETITQR_APP_PATH . 'vendor/autoload.php';
} else {
    spl_autoload_register( function ( $class ) {
        $prefix = 'AppetitQR\\';
        $length = strlen( $prefix );
        if ( strncmp( $prefix, $class, $length ) !== 0 ) {
            return;
        }
        $relative = str_replace( '\\', '/', substr( $class, $length ) );
        $file     = APPETITQR_APP_PATH . 'app/' . $relative . '.php';
        if ( file_exists( $file ) ) {
            require $file;
        }
    } );
}

\AppetitQR\App::run( __FILE__ );
