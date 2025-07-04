<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Taxi_Admin {

    private static $google_maps_api_key = '';

    public function __construct() {
        self::$google_maps_api_key = get_option('wp_taxi_plugin_google_maps_api_key', 'AIzaSyBbWGNbH7yEXc7mtQrwvQEPmghYfr9-glI');

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // AJAX Handler für Admin Aktionen
        add_action( 'wp_ajax_wp_taxi_admin_approve_driver', array( $this, 'ajax_admin_approve_driver' ) );
        add_action( 'wp_ajax_wp_taxi_admin_get_live_drivers', array( $this, 'ajax_admin_get_live_drivers' ) );

        // Laden der Unterseiten-Handler
        require_once WP_TAXI_PLUGIN_DIR . 'admin/partials/class-wp-taxi-admin-drivers-table.php';
        require_once WP_TAXI_PLUGIN_DIR . 'admin/partials/class-wp-taxi-admin-customers-table.php';
        require_once WP_TAXI_PLUGIN_DIR . 'admin/partials/class-wp-taxi-admin-rides-table.php';
    }

    public function enqueue_admin_scripts( $hook_suffix ) {
        // Styles und Skripte nur auf den Plugin-Seiten laden
        $plugin_pages = array(
            'toplevel_page_wp-taxi-plugin',
            'taxi-plugin_page_wp-taxi-drivers',
            'taxi-plugin_page_wp-taxi-customers',
            'taxi-plugin_page_wp-taxi-rides',
            'taxi-plugin_page_wp-taxi-live-map',
            'taxi-plugin_page_wp-taxi-settings',
        );

        if ( in_array( $hook_suffix, $plugin_pages ) ) {
            wp_enqueue_style( 'wp-taxi-admin-style', WP_TAXI_PLUGIN_URL . 'assets/css/admin.css', array(), WP_TAXI_PLUGIN_VERSION );
            wp_enqueue_script( 'wp-taxi-admin-script', WP_TAXI_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), WP_TAXI_PLUGIN_VERSION, true );

            $localize_params = array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce_approve_driver' => wp_create_nonce( 'wp_taxi_admin_approve_driver_nonce' ),
                'text_approving' => __('Genehmige...', 'wp-taxi-plugin'),
                'text_revoking' => __('Entziehe Genehmigung...', 'wp-taxi-plugin'),
                'text_error' => __('Ein Fehler ist aufgetreten.', 'wp-taxi-plugin'),
            );

            if ( $hook_suffix === 'taxi-plugin_page_wp-taxi-live-map' && !empty(self::$google_maps_api_key) ) {
                 wp_enqueue_script( 'google-maps-api-admin', 'https://maps.googleapis.com/maps/api/js?key=' . self::$google_maps_api_key . '&libraries=geometry&callback=initAdminMap', array(), null, true );
                 $localize_params['nonce_get_live_drivers'] = wp_create_nonce('wp_taxi_admin_get_live_drivers_nonce');
                 $localize_params['text_no_drivers_on_map'] = __('Keine Fahrer mit Standortdaten verfügbar.', 'wp-taxi-plugin');
            }
             wp_localize_script( 'wp-taxi-admin-script', 'wp_taxi_admin_params', $localize_params);
        }
    }

    public function admin_menu() {
        add_menu_page(
            __( 'Taxi Plugin', 'wp-taxi-plugin' ),
            __( 'Taxi Plugin', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings', // Capability
            'wp-taxi-plugin', // Menu Slug
            array( $this, 'create_admin_dashboard_page' ), // Callback für die Hauptseite
            'dashicons-car', // Icon
            25 // Position
        );

        add_submenu_page(
            'wp-taxi-plugin', // Parent Slug
            __( 'Fahrer', 'wp-taxi-plugin' ),
            __( 'Fahrer', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings',
            'wp-taxi-drivers',
            array( $this, 'create_admin_drivers_page' )
        );

        add_submenu_page(
            'wp-taxi-plugin',
            __( 'Kunden', 'wp-taxi-plugin' ),
            __( 'Kunden', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings',
            'wp-taxi-customers',
            array( $this, 'create_admin_customers_page' )
        );

        add_submenu_page(
            'wp-taxi-plugin',
            __( 'Fahrten', 'wp-taxi-plugin' ),
            __( 'Fahrten', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings',
            'wp-taxi-rides',
            array( $this, 'create_admin_rides_page' )
        );

        add_submenu_page(
            'wp-taxi-plugin',
            __( 'Live Karte', 'wp-taxi-plugin' ),
            __( 'Live Karte', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings',
            'wp-taxi-live-map',
            array( $this, 'create_admin_live_map_page' )
        );

        add_submenu_page(
            'wp-taxi-plugin',
            __( 'Einstellungen', 'wp-taxi-plugin' ),
            __( 'Einstellungen', 'wp-taxi-plugin' ),
            'manage_options', // Höhere Capability für Einstellungen
            'wp-taxi-settings',
            array( $this, 'create_admin_settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'wp_taxi_plugin_settings_group', 'wp_taxi_plugin_google_maps_api_key', 'sanitize_text_field' );
        register_setting( 'wp_taxi_plugin_settings_group', 'wp_taxi_plugin_base_fare', 'absval' ); // Should be float, handle in validation
        register_setting( 'wp_taxi_plugin_settings_group', 'wp_taxi_plugin_price_per_km', 'absval' ); // Should be float
        register_setting( 'wp_taxi_plugin_settings_group', 'wp_taxi_plugin_currency_symbol', 'sanitize_text_field' );
        // register_setting( 'wp_taxi_plugin_settings_group', 'wp_taxi_plugin_driver_approval_required', 'booleanval' ); // WordPress doesn't have booleanval, use absint(1) or custom sanitize

        add_settings_section(
            'wp_taxi_plugin_general_settings_section',
            __( 'Allgemeine Einstellungen', 'wp-taxi-plugin' ),
            null, // Callback für Sektionsbeschreibung
            'wp-taxi-settings' // Slug der Seite
        );

        add_settings_field(
            'google_maps_api_key',
            __( 'Google Maps API Key', 'wp-taxi-plugin' ),
            array( $this, 'render_settings_field_text' ),
            'wp-taxi-settings', // Slug der Seite
            'wp_taxi_plugin_general_settings_section', // Sektion
            array( 'id' => 'wp_taxi_plugin_google_maps_api_key', 'label_for' => 'wp_taxi_plugin_google_maps_api_key' )
        );

        add_settings_field(
            'base_fare',
            __( 'Grundpreis (CHF)', 'wp-taxi-plugin' ),
            array( $this, 'render_settings_field_number' ),
            'wp-taxi-settings',
            'wp_taxi_plugin_general_settings_section',
            array( 'id' => 'wp_taxi_plugin_base_fare', 'label_for' => 'wp_taxi_plugin_base_fare', 'step' => '0.01', 'default' => '5.00' )
        );

        add_settings_field(
            'price_per_km',
            __( 'Preis pro Kilometer (CHF)', 'wp-taxi-plugin' ),
            array( $this, 'render_settings_field_number' ),
            'wp-taxi-settings',
            'wp_taxi_plugin_general_settings_section',
            array( 'id' => 'wp_taxi_plugin_price_per_km', 'label_for' => 'wp_taxi_plugin_price_per_km', 'step' => '0.01', 'default' => '2.50' )
        );

        add_settings_field(
            'currency_symbol',
            __( 'Währungssymbol', 'wp-taxi-plugin' ),
            array( $this, 'render_settings_field_text' ),
            'wp-taxi-settings',
            'wp_taxi_plugin_general_settings_section',
            array( 'id' => 'wp_taxi_plugin_currency_symbol', 'label_for' => 'wp_taxi_plugin_currency_symbol', 'default' => 'CHF' )
        );
    }

    public function render_settings_field_text( $args ) {
        $option_value = get_option( $args['id'], isset($args['default']) ? $args['default'] : '' );
        echo '<input type="text" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $option_value ) . '" class="regular-text" />';
        if (isset($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }
    public function render_settings_field_number( $args ) {
        $option_value = get_option( $args['id'], isset($args['default']) ? $args['default'] : '' );
        $step = isset($args['step']) ? $args['step'] : '1';
        echo '<input type="number" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $option_value ) . '" class="regular-text" step="'.esc_attr($step).'" />';
         if (isset($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }


    public function create_admin_dashboard_page() {
        require_once WP_TAXI_PLUGIN_DIR . 'admin/partials/admin-display-dashboard.php';
    }

    public function create_admin_drivers_page() {
        require_once WP_TAXI_PLUGIN_DIR . 'admin/partials/admin-display-drivers.php';
    }

    public function create_admin_customers_page() {
        require_once WP_TAXI_PLUGIN_DIR . 'admin/partials/admin-display-customers.php';
    }

    public function create_admin_rides_page() {
        require_once WP_TAXI_PLUGIN_DIR . 'admin/partials/admin-display-rides.php';
    }

    public function create_admin_live_map_page() {
        require_once WP_TAXI_PLUGIN_DIR . 'admin/partials/admin-display-live-map.php';
    }

    public function create_admin_settings_page() {
        require_once WP_TAXI_PLUGIN_DIR . 'admin/partials/admin-display-settings.php';
    }

    /**
     * AJAX Handler: Fahrer genehmigen / Genehmigung entziehen.
     */
    public static function ajax_admin_approve_driver() {
        check_ajax_referer( 'wp_taxi_admin_approve_driver_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) {
            wp_send_json_error( array( 'message' => __( 'Nicht berechtigt.', 'wp-taxi-plugin' ) ) );
            return;
        }

        $driver_id = isset( $_POST['driver_id'] ) ? intval( $_POST['driver_id'] ) : 0;
        $approve_action = isset( $_POST['approve_action'] ) ? sanitize_text_field( $_POST['approve_action'] ) : ''; // 'approve' or 'revoke'

        if ( !$driver_id || !in_array($approve_action, ['approve', 'revoke']) ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Anfrage.', 'wp-taxi-plugin' ) ) );
            return;
        }

        $user = get_userdata( $driver_id );
        if ( !$user || !in_array( 'driver', (array) $user->roles ) ) {
            wp_send_json_error( array( 'message' => __( 'Fahrer nicht gefunden.', 'wp-taxi-plugin' ) ) );
            return;
        }

        if ($approve_action === 'approve') {
            update_user_meta( $driver_id, 'driver_approved', true );
            // TODO: Benachrichtige den Fahrer über die Genehmigung
            wp_send_json_success( array( 'message' => __( 'Fahrer genehmigt.', 'wp-taxi-plugin' ), 'new_status' => __('Genehmigt', 'wp-taxi-plugin'), 'action_taken' => 'approved' ) );
        } else { // revoke
            update_user_meta( $driver_id, 'driver_approved', false );
            // TODO: Benachrichtige den Fahrer über den Entzug der Genehmigung
            wp_send_json_success( array( 'message' => __( 'Fahrergenehmigung entzogen.', 'wp-taxi-plugin' ), 'new_status' => __('Nicht genehmigt', 'wp-taxi-plugin'), 'action_taken' => 'revoked' ) );
        }
    }

    /**
     * AJAX Handler: Live-Fahrerdaten für die Admin-Karte abrufen.
     */
    public static function ajax_admin_get_live_drivers() {
        check_ajax_referer( 'wp_taxi_admin_get_live_drivers_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) {
            wp_send_json_error( array( 'message' => __( 'Nicht berechtigt.', 'wp-taxi-plugin' ) ) );
            return;
        }

        $drivers_data = array();
        $args = array(
            'role'    => 'driver',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'driver_approved',
                    'value' => '1',
                    'compare' => '='
                ),
                // array( // Optional: Nur als verfügbar markierte Fahrer anzeigen
                //     'key' => 'driver_available',
                //     'value' => '1',
                //     'compare' => '='
                // ),
                array(
                    'key' => 'driver_last_lat',
                    'compare' => 'EXISTS'
                ),
                 array(
                    'key' => 'driver_last_lng',
                    'compare' => 'EXISTS'
                )
            )
        );
        $drivers = get_users( $args );

        foreach ( $drivers as $driver ) {
            $lat = get_user_meta( $driver->ID, 'driver_last_lat', true );
            $lng = get_user_meta( $driver->ID, 'driver_last_lng', true );
            $is_available = get_user_meta( $driver->ID, 'driver_available', true );
            $last_update = get_user_meta( $driver->ID, 'driver_last_location_update', true );

            if ( $lat && $lng ) {
                $drivers_data[] = array(
                    'id'        => $driver->ID,
                    'name'      => $driver->display_name,
                    'email'     => $driver->user_email,
                    'latitude'  => floatval($lat),
                    'longitude' => floatval($lng),
                    'available' => $is_available ? __('Ja', 'wp-taxi-plugin') : __('Nein', 'wp-taxi-plugin'),
                    'is_available_raw' => $is_available, // For styling marker
                    'vehicle_model' => get_user_meta( $driver->ID, 'vehicle_model', true ) ?: '-',
                    'last_update' => $last_update ? human_time_diff(strtotime($last_update)) . ' ' . __('her', 'wp-taxi-plugin') : __('Unbekannt', 'wp-taxi-plugin'),
                );
            }
        }

        if ( ! empty( $drivers_data ) ) {
            wp_send_json_success( $drivers_data );
        } else {
            wp_send_json_error( array( 'message' => __( 'Keine Fahrer mit Standortdaten gefunden.', 'wp-taxi-plugin' ) ) );
        }
    }
}

// Admin-Bereich nur laden, wenn is_admin() true ist.
if ( is_admin() ) {
    new WP_Taxi_Admin();
}

?>
