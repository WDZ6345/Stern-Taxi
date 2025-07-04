<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Taxi_Customer_Dashboard_Shortcode {

    private static $google_maps_api_key = ''; // Wird später aus den Admin-Einstellungen geladen

    public static function init() {
        // API Key laden (Beispiel, wie es gemacht werden könnte)
        // self::$google_maps_api_key = get_option('wp_taxi_plugin_google_maps_api_key', '');
        // For now, using the one provided in the prompt.
        self::$google_maps_api_key = 'AIzaSyBbWGNbH7yEXc7mtQrwvQEPmghYfr9-glI';


        add_shortcode( 'wp_taxi_customer_dashboard', array( __CLASS__, 'dashboard_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_wp_taxi_get_available_drivers', array( __CLASS__, 'ajax_get_available_drivers' ) );
        add_action( 'wp_ajax_wp_taxi_request_ride', array( __CLASS__, 'ajax_request_ride' ) );

    }

    public static function enqueue_scripts() {
        // Nur laden, wenn der Shortcode auf der Seite ist (bessere Performance)
        // Dies ist eine vereinfachte Prüfung. Ideal wäre, dies nur zu tun, wenn has_shortcode() true ist.
        if ( is_page() ) { // Prüfen, ob es eine Seite ist, ggf. spezifischer prüfen
            global $post;
            if ( has_shortcode( $post->post_content, 'wp_taxi_customer_dashboard' ) ) {
                wp_enqueue_style( 'wp-taxi-customer-dashboard-style', WP_TAXI_PLUGIN_URL . 'assets/css/customer-dashboard.css', array(), WP_TAXI_PLUGIN_VERSION );

                if ( !empty(self::$google_maps_api_key) ) {
                    wp_enqueue_script( 'google-maps-api', 'https://maps.googleapis.com/maps/api/js?key=' . self::$google_maps_api_key . '&libraries=places,geometry&callback=initMapCustomer', array(), null, true );
                } else {
                    // Fallback oder Fehlermeldung, wenn kein API Key vorhanden ist
                    wp_add_inline_script('jquery', 'console.error("Google Maps API Key is missing for WP Taxi Plugin.");');
                }

                wp_enqueue_script( 'wp-taxi-customer-dashboard-script', WP_TAXI_PLUGIN_URL . 'assets/js/customer-dashboard.js', array( 'jquery' ), WP_TAXI_PLUGIN_VERSION, true );
                wp_localize_script( 'wp-taxi-customer-dashboard-script', 'wp_taxi_customer_params', array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'google_maps_api_key' => self::$google_maps_api_key,
                    'base_fare' => get_option('wp_taxi_plugin_base_fare', '5.00'),
                    'price_per_km' => get_option('wp_taxi_plugin_price_per_km', '2.50'),
                    'currency_symbol' => get_option('wp_taxi_plugin_currency_symbol', 'CHF'),
                    'text_calculating_route' => __('Route wird berechnet...', 'wp-taxi-plugin'),
                    'text_route_not_found' => __('Route konnte nicht gefunden werden.', 'wp-taxi-plugin'),
                    'text_select_pickup_and_dropoff' => __('Bitte wählen Sie einen Abhol- und Zielort.', 'wp-taxi-plugin'),
                    'text_requesting_ride' => __('Fahrt wird angefragt...', 'wp-taxi-plugin'),
                    'text_ride_requested_successfully' => __('Fahrt erfolgreich angefragt. Warte auf Bestätigung.', 'wp-taxi-plugin'),
                    'text_ride_request_failed' => __('Fehler bei der Fahrtenanfrage.', 'wp-taxi-plugin'),
                    'text_no_drivers_available' => __('Momentan sind keine Fahrer verfügbar.', 'wp-taxi-plugin'),
                    'text_checking_ride_status' => __('Prüfe Fahrtstatus...', 'wp-taxi-plugin'),
                    'text_ride_accepted_by_driver' => __('Ihre Fahrt wurde von Fahrer %s angenommen!', 'wp-taxi-plugin'), // %s for driver name
                    'text_driver_on_the_way' => __('Fahrer %s ist auf dem Weg.', 'wp-taxi-plugin'),
                    'text_ride_ongoing' => __('Ihre Fahrt mit %s hat begonnen.', 'wp-taxi-plugin'),
                    'text_ride_completed_customer' => __('Ihre Fahrt wurde abgeschlossen. Vielen Dank!', 'wp-taxi-plugin'),
                    'text_ride_cancelled_customer' => __('Ihre Fahrt wurde leider storniert.', 'wp-taxi-plugin'),
                    'nonce_get_drivers' => wp_create_nonce('wp_taxi_get_available_drivers_nonce'),
                    'nonce_request_ride' => wp_create_nonce('wp_taxi_request_ride_nonce'),
                ) );
            }
        }
    }

    public static function dashboard_shortcode() {
        if ( ! is_user_logged_in() ) {
            // TODO: Link zur Anmeldeseite, die in den Einstellungen konfiguriert werden kann
            return '<p>' . __( 'Bitte melden Sie sich an, um Ihr Dashboard anzuzeigen.', 'wp-taxi-plugin' ) . ' <a href="' . esc_url( home_url( '/anmelden' ) ) . '">' . __( 'Anmelden', 'wp-taxi-plugin' ) . '</a></p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'customer', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
            return '<p>' . __( 'Dieses Dashboard ist nur für Kunden zugänglich.', 'wp-taxi-plugin' ) . '</p>';
        }

        if ( empty(self::$google_maps_api_key) ) {
            return '<p style="color:red;">' . __( 'Google Maps API Key ist nicht konfiguriert. Bitte kontaktieren Sie den Administrator.', 'wp-taxi-plugin') . '</p>';
        }

        ob_start();
        ?>
        <div id="wp-taxi-customer-dashboard">
            <h2><?php _e( 'Willkommen in Ihrem Taxi-Dashboard', 'wp-taxi-plugin' ); ?></h2>

            <div id="booking-form">
                <h3><?php _e( 'Neue Fahrt buchen', 'wp-taxi-plugin' ); ?></h3>
                <p>
                    <label for="pickup-address"><?php _e( 'Abholort:', 'wp-taxi-plugin' ); ?></label>
                    <input type="text" id="pickup-address" name="pickup_address" placeholder="<?php _e( 'Geben Sie die Abholadresse ein', 'wp-taxi-plugin' ); ?>" required>
                    <input type="hidden" id="pickup-lat" name="pickup_lat">
                    <input type="hidden" id="pickup-lng" name="pickup_lng">
                </p>
                <p>
                    <label for="dropoff-address"><?php _e( 'Zielort:', 'wp-taxi-plugin' ); ?></label>
                    <input type="text" id="dropoff-address" name="dropoff_address" placeholder="<?php _e( 'Geben Sie die Zieladresse ein', 'wp-taxi-plugin' ); ?>" required>
                    <input type="hidden" id="dropoff-lat" name="dropoff_lat">
                    <input type="hidden" id="dropoff-lng" name="dropoff_lng">
                </p>
                <div id="route-info" style="margin-bottom: 15px;">
                    <p><?php _e('Distanz:', 'wp-taxi-plugin'); ?> <span id="route-distance">---</span> km</p>
                    <p><?php _e('Geschätzte Fahrzeit:', 'wp-taxi-plugin'); ?> <span id="route-duration">---</span></p>
                    <p><?php _e('Geschätzter Preis:', 'wp-taxi-plugin'); ?> <?php echo esc_html(get_option('wp_taxi_plugin_currency_symbol', 'CHF')); ?> <span id="route-price">---</span></p>
                </div>
                <button id="request-ride-btn"><?php _e( 'Taxi anfragen', 'wp-taxi-plugin' ); ?></button>
                <div id="booking-message" style="margin-top:10px;"></div>
            </div>

            <hr>

            <h3><?php _e( 'Verfügbare Taxis in Ihrer Nähe', 'wp-taxi-plugin' ); ?></h3>
            <div id="customer-map-container" style="height: 400px; width: 100%; margin-bottom: 20px; background-color: #e0e0e0;">
                <!-- Karte wird hier per JS initialisiert -->
                 <p style="text-align:center; padding-top: 180px;"><?php _e('Karte wird geladen...', 'wp-taxi-plugin');?></p>
            </div>

            <hr>
            <h3><?php _e( 'Meine Fahrten (Historie)', 'wp-taxi-plugin' ); ?></h3>
            <div id="customer-rides-history">
                <?php self::display_ride_history(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Zeigt die Fahrtenhistorie des Kunden an.
     * Diese Funktion wird später mit echten Daten gefüllt.
     */
    public static function display_ride_history() {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'taxi_rides';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<p>' . __('Die Fahrtenhistorie ist momentan nicht verfügbar, da die Datenbanktabelle fehlt.', 'wp-taxi-plugin') . '</p>';
            echo '<p>' . __('Bitte kontaktieren Sie den Administrator oder versuchen Sie, das Plugin neu zu aktivieren.', 'wp-taxi-plugin') . '</p>';
            return;
        }

        $rides = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE customer_id = %d ORDER BY created_at DESC LIMIT 10",
            $current_user_id
        ) );

        if ( $rides ) {
            echo '<ul class="wp-taxi-ride-history-list">';
            foreach ( $rides as $ride ) {
                $driver_name = __('Warte auf Fahrer', 'wp-taxi-plugin');
                if ($ride->driver_id) {
                    $driver_info = get_userdata($ride->driver_id);
                    $driver_name = $driver_info ? esc_html($driver_info->display_name) : __('Unbekannter Fahrer', 'wp-taxi-plugin');
                }

                echo '<li>';
                echo '<strong>' . sprintf(__('Fahrt am %s', 'wp-taxi-plugin'),
                       date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ride->created_at ) )) . '</strong><br>';
                echo sprintf( __('Von: %s', 'wp-taxi-plugin'), esc_html($ride->start_address)) . '<br>';
                echo sprintf( __('Nach: %s', 'wp-taxi-plugin'), esc_html($ride->end_address) ) . '<br>';
                echo sprintf( __('Status: %s', 'wp-taxi-plugin'), '<em>' . esc_html(self::get_status_translation($ride->status)) . '</em>' );
                if ($ride->driver_id) {
                     echo ' (' . sprintf(__('Fahrer: %s', 'wp-taxi-plugin'), $driver_name) . ')';
                }
                if ($ride->price) {
                    echo ' - ' . sprintf(__('Preis: %s %.2f', 'wp-taxi-plugin'), esc_html($ride->currency_symbol), floatval($ride->price));
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __( 'Sie haben noch keine Fahrten gebucht.', 'wp-taxi-plugin' ) . '</p>';
        }
    }

    /**
     * Übersetzt Status-Strings.
     */
    private static function get_status_translation( $status ) {
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
     * AJAX Handler: Verfügbare Fahrer abrufen.
     * Dies ist eine vereinfachte Version. In einer echten App wäre die Standortlogik komplexer.
     */
    public static function ajax_get_available_drivers() {
        check_ajax_referer( 'wp_taxi_get_available_drivers_nonce', 'nonce' );

        $drivers_data = array();
        $args = array(
            'role'    => 'driver',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'driver_approved', // Nur genehmigte Fahrer
                    'value' => '1', // oder true, je nachdem wie es gespeichert wird
                    'compare' => '='
                ),
                array(
                    'key' => 'driver_available', // Nur Fahrer, die sich als verfügbar markiert haben
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => 'driver_last_lat', // Fahrer muss eine aktuelle Position haben
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
            if ( $lat && $lng ) {
                $drivers_data[] = array(
                    'id'        => $driver->ID,
                    'name'      => $driver->display_name,
                    'latitude'  => floatval($lat),
                    'longitude' => floatval($lng),
                    'vehicle_model' => get_user_meta( $driver->ID, 'vehicle_model', true ) ?: __('Unbekanntes Modell', 'wp-taxi-plugin'),
                );
            }
        }

        if ( ! empty( $drivers_data ) ) {
            wp_send_json_success( $drivers_data );
        } else {
            wp_send_json_error( array( 'message' => __( 'Keine verfügbaren Fahrer gefunden.', 'wp-taxi-plugin' ) ) );
        }
    }

    /**
     * AJAX Handler: Fahrt anfragen.
     */
    public static function ajax_request_ride() {
        check_ajax_referer( 'wp_taxi_request_ride_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Sie müssen angemeldet sein, um eine Fahrt anzufragen.', 'wp-taxi-plugin' ) ) );
            return;
        }

        $current_user_id = get_current_user_id();
        $user = wp_get_current_user();
        if ( ! in_array( 'customer', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
             wp_send_json_error( array( 'message' => __( 'Nur Kunden können Fahrten anfragen.', 'wp-taxi-plugin' ) ) );
            return;
        }

        // Daten aus dem POST-Request holen und validieren
        $pickup_address = sanitize_text_field( $_POST['pickup_address'] );
        $dropoff_address = sanitize_text_field( $_POST['dropoff_address'] );
        $pickup_lat = floatval( $_POST['pickup_lat'] );
        $pickup_lng = floatval( $_POST['pickup_lng'] );
        $dropoff_lat = isset($_POST['dropoff_lat']) ? floatval( $_POST['dropoff_lat'] ) : null;
        $dropoff_lng = isset($_POST['dropoff_lng']) ? floatval( $_POST['dropoff_lng'] ) : null;
        // Sanitize price coming from JS. JS toFixed(2) produces a string with a dot.
        $estimated_price_str = isset($_POST['estimated_price']) ? sanitize_text_field($_POST['estimated_price']) : null;
        $estimated_price = $estimated_price_str ? floatval(str_replace(',', '.', $estimated_price_str)) : null;
        $currency_symbol = get_option('wp_taxi_plugin_currency_symbol', 'CHF');


        if ( empty( $pickup_address ) || empty( $dropoff_address ) || empty( $pickup_lat ) || empty( $pickup_lng ) ) {
            wp_send_json_error( array( 'message' => __( 'Abhol- und Zielort sind erforderlich.', 'wp-taxi-plugin' ) ) );
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';

        // Prüfen, ob Tabelle existiert (sollte im Installer passiert sein)
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
             wp_send_json_error( array( 'message' => __( 'Datenbanktabelle für Fahrten nicht gefunden. Bitte Plugin neu aktivieren oder Administrator kontaktieren.', 'wp-taxi-plugin' ) ) );
            return;
        }

        $ride_data = array(
            'customer_id'   => $current_user_id,
            'start_address' => $pickup_address,
            'end_address'   => $dropoff_address,
            'start_lat'     => (string) $pickup_lat, // Store as string
            'start_lng'     => (string) $pickup_lng, // Store as string
            'end_lat'       => $dropoff_lat ? (string) $dropoff_lat : null,
            'end_lng'       => $dropoff_lng ? (string) $dropoff_lng : null,
            'status'        => 'pending', // 'pending', 'accepted', 'ongoing', 'completed', 'cancelled'
            'price'         => $estimated_price,
            'currency_symbol' => $currency_symbol,
            'created_at'    => current_time( 'mysql' ),
            'updated_at'    => current_time( 'mysql' ),
        );

        $format = array(
            '%d', // customer_id
            '%s', // start_address
            '%s', // end_address
            '%s', // start_lat
            '%s', // start_lng
            '%s', // end_lat
            '%s', // end_lng
            '%s', // status
            '%f', // price
            '%s', // currency_symbol
            '%s', // created_at
            '%s', // updated_at
        );

        $result = $wpdb->insert( $table_name, $ride_data, $format );

        if ( $result ) {
            $ride_id = $wpdb->insert_id;
            // TODO: Benachrichtige verfügbare Fahrer über die neue Fahrt (z.B. per E-Mail, Push-Benachrichtigung oder AJAX-Polling auf Fahrerseite)
            // Für dieses Beispiel senden wir einfach eine Erfolgsmeldung.
            wp_send_json_success( array(
                'message' => __( 'Fahrt erfolgreich angefragt. Warte auf Bestätigung.', 'wp-taxi-plugin' ),
                'ride_id' => $ride_id
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Fehler beim Speichern der Fahrt in der Datenbank.', 'wp-taxi-plugin' ),
                'db_error' => $wpdb->last_error // Nur für Debugging, im Produktivbetrieb ggf. entfernen
            ) );
        }
    }
}

WP_Taxi_Customer_Dashboard_Shortcode::init();

?>
