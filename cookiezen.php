<?php
/**
 * Plugin Name: CookieZen
 * Plugin URI: https://cookiezen.pl/?utm_source=wordpress&utm_medium=plugin&utm_campaign=plugin_uri
 * Description: A consent management platform (CMP) for handling visitor consent. Automatic scanner, Google Consent Mode v2, full cookie banner customization and regulatory compliance in one place.
 * Version: 1.0.4
 * Author: Semavo Solutions Sp. z o.o.
 * Author URI: https://cookiezen.pl
 * Requires at least: 5.5
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cookiezen
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Wersja wtyczki jako stala. MUSI zgadzac sie z naglowkiem `Version` wyzej;
 * pilnuje tego walidacja w scripts/build-wordpress-plugin.sh, bo naglowka nie da
 * sie odczytac na frontzie bez czytania pliku przy kazdym zadaniu.
 */
define( 'COOKIEZEN_VERSION', '1.0.4' );

/*
 * Nie wolamy `load_plugin_textdomain()`. Mechanizm just-in-time sam znajduje
 * `languages/cookiezen-pl_PL.mo` w katalogu wtyczki, takze przy instalacji
 * recznej spoza katalogu WordPress.org, czyli przy paczce pobranej z panelu
 * CookieZen (zmierzone 2026-09-20 na WordPressie 7.1.1: wariant bez tego
 * wywolania nadal pokazuje polski opis wtyczki i polskie podpisy w ustawieniach).
 * Plugin Check zglasza to wywolanie jako odradzane od WordPressa 4.6.
 *
 * Nagłowek `Description` tlumaczy sie przez te sama domene tekstowa, wiec jego
 * tresc musi byc wpisem w katalogu tlumaczen; sam angielski naglowek w pliku
 * nie wystarczy.
 */

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
            __( 'The Site Key has an invalid format. Only letters, digits and underscores are allowed.', 'cookiezen' ),
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
    $settings_link = '<a href="options-general.php?page=cookiezen">' . esc_html__( 'Settings', 'cookiezen' ) . '</a>';
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
                        <input type="text" name="cookiezen_site_key" value="<?php echo esc_attr( get_option( 'cookiezen_site_key' ) ); ?>" class="regular-text" placeholder="<?php echo esc_attr__( 'e.g. site_rkzxyh40', 'cookiezen' ); ?>" />
                        <p class="description"><?php echo esc_html__( 'Enter the Site Key for your site from the CookieZen panel.', 'cookiezen' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
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
 * Skrypt MUSI byc synchroniczny (bez async/defer) i musi stac przed skryptami
 * innych wtyczek. Powody, w kolejnosci waznosci:
 *
 * 1. Sygnaly Consent Mode v2 (defaults) maja dotrzec do tagow Google, ZANIM
 *    gtag albo GTM sie zainicjuje. Po inicjalizacji jest za pozno: tag zdazy
 *    zebrac dane bez zgody.
 * 2. `window.wp_consent_type = 'optin'` musi byc ustawione, zanim jakakolwiek
 *    wtyczka zgodna z WP Consent API odpyta o zgode. Brak tej zmiennej oznacza
 *    fail open, czyli zgode udzielona dla kazdej kategorii.
 * 3. Gdy wlaczone jest automatyczne blokowanie, loader instaluje dodatkowo
 *    MutationObserver w czasie parsowania. Ta sciezka jest dzis wylaczona
 *    globalnie, wiec nie jest argumentem nosnym, ale byla powodem pierwotnym.
 *
 * `async` albo `defer` lamie punkty 1 i 2 niezaleznie od stanu blokowania.
 *
 * Skrypt idzie przez kolejke, ale drukujemy go sami, zamiast czekac na
 * `wp_print_head_scripts` z priorytetu 9. Kolejka daje uchwyt, ktorym wlasciciel
 * strony moze skrypt usunac przez `wp_dequeue_script( 'cookiezen-loader' )`,
 * a reczny druk zachowuje pierwszenstwo wobec wtyczek wypisujacych wlasne tagi
 * wprost do <head> na priorytetach 2-8. Zmierzone 2026-09-20: loader drukuje sie
 * przed skryptami konkurencyjnej wtyczki CMP korzystajacej z kolejki.
 */
function cookiezen_inject_script() {
    $site_key = get_option( 'cookiezen_site_key' );

    if ( empty( $site_key ) ) {
        return;
    }

    $loader_url = add_query_arg( 'site_key', $site_key, 'https://cz-cdn.com/api/cmp/loader' );

    wp_enqueue_script( 'cookiezen-loader', $loader_url, array(), COOKIEZEN_VERSION, false );
    wp_print_scripts( 'cookiezen-loader' );
}
/*
 * Priorytet `1` (nie -9999): wpada PO `<meta charset>` / `<title>` (wymog HTML5 dla
 * detekcji encodingu w pierwszych 1024B head), ale PRZED pluginami marketingowymi
 * (priorytet >=10) ktorych trackery musza byc zblokowane przez nasz MutationObserver.
 * Ekstremalny -9999 wstawial skrypt przed charsetem co rozjezdzalo layout Elementor Pro.
 */
add_action( 'wp_head', 'cookiezen_inject_script', 1 );
