<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Taxi_Driver_Dashboard_Shortcode {

    private static $google_maps_api_key = '';

    public static function init() {
        self::$google_maps_api_key = get_option('wp_taxi_plugin_google_maps_api_key', '');
        if (empty(self::$google_maps_api_key)) {
            self::$google_maps_api_key = 'AIzaSyBbWGNbH7yEXc7mtQrwvQEPmghYfr9-glI'; // Fallback
        }

        add_shortcode( 'wp_taxi_driver_dashboard', array( __CLASS__, 'dashboard_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

        add_action( 'wp_ajax_wp_taxi_get_pending_rides', array( __CLASS__, 'ajax_get_pending_rides' ) );
        add_action( 'wp_ajax_wp_taxi_accept_ride', array( __CLASS__, 'ajax_accept_ride' ) );
        add_action( 'wp_ajax_wp_taxi_update_ride_status', array( __CLASS__, 'ajax_update_ride_status' ) );
        add_action( 'wp_ajax_wp_taxi_update_driver_status', array( __CLASS__, 'ajax_update_driver_status' ) );
        add_action( 'wp_ajax_wp_taxi_update_driver_location', array( __CLASS__, 'ajax_update_driver_location' ) );
    }

    public static function enqueue_scripts() {
        if ( is_page() ) {
            global $post;
            if ( $post && has_shortcode( $post->post_content, 'wp_taxi_driver_dashboard' ) ) {
                wp_enqueue_style( 'wp-taxi-driver-dashboard-style', WP_TAXI_PLUGIN_URL . 'assets/css/driver-dashboard.css', array(), WP_TAXI_PLUGIN_VERSION );

                if ( !empty(self::$google_maps_api_key) ) {
                    wp_enqueue_script( 'google-maps-api-driver', 'https://maps.googleapis.com/maps/api/js?key=' . self::$google_maps_api_key . '&libraries=geometry,places&callback=initMapDriver', array(), null, true );
                } else {
                    wp_add_inline_script('jquery', 'console.error("Google Maps API Key is missing for WP Taxi Plugin (Driver).");');
                }

                wp_enqueue_script( 'wp-taxi-driver-dashboard-script', WP_TAXI_PLUGIN_URL . 'assets/js/driver-dashboard.js', array( 'jquery' ), WP_TAXI_PLUGIN_VERSION, true );
                wp_localize_script( 'wp-taxi-driver-dashboard-script', 'wp_taxi_driver_params', array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'google_maps_api_key' => self::$google_maps_api_key,
                    'text_error_loading_rides' => __('Fehler beim Laden der Fahrten.', 'wp-taxi-plugin'),
                    'text_no_pending_rides' => __('Keine ausstehenden Fahrten gefunden.', 'wp-taxi-plugin'),
                    'text_ride_accepted' => __('Fahrt angenommen. Navigiere zum Kunden.', 'wp-taxi-plugin'),
                    'text_ride_accept_error' => __('Fehler beim Annehmen der Fahrt.', 'wp-taxi-plugin'),
                    'text_status_updated' => __('Status aktualisiert.', 'wp-taxi-plugin'),
                    'text_status_update_error' => __('Fehler beim Aktualisieren des Status.', 'wp-taxi-plugin'),
                    'text_location_updated' => __('Standort aktualisiert.', 'wp-taxi-plugin'),
                    'text_location_update_error' => __('Fehler beim Aktualisieren des Standorts.', 'wp-taxi-plugin'),
                    'nonce_get_rides' => wp_create_nonce('wp_taxi_get_pending_rides_nonce'),
                    'nonce_accept_ride' => wp_create_nonce('wp_taxi_accept_ride_nonce'),
                    'nonce_update_status' => wp_create_nonce('wp_taxi_update_ride_status_nonce'),
                    'nonce_update_driver_status' => wp_create_nonce('wp_taxi_update_driver_status_nonce'),
                    'nonce_update_location' => wp_create_nonce('wp_taxi_update_driver_location_nonce'),
                ) );
            }
        }
    }

    public static function dashboard_shortcode() {
        if ( ! is_user_logged_in() ) {
            $login_page_id = get_option('wp_taxi_plugin_login_page_id');
            $login_url = $login_page_id ? get_permalink($login_page_id) : wp_login_url(get_permalink());
            return '<p>' . __( 'Bitte melden Sie sich an, um Ihr Fahrer-Dashboard anzuzeigen.', 'wp-taxi-plugin' ) . ' <a href="' . esc_url( $login_url ) . '">' . __( 'Anmelden', 'wp-taxi-plugin' ) . '</a></p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'driver', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
            return '<p>' . __( 'Dieses Dashboard ist nur für Fahrer zugänglich.', 'wp-taxi-plugin' ) . '</p>';
        }

        $is_approved = get_user_meta( $user->ID, 'driver_approved', true );
        if ( ! $is_approved && !in_array( 'administrator', (array) $user->roles )) {
            return '<p>' . __( 'Ihr Fahrerkonto wurde noch nicht vom Administrator genehmigt. Bitte haben Sie etwas Geduld.', 'wp-taxi-plugin' ) . '</p>';
        }

        if ( empty(self::$google_maps_api_key) ) {
             return '<p style="color:red;">' . sprintf(__('Google Maps API Key ist nicht konfiguriert. Bitte kontaktieren Sie den <a href="%s">Administrator</a> oder überprüfen Sie die <a href="%s">Plugin-Einstellungen</a>.', 'wp-taxi-plugin'), get_edit_user_link(get_option('admin_email')), admin_url('admin.php?page=wp-taxi-plugin')) . '</p>';
        }

        $is_available = get_user_meta( $user->ID, 'driver_available', true );

        ob_start();
        ?>
        <div id="wp-taxi-driver-dashboard">
            <h2><?php _e( 'Fahrer-Dashboard', 'wp-taxi-plugin' ); ?></h2>

            <div class="driver-status-section">
                <h3><?php _e( 'Mein Status', 'wp-taxi-plugin' ); ?></h3>
                <p>
                    <?php _e( 'Aktueller Status:', 'wp-taxi-plugin' ); ?>
                    <strong id="current-driver-status-text"><?php echo $is_available ? __('Verfügbar', 'wp-taxi-plugin') : __('Nicht verfügbar', 'wp-taxi-plugin'); ?></strong>
                </p>
                <label class="switch">
                    <input type="checkbox" id="driver-availability-toggle" <?php checked( $is_available, true ); ?>>
                    <span class="slider round"></span>
                </label>
                <span id="driver-status-message" style="margin-left: 10px;"></span>
                 <p style="font-size:0.9em; color: #555;"><?php _e('Ihr Standort wird automatisch aktualisiert, wenn Sie als "Verfügbar" markiert sind.', 'wp-taxi-plugin');?></p>
            </div>
            <hr>

            <div class="pending-rides-section">
                <h3><?php _e( 'Ausstehende Fahrtanfragen', 'wp-taxi-plugin' ); ?></h3>
                <div id="pending-rides-list">
                    <p><?php _e('Lade Fahrten...', 'wp-taxi-plugin'); ?></p>
                </div>
            </div>
            <hr>

            <div class="current-ride-section">
                <h3><?php _e( 'Aktuelle Fahrt', 'wp-taxi-plugin' ); ?></h3>
                <div id="current-ride-details">
                    <p><?php _e('Sie haben derzeit keine aktive Fahrt.', 'wp-taxi-plugin'); ?></p>
                </div>
                <div id="driver-map-container" style="height: 350px; width: 100%; margin-bottom: 20px; background-color: #e0e0e0;">
                    <p style="text-align:center; padding-top: 150px;"><?php _e('Karte wird geladen...', 'wp-taxi-plugin');?></p>
                </div>
            </div>
            <hr>

            <h3><?php _e( 'Meine abgeschlossenen Fahrten', 'wp-taxi-plugin' ); ?></h3>
            <div id="driver-rides-history">
                <?php self::display_ride_history($user->ID); ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    public static function display_ride_history($driver_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';

        $rides = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE driver_id = %d AND status = 'completed' ORDER BY completed_at DESC LIMIT 10",
            $driver_id
        ) );

        if ( $rides ) {
            echo '<ul>';
            foreach ( $rides as $ride ) {
                $customer = get_userdata($ride->customer_id);
                $customer_name = $customer ? esc_html($customer->display_name) : __('Unbekannt', 'wp-taxi-plugin');
                echo '<li>';
                echo sprintf(
                    __('Fahrt für %s von %s nach %s am %s - Preis: %s %s', 'wp-taxi-plugin'),
                    $customer_name,
                    esc_html($ride->start_address),
                    esc_html($ride->end_address),
                    date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ride->completed_at ) ),
                    number_format_i18n(floatval($ride->price), 2),
                    get_option('wp_taxi_plugin_currency_symbol', 'CHF')
                );
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __( 'Sie haben noch keine Fahrten abgeschlossen.', 'wp-taxi-plugin' ) . '</p>';
        }
    }

    public static function ajax_get_pending_rides() {
        check_ajax_referer( 'wp_taxi_get_pending_rides_nonce', 'nonce' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';

        $rides = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.*, u.display_name as customer_name
             FROM {$table_name} r
             LEFT JOIN {$wpdb->users} u ON r.customer_id = u.ID
             WHERE r.status = %s AND r.driver_id IS NULL
             ORDER BY r.requested_at ASC",
            'pending'
        ), ARRAY_A );

        if ( is_wp_error($rides) ) {
            wp_send_json_error( array( 'message' => __('Fehler beim Abrufen der Fahrten.', 'wp-taxi-plugin'), 'db_error' => $rides->get_error_message() ) );
        }

        if ( ! empty( $rides ) ) {
            $rides_adjusted = array_map(function($ride){
                if(isset($ride['price'])) $ride['estimated_price'] = $ride['price'];
                if(empty($ride['customer_name'])) $ride['customer_name'] = __('Unbekannt', 'wp-taxi-plugin');
                return $ride;
            }, $rides);
            wp_send_json_success( $rides_adjusted );
        } else {
            wp_send_json_error( array( 'message' => __( 'Keine ausstehenden Fahrten gefunden.', 'wp-taxi-plugin' ) ) );
        }
    }

    public static function ajax_accept_ride() {
        check_ajax_referer( 'wp_taxi_accept_ride_nonce', 'nonce_val' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Nicht angemeldet.', 'wp-taxi-plugin' ) ) );
        }
        $driver_id = get_current_user_id();
        $user = wp_get_current_user();
        if ( ! in_array( 'driver', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
             wp_send_json_error( array( 'message' => __( 'Nur Fahrer können Fahrten annehmen.', 'wp-taxi-plugin' ) ) );
        }

        $ride_id = isset( $_POST['ride_id'] ) ? intval( $_POST['ride_id'] ) : 0;
        if ( ! $ride_id ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Fahrt-ID.', 'wp-taxi-plugin' ) ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';

        $ride = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $ride_id ) );

        if (!$ride) {
            wp_send_json_error( array( 'message' => __( 'Fahrt nicht gefunden.', 'wp-taxi-plugin' ) ) );
        }
        if ($ride->status !== 'pending' || $ride->driver_id !== null) {
             wp_send_json_error( array( 'message' => __( 'Diese Fahrt ist nicht mehr verfügbar oder wurde bereits angenommen.', 'wp-taxi-plugin' ) ) );
        }

        $updated = $wpdb->update(
            $table_name,
            array(
                'driver_id' => $driver_id,
                'status' => 'accepted',
                'accepted_at' => current_time( 'mysql', 1 ),
                'updated_at' => current_time( 'mysql', 1 )
            ),
            array( 'id' => $ride_id, 'status' => 'pending', 'driver_id' => null ),
            array( '%d', '%s', '%s', '%s' ),
            array( '%d', '%s', '%d' )
        );

        if ( $updated ) {
            update_user_meta( $driver_id, 'driver_available', false );
            update_user_meta( $driver_id, 'driver_current_ride_id', $ride_id);

            if ($ride) {
                do_action( 'wp_taxi_ride_accepted', $ride_id, $driver_id, $ride->customer_id );
            }

            $ride_details_full = $wpdb->get_row( $wpdb->prepare(
                "SELECT r.*, u.display_name as customer_name
                 FROM {$table_name} r
                 LEFT JOIN {$wpdb->users} u ON r.customer_id = u.ID
                 WHERE r.id = %d", $ride_id
            ), ARRAY_A);

            wp_send_json_success( array(
                'message' => __( 'Fahrt erfolgreich angenommen!', 'wp-taxi-plugin' ),
                'ride_details' => $ride_details_full
            ) );
        } else {
            $current_ride_status = $wpdb->get_row($wpdb->prepare("SELECT status, driver_id FROM $table_name WHERE id = %d", $ride_id));
            if ($current_ride_status && ($current_ride_status->status !== 'pending' || $current_ride_status->driver_id !== null)) {
                 wp_send_json_error( array( 'message' => __( 'Diese Fahrt wurde in der Zwischenzeit von einem anderen Fahrer angenommen.', 'wp-taxi-plugin' ) ) );
            } else {
                 wp_send_json_error( array( 'message' => __( 'Fehler beim Annehmen der Fahrt in der Datenbank.', 'wp-taxi-plugin' ), 'db_error' => $wpdb->last_error ) );
            }
        }
    }

    public static function ajax_update_ride_status() {
        check_ajax_referer( 'wp_taxi_update_ride_status_nonce', 'nonce_val' );
         if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Nicht angemeldet.', 'wp-taxi-plugin' ) ) );
        }
        $driver_id = get_current_user_id();

        $ride_id = isset( $_POST['ride_id'] ) ? intval( $_POST['ride_id'] ) : 0;
        $new_status = isset( $_POST['new_status'] ) ? sanitize_text_field( $_POST['new_status'] ) : '';
        $allowed_statuses = array('ongoing', 'completed', 'cancelled_by_driver');

        if ( ! $ride_id || !in_array($new_status, $allowed_statuses) ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Daten für Statusupdate.', 'wp-taxi-plugin' ) ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';

        // Alten Status und Kundendaten für Hook holen
        $ride_before_update = $wpdb->get_row($wpdb->prepare("SELECT status, customer_id FROM $table_name WHERE id = %d AND driver_id = %d", $ride_id, $driver_id));
        if (!$ride_before_update) {
            wp_send_json_error( array( 'message' => __( 'Fahrt nicht gefunden oder nicht Ihre Fahrt.', 'wp-taxi-plugin' ) ) );
        }
        $old_status = $ride_before_update->status;
        $customer_id = $ride_before_update->customer_id;

        $update_data = array(
            'status' => $new_status,
            'updated_at' => current_time( 'mysql', 1 )
        );
        $update_data_formats = array('%s', '%s');

        if ($new_status === 'ongoing') {
            $update_data['started_at'] = current_time( 'mysql', 1 );
            $update_data_formats[] = '%s';
        } elseif ($new_status === 'completed') {
            $update_data['completed_at'] = current_time( 'mysql', 1 );
            $update_data_formats[] = '%s';
        }

        $updated = $wpdb->update(
            $table_name,
            $update_data,
            array( 'id' => $ride_id, 'driver_id' => $driver_id ),
            $update_data_formats,
            array( '%d', '%d' )
        );

        if ( $updated !== false ) {
             if ($new_status === 'completed' || $new_status === 'cancelled_by_driver') {
                 update_user_meta( $driver_id, 'driver_available', true );
                 delete_user_meta( $driver_id, 'driver_current_ride_id');
             }
            do_action( 'wp_taxi_ride_status_updated', $ride_id, $new_status, $old_status, $driver_id, $customer_id );
            wp_send_json_success( array( 'message' => __( 'Fahrtstatus aktualisiert.', 'wp-taxi-plugin' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Fehler beim Aktualisieren des Fahrtstatus.', 'wp-taxi-plugin' ), 'db_error' => $wpdb->last_error ) );
        }
    }

    public static function ajax_update_driver_status() {
        check_ajax_referer( 'wp_taxi_update_driver_status_nonce', 'nonce_val' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Nicht angemeldet.', 'wp-taxi-plugin' ) ) );
        }
        $driver_id = get_current_user_id();
        $user = wp_get_current_user();
         if ( ! in_array( 'driver', (array) $user->roles ) && !in_array( 'administrator', (array) $user->roles )) {
            wp_send_json_error( array( 'message' => __( 'Aktion nicht erlaubt.', 'wp-taxi-plugin' ) ) );
        }

        $is_available = isset( $_POST['is_available'] ) && $_POST['is_available'] === 'true';
        update_user_meta( $driver_id, 'driver_available', $is_available );

        if (!$is_available) { // Wenn nicht verfügbar, auch die Current Ride ID löschen, falls eine Fahrt abgebrochen wurde ohne "Abgeschlossen"
           // delete_user_meta($driver_id, 'driver_current_ride_id'); // Überlegung: Nur löschen wenn wirklich keine aktive Fahrt mehr.
        }


        wp_send_json_success( array(
            'message' => $is_available ? __('Sie sind jetzt als verfügbar markiert.', 'wp-taxi-plugin') : __('Sie sind jetzt als nicht verfügbar markiert.', 'wp-taxi-plugin'),
            'new_status_text' => $is_available ? __('Verfügbar', 'wp-taxi-plugin') : __('Nicht verfügbar', 'wp-taxi-plugin')
        ) );
    }

    public static function ajax_update_driver_location() {
        check_ajax_referer( 'wp_taxi_update_driver_location_nonce', 'nonce_val' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Nicht angemeldet.', 'wp-taxi-plugin' ) ) );
        }
        $driver_id = get_current_user_id();
        $user = wp_get_current_user();
         if ( ! in_array( 'driver', (array) $user->roles ) && !in_array( 'administrator', (array) $user->roles )) {
            wp_send_json_error( array( 'message' => __( 'Aktion nicht erlaubt.', 'wp-taxi-plugin' ) ) );
        }

        $lat = isset( $_POST['latitude'] ) ? floatval( $_POST['latitude'] ) : null;
        $lng = isset( $_POST['longitude'] ) ? floatval( $_POST['longitude'] ) : null;

        if ( $lat !== null && $lng !== null ) {
            update_user_meta( $driver_id, 'driver_last_lat', $lat );
            update_user_meta( $driver_id, 'driver_last_lng', $lng );
            update_user_meta( $driver_id, 'driver_last_location_update', current_time('timestamp') );
            wp_send_json_success( array( 'message' => __( 'Standort aktualisiert.', 'wp-taxi-plugin' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Ungültige Standortdaten.', 'wp-taxi-plugin' ) ) );
        }
    }
}

WP_Taxi_Driver_Dashboard_Shortcode::init();

?>
