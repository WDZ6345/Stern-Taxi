<?php
/**
 * Öffentliche Funktionen des Auto Inserate Plugins.
 *
 * @package AutoInserate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Auto_Inserate_Public Klasse.
 */
class Auto_Inserate_Public {

    /**
     * Hook in WordPress.
     */
    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_filter( 'template_include', array( $this, 'template_loader' ) );
        add_action( 'pre_get_posts', array( $this, 'handle_fahrzeug_suche' ) );

        // Shortcode für Suchformular
        add_shortcode( 'auto_inserate_suche', array( $this, 'render_search_form_shortcode' ) );
        // Shortcode für Fahrzeugliste
        add_shortcode( 'auto_inserate_fahrzeugliste', array( $this, 'render_fahrzeug_liste_shortcode' ) );

        // Hook für die Verarbeitung des Kontaktformulars
        add_action( 'template_redirect', array( $this, 'handle_kontaktformular_submission' ) );
    }

    /**
     * Lädt die Frontend-Stylesheets.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'auto-inserate-public',
            AUTO_INSERATE_PLUGIN_URL . 'assets/css/auto-inserate-public.css',
            array(),
            AUTO_INSERATE_VERSION
        );
    }

    /**
     * Lädt die Templates für Fahrzeuginserate aus dem Plugin-Verzeichnis, falls sie im Theme nicht existieren.
     *
     * @param string $template Der Pfad zum Template.
     * @return string Der Pfad zum zu ladenden Template.
     */
    public function template_loader( $template ) {
        if ( is_embed() ) {
            return $template;
        }

        $default_file = '';

        if ( is_singular( 'fahrzeug' ) ) {
            $default_file = 'single-fahrzeug.php';
        } elseif ( is_post_type_archive( 'fahrzeug' ) ) {
            $default_file = 'archive-fahrzeug.php';
        }

        if ( $default_file ) {
            // Prüfe, ob das Theme ein eigenes Template hat
            $theme_template = locate_template( array( 'auto-inserate/' . $default_file, $default_file ) );

            if ( $theme_template ) {
                return $theme_template;
            } else {
                // Lade das Template aus dem Plugin
                $plugin_template = AUTO_INSERATE_PLUGIN_DIR . 'public/templates/' . $default_file;
                if ( file_exists( $plugin_template ) ) {
                    return $plugin_template;
                }
            }
        }

        return $template;
    }

    /**
     * Rendert das Suchformular über einen Shortcode.
     *
     * @param array $atts Shortcode-Attribute.
     * @return string HTML des Suchformulars.
     */
    public function render_search_form_shortcode( $atts ) {
        ob_start();
        $this->get_search_form_html();
        return ob_get_clean();
    }

    /**
     * Generiert und gibt das HTML für das Suchformular aus.
     */
    public function get_search_form_html() {
        $fahrzeug_archiv_url = get_post_type_archive_link( 'fahrzeug' );
        ?>
        <form role="search" method="get" class="auto-inserate-search-form" action="<?php echo esc_url( $fahrzeug_archiv_url ); ?>">
            <div class="search-form-grid">

                <!-- Keyword -->
                <div class="form-group">
                    <label for="s_keyword"><?php esc_html_e( 'Stichwort', 'auto-inserate' ); ?></label>
                    <input type="search" id="s_keyword" name="s_keyword" value="<?php echo esc_attr( get_query_var( 's_keyword' ) ); ?>" placeholder="<?php esc_attr_e( 'z.B. Golf, SUV...', 'auto-inserate' ); ?>">
                </div>

                <!-- Marke -->
                <div class="form-group">
                    <label for="s_marke"><?php esc_html_e( 'Marke', 'auto-inserate' ); ?></label>
                    <?php
                    wp_dropdown_categories( array(
                        'show_option_all' => esc_html__( 'Alle Marken', 'auto-inserate' ),
                        'taxonomy'        => 'marke',
                        'name'            => 's_marke',
                        'id'              => 's_marke',
                        'selected'        => esc_attr( get_query_var( 's_marke' ) ),
                        'hierarchical'    => true,
                        'value_field'     => 'slug',
                        'show_count'      => false,
                        'hide_empty'      => true,
                    ) );
                    ?>
                </div>

                <!-- Modell (optional, da es viele geben kann) -->
                <div class="form-group">
                    <label for="s_modell"><?php esc_html_e( 'Modell', 'auto-inserate' ); ?></label>
                     <?php
                    wp_dropdown_categories( array(
                        'show_option_all' => esc_html__( 'Alle Modelle', 'auto-inserate' ),
                        'taxonomy'        => 'modell',
                        'name'            => 's_modell',
                        'id'              => 's_modell',
                        'selected'        => esc_attr( get_query_var( 's_modell' ) ),
                        'hierarchical'    => true,
                        'value_field'     => 'slug',
                        'show_count'      => false,
                        'hide_empty'      => true,
                    ) );
                    ?>
                </div>

                <!-- Zustand -->
                <div class="form-group">
                    <label for="s_zustand"><?php esc_html_e( 'Zustand', 'auto-inserate' ); ?></label>
                    <?php
                    wp_dropdown_categories( array(
                        'show_option_all' => esc_html__( 'Alle Zustände', 'auto-inserate' ),
                        'taxonomy'        => 'zustand',
                        'name'            => 's_zustand',
                        'id'              => 's_zustand',
                        'selected'        => esc_attr( get_query_var( 's_zustand' ) ),
                        'hierarchical'    => false,
                        'value_field'     => 'slug',
                        'show_count'      => false,
                        'hide_empty'      => true,
                    ) );
                    ?>
                </div>

                <!-- Kraftstoffart -->
                <div class="form-group">
                    <label for="s_kraftstoff"><?php esc_html_e( 'Kraftstoffart', 'auto-inserate' ); ?></label>
                    <?php
                    wp_dropdown_categories( array(
                        'show_option_all' => esc_html__( 'Alle Kraftstoffarten', 'auto-inserate' ),
                        'taxonomy'        => 'kraftstoffart',
                        'name'            => 's_kraftstoff',
                        'id'              => 's_kraftstoff',
                        'selected'        => esc_attr( get_query_var( 's_kraftstoff' ) ),
                        'hierarchical'    => false,
                        'value_field'     => 'slug',
                        'show_count'      => false,
                        'hide_empty'      => true,
                    ) );
                    ?>
                </div>

                <!-- Preisspanne -->
                <div class="form-group form-group-range">
                    <label><?php esc_html_e( 'Preisspanne (EUR)', 'auto-inserate' ); ?></label>
                    <div class="range-inputs">
                        <input type="number" name="s_preis_min" value="<?php echo esc_attr( get_query_var( 's_preis_min' ) ); ?>" placeholder="<?php esc_attr_e( 'Min.', 'auto-inserate' ); ?>" min="0" step="100">
                        <span>-</span>
                        <input type="number" name="s_preis_max" value="<?php echo esc_attr( get_query_var( 's_preis_max' ) ); ?>" placeholder="<?php esc_attr_e( 'Max.', 'auto-inserate' ); ?>" min="0" step="100">
                    </div>
                </div>

                <!-- Kilometerstand -->
                <div class="form-group">
                    <label for="s_km_max"><?php esc_html_e( 'Max. Kilometerstand (km)', 'auto-inserate' ); ?></label>
                    <input type="number" id="s_km_max" name="s_km_max" value="<?php echo esc_attr( get_query_var( 's_km_max' ) ); ?>" placeholder="<?php esc_attr_e( 'z.B. 100000', 'auto-inserate' ); ?>" min="0" step="1000">
                </div>

                <!-- Erstzulassung (Jahr) -->
                 <div class="form-group form-group-range">
                    <label><?php esc_html_e( 'Erstzulassung (Jahr)', 'auto-inserate' ); ?></label>
                    <div class="range-inputs">
                        <input type="number" name="s_ez_min" value="<?php echo esc_attr( get_query_var( 's_ez_min' ) ); ?>" placeholder="<?php esc_attr_e( 'Von', 'auto-inserate' ); ?>" min="1900" max="<?php echo date('Y'); ?>">
                        <span>-</span>
                        <input type="number" name="s_ez_max" value="<?php echo esc_attr( get_query_var( 's_ez_max' ) ); ?>" placeholder="<?php esc_attr_e( 'Bis', 'auto-inserate' ); ?>" min="1900" max="<?php echo date('Y'); ?>">
                    </div>
                </div>

            </div><!-- .search-form-grid -->
            <div class="form-group form-submit">
                <input type="submit" value="<?php esc_attr_e( 'Fahrzeuge suchen', 'auto-inserate' ); ?>">
                <a href="<?php echo esc_url( $fahrzeug_archiv_url ); ?>" class="reset-search"><?php esc_html_e( 'Filter zurücksetzen', 'auto-inserate' ); ?></a>
            </div>
            <input type="hidden" name="post_type" value="fahrzeug">
        </form>
        <?php
    }

    /**
     * Modifiziert die Hauptquery für die Fahrzeugsuche.
     *
     * @param WP_Query $query Die WP_Query Instanz.
     */
    public function handle_fahrzeug_suche( $query ) {
        if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'fahrzeug' ) ) {

            $meta_query_args = array( 'relation' => 'AND' );
            $tax_query_args = array( 'relation' => 'AND' );

            // Schlüsselwortsuche (allgemein)
            if ( ! empty( $_GET['s_keyword'] ) ) {
                $query->set( 's', sanitize_text_field( wp_unslash( $_GET['s_keyword'] ) ) );
            }

            // Taxonomie-Filter
            $taxonomies = array(
                's_marke'      => 'marke',
                's_modell'     => 'modell',
                's_zustand'    => 'zustand',
                's_kraftstoff' => 'kraftstoffart',
            );

            foreach ( $taxonomies as $param => $taxonomy_slug ) {
                if ( ! empty( $_GET[ $param ] ) ) {
                    $tax_query_args[] = array(
                        'taxonomy' => $taxonomy_slug,
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field( wp_unslash( $_GET[ $param ] ) ),
                    );
                }
            }
            if ( count( $tax_query_args ) > 1 ) {
                $query->set( 'tax_query', $tax_query_args );
            }

            // Meta-Filter
            // Preis
            $s_preis_min = isset( $_GET['s_preis_min'] ) ? absint( $_GET['s_preis_min'] ) : 0;
            $s_preis_max = isset( $_GET['s_preis_max'] ) ? absint( $_GET['s_preis_max'] ) : 0;

            if ( $s_preis_min > 0 && $s_preis_max > 0 && $s_preis_max >= $s_preis_min ) {
                $meta_query_args[] = array(
                    'key'     => '_fahrzeug_preis',
                    'value'   => array( $s_preis_min, $s_preis_max ),
                    'type'    => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            } elseif ( $s_preis_min > 0 ) {
                $meta_query_args[] = array(
                    'key'     => '_fahrzeug_preis',
                    'value'   => $s_preis_min,
                    'type'    => 'NUMERIC',
                    'compare' => '>=',
                );
            } elseif ( $s_preis_max > 0 ) {
                $meta_query_args[] = array(
                    'key'     => '_fahrzeug_preis',
                    'value'   => $s_preis_max,
                    'type'    => 'NUMERIC',
                    'compare' => '<=',
                );
            }

            // Kilometerstand (Max)
            if ( ! empty( $_GET['s_km_max'] ) ) {
                $meta_query_args[] = array(
                    'key'     => '_fahrzeug_kilometerstand',
                    'value'   => absint( $_GET['s_km_max'] ),
                    'type'    => 'NUMERIC',
                    'compare' => '<=',
                );
            }

            // Erstzulassung (Jahr) - Simplifiziert als Textvergleich für MM/JJJJ oder JJJJ
            // Für einen echten Datumsvergleich wäre es besser, das Datum als Unix-Timestamp oder YYYYMMDD zu speichern.
            // Hier gehen wir davon aus, dass '_fahrzeug_erstzulassung' als "MM/JJJJ" oder "JJJJ" gespeichert ist.
            // Dies ist eine vereinfachte Suche und funktioniert am besten, wenn nur das Jahr angegeben wird.
            $s_ez_min_year = isset( $_GET['s_ez_min'] ) ? absint( $_GET['s_ez_min'] ) : 0;
            $s_ez_max_year = isset( $_GET['s_ez_max'] ) ? absint( $_GET['s_ez_max'] ) : 0;

            if ( $s_ez_min_year > 0 ) {
                 $meta_query_args[] = array(
                    'key' => '_fahrzeug_erstzulassung',
                    'value' => (string) $s_ez_min_year, // Vergleiche als String
                    'compare' => '>=', // Funktioniert für JJJJ oder wenn MM/JJJJ so sortiert ist
                );
            }
            if ( $s_ez_max_year > 0 ) {
                 $meta_query_args[] = array(
                    'key' => '_fahrzeug_erstzulassung',
                    'value' => (string) $s_ez_max_year, // Vergleiche als String
                    'compare' => '<=', // Funktioniert für JJJJ oder wenn MM/JJJJ so sortiert ist
                                        // Um genauer zu sein, müsste man bei MM/JJJJ den String splitten und vergleichen oder das Format ändern.
                                        // Für eine genaue Bereichssuche bei MM/JJJJ müsste man die Werte umwandeln oder zwei Felder (Monat, Jahr) verwenden.
                );
            }


            if ( count( $meta_query_args ) > 1 ) {
                $query->set( 'meta_query', $meta_query_args );
            }
        }
    }

    /**
     * Zeigt das Kontaktformular für ein Fahrzeug an.
     *
     * @param int $fahrzeug_id Die ID des Fahrzeugs.
     */
    public function display_fahrzeug_kontaktformular( $fahrzeug_id ) {
        if ( ! $fahrzeug_id ) {
            return;
        }
        ?>
        <div class="auto-inserate-kontaktformular">
            <h3><?php esc_html_e( 'Interesse an diesem Fahrzeug?', 'auto-inserate' ); ?></h3>
            <p><?php esc_html_e( 'Senden Sie uns eine Anfrage über das untenstehende Formular.', 'auto-inserate' ); ?></p>

            <?php
            // Erfolgs- oder Fehlermeldungen anzeigen
            if ( isset( $_GET['anfrage_status'] ) ) {
                if ( sanitize_key( $_GET['anfrage_status'] ) === 'erfolg' ) {
                    echo '<p class="kontakt-status kontakt-erfolg">' . esc_html__( 'Vielen Dank! Ihre Anfrage wurde erfolgreich gesendet.', 'auto-inserate' ) . '</p>';
                } elseif ( sanitize_key( $_GET['anfrage_status'] ) === 'fehler' ) {
                    echo '<p class="kontakt-status kontakt-fehler">' . esc_html__( 'Fehler: Bitte füllen Sie alle Pflichtfelder aus und versuchen Sie es erneut.', 'auto-inserate' ) . '</p>';
                }  elseif ( sanitize_key( $_GET['anfrage_status'] ) === 'nonce_fehler' ) {
                    echo '<p class="kontakt-status kontakt-fehler">' . esc_html__( 'Fehler: Ungültige Anfrage. Bitte versuchen Sie es erneut.', 'auto-inserate' ) . '</p>';
                }
            }
            ?>

            <form method="POST" action="<?php echo esc_url( add_query_arg( array() ) ); // Sendet an die aktuelle URL ?>">
                <input type="hidden" name="fahrzeug_id" value="<?php echo esc_attr( $fahrzeug_id ); ?>">
                <input type="hidden" name="fahrzeug_titel" value="<?php echo esc_attr( get_the_title( $fahrzeug_id ) ); ?>">
                <?php wp_nonce_field( 'auto_inserate_kontaktformular_nonce_' . $fahrzeug_id, 'auto_inserate_kontakt_nonce' ); ?>

                <div class="form-group">
                    <label for="kontakt_name"><?php esc_html_e( 'Ihr Name', 'auto-inserate' ); ?> <span class="required">*</span></label>
                    <input type="text" id="kontakt_name" name="kontakt_name" required>
                </div>

                <div class="form-group">
                    <label for="kontakt_email"><?php esc_html_e( 'Ihre E-Mail-Adresse', 'auto-inserate' ); ?> <span class="required">*</span></label>
                    <input type="email" id="kontakt_email" name="kontakt_email" required>
                </div>

                <div class="form-group">
                    <label for="kontakt_telefon"><?php esc_html_e( 'Ihre Telefonnummer (optional)', 'auto-inserate' ); ?></label>
                    <input type="tel" id="kontakt_telefon" name="kontakt_telefon">
                </div>

                <div class="form-group">
                    <label for="kontakt_nachricht"><?php esc_html_e( 'Ihre Nachricht', 'auto-inserate' ); ?> <span class="required">*</span></label>
                    <textarea id="kontakt_nachricht" name="kontakt_nachricht" rows="5" required></textarea>
                </div>

                <div class="form-submit">
                    <input type="submit" name="auto_inserate_kontakt_submit" value="<?php esc_attr_e( 'Anfrage senden', 'auto-inserate' ); ?>">
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Verarbeitet die Einreichung des Kontaktformulars.
     */
    public function handle_kontaktformular_submission() {
        if ( isset( $_POST['auto_inserate_kontakt_submit'] ) && isset( $_POST['fahrzeug_id'] ) ) {
            $fahrzeug_id = absint( $_POST['fahrzeug_id'] );
            $fahrzeug_titel = sanitize_text_field( wp_unslash( $_POST['fahrzeug_titel'] ) );

            // Nonce verifizieren
            if ( ! isset( $_POST['auto_inserate_kontakt_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['auto_inserate_kontakt_nonce'] ), 'auto_inserate_kontaktformular_nonce_' . $fahrzeug_id ) ) {
                wp_redirect( add_query_arg( 'anfrage_status', 'nonce_fehler', get_permalink( $fahrzeug_id ) ) );
                exit;
            }

            // Daten abrufen und sanitizen
            $name      = isset( $_POST['kontakt_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kontakt_name'] ) ) : '';
            $email     = isset( $_POST['kontakt_email'] ) ? sanitize_email( wp_unslash( $_POST['kontakt_email'] ) ) : '';
            $telefon   = isset( $_POST['kontakt_telefon'] ) ? sanitize_text_field( wp_unslash( $_POST['kontakt_telefon'] ) ) : ''; // Einfache Sanitization für Telefon
            $nachricht = isset( $_POST['kontakt_nachricht'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kontakt_nachricht'] ) ) : '';

            // Validierung
            if ( empty( $name ) || empty( $email ) || ! is_email( $email ) || empty( $nachricht ) ) {
                wp_redirect( add_query_arg( 'anfrage_status', 'fehler', get_permalink( $fahrzeug_id ) ) );
                exit;
            }

            // E-Mail-Empfänger, Betreff und Inhalt
            $empfaenger = get_option( 'admin_email' ); // Später konfigurierbar machen
            $betreff    = sprintf( __( 'Neue Fahrzeuganfrage für: %s', 'auto-inserate' ), $fahrzeug_titel );

            $mail_body  = "Sie haben eine neue Anfrage für das Fahrzeug: " . $fahrzeug_titel . " (ID: " . $fahrzeug_id . ")\n\n";
            $mail_body .= "Name: " . $name . "\n";
            $mail_body .= "E-Mail: " . $email . "\n";
            if ( ! empty( $telefon ) ) {
                $mail_body .= "Telefon: " . $telefon . "\n";
            }
            $mail_body .= "Nachricht:\n" . $nachricht . "\n\n";
            $mail_body .= "--- Gesendet von der Webseite ---";

            $headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

            // E-Mail senden
            if ( wp_mail( $empfaenger, $betreff, $mail_body, $headers ) ) {
                wp_redirect( add_query_arg( 'anfrage_status', 'erfolg', get_permalink( $fahrzeug_id ) . '#auto-inserate-kontaktformular-' . $fahrzeug_id ) );
                exit;
            } else {
                // Fallback, falls wp_mail fehlschlägt, obwohl selten bei korrekter Serverkonfiguration
                wp_redirect( add_query_arg( 'anfrage_status', 'fehler', get_permalink( $fahrzeug_id ) . '#auto-inserate-kontaktformular-' . $fahrzeug_id ) );
                exit;
            }
        }
    }

    /**
     * Rendert eine Liste von Fahrzeugen über einen Shortcode.
     *
     * @param array $atts Shortcode-Attribute.
     * @return string HTML der Fahrzeugliste.
     */
    public function render_fahrzeug_liste_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'anzahl'     => 5,
            'sortierung' => 'datum_ab', // datum_auf, preis_auf, preis_ab, titel_auf, titel_ab
            'marke'      => '',
            'modell'     => '',
            'zustand'    => '',
            'spalten'    => 3, // Für das Grid-Layout
            'ids'        => '', // Komma-separierte IDs für spezifische Fahrzeuge
        ), $atts, 'auto_inserate_fahrzeugliste' );

        $args = array(
            'post_type'      => 'fahrzeug',
            'posts_per_page' => absint( $atts['anzahl'] ),
            'post_status'    => 'publish',
        );

        // IDs verarbeiten
        if( !empty( $atts['ids'] ) ) {
            $post_ids = array_map( 'absint', explode( ',', $atts['ids'] ) );
            $args['post__in'] = $post_ids;
            $args['orderby'] = 'post__in'; // Behält die Reihenfolge der IDs bei
        }

        // Sortierung
        switch ( $atts['sortierung'] ) {
            case 'datum_auf':
                $args['orderby'] = 'date';
                $args['order'] = 'ASC';
                break;
            case 'preis_auf':
                $args['meta_key'] = '_fahrzeug_preis';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'preis_ab':
                $args['meta_key'] = '_fahrzeug_preis';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'titel_auf':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            case 'titel_ab':
                $args['orderby'] = 'title';
                $args['order'] = 'DESC';
                break;
            case 'datum_ab':
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }
        // Wenn 'post__in' gesetzt ist, überschreibt es andere 'orderby'-Parameter, es sei denn, 'orderby' wird explizit auf etwas anderes gesetzt.
        // Für den Fall, dass IDs übergeben werden, aber eine andere Sortierung gewünscht ist (nicht 'post__in'), müsste man 'orderby' nach 'post__in' setzen.
        // Hier wird 'post__in' bevorzugt, wenn IDs da sind.

        // Taxonomie-Filter
        $tax_query = array( 'relation' => 'AND' );
        if ( ! empty( $atts['marke'] ) ) {
            $tax_query[] = array( 'taxonomy' => 'marke', 'field' => 'slug', 'terms' => sanitize_text_field( $atts['marke'] ) );
        }
        if ( ! empty( $atts['modell'] ) ) {
            $tax_query[] = array( 'taxonomy' => 'modell', 'field' => 'slug', 'terms' => sanitize_text_field( $atts['modell'] ) );
        }
        if ( ! empty( $atts['zustand'] ) ) {
            $tax_query[] = array( 'taxonomy' => 'zustand', 'field' => 'slug', 'terms' => sanitize_text_field( $atts['zustand'] ) );
        }

        if ( count( $tax_query ) > 1 ) {
            $args['tax_query'] = $tax_query;
        }

        $fahrzeuge_query = new WP_Query( $args );
        $spalten_class = 'spalten-' . absint( $atts['spalten'] );

        ob_start();

        if ( $fahrzeuge_query->have_posts() ) {
            echo '<div class="auto-inserate-shortcode-liste fahrzeug-liste ' . esc_attr($spalten_class) . '">';
            while ( $fahrzeuge_query->have_posts() ) {
                $fahrzeuge_query->the_post();
                $post_id = get_the_ID();
                $preis = get_post_meta( $post_id, '_fahrzeug_preis', true );
                $marken = get_the_terms( $post_id, 'marke' );
                $modelle = get_the_terms( $post_id, 'modell' );
                $kilometerstand = get_post_meta( $post_id, '_fahrzeug_kilometerstand', true );
                $erstzulassung = get_post_meta( $post_id, '_fahrzeug_erstzulassung', true );
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('fahrzeug-list-item'); ?>>
                    <div class="fahrzeug-list-item-inner">
                        <div class="fahrzeug-list-image">
                            <a href="<?php the_permalink(); ?>">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                <?php else : ?>
                                    <div class="placeholder-image"><span><?php esc_html_e( 'Kein Bild', 'auto-inserate' ); ?></span></div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="fahrzeug-list-details">
                            <header class="entry-header">
                                <?php the_title( sprintf( '<h3 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h3>' ); ?>
                            </header>
                            <div class="fahrzeug-list-meta">
                                <?php if ( ! empty( $preis ) ) : ?>
                                    <span class="preis"><?php echo esc_html( number_format_i18n( $preis, 0 ) ); ?> <?php esc_html_e( 'EUR', 'auto-inserate' ); ?></span>
                                <?php endif; ?>
                                <ul class="quick-details">
                                    <?php if ( $marken && ! is_wp_error( $marken ) ) : ?>
                                        <li><?php echo esc_html( $marken[0]->name ); ?></li>
                                    <?php endif; ?>
                                    <?php if ( $modelle && ! is_wp_error( $modelle ) ) : ?>
                                        <li><?php echo esc_html( $modelle[0]->name ); ?></li>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $kilometerstand ) ) : ?>
                                        <li><?php echo esc_html( number_format_i18n( $kilometerstand, 0 ) ); ?> km</li>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $erstzulassung ) ) : ?>
                                        <li>EZ: <?php echo esc_html( $erstzulassung ); ?></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </article>
                <?php
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__( 'Keine Fahrzeuge gefunden, die den Kriterien entsprechen.', 'auto-inserate' ) . '</p>';
        }

        return ob_get_clean();
    }
}
?>
