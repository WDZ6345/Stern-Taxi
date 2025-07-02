<?php
/**
 * Plugin Name:       Auto Inserate
 * Plugin URI:        https://example.com/plugins/auto-inserate/
 * Description:       Verwalten und verkaufen Sie Autos einfach. Erweiterte Suche, Fahrzeugdaten, Lead-Erfassung, Galerie, Karten. Ideal für Autohändler.
 * Version:           1.0.0
 * Author:            Dein Name
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       auto-inserate
 * Domain Path:       /languages
 */

// Sicherheitsabfrage: Direkten Zugriff auf die Datei verhindern
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin-Konstanten definieren
define( 'AUTO_INSERATE_VERSION', '1.0.0' );
define( 'AUTO_INSERATE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AUTO_INSERATE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Die Hauptklasse des Plugins.
 */
final class Auto_Inserate {

    /**
     * Die einzige Instanz der Klasse.
     *
     * @var Auto_Inserate
     */
    private static $instance;

    /**
     * Stellt sicher, dass nur eine Instanz der Klasse existiert (Singleton).
     *
     * @return Auto_Inserate - Instanz
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Klonen ist nicht erlaubt.
     */
    private function __clone() {}

    /**
     * Unserializing ist nicht erlaubt.
     */
    public function __wakeup() {}

    /**
     * Konstruktor.
     */
    private function __construct() {
        $this->setup_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Definiert Konstanten, falls nicht bereits in der Hauptdatei geschehen.
     */
    private function setup_constants() {
        // Hier könnten weitere Konstanten definiert werden, falls nötig.
    }

    /**
     * Bindet die notwendigen Dateien ein.
     */
    private function includes() {
        require_once AUTO_INSERATE_PLUGIN_DIR . 'includes/class-auto-inserate-post-types.php';
        require_once AUTO_INSERATE_PLUGIN_DIR . 'includes/class-auto-inserate-meta-boxes.php';
        require_once AUTO_INSERATE_PLUGIN_DIR . 'public/class-auto-inserate-public.php';

        if ( is_admin() ) {
            require_once AUTO_INSERATE_PLUGIN_DIR . 'admin/class-auto-inserate-admin.php';
        }
    }

    /**
     * Initialisiert die WordPress Hooks.
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Post Types initialisieren
        $post_types = new Auto_Inserate_Post_Types();
        $post_types->init();

        // Admin- und Meta-Box-spezifische Hooks nur im Admin-Bereich initialisieren
        if ( is_admin() ) {
            $meta_boxes = new Auto_Inserate_Meta_Boxes();
            $meta_boxes->init();

            $admin_area = new Auto_Inserate_Admin();
            $admin_area->init();
        }

        // Public Hooks initialisieren (außerhalb von is_admin())
        $public_hooks = new Auto_Inserate_Public();
        $public_hooks->init();

        // Weitere Hooks hier hinzufügen
    }

    /**
     * Lädt die Textdomain für die Übersetzung.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'auto-inserate',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * Was passiert bei der Plugin-Aktivierung.
     */
    public static function activate() {
        // Stelle sicher, dass der Post Type registriert ist, bevor die Rewrite Rules geflusht werden.
        // Normalerweise wird 'init' vor 'activate_pluginname/pluginname.php' ausgeführt,
        // aber um sicherzugehen, rufen wir die CPT-Registrierung hier explizit auf,
        // falls sie für das Flushen benötigt wird.
        if (class_exists('Auto_Inserate_Post_Types')) {
            $post_types = new Auto_Inserate_Post_Types();
            $post_types->register_fahrzeug_post_type(); // Direkter Aufruf der Registrierungsmethode
        }
        flush_rewrite_rules();
    }

    /**
     * Was passiert bei der Plugin-Deaktivierung.
     */
    public static function deactivate() {
        // flush_rewrite_rules() ist hier auch gut, um Permalinks zu bereinigen.
        flush_rewrite_rules();
    }
}

/**
 * Die Hauptfunktion, die die Instanz von Auto_Inserate zurückgibt.
 *
 * @return Auto_Inserate
 */
function auto_inserate() {
    return Auto_Inserate::instance();
}

// Plugin starten
auto_inserate();

// Aktivierungs- und Deaktivierungs-Hooks
register_activation_hook( __FILE__, array( 'Auto_Inserate', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Auto_Inserate', 'deactivate' ) );
