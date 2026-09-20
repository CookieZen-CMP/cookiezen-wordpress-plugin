# Publikacja i wydawanie wtyczki CookieZen w katalogu WordPress.org

## Model repozytoriów

| Miejsce | Rola | Co zawiera |
|---|---|---|
| To repo (`CookieZen-CMP/cookiezen-wordpress-plugin`) | źródło prawdy kodu wtyczki | `cookiezen.php`, `uninstall.php`, `readme.txt`, `LICENSE.txt`, `languages/`, `.wporg-assets/`, `scripts/`, `docs/` |
| SVN WordPress.org (`plugins.svn.wordpress.org/cookiezen`) | dystrybucja i automatyczna aktualizacja u klientów | `trunk/`, `tags/X.Y.Z/`, `assets/` |
| Monorepo `cmp-app` | panel klienta, zakładka „Integracje" | `public/cookiezen.zip` |

Kod wtyczki zmienia się wyłącznie tutaj.
Stąd powstaje ZIP, który idzie do SVN oraz jako kopia do panelu klienta.

`docs/readme-pl.txt` to polski wzorzec redakcyjny treści karty katalogowej, a nie tłumaczenie do wgrania gdziekolwiek.
`readme.txt` musi być po angielsku, a polską wersję strony wtyczki daje translate.wordpress.org.
Wzorzec istnieje z tego samego powodu co `appstore/opis.md` dla Shopera: treść uzgadnia się po polsku, a panel po drugiej stronie nie ma historii zmian.

## Konto WordPress.org

Konto `cookiezen` istnieje i jest zatwierdzone jako konto marki z rolą Spectator.
Ta sekcja opisuje konsekwencje tego stanu i to, co trzeba wiedzieć przy zakładaniu kolejnego konta.

**Pułapka: login zbieżny z domeną adresu uruchamia ręczną moderację.**
Konto zakładane z firmowego adresu pod nazwą marki zostaje oznaczone jako konto brandowe, a mail aktywacyjny nie przychodzi, dopóki człowiek nie przejrzy zgłoszenia.
To nie jest awaria dostarczalności i zakładanie konta drugi raz niczego nie przyspieszy.
Napisz na `forum-password-resets@wordpress.org`, potwierdź status konta firmowego i poczekaj na decyzję.

Konto brandowe dostaje rolę Spectator: wgrywa i zarządza wtyczkami, ale nie pisze na forach wsparcia.
Forum wtyczki jest jedynym kanałem pomocy widocznym na jej stronie w katalogu, więc po zatwierdzeniu wtyczki trzeba założyć osobne konto imienne i przypisać je jako support representative.

Login jest case-sensitive i musi zgadzać się z polem `Contributors:` w `readme.txt`, inaczej katalog nie powiąże wtyczki z kontem.
Korespondencja od zespołu recenzji przychodzi z `plugins@wordpress.org`.

## Język treści

`readme.txt` musi być po angielsku.
Zespół wtyczek ogłosił ten wymóg 2025-07-28 wpisem „Requiring the README to be written in English", a powodem jest to, że angielski jest językiem roboczym recenzji.
Polska wersja karty nie powstaje przez drugi plik, tylko przez tłumaczenie na translate.wordpress.org.
Projekty tłumaczeń zakłada się po zatwierdzeniu wtyczki, a własne tłumaczenia zatwierdza się samodzielnie po uzyskaniu roli PTE dla swojej wtyczki.

Interfejsu wtyczki ten wymóg nie dotyczy, ale i tak trzymamy angielskie źródła w `__()` oraz polskie tłumaczenie w `languages/`.

Nie wołamy `load_plugin_textdomain()`, bo mechanizm just-in-time sam znajduje plik `.mo` w katalogu `languages/` wtyczki.
Dotyczy to także instalacji ręcznej spoza katalogu, czyli paczki pobranej z panelu CookieZen: zmierzone 2026-09-20 na WordPressie 7.1.1 przez wgranie wariantu bez tego wywołania, po którym opis wtyczki i podpisy w ustawieniach pozostały polskie.
Plugin Check zgłasza to wywołanie jako odradzane od WordPressa 4.6, więc jego brak jest jednocześnie zgodny z narzędziem i wystarczający.

**Pułapka: nagłówek `Description` też idzie przez katalog tłumaczeń.**
WordPress tłumaczy nagłówki wtyczki przez jej domenę tekstową, więc angielski napis w pliku musi mieć swój wpis w `.po`, inaczej polski klient zobaczy angielski opis na liście wtyczek.
`xgettext` nie wyciąga nagłówków z komentarza, trzeba je dopisać ręcznie z komentarzem `#. Description of the plugin`.

## Zgłoszenie wtyczki

Zbuduj czysty artefakt razem z walidacją readme w oficjalnym walidatorze:

```bash
./scripts/build-wordpress-plugin.sh --validate-readme
```

Build wymusza spójność metadanych między `readme.txt` a `cookiezen.php` i zatrzymuje się na rozjeździe, więc jego zielony wynik jest warunkiem zgłoszenia, a nie formalnością.
Wynik to `dist/cookiezen.zip` z jednym katalogiem `cookiezen/` w korzeniu.

Wykonaj smoke test z sekcji „Smoke test", zanim cokolwiek wyślesz.
Potem wejdź na https://wordpress.org/plugins/developers/add/ i wgraj `dist/cookiezen.zip`.

Recenzja nowej wtyczki liczy się w tygodniach, nie w dniach, i nie ma sposobu, żeby ją przyspieszyć.
Nie obiecuj klientom konkretnej daty, komunikuj „wkrótce w katalogu WordPress".

Najczęstsze pytania recenzentów dotyczą usługi zewnętrznej `cz-cdn.com` oraz synchronicznego znacznika `script`.
Gotowe odpowiedzi po angielsku czekają w sekcji „Odpowiedzi dla zespołu recenzji".

## Pierwszy commit do SVN

Dostęp do repozytorium SVN wtyczki dostajesz dopiero po akceptacji.

**Pułapka: WordPress.org używa SVN, nie gita.**
Nazwy poleceń są podobne, ale model pracy jest inny: `tags/` to katalogi kopiowane z `trunk/`, a nie wskaźniki na commity.

```bash
# 1. Checkout pustego repo SVN
svn co https://plugins.svn.wordpress.org/cookiezen cookiezen-svn
cd cookiezen-svn

# 2. Zbuduj artefakt w repo źródłowym i rozpakuj do trunk/
#    (kopiujemy zawartość katalogu cookiezen/ z ZIP-a, bez zagnieżdżonego katalogu)
cd ~/Projects/cookiezen-wordpress-plugin
./scripts/build-wordpress-plugin.sh
cd -
rm -rf trunk/* 2>/dev/null || true
unzip -o ~/Projects/cookiezen-wordpress-plugin/dist/cookiezen.zip -d /tmp/cz-unzip
cp -R /tmp/cz-unzip/cookiezen/* trunk/

# 3. Grafiki idą do assets/ w korzeniu SVN, nie do trunk/
cp ~/Projects/cookiezen-wordpress-plugin/.wporg-assets/*.png assets/

# 4. Dodaj i zacommituj
svn add --force trunk assets
svn ci -m "Initial release 1.0.4" --username cookiezen

# 5. Otaguj wersję do dystrybucji
svn cp trunk tags/1.0.4
svn ci -m "Tag 1.0.4" --username cookiezen
```

**Pułapka: `Stable tag` musi wskazywać katalog, który już istnieje w `tags/`.**
Sam commit do `trunk/` niczego nie dystrybuuje, a wskazanie nieistniejącego tagu zatrzymuje aktualizacje u klientów bez żadnego komunikatu.

`assets/` leży obok `trunk/` i `tags/`, nie w środku, i nie wchodzi do ZIP-a; dlatego build celowo pomija `.wporg-assets/`.
Ta niezależność jest wygodna: grafiki i zrzuty ekranu można dodać albo podmienić w dowolnym momencie, bez podbijania wersji wtyczki.

Grafiki mają warianty językowe.
Plik bez sufiksu jest domyślny, a `banner-772x250-pl_PL.png` serwuje się polskim odwiedzającym; ta sama zasada działa dla zrzutów ekranu, na przykład `screenshot-1-pl_PL.png`.
Zrzuty wymagają jednego pliku na każdą linię sekcji `== Screenshots ==` w `readme.txt`, więc sekcję dopisuje się razem z plikami, nigdy wcześniej.

## Wydanie nowej wersji

Podbij metadane w tym repo: `Version:` w `cookiezen.php`, `Stable tag:` w `readme.txt` oraz wpis w sekcji `== Changelog ==`, opcjonalnie też w `== Upgrade Notice ==`.
Build wymusza zgodność tych pól, więc rozjazd zatrzyma się lokalnie, a nie u recenzenta.

Zbuduj, zwaliduj i skopiuj paczkę do panelu jedną komendą:

```bash
./scripts/build-wordpress-plugin.sh --validate-readme
```

Kopia do `cmp-app/public/cookiezen.zip` jest domyślna, a skrypt szuka repo panelu obok tego katalogu.
Gdy `cmp-app` stoi gdzie indziej, podaj `CMP_APP_PANEL_ZIP` albo `--copy-to-panel <ścieżka>`; gdy świadomie nie chcesz ruszać panelu, użyj `--no-copy`.

Wykonaj smoke test zbudowanej paczki, potem zacommituj oba repozytoria ręcznie: to repo ze zmianami w źródle i `cmp-app` z odświeżonym `public/cookiezen.zip`.

Na koniec zsynchronizuj SVN:

```bash
cd cookiezen-svn
# zaktualizuj trunk/ z nowego ZIP-a, jak w kroku 2 sekcji o pierwszym commicie
svn ci -m "Update to X.Y.Z" --username cookiezen
svn cp trunk tags/X.Y.Z
svn ci -m "Tag X.Y.Z" --username cookiezen
```

Sam bump pola `Tested up to`, bez zmian w kodzie, jest dozwolony przez [wytyczne katalogu](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).
Rób go po każdym większym wydaniu WordPressa, ale dopiero po smoke teście na tej wersji.

## Smoke test

Domyślne środowisko to https://cookiezen.alwaysdata.net, czyli nasza testowa instalacja.
Ma zainstalowane WP Consent API razem z wtyczką przykładową, więc pokrywa całą checklistę bez dokładania czegokolwiek.
Lokalnie sprawdza się [Local by Flywheel](https://localwp.com/) albo `docker run -p 8080:80 wordpress`.

**Pułapka: pole `Tested up to` deklaruje wersję, na której wtyczkę faktycznie sprawdzono, i przyjmuje wyłącznie wersję główną.**
Nie jest to najnowsza wersja WordPressa, jaka istnieje, więc wersja środowiska testowego i wartość tego pola muszą się zgadzać.
Numer łatki jest błędem: przy WordPressie 7.1.1 w polu ma stać `7.1`, a Plugin Check odrzuca `7.1.1` kodem `invalid_tested_upto_minor`.

Przed każdym zgłoszeniem uruchom wtyczkę [Plugin Check](https://wordpress.org/plugins/plugin-check/) na środowisku testowym, w Narzędzia → Sprawdź wtyczkę.
Formularz zgłoszeniowy wymaga potwierdzenia, że jej uwagi są rozwiązane poza tymi, które uznajesz za fałszywe trafienia.
Nasze stałe odstępstwo to `NonEnqueuedScript`, opisane w sekcji o odpowiedziach dla recenzji; wklej tamto uzasadnienie do pola „Additional Information" przy zgłoszeniu.

Testowa instalacja ma podpięty żywy Site Key, więc kliknięcie w baner podczas testu dopisuje prawdziwy rekord zgody do statystyk tej witryny w produkcji.

Checklista:

- [ ] Instalacja: Wtyczki → Dodaj → Wyślij na serwer → `dist/cookiezen.zip` → Zainstaluj → Włącz, bez błędów PHP.
- [ ] Ustawienia: Ustawienia → CookieZen, wpisz testowy Site Key, zapisz i przeładuj, wartość się utrzymuje.
- [ ] Walidacja Site Key: wpisz znak spoza `[a-zA-Z0-9_]`, na przykład `abc!`, i zapisz; pojawia się komunikat o błędnym formacie, a wartość nie zostaje zapisana.
- [ ] Wstrzyknięcie: w źródle strony na froncie znacznik `script` z adresem loadera stoi w `head` po `meta charset` i `title`, a przed skryptami innych wtyczek.
- [ ] Brak Site Key oznacza brak wstrzyknięcia: usuń klucz, zapisz, znacznik znika z `head`.
- [ ] WP Consent API: przy aktywnej wtyczce [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) loader ustawia `window.wp_consent_type` na `optin`, sprawdzalne w konsoli przeglądarki.
- [ ] Deinstalacja: Wtyczki → Usuń, po czym `uninstall.php` czyści opcję `cookiezen_site_key`; bez dostępu do bazy sprawdzisz to pośrednio, bo po ponownej instalacji pole Site Key jest puste.

## Odpowiedzi dla zespołu recenzji

Teksty są po angielsku i do wklejenia w mail bez zmian.
Każdy akapit jest jedną linią, żeby kopiowanie nie wnosiło łamania wierszy do wiadomości.

Usługa zewnętrzna `cz-cdn.com`:

```text
CookieZen is a Consent Management Platform delivered as a service. The plugin connects the site to it by injecting our consent loader script from https://cz-cdn.com/api/cmp/loader?site_key=<SITE_KEY>.

Why the service is required: all of the CMP logic runs on our backend, including automatic cookie scanning, tracker classification, geolocation for different legal frameworks, Google Consent Mode v2 signals and storage of consent records. None of it can be bundled into the plugin.

Data sent: the site_key entered by the site owner in the plugin settings, plus the standard HTTP request headers of the visitor's browser (IP address, User-Agent, Referer) needed to deliver the script. IP addresses are irreversibly hashed on our side and are never stored in a form that allows identification.

This is documented in readme.txt under "External services". Privacy policy: https://cookiezen.pl/polityka-prywatnosci Terms of service: https://cookiezen.pl/regulamin The service is operated by Semavo Solutions Sp. z o.o.
```

Synchroniczny znacznik `script` w `wp_head` z priorytetem 1:

```text
Plugin Check reports this as WordPress.WP.EnqueuedResources.NonEnqueuedScript. We are aware of the rule and the deviation is deliberate, for two reasons that both depend on our script running before any other script on the page.

First, Google Consent Mode v2 default signals have to reach Google tags before gtag or Google Tag Manager initialises. Once a tag has initialised, the defaults arrive too late and the tag has already collected data without consent. Second, our loader sets window.wp_consent_type to "optin" for the WP Consent API. If any plugin that follows that standard reads the consent level before this value is set, the API reports consent as granted for every category, which is a fail-open exactly in the place a consent manager exists to prevent.

wp_enqueue_script cannot give us that ordering. Enqueued scripts are printed together on wp_head at priority 9, ordered by their dependency graph, and a marketing plugin that enqueues its own tag can be printed before ours. We therefore hook wp_head at priority 1: after meta charset and title, which have to appear within the first 1024 bytes of head for correct HTML5 encoding detection, and before plugins hooked at priority 10 or later. The script is a single tag with no inline code, its URL is escaped with esc_url, and the same rationale is documented inline in cookiezen.php.
```

## Synchronizacja z panelem klienta

Panel „Integracje" w `cmp-app` serwuje wtyczkę linkiem do `public/cookiezen.zip`; kod tej zakładki żyje w `components/dashboard/integration-manager.tsx`.

Przejście na model statycznego artefaktu jest wykonane.
`cmp-app` nie ma już katalogu `wordpress-plugin/` ani skryptu budującego paczkę, `package.json` nie odbudowuje jej przy `dev` ani `build`, a `public/cookiezen.zip` jest wersjonowany w gicie.
Żadne uruchomienie serwera deweloperskiego nie nadpisze więc skopiowanej paczki.

**Pułapka: pominięcie kopii do panelu nie zapala się nigdzie.**
`public/cookiezen.zip` aktualizuje wyłącznie krok z sekcji o wydaniu nowej wersji, a commit w `cmp-app` robi się ręcznie.
Pominięcie tego zostawia klientom w panelu poprzednią wersję wtyczki przy zielonym buildzie po obu stronach, bo nic tej zgodności nie sprawdza.
