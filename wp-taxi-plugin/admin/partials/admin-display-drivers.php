<?php
/**
 * Stellt die Admin-Seite für die Fahrer-Verwaltung dar.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Berechtigungsprüfung
if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) {
    wp_die( __( 'Sie haben keine Berechtigung, diese Seite anzuzeigen.', 'wp-taxi-plugin' ) );
}

// Erstelle eine Instanz der WP_List_Table Klasse
$drivers_table = new WP_Taxi_Admin_Drivers_Table();
$drivers_table->prepare_items();

?>
<div class="wrap wp-taxi-admin-page">
    <h1><?php _e( 'Fahrer Verwalten', 'wp-taxi-plugin' ); ?></h1>
    <p><?php _e( 'Hier können Sie alle registrierten Fahrer einsehen und deren Genehmigungsstatus verwalten.', 'wp-taxi-plugin' ); ?></p>

    <form method="post">
        <?php
        // $drivers_table->search_box( __( 'Fahrer suchen', 'wp-taxi-plugin' ), 'driver_search' ); // Suchfeld (optional)
        $drivers_table->display();
        ?>
    </form>
</div>
