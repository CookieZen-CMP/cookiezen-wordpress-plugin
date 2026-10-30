# CLAUDE.md

Wtyczka WordPress CookieZen: wstawia na stronę loader CMP z `cz-cdn.com` i integruje go z innymi wtyczkami (PixelYourSite, WP Consent API).
Reszta platformy (loader, baner, panel klienta) żyje w repo `cmp-app`, obok tego katalogu w `~/Projects`.
Dokumentacja i komentarze są po polsku, identyfikatory po angielsku; komentarze w PHP i skryptach powłoki piszemy bez polskich znaków, jak reszta tych plików.

## Mapa

- `cookiezen.php`: cała wtyczka; mechanikę każdej integracji opisuje docblock przy jej kodzie.
- `uninstall.php`: usuwa opcje wtyczki; nowa opcja trafia tutaj i do checklisty smoke testu w `PUBLISHING.md`.
- `readme.txt`: karta w katalogu WordPress.org, po angielsku; `docs/readme-pl.txt` to polski wzorzec tej treści.
- `docs/INTEGRATIONS.md`: decyzje, odrzucone warianty, ograniczenia i weryfikacja integracji z cudzymi wtyczkami.
- `PUBLISHING.md`: model repozytoriów, wydanie wersji, canary i wycofanie, smoke test, SVN WordPress.org.
- `scripts/build-wordpress-plugin.sh`: build z listą plików wpisaną w skrypt; plik spoza niej nie trafia do paczki.
- `tests/`: skrypty PHP poza paczką.

## Komendy

```bash
php tests/pys-gate.php                                      # logika bramki PixelYourSite na atrapach WordPressa
php tests/pys-contract.php <rozpakowany pixelyoursite>      # nazwy z wnętrza PixelYourSite na prawdziwej paczce
./scripts/build-wordpress-plugin.sh --validate-readme --no-copy
```

**Pułapka: build bez `--no-copy` nadpisuje `public/cookiezen.zip` w repo `cmp-app`, czyli paczkę, którą klienci pobierają z panelu.**
Kopia jest właściwa tylko przy wydaniu wersji według `PUBLISHING.md`, nigdy przy kandydacie ani w trakcie pracy.
W worktree domyślna ścieżka do `cmp-app` nie istnieje i build tylko ostrzega, więc wydanie z worktree wymaga `CMP_APP_PANEL_ZIP` albo `--copy-to-panel`.

## Zasady

- Wersja stoi w trzech miejscach sprawdzanych przez build: nagłówek `Version` i stała `COOKIEZEN_VERSION` w `cookiezen.php` oraz `Stable tag` w `readme.txt`; czwarte, `docs/readme-pl.txt`, podbijasz ręcznie, bo build go nie czyta.
- Minimum to PHP 7.4 i WordPress 5.5, a JS wstawiany inline piszemy w ES5, bo nie przechodzi przez żaden krok budowania; skrypt lustra ma `try/catch` na całości, bo wykonuje się synchronicznie w `<head>`.
- HTML wstawiany do strony jest identyczny dla każdego odwiedzającego, bo page cache podaje go wszystkim; decyzje zależne od zgody zapadają w przeglądarce albo w żądaniach poza renderem (`docs/INTEGRATIONS.md`, „Zasady wspólne”).
- Zmiana tego HTML podnosi `COOKIEZEN_MARKUP_VERSION`, co jednorazowo czyści page cache klientów.
- Wtyczka czyta handoff loadera i zdarzenie banera, a pola przypinają testy w `cmp-app`; zmiana reguły w `cookiezen_pys_mirror_js()` wymaga tej samej zmiany w `pluginMarketing` w `cmp-app/tests/unit/wordpress-plugin-handoff-contract.test.ts`.
- Zachowanie na prawdziwym WordPressie sprawdza uprząż `scripts/wordpress-pys-harness/` w `cmp-app`; przebieg w nagłówku jej `run.ts`.
- Żadnych danych klientów w repo: domen sklepów, kluczy stron, adresów e-mail.

## Git

Te same zasady co w `cmp-app` (jego `CLAUDE.md`, sekcja „Zero automatycznego gita”): `commit`, `push` i PR wyłącznie na jawną, świeżą komendę, a edycja we własnym worktree w `.claude/worktrees/`.
Opis commita po polsku, z typem i zakresem po angielsku, na przykład `fix(wordpress-plugin): ...`.
Wydanie zmienia oba repozytoria (to i `cmp-app` z nową paczką), więc commity idą w parze.
