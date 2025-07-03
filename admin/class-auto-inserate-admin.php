<?php
/**
 * Admin-spezifische Funktionen für das Auto Inserate Plugin.
 *
 * @package AutoInserate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Auto_Inserate_Admin Klasse.
 */
class Auto_Inserate_Admin {

    /**
     * Hook in WordPress.
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );
    }

    /**
     * Fügt die Admin-Menüseite für Plugin-Einstellungen hinzu.
     */
    public function add_admin_menu_page() {
        add_submenu_page(
            'edit.php?post_type=fahrzeug', // Parent Slug (unter Fahrzeuge)
            __( 'Auto Inserate Einstellungen', 'auto-inserate' ), // Seitentitel
            __( 'Einstellungen', 'auto-inserate' ), // Menütitel
            'manage_options', // Capability
            'auto_inserate_settings', // Menu Slug
            array( $this, 'render_settings_page' ) // Callback-Funktion
        );
    }

    /**
     * Rendert die HTML-Struktur der Einstellungsseite.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p><?php esc_html_e( 'Verwalten Sie hier die Einstellungen für das Auto Inserate Plugin.', 'auto-inserate' ); ?></p>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'auto_inserate_settings_group' ); // Muss mit register_setting übereinstimmen
                do_settings_sections( 'auto_inserate_settings' ); // Slug der Seite
                submit_button( __( 'Änderungen speichern', 'auto-inserate' ) );
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Registriert die Einstellungen, Sektionen und Felder.
     */
    public function register_plugin_settings() {
        // Optionsgruppe registrieren
        register_setting(
            'auto_inserate_settings_group', // Name der Optionsgruppe
            'auto_inserate_settings',       // Name der Option im `wp_options` table
            array( $this, 'sanitize_settings' ) // Sanitization Callback
        );

        // Sektion für Google Maps hinzufügen
        add_settings_section(
            'auto_inserate_maps_section', // ID
            __( 'Google Maps Einstellungen', 'auto-inserate' ), // Titel
            array( $this, 'maps_section_callback' ), // Callback für die Sektionsbeschreibung
            'auto_inserate_settings' // Slug der Seite, auf der die Sektion angezeigt wird
        );

        // Feld für den API Key hinzufügen
        add_settings_field(
            'google_maps_api_key', // ID
            __( 'Google Maps API Key', 'auto-inserate' ), // Titel
            array( $this, 'maps_api_key_field_callback' ), // Callback zum Rendern des Feldes
            'auto_inserate_settings', // Slug der Seite
            'auto_inserate_maps_section' // Sektions-ID
        );
    }

    /**
     * Callback für die Beschreibung der Google Maps Sektion.
     */
    public function maps_section_callback() {
        echo '<p>' . esc_html__( 'Geben Sie hier Ihren Google Maps API Key ein, um die Kartenfunktionen zu aktivieren.', 'auto-inserate' ) . '</p>';
        echo '<p>' . sprintf(
            wp_kses(
                /* translators: %s: Link to Google Cloud Console */
                __( 'Sie benötigen einen API Key von der <a href="%s" target="_blank">Google Cloud Console</a>. Stellen Sie sicher, dass die "Maps JavaScript API" und die "Geocoding API" für Ihren Key aktiviert sind.', 'auto-inserate' ),
                array( 'a' => array( 'href' => array(), 'target' => array() ) )
            ),
            'https://console.cloud.google.com/google/maps-apis/overview'
        ) . '</p>';
    }

    /**
     * Callback zum Rendern des API Key Feldes.
     */
    public function maps_api_key_field_callback() {
        $options = get_option( 'auto_inserate_settings' );
        $api_key = isset( $options['google_maps_api_key'] ) ? $options['google_maps_api_key'] : '';
        ?>
        <input type="text" id="google_maps_api_key" name="auto_inserate_settings[google_maps_api_key]" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
        <?php
    }

    /**
     * Sanitisiert die Plugin-Einstellungen vor dem Speichern.
     *
     * @param array $input Die eingegebenen Einstellungen.
     * @return array Die sanitisierten Einstellungen.
     */
    public function sanitize_settings( $input ) {
        $new_input = array();
        if ( isset( $input['google_maps_api_key'] ) ) {
            $new_input['google_maps_api_key'] = sanitize_text_field( $input['google_maps_api_key'] );
        }
        // Hier könnten weitere Einstellungen sanitisiert werden
        return $new_input;
    }

    /*
    public function register_settings() { // Alte Funktion kann entfernt oder umbenannt werden
        // Beispiel: register_setting( 'auto_inserate_options_group', 'auto_inserate_option_name', array( $this, 'sanitize_callback' ) );

        // add_settings_section(
        //     'auto_inserate_general_section',
        //     __( 'Allgemeine Einstellungen', 'auto-inserate' ),
        //     array( $this, 'general_section_callback' ),
        //     'auto_inserate_settings'
        // );

        // add_settings_field(
        //     'auto_inserate_currency_field',
        //     __( 'Währungssymbol', 'auto-inserate' ),
        //     array( $this, 'currency_field_callback' ),
        //     'auto_inserate_settings',
        //     'auto_inserate_general_section'
        // );
    }

    public function general_section_callback() {
        echo '<p>' . esc_html__( 'Grundeinstellungen für das Plugin.', 'auto-inserate' ) . '</p>';
    }

    public function currency_field_callback() {
        // $option = get_option( 'auto_inserate_option_name' );
        // $currency = isset( $option['currency'] ) ? $option['currency'] : 'EUR';
        // echo '<input type="text" name="auto_inserate_option_name[currency]" value="' . esc_attr( $currency ) . '" />';
    }

    public function sanitize_callback( $input ) {
        // $new_input = array();
        // if( isset( $input['currency'] ) ) {
        //     $new_input['currency'] = sanitize_text_field( $input['currency'] );
        // }
        // return $new_input;
        return $input; // Für den Anfang
    }
    */
}
?>
