<?php
/**
 * Stellt die Admin-Seite für die Live-Karte der Fahrer dar.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Berechtigungsprüfung
if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) {
    wp_die( __( 'Sie haben keine Berechtigung, diese Seite anzuzeigen.', 'wp-taxi-plugin' ) );
}

$api_key = get_option('wp_taxi_plugin_google_maps_api_key', 'AIzaSyBbWGNbH7yEXc7mtQrwvQEPmghYfr9-glI');

?>
<div class="wrap wp-taxi-admin-page">
    <h1><?php _e( 'Live-Karte der Fahrer', 'wp-taxi-plugin' ); ?></h1>

    <?php if ( empty($api_key) ): ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    __( 'Es ist kein Google Maps API Key in den <a href="%s">Einstellungen</a> konfiguriert. Die Karte kann nicht angezeigt werden.', 'wp-taxi-plugin' ),
                    admin_url( 'admin.php?page=wp-taxi-settings' )
                );
                ?>
            </p>
        </div>
    <?php else: ?>
        <p><?php _e( 'Zeigt alle genehmigten Fahrer an, die kürzlich ihren Standort aktualisiert haben.', 'wp-taxi-plugin' ); ?></p>
        <div id="admin-live-map-container" style="height: 500px; width: 100%; max-width: 900px; margin-top: 20px; border: 1px solid #ccc; background-color: #e9e9e9;">
            <p style="text-align:center; padding-top: 230px;"><?php _e('Karte wird geladen...', 'wp-taxi-plugin');?></p>
        </div>
        <p style="margin-top: 10px;">
            <span style="display: inline-block; width: 12px; height: 12px; background-color: #2ECC71; border-radius: 50%; margin-right: 5px;"></span> <?php _e('Verfügbarer Fahrer', 'wp-taxi-plugin'); ?>
            <span style="display: inline-block; width: 12px; height: 12px; background-color: #E74C3C; border-radius: 50%; margin-right: 5px; margin-left: 15px;"></span> <?php _e('Nicht verfügbarer Fahrer', 'wp-taxi-plugin'); ?>
        </p>
    <?php endif; ?>
</div>
