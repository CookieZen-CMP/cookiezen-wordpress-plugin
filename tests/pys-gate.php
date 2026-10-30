<?php
/*
 * Test bramki PixelYourSite bez WordPressa: atrapy kilku funkcji WP, potem
 * prawdziwy cookiezen.php i asercje na jego funkcjach.
 *
 * Uruchomienie: php tests/pys-gate.php (kod wyjscia 1 przy pierwszym bledzie).
 * Repo nie ma PHPUnit ani Composera, a logika ma kilka sciezek wyjscia, wiec
 * test jest zwyklym skryptem. Katalog tests/ nie trafia do paczki.
 */

define( 'ABSPATH', __DIR__ . '/' );

// Ostrzezenie PHP w testowanym kodzie to blad testu, nie szum na wyjsciu.
set_error_handler( function ( $no, $msg, $file, $line ) {
    throw new ErrorException( $msg, 0, $no, $file, $line );
} );

$GLOBALS['cz_options'] = array();
$GLOBALS['cz_filters'] = array();
$GLOBALS['cz_doing_ajax'] = false;
$GLOBALS['cz_actions_removed'] = array();

function add_action( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['cz_filters'][ $hook ][] = array( $cb, $priority, $args ); return true; }
function add_filter( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['cz_filters'][ $hook ][] = array( $cb, $priority, $args ); return true; }
function remove_action( $hook, $cb, $priority = 10 ) { $GLOBALS['cz_actions_removed'][] = $hook; return true; }
function has_action( $hook, $cb = false ) { return 10; }
function apply_filters( $hook, $value ) {
    if ( isset( $GLOBALS['cz_test_filters'][ $hook ] ) ) {
        return $GLOBALS['cz_test_filters'][ $hook ];
    }
    return $value;
}
function get_option( $name, $default = false ) { return array_key_exists( $name, $GLOBALS['cz_options'] ) ? $GLOBALS['cz_options'][ $name ] : $default; }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function wp_unslash( $s ) { return stripslashes( $s ); }
function wp_doing_ajax() { return $GLOBALS['cz_doing_ajax']; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
class Cz_Json_Exit extends Exception {}
function wp_send_json_error( $data = null, $status = null ) { throw new Cz_Json_Exit( (string) $status ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function plugin_basename( $f ) { return 'cookiezen/cookiezen.php'; }
function is_ssl() { return true; }
function doing_action( $hook ) { return in_array( $hook, $GLOBALS['cz_doing'], true ); }
$GLOBALS['cz_doing'] = array();

class WP_Error {
    public $code;
    public $data;
    public function __construct( $code = '', $message = '', $data = '' ) { $this->code = $code; $this->data = $data; }
}
class Cz_Fake_Request {
    private $route;
    public function __construct( $route ) { $this->route = $route; }
    public function get_route() { return $this->route; }
}

require dirname( __DIR__ ) . '/cookiezen.php';
$registered_hooks = $GLOBALS['cz_filters'];

$failures = 0;
function check( $label, $actual, $expected ) {
    global $failures;
    if ( $actual === $expected ) {
        echo "ok   {$label}\n";
        return;
    }
    $failures++;
    echo "FAIL {$label}\n     oczekiwane: " . var_export( $expected, true ) . "\n     otrzymane:  " . var_export( $actual, true ) . "\n";
}

function reset_state( $site_key = 'site_abc', $mirror = null ) {
    $GLOBALS['cz_options'] = array( 'cookiezen_site_key' => $site_key );
    $GLOBALS['cz_test_filters'] = array();
    $GLOBALS['cz_doing_ajax'] = false;
    $GLOBALS['cz_doing'] = array();
    unset( $GLOBALS['pagenow'] );
    $_COOKIE = array();
    $_REQUEST = array();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
    if ( null !== $mirror ) {
        $_COOKIE['cmp_pys_site_abc'] = $mirror;
    }
}

// --- Nazwa lustra i klucz strony ---
check( 'nazwa lustra czysci znaki spoza [A-Za-z0-9_-]', cookiezen_pys_cookie_name( 'site_a-b!c"<d' ), 'cmp_pys_site_a-bcd' );

reset_state( '' );
check( 'pusty klucz strony wylacza integracje', cookiezen_pys_enabled(), false );

reset_state();
check( 'klucz strony i brak wylacznika: integracja wlaczona', cookiezen_pys_enabled(), true );

reset_state();
$GLOBALS['cz_test_filters']['cookiezen_pys_integration'] = false;
check( 'wylacznik cookiezen_pys_integration', cookiezen_pys_enabled(), false );

// --- Odczyt lustra ---
reset_state();
check( 'brak lustra: brak zgody', cookiezen_pys_server_allowed(), false );

reset_state( 'site_abc', '1' );
check( 'lustro 1: zgoda', cookiezen_pys_server_allowed(), true );

reset_state( 'site_abc', '0' );
check( 'lustro 0: brak zgody', cookiezen_pys_server_allowed(), false );

reset_state( 'site_abc', '\\"1\\"' );
check( 'lustro ze slashami WordPressa i cudzyslowami: brak zgody', cookiezen_pys_server_allowed(), false );

reset_state( 'site_abc', array( '1' ) );
check( 'lustro jako tablica: brak zgody', cookiezen_pys_server_allowed(), false );

reset_state( 'site_xyz', '1' );
check( 'lustro innego klucza strony: brak zgody', cookiezen_pys_server_allowed(), false );

foreach ( array( '01', '2', '1.0', '<b>1</b>x', '1\\x' ) as $value ) {
    reset_state( 'site_abc', $value );
    check( "lustro '{$value}': brak zgody", cookiezen_pys_server_allowed(), false );
}
reset_state( 'site_abc', ' 1 ' );
check( 'lustro z bialymi znakami po sanityzacji: zgoda', cookiezen_pys_server_allowed(), true );
reset_state( 'site_abc', '<b>1</b>' );
check( 'lustro ze znacznikami po sanityzacji: zgoda', cookiezen_pys_server_allowed(), true );
reset_state( 'site_abc', '1\\' );
check( 'lustro z ukosnikiem WordPressa po wp_unslash: zgoda', cookiezen_pys_server_allowed(), true );

reset_state( array( 'site_abc' ) );
check( 'klucz strony nie bedacy tekstem wylacza integracje', cookiezen_pys_enabled(), false );

// --- Trasy zdarzen PYS ---
check( 'trasa pys-facebook/v1/event', cookiezen_pys_is_event_route( '/pys-facebook/v1/event' ), true );
check( 'trasa pys-openai/v1/event', cookiezen_pys_is_event_route( '/pys-openai/v1/event' ), true );
check( 'trasa pys/v1/dynamic-options nie jest zdarzeniem', cookiezen_pys_is_event_route( '/pys/v1/dynamic-options' ), false );
check( 'trasa pys-facebook/v1/events nie jest zdarzeniem', cookiezen_pys_is_event_route( '/pys-facebook/v1/events' ), false );
check( 'trasa pys-facebook/v2/event nie jest zdarzeniem', cookiezen_pys_is_event_route( '/pys-facebook/v2/event' ), false );
check( 'trasa z koncowym ukosnikiem', cookiezen_pys_is_event_route( '/pys-facebook/v1/event/' ), true );
check( 'trasa zagniezdzona nie jest zdarzeniem PYS', cookiezen_pys_is_event_route( '/x/pys-facebook/v1/event' ), false );
check( 'trasa bez nazwy piksela', cookiezen_pys_is_event_route( '/pys-/v1/event' ), false );

// --- Akcje AJAX zdarzen PYS ---
check( 'akcja pys_api_event', cookiezen_pys_is_event_action( 'pys_api_event' ), true );
check( 'akcja pys_openai_api_event', cookiezen_pys_is_event_action( 'pys_openai_api_event' ), true );
check( 'akcja pys_get_pbid nie jest zdarzeniem', cookiezen_pys_is_event_action( 'pys_get_pbid' ), false );
check( 'akcja obcej wtyczki', cookiezen_pys_is_event_action( 'other_api_event' ), false );
check( 'akcja z prefiksem przed pys_', cookiezen_pys_is_event_action( 'xpys_api_event' ), false );
check( 'akcja z sufiksem po api_event', cookiezen_pys_is_event_action( 'pys_api_event_x' ), false );
check( 'akcja asynchronicznej wysylki Meta', cookiezen_pys_is_event_action( 'wp_async_pys_send_server_event' ), true );
check( 'akcja asynchronicznej wysylki OpenAI', cookiezen_pys_is_event_action( 'wp_async_pys_send_openai_server_event' ), true );
check( 'obca akcja asynchroniczna', cookiezen_pys_is_event_action( 'wp_async_other_server_event' ), false );

/** Wynik straznika: status odpowiedzi albo null, gdy przepuscil. */
function guard_result( $context, $action, $mirror ) {
    reset_state( 'site_abc', $mirror );
    $GLOBALS['cz_doing_ajax'] = 'ajax' === $context;
    if ( 'admin-post' === $context ) {
        $GLOBALS['pagenow'] = 'admin-post.php';
    }
    $_REQUEST['action'] = $action;
    try {
        cookiezen_pys_ajax_guard();
    } catch ( Cz_Json_Exit $e ) {
        return (int) $e->getMessage();
    }
    return null;
}
check( 'straznik: AJAX zdarzenia bez lustra 403', guard_result( 'ajax', 'pys_openai_api_event', null ), 403 );
check( 'straznik: AJAX zdarzenia z lustrem przechodzi', guard_result( 'ajax', 'pys_openai_api_event', '1' ), null );
check( 'straznik: wysylka asynchroniczna bez lustra 403', guard_result( 'admin-post', 'wp_async_pys_send_openai_server_event', null ), 403 );
check( 'straznik: wysylka asynchroniczna z lustrem przechodzi', guard_result( 'admin-post', 'wp_async_pys_send_server_event', '1' ), null );
check( 'straznik: inna akcja admin-post przechodzi', guard_result( 'admin-post', 'save_settings', null ), null );
check( 'straznik: zwykle admin_init w kokpicie pomijany', guard_result( 'admin', 'wp_async_pys_send_server_event', null ), null );

// --- Skrypty PYS ---
check( 'src PYS free', cookiezen_pys_is_pys_src( 'https://x.pl/wp-content/plugins/pixelyoursite/dist/scripts/public.js?ver=11.4.4' ), true );
check( 'src PYS Pro', cookiezen_pys_is_pys_src( 'https://x.pl/wp-content/plugins/pixelyoursite-pro/dist/scripts/public.js' ), true );
check( 'src innej wtyczki', cookiezen_pys_is_pys_src( 'https://x.pl/wp-content/plugins/woocommerce/assets/js/a.js' ), false );
check( 'src jQuery', cookiezen_pys_is_pys_src( 'https://x.pl/wp-includes/js/jquery/jquery.min.js' ), false );
check( 'src motywu o nazwie pixelyoursite', cookiezen_pys_is_pys_src( 'https://x.pl/wp-content/themes/pixelyoursite-x/a.js' ), false );
check( 'plik, nie katalog pixelyoursite', cookiezen_pys_is_pys_src( 'https://x.pl/wp-content/plugins/pixelyoursitefoo.js' ), false );
check( 'inna wtyczka z prefiksem pixel', cookiezen_pys_is_pys_src( 'https://x.pl/wp-content/plugins/pixelyourthing/a.js' ), false );

$held = cookiezen_pys_hold_tag( "<script type='text/javascript' src='https://x.pl/p.js' id='pys-js'></script>\n" );
check( 'tag zewnetrzny: type text/plain', strpos( $held, 'type="text/plain"' ) !== false, true );
check( 'tag zewnetrzny: znacznik kolejki', strpos( $held, 'data-cookiezen-pys' ) !== false, true );
check( 'tag zewnetrzny: bez dawnego type', strpos( $held, 'text/javascript' ), false );
check( 'tag zewnetrzny: src i id zachowane', strpos( $held, "src='https://x.pl/p.js' id='pys-js'" ) !== false, true );
check( 'tag zewnetrzny: bez kategorii banera', strpos( $held, 'data-cmp-category' ), false );
check( 'tag zewnetrzny: atrybuty wykluczajace optymalizatory', strpos( $held, 'nowprocket' ) !== false && strpos( $held, 'data-no-optimize="1"' ) !== false, true );

$held = cookiezen_pys_hold_tag( "<SCRIPT TYPE='text/javascript' data-type='x' src='https://x.pl/p.js'></SCRIPT>\n" );
check( 'wielkie litery: zamienione', strpos( $held, 'type="text/plain" data-cookiezen-pys' ) !== false, true );
check( 'wielkie litery: data-type zachowane', strpos( $held, "data-type='x'" ) !== false, true );
check( 'wielkie litery: stary TYPE usuniety', strpos( $held, 'TYPE=' ), false );
check( '<scriptfoo nie jest elementem script', cookiezen_pys_hold_tag( '<scriptfoo a="1">' ), '<scriptfoo a="1">' );

$held = cookiezen_pys_hold_tag( "<script id=\"a-js-before\">var a=1;</script>\n<script src=\"https://x.pl/a.js\" id=\"a-js\"></script>\n<script id=\"a-js-after\">a=2;</script>\n" );
check( 'inline before, skrypt i after: trzy elementy w kolejce', substr_count( $held, 'type="text/plain" data-cookiezen-pys' ), 3 );
check( 'inline zachowuje tresc', strpos( $held, '>var a=1;</script>' ) !== false, true );

$held = cookiezen_pys_hold_tag( "<script id=\"b-js-after\">var t=\"<script src='x'>\";</script>\n" );
check( '<script wewnatrz tekstu inline zostaje nietkniety', strpos( $held, "var t=\"<script src='x'>\";" ) !== false, true );
check( '<script wewnatrz tekstu inline: jeden element w kolejce', substr_count( $held, 'data-cookiezen-pys' ), 1 );

// --- Domkniecie zaleznosci ---
$registered = array(
    'jquery'      => (object) array( 'src' => '/wp-includes/js/jquery/jquery.min.js', 'deps' => array() ),
    'pys'         => (object) array( 'src' => '/wp-content/plugins/pixelyoursite/dist/scripts/public.js', 'deps' => array( 'jquery' ) ),
    'addon'       => (object) array( 'src' => '/wp-content/plugins/inna-wtyczka/addon.js', 'deps' => array( 'pys' ) ),
    'addon2'      => (object) array( 'src' => '/wp-content/themes/motyw/a.js', 'deps' => array( 'addon' ) ),
    'multi'       => (object) array( 'src' => '/wp-content/themes/motyw/c.js', 'deps' => array( 'jquery', 'pys' ) ),
    'theme'       => (object) array( 'src' => '/wp-content/themes/motyw/b.js', 'deps' => array( 'jquery' ) ),
    'cycle-a'     => (object) array( 'src' => '/a.js', 'deps' => array( 'cycle-b' ) ),
    'cycle-b'     => (object) array( 'src' => '/b.js', 'deps' => array( 'cycle-a' ) ),
);
check( 'uchwyt PYS wstrzymany', cookiezen_pys_handle_held( 'pys', $registered ), true );
check( 'obca wtyczka zalezna od pys wstrzymana', cookiezen_pys_handle_held( 'addon', $registered ), true );
check( 'zaleznosc posrednia od pys wstrzymana', cookiezen_pys_handle_held( 'addon2', $registered ), true );
check( 'pys jako druga zaleznosc: wstrzymany', cookiezen_pys_handle_held( 'multi', $registered ), true );
check( 'nieznany uchwyt: nie wstrzymany', cookiezen_pys_handle_held( 'brak', $registered ), false );
check( 'jQuery nie jest wstrzymane', cookiezen_pys_handle_held( 'jquery', $registered ), false );
check( 'motyw zalezny tylko od jQuery nie jest wstrzymany', cookiezen_pys_handle_held( 'theme', $registered ), false );
check( 'cykl zaleznosci konczy sie bez wstrzymania', cookiezen_pys_handle_held( 'cycle-a', $registered ), false );

// --- Filtry bramki ---
reset_state();
check( 'CAPI bez lustra: wylaczone', cookiezen_pys_filter_disable_server_event( false ), true );
reset_state( 'site_abc', '1' );
check( 'CAPI z lustrem: decyzja PYS', cookiezen_pys_filter_disable_server_event( false ), false );
reset_state();
$GLOBALS['cz_test_filters']['cookiezen_pys_integration'] = false;
check( 'CAPI przy wylaczniku: decyzja PYS', cookiezen_pys_filter_disable_server_event( false ), false );

reset_state();
check( 'zgoda PYS bez lustra: brak', cookiezen_pys_filter_check_consent( true, 'facebook' ), false );
reset_state( 'site_abc', '1' );
check( 'zgoda PYS z lustrem: decyzja PYS', cookiezen_pys_filter_check_consent( true, 'facebook' ), true );
check( 'zgoda PYS z lustrem, PYS odmawia: odmowa', cookiezen_pys_filter_check_consent( false, 'facebook' ), false );

reset_state();
check( 'tryb ograniczony bez lustra: wylaczony', cookiezen_pys_filter_restricted_mode( true, 'openai' ), false );
reset_state( 'site_abc', '1' );
check( 'tryb ograniczony z lustrem: decyzja PYS', cookiezen_pys_filter_restricted_mode( true, 'openai' ), true );

reset_state();
check( 'sesja PYS bez lustra: pominieta', cookiezen_pys_filter_skip_session( false ), true );
reset_state( 'site_abc', '1' );
check( 'sesja PYS z lustrem: decyzja PYS', cookiezen_pys_filter_skip_session( false ), false );

reset_state();
$r = cookiezen_pys_filter_rest_pre_dispatch( null, null, new Cz_Fake_Request( '/pys-facebook/v1/event' ) );
check( 'REST zdarzenia bez lustra: 403', ( $r instanceof WP_Error ) ? $r->data['status'] : null, 403 );
$other = new WP_Error( 'inna_wtyczka', '', array( 'status' => 401 ) );
$r = cookiezen_pys_filter_rest_pre_dispatch( $other, null, new Cz_Fake_Request( '/pys-facebook/v1/event' ) );
check( 'REST: wynik innej wtyczki zostaje nietkniety', $r === $other, true );
$r = cookiezen_pys_filter_rest_pre_dispatch( null, null, new Cz_Fake_Request( '/pys/v1/dynamic-options' ) );
check( 'REST innej trasy PYS: bez zmian', $r, null );
reset_state( 'site_abc', '1' );
$r = cookiezen_pys_filter_rest_pre_dispatch( null, null, new Cz_Fake_Request( '/pys-facebook/v1/event' ) );
check( 'REST zdarzenia z lustrem: bez zmian', $r, null );

// --- Kontekst renderu: wariant zapasowy (PYS bez pys_html_render_context) ---
reset_state();
$GLOBALS['cz_doing'] = array( 'wp_enqueue_scripts' );
check( 'zapasowy: wp_enqueue_scripts to render', cookiezen_pys_outside_render(), false );
$GLOBALS['cz_doing'] = array( 'wp_head' );
check( 'zapasowy: wp_head to render', cookiezen_pys_outside_render(), false );
check( 'zapasowy: ciasteczka PYS przy renderze bez zmian', cookiezen_pys_filter_disable_cookie( false ), false );
$GLOBALS['cz_doing'] = array( 'wp_footer' );
check( 'zapasowy: wp_footer to render', cookiezen_pys_outside_render(), false );
$GLOBALS['cz_doing'] = array( 'init' );
check( 'zapasowy: init jest poza renderem', cookiezen_pys_outside_render(), true );
check( 'zapasowy: ciasteczka PYS na init bez lustra: wylaczone', cookiezen_pys_filter_disable_cookie( false ), true );
$GLOBALS['cz_doing'] = array();
$_SERVER['REQUEST_METHOD'] = 'POST';
check( 'zapasowy: POST na strone frontu jest poza renderem', cookiezen_pys_outside_render(), true );

// --- Kontekst renderu z funkcji PYS ---
eval( 'namespace PixelYourSite; function pys_html_render_context( $set = null ) { return ! empty( $GLOBALS["cz_render"] ); }' );
reset_state();
$GLOBALS['cz_render'] = true;
check( 'PYS: kontekst renderu', cookiezen_pys_outside_render(), false );
$GLOBALS['cz_render'] = false;
check( 'PYS: poza kontekstem renderu', cookiezen_pys_outside_render(), true );
reset_state( 'site_abc', '1' );
check( 'PYS: poza renderem z lustrem: decyzja PYS', cookiezen_pys_filter_disable_cookie( false ), false );

// --- Rejestracja hookow: nazwa, priorytet i liczba argumentow ---
function hooked( $hooks, $hook, $callback, $priority, $args = 1 ) {
    foreach ( isset( $hooks[ $hook ] ) ? $hooks[ $hook ] : array() as $entry ) {
        if ( $entry[0] === $callback && $entry[1] === $priority && $entry[2] === $args ) {
            return true;
        }
    }
    return false;
}
$expected_hooks = array(
    array( 'pys_disable_server_event_filter', 'cookiezen_pys_filter_disable_server_event', 99, 1 ),
    array( 'pys_check_consent_by_gdpr', 'cookiezen_pys_filter_check_consent', 99, 2 ),
    array( 'pys_pixel_restricted_mode', 'cookiezen_pys_filter_restricted_mode', 99, 2 ),
    array( 'pys_skip_session', 'cookiezen_pys_filter_skip_session', 99, 1 ),
    array( 'pys_disable_all_cookie', 'cookiezen_pys_filter_disable_cookie', 99, 1 ),
    array( 'pys_disable_advanced_form_data_cookie', 'cookiezen_pys_filter_disable_cookie', 99, 1 ),
    array( 'pys_disable_externalID_by_gdpr', 'cookiezen_pys_filter_disable_cookie', 99, 1 ),
    array( 'rest_pre_dispatch', 'cookiezen_pys_filter_rest_pre_dispatch', 10, 3 ),
    array( 'admin_init', 'cookiezen_pys_ajax_guard', 0, 1 ),
    array( 'template_redirect', 'cookiezen_pys_remove_noscript', 2, 1 ),
    array( 'wp_loaded', 'cookiezen_pys_unhook_session_orders', 10, 1 ),
    array( 'script_loader_tag', 'cookiezen_pys_script_loader_tag', 10, 3 ),
    array( 'init', 'cookiezen_maybe_purge_cache_after_update', 10, 1 ),
);
foreach ( $expected_hooks as $h ) {
    check( "hook {$h[0]} -> {$h[1]} ({$h[2]})", hooked( $registered_hooks, $h[0], $h[1], $h[2], $h[3] ), true );
}

// --- JS lustra wykonany w Node ---
/*
 * Regula lustra zyje w JS wklejanym do strony, a test kontraktu w cmp-app ma
 * wlasna kopie stanu poczatkowego. Ten blok wykonuje prawdziwy JS z
 * `cookiezen_pys_mirror_js()` na atrapie okna, wiec rozjazd reguly jest
 * czerwony tutaj. Bez `node` w PATH blok jest pomijany z komunikatem.
 */
$node = trim( (string) shell_exec( 'command -v node 2>/dev/null' ) );
if ( '' === $node ) {
    echo "POMINIETE JS lustra: brak node w PATH\n";
} else {
    $runner = <<<'JS'
const mirror = process.argv[1];
const scenarios = JSON.parse(process.argv[2]);
const out = {};
for (const [name, sc] of Object.entries(scenarios)) {
  const listeners = [];
  let jar = {};
  const deleted = [];
  for (const c of sc.cookies || []) jar[c] = { val: 'x', attrs: [] };
  const document = {
    get cookie() {
      return Object.keys(jar).map((k) => k + '=' + jar[k].val).join('; ');
    },
    set cookie(v) {
      const [pair, ...attrs] = v.split(';');
      const [k, val] = pair.split('=');
      if (attrs.some((a) => a.trim() === 'max-age=0')) {
        delete jar[k];
        if (k !== 'cmp_pys_site_abc') deleted.push([k, (attrs.find((a) => a.trim().startsWith('domain=')) || '').trim()].join('|'));
      } else jar[k] = { val, attrs: attrs.map((a) => a.trim()) };
    },
  };
  const session = { pys_session_entry_referrer: 'https://ref.example/' };
  const window = {
    __CMP_LOADER: sc.handoff,
    addEventListener: (t, f) => { if (t === 'cookiezen:consent') listeners.push(f); },
    sessionStorage: { removeItem: (key) => { delete session[key]; } },
  };
  if (sc.prevFilter) window.pys_disable_event_filter = (n) => n === sc.prevFilter;
  const location = { protocol: sc.https ? 'https:' : 'http:', hostname: 'www.example.com' };
  new Function('window', 'document', 'location', mirror)(window, document, location);
  const blocked = () => ['PageView', 'Lead'].map((e) => window.pys_disable_event_filter(e, 'facebook'));
  const states = [jar.cmp_pys_site_abc ? jar.cmp_pys_site_abc.val : null];
  const attrs = [jar.cmp_pys_site_abc ? jar.cmp_pys_site_abc.attrs : null];
  const filters = [blocked()];
  const deletions = [deleted.splice(0).sort()];
  for (const detail of sc.events || []) {
    if (sc.mutateMeta !== undefined && window.__CMP_LOADER && window.__CMP_LOADER.meta) window.__CMP_LOADER.meta.granted = sc.mutateMeta;
    listeners.forEach((f) => f({ detail }));
    states.push(jar.cmp_pys_site_abc ? jar.cmp_pys_site_abc.val : null);
    attrs.push(jar.cmp_pys_site_abc ? jar.cmp_pys_site_abc.attrs : null);
    filters.push(blocked());
    deletions.push(deleted.splice(0).sort());
  }
  out[name] = { states, attrs, filters, deletions, left: Object.keys(jar).sort(), session: Object.keys(session), start: window.__cookiezenPysStart };
}
process.stdout.write(JSON.stringify(out));
JS;
    $base = array( 'version' => 1, 'siteKey' => 'site_abc', 'hasConsent' => false, 'existingCategories' => null, 'region' => array( 'pending' => false, 'exempt' => false ) );
    $grant = array( 'categories' => array( 'marketing' => true ), 'finalized' => true );
    $deny = array( 'categories' => array( 'marketing' => false ), 'finalized' => true );
    $exempt = array( 'categories' => array( 'marketing' => true ), 'finalized' => false );
    $neutral = array( 'categories' => array( 'marketing' => false ), 'finalized' => false );
    $pys_cookies = array( 'pys_first_visit', 'pysTrafficSource', 'last_pys_landing_page', 'pbid', '_fbp', '_fbc', '__oppref', '_ga', 'mypys', 'pbid_x', 'wp_woocommerce_session_x' );
    $scenarios = array(
        'brak handoffu'              => array( 'handoff' => null, 'events' => array( $grant ) ),
        'cudzy klucz'                => array( 'handoff' => array_merge( $base, array( 'siteKey' => 'site_inny', 'meta' => array( 'granted' => true ) ) ), 'events' => array( $grant ) ),
        'meta false, zgoda, odmowa'  => array( 'handoff' => array_merge( $base, array( 'meta' => array( 'granted' => false ) ) ), 'events' => array( $grant, $deny ), 'https' => true ),
        'meta true na starcie'       => array( 'handoff' => array_merge( $base, array( 'meta' => array( 'granted' => true ) ) ), 'events' => array( $neutral ) ),
        'zdarzenie wygrywa z meta'   => array( 'handoff' => array_merge( $base, array( 'meta' => array( 'granted' => false ) ) ), 'events' => array( $deny, $grant ), 'mutateMeta' => false ),
        'bez meta: zapisana zgoda'   => array( 'handoff' => array_merge( $base, array( 'hasConsent' => true, 'existingCategories' => array( 'marketing' => true ) ) ) ),
        'bez meta: region, potem nic' => array( 'handoff' => array_merge( $base, array( 'region' => array( 'pending' => false, 'exempt' => true ) ) ), 'events' => array( $neutral ) ),
        'bez meta: zwolnienie z banera' => array( 'handoff' => $base, 'events' => array( $exempt ) ),
        'wycofanie sprzata PYS'      => array( 'handoff' => array_merge( $base, array( 'meta' => array( 'granted' => true ) ) ), 'events' => array( $deny, $grant ), 'cookies' => $pys_cookies ),
        'start bez zgody sprzata'    => array( 'handoff' => array_merge( $base, array( 'meta' => array( 'granted' => false ) ) ), 'cookies' => $pys_cookies ),
        'geo w drodze, potem zwolnienie' => array( 'handoff' => array_merge( $base, array( 'region' => array( 'pending' => true, 'exempt' => false ), 'meta' => array( 'granted' => false ) ) ), 'events' => array( $exempt ), 'cookies' => $pys_cookies ),
        'geo w drodze, potem odmowa' => array( 'handoff' => array_merge( $base, array( 'region' => array( 'pending' => true, 'exempt' => false ), 'meta' => array( 'granted' => false ) ) ), 'events' => array( $deny ), 'cookies' => $pys_cookies ),
        'cudzy hak pytany przy zgodzie' => array( 'handoff' => array_merge( $base, array( 'meta' => array( 'granted' => true ) ) ), 'events' => array( $deny ), 'prevFilter' => 'Lead' ),
    );
    $result = json_decode( (string) shell_exec( escapeshellarg( $node ) . ' -e ' . escapeshellarg( $runner ) . ' ' . escapeshellarg( cookiezen_pys_mirror_js( 'site_abc' ) ) . ' ' . escapeshellarg( wp_json_encode( $scenarios ) ) ), true );
    check( 'JS: brak handoffu, zdarzenie zgody ignorowane', $result['brak handoffu']['states'], array( null, null ) );
    check( 'JS: cudzy klucz, zdarzenie zgody ignorowane', $result['cudzy klucz']['states'], array( null, null ) );
    check( 'JS: meta false, potem zgoda i odmowa', $result['meta false, zgoda, odmowa']['states'], array( null, '1', null ) );
    check( 'JS: meta true na starcie, zdarzenie niesfinalizowane bez marketingu nic nie zmienia', $result['meta true na starcie']['states'], array( '1', '1' ) );
    check( 'JS: zmiany wylacznie ze zdarzenia, nie z meta.granted', $result['zdarzenie wygrywa z meta']['states'], array( null, null, '1' ) );
    check( 'JS: bez meta, zapisana zgoda z marketingiem', $result['bez meta: zapisana zgoda']['states'], array( '1' ) );
    check( 'JS: bez meta, zwolnienie regionalne', $result['bez meta: region, potem nic']['states'], array( '1', '1' ) );
    check( 'JS: zwolnienie z banera (marketing, niesfinalizowane)', $result['bez meta: zwolnienie z banera']['states'], array( null, '1' ) );
    check( 'JS: znacznik startu dla bootstrapu', array( $result['meta true na starcie']['start'], $result['brak handoffu']['start'] ), array( true, false ) );
    check( 'JS: HTTPS daje SameSite=None i Secure', $result['meta false, zgoda, odmowa']['attrs'][1], array( 'path=/', 'SameSite=None', 'Secure' ) );
    check( 'JS: HTTP daje SameSite=Lax', $result['meta true na starcie']['attrs'][0], array( 'path=/', 'SameSite=Lax' ) );
    check( 'JS: hak PYS blokuje bez zgody, przepuszcza po zgodzie i blokuje po odmowie', $result['meta false, zgoda, odmowa']['filters'], array( array( true, true ), array( false, false ), array( true, true ) ) );
    check( 'JS: hak PYS blokuje bez handoffu', $result['brak handoffu']['filters'], array( array( true, true ), array( true, true ) ) );
    $pys_deleted = array();
    foreach ( array( 'pys_first_visit', 'pysTrafficSource', 'last_pys_landing_page', 'pbid', '_fbp', '_fbc', '__oppref' ) as $c ) {
        foreach ( array( '', 'domain=.example.com', 'domain=.www.example.com' ) as $dom ) {
            $pys_deleted[] = $c . '|' . $dom;
        }
    }
    sort( $pys_deleted );
    $wycofanie = $result['wycofanie sprzata PYS'];
    check( 'JS: przy zgodzie na starcie nic nie jest usuwane', $wycofanie['deletions'][0], array() );
    check( 'JS: wycofanie usuwa identyfikatory PYS na hoscie i domenach nadrzednych', $wycofanie['deletions'][1], $pys_deleted );
    check( 'JS: ponowna zgoda niczego nie usuwa', $wycofanie['deletions'][2], array() );
    check( 'JS: cudze ciasteczka zostaja', $wycofanie['left'], array( '_ga', 'cmp_pys_site_abc', 'mypys', 'pbid_x', 'wp_woocommerce_session_x' ) );
    check( 'JS: wycofanie usuwa referrer sesji PYS z sessionStorage', $wycofanie['session'], array() );
    check( 'JS: hak po wycofaniu i ponownej zgodzie', $wycofanie['filters'], array( array( false, false ), array( true, true ), array( false, false ) ) );
    check( 'JS: start bez zgody usuwa pozostalosci PYS', $result['start bez zgody sprzata']['deletions'][0], $pys_deleted );
    check( 'JS: geo w drodze, start niczego nie usuwa', $result['geo w drodze, potem zwolnienie']['deletions'], array( array(), array() ) );
    check( 'JS: geo w drodze, zwolnienie wlacza lustro i hak', array( $result['geo w drodze, potem zwolnienie']['states'], $result['geo w drodze, potem zwolnienie']['filters'][1] ), array( array( null, '1' ), array( false, false ) ) );
    check( 'JS: geo w drodze, hak blokuje do rozstrzygniecia', $result['geo w drodze, potem zwolnienie']['filters'][0], array( true, true ) );
    check( 'JS: geo w drodze, odmowa usuwa identyfikatory', $result['geo w drodze, potem odmowa']['deletions'], array( array(), $pys_deleted ) );
    check( 'JS: cudzy hak pytany przy zgodzie, pomijany bez zgody', $result['cudzy hak pytany przy zgodzie']['filters'], array( array( false, true ), array( true, true ) ) );
}

echo $failures ? "\n{$failures} bledow\n" : "\nwszystko zielone\n";
exit( $failures ? 1 : 0 );
