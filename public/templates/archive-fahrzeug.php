<?php
/**
 * Template für die Archivansicht der Fahrzeuge.
 *
 * @package AutoInserate
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main auto-inserate-archive-fahrzeug">

        <?php if ( have_posts() ) : ?>

            <header class="page-header">
                <?php
                the_archive_title( '<h1 class="page-title">', '</h1>' );
                the_archive_description( '<div class="archive-description">', '</div>' );
                ?>
            </header><!-- .page-header -->

            <div class="fahrzeug-liste">
                <?php
                /* Start the Loop */
                while ( have_posts() ) :
                    the_post();
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
                                        <?php the_post_thumbnail( 'medium' ); // 'medium' oder eine andere passende Größe ?>
                                    <?php else : ?>
                                        <div class="placeholder-image" style="width:100%; height:200px; background:#eee; text-align:center; line-height:200px;">
                                            <?php esc_html_e( 'Kein Bild', 'auto-inserate' ); ?>
                                        </div>
                                    <?php endif; ?>
                                </a>
                            </div>

                            <div class="fahrzeug-list-details">
                                <header class="entry-header">
                                    <?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
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

                                <div class="entry-summary">
                                    <?php the_excerpt(); ?>
                                </div>
                            </div>
                        </div>
                    </article><!-- #post-<?php the_ID(); ?> -->
                    <?php
                endwhile;
                ?>
            </div>
            <?php
            the_posts_navigation(array(
                'prev_text' => __('&laquo; Ältere Fahrzeuge', 'auto-inserate'),
                'next_text' => __('Neuere Fahrzeuge &raquo;', 'auto-inserate'),
            ));

        else :
            ?>
            <section class="no-results not-found">
                <header class="page-header">
                    <h1 class="page-title"><?php esc_html_e( 'Keine Fahrzeuge gefunden', 'auto-inserate' ); ?></h1>
                </header>
                <div class="page-content">
                    <p><?php esc_html_e( 'Es wurden leider keine Fahrzeuge gefunden, die Ihren Kriterien entsprechen. Bitte versuchen Sie es später erneut oder passen Sie Ihre Suche an.', 'auto-inserate' ); ?></p>
                    <?php // Hier könnte ein Link zur Suchseite oder zur Homepage stehen ?>
                </div>
            </section>
            <?php
        endif;
        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
