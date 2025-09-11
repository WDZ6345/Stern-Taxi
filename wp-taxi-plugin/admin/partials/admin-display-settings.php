<?php
/**
 * Stellt die Admin-Einstellungsseite für das WP Taxi Plugin dar.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Berechtigungsprüfung
if ( ! current_user_can( 'manage_options' ) ) { // Höhere Berechtigung für Einstellungen
    wp_die( __( 'Sie haben keine Berechtigung, diese Seite anzuzeigen.', 'wp-taxi-plugin' ) );
}
?>
<div class="wrap wp-taxi-admin-page wp-taxi-settings-form">
    <h1><?php _e( 'Taxi Plugin Einstellungen', 'wp-taxi-plugin' ); ?></h1>

    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'wp_taxi_plugin_settings_group' ); // Slug der Einstellungsgruppe
        do_settings_sections( 'wp-taxi-settings' ); // Slug der Seite
        submit_button();
        ?>
    </form>

    <h2><?php _e('Wichtige Hinweise zur Google Maps API', 'wp-taxi-plugin'); ?></h2>
    <p>
        <?php _e('Damit das Plugin korrekt funktioniert, benötigen Sie einen gültigen Google Maps API Key. Stellen Sie sicher, dass die folgenden APIs in Ihrer Google Cloud Console aktiviert sind:', 'wp-taxi-plugin'); ?>
    </p>
    <ul>
        <li><strong>Maps JavaScript API</strong> - <?php _e('Für die Anzeige der Karten.', 'wp-taxi-plugin'); ?></li>
        <li><strong>Places API</strong> - <?php _e('Für die Adress-Autovervollständigung.', 'wp-taxi-plugin'); ?></li>
        <li><strong>Directions API</strong> - <?php _e('Für die Routenberechnung.', 'wp-taxi-plugin'); ?></li>
        <li><strong>Geocoding API</strong> - <?php _e('Für die Umwandlung von Adressen in Koordinaten und umgekehrt.', 'wp-taxi-plugin'); ?></li>
        <li><strong>Geolocation API</strong> (Optional, aber empfohlen) - <?php _e('Zur Bestimmung des Nutzerstandorts.', 'wp-taxi-plugin'); ?></li>
    </ul>
    <p>
        <?php _e('Schützen Sie Ihren API-Key, indem Sie ihn in der Google Cloud Console auf die Domains beschränken, auf denen Ihre Webseite läuft.', 'wp-taxi-plugin'); ?>
    </p>
    <p>
        <strong><?php _e('Der aktuell im Prompt angegebene API Key wird als Fallback verwendet, falls hier keiner eingetragen ist:', 'wp-taxi-plugin'); ?></strong>
        <code>AIzaSyBbWGNbH7yEXc7mtQrwvQEPmghYfr9-glI</code><br>
        <em><?php _e('Es wird dringend empfohlen, Ihren eigenen API Key zu verwenden und zu konfigurieren.', 'wp-taxi-plugin'); ?></em>
    </p>

    <h2><?php _e('Shortcodes', 'wp-taxi-plugin'); ?></h2>
    <p><?php _e('Verwenden Sie die folgenden Shortcodes, um die Plugin-Funktionen auf Ihren Seiten einzubinden:', 'wp-taxi-plugin'); ?></p>
    <ul>
        <li><code>[wp_taxi_login_form]</code> - <?php _e('Zeigt das Anmeldeformular an.', 'wp-taxi-plugin'); ?></li>
        <li><code>[wp_taxi_registration_form]</code> - <?php _e('Zeigt das Registrierungsformular an.', 'wp-taxi-plugin'); ?></li>
        <li><code>[wp_taxi_customer_dashboard]</code> - <?php _e('Zeigt das Kunden-Dashboard an (nur für angemeldete Kunden).', 'wp-taxi-plugin'); ?></li>
        <li><code>[wp_taxi_driver_dashboard]</code> - <?php _e('Zeigt das Fahrer-Dashboard an (nur für angemeldete und genehmigte Fahrer).', 'wp-taxi-plugin'); ?></li>
    </ul>
    <p><?php _e('Es wird empfohlen, für diese Shortcodes eigene Seiten zu erstellen (z.B. /anmelden, /registrieren, /dashboard).', 'wp-taxi-plugin'); ?></p>

</div>
