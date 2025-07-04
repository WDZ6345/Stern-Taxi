<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WP_Taxi_Admin_Customers_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Kunde', 'wp-taxi-plugin' ),
            'plural'   => __( 'Kunden', 'wp-taxi-plugin' ),
            'ajax'     => false,
        ) );
    }

    public function get_columns() {
        $columns = array(
            'cb'              => '<input type="checkbox" />',
            'display_name'    => __( 'Anzeigename', 'wp-taxi-plugin' ),
            'user_email'      => __( 'E-Mail', 'wp-taxi-plugin' ),
            // 'customer_total_rides' => __( 'Anzahl Fahrten', 'wp-taxi-plugin' ), // Benötigt DB-Abfrage
            // 'customer_total_spent' => __( 'Gesamtausgaben (CHF)', 'wp-taxi-plugin' ), // Benötigt DB-Abfrage
            'user_registered' => __( 'Registriert am', 'wp-taxi-plugin' ),
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
        $total_items  = $this->get_customer_count();

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ) );

        $this->items = $this->get_customers_data( $per_page, $current_page );
    }

    protected function get_customer_count() {
        $args = array(
            'role'    => 'customer',
            'fields'  => 'ID',
            'count_total' => true,
        );
        $user_query = new WP_User_Query( $args );
        return $user_query->get_total();
    }

    protected function get_customers_data( $per_page, $current_page ) {
        $customers_data = array();
        $args = array(
            'role'    => 'customer',
            'orderby' => 'user_registered',
            'order'   => 'DESC',
            'number'  => $per_page,
            'paged'   => $current_page,
        );

        if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
            $args['orderby'] = sanitize_key( $_REQUEST['orderby'] );
            $args['order']   = sanitize_key( $_REQUEST['order'] );
        }

        $user_query = new WP_User_Query( $args );
        $customers = $user_query->get_results();

        // global $wpdb;
        // $rides_table_name = $wpdb->prefix . 'taxi_rides';
        // $rides_table_exists = ($wpdb->get_var("SHOW TABLES LIKE '$rides_table_name'") == $rides_table_name);

        if ( ! empty( $customers ) ) {
            foreach ( $customers as $customer ) {
                // $total_rides = 0;
                // $total_spent = 0;
                // if ($rides_table_exists) {
                //     $total_rides = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $rides_table_name WHERE customer_id = %d", $customer->ID ) );
                //     $total_spent = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(price) FROM $rides_table_name WHERE customer_id = %d AND status = 'completed'", $customer->ID ) );
                // }

                $customers_data[] = array(
                    'ID'              => $customer->ID,
                    'display_name'    => $customer->display_name,
                    'user_email'      => $customer->user_email,
                    // 'customer_total_rides' => (int) $total_rides,
                    // 'customer_total_spent' => (float) $total_spent,
                    'user_registered' => date_i18n( get_option('date_format'), strtotime($customer->user_registered) ),
                );
            }
        }
        return $customers_data;
    }

    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'display_name':
            case 'user_email':
            case 'user_registered':
                return esc_html( $item[ $column_name ] );
            // case 'customer_total_rides':
            //     return number_format_i18n( $item[ $column_name ] );
            // case 'customer_total_spent':
            //     return number_format_i18n( $item[ $column_name ], 2 );
            default:
                return print_r( $item, true );
        }
    }

    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="customer_id[]" value="%s" />', $item['ID']
        );
    }

    protected function column_display_name( $item ) {
        $edit_link = get_edit_user_link( $item['ID'] );
        $actions = array(
            'edit' => sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), __( 'Bearbeiten', 'wp-taxi-plugin' ) ),
        );
        return sprintf( '<strong><a href="%s">%s</a></strong>%s', esc_url( $edit_link ), esc_html( $item['display_name'] ), $this->row_actions( $actions ) );
    }

    protected function get_sortable_columns() {
        return array(
            'display_name'    => array( 'display_name', false ),
            'user_email'      => array( 'user_email', false ),
            // 'customer_total_rides' => array( 'customer_total_rides', false), // Benötigt komplexe Sortierung
            // 'customer_total_spent' => array( 'customer_total_spent', false), // Benötigt komplexe Sortierung
            'user_registered' => array( 'user_registered', true ),
        );
    }
}
?>
