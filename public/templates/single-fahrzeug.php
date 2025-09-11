<?php
/**
 * Template für die Einzelansicht eines Fahrzeugs.
 *
 * @package AutoInserate
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main auto-inserate-single-fahrzeug">

        <?php
        while ( have_posts() ) :
            the_post();

            $post_id = get_the_ID();

            // Meta-Daten abrufen
            $preis = get_post_meta( $post_id, '_fahrzeug_preis', true );
            $kilometerstand = get_post_meta( $post_id, '_fahrzeug_kilometerstand', true );
            $erstzulassung = get_post_meta( $post_id, '_fahrzeug_erstzulassung', true );
            $leistung = get_post_meta( $post_id, '_fahrzeug_leistung', true );
            $getriebe = get_post_meta( $post_id, '_fahrzeug_getriebe', true );
            $getriebe_display = '';
            if ($getriebe === 'manuell') {
                $getriebe_display = __( 'Manuell', 'auto-inserate' );
            } elseif ($getriebe === 'automatik') {
                $getriebe_display = __( 'Automatik', 'auto-inserate' );
            } elseif ($getriebe === 'halbautomatik') {
                $getriebe_display = __( 'Halbautomatik', 'auto-inserate' );
            }

            $farbe = get_post_meta( $post_id, '_fahrzeug_farbe', true );
            $standort_adresse = get_post_meta( $post_id, '_fahrzeug_standort_adresse', true );

            // Taxonomien abrufen
            $marken = get_the_terms( $post_id, 'marke' );
            $modelle = get_the_terms( $post_id, 'modell' );
            $zustaende = get_the_terms( $post_id, 'zustand' );
            $kraftstoffarten = get_the_terms( $post_id, 'kraftstoffart' );
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header><!-- .entry-header -->

                <div class="entry-content">
                    <div class="fahrzeug-details-grid">
                        <div class="fahrzeug-gallery">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="fahrzeug-featured-image">
                                    <?php the_post_thumbnail( 'large' ); ?>
                                </div>
                            <?php else : ?>
                                <div class="fahrzeug-featured-image placeholder">
                                    <p><?php esc_html_e( 'Kein Bild vorhanden', 'auto-inserate' ); ?></p>
                                </div>
                            <?php endif; ?>
                            <!-- Hier könnte eine erweiterte Galerie später folgen -->
                        </div>

                        <div class="fahrzeug-meta-data">
                            <h2><?php esc_html_e( 'Fahrzeugdaten', 'auto-inserate' ); ?></h2>
                            <ul>
                                <?php if ( ! empty( $preis ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Preis:', 'auto-inserate' ); ?></strong> <?php echo esc_html( number_format_i18n( $preis, 0 ) ); ?> <?php esc_html_e( 'EUR', 'auto-inserate' ); // Ggf. Währung konfigurierbar machen ?></li>
                                <?php endif; ?>

                                <?php if ( $marken && ! is_wp_error( $marken ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Marke:', 'auto-inserate' ); ?></strong> <?php echo esc_html( $marken[0]->name ); ?></li>
                                <?php endif; ?>

                                <?php if ( $modelle && ! is_wp_error( $modelle ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Modell:', 'auto-inserate' ); ?></strong> <?php echo esc_html( $modelle[0]->name ); ?></li>
                                <?php endif; ?>

                                <?php if ( ! empty( $kilometerstand ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Kilometerstand:', 'auto-inserate' ); ?></strong> <?php echo esc_html( number_format_i18n( $kilometerstand, 0 ) ); ?> km</li>
                                <?php endif; ?>

                                <?php if ( ! empty( $erstzulassung ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Erstzulassung:', 'auto-inserate' ); ?></strong> <?php echo esc_html( $erstzulassung ); ?></li>
                                <?php endif; ?>

                                <?php if ( ! empty( $leistung ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Leistung:', 'auto-inserate' ); ?></strong> <?php echo esc_html( $leistung ); ?></li>
                                <?php endif; ?>

                                <?php if ( ! empty( $getriebe_display ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Getriebe:', 'auto-inserate' ); ?></strong> <?php echo esc_html( $getriebe_display ); ?></li>
                                <?php endif; ?>

                                <?php if ( ! empty( $farbe ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Farbe:', 'auto-inserate' ); ?></strong> <?php echo esc_html( $farbe ); ?></li>
                                <?php endif; ?>

                                <?php if ( $zustaende && ! is_wp_error( $zustaende ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Zustand:', 'auto-inserate' ); ?></strong>
                                        <?php foreach ( $zustaende as $i => $zustand ) : ?>
                                            <?php echo esc_html( $zustand->name ) . ( $i < count( $zustaende ) - 1 ? ', ' : '' ); ?>
                                        <?php endforeach; ?>
                                    </li>
                                <?php endif; ?>

                                <?php if ( $kraftstoffarten && ! is_wp_error( $kraftstoffarten ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Kraftstoffart:', 'auto-inserate' ); ?></strong>
                                        <?php foreach ( $kraftstoffarten as $i => $kraftstoff ) : ?>
                                            <?php echo esc_html( $kraftstoff->name ) . ( $i < count( $kraftstoffarten ) - 1 ? ', ' : '' ); ?>
                                        <?php endforeach; ?>
                                    </li>
                                <?php endif; ?>

                                <?php if ( ! empty( $standort_adresse ) ) : ?>
                                    <li><strong><?php esc_html_e( 'Standort:', 'auto-inserate' ); ?></strong>
                                        <?php echo esc_html( $standort_adresse ); ?>
                                        <?php
                                        // Prüfen, ob API Key und Koordinaten für die Karte vorhanden sind
                                        $options = get_option( 'auto_inserate_settings' );
                                        $api_key = isset( $options['google_maps_api_key'] ) ? $options['google_maps_api_key'] : '';
                                        $lat = get_post_meta( $post_id, '_fahrzeug_lat', true );
                                        $lng = get_post_meta( $post_id, '_fahrzeug_lng', true );

                                        if ( ! empty( $api_key ) && ! empty( $lat ) && ! empty( $lng ) ) :
                                            // Link wird nicht mehr primär angezeigt, wenn Karte da ist,
                                            // aber als Fallback oder zusätzliche Info nützlich.
                                            // Man könnte ihn auch ganz entfernen, wenn die Karte angezeigt wird.
                                            // echo ' <a href="https://www.google.com/maps?q=' . urlencode( $standort_adresse ) . '" target="_blank" rel="noopener noreferrer" class="map-link">(' . esc_html__( 'Extern öffnen', 'auto-inserate' ) . ')</a>';
                                        else : // Fallback, wenn keine Karte angezeigt werden kann (kein API Key oder keine Koordinaten)
                                            ?>
                                            <a href="https://www.google.com/maps?q=<?php echo urlencode( $standort_adresse ); ?>" target="_blank" rel="noopener noreferrer" class="map-link">
                                                (<?php esc_html_e( 'Auf Karte anzeigen', 'auto-inserate' ); ?>)
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div> <!-- .fahrzeug-meta-data -->
                    </div> <!-- .fahrzeug-details-grid -->

                    <?php
                    // Karte hier anzeigen, wenn Bedingungen erfüllt sind
                    if ( ! empty( $api_key ) && ! empty( $lat ) && ! empty( $lng ) ) : ?>
                    <div class="fahrzeug-karten-container">
                        <h3><?php esc_html_e( 'Standort auf Karte', 'auto-inserate' ); ?></h3>
                        <div id="fahrzeug-karte" style="height: 400px; width: 100%; margin-bottom: 20px;">
                            <p><?php esc_html_e( 'Karte wird geladen...', 'auto-inserate' ); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="fahrzeug-beschreibung">
                        <h2><?php esc_html_e( 'Beschreibung', 'auto-inserate' ); ?></h2>
                        <?php the_content(); ?>
                    </div>

                    <?php
                    // Wenn Kommentare für diesen Post Type aktiviert sind und Kommentare offen sind oder wir mindestens einen Kommentar haben, laden wir das Kommentar-Template.
                    // if ( comments_open() || get_comments_number() ) :
                    //     comments_template();
                    // endif;
                    ?>
                </div><!-- .entry-content -->

                <section class="fahrzeug-kontakt">
                    <?php
                    if ( class_exists( 'Auto_Inserate_Public' ) && method_exists( 'Auto_Inserate_Public', 'display_fahrzeug_kontaktformular' ) ) {
                        $public_functions = new Auto_Inserate_Public();
                        $public_functions->display_fahrzeug_kontaktformular( $post_id );
                    }
                    ?>
                </section>

                <footer class="entry-footer">
                    <?php // Hier könnten Bearbeitungslinks oder ähnliches stehen ?>
                </footer><!-- .entry-footer -->
            </article><!-- #post-<?php the_ID(); ?> -->

        <?php
        endwhile; // End of the loop.
        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
