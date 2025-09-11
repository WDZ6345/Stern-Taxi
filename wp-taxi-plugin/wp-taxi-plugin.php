<?php
/**
 * Plugin Name: WP Taxi Plugin
 * Description: Ein WordPress-Plugin für Taxibestellungen, ähnlich wie Uber.
 * Version: 0.1.0
 * Author: Jules
 * Author URI: https://uber.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-taxi-plugin
 * Domain Path: /languages
 */

// Sicherheitsabfrage: Direkten Zugriff auf die Datei verhindern
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin-Konstanten definieren
define( 'WP_TAXI_PLUGIN_VERSION', '0.1.0' );
define( 'WP_TAXI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_TAXI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Aktivierungs-Hook
register_activation_hook( __FILE__, 'wp_taxi_plugin_activate' );
function wp_taxi_plugin_activate() {
    // Hier Code einfügen, der bei der Plugin-Aktivierung ausgeführt werden soll
    // z.B. Erstellung von Datenbanktabellen, Standardeinstellungen setzen
    require_once WP_TAXI_PLUGIN_DIR . 'includes/class-wp-taxi-installer.php';
    WP_Taxi_Installer::install();
}

// Deaktivierungs-Hook
register_deactivation_hook( __FILE__, 'wp_taxi_plugin_deactivate' );
function wp_taxi_plugin_deactivate() {
    // Hier Code einfügen, der bei der Plugin-Deaktivierung ausgeführt werden soll
    // z.B. Aufräumarbeiten
}

// Haupt-Plugin-Klasse laden
require_once WP_TAXI_PLUGIN_DIR . 'includes/class-wp-taxi-plugin.php';

// Plugin initialisieren
function wp_taxi_plugin_run() {
    $plugin = new WP_Taxi_Plugin();
    $plugin->run();
}
add_action( 'plugins_loaded', 'wp_taxi_plugin_run' );

?>
