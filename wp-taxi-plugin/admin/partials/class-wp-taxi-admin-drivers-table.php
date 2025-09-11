<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WP_Taxi_Admin_Drivers_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Fahrer', 'wp-taxi-plugin' ),
            'plural'   => __( 'Fahrer', 'wp-taxi-plugin' ),
            'ajax'     => false, // Kein AJAX für diese Tabelle (vorerst)
        ) );
    }

    public function get_columns() {
        $columns = array(
            'cb'              => '<input type="checkbox" />',
            'display_name'    => __( 'Anzeigename', 'wp-taxi-plugin' ),
            'user_email'      => __( 'E-Mail', 'wp-taxi-plugin' ),
            'driver_approved' => __( 'Genehmigt?', 'wp-taxi-plugin' ),
            'driver_available'=> __( 'Aktuell Verfügbar?', 'wp-taxi-plugin'),
            'vehicle_model'   => __( 'Fahrzeugmodell', 'wp-taxi-plugin' ),
            'driver_license'  => __( 'Führerschein', 'wp-taxi-plugin' ),
            'user_registered' => __( 'Registriert am', 'wp-taxi-plugin' ),
            'driver_actions'  => __( 'Aktionen', 'wp-taxi-plugin' ),
        );
        return $columns;
    }

    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $total_items  = $this->get_driver_count();

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ) );

        $this->items = $this->get_drivers_data( $per_page, $current_page );
    }

    protected function get_driver_count() {
        $args = array(
            'role'    => 'driver',
            'fields'  => 'ID',
            'count_total' => true,
        );
        $user_query = new WP_User_Query( $args );
        return $user_query->get_total();
    }

    protected function get_drivers_data( $per_page, $current_page ) {
        $drivers_data = array();
        $args = array(
            'role'    => 'driver',
            'orderby' => 'user_registered', // Standard-Sortierung
            'order'   => 'DESC',
            'number'  => $per_page,
            'paged'   => $current_page,
        );

        // Sortierung aus Request übernehmen
        if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
            $args['orderby'] = sanitize_key( $_REQUEST['orderby'] );
            $args['order']   = sanitize_key( $_REQUEST['order'] );
        }

        $user_query = new WP_User_Query( $args );
        $drivers = $user_query->get_results();

        if ( ! empty( $drivers ) ) {
            foreach ( $drivers as $driver ) {
                $drivers_data[] = array(
                    'ID'              => $driver->ID,
                    'display_name'    => $driver->display_name,
                    'user_email'      => $driver->user_email,
                    'driver_approved' => get_user_meta( $driver->ID, 'driver_approved', true ),
                    'driver_available'=> get_user_meta( $driver->ID, 'driver_available', true ),
                    'vehicle_model'   => get_user_meta( $driver->ID, 'vehicle_model', true ),
                    'driver_license'  => get_user_meta( $driver->ID, 'driver_license', true ),
                    'user_registered' => date_i18n( get_option('date_format'), strtotime($driver->user_registered) ),
                );
            }
        }
        return $drivers_data;
    }

    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'display_name':
            case 'user_email':
            case 'vehicle_model':
            case 'driver_license':
            case 'user_registered':
                return esc_html( $item[ $column_name ] );
            default:
                return print_r( $item, true ); // Nur für Debugging-Zwecke
        }
    }

    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="driver_id[]" value="%s" />', $item['ID']
        );
    }

    protected function column_display_name( $item ) {
        $edit_link = get_edit_user_link( $item['ID'] );
        $actions = array(
            'edit' => sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), __( 'Bearbeiten', 'wp-taxi-plugin' ) ),
            // 'view_profile' => sprintf( '<a href="%s">%s</a>', esc_url( get_author_posts_url($item['ID']) ), __( 'Profil ansehen', 'wp-taxi-plugin' ) ),
        );
        return sprintf( '<strong><a href="%s">%s</a></strong>%s', esc_url( $edit_link ), esc_html( $item['display_name'] ), $this->row_actions( $actions ) );
    }

    protected function column_driver_approved( $item ) {
        $is_approved = $item['driver_approved'];
        if ($is_approved === true || $is_approved === '1' || $is_approved === 1 ) {
            return '<span class="driver-status-text driver-status-approved">' . __( 'Genehmigt', 'wp-taxi-plugin' ) . '</span>';
        } elseif ($is_approved === false || $is_approved === '0' || $is_approved === 0 || $is_approved === '') {
             return '<span class="driver-status-text driver-status-not-approved">' . __( 'Nicht genehmigt', 'wp-taxi-plugin' ) . '</span>';
        }
        return '<span class="driver-status-text driver-status-pending">' . __( 'Ausstehend', 'wp-taxi-plugin' ) . '</span>'; // Default if meta not set explicitly
    }

    protected function column_driver_available( $item ) {
        $is_available = $item['driver_available'];
         if ($is_available === true || $is_available === '1' || $is_available === 1 ) {
            return '<span style="color:green;">' . __( 'Ja', 'wp-taxi-plugin' ) . '</span>';
        }
        return '<span style="color:red;">' . __( 'Nein', 'wp-taxi-plugin' ) . '</span>';
    }

    protected function column_driver_actions( $item ) {
        $is_approved = $item['driver_approved'];
        if ($is_approved === true || $is_approved === '1') {
            return sprintf(
                '<a href="#" class="button revoke-driver-btn" data-driver-id="%s">%s</a>',
                esc_attr( $item['ID'] ),
                __( 'Genehmigung entziehen', 'wp-taxi-plugin' )
            );
        } else {
            return sprintf(
                '<a href="#" class="button approve-driver-btn" data-driver-id="%s">%s</a>',
                esc_attr( $item['ID'] ),
                __( 'Genehmigen', 'wp-taxi-plugin' )
            );
        }
    }

    protected function get_sortable_columns() {
        return array(
            'display_name'    => array( 'display_name', false ),
            'user_email'      => array( 'user_email', false ),
            'user_registered' => array( 'user_registered', true ), // true = default sort
        );
    }

    // Bulk Actions (optional, für später)
    /*
    protected function get_bulk_actions() {
        $actions = array(
            'bulk_approve'   => __( 'Genehmigen', 'wp-taxi-plugin' ),
            'bulk_revoke'    => __( 'Genehmigung entziehen', 'wp-taxi-plugin' ),
            'bulk_delete'    => __( 'Löschen', 'wp-taxi-plugin' ),
        );
        return $actions;
    }

    protected function process_bulk_action() {
        // ... Logik für Bulk Actions ...
    }
    */

}
?>
