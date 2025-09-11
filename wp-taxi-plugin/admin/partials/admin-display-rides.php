<?php
/**
 * Stellt die Admin-Seite für die Fahrten-Verwaltung dar.
 * (Platzhalter, da die Datenbanktabelle noch nicht implementiert ist)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Berechtigungsprüfung
if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) {
    wp_die( __( 'Sie haben keine Berechtigung, diese Seite anzuzeigen.', 'wp-taxi-plugin' ) );
}

// Prüfen, ob die Fahrten-Tabelle existiert (wird später im Installer erstellt)
global $wpdb;
$table_name = $wpdb->prefix . 'taxi_rides';
$table_exists = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name);

?>
<div class="wrap wp-taxi-admin-page">
    <h1><?php _e( 'Fahrten Verwalten', 'wp-taxi-plugin' ); ?></h1>

    <?php if ( $table_exists ): ?>
        <p><?php _e( 'Hier können Sie alle gebuchten Fahrten einsehen und verwalten.', 'wp-taxi-plugin' ); ?></p>
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <?php
            // Erstelle eine Instanz der WP_List_Table Klasse
            $rides_table = new WP_Taxi_Admin_Rides_Table();
            $rides_table->prepare_items();
            // $rides_table->search_box( __( 'Fahrten suchen', 'wp-taxi-plugin' ), 'ride_search' ); // Suchfeld (optional, muss in Klasse implementiert werden)
            $rides_table->display();
            ?>
        </form>
    <?php else: ?>
        <div class="notice notice-warning">
            <p>
                <?php
                echo sprintf(
                    __( 'Die Datenbanktabelle für Fahrten (<code>%s</code>) existiert noch nicht. ', 'wp-taxi-plugin' ),
                    esc_html($table_name)
                );
                // Link zur Aktivierung/Installer?
                _e( 'Bitte stellen Sie sicher, dass das Plugin korrekt installiert und aktiviert wurde. Ggf. das Plugin deaktivieren und neu aktivieren, um den Installer auszuführen.', 'wp-taxi-plugin');
                ?>
            </p>
        </div>
        <p><?php _e( 'Diese Seite wird die Verwaltung aller Fahrten ermöglichen, sobald die Datenbankstruktur vorhanden ist.', 'wp-taxi-plugin' ); ?></p>
    <?php endif; ?>
</div>
