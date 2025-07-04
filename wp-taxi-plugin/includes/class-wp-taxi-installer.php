<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Taxi_Installer {

    public static function install() {
        // Erstelle Benutzerrollen bei der Aktivierung
        self::create_roles();
        // Erstelle notwendige Datenbanktabellen
        self::create_tables();
        // Setze Standardoptionen
        self::set_default_options();

        // Flush rewrite rules, falls Custom Post Types oder Taxonomien registriert werden
        flush_rewrite_rules();
    }

    private static function create_roles() {
        // Rolle für Kunden
        add_role(
            'customer',
            __( 'Kunde', 'wp-taxi-plugin' ),
            array(
                'read' => true, // Grundlegende Leseberechtigung
                // Weitere Berechtigungen hier definieren
                'can_book_ride' => true,
            )
        );

        // Rolle für Fahrer
        add_role(
            'driver',
            __( 'Fahrer', 'wp-taxi-plugin' ),
            array(
                'read' => true, // Grundlegende Leseberechtigung
                // Weitere Berechtigungen hier definieren
                'can_accept_ride' => true,
                'edit_posts' => false, // Fahrer sollten keine Beiträge bearbeiten können, es sei denn, es ist gewollt
                'delete_posts' => false,
            )
        );

        // Admin-Rolle bekommt auch die neuen Berechtigungen (optional, aber oft nützlich für Tests)
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $admin_role->add_cap( 'can_book_ride' );
            $admin_role->add_cap( 'can_accept_ride' );
            // Hier könnten auch spezifische Admin-Caps für das Plugin hinzugefügt werden
            $admin_role->add_cap( 'manage_taxi_plugin_settings' );
        }
    }


    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'taxi_rides';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) UNSIGNED NOT NULL,
            driver_id bigint(20) UNSIGNED DEFAULT NULL,
            start_address text NOT NULL,
            end_address text NOT NULL,
            start_lat varchar(50) NOT NULL,
            start_lng varchar(50) NOT NULL,
            end_lat varchar(50) DEFAULT NULL,
            end_lng varchar(50) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending', /* möglichen Werte: pending, accepted, ongoing, completed, cancelled, (ggf. denied) */
            price decimal(10, 2) DEFAULT NULL,
            currency_symbol VARCHAR(10) DEFAULT 'CHF',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY driver_id (driver_id),
            KEY status (status)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // Weitere Tabellen könnten hier hinzugefügt werden, z.B. für Bewertungen, Fahrer-Dokumente etc.
    }



    private static function set_default_options() {
        // API Key wird beim ersten Mal mit dem aus dem Prompt befüllt, falls leer
        if (get_option('wp_taxi_plugin_google_maps_api_key') === false) {
             add_option( 'wp_taxi_plugin_google_maps_api_key', 'AIzaSyBbWGNbH7yEXc7mtQrwvQEPmghYfr9-glI' );
        }
        if (get_option('wp_taxi_plugin_base_fare') === false) {
            add_option( 'wp_taxi_plugin_base_fare', '5.00' );
        }
        if (get_option('wp_taxi_plugin_price_per_km') === false) {
            add_option( 'wp_taxi_plugin_price_per_km', '2.50' );
        }
        if (get_option('wp_taxi_plugin_currency_symbol') === false) {
            add_option( 'wp_taxi_plugin_currency_symbol', 'CHF' );
        }
        // Standard-Seiten-Slugs (optional, könnte man auch den Admin erstellen lassen und hier speichern)
        // add_option( 'wp_taxi_plugin_login_page_slug', 'anmelden');
        // add_option( 'wp_taxi_plugin_register_page_slug', 'registrierung');
        // add_option( 'wp_taxi_plugin_customer_dashboard_slug', 'kunden-dashboard');
        // add_option( 'wp_taxi_plugin_driver_dashboard_slug', 'fahrer-dashboard');
    }

}
?>
