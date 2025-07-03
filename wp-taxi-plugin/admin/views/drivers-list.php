<?php
// Fahrerliste Admin Seite

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WP_Taxi_Drivers_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Fahrer', 'wp-taxi-plugin' ),
            'plural'   => __( 'Fahrer', 'wp-taxi-plugin' ),
            'ajax'     => false // Kein AJAX für diese Tabelle vorerst
        ) );
    }

    public static function get_drivers( $per_page = 20, $page_number = 1 ) {
        $args = array(
            'role'    => 'driver',
            'orderby' => 'registered',
            'order'   => 'DESC',
            'number'  => $per_page,
            'offset'  => ( $page_number - 1 ) * $per_page,
        );

        // Suchfunktion
        if ( ! empty( $_REQUEST['s'] ) ) {
            $args['search'] = '*' . sanitize_text_field( $_REQUEST['s'] ) . '*';
            $args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
        }

        // Filter nach Genehmigungsstatus
        if ( ! empty( $_REQUEST['driver_status_filter'] ) ) {
             $status_filter = sanitize_text_field($_REQUEST['driver_status_filter']);
             if ($status_filter === 'approved') {
                 $args['meta_query'] = array(
                    array(
                        'key' => 'driver_approved',
                        'value' => true, // Gespeichert als boolean
                        'compare' => '='
                    )
                );
             } elseif ($status_filter === 'pending') {
                 $args['meta_query'] = array(
                     'relation' => 'OR',
                    array(
                        'key' => 'driver_approved',
                        'value' => false,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'driver_approved',
                        'compare' => 'NOT EXISTS' // Auch Fahrer ohne den Meta-Key (gerade registriert)
                    )
                );
             }
        }


        $user_query = new WP_User_Query( $args );
        return $user_query->get_results();
    }

    public static function get_total_drivers_count() {
         $args = array(
            'role'    => 'driver',
            'fields' => 'ID', // Nur IDs für die Zählung
        );
        // Suchfunktion
        if ( ! empty( $_REQUEST['s'] ) ) {
            $args['search'] = '*' . sanitize_text_field( $_REQUEST['s'] ) . '*';
            $args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
        }
        // Filter nach Genehmigungsstatus
        if ( ! empty( $_REQUEST['driver_status_filter'] ) ) {
             $status_filter = sanitize_text_field($_REQUEST['driver_status_filter']);
             if ($status_filter === 'approved') {
                 $args['meta_query'] = array(array('key' => 'driver_approved', 'value' => true, 'compare' => '='));
             } elseif ($status_filter === 'pending') {
                 $args['meta_query'] = array('relation' => 'OR', array('key' => 'driver_approved', 'value' => false, 'compare' => '='), array('key' => 'driver_approved', 'compare' => 'NOT EXISTS'));
             }
        }
        $user_query = new WP_User_Query( $args );
        return $user_query->get_total();
    }


    public function no_items() {
        _e( 'Keine Fahrer gefunden.', 'wp-taxi-plugin' );
    }

    function column_name( $item ) {
        $edit_link = esc_url( get_edit_user_link( $item->ID ) );
        $title = '<strong><a href="' . $edit_link . '">' . esc_html( $item->display_name ) . '</a></strong>';

        $actions = array(
            'edit' => sprintf( '<a href="%s">%s</a>', $edit_link, __( 'Bearbeiten', 'wp-taxi-plugin' ) ),
        );
        return $title . $this->row_actions( $actions );
    }

    function column_user_email( $item ) {
        return '<a href="mailto:' . esc_attr( $item->user_email ) . '">' . esc_html( $item->user_email ) . '</a>';
    }

    function column_registered_date( $item ) {
        return date_i18n( get_option( 'date_format' ), strtotime( $item->user_registered ) );
    }

    function column_vehicle_model( $item ) {
        return esc_html( get_user_meta( $item->ID, 'vehicle_model', true ) ?: '–' );
    }

    function column_driver_license( $item ) {
        return esc_html( get_user_meta( $item->ID, 'driver_license', true ) ?: '–' );
    }

    function column_driver_approved( $item ) {
        $is_approved = get_user_meta( $item->ID, 'driver_approved', true );
        if ($is_approved === '' || $is_approved === false || $is_approved === '0' || $is_approved === 0) { // Berücksichtigt verschiedene falsey Werte
            $is_approved = false;
        } else {
            $is_approved = true;
        }

        $status_text = $is_approved ? __( 'Genehmigt', 'wp-taxi-plugin' ) : __( 'Nicht genehmigt', 'wp-taxi-plugin' );
        $status_class = $is_approved ? 'status-approved status-yes' : 'status-not-approved status-no';
        return '<span class="' . $status_class . '">' . $status_text . '</span>';
    }

    function column_actions( $item ) {
        $is_approved = get_user_meta( $item->ID, 'driver_approved', true );
        if ($is_approved === '' || $is_approved === false || $is_approved === '0' || $is_approved === 0) {
            $is_approved = false;
        } else {
            $is_approved = true;
        }

        if ( $is_approved ) {
            $button_text = __( 'Sperren', 'wp-taxi-plugin' );
            $button_class = 'unapprove-driver-btn button button-secondary';
            $action = 'unapprove';
        } else {
            $button_text = __( 'Genehmigen', 'wp-taxi-plugin' );
            $button_class = 'approve-driver-btn button button-primary';
            $action = 'approve';
        }
        return sprintf(
            '<button type="button" class="%s" data-driver-id="%d" data-action="%s">%s</button>',
            esc_attr($button_class),
            absint($item->ID),
            esc_attr($action),
            esc_html($button_text)
        );
    }


    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />', // Für Bulk Actions (nicht implementiert)
            'name'          => __( 'Name', 'wp-taxi-plugin' ),
            'user_email'    => __( 'E-Mail', 'wp-taxi-plugin' ),
            'vehicle_model' => __( 'Fahrzeugmodell', 'wp-taxi-plugin'),
            'driver_license'=> __( 'Führerschein', 'wp-taxi-plugin'),
            'driver_approved' => __( 'Genehmigungsstatus', 'wp-taxi-plugin' ),
            'registered_date' => __( 'Registriert am', 'wp-taxi-plugin' ),
            'actions'       => __( 'Aktionen', 'wp-taxi-plugin' )
        );
        return $columns;
    }

    public function get_sortable_columns() {
        $sortable_columns = array(
            'name' => array( 'display_name', true ),
            'user_email' => array( 'user_email', false ),
            'registered_date' => array( 'user_registered', false ),
            'driver_approved' => array( 'driver_approved_meta', false) // Benötigt benutzerdefinierte Sortierung
        );
        return $sortable_columns;
    }

    protected function get_views() {
        $views = array();
        $current = ( !empty($_REQUEST['driver_status_filter']) ? $_REQUEST['driver_status_filter'] : 'all');
        $base_url = admin_url('admin.php?page=wp-taxi-drivers');

        // Alle Fahrer
        $all_count = $this->get_user_count_by_role_and_meta('driver');
        $views['all'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            remove_query_arg('driver_status_filter', $base_url),
            $current === 'all' ? 'current' : '',
            __('Alle', 'wp-taxi-plugin'),
            $all_count
        );

        // Genehmigte Fahrer
        $approved_count = $this->get_user_count_by_role_and_meta('driver', array('key' => 'driver_approved', 'value' => true));
        $views['approved'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            add_query_arg('driver_status_filter', 'approved', $base_url),
            $current === 'approved' ? 'current' : '',
            __('Genehmigt', 'wp-taxi-plugin'),
            $approved_count
        );

        // Ausstehende Fahrer
        $pending_count = $this->get_user_count_by_role_and_meta('driver', array('relation' => 'OR', array('key' => 'driver_approved', 'value' => false), array('key' => 'driver_approved', 'compare' => 'NOT EXISTS')));
         $views['pending'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            add_query_arg('driver_status_filter', 'pending', $base_url),
            $current === 'pending' ? 'current' : '',
            __('Ausstehend', 'wp-taxi-plugin'),
            $pending_count
        );

        return $views;
    }

    private function get_user_count_by_role_and_meta($role, $meta_query_args = null) {
        $args = array('role' => $role, 'fields' => 'ID');
        if ($meta_query_args) {
            $args['meta_query'] = array($meta_query_args);
        }
        $user_query = new WP_User_Query($args);
        return $user_query->get_total();
    }


    public function prepare_items() {
        $this->_column_headers = $this->get_column_info();
        $per_page     = $this->get_items_per_page( 'drivers_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = self::get_total_drivers_count();

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ) );

        $this->items = self::get_drivers( $per_page, $current_page );

        // Sortierung nach Meta-Value (driver_approved)
        // WP_User_Query sortiert nicht direkt nach boolean meta values, wie man es erwarten würde.
        // Daher manuelle Sortierung nach dem Abrufen, wenn nach 'driver_approved_meta' sortiert wird.
        $orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_key( $_REQUEST['orderby'] ) : 'registered_date';
        $order   = ( ! empty( $_REQUEST['order'] ) ) ? strtoupper( sanitize_key( $_REQUEST['order'] ) ) : 'DESC';

        if ( 'driver_approved_meta' === $orderby ) {
            usort( $this->items, function ( $a, $b ) use ( $order ) {
                $val_a = (bool) get_user_meta( $a->ID, 'driver_approved', true );
                $val_b = (bool) get_user_meta( $b->ID, 'driver_approved', true );
                if ( $val_a === $val_b ) return 0;
                $comparison = $val_a < $val_b ? -1 : 1;
                return ( $order === 'DESC' ) ? -$comparison : $comparison;
            } );
        }
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="driver_id[]" value="%s" />', $item->ID
        );
    }
} // class

// Hauptanzeige der Seite
?>
<div class="wrap wp-taxi-admin-wrap">
    <h1><?php _e( 'Fahrerverwaltung', 'wp-taxi-plugin' ); ?></h1>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php
        // Suchfeld
        $GLOBALS['wp_taxi_drivers_table']->search_box( __( 'Fahrer suchen', 'wp-taxi-plugin' ), 'wp-taxi-driver-search' );

        // Filter (werden in get_views() hinzugefügt)
        // $GLOBALS['wp_taxi_drivers_table']->views(); // Wird automatisch von display() aufgerufen, wenn vorhanden

        $GLOBALS['wp_taxi_drivers_table'] = new WP_Taxi_Drivers_List_Table();
        $GLOBALS['wp_taxi_drivers_table']->prepare_items();
        $GLOBALS['wp_taxi_drivers_table']->display();
        ?>
    </form>
</div>
<?php
// Text für JavaScript Parameter (damit Buttons korrekt benannt werden)
$wp_taxi_admin_params_extra = array(
    'text_approve_driver_btn' => __('Genehmigen', 'wp-taxi-plugin'),
    'text_unapprove_driver_btn' => __('Sperren', 'wp-taxi-plugin'),
);
wp_localize_script( 'wp-taxi-admin-script', 'wp_taxi_admin_params', array_merge(wp_scripts()->get_data('wp-taxi-admin-script', 'data'), $wp_taxi_admin_params_extra) );

?>
