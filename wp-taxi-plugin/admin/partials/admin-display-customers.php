<?php
/**
 * Stellt die Admin-Seite für die Kunden-Verwaltung dar.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Berechtigungsprüfung
if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) {
    wp_die( __( 'Sie haben keine Berechtigung, diese Seite anzuzeigen.', 'wp-taxi-plugin' ) );
}

// Erstelle eine Instanz der WP_List_Table Klasse
$customers_table = new WP_Taxi_Admin_Customers_Table();
$customers_table->prepare_items();

?>
<div class="wrap wp-taxi-admin-page">
    <h1><?php _e( 'Kunden Verwalten', 'wp-taxi-plugin' ); ?></h1>
    <p><?php _e( 'Hier können Sie alle registrierten Kunden einsehen.', 'wp-taxi-plugin' ); ?></p>
    <?php /*
    <p style="font-style: italic;">
        <?php _e('Hinweis: Die Spalten "Anzahl Fahrten" und "Gesamtausgaben" werden erst gefüllt, wenn die Datenbanktabelle für Fahrten implementiert ist.', 'wp-taxi-plugin'); ?>
    </p>
    */ ?>
    <form method="post">
        <?php
        // $customers_table->search_box( __( 'Kunden suchen', 'wp-taxi-plugin' ), 'customer_search' ); // Suchfeld (optional)
        $customers_table->display();
        ?>
    </form>
</div>
