<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Taxi_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // AJAX Handler für Admin-Aktionen (z.B. Fahrer genehmigen)
        add_action( 'wp_ajax_wp_taxi_approve_driver', array( $this, 'ajax_approve_driver' ) );
        add_action( 'wp_ajax_wp_taxi_get_live_drivers', array( $this, 'ajax_get_live_drivers' ) );

    }

    public function enqueue_admin_scripts( $hook_suffix ) {
        // Globale Admin CSS
        wp_enqueue_style( 'wp-taxi-admin-style', WP_TAXI_PLUGIN_URL . 'admin/css/admin.css', array(), WP_TAXI_PLUGIN_VERSION );

        // Skripte nur auf relevanten Plugin-Seiten laden
        $plugin_pages_hooks = array(
            'toplevel_page_wp-taxi-plugin', // Settings
            'taxi-plugin_page_wp-taxi-drivers', // Drivers
            'taxi-plugin_page_wp-taxi-customers', // Customers
            'taxi-plugin_page_wp-taxi-rides', // Rides
            'taxi-plugin_page_wp-taxi-live-map' // Live Map
        );

        if ( in_array($hook_suffix, $plugin_pages_hooks) ) {
            // Allgemeines Admin JS (z.B. für Fahrer genehmigen)
            wp_enqueue_script( 'wp-taxi-admin-script', WP_TAXI_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), WP_TAXI_PLUGIN_VERSION, true );
            wp_localize_script( 'wp-taxi-admin-script', 'wp_taxi_admin_params', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce_approve_driver' => wp_create_nonce( 'wp_taxi_approve_driver_nonce' ),
                'text_approving' => __('Genehmige...', 'wp-taxi-plugin'),
                'text_approved' => __('Genehmigt', 'wp-taxi-plugin'),
                'text_unapproving' => __('Sperre...', 'wp-taxi-plugin'),
                'text_unapproved' => __('Nicht genehmigt', 'wp-taxi-plugin'),
                'text_error_approving' => __('Fehler bei Genehmigung', 'wp-taxi-plugin'),
            ) );
        }

        // Nur auf der Live-Map-Seite das Google Maps Skript laden
        if ( $hook_suffix === 'taxi-plugin_page_wp-taxi-live-map') {
            $api_key = get_option('wp_taxi_plugin_google_maps_api_key', '');
            if (!empty($api_key)) {
                wp_enqueue_script( 'google-maps-api-admin', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=geometry,places&callback=initLiveMapAdmin', array(), null, true );
            } else {
                 add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>' . __('WP Taxi Plugin: Google Maps API Key nicht gesetzt. Die Live-Karte kann nicht angezeigt werden.', 'wp-taxi-plugin') . '</p></div>';
                });
            }
             wp_enqueue_script( 'wp-taxi-admin-live-map-script', WP_TAXI_PLUGIN_URL . 'admin/js/admin-live-map.js', array( 'jquery', 'google-maps-api-admin' ), WP_TAXI_PLUGIN_VERSION, true );
             wp_localize_script('wp-taxi-admin-live-map-script', 'wp_taxi_admin_map_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce_get_live_drivers' => wp_create_nonce('wp_taxi_get_live_drivers_nonce'),
                'text_no_drivers' => __('Keine Fahrerdaten für die Karte verfügbar.', 'wp-taxi-plugin'),
                'text_driver' => __('Fahrer', 'wp-taxi-plugin'),
                'text_vehicle' => __('Fahrzeug', 'wp-taxi-plugin'),
                'text_status' => __('Status', 'wp-taxi-plugin'),
                'text_last_update' => __('Letzte Aktualisierung', 'wp-taxi-plugin'),
            ));
        }
    }

    public function admin_menu() {
        add_menu_page(
            __( 'Taxi Plugin', 'wp-taxi-plugin' ),
            __( 'Taxi Plugin', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings', // Eigene Capability
            'wp-taxi-plugin', // Slug
            array( $this, 'settings_page_html' ),
            'dashicons-car',
            25
        );

        add_submenu_page(
            'wp-taxi-plugin', // Parent Slug
            __( 'Einstellungen', 'wp-taxi-plugin' ),
            __( 'Einstellungen', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings',
            'wp-taxi-plugin', // Identisch zum Parent, um die Hauptseite zu sein
            array( $this, 'settings_page_html' )
        );

        add_submenu_page(
            'wp-taxi-plugin',
            __( 'Fahrer', 'wp-taxi-plugin' ),
            __( 'Fahrer', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings',
            'wp-taxi-drivers',
            array( $this, 'drivers_page_html' )
        );

        add_submenu_page(
            'wp-taxi-plugin',
            __( 'Kunden', 'wp-taxi-plugin' ),
            __( 'Kunden', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings',
            'wp-taxi-customers',
            array( $this, 'customers_page_html' )
        );

        add_submenu_page(
            'wp-taxi-plugin',
            __( 'Fahrtenübersicht', 'wp-taxi-plugin' ),
            __( 'Fahrtenübersicht', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings',
            'wp-taxi-rides',
            array( $this, 'rides_page_html' )
        );

        add_submenu_page(
            'wp-taxi-plugin',
            __( 'Live Karte', 'wp-taxi-plugin' ),
            __( 'Live Karte', 'wp-taxi-plugin' ),
            'manage_taxi_plugin_settings',
            'wp-taxi-live-map',
            array( $this, 'live_map_page_html' )
        );
    }

    public function register_settings() {
        // Settings Group
        $settings_group = 'wp_taxi_plugin_settings_group';

        // General Settings
        register_setting( $settings_group, 'wp_taxi_plugin_google_maps_api_key', 'sanitize_text_field' );
        register_setting( $settings_group, 'wp_taxi_plugin_base_fare', 'sanitize_text_field' );
        register_setting( $settings_group, 'wp_taxi_plugin_price_per_km', 'sanitize_text_field' );
        register_setting( $settings_group, 'wp_taxi_plugin_currency_symbol', 'sanitize_text_field' );

        // Page URL settings (speichert Page IDs)
        register_setting( $settings_group, 'wp_taxi_plugin_register_page_id', 'intval' );
        register_setting( $settings_group, 'wp_taxi_plugin_login_page_id', 'intval' );
        register_setting( $settings_group, 'wp_taxi_plugin_customer_dashboard_page_id', 'intval' );
        register_setting( $settings_group, 'wp_taxi_plugin_driver_dashboard_page_id', 'intval' );

        // General Settings Section
        $general_section_id = 'wp_taxi_plugin_general_settings_section';
        add_settings_section(
            $general_section_id,
            __( 'Allgemeine Einstellungen', 'wp-taxi-plugin' ),
            null,
            'wp-taxi-plugin' // Page slug for settings page
        );

        add_settings_field('wp_taxi_plugin_google_maps_api_key', __('Google Maps API Key', 'wp-taxi-plugin'), array($this, 'render_settings_field'), 'wp-taxi-plugin', $general_section_id, array('type' => 'text', 'id' => 'wp_taxi_plugin_google_maps_api_key', 'description' => __('Geben Sie Ihren Google Maps API Key ein. Stellen Sie sicher, dass die "Maps JavaScript API", "Geocoding API", "Directions API" und "Places API" in Ihrer Google Cloud Console aktiviert sind.', 'wp-taxi-plugin')));
        add_settings_field('wp_taxi_plugin_base_fare', __('Grundpreis', 'wp-taxi-plugin'), array($this, 'render_settings_field'), 'wp-taxi-plugin', $general_section_id, array('type' => 'number', 'id' => 'wp_taxi_plugin_base_fare', 'step' => '0.01', 'min' => '0', 'description' => get_option('wp_taxi_plugin_currency_symbol', 'CHF')));
        add_settings_field('wp_taxi_plugin_price_per_km', __('Preis pro km', 'wp-taxi-plugin'), array($this, 'render_settings_field'), 'wp-taxi-plugin', $general_section_id, array('type' => 'number', 'id' => 'wp_taxi_plugin_price_per_km', 'step' => '0.01', 'min' => '0', 'description' => get_option('wp_taxi_plugin_currency_symbol', 'CHF')));
        add_settings_field('wp_taxi_plugin_currency_symbol', __('Währungssymbol', 'wp-taxi-plugin'), array($this, 'render_settings_field'), 'wp-taxi-plugin', $general_section_id, array('type' => 'text', 'id' => 'wp_taxi_plugin_currency_symbol', 'default' => 'CHF'));

        // Page Settings Section
        $page_section_id = 'wp_taxi_plugin_page_settings_section';
        add_settings_section(
            $page_section_id,
            __( 'Seiten Zuweisung', 'wp-taxi-plugin' ),
            function() { echo '<p>' . __('Weisen Sie hier die WordPress-Seiten zu, auf denen Ihre Plugin-Shortcodes platziert sind. Dies hilft dem Plugin, korrekte Links zu generieren.', 'wp-taxi-plugin') . '</p>'; },
            'wp-taxi-plugin'
        );

        $pages = get_pages();
        $page_options = array(0 => __('-- Seite auswählen --', 'wp-taxi-plugin'));
        if ($pages) {
            foreach ($pages as $page) {
                $page_options[$page->ID] = $page->post_title;
            }
        }

        $page_fields = array(
            'register_page_id' => array('label' => __('Registrierungsseite', 'wp-taxi-plugin'), 'shortcode' => '[wp_taxi_registration_form]'),
            'login_page_id' => array('label' => __('Anmeldeseite', 'wp-taxi-plugin'), 'shortcode' => '[wp_taxi_login_form]'),
            'customer_dashboard_page_id' => array('label' => __('Kunden-Dashboard', 'wp-taxi-plugin'), 'shortcode' => '[wp_taxi_customer_dashboard]'),
            'driver_dashboard_page_id' => array('label' => __('Fahrer-Dashboard', 'wp-taxi-plugin'), 'shortcode' => '[wp_taxi_driver_dashboard]'),
        );

        foreach ($page_fields as $id_suffix => $field_data) {
            $option_id = 'wp_taxi_plugin_' . $id_suffix;
            add_settings_field(
                $option_id,
                $field_data['label'],
                array( $this, 'render_settings_field' ),
                'wp-taxi-plugin',
                $page_section_id,
                array(
                    'type' => 'select',
                    'id' => $option_id,
                    'options' => $page_options,
                    'description' => sprintf(__('Wählen Sie die Seite, die den Shortcode %s enthält.', 'wp-taxi-plugin'), '<code>' . $field_data['shortcode'] . '</code>')
                )
            );
        }
    }

    public function render_settings_field( $args ) {
        $option_value = get_option( $args['id'], isset($args['default']) ? $args['default'] : '' );
        $type = isset($args['type']) ? $args['type'] : 'text';
        $description = isset($args['description']) ? '<p class="description">' . $args['description'] . '</p>' : '';
        $field_id = esc_attr($args['id']);

        if ($type === 'select') {
            echo "<select id='{$field_id}' name='{$field_id}'>";
            foreach ($args['options'] as $value => $label) {
                echo "<option value='" . esc_attr($value) . "'" . selected($option_value, $value, false) . ">" . esc_html($label) . "</option>";
            }
            echo "</select>";
        } elseif ($type === 'number') {
            $step = isset($args['step']) ? "step='{$args['step']}'" : "";
            $min = isset($args['min']) ? "min='{$args['min']}'" : "";
            echo "<input type='number' id='{$field_id}' name='{$field_id}' value='" . esc_attr( $option_value ) . "' {$step} {$min} class='small-text' />";
        }
        else { // text, password, email etc.
            echo "<input type='{$type}' id='{$field_id}' name='{$field_id}' value='" . esc_attr( $option_value ) . "' class='regular-text' />";
        }
        if ($description && $type !== 'number') { // Description for number fields is handled by suffixing currency.
             echo $description;
        } elseif ($description && $type === 'number' && isset($args['description'])) {
            echo ' ' . esc_html($args['description']); // Suffix for number, e.g. currency
        }
    }


    public function settings_page_html() {
        if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) {
            wp_die(__( 'Sie haben keine Berechtigung, diese Seite anzuzeigen.', 'wp-taxi-plugin'));
        }
        ?>
        <div class="wrap wp-taxi-admin-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <?php settings_errors(); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'wp_taxi_plugin_settings_group' );
                do_settings_sections( 'wp-taxi-plugin' ); // Slug der Seite
                submit_button( __( 'Einstellungen speichern', 'wp-taxi-plugin' ) );
                ?>
            </form>
        </div>
        <?php
    }

    public function drivers_page_html() {
        if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) wp_die(__( 'Zugriff verweigert', 'wp-taxi-plugin'));
        require_once WP_TAXI_PLUGIN_DIR . 'admin/views/drivers-list.php';
    }

    public function customers_page_html() {
        if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) wp_die(__( 'Zugriff verweigert', 'wp-taxi-plugin'));
        require_once WP_TAXI_PLUGIN_DIR . 'admin/views/customers-list.php';
    }

    public function rides_page_html() {
        if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) wp_die(__( 'Zugriff verweigert', 'wp-taxi-plugin'));
        require_once WP_TAXI_PLUGIN_DIR . 'admin/views/rides-list.php';
    }

    public function live_map_page_html() {
        if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) wp_die(__( 'Zugriff verweigert', 'wp-taxi-plugin'));
        require_once WP_TAXI_PLUGIN_DIR . 'admin/views/live-map.php';
    }

    /**
     * AJAX Handler: Fahrer genehmigen/sperren.
     */
    public function ajax_approve_driver() {
        check_ajax_referer( 'wp_taxi_approve_driver_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'wp-taxi-plugin' ) ) );
        }

        $driver_id = isset( $_POST['driver_id'] ) ? intval( $_POST['driver_id'] ) : 0;
        $approve_action = isset( $_POST['approve_action'] ) ? sanitize_text_field( $_POST['approve_action'] ) : 'approve'; // 'approve' or 'unapprove'

        if ( ! $driver_id ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Fahrer-ID.', 'wp-taxi-plugin' ) ) );
        }

        $user = get_userdata($driver_id);
        if (!$user || !in_array('driver', (array)$user->roles)) {
            wp_send_json_error( array( 'message' => __( 'Benutzer ist kein Fahrer.', 'wp-taxi-plugin' ) ) );
        }

        $new_status_bool = ($approve_action === 'approve');
        update_user_meta( $driver_id, 'driver_approved', $new_status_bool );

        wp_send_json_success( array(
            'message' => $new_status_bool ? __( 'Fahrer genehmigt.', 'wp-taxi-plugin' ) : __( 'Fahrergenehmigung entzogen.', 'wp-taxi-plugin' ),
            'new_status_text' => $new_status_bool ? __( 'Genehmigt', 'wp-taxi-plugin' ) : __( 'Nicht genehmigt', 'wp-taxi-plugin' ),
            'action_taken' => $approve_action,
            'is_approved' => $new_status_bool
        ) );
    }

    /**
     * AJAX Handler: Live-Positionen der Fahrer abrufen
     */
    public function ajax_get_live_drivers() {
        check_ajax_referer('wp_taxi_get_live_drivers_nonce', 'nonce');

        if (!current_user_can('manage_taxi_plugin_settings')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'wp-taxi-plugin')]);
        }

        $drivers_data = array();
        $args = array(
            'role'    => 'driver',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'driver_approved',
                    'value' => true, // Gespeichert als boolean
                    'compare' => '='
                ),
                array(
                    'key' => 'driver_available',
                    'value' => true, // Gespeichert als boolean
                    'compare' => '='
                ),
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
            $last_update = get_user_meta( $driver->ID, 'driver_last_location_update', true ); // Timestamp
            $active_ride_id = get_user_meta( $driver->ID, 'driver_current_ride_id', true );

            // Nur Fahrer anzeigen, deren Standort kürzlich aktualisiert wurde (z.B. letzte 5 Minuten)
            if ( $lat && $lng && ( empty($last_update) || (time() - intval($last_update)) < 300 ) ) { // 5 Minuten = 300 Sekunden
                $drivers_data[] = array(
                    'id'        => $driver->ID,
                    'name'      => esc_html($driver->display_name),
                    'latitude'  => floatval($lat),
                    'longitude' => floatval($lng),
                    'vehicle_model' => esc_html(get_user_meta( $driver->ID, 'vehicle_model', true ) ?: __('N/A', 'wp-taxi-plugin')),
                    'status_code' => $active_ride_id ? 'on_ride' : 'available',
                    'status_text' => $active_ride_id ? __('Auf Fahrt', 'wp-taxi-plugin') : __('Verfügbar', 'wp-taxi-plugin'),
                    'last_update_timestamp' => $last_update,
                    'last_update_readable' => $last_update ? sprintf(__('%s her', 'wp-taxi-plugin'), human_time_diff(intval($last_update))) : __('Unbekannt', 'wp-taxi-plugin')
                );
            }
        }

        if ( ! empty( $drivers_data ) ) {
            wp_send_json_success( $drivers_data );
        } else {
            wp_send_json_error( array( 'message' => __( 'Keine aktiven Fahrer mit aktuellen Standortdaten gefunden.', 'wp-taxi-plugin' ) ) );
        }
    }

}

// Admin-Klasse nur im Admin-Bereich instanziieren
if ( is_admin() ) {
    new WP_Taxi_Admin();
}

?>
