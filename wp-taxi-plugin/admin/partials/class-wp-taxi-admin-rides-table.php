<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WP_Taxi_Admin_Rides_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Fahrt', 'wp-taxi-plugin' ),
            'plural'   => __( 'Fahrten', 'wp-taxi-plugin' ),
            'ajax'     => false,
        ) );
    }

    public function get_columns() {
        $columns = array(
            // 'cb'            => '<input type="checkbox" />', // Für Bulk Actions, später
            'id'              => __( 'ID', 'wp-taxi-plugin' ),
            'customer_id'     => __( 'Kunde', 'wp-taxi-plugin' ),
            'driver_id'       => __( 'Fahrer', 'wp-taxi-plugin' ),
            'start_address'   => __( 'Von', 'wp-taxi-plugin' ),
            'end_address'     => __( 'Nach', 'wp-taxi-plugin' ),
            'price'           => __( 'Preis', 'wp-taxi-plugin' ),
            'status'          => __( 'Status', 'wp-taxi-plugin' ),
            'created_at'      => __( 'Erstellt am', 'wp-taxi-plugin' ),
            'updated_at'      => __( 'Aktualisiert am', 'wp-taxi-plugin' ),
        );
        return $columns;
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $per_page     = 20;
        $current_page = $this->get_pagenum();

        $sql_count = "SELECT COUNT(id) FROM {$table_name}";
        // Filter hinzufügen (Beispiel für Status-Filter)
        $where_clauses = array();
        if ( ! empty( $_REQUEST['status_filter'] ) ) {
            $status_filter = sanitize_key( $_REQUEST['status_filter'] );
            if ($status_filter !== 'all') {
                 $where_clauses[] = $wpdb->prepare( "status = %s", $status_filter );
            }
        }
        // TODO: Suchfilter hinzufügen

        if (count($where_clauses) > 0) {
            $sql_count .= " WHERE " . implode( " AND ", $where_clauses );
        }
        $total_items  = $wpdb->get_var($sql_count);


        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ) );

        $offset = ( $current_page - 1 ) * $per_page;
        $sql_data = "SELECT * FROM {$table_name}";
        if (count($where_clauses) > 0) {
            $sql_data .= " WHERE " . implode( " AND ", $where_clauses );
        }

        // Sortierung
        $orderby = ! empty( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'created_at';
        $order   = ! empty( $_REQUEST['order'] ) ? strtoupper(sanitize_key( $_REQUEST['order'] )) : 'DESC';
        if (array_key_exists($orderby, $this->get_sortable_columns())) {
             $sql_data .= $wpdb->prepare( " ORDER BY {$orderby} {$order}", ''); // Validated above
        } else {
            $sql_data .= $wpdb->prepare( " ORDER BY created_at DESC", '');
        }

        $sql_data .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );

        $this->items = $wpdb->get_results( $sql_data, ARRAY_A );
    }

    protected function get_sortable_columns() {
        return array(
            'id'          => array( 'id', false ),
            'customer_id' => array( 'customer_id', false ),
            'driver_id'   => array( 'driver_id', false ),
            'price'       => array( 'price', false ),
            'status'      => array( 'status', false ),
            'created_at'  => array( 'created_at', true ), // true = default sort
            'updated_at'  => array( 'updated_at', false ),
        );
    }

    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id':
            case 'start_address':
            case 'end_address':
                return esc_html( $item[ $column_name ] );
            case 'created_at':
            case 'updated_at':
                return date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime($item[$column_name]) );
            default:
                return print_r( $item, true ); // Nur für Debugging
        }
    }

    protected function column_customer_id($item) {
        $customer_id = $item['customer_id'];
        if ($customer_id) {
            $customer = get_userdata($customer_id);
            if ($customer) {
                return sprintf('<a href="%s">%s (#%d)</a>', get_edit_user_link($customer_id), esc_html($customer->display_name), $customer_id);
            }
            return __('Unbekannt', 'wp-taxi-plugin') . ' (#'.esc_html($customer_id).')';
        }
        return __('N/A', 'wp-taxi-plugin');
    }

    protected function column_driver_id($item) {
        $driver_id = $item['driver_id'];
        if ($driver_id) {
            $driver = get_userdata($driver_id);
            if ($driver) {
                 return sprintf('<a href="%s">%s (#%d)</a>', get_edit_user_link($driver_id), esc_html($driver->display_name), $driver_id);
            }
             return __('Unbekannt', 'wp-taxi-plugin') . ' (#'.esc_html($driver_id).')';
        }
        return __('Nicht zugewiesen', 'wp-taxi-plugin');
    }

    protected function column_price($item) {
        if (isset($item['price']) && $item['price'] !== null) {
            return esc_html($item['currency_symbol']) . ' ' . number_format_i18n(floatval($item['price']), 2);
        }
        return __('N/A', 'wp-taxi-plugin');
    }

    protected function column_status($item) {
        $status = $item['status'];
        $status_translations = array( // Should be in a helper
            'pending'   => __( 'Ausstehend', 'wp-taxi-plugin' ),
            'accepted'  => __( 'Angenommen', 'wp-taxi-plugin' ),
            'ongoing'   => __( 'Unterwegs', 'wp-taxi-plugin' ),
            'completed' => __( 'Abgeschlossen', 'wp-taxi-plugin' ),
            'cancelled' => __( 'Storniert', 'wp-taxi-plugin' ),
        );
        $display_status = isset($status_translations[$status]) ? $status_translations[$status] : ucfirst($status);

        $color = 'inherit';
        switch ($status) {
            case 'pending': $color = 'orange'; break;
            case 'accepted': $color = 'blue'; break;
            case 'ongoing': $color = 'purple'; break;
            case 'completed': $color = 'green'; break;
            case 'cancelled': $color = 'red'; break;
        }
        return '<strong style="color:' . $color . ';">' . esc_html($display_status) . '</strong>';
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' === $which ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'taxi_rides';
            $statuses = $wpdb->get_col("SELECT DISTINCT status FROM {$table_name} ORDER BY status ASC");

            $current_filter = !empty($_REQUEST['status_filter']) ? sanitize_key($_REQUEST['status_filter']) : 'all';
            ?>
            <div class="alignleft actions">
                <label for="status_filter" class="screen-reader-text"><?php _e('Nach Status filtern', 'wp-taxi-plugin'); ?></label>
                <select name="status_filter" id="status_filter">
                    <option value="all" <?php selected($current_filter, 'all'); ?>><?php _e('Alle Status', 'wp-taxi-plugin'); ?></option>
                    <?php
                    if ($statuses) {
                        foreach ($statuses as $status) {
                             $status_translations = array(
                                'pending'   => __( 'Ausstehend', 'wp-taxi-plugin' ), 'accepted'  => __( 'Angenommen', 'wp-taxi-plugin' ),
                                'ongoing'   => __( 'Unterwegs', 'wp-taxi-plugin' ), 'completed' => __( 'Abgeschlossen', 'wp-taxi-plugin' ),
                                'cancelled' => __( 'Storniert', 'wp-taxi-plugin' ),
                            );
                            $display_status = isset($status_translations[$status]) ? $status_translations[$status] : ucfirst($status);
                            printf('<option value="%s" %s>%s</option>', esc_attr($status), selected($current_filter, $status, false), esc_html($display_status));
                        }
                    }
                    ?>
                </select>
                <?php submit_button( __( 'Filtern' ), 'action', 'filter_action', false, array( 'id' => 'post-query-submit' ) ); ?>
            </div>
            <?php
        }
    }
}
?>
