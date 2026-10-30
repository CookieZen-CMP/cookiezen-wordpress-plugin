# Integracje wtyczki z innymi wtyczkami WordPress

Dokument opisuje, dlaczego integracje wyglądają tak, a nie inaczej: decyzje, odrzucone warianty, znane ograniczenia i sposób weryfikacji.
Mechanikę opisują docblocki w `cookiezen.php`, więc tutaj nie ma jej kopii, tylko odsyłacze do funkcji.
Każda integracja z cudzą wtyczką, która wysyła dane, dostaje własną sekcję w układzie sekcji PixelYourSite.

## Zasady wspólne

**HTML strony jest identyczny dla każdego odwiedzającego.**
Page cache (WP Rocket, LiteSpeed, Cloudflare) zamraża stronę zbudowaną dla pierwszej osoby i podaje ją wszystkim, więc decyzja zależna od zgody nie może zapadać przy renderze HTML.
Zapada w przeglądarce albo w żądaniu poza renderem (REST, AJAX, `admin-post.php`); filtr zwracający zawsze tę samą wartość jest bezpieczny.

**Punkty zaczepienia dostawcy: najpierw publiczne.**
Filtr albo hak JS opisany przez dostawcę ma pierwszeństwo przed nazwą z jego wnętrza (uchwyt skryptu, metoda, trasa, akcja AJAX).
Nazwa z wnętrza wymaga sprawdzenia w teście kontraktu na prawdziwej paczce dostawcy, bo jej zmiana nie daje błędu, tylko po cichu wyłącza jedną warstwę ochrony.
Wzorem jest `tests/pys-contract.php`.

**Stan zgody w przeglądarce tylko z pól przypiętych w cmp-app.**
Wtyczka czyta handoff loadera `window.__CMP_LOADER` i zdarzenie banera `cookiezen:consent`, a pola, z których korzysta, przypinają testy w repo cmp-app: `tests/unit/wordpress-plugin-handoff-contract.test.ts` i `tests/unit/banner-consent-event-contract.test.ts`.
Nowe pole czytane przez wtyczkę wymaga dopisania go do tych testów w tej samej parze zmian.
Zmiana znaczenia istniejącego pola po stronie cmp-app jest zabroniona, bo wydanie wtyczki nie naprawia kopii zainstalowanych u klientów; nowe znaczenie dostaje nowe pole (reguła w cmp-app: `.claude/rules/runtime.md`).

**Fail open jest regułą, fail closed wyjątkiem z decyzją.**
Algorytm blokowania CookieZen przepuszcza to, czego nie zna.
Integracja z PixelYourSite jest jedynym świadomym wyjątkiem: bez lustra decyzji serwer nie wysyła niczego do Meta.
Kolejny taki wyjątek wymaga decyzji właściciela i wpisu w repo cmp-app, w `docs/engineering/BLOCKING_ALGORITHM.md`, sekcja „Stan produkcyjny”.

**Każda integracja z bramką dla cudzej wtyczki ma wyłącznik filtrem dla właściciela strony.**
Wyłącznik jest domyślnie włączony, a zdalnego wyłącznika z panelu nie ma.

**Zmiana HTML wstawianego przez wtyczkę podnosi `COOKIEZEN_MARKUP_VERSION`.**
Podniesienie jednorazowo czyści page cache u wszystkich klientów, którzy zaktualizują wtyczkę, więc tylko wtedy, gdy HTML naprawdę się zmienia.

## PixelYourSite (od 1.0.5)

### Problem

PixelYourSite (PYS) przed decyzją i po odmowie zakłada w przeglądarce własne identyfikatory (`_fbp`, `pbid`, `pys*`, `last_pys*`).
Z serwera wysyła zdarzenia do Meta Conversions API (CAPI) przez własne endpointy REST i AJAX, które nie sprawdzają zgody.
Natywnie zna tylko kilka CMP (ConsentMagic, Cookiebot, CookieYes, Real Cookie Banner, Cookie Notice) i nie czyta WP Consent API.
Natywny `fbq('consent','revoke')` z loadera CookieZen zatrzymuje wyłącznie Pixel w przeglądarce, więc ścieżka serwerowa i identyfikatory PYS przed wersją 1.0.5 nie miały żadnej ochrony.

### Mechanizm

Integracja ma trzy części, opisane w docblocku przy sekcji „Integracja z PixelYourSite” w `cookiezen.php` (tam lustro i serwer są jedną częścią):

| Warstwa | Co robi | Funkcje |
|---|---|---|
| Przeglądarka | skrypty PYS drukują się jako nieaktywne `text/plain`, a jeden bootstrap z `data-cmp-category="marketing"` ładuje je po kolei, gdy baner dostanie zgodę na marketing | `cookiezen_pys_script_loader_tag`, `cookiezen_pys_bootstrap_js` |
| Lustro decyzji | skrypt zaraz za loaderem zapisuje sesyjne ciasteczko `cmp_pys_<site_key>` z handoffu i ze zdarzenia banera, steruje hakiem `window.pys_disable_event_filter` i bez zgody usuwa ciasteczka PYS i Meta | `cookiezen_pys_mirror_js` |
| Serwer | filtry PYS, odpowiedź 403 na endpointy zdarzeń REST i AJAX, odpięcie haków zamówienia WooCommerce, wszystko rozstrzygane wyłącznie lustrem; do tego bezwarunkowe zdjęcie obrazka `<noscript>` | `cookiezen_pys_blocks` i funkcje pod komentarzem „Bramka PHP” |

Integracja działa przy ustawionym kluczu strony, aktywnym PYS i niewyłączonym filtrze `cookiezen_pys_integration`.
Blokada w przeglądarce działa dodatkowo tylko wtedy, gdy loader wydrukował się w tym samym żądaniu.

### Decyzje

| Decyzja | Powód |
|---|---|
| Integracja we wtyczce, nie w loaderze | Zero bajtów w synchronicznym loaderze wszystkich stron i brak fali regeneracji skryptów w cmp-app. |
| Cały PYS traktowany jako narzędzie marketingowe | Blokada całego skryptu korzysta z produkcyjnego mechanizmu ręcznego tagowania i nie zależy od wnętrza JS PYS (`window.pys`, gettery, `loadPixel`). Koszt: Google Analytics ładowane przez PYS czeka na zgodę marketingową, a nie analityczną. |
| Bootstrap zamiast tagu na samym `pys-js` | Klon zewnętrznego skryptu wstawiony przez baner wykonuje się asynchronicznie, a `public.js` od pierwszej linii potrzebuje `jQuery` i `pysOptions`, które optymalizatory potrafią opóźnić do pierwszej interakcji. |
| PHP czyta lustro, nie ciasteczko zgody | PHP nie zależy od formatu zapisu zgody, a reset wersji polityki, bramkę regionalną i zgodę trzymaną tylko w localStorage (Safari) rozstrzyga loader. Lustro jest sesyjne i odtwarzane przy każdej odsłonie, więc nie utrwala zwolnienia regionalnego jako zgody. |
| Lustro zmienia się wyłącznie ze zdarzenia `cookiezen:consent` | Baner zapisuje `meta.granted` do bieżącego handoffu, który przy dwóch kluczach na stronie należy do innego przebiegu loadera. |
| `SameSite=None` lustra na HTTPS | Powrót z bramki płatności przychodzi międzywitrynowym POST-em, z którym `Lax` nie dochodzi, a Purchase z PHP zostałby zablokowany osobie ze zgodą. |
| Bez lustra serwer jest zamknięty (fail closed) | Endpointy zdarzeń PYS nie sprawdzają zgody, a CAPI OpenAI nie ma filtra wyłączającego. Skutek: strona, która usunie nasz loader, traci CAPI, dopóki właściciel nie wyłączy integracji filtrem. |
| Druga linia na serwerze obok filtra `pys_disable_server_event_filter` | 403 na trasach `pys-*/v1/event` i strażnik akcji AJAX na `admin_init` obejmują piksele bez własnego filtra oraz dodatki i Pro, które trzymają tę konwencję nazw. |
| Filtry ciasteczek PYS tylko poza renderem HTML | Te filtry trafiają też do `pysOptions`, a wartość z renderu zamarzłaby w page cache dla wszystkich, także po zgodzie. |
| Obrazek `<noscript>` zdejmowany bezwarunkowo | Bez JS baner nie działa i zgody nie da się udzielić, więc zdjęcie jest poprawne dla każdego odwiedzającego i bezpieczne dla page cache. |
| Atrybuty wykluczające optymalizatory na loaderze dla każdej strony z wtyczką, także bez PYS | Loader opóźniony przez WP Rocket, LiteSpeed albo Rocket Loader zostawia PHP bez lustra, a strony bez PYS i tak potrzebują synchronicznego loadera dla Consent Mode. Zmienia to zachowanie optymalizatorów na całej flocie WordPress. |
| Czyszczenie page cache przez porównanie zapisanej `COOKIEZEN_MARKUP_VERSION`, nie hakiem aktualizacji | Hak `upgrader_process_complete` wykonuje jeszcze stary kod wtyczki. Bez czyszczenia stary HTML nie ma blokady ani lustra, a nowy PHP blokowałby CAPI wszystkim do wygaśnięcia cache. |
| Lustro nie sprawdza pola `version` handoffu | Traktowanie nieznanej wersji jako braku zgody odcinałoby CAPI wszystkim zainstalowanym kopiom wtyczki przy każdym podniesieniu wersji handoffu w cmp-app. |
| Testy PHP jako zwykłe skrypty `php` | Repo nie ma PHPUnit ani Composera, a logika bramki ma kilka ścieżek wyjścia. |
| Uprząż z prawdziwym WordPressem wersjonowana w cmp-app, poza CI | Buduje loader i baner tymi samymi funkcjami co testy przeglądarkowe cmp-app; poza CI, bo WordPress Playground pobiera WordPress i wtyczki z sieci. |

### Odrzucone i odłożone

| Wariant | Stan | Powód |
|---|---|---|
| Przechwytywanie wnętrza JS PYS (`window.pys`, gettery, `loadPixel`) | odrzucone | Zależność od kodu, który PYS zmienia bez zapowiedzi; blokada całego skryptu zamyka te same dziury bez tej zależności. |
| Podstawienie `window.pys_event_data_filter` dla PYS przed 11.4.3 | odrzucone | Starsze PYS sprawdzają istnienie tej funkcji, a wołają `pys_disable_event_filter`, więc podstawienie uruchomiłoby hak także tam; nazwa zapowiada jednak filtr danych zdarzenia, a nie przełącznik. |
| Poszerzenie kontekstu renderu o całe `wp_head` | odrzucone | Ryzyko zapisu ciasteczek z PHP bez zgody za zysk wyłącznie w pomiarze. |
| Filtr `pys_disable_by_gdpr` włączany na czas druku stopki zamiast zdejmowania obrazka `<noscript>` | odrzucone | Ten sam filtr zasila `pysOptions.gdpr` i `isGdprPluginEnabled()` w PYS, więc wartość z tego okna może trafić do HTML i zamarznąć w page cache; zdjęcie callbacku nie dotyka niczego poza obrazkiem. |
| Przeładowanie strony po wycofaniu zgody, gdy PYS był uruchomiony | odrzucone | Hak `pys_disable_event_filter` wycisza PYS od razu od wersji 11.4.3, a w starszych do końca odsłony zostają revoke Meta z loadera i bramka CAPI. |
| Wykrywanie w PHP, że strona nie drukuje loadera | odrzucone | W żądaniach REST i AJAX nie da się tego wiedzieć; zamiast tego wyłącznik filtrem. |
| Bramka serwerowa w module PrestaShop | odłożone | Decyzja zapisana w repo cmp-app, `docs/engineering/LOADER_BANNER_ARCHITECTURE.md`, tabela „Decyzje odrzucone i odłożone”. |

### Znane ograniczenia

- Strony z loaderem wklejonym ręcznie albo przez GTM nie mają integracji; rozwiązaniem jest przejście na wtyczkę.
- PYS przed 11.4.3: po wycofaniu zgody w trakcie odsłony PYS działa do jej końca, Pixel zatrzymuje revoke Meta z loadera, CAPI bramka PHP, a PYS milknie od następnej odsłony.
- Skrypt `pys-pbid-prefetch` w `<head>` odpytuje serwer przed zgodą; bez lustra PHP nie zakłada `pbid`, a bootstrap odrzuca tę odpowiedź.
- Sonda domeny PYS zakłada przed zgodą ulotne ciasteczko `pys_dt`.
- `pys_woo_orders` ma flagę HttpOnly, więc po wycofaniu zgody zostaje w przeglądarce; bez lustra nie powstaje, bo haki zamówienia są odpięte.
- Przy wyłączonych w PYS opcjach „static events via AJAX” pole `ajaxFire` w HTML pochodzi od osoby, dla której zbudowano page cache, a pierwsze żądanie nowej sesji nie ma jeszcze lustra; oba przypadki gubią zdarzenia CAPI, nie są przeciekiem.
- Przez jedno żądanie lustro może mieć nieaktualne `1` (po przywróceniu sesji przeglądarki, resecie wersji polityki w trakcie sesji i wygaśnięciu zgody), a skrypt lustra poprawia stan przy tej samej odsłonie.
- Zakupy z webhooków płatności w PYS Pro nie niosą ciasteczek przeglądarki, więc bramka je blokuje (utrata danych, tylko Pro).
- Hosting z Varnishem, który obcina nieznane ciasteczka w żądaniach, zostawi PHP bez lustra, a bramka zablokuje CAPI wszystkim.
- Page cache serwerowego bez wtyczki (Cloudflare APO, Varnish hostingu) wtyczka nie czyści; `readme.txt` każe zrobić to ręcznie po aktualizacji.
- PYS Pro: blokada w przeglądarce działa tak samo (ten sam uchwyt `pys`), ale filtrów PHP Pro nie potwierdziliśmy w kodzie, bo go nie mamy.

### Weryfikacja

- `php tests/pys-gate.php` sprawdza logikę bramki na atrapach WordPressa, przy każdej zmianie w `cookiezen.php`.
- `php tests/pys-contract.php <rozpakowany pixelyoursite>` sprawdza nazwy z wnętrza PYS na prawdziwej paczce, przed wydaniem i po każdym wydaniu PYS.
- Uprząż `scripts/wordpress-pys-harness/` w repo cmp-app prowadzi prawdziwy Chrome przez scenariusze na WordPress Playground; przebieg i kontrola pozytywna są w nagłówku `run.ts`.
- Canary na żywych sklepach według `PUBLISHING.md`, sekcja „Canary i wycofanie”.

Sprawdzone przed wydaniem 1.0.5 (2026-10-30): kontrakt zielony na PYS 11.4.4; uprząż zielona na PYS 11.4.4 (PHP 7.4 i 8.x, także z emulacją opóźniania JS) i 11.1.2; canary 1.0.5-rc.2 zgodny na PYS 11.1.2, 11.4.2.1 i 11.4.4 z WP Rocket, LiteSpeed Cache i Cloudflare.

## WP Consent API (od 1.0.3)

Wtyczka zwraca `optin` z filtra `wp_get_consent_type`, bo bez niego `wp_has_consent()` w PHP zwraca prawdę dla każdej kategorii.
Część przeglądarkową robi runtime cmp-app: loader ustawia `window.wp_consent_type`, a baner woła `wp_set_consent` przy każdej zmianie zgody (repo cmp-app, `docs/engineering/ARCHITECTURE.md`, sekcja 7.5).

Ograniczenia:
- przy pierwszym żądaniu, zanim JS zapisze ciasteczka `wp_consent_*`, PHP zwraca brak zgody dla każdej kategorii, także `functional`; to zachowanie samego WP Consent API w trybie opt-in;
- WooCommerce, Jetpack i Site Kit włączają swoją obsługę WP Consent API tylko wtedy, gdy na stronie jest wtyczka WP Consent API;
- wtyczka, która czyta `wp_has_consent()` w PHP przy renderze strony, przy page cache dostaje wynik osoby, dla której zbudowano cache; tego nie mierzyliśmy na żywej stronie.

## Rozpoznane, jeszcze nieobjęte

Rozpoznanie w kodzie dostawców z 2026-10-30, na wersjach z wordpress.org podanych w tabeli.
Pozycja trafia do własnej sekcji wyżej dopiero razem z implementacją.

| Wtyczka | Problem | Punkt zaczepienia u dostawcy |
|---|---|---|
| GTM4WP 2.0.6 | Opcjonalny własny Consent Mode; ustawiony na `granted` wysyła `consent default` po loaderze i nadpisuje jego `denied` (zmierzone na żywej stronie klienta 2026-10-30) | filtr `gtm4wp_consent_mode_default_enabled`, stały, więc bezpieczny dla page cache |
| Meta for WooCommerce 3.7.8 | CAPI z serwera i `_fbp` zakładane z PHP i z JS | filtr `facebook_signals_held`, ciasteczko `wc_facebook_signals_state`, `FacebookSignals.release()` |
| WooCommerce 11.2.1, atrybucja zamówień | ciasteczka sesyjne `sbjs_*` przed decyzją, bez wysyłki do firm trzecich | filtr `wc_order_attribution_allow_tracking` i JS `setOrderTracking` |
| Jetpack 16.3.1, WooCommerce Analytics | bez wtyczki WP Consent API uznaje zgodę za daną, zdarzenia idą do `pixel.wp.com` | brak własnego; tylko przez WP Consent API |
| TikTok for Business 1.4.3 | Events API z serwera bez obsługi zgody | brak filtrów, wyłącznie odpinanie wewnętrznych callbacków |
| Site Kit 1.190.0 | własny Consent Mode domyślnie wyłączony, po włączeniu ustawia `denied` | nic do zrobienia |
