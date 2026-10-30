<?php
/*
 * Kontrakt integracji z PixelYourSite sprawdzany na prawdziwej paczce PYS.
 *
 * Integracja opiera sie na udokumentowanych filtrach PYS, ale tez na kilku
 * nazwach z jego wnetrza (sciezki skryptow, akcje, trasy, metody, hak JS).
 * Zmiana ktorejkolwiek nie daje bledu, tylko po cichu wylacza jedna warstwe
 * bramki, dlatego przed wydaniem wtyczki i po kazdym nowym wydaniu PYS ten
 * skrypt sprawdza je na kodzie PYS, a nie na atrapach z tests/pys-gate.php.
 *
 * Uruchomienie (wersja PYS z wordpress.org, katalog poza repo):
 *   curl -sSLo /tmp/pys.zip https://downloads.wordpress.org/plugin/pixelyoursite.11.4.4.zip
 *   unzip -qo /tmp/pys.zip -d /tmp/pys-11.4.4
 *   php tests/pys-contract.php /tmp/pys-11.4.4/pixelyoursite
 * Kod wyjscia 1 przy pierwszym naruszeniu. UWAGA oznacza zachowanie znane i
 * opisane w cookiezen.php, ktore nie jest bledem integracji.
 */

if ( $argc < 2 || ! is_dir( $argv[1] ) || ! is_file( $argv[1] . '/pixelyoursite.php' ) ) {
    fwrite( STDERR, "Uzycie: php tests/pys-contract.php <katalog wtyczki pixelyoursite>\n" );
    exit( 2 );
}
$pys = rtrim( $argv[1], '/' );

/** Tresc wszystkich plikow PHP PYS, bez katalogu MCP. */
function pys_php_source( $dir ) {
    $out = '';
    $it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
    foreach ( $it as $file ) {
        if ( 'php' === $file->getExtension() && false === strpos( $file->getPathname(), '/mcp/' ) ) {
            $out .= file_get_contents( $file->getPathname() ) . "\n";
        }
    }
    return $out;
}

$php = pys_php_source( $pys );
$js  = (string) @file_get_contents( $pys . '/dist/scripts/public.js' );
preg_match( "/define\(\s*'PYS_FREE_VERSION',\s*'([^']+)'/", (string) file_get_contents( $pys . '/pixelyoursite.php' ), $m );
echo 'PixelYourSite ' . ( $m[1] ?? '?' ) . "\n";

$failures = 0;
function contract( $label, $ok, $warn_only = false ) {
    global $failures;
    if ( $ok ) {
        echo "ok    {$label}\n";
        return;
    }
    if ( $warn_only ) {
        echo "UWAGA {$label}\n";
        return;
    }
    $failures++;
    echo "FAIL  {$label}\n";
}

// Wykrycie PYS: cookiezen_pys_active() i cookiezen_pys_unhook_session_orders() wolaja PixelYourSite\PYS().
$pys_function_ns = null;
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $pys, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
    if ( 'php' !== $file->getExtension() || false !== strpos( $file->getPathname(), '/mcp/' ) ) {
        continue;
    }
    $source = (string) file_get_contents( $file->getPathname() );
    if ( 1 === preg_match( '/^function PYS\(\)\s*\{\s*return PYS::instance\(\);/m', $source ) ) {
        $pys_function_ns = preg_match( '/^namespace\s+([A-Za-z0-9_\\\\]+)\s*;/m', $source, $ns ) ? $ns[1] : '';
        break;
    }
}
contract( 'funkcja PixelYourSite\PYS() zwraca instancje PYS', 'PixelYourSite' === $pys_function_ns );

// Wstrzymanie skryptow i bootstrap.
contract( 'skrypty PYS spod katalogu wtyczki (PYS_FREE_URL z plugin_dir_url)', 1 === preg_match( "/define\(\s*'PYS_FREE_URL',\s*untrailingslashit\(\s*plugin_dir_url\(/", $php ) );
contract( 'uchwyt skryptu pys z public.js albo public.min.js', 1 === preg_match( "#wp_enqueue_script\(\s*'pys',\s*PYS_FREE_URL\s*\.\s*'/dist/scripts/public(\.min)?\.js'#", $php ) );
contract( 'pysOptions przez wp_localize_script, poza wstrzymanym tagiem', 1 === preg_match( "/wp_localize_script\(\s*'pys',\s*'pysOptions'/", $php ) );
// Bootstrap zeruje odpowiedz prefetchu pbid sprzed zgody; po zmianie nazwy PYS uzylby odmowy z serwera.
contract( 'public.js czyta odpowiedz prefetchu pbid z window.pysPbidPrefetch', false === strpos( $js, 'pbid' ) || false !== strpos( $js, 'window.pysPbidPrefetch' ) );
contract( 'prefetch pbid w head zapisuje window.pysPbidPrefetch', false === strpos( $js, 'window.pysPbidPrefetch' ) || false !== strpos( $php, 'window.pysPbidPrefetch=' ) );
contract( 'public.js startuje bez zdarzen load i DOMContentLoaded', '' !== $js && 0 === preg_match( '/DOMContentLoaded|addEventListener\(\s*[\'"]load[\'"]|\.on\(\s*[\'"]load[\'"]|window\.onload/', $js ) );

// Hak przegladarki po wycofaniu zgody.
contract( 'hak pys_disable_event_filter wolany przed zdarzeniem Meta', false !== strpos( $js, "window.pys_disable_event_filter(name,'facebook')" ) );
foreach ( array( 'ga', 'gtm', 'openai' ) as $pixel ) {
    contract( "hak pys_disable_event_filter wolany przed zdarzeniem {$pixel}", 1 === preg_match( "/window\.pys_disable_event_filter\(\s*name,\s*'" . $pixel . "'\s*\)/", $js ), 'openai' === $pixel );
}
contract( 'straznik haka sprawdza pys_disable_event_filter (PYS od 11.4.3)', 1 === preg_match( '/typeof window\.pys_disable_event_filter === "function"/', $js ), true );

// Bramka serwerowa.
foreach ( array( 'pys_disable_server_event_filter', 'pys_check_consent_by_gdpr', 'pys_pixel_restricted_mode', 'pys_skip_session', 'pys_disable_all_cookie', 'pys_disable_advanced_form_data_cookie', 'pys_disable_externalID_by_gdpr' ) as $filter ) {
    contract( "filtr {$filter}", 1 === preg_match( "/apply_filters\(\s*'" . $filter . "'/", $php ), in_array( $filter, array( 'pys_check_consent_by_gdpr', 'pys_pixel_restricted_mode', 'pys_skip_session' ), true ) );
}
contract( 'funkcja pys_html_render_context', false !== strpos( $php, 'function pys_html_render_context(' ), true );
contract( 'trasa REST pys-facebook/v1/event', 1 === preg_match( "#register_rest_route\(\s*'pys-facebook/v1',\s*'/event'#", $php ) );
contract( 'trasa REST pys-openai/v1/event', 1 === preg_match( "#register_rest_route\(\s*'pys-openai/v1',\s*'/event'#", $php ), true );
contract( 'akcja AJAX pys_api_event', false !== strpos( $php, "'wp_ajax_nopriv_pys_api_event'" ) );
contract( 'akcja AJAX pys_openai_api_event', false !== strpos( $php, "'wp_ajax_nopriv_pys_openai_api_event'" ), true );
contract( 'wysylka asynchroniczna Meta: pys_send_server_event', false !== strpos( $php, "protected \$action = 'pys_send_server_event'" ) );
contract( 'wysylka asynchroniczna OpenAI: pys_send_openai_server_event', false !== strpos( $php, "protected \$action = 'pys_send_openai_server_event'" ), true );

// Wnetrze PYS, z ktorego bramka zdejmuje callbacki.
contract( 'EventsManager::outputNoScriptData na wp_footer, priorytet 10', 1 === preg_match( "/add_action\(\s*'wp_footer',\s*array\(\s*\\\$this,\s*'outputNoScriptData'\s*\)(\s*,\s*10)?\s*\)/", $php ) );
contract( 'EventsManager powstaje w managePixels na template_redirect, priorytet 1', 1 === preg_match( "/add_action\(\s*'template_redirect',\s*array\(\s*\\\$this,\s*'managePixels'\s*\),\s*1\s*\)/", $php ) );
foreach ( array( 'woocommerce_checkout_order_processed', 'woocommerce_store_api_checkout_order_processed', 'woocommerce_new_order' ) as $hook ) {
    contract( "woo_remember_session_order na {$hook}, priorytet 10", 1 === preg_match( "/add_action\(\s*'" . $hook . "',\s*array\(\s*\\\$this,\s*'woo_remember_session_order'\s*\),\s*10/", $php ), true );
}

// Sprzatanie ciasteczek: kazda nazwa zapisywana przez PYS musi pasowac do wzorca z cookiezen_pys_mirror_js().
$names = array();
preg_match_all( '/Cookies\.set\(\s*[\'"]([A-Za-z0-9_]+)[\'"]/', $js, $mm );
$names = array_merge( $names, $mm[1] );
preg_match_all( '/var (PYS_[A-Z_]+_KEY)\s*=\s*[\'"]([A-Za-z0-9_]+)[\'"]/', $js, $mm, PREG_SET_ORDER );
foreach ( $mm as $const ) {
    if ( false !== strpos( $js, 'Cookies.set(' . $const[1] ) ) {
        $names[] = $const[2];
    }
}
preg_match_all( '/setcookie\(\s*[\'"]([A-Za-z0-9_]+)[\'"]/', $php, $mm );
$names = array_unique( array_merge( $names, $mm[1] ) );
sort( $names );
// Wzorzec brany z cookiezen.php, zeby kontrakt nie mial wlasnej kopii.
preg_match( '#if\((/[^/]+/)\.test\(m\)\)#', (string) file_get_contents( dirname( __DIR__ ) . '/cookiezen.php' ), $pattern );
contract( 'wzorzec sprzatania znaleziony w cookiezen_pys_mirror_js()', isset( $pattern[1] ) );
$missed = isset( $pattern[1] ) ? array_values( array_filter( $names, function ( $name ) use ( $pattern ) {
    return 1 !== preg_match( $pattern[1], $name );
} ) ) : $names;
contract( 'wzorzec sprzatania obejmuje ciasteczka PYS (' . implode( ', ', $names ) . ')', array() === $missed );
if ( $missed ) {
    echo '      poza wzorcem: ' . implode( ', ', $missed ) . "\n";
}

// Sprzatanie sessionStorage: kazdy klucz zapisywany przez PYS musi byc usuwany w cookiezen_pys_mirror_js().
$session_keys = array();
preg_match_all( '/sessionStorage\.setItem\(\s*(?:[\'"]([A-Za-z0-9_]+)[\'"]|([A-Za-z_][A-Za-z0-9_]*))/', $js, $mm, PREG_SET_ORDER );
foreach ( $mm as $call ) {
    if ( '' !== $call[1] ) {
        $session_keys[] = $call[1];
    } elseif ( preg_match( '/var ' . $call[2] . '\s*=\s*[\'"]([A-Za-z0-9_]+)[\'"]/', $js, $const ) ) {
        $session_keys[] = $const[1];
    } else {
        $session_keys[] = '?' . $call[2];
    }
}
$session_keys = array_values( array_unique( $session_keys ) );
preg_match_all( '/sessionStorage\.removeItem\("([^"]+)"\)/', (string) file_get_contents( dirname( __DIR__ ) . '/cookiezen.php' ), $mm );
$session_missed = array_values( array_diff( $session_keys, $mm[1] ) );
contract( 'sprzatanie obejmuje wpisy sessionStorage PYS (' . implode( ', ', $session_keys ) . ')', array() === $session_missed );
if ( $session_missed ) {
    echo '      poza sprzataniem: ' . implode( ', ', $session_missed ) . "\n";
}

echo $failures ? "\n{$failures} naruszen kontraktu\n" : "\nkontrakt spelniony\n";
exit( $failures ? 1 : 0 );
