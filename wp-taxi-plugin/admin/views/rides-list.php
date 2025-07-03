<?php
// Fahrtenübersicht Admin Seite

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WP_Taxi_Rides_List_Table extends WP_List_Table {

    private $status_translations;

    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Fahrt', 'wp-taxi-plugin' ),
            'plural'   => __( 'Fahrten', 'wp-taxi-plugin' ),
            'ajax'     => false
        ) );

        $this->status_translations = array(
            'pending'   => __( 'Ausstehend', 'wp-taxi-plugin' ),
            'accepted'  => __( 'Angenommen', 'wp-taxi-plugin' ),
            'declined_by_driver' => __( 'Vom Fahrer abgelehnt', 'wp-taxi-plugin' ),
            'ongoing'   => __( 'Unterwegs', 'wp-taxi-plugin' ),
            'completed' => __( 'Abgeschlossen', 'wp-taxi-plugin' ),
            'cancelled_by_customer' => __( 'Vom Kunden storniert', 'wp-taxi-plugin' ),
            'cancelled_by_driver' => __( 'Vom Fahrer storniert', 'wp-taxi-plugin' ),
            'no_driver_found' => __( 'Kein Fahrer gefunden', 'wp-taxi-plugin' ),
        );
    }

    private function get_rides_data( $per_page = 20, $page_number = 1 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';

        $sql = "SELECT * FROM {$table_name}";

        // Filter und Suchbedingungen
        $where_clauses = array();

        // Suche
        if ( ! empty( $_REQUEST['s'] ) ) {
            $search_term = sanitize_text_field( $_REQUEST['s'] );
            // Suche in Adressen, oder versuche User ID zu matchen wenn es eine Zahl ist
            if (is_numeric($search_term)) {
                 $where_clauses[] = $wpdb->prepare( "(customer_id = %d OR driver_id = %d OR id = %d)", $search_term, $search_term, $search_term );
            } else {
                 $where_clauses[] = $wpdb->prepare( "(start_address LIKE %s OR end_address LIKE %s)", '%' . $wpdb->esc_like( $search_term ) . '%', '%' . $wpdb->esc_like( $search_term ) . '%' );
            }
        }

        // Filter nach Status
        if ( ! empty( $_REQUEST['status_filter'] ) && array_key_exists($_REQUEST['status_filter'], $this->status_translations) ) {
            $where_clauses[] = $wpdb->prepare( "status = %s", sanitize_text_field( $_REQUEST['status_filter'] ) );
        }

        // Filter nach Kunden-ID (aus Kundenliste verlinkt)
        if ( ! empty( $_REQUEST['customer_id'] ) ) {
            $where_clauses[] = $wpdb->prepare( "customer_id = %d", intval( $_REQUEST['customer_id'] ) );
        }
        // Filter nach Fahrer-ID
        if ( ! empty( $_REQUEST['driver_id'] ) ) {
            $where_clauses[] = $wpdb->prepare( "driver_id = %d", intval( $_REQUEST['driver_id'] ) );
        }


        if ( count( $where_clauses ) > 0 ) {
            $sql .= " WHERE " . implode( ' AND ', $where_clauses );
        }

        // Sortierung
        $orderby = ! empty( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'requested_at';
        $order   = ! empty( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), array( 'ASC', 'DESC' ) ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';
        // Whitelist allowed orderby columns
        $allowed_orderby = array('id', 'customer_id', 'driver_id', 'status', 'price', 'requested_at', 'completed_at');
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'requested_at';
        }
        $sql .= " ORDER BY {$orderby} {$order}";

        $sql .= " LIMIT $per_page";
        $sql .= " OFFSET " . ( $page_number - 1 ) * $per_page;

        return $wpdb->get_results( $sql, ARRAY_A );
    }

    private function get_total_rides_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';
        $sql = "SELECT COUNT(id) FROM {$table_name}";

        $where_clauses = array();
        if ( ! empty( $_REQUEST['s'] ) ) {
             $search_term = sanitize_text_field( $_REQUEST['s'] );
            if (is_numeric($search_term)) {
                 $where_clauses[] = $wpdb->prepare( "(customer_id = %d OR driver_id = %d OR id = %d)", $search_term, $search_term, $search_term );
            } else {
                 $where_clauses[] = $wpdb->prepare( "(start_address LIKE %s OR end_address LIKE %s)", '%' . $wpdb->esc_like( $search_term ) . '%', '%' . $wpdb->esc_like( $search_term ) . '%' );
            }
        }
        if ( ! empty( $_REQUEST['status_filter'] ) && array_key_exists($_REQUEST['status_filter'], $this->status_translations) ) {
            $where_clauses[] = $wpdb->prepare( "status = %s", sanitize_text_field( $_REQUEST['status_filter'] ) );
        }
        if ( ! empty( $_REQUEST['customer_id'] ) ) {
            $where_clauses[] = $wpdb->prepare( "customer_id = %d", intval( $_REQUEST['customer_id'] ) );
        }
        if ( ! empty( $_REQUEST['driver_id'] ) ) {
            $where_clauses[] = $wpdb->prepare( "driver_id = %d", intval( $_REQUEST['driver_id'] ) );
        }

        if ( count( $where_clauses ) > 0 ) {
            $sql .= " WHERE " . implode( ' AND ', $where_clauses );
        }
        return $wpdb->get_var( $sql );
    }

    public function no_items() {
        _e( 'Keine Fahrten gefunden.', 'wp-taxi-plugin' );
    }

    protected function get_views() {
        $views = array();
        $current_status = !empty($_REQUEST['status_filter']) ? $_REQUEST['status_filter'] : 'all';
        $base_url = admin_url('admin.php?page=wp-taxi-rides');

        global $wpdb;
        $table_name = $wpdb->prefix . 'taxi_rides';

        // Alle Fahrten
        $total_rides = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        $views['all'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            remove_query_arg('status_filter', $base_url),
            $current_status === 'all' ? 'current' : '',
            __('Alle', 'wp-taxi-plugin'),
            $total_rides
        );

        // Status-Filter dynamisch erstellen
        foreach ($this->status_translations as $status_key => $status_name) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE status = %s", $status_key));
            if ($count > 0 || $current_status === $status_key) { // Zeige Filter auch wenn Count 0 ist, aber aktuell ausgewählt
                 $views[$status_key] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                    add_query_arg('status_filter', $status_key, $base_url),
                    $current_status === $status_key ? 'current' : '',
                    esc_html($status_name),
                    $count
                );
            }
        }
        return $views;
    }


    function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id':
                return '#' . $item[ $column_name ];
            case 'customer_id':
                $customer = get_userdata( $item[ $column_name ] );
                return $customer ? '<a href="'.get_edit_user_link($customer->ID).'">'.esc_html( $customer->display_name ).'</a> (ID: '.$customer->ID.')' : __( 'Unbekannt', 'wp-taxi-plugin' );
            case 'driver_id':
                if ( !empty($item[ $column_name ]) ) {
                    $driver = get_userdata( $item[ $column_name ] );
                    return $driver ? '<a href="'.get_edit_user_link($driver->ID).'">'.esc_html( $driver->display_name ).'</a> (ID: '.$driver->ID.')' : __( 'N/A', 'wp-taxi-plugin' );
                }
                return __( 'N/A', 'wp-taxi-plugin' );
            case 'start_address':
            case 'end_address':
                return esc_html( $item[ $column_name ] );
            case 'status':
                $status_text = isset($this->status_translations[$item[$column_name]]) ? $this->status_translations[$item[$column_name]] : ucfirst($item[$column_name]);
                return '<span class="status-' . esc_attr($item[ $column_name ]) . '">' . esc_html($status_text) . '</span>';
            case 'price':
                return $item[ $column_name ] ? number_format_i18n( floatval($item[ $column_name ]), 2 ) . ' ' . get_option('wp_taxi_plugin_currency_symbol', 'CHF') : '–';
            case 'requested_at':
            case 'completed_at':
                return $item[ $column_name ] ? date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $item[ $column_name ] ) ) : '–';
            default:
                return print_r( $item, true ); // Zeige alles andere für Debugging
        }
    }

    public function get_columns() {
        $columns = array(
            // 'cb'        => '<input type="checkbox" />', // Für Bulk Actions
            'id'            => __( 'ID', 'wp-taxi-plugin' ),
            'customer_id'   => __( 'Kunde', 'wp-taxi-plugin' ),
            'driver_id'     => __( 'Fahrer', 'wp-taxi-plugin' ),
            'start_address' => __( 'Startadresse', 'wp-taxi-plugin' ),
            'end_address'   => __( 'Zieladresse', 'wp-taxi-plugin' ),
            'status'        => __( 'Status', 'wp-taxi-plugin' ),
            'price'         => __( 'Preis', 'wp-taxi-plugin' ),
            'requested_at'  => __( 'Angefragt am', 'wp-taxi-plugin' ),
            'completed_at'  => __( 'Abgeschlossen am', 'wp-taxi-plugin' ),
        );
        return $columns;
    }

    public function get_sortable_columns() {
        // Key = Spalten-ID, Value = array( Datenbankspalte, initial sort order (false for default/ASC) )
        return array(
            'id'           => array( 'id', false ),
            'customer_id'  => array( 'customer_id', false ),
            'driver_id'    => array( 'driver_id', false ),
            'status'       => array( 'status', false ),
            'price'        => array( 'price', false ),
            'requested_at' => array( 'requested_at', true ), // Standard Sortierung DESC
            'completed_at' => array( 'completed_at', false ),
        );
    }

    public function prepare_items() {
        $this->_column_headers = $this->get_column_info();

        // Bulk actions (optional)
        // $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'rides_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = $this->get_total_rides_count();

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ) );

        $this->items = $this->get_rides_data( $per_page, $current_page );
    }
} // class


// Hauptanzeige der Seite
?>
<div class="wrap wp-taxi-admin-wrap">
    <h1><?php _e( 'Fahrtenübersicht', 'wp-taxi-plugin' ); ?></h1>

    <?php
    // Links zum Filtern nach Kunden oder Fahrern anzeigen, wenn ID in URL
    if ( ! empty( $_REQUEST['customer_id'] ) ) {
        $customer = get_userdata( intval( $_REQUEST['customer_id'] ) );
        if ($customer) {
            echo '<h2>' . sprintf(__('Fahrten für Kunde: %s', 'wp-taxi-plugin'), esc_html($customer->display_name)) . '</h2>';
        }
    }
    if ( ! empty( $_REQUEST['driver_id'] ) ) {
        $driver = get_userdata( intval( $_REQUEST['driver_id'] ) );
         if ($driver) {
            echo '<h2>' . sprintf(__('Fahrten für Fahrer: %s', 'wp-taxi-plugin'), esc_html($driver->display_name)) . '</h2>';
        }
    }
    ?>

    <form method="get" id="wp-taxi-rides-filter-form">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        <?php
        // Behalte bestehende Filter bei der Suche
        if ( ! empty( $_REQUEST['status_filter'] ) ) {
            echo '<input type="hidden" name="status_filter" value="' . esc_attr($_REQUEST['status_filter']) . '" />';
        }
        if ( ! empty( $_REQUEST['customer_id'] ) ) {
            echo '<input type="hidden" name="customer_id" value="' . esc_attr($_REQUEST['customer_id']) . '" />';
        }
         if ( ! empty( $_REQUEST['driver_id'] ) ) {
            echo '<input type="hidden" name="driver_id" value="' . esc_attr($_REQUEST['driver_id']) . '" />';
        }

        $GLOBALS['wp_taxi_rides_table'] = new WP_Taxi_Rides_List_Table();
        $GLOBALS['wp_taxi_rides_table']->prepare_items();

        // Suchfeld und Filter-Dropdown
        $GLOBALS['wp_taxi_rides_table']->search_box( __( 'Fahrten suchen (ID, Adresse)', 'wp-taxi-plugin' ), 'wp-taxi-ride-search' );

        // Views (Filter-Links: Alle, Ausstehend, etc.)
        // $GLOBALS['wp_taxi_rides_table']->views(); // Wird automatisch von display() aufgerufen

        $GLOBALS['wp_taxi_rides_table']->display();
        ?>
    </form>
</div>
