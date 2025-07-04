<?php
/**
 * Stellt die Admin-Dashboard-Seite für das WP Taxi Plugin dar.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Berechtigungsprüfung
if ( ! current_user_can( 'manage_taxi_plugin_settings' ) ) {
    wp_die( __( 'Sie haben keine Berechtigung, diese Seite anzuzeigen.', 'wp-taxi-plugin' ) );
}

// Statistik-Daten sammeln (Beispiele, müssen mit echten Daten gefüllt werden)
$total_customers = count_users( array( 'role' => 'customer' ) )['total_users'];
$total_drivers = count_users( array( 'role' => 'driver' ) )['total_users'];
$approved_drivers = count_users( array( 'role' => 'driver', 'meta_key' => 'driver_approved', 'meta_value' => '1' ) )['total_users'];

$total_rides_today = 0;
$total_earnings_today = 0;
$rides_table_exists = false;
$currency_symbol = get_option('wp_taxi_plugin_currency_symbol', 'CHF');

global $wpdb;
$table_name = $wpdb->prefix . 'taxi_rides';
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
   $rides_table_exists = true;
   $today_date = current_time('Y-m-d');
   $total_rides_today = $wpdb->get_var(
       $wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE DATE(created_at) = %s", $today_date)
   );
   $total_earnings_today = $wpdb->get_var(
       $wpdb->prepare("SELECT SUM(price) FROM $table_name WHERE DATE(created_at) = %s AND status = %s", $today_date, 'completed')
   );
}


?>
<div class="wrap wp-taxi-admin-page">
    <h1><?php _e( 'Taxi Plugin Dashboard', 'wp-taxi-plugin' ); ?></h1>

    <div id="dashboard-widgets-wrap">
        <div class="wp-taxi-dashboard-widgets">

            <div class="wp-taxi-dashboard-widget">
                <h3><?php _e('Kunden', 'wp-taxi-plugin'); ?></h3>
                <span class="count"><?php echo esc_html( $total_customers ); ?></span>
                <p><a href="<?php echo admin_url('admin.php?page=wp-taxi-customers'); ?>"><?php _e('Kunden verwalten', 'wp-taxi-plugin'); ?></a></p>
            </div>

            <div class="wp-taxi-dashboard-widget">
                <h3><?php _e('Fahrer', 'wp-taxi-plugin'); ?></h3>
                <span class="count"><?php echo esc_html( $total_drivers ); ?></span>
                <p><?php printf(__('Davon genehmigt: %s', 'wp-taxi-plugin'), esc_html($approved_drivers)); ?></p>
                <p><a href="<?php echo admin_url('admin.php?page=wp-taxi-drivers'); ?>"><?php _e('Fahrer verwalten', 'wp-taxi-plugin'); ?></a></p>
            </div>

            <div class="wp-taxi-dashboard-widget">
                <h3><?php _e('Fahrten Heute', 'wp-taxi-plugin'); ?></h3>
                <?php if ($rides_table_exists): ?>
                    <span class="count"><?php echo esc_html( $total_rides_today ?: 0 ); ?></span>
                    <p>
                        <?php
                        printf(
                            __('Geschätzter Umsatz heute: %1$s %2$s', 'wp-taxi-plugin'),
                            esc_html($currency_symbol),
                            esc_html(number_format_i18n($total_earnings_today ?: 0, 2))
                        );
                        ?>
                    </p>
                <?php else: ?>
                     <span class="count">0</span>
                    <p><?php _e('Die Fahrten-Datenbanktabelle wurde noch nicht erstellt.', 'wp-taxi-plugin');?></p>
                <?php endif; ?>
                <p><a href="<?php echo admin_url('admin.php?page=wp-taxi-rides'); ?>"><?php _e('Alle Fahrten anzeigen', 'wp-taxi-plugin'); ?></a></p>
            </div>


        </div>
    </div>

    <h2><?php _e( 'Schnellaktionen', 'wp-taxi-plugin' ); ?></h2>
    <ul>
        <li><a href="<?php echo admin_url('admin.php?page=wp-taxi-settings'); ?>"><?php _e('Plugin-Einstellungen', 'wp-taxi-plugin'); ?></a></li>
        <li><a href="<?php echo admin_url('admin.php?page=wp-taxi-live-map'); ?>"><?php _e('Live-Karte der Fahrer anzeigen', 'wp-taxi-plugin'); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/wp-admin/user-new.php' ) ); ?>"><?php _e('Neuen Benutzer (Kunde/Fahrer) anlegen', 'wp-taxi-plugin'); ?></a></li>
    </ul>

    <?php
    // Hier könnten weitere Informationen oder Links angezeigt werden
    ?>

</div>
