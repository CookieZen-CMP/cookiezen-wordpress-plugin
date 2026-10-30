<?php
/**
 * Plugin Name: CookieZen
 * Plugin URI: https://cookiezen.pl/?utm_source=wordpress&utm_medium=plugin&utm_campaign=plugin_uri
 * Description: A consent management platform (CMP) for handling visitor consent. Automatic scanner, Google Consent Mode v2, full cookie banner customization and regulatory compliance in one place.
 * Version: 1.0.5-rc.2
 * Author: Semavo Solutions Sp. z o.o.
 * Author URI: https://cookiezen.pl
 * Requires at least: 5.5
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
define( 'COOKIEZEN_VERSION', '1.0.5-rc.2' );

/*
 * Nie wolamy `load_plugin_textdomain()`. Mechanizm just-in-time sam znajduje plik
 * `.mo` biezacej lokalizacji, zarowno w `wp-content/languages/plugins/`, gdzie
 * kladzie go katalog WordPress.org, jak i w `languages/` wtyczki (zmierzone
 * 2026-09-20 na WordPressie 7.1.1: wariant bez tego wywolania nadal pokazuje
 * polski opis wtyczki i polskie podpisy w ustawieniach).
 * Plugin Check zglasza to wywolanie jako odradzane od WordPressa 4.6.
 *
 * Gotowe tlumaczenia (.po/.mo) nie ida w paczce dystrybucyjnej: katalog
 * WordPress.org generuje je z translate.wordpress.org i dostarcza wlasnym kanalem.
 * Zrodla zostaja w repo, w `languages/`.
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
 * wtedy w pierwsza galez i zwraca `true` dla KAZDEJ kategorii - niezaleznie od cookies
 * `wp_consent_*`, ktore CookieZen poprawnie zapisuje z przegladarki. Skutek: wtyczki
 * sprawdzajace zgode po stronie serwera przepuszczaly skrypty mimo odmowy uzytkownika.
 * Zrodlo tej logiki: wp-consent-api, inc/api-functions.php, funkcja wp_has_consent().
 *
 * Sciezka JS byla i jest poprawna - loader ustawia `window.wp_consent_type` w <head>,
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
    if ( cookiezen_pys_enabled() && cookiezen_pys_active() ) {
        wp_add_inline_script( 'cookiezen-loader', cookiezen_pys_mirror_js( $site_key ), 'after' );
    }
    wp_print_scripts( 'cookiezen-loader' );
}
/*
 * Priorytet `1` (nie -9999): wpada PO `<meta charset>` / `<title>` (wymog HTML5 dla
 * detekcji encodingu w pierwszych 1024B head), ale PRZED pluginami marketingowymi
 * (priorytet >=10) ktorych trackery musza byc zblokowane przez nasz MutationObserver.
 * Ekstremalny -9999 wstawial skrypt przed charsetem co rozjezdzalo layout Elementor Pro.
 */
add_action( 'wp_head', 'cookiezen_inject_script', 1 );

/**
 * Atrybuty, po ktorych optymalizatory JS pomijaja skrypt: LiteSpeed
 * (`data-no-optimize`, `data-no-defer`), Cloudflare Rocket Loader
 * (`data-cfasync`), WP Rocket (`nowprocket`) i PageSpeed. Loader musi wykonac
 * sie synchronicznie w <head>, a lustro zaraz po nim; loader i lustro
 * opoznione przez optymalizator zostawiaja PHP bez lustra, czyli CAPI odciete
 * osobom ze zgoda.
 */
function cookiezen_optimizer_attribute_map() {
    return array(
        'data-no-optimize'        => '1',
        'data-no-defer'           => '1',
        'data-cfasync'            => 'false',
        'nowprocket'              => true,
        'data-pagespeed-no-defer' => true,
    );
}

function cookiezen_optimizer_attributes() {
    return ' data-no-optimize="1" data-no-defer="1" data-cfasync="false" nowprocket data-pagespeed-no-defer';
}

function cookiezen_add_optimizer_attributes( $tag ) {
    return preg_replace( '/<script\b(?![^>]*\bdata-no-optimize=)/i', '<script' . cookiezen_optimizer_attributes(), $tag );
}

/*
 * Integracja z PixelYourSite (PYS).
 *
 * PYS wysyla zdarzenia do Meta takze z serwera (Conversions API) i zaklada
 * ciasteczka z PHP, a jego endpointy nie sprawdzaja zgody. Blokada w
 * przegladarce tego nie obejmuje, wiec integracja ma dwie czesci:
 *
 * 1. Przegladarka: skrypty PYS drukuja sie jako nieaktywne `text/plain`, a
 *    baner CookieZen uruchamia przy zgodzie na marketing jeden bootstrap, ktory
 *    laduje je po kolei. Bootstrap zamiast tagu na samym `pys-js`, bo klon
 *    zewnetrznego skryptu wstawiony przez baner wykonuje sie asynchronicznie,
 *    a `public.js` od pierwszej linii potrzebuje `jQuery` i `pysOptions`, ktore
 *    optymalizatory potrafia opoznic do pierwszej interakcji.
 * 2. Serwer: lustro decyzji, sesyjne ciasteczko `cmp_pys_<site_key>` zapisywane
 *    przez skrypt zaraz za loaderem z jego handoffu i ze zdarzenia
 *    `cookiezen:consent`. PHP czyta wylacznie lustro, nie ciasteczko zgody: nie
 *    zalezy od jego formatu, a reset wersji polityki, bramke regionalna i zgode
 *    trzymana tylko w localStorage rozstrzyga loader.
 *
 * Pola handoffu czytane przez lustro przypina test kontraktu w repo cmp-app
 * (`tests/unit/wordpress-plugin-handoff-contract.test.ts`), a pola zdarzenia
 * `tests/unit/banner-consent-event-contract.test.ts`; regula stanu
 * poczatkowego w `cookiezen_pys_mirror_js()` musi pozostac wierna kopia
 * `pluginMarketing` z pierwszego z nich.
 *
 * Wylacznik dla wlasciciela strony: filtr `cookiezen_pys_integration` na false.
 * Usuniecie loadera przez `wp_dequeue_script( 'cookiezen-loader' )` zostawia
 * PYS w przegladarce bez blokady, ale bramka PHP dalej czyta lustro, ktorego
 * nikt nie zapisuje, wiec taka strona potrzebuje tez wylacznika.
 */

function cookiezen_pys_enabled() {
    $site_key = get_option( 'cookiezen_site_key' );
    if ( empty( $site_key ) || ! is_string( $site_key ) ) {
        return false;
    }
    return (bool) apply_filters( 'cookiezen_pys_integration', true );
}

/** PYS free i Pro definiuja te funkcje na starcie wtyczki. */
function cookiezen_pys_active() {
    return function_exists( 'PixelYourSite\PYS' );
}

function cookiezen_pys_cookie_name( $site_key ) {
    return 'cmp_pys_' . preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $site_key );
}

function cookiezen_pys_server_allowed() {
    $name = cookiezen_pys_cookie_name( get_option( 'cookiezen_site_key' ) );
    if ( ! isset( $_COOKIE[ $name ] ) || ! is_string( $_COOKIE[ $name ] ) ) {
        return false;
    }
    return '1' === sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) );
}

function cookiezen_pys_blocks() {
    return cookiezen_pys_enabled() && ! cookiezen_pys_server_allowed();
}

/**
 * Czy kod PYS biegnie poza budowaniem HTML strony.
 *
 * Filtry ciasteczek PYS trafiaja tez do `pysOptions` w HTML, a page cache
 * zamrozilby wartosc `true` dla wszystkich odwiedzajacych, takze po zgodzie.
 * Dlatego przy renderze zostaja nietkniete: przegladarke i tak blokuje baner.
 * Bez funkcji kontekstu (starsze PYS) renderem jest wylacznie wykonanie
 * `wp_enqueue_scripts`, `wp_head` i `wp_footer`, bo tam PYS buduje
 * `pysOptions`; reszta zadania, takze `init` i `template_redirect`, gdzie
 * PYS moze zakladac ciasteczka, jest poza renderem.
 */
function cookiezen_pys_outside_render() {
    if ( function_exists( 'PixelYourSite\pys_html_render_context' ) ) {
        return ! \PixelYourSite\pys_html_render_context();
    }
    return ! ( doing_action( 'wp_enqueue_scripts' ) || doing_action( 'wp_head' ) || doing_action( 'wp_footer' ) );
}

function cookiezen_pys_is_event_route( $route ) {
    return 1 === preg_match( '#^/pys-[a-z]+/v1/event/?$#', (string) $route );
}

/** Akcje AJAX zdarzen PYS: `pys_api_event` (Meta), `pys_openai_api_event` i dodatki. */
function cookiezen_pys_is_event_action( $action ) {
    return 1 === preg_match( '/^pys_[a-z_]*api_event$|^wp_async_pys_send_[a-z_]*server_event$/', (string) $action );
}

/** Skrypty z katalogow PYS free, Pro i dodatkow; jQuery i obce wtyczki zostaja. */
function cookiezen_pys_is_pys_src( $src ) {
    return 1 === preg_match( '#/plugins/pixelyoursite[^/]*/#', (string) $src );
}

/**
 * Uchwyt wstrzymany do zgody: skrypt PYS albo skrypt zalezny (takze posrednio)
 * od skryptu PYS, bo bez wstrzymanej zaleznosci wykonalby sie przed nia.
 *
 * @param string $handle
 * @param array  $registered `wp_scripts()->registered`: uchwyt => obiekt z `src` i `deps`.
 * @param array  $seen       Uchwyty juz na sciezce, ochrona przed cyklem zaleznosci.
 */
function cookiezen_pys_handle_held( $handle, $registered, $seen = array() ) {
    if ( ! isset( $registered[ $handle ] ) || isset( $seen[ $handle ] ) ) {
        return false;
    }
    $script = $registered[ $handle ];
    if ( cookiezen_pys_is_pys_src( $script->src ) ) {
        return true;
    }
    $seen[ $handle ] = true;
    foreach ( (array) $script->deps as $dep ) {
        if ( cookiezen_pys_handle_held( $dep, $registered, $seen ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Zamienia kazdy element <script> uchwytu (takze inline before i after) na
 * nieaktywny `text/plain` z kolejki bootstrapu. Bez `data-cmp-category`, bo
 * baner nie moze ich uruchamiac pojedynczo: kolejnosc trzyma bootstrap.
 * Przepisywany jest tylko znacznik otwierajacy element (na poczatku albo zaraz
 * po `</script>`), nie `<script` wewnatrz tekstu skryptu inline.
 */
function cookiezen_pys_hold_tag( $tag ) {
    return preg_replace_callback(
        '#(\A|</script>)(\s*)<script\b([^>]*)>#i',
        function ( $m ) {
            $attrs = preg_replace( '/\stype\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $m[3] );
            return $m[1] . $m[2] . '<script type="text/plain" data-cookiezen-pys' . cookiezen_optimizer_attributes() . $attrs . '>';
        },
        $tag
    );
}

/**
 * Bootstrap przez `wp_get_inline_script_tag` (WP 5.7+), zeby wtyczki CSP
 * dodajace nonce przez `wp_inline_script_attributes` go widzialy; baner
 * przenosi nonce na klon, a bez niego scisle CSP zablokowaloby start PYS.
 */
function cookiezen_pys_bootstrap_tag() {
    $attributes = array_merge(
        array(
            'type'              => 'text/plain',
            'data-cmp-category' => 'marketing',
            'id'                => 'cookiezen-pys-bootstrap',
        ),
        cookiezen_optimizer_attribute_map()
    );
    if ( function_exists( 'wp_get_inline_script_tag' ) ) {
        return wp_get_inline_script_tag( cookiezen_pys_bootstrap_js(), $attributes );
    }
    return '<script type="text/plain" data-cmp-category="marketing" id="cookiezen-pys-bootstrap"' . cookiezen_optimizer_attributes() . '>' . cookiezen_pys_bootstrap_js() . "</script>\n";
}

function cookiezen_pys_script_loader_tag( $tag, $handle, $src ) {
    static $bootstrap_printed = false;

    if ( 'cookiezen-loader' === $handle ) {
        return cookiezen_add_optimizer_attributes( $tag );
    }
    if ( ! cookiezen_pys_enabled() || ! wp_script_is( 'cookiezen-loader', 'done' ) ) {
        return $tag;
    }
    if ( ! cookiezen_pys_is_pys_src( $src ) && ! cookiezen_pys_handle_held( $handle, wp_scripts()->registered ) ) {
        return $tag;
    }

    $tag = cookiezen_pys_hold_tag( $tag );
    if ( ! $bootstrap_printed ) {
        $bootstrap_printed = true;
        $tag .= cookiezen_pys_bootstrap_tag();
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'cookiezen_pys_script_loader_tag', 10, 3 );

/**
 * Lustro decyzji. Stan poczatkowy z handoffu loadera: przy polu `meta` zywe
 * `meta.granted`, ktore zna tez bramke regionalna, bez niego zapisana zgoda albo
 * zwolnienie regionalne. Referencja do handoffu jest brana od razu, bo loader
 * innego klucza na tej samej stronie nadpisuje `window.__CMP_LOADER`.
 *
 * Zmiany wylacznie ze zdarzenia banera, wedlug tej samej reguly co natywna
 * zgoda TikToka i Pinteresta w loaderze: marketing przyznaje, sfinalizowany
 * brak marketingu odbiera, reszta nic nie zmienia. Nie z `meta.granted`, bo
 * baner zapisuje je do biezacego handoffu, ktory przy dwoch kluczach nalezy do
 * innego przebiegu, a zgodnosc z momentem zdarzenia nie jest niczym przypieta.
 *
 * `SameSite=None` na HTTPS, bo powrot z bramki platnosci przychodzi
 * miedzywitrynowym POST-em, z ktorym `Lax` nie dochodzi, a Purchase z PHP
 * zostalby zablokowany osobie ze zgoda. `__cookiezenPysStart` mowi bootstrapowi,
 * czy odslona startowala z lustrem.
 *
 * Ten sam stan steruje przegladarka przez `window.pys_disable_event_filter`:
 * PYS pyta te funkcje przed kazdym zdarzeniem (Meta, GA, GTM, OpenAI), jeszcze
 * przed wysylka z serwera, wiec po wycofaniu zgody zaladowany juz PYS milknie od
 * razu, a nie od nastepnej odslony. Funkcja wpisana wczesniej przez kogos innego
 * jest pytana dalej, gdy zgoda jest. PYS przed 11.4.3 sprawdza istnienie innej
 * funkcji niz ta, ktora wola, wiec hak tam nie dziala: Pixel zatrzymuje wtedy
 * natywny revoke loadera, CAPI bramka PHP, a PYS milknie od nastepnej odslony.
 * Nie podstawiamy tamtej funkcji, bo jej nazwa (`pys_event_data_filter`)
 * zapowiada filtr danych zdarzenia, a nie przelacznik.
 *
 * Bez lustra wtyczka usuwa identyfikatory, ktore zapisuje PYS: `pys*`,
 * `last_pys*`, `pbid`, `_fbp` i `_fbc` (dwa ostatnie PYS zaklada sam przy
 * ladowaniu piksela Meta), `__oppref` (identyfikator klikniecia OpenAI z PHP) oraz
 * wpis sessionStorage z referrerem sesji. Przy wycofaniu i na starcie kazdej
 * odslony bez zgody, bo zdarzenie spoza haka (np. odswiezenie ID zdarzenia przy
 * koszyku) moze cos jeszcze zapisac do konca strony. Wyjatek to start z odpowiedzia
 * geo w drodze: odwiedzajacy spoza regionu dostanie za chwile zwolnienie, a
 * usuniecie dawaloby mu nowy identyfikator Meta i gubilo atrybucje w kazdej sesji.
 * PYS zaklada je na hoscie albo, przy opcji subdomen, na domenie nadrzednej, wiec
 * usuwanie idzie po obu. `pys_woo_orders` jest HttpOnly i zostaje.
 */
function cookiezen_pys_mirror_js( $site_key ) {
    return '(function(){try{'
        . 'var k=' . wp_json_encode( (string) $site_key ) . ',w=window,d=document,h=w.__CMP_LOADER,on=false,'
        . 'n="cmp_pys_"+k.replace(/[^A-Za-z0-9_-]/g,"");'
        . 'if(!h||h.siteKey!==k){h=null;}'
        . 'function clear(){var c=d.cookie?d.cookie.split(";"):[],p=location.hostname.split("."),s=[""],i,j,m;'
        . 'for(j=p.length-2;j>=0;j--){s.push(";domain=."+p.slice(j).join("."));}'
        . 'for(i=0;i<c.length;i++){m=c[i].split("=")[0].replace(/^\s+|\s+$/g,"");'
        . 'if(/^(pys|last_pys)|^(pbid|_fbp|_fbc|__oppref)$/.test(m)){for(j=0;j<s.length;j++){d.cookie=m+"=;path=/;max-age=0"+s[j];}}}'
        . 'try{w.sessionStorage.removeItem("pys_session_entry_referrer");}catch(e){}}'
        . 'function set(v,keep){on=v;try{d.cookie=n+"="+(v?"1":"")+";path=/"+(v?"":";max-age=0")+(location.protocol==="https:"?";SameSite=None;Secure":";SameSite=Lax");if(!v&&!keep){clear();}}catch(e){}}'
        . 'function start(){if(!h){return false;}if(h.meta){return !!h.meta.granted;}'
        . 'var c=h.existingCategories;return !!(h.hasConsent&&c&&c.marketing)||!!(h.region&&h.region.exempt);}'
        . 'var prev=w.pys_disable_event_filter;'
        . 'w.pys_disable_event_filter=function(name,pixel){if(!on){return true;}return typeof prev==="function"?!!prev(name,pixel):false;};'
        . 'var s0=start();w.__cookiezenPysStart=s0;set(s0,!!(h&&h.region&&h.region.pending));'
        . 'if(h){w.addEventListener("cookiezen:consent",function(e){'
        . 'var d=(e&&e.detail)||{};'
        . 'if(d.categories&&d.categories.marketing){set(true);}'
        . 'else if(d.finalized){set(false);}'
        . '});}'
        . '}catch(e){}})();';
}

/**
 * Bootstrap uruchamiany przez baner. Kolejno:
 * - odroczenie o jedno zadanie, bo baner aktywuje skrypty przed wyslaniem
 *   `cookiezen:consent`, a lustro musi juz stac, zanim PYS cokolwiek wysle;
 * - wyzerowanie `pysPbidPrefetch`, gdy odslona startowala bez lustra: prefetch
 *   z <head> odpytal wtedy serwer przed zgoda i dostal odmowe, a PYS uzylby jej
 *   zamiast wlasnego zapytania;
 * - czekanie na koniec parsowania, `jQuery` i `pysOptions`: sprawdzenie co
 *   100 ms przez 15 s, potem przy kazdej interakcji, bo opoznianie JS w WP Rocket
 *   i LiteSpeed laduje skrypty dopiero po niej; lista obejmuje tez ruch myszy,
 *   kolko i dotyk, bo optymalizator potrafi zaladowac skrypty po samym ruchu
 *   myszy, a bootstrap nasluchujacy tylko klikniec czekalby dalej;
 * - jedno ostrzezenie w konsoli, gdy po 15 s strona jest wczytana i jest
 *   jQuery, ale nie ma `pysOptions`: tak wygladalaby zmiana sposobu, w jaki PYS
 *   podaje konfiguracje, a bez komunikatu PYS po prostu by nie ruszyl;
 * - ladowanie skryptow z kolejki po jednym, w kolejnosci dokumentu.
 */
function cookiezen_pys_bootstrap_js() {
    return '(function(){var w=window,d=document;'
        . 'if(w.__cookiezenPysBoot){return;}w.__cookiezenPysBoot=1;var warned=false;'
        . 'var ev=["mousedown","mousemove","mouseover","pointerdown","keydown","touchstart","touchmove","wheel","scroll","click"];'
        . 'function ready(){return d.readyState!=="loading"&&!!w.jQuery&&typeof w.pysOptions==="object";}'
        . 'function poll(){var n=0,t=setInterval(function(){if(ready()){clearInterval(t);run();}else if(++n>=150){clearInterval(t);'
        . 'if(!warned&&d.readyState==="complete"&&w.jQuery&&typeof w.pysOptions!=="object"&&w.console){warned=true;w.console.warn("CookieZen: PixelYourSite settings (pysOptions) not found, PixelYourSite was not started.");}'
        . 'listen();}},100);}'
        . 'function listen(){function h(){for(var i=0;i<ev.length;i++){w.removeEventListener(ev[i],h,true);}poll();}'
        . 'for(var i=0;i<ev.length;i++){w.addEventListener(ev[i],h,true);}}'
        . 'function load(list,i){for(;i<list.length;i++){var n=list[i],s=d.createElement("script"),a,at;'
        . 'for(a=0;a<n.attributes.length;a++){at=n.attributes[a];if(at.name==="type"||at.name==="data-cookiezen-pys"){continue;}s.setAttribute(at.name,at.value);}'
        . 'try{if(n.nonce){s.nonce=n.nonce;}}catch(e){}'
        . 'if(n.src){s.async=false;s.onload=s.onerror=(function(k){return function(){load(list,k+1);};})(i);'
        . 'n.parentNode.insertBefore(s,n);n.parentNode.removeChild(n);return;}'
        . 's.text=n.text;n.parentNode.insertBefore(s,n);n.parentNode.removeChild(n);}}'
        . 'function run(){if(!w.__cookiezenPysStart){w.pysPbidPrefetch=null;}var q=d.querySelectorAll("script[data-cookiezen-pys]"),l=[],i;'
        . 'for(i=0;i<q.length;i++){l.push(q[i]);}load(l,0);}'
        . 'setTimeout(function(){if(ready()){run();}else{poll();}},0);})();';
}

/*
 * Bramka PHP: bez lustra PYS nie wysyla nic z serwera i nie zaklada ciasteczek.
 * Kazdy filtr przy wylaczonej integracji albo z lustrem oddaje decyzje PYS.
 */

function cookiezen_pys_filter_disable_server_event( $disabled ) {
    return cookiezen_pys_blocks() ? true : $disabled;
}
add_filter( 'pys_disable_server_event_filter', 'cookiezen_pys_filter_disable_server_event', 99 );

function cookiezen_pys_filter_check_consent( $consented, $pixel = '' ) {
    return cookiezen_pys_blocks() ? false : $consented;
}
add_filter( 'pys_check_consent_by_gdpr', 'cookiezen_pys_filter_check_consent', 99, 2 );

/** Tryb ograniczony wlaczony cudzym filtrem przepuszcza wysylke mimo braku zgody. */
function cookiezen_pys_filter_restricted_mode( $restricted, $pixel = '' ) {
    return cookiezen_pys_blocks() ? false : $restricted;
}
add_filter( 'pys_pixel_restricted_mode', 'cookiezen_pys_filter_restricted_mode', 99, 2 );

function cookiezen_pys_filter_skip_session( $skip ) {
    return cookiezen_pys_blocks() ? true : $skip;
}
add_filter( 'pys_skip_session', 'cookiezen_pys_filter_skip_session', 99 );

function cookiezen_pys_filter_disable_cookie( $disabled ) {
    return ( cookiezen_pys_blocks() && cookiezen_pys_outside_render() ) ? true : $disabled;
}
add_filter( 'pys_disable_all_cookie', 'cookiezen_pys_filter_disable_cookie', 99 );
add_filter( 'pys_disable_advanced_form_data_cookie', 'cookiezen_pys_filter_disable_cookie', 99 );
add_filter( 'pys_disable_externalID_by_gdpr', 'cookiezen_pys_filter_disable_cookie', 99 );

/** Endpointy REST zdarzen PYS (`pys-facebook/v1/event`, `pys-openai/v1/event`). */
function cookiezen_pys_filter_rest_pre_dispatch( $result, $server, $request ) {
    if ( null !== $result || ! cookiezen_pys_is_event_route( $request->get_route() ) || ! cookiezen_pys_blocks() ) {
        return $result;
    }
    return new WP_Error( 'cookiezen_pys_no_consent', 'No marketing consent.', array( 'status' => 403 ) );
}
add_filter( 'rest_pre_dispatch', 'cookiezen_pys_filter_rest_pre_dispatch', 10, 3 );

/**
 * Akcje AJAX zdarzen PYS i asynchroniczna wysylka zdarzen z serwera. Jeden
 * straznik na `admin_init` zamiast hakow per akcja, bo kazdy piksel ma wlasna
 * akcje (OpenAI wysyla przez nia bez filtra zgody), a dodatki i Pro dokladaja
 * kolejne. Wysylka asynchroniczna (`WP_Async_Task` PYS) wraca na
 * `admin-post.php` z ciasteczkami odwiedzajacego, w tym z lustrem, wiec ta sama
 * regula rozstrzyga ja tak samo jak zapytanie z przegladarki.
 */
function cookiezen_pys_ajax_guard() {
    $admin_post = isset( $GLOBALS['pagenow'] ) && 'admin-post.php' === $GLOBALS['pagenow'];
    if ( ! ( wp_doing_ajax() || $admin_post ) || ! isset( $_REQUEST['action'] ) || ! is_string( $_REQUEST['action'] ) ) {
        return;
    }
    if ( cookiezen_pys_is_event_action( sanitize_key( wp_unslash( $_REQUEST['action'] ) ) ) && cookiezen_pys_blocks() ) {
        wp_send_json_error( array( 'message' => 'No marketing consent.' ), 403 );
    }
}
add_action( 'admin_init', 'cookiezen_pys_ajax_guard', 0 );

/**
 * Obrazek `<noscript>` Pixela PYS drukuje bez sprawdzania zgody. Zdejmowany
 * bezwarunkowo przy wlaczonej integracji, bo bez JS baner nie dziala i zgody
 * nie da sie udzielic, wiec jest to bezpieczne takze dla page cache.
 * Wyszukiwany po klasie i metodzie, bo PYS tworzy `EventsManager` przez `new`,
 * a `EventsManager::instance()` zwrocilby inny obiekt niz zarejestrowany. Na
 * `template_redirect` z priorytetem 2, bo PYS tworzy go tam z priorytetem 1.
 */
function cookiezen_pys_remove_noscript() {
    global $wp_filter;
    if ( ! cookiezen_pys_enabled() || empty( $wp_filter['wp_footer'] ) || empty( $wp_filter['wp_footer']->callbacks[10] ) ) {
        return;
    }
    foreach ( $wp_filter['wp_footer']->callbacks[10] as $entry ) {
        $callback = $entry['function'];
        if ( is_array( $callback ) && is_object( $callback[0] ) && $callback[0] instanceof \PixelYourSite\EventsManager && 'outputNoScriptData' === $callback[1] ) {
            remove_action( 'wp_footer', $callback, 10 );
        }
    }
}
add_action( 'template_redirect', 'cookiezen_pys_remove_noscript', 2 );

/**
 * `pys_woo_orders` PYS zapisuje przy zlozeniu zamowienia bez zadnego filtra,
 * wiec bez lustra odpinamy jego haki. Na `wp_loaded`, bo PYS dopina je w
 * konstruktorze przy ladowaniu wtyczek.
 */
function cookiezen_pys_unhook_session_orders() {
    if ( ! cookiezen_pys_active() || ! cookiezen_pys_blocks() ) {
        return;
    }
    $pys = \PixelYourSite\PYS();
    if ( ! is_object( $pys ) || ! method_exists( $pys, 'woo_remember_session_order' ) ) {
        return;
    }
    $callback = array( $pys, 'woo_remember_session_order' );
    remove_action( 'woocommerce_checkout_order_processed', $callback, 10 );
    remove_action( 'woocommerce_store_api_checkout_order_processed', $callback, 10 );
    remove_action( 'woocommerce_new_order', $callback, 10 );
}
add_action( 'wp_loaded', 'cookiezen_pys_unhook_session_orders' );

/**
 * Licznik wersji znacznikow, ktore wtyczka wstawia do HTML. Podnosi sie go tylko
 * wtedy, gdy zmienia sie HTML strony, bo jego zmiana czysci page cache klientow.
 */
define( 'COOKIEZEN_MARKUP_VERSION', '2' );

/**
 * Jednorazowe czyszczenie page cache po zmianie znacznikow.
 *
 * Strona z cache sprzed aktualizacji nie ma lustra ani blokady PYS, wiec bramka
 * PHP odcinalaby CAPI wszystkim do wygasniecia cache. Hak
 * `upgrader_process_complete` nie wystarcza, bo wykonuje go jeszcze stary kod,
 * wiec decyduje porownanie zapisanej wersji znacznikow; przy pierwszym
 * zapisaniu klucza strony wersji jeszcze nie ma, wiec czyszczenie tez rusza.
 *
 * Czyszczenie biegnie od razu na `init`, przed wyslaniem naglowkow, bo LiteSpeed
 * czysci cache naglowkiem odpowiedzi. Przed petla chroni blokada w opcji: przy
 * nieudanym zapisie wersji albo nieaktualnym cache obiektow ponowienie
 * nastepuje po 10 minutach, nie przy kazdym zadaniu. `add_option` w WordPressie
 * nie jest atomowe, wiec dwa rownolegle pierwsze zadania moga wyczyscic cache
 * dwa razy; to jedyny skutek. Cache serwerowego bez wtyczki (Cloudflare APO,
 * Varnish hostingu) nie da sie stad wyczyscic; mowi o tym readme.
 */
function cookiezen_maybe_purge_cache_after_update() {
    if ( empty( get_option( 'cookiezen_site_key' ) ) || COOKIEZEN_MARKUP_VERSION === get_option( 'cookiezen_markup_version' ) ) {
        return;
    }
    $lock = (int) get_option( 'cookiezen_purge_lock', 0 );
    if ( $lock && time() - $lock < 10 * MINUTE_IN_SECONDS ) {
        return;
    }
    if ( $lock ) {
        delete_option( 'cookiezen_purge_lock' );
    }
    if ( ! add_option( 'cookiezen_purge_lock', time(), '', 'no' ) ) {
        return;
    }
    update_option( 'cookiezen_markup_version', COOKIEZEN_MARKUP_VERSION );
    cookiezen_purge_page_caches();
}
add_action( 'init', 'cookiezen_maybe_purge_cache_after_update' );

function cookiezen_purge_page_caches() {
    if ( has_action( 'litespeed_purge_all' ) ) {
        do_action( 'litespeed_purge_all' );
    }
    if ( function_exists( 'rocket_clean_domain' ) ) {
        rocket_clean_domain();
    }
    if ( function_exists( 'w3tc_flush_all' ) ) {
        w3tc_flush_all();
    }
    if ( function_exists( 'wp_cache_clear_cache' ) ) {
        wp_cache_clear_cache();
    }
    if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
        sg_cachepress_purge_cache();
    }
    if ( has_action( 'rt_nginx_helper_purge_all' ) ) {
        do_action( 'rt_nginx_helper_purge_all' );
    }
    if ( has_action( 'breeze_clear_all_cache' ) ) {
        do_action( 'breeze_clear_all_cache' );
    }
    if ( function_exists( 'wpfc_clear_all_cache' ) ) {
        wpfc_clear_all_cache( true );
    }
}
