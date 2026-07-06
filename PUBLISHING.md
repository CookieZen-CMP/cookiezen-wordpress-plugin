# Publikacja i wydawanie wtyczki CookieZen w katalogu WordPress.org

Ten dokument to kompletna instrukcja operacyjna: od pierwszego zgłoszenia
wtyczki do katalogu WordPress.org, przez proces review, po workflow wydawania
kolejnych wersji. Trzymaj się go krok po kroku.

---

## 0. Model repozytoriów (co gdzie żyje)

| Miejsce | Rola | Co zawiera |
|---|---|---|
| **To repo** (`Semavo/cookiezen-wordpress-plugin`) | **Źródło prawdy** kodu wtyczki | `cookiezen.php`, `uninstall.php`, `readme.txt`, `LICENSE.txt`, `languages/`, `.wporg-assets/`, `scripts/` |
| **SVN WordPress.org** (`plugins.svn.wordpress.org/cookiezen`) | Kanał dystrybucji + auto-update u klientów | `trunk/`, `tags/X.Y.Z/`, `assets/` |
| **Monorepo `cmp-app`** | Panel klienta „Integracje" (ręczne pobranie ZIP) | `public/cookiezen.zip` (zbudowany artefakt) |

Zasada: **kod zmieniasz TYLKO tutaj**. Stąd budujesz ZIP, który trafia do SVN
(dystrybucja WP.org) oraz — jako kopia — do panelu klienta w `cmp-app`.

> ⚠️ **Zależność w `cmp-app`** — przeczytaj `docs/` sekcję „Synchronizacja z panelem klienta"
> na końcu tego pliku, zanim wypuścisz pierwsze wydanie. Panel historycznie
> odbudowywał `public/cookiezen.zip` z własnego folderu `wordpress-plugin/`. Po
> migracji do tego repo trzeba przełączyć panel na statyczny artefakt.

---

## 1. Czynności jednorazowe (przed pierwszym zgłoszeniem)

### 1.1. Konto WordPress.org

1. Załóż konto na https://login.wordpress.org/register.
2. **Rekomendowany login: `cookiezen`** — będzie widoczny jako `Contributors:`
   w `readme.txt` i jest **case-sensitive** (musi zgadzać się dokładnie).
3. Podaj e-mail kontaktowy, na który przyjdzie korespondencja od zespołu review
   (`plugins@wordpress.org`).
4. Jeśli wybierzesz inny login niż `cookiezen`, **zaktualizuj pole
   `Contributors:` w `readme.txt`**, inaczej WP.org nie powiąże wtyczki z kontem.

### 1.2. Walidacja readme.txt (obowiązkowo przed zgłoszeniem)

Otwórz oficjalny walidator: https://wordpress.org/plugins/developers/readme-validator/

- Wklej zawartość `readme.txt` **lub** uruchom build z flagą:
  ```bash
  ./scripts/build-wordpress-plugin.sh --validate-readme
  ```
- Popraw wszystkie **błędy** (warnings nie blokują publikacji, ale warto poprawić).

### 1.3. Zgłoszenie wtyczki

1. Zbuduj czysty artefakt:
   ```bash
   ./scripts/build-wordpress-plugin.sh --validate-readme
   ```
   Wynik: `dist/cookiezen.zip` (jeden folder root `cookiezen/`).
2. **Wykonaj smoke test** na lokalnym WordPress (patrz sekcja 4) — zanim wyślesz.
3. Wejdź na https://wordpress.org/plugins/developers/add/ i wgraj `dist/cookiezen.zip`.
4. Czekaj na maila od `plugins@wordpress.org`. Czas review dla nowych wtyczek
   w 2026 to typowo **7–21 dni** (czasem dłużej). Nie obiecuj klientom
   konkretnej daty — komunikuj „wkrótce w katalogu WordPress".

### 1.4. Odpowiadanie na uwagi review teamu

Najczęstsze pytania dotyczą **usług zewnętrznych** (nasz loader `cz-cdn.com`)
oraz **synchronicznego `<script>`**. Gotowe odpowiedzi (po angielsku, do wklejenia
w mailu) znajdziesz w sekcji 5.

---

## 2. Pierwszy commit do SVN (po akceptacji)

Po akceptacji dostaniesz dostęp do repozytorium SVN wtyczki. WordPress.org
używa **SVN**, nie Git.

```bash
# 1. Checkout pustego repo SVN (podmień USERNAME)
svn co https://plugins.svn.wordpress.org/cookiezen cookiezen-svn
cd cookiezen-svn

# 2. Zbuduj artefakt w repo źródłowym i rozpakuj do trunk/
#    (kopiujemy ZAWARTOSC folderu cookiezen/ z ZIP-a, bez zagnieżdżonego folderu)
cd ~/Projects/cookiezen-wordpress-plugin
./scripts/build-wordpress-plugin.sh
cd -
rm -rf trunk/* 2>/dev/null || true
unzip -o ~/Projects/cookiezen-wordpress-plugin/dist/cookiezen.zip -d /tmp/cz-unzip
cp -R /tmp/cz-unzip/cookiezen/* trunk/

# 3. Assety graficzne — WAŻNE: idą do /assets/ w ROOCIE SVN, NIE do trunk/!
cp ~/Projects/cookiezen-wordpress-plugin/.wporg-assets/*.png assets/

# 4. Dodaj i zacommituj
svn add --force trunk assets
svn ci -m "Initial release 1.0.2" --username TWOJ_LOGIN

# 5. Otaguj wersję do dystrybucji (Stable tag z readme.txt)
svn cp trunk tags/1.0.2
svn ci -m "Tag 1.0.2" --username TWOJ_LOGIN
```

Uwagi:
- `Stable tag: 1.0.2` w `readme.txt` MUSI wskazywać na istniejący `tags/1.0.2/`.
- `assets/` **nie wchodzi** do ZIP-a ani do `trunk/` — to osobny katalog SVN
  na ikonę, banner i screenshoty.

---

## 3. Workflow wydania nowej wersji (X.Y.Z)

Dwa cele jednocześnie: **SVN (auto-update u klientów)** + **panel klienta w `cmp-app`**.

1. **W tym repo** podbij metadane (muszą być spójne — build to wymusza):
   - `Version:` w `cookiezen.php`
   - `Stable tag:` w `readme.txt`
   - dopisz wpis w sekcji `== Changelog ==` (+ opcjonalnie `== Upgrade Notice ==`)

2. **Zbuduj + zwaliduj + skopiuj do panelu** jedną komendą:
   ```bash
   ./scripts/build-wordpress-plugin.sh --validate-readme \
     --copy-to-panel ~/Projects/cmp-app/public/cookiezen.zip
   ```

3. **Smoke test** `dist/cookiezen.zip` na lokalnym WordPress (sekcja 4).

4. **Commit obu repo** (ręcznie — nigdy automatycznie):
   - to repo (GitHub) — zmiany w źródle
   - `cmp-app` — zaktualizowany `public/cookiezen.zip` (panel „Integracje" odświeżony)

5. **Sync do SVN** (dystrybucja katalogowa + auto-update):
   ```bash
   cd cookiezen-svn
   # zaktualizuj trunk/ z nowego ZIP-a (jak w kroku 2 sekcji 2)
   svn ci -m "Update to X.Y.Z" --username TWOJ_LOGIN
   svn cp trunk tags/X.Y.Z
   svn ci -m "Tag X.Y.Z" --username TWOJ_LOGIN
   ```

> **Sam bump `Tested up to`** (bez zmian w kodzie) jest dozwolony przez
> [guideline #14](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
> — rób go co kwartał, gdy wyjdzie nowa wersja WordPress.

---

## 4. Smoke test na lokalnym WordPress (przed każdym zgłoszeniem/wydaniem)

Najszybsze środowisko: [Local by Flywheel](https://localwp.com/) albo
`docker run -p 8080:80 wordpress`.

Checklista:

- [ ] **Instalacja** — Wtyczki → Dodaj → Wyślij na serwer → `dist/cookiezen.zip` → Zainstaluj → Włącz. Bez błędów PHP.
- [ ] **Ustawienia** — Ustawienia → CookieZen. Wpisz testowy Site Key, zapisz, przeładuj — wartość się utrzymuje.
- [ ] **Walidacja Site Key** — wpisz znak spoza `[a-zA-Z0-9_]` (np. `abc!`), zapisz — pojawia się komunikat o błędnym formacie, wartość NIE zostaje zapisana.
- [ ] **Injection** — na froncie (View Source) w `<head>` jest `<script src="https://cz-cdn.com/api/cmp/loader?site_key=...">`, wstawiony PO `<meta charset>`/`<title>`, a PRZED skryptami innych wtyczek.
- [ ] **Brak Site Key = brak injectu** — usuń Site Key, zapisz — skrypt znika z `<head>`.
- [ ] **WP Consent API (opcjonalnie)** — zainstaluj wtyczkę [WP Consent API](https://wordpress.org/plugins/wp-consent-api/); loader ustawia `window.wp_consent_type = 'optin'` (sprawdź w konsoli przeglądarki).
- [ ] **Deinstalacja** — Wtyczki → Usuń. `uninstall.php` czyści opcję `cookiezen_site_key` z bazy (sprawdź w `wp_options`).

---

## 5. Gotowe odpowiedzi dla zespołu review (EN, do wklejenia)

### 5.1. Usługi zewnętrzne (`cz-cdn.com` loader)

> **External service — cz-cdn.com (CookieZen CMP)**
>
> CookieZen is a Consent Management Platform (SaaS). The plugin connects the
> site to the CookieZen service by injecting our consent loader script:
> `https://cz-cdn.com/api/cmp/loader?site_key=<SITE_KEY>`.
>
> **Why the service is required:** all CMP logic runs server-side — automatic
> cookie scanning, tracker classification, geo-based legal routing (GDPR/ePrivacy/CCPA),
> Google Consent Mode v2 signals, and storage of consent proofs. This cannot be
> bundled into the plugin.
>
> **Data sent:** the site's `site_key` (entered by the site owner in plugin
> settings) plus the visitor's standard HTTP request headers (IP, User-Agent,
> Referer) required to serve the loader. IP addresses are irreversibly hashed
> on our side and never stored in an identifiable form.
>
> This is fully documented in `readme.txt` under "External services".
> Privacy Policy: https://cookiezen.pl/polityka-prywatnosci
> Terms of Service: https://cookiezen.pl/regulamin
>
> The service is operated by Semavo Solutions Sp. z o.o.

### 5.2. Synchroniczny `<script>` w `wp_head` z priorytetem 1

> **Why the loader is a synchronous script hooked early into `wp_head`**
>
> A consent banner must block tracking scripts *before* they execute. Our loader
> installs a `MutationObserver` at parse time to catch and block dynamically
> injected `<script>` tags from marketing plugins. Loading it with `async`/`defer`
> would create a fail-open race condition: trackers could fire before our
> observer is active, defeating the purpose of consent gating.
>
> We hook `wp_head` at priority `1` (not a large negative value) intentionally:
> it runs *after* `<meta charset>` / `<title>` (required within the first 1024
> bytes of `<head>` for correct HTML5 encoding detection), but *before* marketing
> plugins (priority >= 10) whose trackers must be intercepted. The rationale is
> also documented inline in `cookiezen.php`.

---

## 6. Synchronizacja z panelem klienta w `cmp-app` (prerequisite)

**Kontekst:** w `cmp-app` panel „Integracje" serwuje wtyczkę przez
`<a href="/cookiezen.zip" download>` (`components/dashboard/integration-manager.tsx`).
Historycznie plik `public/cookiezen.zip` był:
- **generowany** przy każdym `npm run build` / `npm run dev` przez
  `scripts/build-wp-plugin.mjs` ze źródeł w `cmp-app/wordpress-plugin/*.php`,
- **ignorowany** w gicie (`/public/cookiezen.zip` w `.gitignore`).

Po przeniesieniu źródła wtyczki do tego repo, `cmp-app/wordpress-plugin/` staje
się nieaktualnym duplikatem. Aby panel serwował poprawny, aktualny build,
**trzeba przełączyć `cmp-app` na model statycznego artefaktu**:

1. Usuń hook budujący ze `cmp-app/package.json`:
   - `build`: z `npm run build:wp-plugin && next build` → `next build`
   - usuń `predev` i `build:wp-plugin`
2. Usuń `cmp-app/scripts/build-wp-plugin.mjs` (nie ma już źródła do pakowania).
3. Odkomituj artefakt: usuń `/public/cookiezen.zip` z `cmp-app/.gitignore`,
   tak by ZIP był wersjonowany w gicie.
4. Usuń `cmp-app/wordpress-plugin/` (źródło żyje wyłącznie w tym repo).
5. Od teraz `public/cookiezen.zip` aktualizujesz **wyłącznie** krokiem
   `--copy-to-panel` z tego repo (krok 2 w sekcji 3) i commitujesz ręcznie.

> Dopóki ten prerequisite nie jest wykonany, kolejny `npm run dev`/`build`
> w `cmp-app` **nadpisze** skopiowany ZIP starą wersją z `wordpress-plugin/`.
> Ta zmiana dotyka pipeline deployu Vercela — wykonaj ją świadomie i przetestuj
> build (`npm run build`) po zmianie.
