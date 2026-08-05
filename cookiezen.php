<?php
/**
 * Plugin Name: CookieZen
 * Plugin URI: https://cookiezen.pl/wordpress
 * Description: Integracja z systemem zarządzania zgodami CookieZen. Wpisz Site Key, a my zajmiemy się resztą.
 * Version: 1.0.3
 * Author: Semavo Solutions Sp. z o.o.
 * Author URI: https://cookiezen.pl
 * Requires at least: 5.5
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cookiezen
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cookiezen_register_settings() {
    register_setting(
        'cookiezen_options',
        'cookiezen_site_key',
        array(
            'sanitize_callback' => 'cookiezen_validate_site_key',
        )
    );
}
add_action( 'admin_init', 'cookiezen_register_settings' );

function cookiezen_validate_site_key( $input ) {
    $clean = trim( sanitize_text_field( $input ) );

    if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $clean ) && ! empty( $clean ) ) {
        add_settings_error(
            'cookiezen_site_key',
            'cookiezen_site_key_error',
            'Site Key ma nieprawidłowy format. Dozwolone są tylko litery, cyfry i podkreślniki.',
            'error'
        );
        return get_option( 'cookiezen_site_key' );
    }

    return $clean;
}

function cookiezen_add_options_page() {
    add_options_page(
        'CookieZen',
        'CookieZen',
        'manage_options',
        'cookiezen',
        'cookiezen_options_page_html'
    );
}
add_action( 'admin_menu', 'cookiezen_add_options_page' );

function cookiezen_add_action_links( $links ) {
    $settings_link = '<a href="options-general.php?page=cookiezen">Ustawienia</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cookiezen_add_action_links' );

function cookiezen_options_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'cookiezen_options' );
            do_settings_sections( 'cookiezen_options' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Site Key</th>
                    <td>
                        <input type="text" name="cookiezen_site_key" value="<?php echo esc_attr( get_option( 'cookiezen_site_key' ) ); ?>" class="regular-text" placeholder="np. site_rkzxyh40" />
                        <p class="description">Wprowadź swój klucz strony (site_key) z panelu CookieZen.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Zapisz zmiany' ); ?>
        </form>
    </div>
    <?php
}

/**
 * WP Consent API: deklaracja trybu zgody po stronie PHP.
 *
 * Bez tego filtru `wp_get_consent_type()` zwraca pustke, a `wp_has_consent()` wpada
 * wtedy w pierwsza galez i zwraca `true` dla KAZDEJ kategorii — niezaleznie od cookies
 * `wp_consent_*`, ktore CookieZen poprawnie zapisuje z przegladarki. Skutek: wtyczki
 * sprawdzajace zgode po stronie serwera przepuszczaly skrypty mimo odmowy uzytkownika.
 * Zrodlo tej logiki: wp-consent-api, inc/api-functions.php, funkcja wp_has_consent().
 *
 * Sciezka JS byla i jest poprawna — loader ustawia `window.wp_consent_type` w <head>,
 * a JS-owe `wp_has_consent()` czyta ta zmienna przed czymkolwiek innym. Ten filtr
 * domyka wylacznie warstwe PHP.
 *
 * Rejestrujemy bezwarunkowo, bez `is_plugin_active()`: ta funkcja zyje w
 * wp-admin/includes/plugin.php i nie jest gwarantowana na frontzie, a filtr bez
 * wtyczki WP Consent API nigdy nie zostanie wywolany, wiec jest bezkosztowy.
 *
 * UWAGA: przy pierwszym zadaniu, zanim JS zapisze cookies, PHP zwroci `false` dla
 * wszystkich kategorii, takze `functional`. To zachowanie samego WP Consent API
 * w trybie opt-in (brak cookie = brak zgody), identyczne u innych CMP-ow.
 */
function cookiezen_wp_consent_type() {
    return 'optin';
}
add_filter( 'wp_get_consent_type', 'cookiezen_wp_consent_type' );

/**
 * Script MUSI byc synchroniczny (bez async/defer) — loader instaluje MutationObserver
 * w parse-time aby zblokowac dynamicznie dodawane <script> trackerow. Async spowodowalby
 * fail-open (trackery odpalane PRZED naszym obserwatorem).
 */
function cookiezen_inject_script() {
    $site_key = get_option( 'cookiezen_site_key' );

    if ( empty( $site_key ) ) {
        return;
    }

    $safe_key = urlencode( $site_key );
    echo '<script src="https://cz-cdn.com/api/cmp/loader?site_key=' . $safe_key . '"></script>' . "\n";
}
/*
 * Priorytet `1` (nie -9999): wpada PO `<meta charset>` / `<title>` (wymog HTML5 dla
 * detekcji encodingu w pierwszych 1024B head), ale PRZED pluginami marketingowymi
 * (priorytet >=10) ktorych trackery musza byc zblokowane przez nasz MutationObserver.
 * Ekstremalny -9999 wstawial skrypt przed charsetem co rozjezdzalo layout Elementor Pro.
 */
add_action( 'wp_head', 'cookiezen_inject_script', 1 );
