<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Taxi_Auth_Shortcodes {

    public static function init() {
        add_shortcode( 'wp_taxi_login_form', array( __CLASS__, 'login_form_shortcode' ) );
        add_shortcode( 'wp_taxi_registration_form', array( __CLASS__, 'registration_form_shortcode' ) );
        add_action( 'init', array( __CLASS__, 'handle_registration' ) );
        add_action( 'init', array( __CLASS__, 'handle_login' ) );
    }

    /**
     * Zeigt das Anmeldeformular an.
     */
    public static function login_form_shortcode() {
        if ( is_user_logged_in() ) {
            return '<p>' . __( 'Sie sind bereits angemeldet.', 'wp-taxi-plugin' ) . ' <a href="' . wp_logout_url( home_url() ) . '">' . __( 'Abmelden', 'wp-taxi-plugin' ) . '</a></p>';
        }

        ob_start();
        ?>
        <div class="wp-taxi-login-form-container">
            <h3><?php _e( 'Anmelden', 'wp-taxi-plugin' ); ?></h3>
            <?php if ( isset( $_GET['login_error'] ) ) : ?>
                <p class="wp-taxi-error"><?php _e( 'Anmeldung fehlgeschlagen. Bitte überprüfen Sie Ihre Daten.', 'wp-taxi-plugin' ); ?></p>
            <?php endif; ?>
            <form id="wp-taxi-login-form" action="" method="post">
                <p>
                    <label for="wp-taxi-user-login"><?php _e( 'Benutzername oder E-Mail', 'wp-taxi-plugin' ); ?></label>
                    <input type="text" name="log" id="wp-taxi-user-login" class="input" value="" size="20" required />
                </p>
                <p>
                    <label for="wp-taxi-user-pass"><?php _e( 'Passwort', 'wp-taxi-plugin' ); ?></label>
                    <input type="password" name="pwd" id="wp-taxi-user-pass" class="input" value="" size="20" required />
                </p>
                <p class="login-remember">
                    <label><input name="rememberme" type="checkbox" id="wp-taxi-rememberme" value="forever" /> <?php _e( 'Angemeldet bleiben', 'wp-taxi-plugin' ); ?></label>
                </p>
                <p class="login-submit">
                    <input type="submit" name="wp-taxi-login-submit" id="wp-taxi-login-submit" class="button button-primary" value="<?php _e( 'Anmelden', 'wp-taxi-plugin' ); ?>" />
                    <input type="hidden" name="wp_taxi_login_nonce" value="<?php echo wp_create_nonce( 'wp_taxi_login_nonce_action' ); ?>" />
                </p>
            </form>
            <p>
                <?php _e( 'Noch kein Konto?', 'wp-taxi-plugin' ); ?>
                <a href="<?php echo esc_url( home_url( '/registrierung' ) ); // TODO: Make this URL configurable ?>"><?php _e( 'Registrieren Sie sich hier', 'wp-taxi-plugin' ); ?></a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Zeigt das Registrierungsformular an.
     */
    public static function registration_form_shortcode() {
        if ( is_user_logged_in() ) {
            return '<p>' . __( 'Sie sind bereits registriert und angemeldet.', 'wp-taxi-plugin' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="wp-taxi-registration-form-container">
            <h3><?php _e( 'Registrieren', 'wp-taxi-plugin' ); ?></h3>
            <?php
            if ( isset( $_GET['registration_error'] ) ) {
                echo '<p class="wp-taxi-error">' . esc_html( urldecode( $_GET['registration_error'] ) ) . '</p>';
            }
            if ( isset( $_GET['registration_success'] ) ) {
                echo '<p class="wp-taxi-success">' . __( 'Registrierung erfolgreich. Sie können sich nun anmelden.', 'wp-taxi-plugin' ) . '</p>';
            }
            ?>
            <form id="wp-taxi-registration-form" action="" method="post">
                <p>
                    <label for="wp-taxi-reg-username"><?php _e( 'Benutzername', 'wp-taxi-plugin' ); ?> *</label>
                    <input type="text" name="reg_username" id="wp-taxi-reg-username" class="input" required />
                </p>
                <p>
                    <label for="wp-taxi-reg-email"><?php _e( 'E-Mail-Adresse', 'wp-taxi-plugin' ); ?> *</label>
                    <input type="email" name="reg_email" id="wp-taxi-reg-email" class="input" required />
                </p>
                <p>
                    <label for="wp-taxi-reg-password"><?php _e( 'Passwort', 'wp-taxi-plugin' ); ?> *</label>
                    <input type="password" name="reg_password" id="wp-taxi-reg-password" class="input" required />
                </p>
                <p>
                    <label for="wp-taxi-reg-password-confirm"><?php _e( 'Passwort bestätigen', 'wp-taxi-plugin' ); ?> *</label>
                    <input type="password" name="reg_password_confirm" id="wp-taxi-reg-password-confirm" class="input" required />
                </p>
                <p>
                    <label for="wp-taxi-reg-role"><?php _e( 'Ich möchte mich registrieren als:', 'wp-taxi-plugin' ); ?> *</label>
                    <select name="reg_role" id="wp-taxi-reg-role" required>
                        <option value="customer"><?php _e( 'Kunde', 'wp-taxi-plugin' ); ?></option>
                        <option value="driver"><?php _e( 'Fahrer', 'wp-taxi-plugin' ); ?></option>
                    </select>
                </p>

                <!-- Spezifische Felder für Fahrer (optional, können später hinzugefügt werden) -->
                <div id="driver-specific-fields" style="display:none;">
                    <p>
                        <label for="wp-taxi-driver-license"><?php _e( 'Führerscheinnummer (optional)', 'wp-taxi-plugin' ); ?></label>
                        <input type="text" name="driver_license" id="wp-taxi-driver-license" class="input" />
                    </p>
                     <p>
                        <label for="wp-taxi-vehicle-model"><?php _e( 'Fahrzeugmodell (optional)', 'wp-taxi-plugin' ); ?></label>
                        <input type="text" name="vehicle_model" id="wp-taxi-vehicle-model" class="input" />
                    </p>
                </div>

                <p>
                    <input type="submit" name="wp-taxi-register-submit" class="button button-primary" value="<?php _e( 'Registrieren', 'wp-taxi-plugin' ); ?>" />
                    <input type="hidden" name="wp_taxi_registration_nonce" value="<?php echo wp_create_nonce( 'wp_taxi_registration_nonce_action' ); ?>" />
                </p>
            </form>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    var roleSelect = document.getElementById('wp-taxi-reg-role');
                    var driverFields = document.getElementById('driver-specific-fields');
                    if (roleSelect && driverFields) {
                        roleSelect.addEventListener('change', function() {
                            if (this.value === 'driver') {
                                driverFields.style.display = 'block';
                            } else {
                                driverFields.style.display = 'none';
                            }
                        });
                         // Trigger change on load in case 'driver' is pre-selected
                        if (roleSelect.value === 'driver') {
                            driverFields.style.display = 'block';
                        }
                    }
                });
            </script>
             <p>
                <?php _e( 'Bereits ein Konto?', 'wp-taxi-plugin' ); ?>
                <a href="<?php echo esc_url( home_url( '/anmelden' ) ); // TODO: Make this URL configurable ?>"><?php _e( 'Melden Sie sich hier an', 'wp-taxi-plugin' ); ?></a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Verarbeitet die Registrierungsdaten.
     */
    public static function handle_registration() {
        if ( isset( $_POST['wp-taxi-register-submit'] ) && isset( $_POST['wp_taxi_registration_nonce'] ) ) {
            if ( ! wp_verify_nonce( $_POST['wp_taxi_registration_nonce'], 'wp_taxi_registration_nonce_action' ) ) {
                wp_redirect( add_query_arg( 'registration_error', urlencode( __( 'Sicherheitsüberprüfung fehlgeschlagen.', 'wp-taxi-plugin' ) ), $_SERVER['REQUEST_URI'] ) );
                exit;
            }

            $username   = sanitize_user( $_POST['reg_username'] );
            $email      = sanitize_email( $_POST['reg_email'] );
            $password   = $_POST['reg_password'];
            $password_confirm = $_POST['reg_password_confirm'];
            $role       = sanitize_text_field( $_POST['reg_role'] );

            // Validierung
            if ( empty( $username ) || empty( $email ) || empty( $password ) || empty( $password_confirm ) || empty( $role ) ) {
                wp_redirect( add_query_arg( 'registration_error', urlencode( __( 'Bitte füllen Sie alle Pflichtfelder aus.', 'wp-taxi-plugin' ) ), $_SERVER['REQUEST_URI'] ) );
                exit;
            }
            if ( username_exists( $username ) ) {
                wp_redirect( add_query_arg( 'registration_error', urlencode( __( 'Benutzername bereits vergeben.', 'wp-taxi-plugin' ) ), $_SERVER['REQUEST_URI'] ) );
                exit;
            }
            if ( ! is_email( $email ) ) {
                wp_redirect( add_query_arg( 'registration_error', urlencode( __( 'Ungültige E-Mail-Adresse.', 'wp-taxi-plugin' ) ), $_SERVER['REQUEST_URI'] ) );
                exit;
            }
            if ( email_exists( $email ) ) {
                wp_redirect( add_query_arg( 'registration_error', urlencode( __( 'E-Mail-Adresse bereits registriert.', 'wp-taxi-plugin' ) ), $_SERVER['REQUEST_URI'] ) );
                exit;
            }
            if ( $password !== $password_confirm ) {
                wp_redirect( add_query_arg( 'registration_error', urlencode( __( 'Die Passwörter stimmen nicht überein.', 'wp-taxi-plugin' ) ), $_SERVER['REQUEST_URI'] ) );
                exit;
            }
            if ( ! in_array( $role, array( 'customer', 'driver' ) ) ) {
                wp_redirect( add_query_arg( 'registration_error', urlencode( __( 'Ungültige Rolle ausgewählt.', 'wp-taxi-plugin' ) ), $_SERVER['REQUEST_URI'] ) );
                exit;
            }

            $user_data = array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass'  => $password,
                'role'       => $role,
            );

            $user_id = wp_insert_user( $user_data );

            if ( is_wp_error( $user_id ) ) {
                wp_redirect( add_query_arg( 'registration_error', urlencode( $user_id->get_error_message() ) ), $_SERVER['REQUEST_URI'] ) );
                exit;
            } else {
                // Zusätzliche Fahrermetadaten speichern (optional)
                if ( $role === 'driver' ) {
                    if ( ! empty( $_POST['driver_license'] ) ) {
                        update_user_meta( $user_id, 'driver_license', sanitize_text_field( $_POST['driver_license'] ) );
                    }
                    if ( ! empty( $_POST['vehicle_model'] ) ) {
                        update_user_meta( $user_id, 'vehicle_model', sanitize_text_field( $_POST['vehicle_model'] ) );
                    }
                    // Fahrer könnten standardmäßig eine Genehmigung benötigen
                    update_user_meta( $user_id, 'driver_approved', false );
                }
                // Erfolgreich registriert, umleiten zur Anmeldeseite oder einer Erfolgsmeldung
                wp_redirect( add_query_arg( 'registration_success', 'true', remove_query_arg('registration_error', $_SERVER['REQUEST_URI']) ) );
                exit;
            }
        }
    }

    /**
     * Verarbeitet die Anmeldedaten.
     */
    public static function handle_login() {
        if ( isset( $_POST['wp-taxi-login-submit'] ) && isset( $_POST['wp_taxi_login_nonce'] ) ) {
            if ( ! wp_verify_nonce( $_POST['wp_taxi_login_nonce'], 'wp_taxi_login_nonce_action' ) ) {
                 // Optional: Fehler anzeigen oder einfach ignorieren
                return;
            }

            $creds = array(
                'user_login'    => sanitize_user( $_POST['log'] ),
                'user_password' => $_POST['pwd'],
                'remember'      => isset( $_POST['rememberme'] ),
            );

            $user = wp_signon( $creds, false );

            if ( is_wp_error( $user ) ) {
                $redirect_url = add_query_arg( 'login_error', 'true', $_SERVER['REQUEST_URI'] );
                wp_redirect( $redirect_url );
                exit;
            } else {
                // Erfolgreich angemeldet, wohin umleiten?
                // Basierend auf der Rolle umleiten
                if ( in_array( 'driver', (array) $user->roles ) ) {
                    // TODO: Fahrer-Dashboard-URL
                    wp_redirect( home_url( '/fahrer-dashboard' ) );
                } elseif ( in_array( 'customer', (array) $user->roles ) ) {
                    // TODO: Kunden-Dashboard-URL
                    wp_redirect( home_url( '/kunden-dashboard' ) );
                } else {
                    wp_redirect( home_url() ); // Standard-Umleitung
                }
                exit;
            }
        }
    }
}

WP_Taxi_Auth_Shortcodes::init();
?>
