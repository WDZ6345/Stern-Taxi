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
        // Hier könnten später Hooks für die Settings API folgen, z.B. add_action( 'admin_init', array( $this, 'register_settings' ) );
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
            <p><?php esc_html_e( 'Hier können Sie zukünftig die Einstellungen für das Auto Inserate Plugin verwalten.', 'auto-inserate' ); ?></p>

            <!--
            Beispiel für spätere Settings API Integration:
            <form action="options.php" method="post">
                <?php
                // settings_fields( 'auto_inserate_options_group' ); // Gruppe von Einstellungen
                // do_settings_sections( 'auto_inserate_settings' ); // Slug der Seite
                // submit_button( __( 'Änderungen speichern', 'auto-inserate' ) );
                ?>
            </form>
            -->
        </div>
        <?php
    }

    /**
     * Registriert die Einstellungen, Sektionen und Felder (für später).
     */
    /*
    public function register_settings() {
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
