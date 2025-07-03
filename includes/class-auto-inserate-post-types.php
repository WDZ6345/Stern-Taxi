<?php
/**
 * Registriert Custom Post Types für das Auto Inserate Plugin.
 *
 * @package AutoInserate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Auto_Inserate_Post_Types Klasse.
 */
class Auto_Inserate_Post_Types {

    /**
     * Hook in die Initialisierung.
     */
    public function init() {
        add_action( 'init', array( $this, 'register_fahrzeug_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
    }

    /**
     * Registriert den 'fahrzeug' Custom Post Type.
     */
    public function register_fahrzeug_post_type() {
        $labels = array(
            'name'                  => _x( 'Fahrzeuge', 'Post Type General Name', 'auto-inserate' ),
            'singular_name'         => _x( 'Fahrzeug', 'Post Type Singular Name', 'auto-inserate' ),
            'menu_name'             => __( 'Fahrzeuge', 'auto-inserate' ),
            'name_admin_bar'        => __( 'Fahrzeug', 'auto-inserate' ),
            'archives'              => __( 'Fahrzeug-Archive', 'auto-inserate' ),
            'attributes'            => __( 'Fahrzeug-Attribute', 'auto-inserate' ),
            'parent_item_colon'     => __( 'Übergeordnetes Fahrzeug:', 'auto-inserate' ),
            'all_items'             => __( 'Alle Fahrzeuge', 'auto-inserate' ),
            'add_new_item'          => __( 'Neues Fahrzeug hinzufügen', 'auto-inserate' ),
            'add_new'               => __( 'Neu hinzufügen', 'auto-inserate' ),
            'new_item'              => __( 'Neues Fahrzeug', 'auto-inserate' ),
            'edit_item'             => __( 'Fahrzeug bearbeiten', 'auto-inserate' ),
            'update_item'           => __( 'Fahrzeug aktualisieren', 'auto-inserate' ),
            'view_item'             => __( 'Fahrzeug ansehen', 'auto-inserate' ),
            'view_items'            => __( 'Fahrzeuge ansehen', 'auto-inserate' ),
            'search_items'          => __( 'Fahrzeuge suchen', 'auto-inserate' ),
            'not_found'             => __( 'Nicht gefunden', 'auto-inserate' ),
            'not_found_in_trash'    => __( 'Nicht im Papierkorb gefunden', 'auto-inserate' ),
            'featured_image'        => __( 'Fahrzeugbild', 'auto-inserate' ),
            'set_featured_image'    => __( 'Fahrzeugbild festlegen', 'auto-inserate' ),
            'remove_featured_image' => __( 'Fahrzeugbild entfernen', 'auto-inserate' ),
            'use_featured_image'    => __( 'Als Fahrzeugbild verwenden', 'auto-inserate' ),
            'insert_into_item'      => __( 'In Fahrzeug einfügen', 'auto-inserate' ),
            'uploaded_to_this_item' => __( 'Zu diesem Fahrzeug hochgeladen', 'auto-inserate' ),
            'items_list'            => __( 'Fahrzeugliste', 'auto-inserate' ),
            'items_list_navigation' => __( 'Fahrzeuglisten-Navigation', 'auto-inserate' ),
            'filter_items_list'     => __( 'Fahrzeugliste filtern', 'auto-inserate' ),
        );
        $args = array(
            'label'                 => __( 'Fahrzeug', 'auto-inserate' ),
            'description'           => __( 'Post Type für Fahrzeuginserate', 'auto-inserate' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-car',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => 'fahrzeuge', // Slug für das Archiv
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true, // Für Gutenberg-Editor und REST API
            'rewrite'               => array( 'slug' => 'fahrzeuge', 'with_front' => true ),
        );
        register_post_type( 'fahrzeug', $args );
    }

    /**
     * Registriert die Taxonomien für den 'fahrzeug' Post Type.
     */
    public function register_taxonomies() {
        // Marke
        $marke_labels = array(
            'name'              => _x( 'Marken', 'taxonomy general name', 'auto-inserate' ),
            'singular_name'     => _x( 'Marke', 'taxonomy singular name', 'auto-inserate' ),
            'search_items'      => __( 'Marken suchen', 'auto-inserate' ),
            'all_items'         => __( 'Alle Marken', 'auto-inserate' ),
            'parent_item'       => __( 'Übergeordnete Marke', 'auto-inserate' ),
            'parent_item_colon' => __( 'Übergeordnete Marke:', 'auto-inserate' ),
            'edit_item'         => __( 'Marke bearbeiten', 'auto-inserate' ),
            'update_item'       => __( 'Marke aktualisieren', 'auto-inserate' ),
            'add_new_item'      => __( 'Neue Marke hinzufügen', 'auto-inserate' ),
            'new_item_name'     => __( 'Neuer Markenname', 'auto-inserate' ),
            'menu_name'         => __( 'Marken', 'auto-inserate' ),
        );
        $marke_args = array(
            'hierarchical'      => true,
            'labels'            => $marke_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'marke' ),
            'show_in_rest'      => true,
        );
        register_taxonomy( 'marke', array( 'fahrzeug' ), $marke_args );

        // Modell
        $modell_labels = array(
            'name'              => _x( 'Modelle', 'taxonomy general name', 'auto-inserate' ),
            'singular_name'     => _x( 'Modell', 'taxonomy singular name', 'auto-inserate' ),
            'search_items'      => __( 'Modelle suchen', 'auto-inserate' ),
            'all_items'         => __( 'Alle Modelle', 'auto-inserate' ),
            'parent_item'       => __( 'Übergeordnetes Modell', 'auto-inserate' ),
            'parent_item_colon' => __( 'Übergeordnetes Modell:', 'auto-inserate' ),
            'edit_item'         => __( 'Modell bearbeiten', 'auto-inserate' ),
            'update_item'       => __( 'Modell aktualisieren', 'auto-inserate' ),
            'add_new_item'      => __( 'Neues Modell hinzufügen', 'auto-inserate' ),
            'new_item_name'     => __( 'Neuer Modellname', 'auto-inserate' ),
            'menu_name'         => __( 'Modelle', 'auto-inserate' ),
        );
        $modell_args = array(
            'hierarchical'      => true, // Kann auch false sein, je nach Anforderung
            'labels'            => $modell_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'modell' ),
            'show_in_rest'      => true,
        );
        register_taxonomy( 'modell', array( 'fahrzeug' ), $modell_args );

        // Zustand
        $zustand_labels = array(
            'name'                       => _x( 'Zustände', 'taxonomy general name', 'auto-inserate' ),
            'singular_name'              => _x( 'Zustand', 'taxonomy singular name', 'auto-inserate' ),
            'search_items'               => __( 'Zustände suchen', 'auto-inserate' ),
            'popular_items'              => __( 'Beliebte Zustände', 'auto-inserate' ),
            'all_items'                  => __( 'Alle Zustände', 'auto-inserate' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Zustand bearbeiten', 'auto-inserate' ),
            'update_item'                => __( 'Zustand aktualisieren', 'auto-inserate' ),
            'add_new_item'               => __( 'Neuen Zustand hinzufügen', 'auto-inserate' ),
            'new_item_name'              => __( 'Neuer Zustand Name', 'auto-inserate' ),
            'separate_items_with_commas' => __( 'Zustände mit Kommas trennen', 'auto-inserate' ),
            'add_or_remove_items'        => __( 'Zustände hinzufügen oder entfernen', 'auto-inserate' ),
            'choose_from_most_used'      => __( 'Aus den meistverwendeten Zuständen wählen', 'auto-inserate' ),
            'not_found'                  => __( 'Keine Zustände gefunden.', 'auto-inserate' ),
            'menu_name'                  => __( 'Zustände', 'auto-inserate' ),
        );
        $zustand_args = array(
            'hierarchical'          => false,
            'labels'                => $zustand_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'zustand' ),
            'show_in_rest'          => true,
        );
        register_taxonomy( 'zustand', array( 'fahrzeug' ), $zustand_args );

        // Kraftstoffart
        $kraftstoff_labels = array(
            'name'                       => _x( 'Kraftstoffarten', 'taxonomy general name', 'auto-inserate' ),
            'singular_name'              => _x( 'Kraftstoffart', 'taxonomy singular name', 'auto-inserate' ),
            'search_items'               => __( 'Kraftstoffarten suchen', 'auto-inserate' ),
            'popular_items'              => __( 'Beliebte Kraftstoffarten', 'auto-inserate' ),
            'all_items'                  => __( 'Alle Kraftstoffarten', 'auto-inserate' ),
            'edit_item'                  => __( 'Kraftstoffart bearbeiten', 'auto-inserate' ),
            'update_item'                => __( 'Kraftstoffart aktualisieren', 'auto-inserate' ),
            'add_new_item'               => __( 'Neue Kraftstoffart hinzufügen', 'auto-inserate' ),
            'new_item_name'              => __( 'Neue Kraftstoffart Name', 'auto-inserate' ),
            'separate_items_with_commas' => __( 'Kraftstoffarten mit Kommas trennen', 'auto-inserate' ),
            'add_or_remove_items'        => __( 'Kraftstoffarten hinzufügen oder entfernen', 'auto-inserate' ),
            'choose_from_most_used'      => __( 'Aus den meistverwendeten Kraftstoffarten wählen', 'auto-inserate' ),
            'not_found'                  => __( 'Keine Kraftstoffarten gefunden.', 'auto-inserate' ),
            'menu_name'                  => __( 'Kraftstoffarten', 'auto-inserate' ),
        );
        $kraftstoff_args = array(
            'hierarchical'          => false,
            'labels'                => $kraftstoff_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'kraftstoffart' ),
            'show_in_rest'          => true,
        );
        register_taxonomy( 'kraftstoffart', array( 'fahrzeug' ), $kraftstoff_args );
    }
} // Diese schließende Klammer beendet die Klasse Auto_Inserate_Post_Types

// Initialisierung der Klasse, um die Hooks zu registrieren (auskommentiert, da es von der Hauptklasse gehandhabt wird)
// Dies wird normalerweise von der Haupt-Plugin-Klasse gehandhabt.
// $auto_inserate_post_types = new Auto_Inserate_Post_Types();
// $auto_inserate_post_types->init();

?>
