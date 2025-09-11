<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Taxi_Driver_Dashboard_Shortcode {

    private static $google_maps_api_key = '';

    public static function init() {
        // API Key laden (wie im Kunden-Dashboard)
        self::$google_maps_api_key = get_option('wp_taxi_plugin_google_maps_api_key', 'AIzaSyBbWGNbH7yEXc7mtQrwvQEPmghYfr9-glI'); // Fallback auf Prompt-Key

        add_shortcode( 'wp_taxi_driver_dashboard', array( __CLASS__, 'dashboard_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

        // AJAX Handler für das Fahrer-Dashboard
        add_action( 'wp_ajax_wp_taxi_get_pending_rides', array( __CLASS__, 'ajax_get_pending_rides' ) );
        add_action( 'wp_ajax_wp_taxi_accept_ride', array( __CLASS__, 'ajax_accept_ride' ) );
        add_action( 'wp_ajax_wp_taxi_update_driver_status', array( __CLASS__, 'ajax_update_driver_status' ) );
        add_action( 'wp_ajax_wp_taxi_update_ride_status', array( __CLASS__, 'ajax_update_ride_status' ) );
        add_action( 'wp_ajax_wp_taxi_get_current_ride', array( __CLASS__, 'ajax_get_current_ride' ) );

    }

    public static function enqueue_scripts() {
        if ( is_page() ) { // Prüfen, ob es eine Seite ist, ggf. spezifischer prüfen
            global $post;
            if ( has_shortcode( $post->post_content, 'wp_taxi_driver_dashboard' ) ) {
                wp_enqueue_style( 'wp-taxi-driver-dashboard-style', WP_TAXI_PLUGIN_URL . 'assets/css/driver-dashboard.css', array(), WP_TAXI_PLUGIN_VERSION );

                if ( !empty(self::$google_maps_api_key) ) {
                    wp_enqueue_script( 'google-maps-api-driver', 'https://maps.googleapis.com/maps/api/js?key=' . self::$google_maps_api_key . '&libraries=places,geometry&callback=initMapDriver', array(), null, true );
                } else {
                    wp_add_inline_script('jquery', 'console.error("Google Maps API Key is missing for WP Taxi Driver Dashboard.");');
                }

                wp_enqueue_script( 'wp-taxi-driver-dashboard-script', WP_TAXI_PLUGIN_URL . 'assets/js/driver-dashboard.js', array( 'jquery' ), WP_TAXI_PLUGIN_VERSION, true );
                wp_localize_script( 'wp-taxi-driver-dashboard-script', 'wp_taxi_driver_params', array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'google_maps_api_key' => self::$google_maps_api_key,
                    'text_error_loading_rides' => __('Fehler beim Laden der Fahrten.', 'wp-taxi-plugin'),
                    'text_no_pending_rides' => __('Aktuell keine offenen Fahraufträge.', 'wp-taxi-plugin'),
                    'text_accepting_ride' => __('Fahrt wird angenommen...', 'wp-taxi-plugin'),
                    'text_ride_accepted' => __('Fahrt erfolgreich angenommen.', 'wp-taxi-plugin'),
                    'text_error_accepting_ride' => __('Fehler beim Annehmen der Fahrt.', 'wp-taxi-plugin'),
                    'text_updating_status' => __('Status wird aktualisiert...', 'wp-taxi-plugin'),
                    'text_status_updated' => __('Status erfolgreich aktualisiert.', 'wp-taxi-plugin'),
                    'text_error_updating_status' => __('Fehler beim Aktualisieren des Status.', 'wp-taxi-plugin'),
                    'text_completing_ride' => __('Fahrt wird abgeschlossen...', 'wp-taxi-plugin'),
                    'text_ride_completed' => __('Fahrt erfolgreich abgeschlossen.', 'wp-taxi-plugin'),
                    'text_starting_ride' => __('Fahrt wird gestartet...', 'wp-taxi-plugin'),
                    'text_ride_started' => __('Fahrt gestartet.', 'wp-taxi-plugin'),
                    'text_cancelling_ride' => __('Fahrt wird storniert...', 'wp-taxi-plugin'),
                    'text_ride_cancelled' => __('Fahrt storniert.', 'wp-taxi-plugin'),
                    'text_error_ride_action' => __('Fehler bei der Fahrtaktion.', 'wp-taxi-plugin'),
                    'text_customer_notified' => __('Kunde wurde benachrichtigt (Simulation).', 'wp-taxi-plugin'),
                    'text_ride_request' => __('Fahranfrage', 'wp-taxi-plugin'),
                    'text_customer' => __('Kunde', 'wp-taxi-plugin'),
                    'text_from' => __('Von', 'wp-taxi-plugin'),
                    'text_to' => __('Nach', 'wp-taxi-plugin'),
                    'text_price' => __('Preis (ca.)', 'wp-taxi-plugin'),
                    'currency_symbol' => get_option('wp_taxi_plugin_currency_symbol', 'CHF'),
                    'text_status' => __('Status', 'wp-taxi-plugin'),
                    'text_phone' => __('Tel', 'wp-taxi-plugin'),
                    'text_current_ride_details' => __('Details zur aktuellen Fahrt', 'wp-taxi-plugin'),
                    'text_start_ride' => __('Fahrt starten', 'wp-taxi-plugin'),
                    'text_complete_ride' => __('Fahrt abschliessen', 'wp-taxi-plugin'),
                    'text_cancel_ride' => __('Fahrt stornieren', 'wp-taxi-plugin'),
                    'text_no_active_ride' => __('Sie haben derzeit keine aktive Fahrt.', 'wp-taxi-plugin'),
                    'text_error_loading_current_ride' => __('Fehler beim Laden der aktuellen Fahrt.', 'wp-taxi-plugin'),
                    'text_on_active_ride' => __('Sie sind in einer aktiven Fahrt. Keine neuen Anfragen bis Abschluss.', 'wp-taxi-plugin'),
                    'nonce_get_rides' => wp_create_nonce('wp_taxi_get_pending_rides_nonce'),
                    'nonce_accept_ride' => wp_create_nonce('wp_taxi_accept_ride_nonce'),
                    'nonce_update_status' => wp_create_nonce('wp_taxi_update_driver_status_nonce'),
                    'nonce_update_ride_status' => wp_create_nonce('wp_taxi_update_ride_status_nonce'),
                    'nonce_get_current_ride' => wp_create_nonce('wp_taxi_get_current_ride_nonce'),
                ) );
            }
        }
    }

    public static function dashboard_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Bitte melden Sie sich an, um Ihr Fahrer-Dashboard anzuzeigen.', 'wp-taxi-plugin' ) . ' <a href="' . esc_url( home_url( '/anmelden' ) ) . '">' . __( 'Anmelden', 'wp-taxi-plugin' ) . '</a></p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'driver', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
            return '<p>' . __( 'Dieses Dashboard ist nur für Fahrer zugänglich.', 'wp-taxi-plugin' ) . '</p>';
        }

        $driver_id = $user->ID;
        $is_approved = get_user_meta( $driver_id, 'driver_approved', true );
        if ( !$is_approved && !in_array( 'administrator', (array) $user->roles )) { // Admins können immer zugreifen
             return '<p>' . __( 'Ihr Fahrerkonto wurde noch nicht vom Administrator genehmigt. Bitte haben Sie etwas Geduld.', 'wp-taxi-plugin' ) . '</p>';
        }

        if ( empty(self::$google_maps_api_key) ) {
            return '<p style="color:red;">' . __( 'Google Maps API Key ist nicht konfiguriert. Das Dashboard kann nicht vollständig funktionieren.', 'wp-taxi-plugin') . '</p>';
        }

        $is_available = get_user_meta( $driver_id, 'driver_available', true );

        ob_start();
        ?>
        <div id="wp-taxi-driver-dashboard">
            <h2><?php printf(__( 'Willkommen, %s!', 'wp-taxi-plugin' ), $user->display_name); ?></h2>

            <div class="driver-status-panel">
                <h3><?php _e('Mein Status', 'wp-taxi-plugin'); ?></h3>
                <p>
                    <label for="driver-availability"><?php _e('Verfügbarkeit:', 'wp-taxi-plugin'); ?></label>
                    <select id="driver-availability" name="driver_availability">
                        <option value="1" <?php selected( $is_available, '1' ); ?>><?php _e('Verfügbar', 'wp-taxi-plugin'); ?></option>
                        <option value="0" <?php selected( $is_available, '0' ); selected( $is_available, '' ); // Treat empty as unavailable ?>><?php _e('Nicht verfügbar', 'wp-taxi-plugin'); ?></option>
                    </select>
                    <button id="update-availability-btn"><?php _e('Status aktualisieren', 'wp-taxi-plugin'); ?></button>
                </p>
                <div id="driver-status-message" style="margin-top:10px;"></div>
                <p><em><?php _e('Ihr aktueller Standort wird automatisch aktualisiert, wenn Sie als "Verfügbar" markiert sind und die Seite geöffnet ist.', 'wp-taxi-plugin'); ?></em></p>
            </div>
            <hr>

            <div id="current-ride-panel">
                <h3><?php _e('Aktuelle Fahrt', 'wp-taxi-plugin'); ?></h3>
                <div id="current-ride-details">
                    <p><?php _e('Sie haben derzeit keine aktive Fahrt.', 'wp-taxi-plugin'); ?></p>
                </div>
                 <div id="driver-map-container" style="height: 350px; width: 100%; margin-bottom: 20px; background-color: #e0e0e0;">
                    <p style="text-align:center; padding-top: 150px;"><?php _e('Karte für aktuelle Fahrt wird geladen...', 'wp-taxi-plugin');?></p>
                </div>
            </div>
            <hr>

            <div id="pending-rides-panel">
                <h3><?php _e('Offene Fahraufträge', 'wp-taxi-plugin'); ?></h3>
                <div id="pending-rides-list">
                    <p><?php _e('Lade verfügbare Fahrten...', 'wp-taxi-plugin'); ?></p>
                    <!-- Fahrten werden hier per AJAX geladen -->
                </div>
            </div>

            <hr>
            <h3><?php _e( 'Meine Fahrtenhistorie (als Fahrer)', 'wp-taxi-plugin' ); ?></h3>
            <div id="driver-rides-history">
                <?php self::display_ride_history($driver_id); ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }


    /**
     * Zeigt die Fahrtenhistorie des Fahrers an.
     */
    public static function display_ride_history($driver_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<p>' . __('Die Fahrtenhistorie ist momentan nicht verfügbar, da die Datenbanktabelle fehlt.', 'wp-taxi-plugin') . '</p>';
            return;
        }

        $rides = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE driver_id = %d AND status IN ('completed', 'cancelled') ORDER BY updated_at DESC LIMIT 10",
            $driver_id
        ) );

        if ( $rides ) {
            echo '<ul class="wp-taxi-ride-history-list">';
            foreach ( $rides as $ride ) {
                $customer = get_userdata($ride->customer_id);
                $customer_name = $customer ? esc_html($customer->display_name) : __('Unbekannter Kunde', 'wp-taxi-plugin');

                echo '<li>';
                echo '<strong>' . sprintf(__('Fahrt am %s', 'wp-taxi-plugin'),
                       date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ride->updated_at ) )) . '</strong><br>';
                echo sprintf( __('Kunde: %s', 'wp-taxi-plugin'), $customer_name) . '<br>';
                echo sprintf( __('Von: %s', 'wp-taxi-plugin'), esc_html($ride->start_address)) . '<br>';
                echo sprintf( __('Nach: %s', 'wp-taxi-plugin'), esc_html($ride->end_address) ) . '<br>';
                echo sprintf( __('Status: %s', 'wp-taxi-plugin'), '<em>' . esc_html(self::get_status_translation_static($ride->status)) . '</em>' ); // Use static call

                if ($ride->price) {
                    echo ' - ' . sprintf(__('Preis: %s %.2f', 'wp-taxi-plugin'), esc_html($ride->currency_symbol), floatval($ride->price));
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __( 'Sie haben noch keine Fahrten abgeschlossen oder storniert.', 'wp-taxi-plugin' ) . '</p>';
        }
    }

    // Helper function to translate status, similar to the one in Customer Dashboard but static for reuse here.
    // Ideally, this would be in a shared helper class/file.
    private static function get_status_translation_static( $status ) {
        $translations = array(
            'pending'   => __( 'Ausstehend', 'wp-taxi-plugin' ),
            'accepted'  => __( 'Angenommen', 'wp-taxi-plugin' ),
            'ongoing'   => __( 'Unterwegs', 'wp-taxi-plugin' ),
            'completed' => __( 'Abgeschlossen', 'wp-taxi-plugin' ),
            'cancelled' => __( 'Storniert', 'wp-taxi-plugin' ),
        );
        return isset( $translations[ $status ] ) ? $translations[ $status ] : ucfirst( $status );
    }


    /**
     * AJAX Handler: Ausstehende Fahrten abrufen.
     * Ruft Fahrten ab, die 'pending' sind und noch keinen Fahrer haben.
     * Hier könnte man später noch eine Umkreissuche basierend auf dem Fahrerstandort hinzufügen.
     */
    public static function ajax_get_pending_rides() {
        check_ajax_referer( 'wp_taxi_get_pending_rides_nonce', 'nonce' );

        $driver_id = get_current_user_id();
        $user = wp_get_current_user();
        if ( !in_array( 'driver', (array) $user->roles ) && !in_array( 'administrator', (array) $user->roles )) {
            wp_send_json_error( array( 'message' => __('Nur Fahrer können dies tun.', 'wp-taxi-plugin') ) );
            return;
        }
        if ( !get_user_meta( $driver_id, 'driver_approved', true ) && !in_array( 'administrator', (array) $user->roles ) ) {
            wp_send_json_error( array( 'message' => __('Ihr Konto ist nicht genehmigt.', 'wp-taxi-plugin') ) );
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error( array( 'message' => __('Datenbank nicht bereit.', 'wp-taxi-plugin') ) );
            return;
        }

        // Prüfen, ob der Fahrer bereits eine aktive Fahrt hat
        $active_ride_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(id) FROM {$table_name} WHERE driver_id = %d AND status IN ('accepted', 'ongoing')",
            $driver_id
        ) );

        if ($active_ride_count > 0) {
            wp_send_json_success( array() ); // Fahrer hat eine aktive Fahrt, keine neuen "pending" anzeigen
            return;
        }

        // Hier könnte man später eine Umkreissuche hinzufügen.
        // Für jetzt: alle pending rides ohne Fahrer.
        $rides = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE status = %s AND driver_id IS NULL ORDER BY created_at ASC",
                'pending'
            )
        );

        if ( ! empty( $rides ) ) {
            foreach ($rides as $ride) {
                $customer = get_userdata($ride->customer_id);
                $ride->customer_name = $customer ? esc_html($customer->display_name) : __('Unbekannt', 'wp-taxi-plugin');
                $ride->price = $ride->price ? number_format_i18n(floatval($ride->price), 2) : 'N/A';
                 // Wichtige Felder für JS explizit als Float, falls sie in JS für Berechnungen gebraucht werden
                $ride->start_lat = floatval($ride->start_lat);
                $ride->start_lng = floatval($ride->start_lng);
                $ride->end_lat = $ride->end_lat ? floatval($ride->end_lat) : null;
                $ride->end_lng = $ride->end_lng ? floatval($ride->end_lng) : null;
            }
            wp_send_json_success( $rides );
        } else {
            wp_send_json_success( array() );
        }
    }

    /**
     * AJAX Handler: Fahrt annehmen.
     */
    public static function ajax_accept_ride() {
        check_ajax_referer( 'wp_taxi_accept_ride_nonce', 'nonce' );
        $ride_id = isset($_POST['ride_id']) ? intval($_POST['ride_id']) : 0;
        $driver_id = get_current_user_id();

        if ( !$ride_id || !$driver_id ) {
            wp_send_json_error( array( 'message' => __('Ungültige Anfrage.', 'wp-taxi-plugin') ) );
            return;
        }

        $user = wp_get_current_user();
        if ( (!in_array( 'driver', (array) $user->roles ) || !get_user_meta( $driver_id, 'driver_approved', true )) && !in_array( 'administrator', (array) $user->roles ) ) {
            wp_send_json_error( array( 'message' => __('Nicht berechtigt, Fahrten anzunehmen.', 'wp-taxi-plugin') ) );
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error( array( 'message' => __('Datenbank nicht bereit.', 'wp-taxi-plugin') ) );
            return;
        }

        // Prüfen, ob der Fahrer bereits eine aktive Fahrt hat
        $active_ride = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE driver_id = %d AND status IN ('accepted', 'ongoing')",
            $driver_id
        ) );
        if ( $active_ride ) {
            wp_send_json_error( array( 'message' => __('Sie haben bereits eine aktive Fahrt.', 'wp-taxi-plugin') ) );
            return;
        }

        // Atomare Operation: Fahrt aktualisieren, nur wenn sie noch 'pending' ist und keinen Fahrer hat.
        $updated = $wpdb->update(
            $table_name,
            array(
                'driver_id' => $driver_id,
                'status' => 'accepted',
                'updated_at' => current_time('mysql')
            ),
            array(
                'id' => $ride_id,
                'status' => 'pending',
                // 'driver_id' => null // Explizit prüfen, dass driver_id NULL ist. $wpdb->update kann das so nicht direkt.
                                     // Besser: Zuerst die Fahrt selektieren und prüfen.
            ),
            array( '%d', '%s', '%s' ), // Format für Daten
            array( '%d', '%s' )        // Format für WHERE-Klausel
        );

        // Da $wpdb->update nicht gut mit 'driver_id IS NULL' umgeht, machen wir eine separate Prüfung:
        $ride_check = $wpdb->get_row($wpdb->prepare("SELECT driver_id, status FROM {$table_name} WHERE id = %d", $ride_id));
        if (!$ride_check || $ride_check->status !== 'accepted' || (int)$ride_check->driver_id !== (int)$driver_id) {
             // Wenn das Update oben fehlschlug, weil driver_id nicht null war oder status nicht pending,
             // oder wenn ein anderer Fahrer schneller war und die Fahrt angenommen hat.
             // Setze den Status zurück, falls unser Update fehlschlug, aber die ID korrekt war.
             // Dies ist eine Vereinfachung. Eine robustere Lösung würde Transaktionen erfordern, wenn die DB es unterstützt.
            if ($updated === false && $ride_check && (int)$ride_check->driver_id !== (int)$driver_id && $ride_check->status === 'accepted') {
                 // Ein anderer Fahrer hat es bekommen
            } else if ($updated === false && $ride_check && $ride_check->status !== 'pending') {
                // Status war nicht mehr pending
            }

            // Wenn $updated 0 ist, konnte die Zeile nicht gefunden werden oder die Werte waren schon so.
            // Wenn $updated false ist, gab es einen DB Fehler.
            if($updated === 0 && $ride_check && ( (int)$ride_check->driver_id !== (int)$driver_id || $ride_check->status !== 'accepted') ){
                 wp_send_json_error( array( 'message' => __('Fahrt konnte nicht angenommen werden. Eventuell bereits vergeben oder nicht mehr verfügbar.', 'wp-taxi-plugin') ) );
                 return;
            }
            if ($updated === false ) {
                 wp_send_json_error( array( 'message' => __('Datenbankfehler beim Annehmen der Fahrt.', 'wp-taxi-plugin') ) );
                 return;
            }
        }


        if ( $updated !== false ) { // updated kann 0 sein, wenn nichts geändert wurde, aber nicht false bei Fehler
            update_user_meta( $driver_id, 'driver_available', '0' );
            // TODO: Benachrichtige den Kunden, dass die Fahrt angenommen wurde.
            // z.B. set_transient('wp_taxi_ride_status_' . $ride_id . '_customer_' . $ride_check->customer_id, array('status' => 'accepted', 'driver_name' => $user->display_name), HOUR_IN_SECONDS);
            wp_send_json_success( array( 'message' => __('Fahrt erfolgreich angenommen.', 'wp-taxi-plugin'), 'ride_id' => $ride_id ) );
        } else {
             wp_send_json_error( array( 'message' => __('Fahrt konnte nicht angenommen werden oder Datenbankfehler.', 'wp-taxi-plugin') ) );
        }
    }


    /**
     * AJAX Handler: Fahrerstatus (Verfügbarkeit, Standort) aktualisieren.
     */
    public static function ajax_update_driver_status() {
        check_ajax_referer( 'wp_taxi_update_driver_status_nonce', 'nonce' );
        $driver_id = get_current_user_id();

        if ( !$driver_id ) {
            wp_send_json_error( array( 'message' => __('Ungültige Anfrage.', 'wp-taxi-plugin') ) );
            return;
        }
        // Sicherstellen, dass der Benutzer ein Fahrer ist
        // ...

        $availability = isset($_POST['availability']) ? sanitize_text_field($_POST['availability']) : null;
        $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;

        if ( $availability !== null ) {
            update_user_meta( $driver_id, 'driver_available', $availability === '1' ? '1' : '0' );
        }
        if ( $latitude && $longitude ) {
            update_user_meta( $driver_id, 'driver_last_lat', $latitude );
            update_user_meta( $driver_id, 'driver_last_lng', $longitude );
            update_user_meta( $driver_id, 'driver_last_location_update', current_time('mysql') );
        }

        wp_send_json_success( array( 'message' => __('Status erfolgreich aktualisiert.', 'wp-taxi-plugin') ) );
    }

    /**
     * AJAX Handler: Status einer Fahrt aktualisieren (z.B. gestartet, abgeschlossen, storniert).
     */
    public static function ajax_update_ride_status() {
        check_ajax_referer( 'wp_taxi_update_ride_status_nonce', 'nonce' );
        $ride_id = isset($_POST['ride_id']) ? intval($_POST['ride_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
        $driver_id = get_current_user_id();

        if ( !$ride_id || !$driver_id || !in_array($new_status, ['ongoing', 'completed', 'cancelled']) ) {
            wp_send_json_error( array( 'message' => __('Ungültige Anfrage oder Status.', 'wp-taxi-plugin') ) );
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error( array( 'message' => __('Datenbank nicht bereit.', 'wp-taxi-plugin') ) );
            return;
        }

        // Prüfen, ob die Fahrt dem Fahrer gehört
        $current_ride = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d AND driver_id = %d", $ride_id, $driver_id));
        if (!$current_ride) {
            wp_send_json_error( array( 'message' => __('Fahrt nicht gefunden oder nicht Ihre Fahrt.', 'wp-taxi-plugin') ) );
            return;
        }

        // Logik für Statusübergänge
        $valid_transition = false;
        if ($current_ride->status === 'accepted' && $new_status === 'ongoing') { $valid_transition = true; }
        else if ($current_ride->status === 'ongoing' && $new_status === 'completed') { $valid_transition = true; }
        else if (in_array($current_ride->status, ['accepted', 'ongoing']) && $new_status === 'cancelled') { $valid_transition = true; }

        if (!$valid_transition) {
            wp_send_json_error( array( 'message' => __('Ungültiger Statusübergang.', 'wp-taxi-plugin') ) );
            return;
        }

        $updated = $wpdb->update(
            $table_name,
            array( 'status' => $new_status, 'updated_at' => current_time('mysql') ),
            array( 'id' => $ride_id, 'driver_id' => $driver_id ), // Stelle sicher, dass es immer noch diesem Fahrer gehört
            array( '%s', '%s' ),
            array( '%d', '%d' )
        );

        if ($updated !== false) { // $updated kann 0 sein, wenn der Status bereits der neue Status war.
            if (in_array($new_status, ['completed', 'cancelled'])) {
                update_user_meta( $driver_id, 'driver_available', '1' );
            }
            // TODO: Kunden benachrichtigen
            // z.B. set_transient('wp_taxi_ride_status_' . $ride_id . '_customer_' . $current_ride->customer_id, array('status' => $new_status), HOUR_IN_SECONDS);
            wp_send_json_success( array( 'message' => __('Fahrtstatus erfolgreich aktualisiert.', 'wp-taxi-plugin'), 'new_status' => $new_status ) );
        } else {
            wp_send_json_error( array( 'message' => __('Fehler beim Aktualisieren des Fahrtstatus.', 'wp-taxi-plugin') ) );
        }
    }

    /**
     * AJAX Handler: Aktuelle Fahrt des Fahrers abrufen (accepted oder ongoing).
     */
    public static function ajax_get_current_ride() {
        check_ajax_referer( 'wp_taxi_get_current_ride_nonce', 'nonce' );
        $driver_id = get_current_user_id();

        if ( !$driver_id ) {
            wp_send_json_error( array( 'message' => __('Ungültige Anfrage.', 'wp-taxi-plugin') ) );
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error( array( 'message' => __('Datenbank nicht bereit.', 'wp-taxi-plugin') ) );
            return;
        }

        $current_ride = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE driver_id = %d AND status IN ('accepted', 'ongoing') ORDER BY updated_at DESC LIMIT 1",
            $driver_id
        ) );

        if ( $current_ride ) {
            $customer = get_userdata($current_ride->customer_id);
            $current_ride->customer_name = $customer ? esc_html($customer->display_name) : __('Unbekannt', 'wp-taxi-plugin');
            // Versuche, eine Telefonnummer zu bekommen (Beispiel: WooCommerce billing_phone)
            $current_ride->customer_phone = $customer ? get_user_meta($current_ride->customer_id, 'billing_phone', true) : '';
            if(empty($current_ride->customer_phone)) {
                 $current_ride->customer_phone = $customer ? get_user_meta($current_ride->customer_id, 'phone_number', true) : ''; // Anderes mögliches Meta-Feld
            }
             // Format price
            $current_ride->price = $current_ride->price ? number_format_i18n(floatval($current_ride->price), 2) : 'N/A';
            // Lat/Lng als float für JS
            $current_ride->start_lat = floatval($current_ride->start_lat);
            $current_ride->start_lng = floatval($current_ride->start_lng);
            $current_ride->end_lat = $current_ride->end_lat ? floatval($current_ride->end_lat) : null;
            $current_ride->end_lng = $current_ride->end_lng ? floatval($current_ride->end_lng) : null;


            wp_send_json_success( $current_ride );
        } else {
             wp_send_json_success( null ); // Kein Fehler, einfach keine aktive Fahrt
        }
    }
}

WP_Taxi_Driver_Dashboard_Shortcode::init();

?>
