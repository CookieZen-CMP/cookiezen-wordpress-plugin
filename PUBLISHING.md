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

**Pułapka: dla wtyczki z katalogu `load_plugin_textdomain()` jest zbędne, ale my mamy drugi kanał dystrybucji.**
Klient pobierający ZIP z panelu CookieZen instaluje wtyczkę spoza katalogu, więc WordPress nie ma dla niej niczego w `WP_LANG_DIR`, a mechanizm just-in-time nie zagląda do katalogu `languages/` wtyczki.
Dlatego wywołanie zostaje, na hooku `init`; wcześniejszy hook daje w WordPressie 6.7 i nowszych notice o zbyt wczesnym ładowaniu domeny.
Ta sama uwaga dotyczy nagłówków wtyczki: `Description` tłumaczy się przez domenę tekstową, więc napis musi być w katalogu tłumaczeń, inaczej polski klient zobaczy angielski opis na liście wtyczek.

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

**Pułapka: pole `Tested up to` deklaruje wersję, na której wtyczkę faktycznie sprawdzono.**
Nie jest to najnowsza wersja WordPressa, jaka istnieje, więc wersja środowiska testowego i wartość tego pola muszą być te same.

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
A consent banner has to be able to gate tracking scripts before they execute. Our loader installs a MutationObserver at parse time so that dynamically injected script tags from marketing plugins are caught. Loading it with async or defer would create a fail-open race: trackers could fire before the observer is active, which defeats the purpose of consent gating.

We hook wp_head at priority 1 rather than at a large negative value on purpose. Priority 1 runs after meta charset and title, which have to appear within the first 1024 bytes of head for correct HTML5 encoding detection, and still before marketing plugins at priority 10 or higher whose trackers need to be intercepted. The same rationale is documented inline in cookiezen.php.
```

## Synchronizacja z panelem klienta

Panel „Integracje" w `cmp-app` serwuje wtyczkę linkiem do `public/cookiezen.zip`; kod tej zakładki żyje w `components/dashboard/integration-manager.tsx`.

Przejście na model statycznego artefaktu jest wykonane.
`cmp-app` nie ma już katalogu `wordpress-plugin/` ani skryptu budującego paczkę, `package.json` nie odbudowuje jej przy `dev` ani `build`, a `public/cookiezen.zip` jest wersjonowany w gicie.
Żadne uruchomienie serwera deweloperskiego nie nadpisze więc skopiowanej paczki.

**Pułapka: pominięcie kopii do panelu nie zapala się nigdzie.**
`public/cookiezen.zip` aktualizuje wyłącznie krok z sekcji o wydaniu nowej wersji, a commit w `cmp-app` robi się ręcznie.
Pominięcie tego zostawia klientom w panelu poprzednią wersję wtyczki przy zielonym buildzie po obu stronach, bo nic tej zgodności nie sprawdza.
