<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Taxi_Plugin {

    protected static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konstruktor.
     */
    public function __construct() {
        // Initialisierungsaktionen
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Lädt die erforderlichen Dateien.
     */
    private function includes() {
        // Helferfunktionen, Shortcodes, AJAX-Handler etc.
        // require_once WP_TAXI_PLUGIN_DIR . 'includes/functions.php';
        require_once WP_TAXI_PLUGIN_DIR . 'includes/shortcodes/class-wp-taxi-auth-shortcodes.php';
        require_once WP_TAXI_PLUGIN_DIR . 'includes/shortcodes/class-wp-taxi-customer-dashboard-shortcode.php';
        require_once WP_TAXI_PLUGIN_DIR . 'includes/shortcodes/class-wp-taxi-driver-dashboard-shortcode.php';
        // require_once WP_TAXI_PLUGIN_DIR . 'includes/ajax/class-wp-taxi-ajax-handler.php';

        // Admin-spezifische Funktionen
        if ( is_admin() ) {
            require_once WP_TAXI_PLUGIN_DIR . 'admin/class-wp-taxi-admin.php';
        }
    }

    /**
     * Initialisiert die WordPress Hooks.
     */
    private function init_hooks() {
        // WordPress-Aktionen und Filter hier einhängen
        // add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        // add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Internationalisierung laden
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
    }

    /**
     * Lädt die Textdomain für die Übersetzung.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-taxi-plugin',
            false,
            dirname( plugin_basename( WP_TAXI_PLUGIN_DIR ) ) . '/languages/'
        );
    }

    /**
     * Enqueue Scripts und Styles für das Frontend.
     */
    public function enqueue_scripts() {
        // wp_enqueue_style( 'wp-taxi-frontend-style', WP_TAXI_PLUGIN_URL . 'assets/css/frontend.css', array(), WP_TAXI_PLUGIN_VERSION );
        // wp_enqueue_script( 'wp-taxi-frontend-script', WP_TAXI_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), WP_TAXI_PLUGIN_VERSION, true );
        // wp_localize_script( 'wp-taxi-frontend-script', 'wp_taxi_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    /**
     * Enqueue Scripts und Styles für das Backend.
     */
    public function enqueue_admin_scripts() {
        // wp_enqueue_style( 'wp-taxi-admin-style', WP_TAXI_PLUGIN_URL . 'assets/css/admin.css', array(), WP_TAXI_PLUGIN_VERSION );
        // wp_enqueue_script( 'wp-taxi-admin-script', WP_TAXI_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), WP_TAXI_PLUGIN_VERSION, true );
    }


    /**
     * Führt das Plugin aus.
     */
    public function run() {
        // Die Aktionen, die beim Laden des Plugins ausgeführt werden sollen.
        // Zum Beispiel: Initialisierung von Klassen, Registrierung von Shortcodes, etc.
        // if ( is_admin() ) {
        //     $admin = new WP_Taxi_Admin();
        // }

        // Shortcodes registrieren
        // add_shortcode( 'wp_taxi_booking_form', array( 'WP_Taxi_Booking_Form_Shortcode', 'output' ) );

        // AJAX Handler initialisieren
        // WP_Taxi_Ajax_Handler::init();
    }
}
?>
