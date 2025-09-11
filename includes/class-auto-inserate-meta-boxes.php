<?php
/**
 * Fügt Meta-Boxen für Fahrzeugdetails hinzu.
 *
 * @package AutoInserate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Auto_Inserate_Meta_Boxes Klasse.
 */
class Auto_Inserate_Meta_Boxes {

    /**
     * Hook in WordPress.
     */
    public function init() {
        add_action( 'add_meta_boxes', array( $this, 'add_fahrzeug_details_meta_box' ) );
        add_action( 'save_post_fahrzeug', array( $this, 'save_fahrzeug_details_meta_data' ), 10, 2 );
    }

    /**
     * Fügt die Meta-Box für Fahrzeugdetails hinzu.
     */
    public function add_fahrzeug_details_meta_box() {
        add_meta_box(
            'auto_inserate_fahrzeug_details', // ID der Meta-Box
            __( 'Fahrzeugdetails', 'auto-inserate' ), // Titel der Meta-Box
            array( $this, 'render_fahrzeug_details_meta_box' ), // Callback-Funktion zum Rendern des Inhalts
            'fahrzeug', // Post Type
            'normal', // Kontext (normal, side, advanced)
            'high' // Priorität (high, core, default, low)
        );
    }

    /**
     * Rendert den Inhalt der Meta-Box für Fahrzeugdetails.
     *
     * @param WP_Post $post Das aktuelle Post-Objekt.
     */
    public function render_fahrzeug_details_meta_box( $post ) {
        // Nonce-Feld für die Sicherheit hinzufügen
        wp_nonce_field( 'auto_inserate_save_fahrzeug_details', 'auto_inserate_fahrzeug_details_nonce' );

        // Gespeicherte Werte abrufen
        $preis = get_post_meta( $post->ID, '_fahrzeug_preis', true );
        $kilometerstand = get_post_meta( $post->ID, '_fahrzeug_kilometerstand', true );
        $erstzulassung = get_post_meta( $post->ID, '_fahrzeug_erstzulassung', true );
        $leistung = get_post_meta( $post->ID, '_fahrzeug_leistung', true );
        $getriebe = get_post_meta( $post->ID, '_fahrzeug_getriebe', true );
        $farbe = get_post_meta( $post->ID, '_fahrzeug_farbe', true );
        $standort_adresse = get_post_meta( $post->ID, '_fahrzeug_standort_adresse', true );
        ?>
        <table class="form-table">
            <tbody>
                <!-- Preis -->
                <tr>
                    <th scope="row">
                        <label for="fahrzeug_preis"><?php esc_html_e( 'Preis', 'auto-inserate' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="fahrzeug_preis" name="fahrzeug_preis" class="regular-text" value="<?php echo esc_attr( $preis ); ?>" placeholder="<?php esc_attr_e( 'z.B. 15000', 'auto-inserate' ); ?>">
                        <p class="description"><?php esc_html_e( 'Preis in EUR (nur Zahl eingeben).', 'auto-inserate' ); ?></p>
                    </td>
                </tr>
                <!-- Kilometerstand -->
                <tr>
                    <th scope="row">
                        <label for="fahrzeug_kilometerstand"><?php esc_html_e( 'Kilometerstand', 'auto-inserate' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="fahrzeug_kilometerstand" name="fahrzeug_kilometerstand" class="regular-text" value="<?php echo esc_attr( $kilometerstand ); ?>" placeholder="<?php esc_attr_e( 'z.B. 50000', 'auto-inserate' ); ?>">
                        <p class="description"><?php esc_html_e( 'Kilometerstand in km (nur Zahl eingeben).', 'auto-inserate' ); ?></p>
                    </td>
                </tr>
                <!-- Erstzulassung -->
                <tr>
                    <th scope="row">
                        <label for="fahrzeug_erstzulassung"><?php esc_html_e( 'Erstzulassung', 'auto-inserate' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="fahrzeug_erstzulassung" name="fahrzeug_erstzulassung" class="regular-text" value="<?php echo esc_attr( $erstzulassung ); ?>" placeholder="<?php esc_attr_e( 'z.B. 03/2018', 'auto-inserate' ); ?>">
                        <p class="description"><?php esc_html_e( 'Monat und Jahr der Erstzulassung (Format MM/JJJJ).', 'auto-inserate' ); ?></p>
                    </td>
                </tr>
                <!-- Leistung -->
                <tr>
                    <th scope="row">
                        <label for="fahrzeug_leistung"><?php esc_html_e( 'Leistung', 'auto-inserate' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="fahrzeug_leistung" name="fahrzeug_leistung" class="regular-text" value="<?php echo esc_attr( $leistung ); ?>" placeholder="<?php esc_attr_e( 'z.B. 150 PS oder 110 kW', 'auto-inserate' ); ?>">
                    </td>
                </tr>
                <!-- Getriebe -->
                <tr>
                    <th scope="row">
                        <label for="fahrzeug_getriebe"><?php esc_html_e( 'Getriebe', 'auto-inserate' ); ?></label>
                    </th>
                    <td>
                        <select id="fahrzeug_getriebe" name="fahrzeug_getriebe">
                            <option value="manuell" <?php selected( $getriebe, 'manuell' ); ?>><?php esc_html_e( 'Manuell', 'auto-inserate' ); ?></option>
                            <option value="automatik" <?php selected( $getriebe, 'automatik' ); ?>><?php esc_html_e( 'Automatik', 'auto-inserate' ); ?></option>
                            <option value="halbautomatik" <?php selected( $getriebe, 'halbautomatik' ); ?>><?php esc_html_e( 'Halbautomatik', 'auto-inserate' ); ?></option>
                            <option value="" <?php selected( $getriebe, '' ); ?>><?php esc_html_e( '-- Bitte wählen --', 'auto-inserate' ); ?></option>
                        </select>
                    </td>
                </tr>
                <!-- Farbe -->
                <tr>
                    <th scope="row">
                        <label for="fahrzeug_farbe"><?php esc_html_e( 'Farbe', 'auto-inserate' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="fahrzeug_farbe" name="fahrzeug_farbe" class="regular-text" value="<?php echo esc_attr( $farbe ); ?>" placeholder="<?php esc_attr_e( 'z.B. Schwarz Metallic', 'auto-inserate' ); ?>">
                    </td>
                </tr>
                <!-- Standort Adresse -->
                <tr>
                    <th scope="row">
                        <label for="fahrzeug_standort_adresse"><?php esc_html_e( 'Standort (Adresse)', 'auto-inserate' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="fahrzeug_standort_adresse" name="fahrzeug_standort_adresse" class="regular-text" value="<?php echo esc_attr( $standort_adresse ); ?>" placeholder="<?php esc_attr_e( 'z.B. Musterstraße 1, 12345 Musterstadt', 'auto-inserate' ); ?>">
                        <p class="description"><?php esc_html_e( 'Geben Sie die Adresse des Fahrzeugstandorts ein. Wird für einen Kartenlink verwendet.', 'auto-inserate' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Speichert die Meta-Daten für Fahrzeugdetails.
     *
     * @param int $post_id Die ID des aktuellen Posts.
     */
    public function save_fahrzeug_details_meta_data( $post_id, $post ) {
        // Nonce prüfen
        if ( ! isset( $_POST['auto_inserate_fahrzeug_details_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['auto_inserate_fahrzeug_details_nonce'] ), 'auto_inserate_save_fahrzeug_details' ) ) {
            return;
        }

        // Autosave ignorieren
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Berechtigungen prüfen
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Daten sanitizen und speichern
        $fields = array(
            'fahrzeug_preis' => 'intval',
            'fahrzeug_kilometerstand' => 'intval',
            'fahrzeug_erstzulassung' => 'sanitize_text_field',
            'fahrzeug_leistung' => 'sanitize_text_field',
            'fahrzeug_getriebe' => 'sanitize_text_field',
            'fahrzeug_farbe' => 'sanitize_text_field',
            'fahrzeug_standort_adresse' => 'sanitize_text_field',
        );

        foreach ( $fields as $field_name => $sanitize_callback ) {
            if ( isset( $_POST[ $field_name ] ) ) {
                $value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );
                update_post_meta( $post_id, '_' . $field_name, $value );
            } else {
                // Falls das Feld nicht gesendet wurde (z.B. leere Checkbox), lösche den Meta-Wert
                // Für unsere aktuellen Felder nicht direkt relevant, aber gute Praxis
                // delete_post_meta( $post_id, '_' . $field_name );
            }
        }

        // Geocoding für die Adresse durchführen
        $this->handle_address_geocoding( $post_id, sanitize_text_field(wp_unslash($_POST['fahrzeug_standort_adresse'] ?? '')) );
    }

    /**
     * Führt das Geocoding für eine Adresse durch und speichert Lat/Lng.
     *
     * @param int    $post_id Die Post ID.
     * @param string $adresse Die zu geocodierende Adresse.
     */
    private function handle_address_geocoding( $post_id, $adresse ) {
        $options = get_option( 'auto_inserate_settings' );
        $api_key = isset( $options['google_maps_api_key'] ) ? $options['google_maps_api_key'] : '';
        $current_address = $adresse;
        $old_address = get_post_meta( $post_id, '_fahrzeug_standort_adresse_geocoded', true ); // Speichern die zuletzt geocodierte Adresse

        // Nur Geocoden wenn API Key vorhanden ist und Adresse sich geändert hat oder neu ist
        // oder wenn Adresse vorhanden ist, aber keine Koordinaten.
        $lat = get_post_meta( $post_id, '_fahrzeug_lat', true );
        $lng = get_post_meta( $post_id, '_fahrzeug_lng', true );

        if ( empty( $api_key ) ) {
            // Wenn kein API Key, alte Koordinaten löschen, falls Adresse gelöscht wurde
            if ( empty( $current_address ) && ( !empty($lat) || !empty($lng) ) ) {
                delete_post_meta( $post_id, '_fahrzeug_lat' );
                delete_post_meta( $post_id, '_fahrzeug_lng' );
                delete_post_meta( $post_id, '_fahrzeug_standort_adresse_geocoded' );
            }
            return;
        }

        if ( empty( $current_address ) ) {
            delete_post_meta( $post_id, '_fahrzeug_lat' );
            delete_post_meta( $post_id, '_fahrzeug_lng' );
            delete_post_meta( $post_id, '_fahrzeug_standort_adresse_geocoded' );
            return;
        }

        // Geocode nur, wenn Adresse sich geändert hat oder Koordinaten fehlen
        if ( $current_address !== $old_address || empty($lat) || empty($lng) ) {
            $coordinates = $this->geocode_address_with_google( $current_address, $api_key );

            if ( $coordinates && isset( $coordinates['lat'] ) && isset( $coordinates['lng'] ) ) {
                update_post_meta( $post_id, '_fahrzeug_lat', $coordinates['lat'] );
                update_post_meta( $post_id, '_fahrzeug_lng', $coordinates['lng'] );
                update_post_meta( $post_id, '_fahrzeug_standort_adresse_geocoded', $current_address );
            } else {
                // Geocoding fehlgeschlagen oder keine Adresse mehr, alte Koordinaten löschen
                delete_post_meta( $post_id, '_fahrzeug_lat' );
                delete_post_meta( $post_id, '_fahrzeug_lng' );
                // Wir löschen _fahrzeug_standort_adresse_geocoded nicht, damit wir nicht ständig versuchen zu geocoden, wenn es einmal fehlschlug für diese Adresse
                // Man könnte hier einen Fehler loggen oder eine Admin-Notiz erstellen.
            }
        }
    }

    /**
     * Ruft die Google Geocoding API auf.
     *
     * @param string $address Die zu geocodierende Adresse.
     * @param string $api_key Der Google Maps API Key.
     * @return array|false Ein Array mit 'lat' und 'lng' oder false bei Fehler.
     */
    private function geocode_address_with_google( $address, $api_key ) {
        if ( empty( $address ) || empty( $api_key ) ) {
            return false;
        }

        $url = add_query_arg(
            array(
                'address' => urlencode( $address ),
                'key'     => $api_key,
            ),
            'https://maps.googleapis.com/maps/api/geocode/json'
        );

        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            // Fehler beim HTTP-Request
            // error_log( 'Google Geocoding API request error: ' . $response->get_error_message() );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $data && isset( $data['status'] ) && $data['status'] === 'OK' && ! empty( $data['results'][0]['geometry']['location'] ) ) {
            return array(
                'lat' => $data['results'][0]['geometry']['location']['lat'],
                'lng' => $data['results'][0]['geometry']['location']['lng'],
            );
        } else {
            // Fehler in der API-Antwort
            // error_log( 'Google Geocoding API error: ' . (isset($data['status']) ? $data['status'] : 'Unknown error') . ' for address: ' . $address);
            // if (isset($data['error_message'])) error_log('Google Geocoding API error message: ' . $data['error_message']);
            return false;
        }
    }
}
?>
