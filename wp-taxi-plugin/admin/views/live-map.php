<?php
// Live Map Admin Seite
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="wrap wp-taxi-admin-wrap">
    <h1><?php _e( 'Live Karte der Fahrer', 'wp-taxi-plugin' ); ?></h1>
    <p><?php _e('Auf dieser Karte sehen Sie die aktuellen Positionen Ihrer verfügbaren und genehmigten Fahrer, die kürzlich ihren Standort aktualisiert haben.', 'wp-taxi-plugin'); ?></p>

    <?php
    $api_key = get_option('wp_taxi_plugin_google_maps_api_key', '');
    if (empty($api_key)) {
        echo '<div class="notice notice-error"><p>' .
             sprintf(
                __('Es ist kein Google Maps API Key in den <a href="%s">Plugin-Einstellungen</a> hinterlegt. Die Karte kann nicht angezeigt werden.', 'wp-taxi-plugin'),
                admin_url('admin.php?page=wp-taxi-plugin')
            ) .
             '</p></div>';
    } else {
    ?>
        <div id="wp-taxi-live-map-admin-container">
            <div class="map-loading-placeholder">
                <p><?php _e('Karte wird geladen...', 'wp-taxi-plugin'); ?></p>
            </div>
        </div>
        <div id="map-legend" style="margin-top:15px;">
            <h4><?php _e('Legende:', 'wp-taxi-plugin'); ?></h4>
            <p><span style="display:inline-block; width:12px; height:12px; background-color:#0073aa; border-radius:50%; margin-right:5px;"></span> <?php _e('Verfügbarer Fahrer', 'wp-taxi-plugin'); ?></p>
            <p><span style="display:inline-block; width:12px; height:12px; background-color:#FFC107; border-radius:50%; margin-right:5px;"></span> <?php _e('Fahrer auf Fahrt', 'wp-taxi-plugin'); ?></p>
        </div>
    <?php
    } // endif API key check
    ?>
</div>
