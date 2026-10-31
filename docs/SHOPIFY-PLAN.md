# Integracja CookieZen z Shopify: plan i rekomendacja

Stan na 2026-10-31. Dokument roboczy: po decyzji właściciela przenieść go do repo `cmp-app`, bo tam powstanie kod (loader, baner, backend aplikacji).

## Jak powstał ten plan i czego nie potwierdziliśmy

- Proxy tej sesji blokuje `shopify.dev`, `help.shopify.com`, `apps.shopify.com`, `help.pandectes.io` i `pandectes.io`. Fakty o Shopify i Pandectes pochodzą z fragmentów stron w wynikach wyszukiwarki, a nie z lektury całych stron.
- Jedynym źródłem pierwotnym, czytanym bezpośrednio, był kod Shopify z npm i GitHuba: `@shopify/hydrogen` 2026.10.2 (2026-10-20), `@shopify/hydrogen-react` 2026.4.4 (2026-09-29) i `Shopify/ui-extensions`.
- Nie udało się obejrzeć HTML żywego sklepu z Pandectes, więc kolejność skryptów w `<head>` znamy tylko z dokumentacji.
- Repo `cmp-app` nie było dostępne w tej sesji. Nazwy kategorii zgody poza `marketing` (`analytics`, `preferences`) są założeniem i trzeba je sprawdzić w teście kontraktu zdarzenia banera.
- Oznaczenia pewności w tekście:
  - **[kod]** potwierdzone w kodzie Shopify,
  - **[docs]** fragment oficjalnej dokumentacji z wyszukiwarki,
  - **[vendor]** deklaracja dostawcy,
  - **[niepotwierdzone]** wniosek albo forum.
- Wszystko oznaczone [niepotwierdzone] trzeba zweryfikować w fazie 0 na sklepie deweloperskim.

## Rekomendacja w skrócie

1. **Tak, jako „wtyczka”, ale w formie Shopify. Bez przenoszenia panelu do Shopify.**
   - Odpowiednikiem wtyczki WordPress jest **publiczna aplikacja Shopify z theme app extension (app embed)**. Merchant instaluje ją z Shopify App Store i włącza jednym przełącznikiem w edytorze motywu.
   - Konfiguracja banera, skaner, kategorie i regiony zostają w panelu CookieZen. W adminie Shopify aplikacja ma tylko ekran połączenia ze stroną CookieZen i listę kontrolną stanu.
   - Budowanie drugiego panelu w Polaris to duży koszt bez zysku dla klienta.
2. **Synchronizacja zgody z Shopify Customer Privacy API w runtime banera (cmp-app), tak jak WP Consent API.**
   - Baner przy każdej decyzji woła `Shopify.customerPrivacy.setTrackingConsent(...)`.
   - Działa to też u klientów z loaderem wklejonym ręcznie, więc da się to wdrożyć od razu, przed aplikacją.
   - To największy zysk tej integracji: Shopify sam wstrzymuje wtedy swoje i aplikacyjne Web Pixels (Google & YouTube, Meta, TikTok, piksele własne) oraz analitykę do chwili zgody. Nie trzeba ich blokować w DOM.
3. **Blokowanie skryptów warstwowo, wzorem Pandectes.**
   - Customer Privacy API obsługuje wszystko, co Shopify ładuje sam.
   - Loader z app embedu w miejscu `compliance_head` zatrzymuje skrypty innych app embedów, ScriptTagi i skrypty motywu, które stoją za nim.
   - Skrypty wpisane na sztywno w motyw przed `content_for_header` wymagają ręcznego oznaczenia. Aplikacja wskaże je sama, czytając motyw (`read_themes`), i poda gotową poprawkę.
   - Automatycznego dopisywania snippetu do `theme.liquid`, jak robi Pandectes, nie planujemy. Wymaga zakresu `write_themes`, który Shopify przyznaje wyjątkowo, a regulamin App Store każe integrować się z motywem przez theme app extensions.
4. **Ręczne wklejenie loadera do `theme.liquid` jest dobre tylko na start (pilotaż), nie jako produkt.** Znika przy zmianie motywu, nie daje obecności w App Store i nie daje ekranu stanu.
5. **Najpierw decyzja biznesowa o rozliczeniach.**
   - Shopify App Store wymaga, żeby opłaty szły przez Shopify Billing. Wyjątek dla zewnętrznego SaaS z własnym billingiem nie jest samoobsługowy, a darmowa aplikacja jako „łącznik” może zostać uznana za płatną [docs + forum].
   - To ryzyko dla modelu CookieZen i decyzja właściciela przed rozpoczęciem fazy 1. Szczegóły w sekcji „Rozliczenia”.

## Co już wiemy o Shopify

### Customer Privacy API

| Element | Stan | Pewność |
|---|---|---|
| Ładowanie | `Shopify.loadFeatures([{name: 'consent-tracking-api', version: '0.1'}], cb)`, potem `window.Shopify.customerPrivacy`. Hydrogen ładuje bezpośrednio `cdn.shopify.com/shopifycloud/consent-tracking-api/v0.2/consent-tracking-api.js`. Callback `loadFeatures` bywa niewywołany, więc potrzebny jest timeout. | [docs], [kod], [forum] |
| Zapis | `setTrackingConsent({analytics, marketing, preferences, sale_of_data}, callback)`, cztery pola boolean. W Hydrogen metoda zwraca Promise. | [kod] |
| Kiedy wolno zapisać | Tylko po interakcji odwiedzającego, nigdy automatycznie. | [docs] |
| Odczyt | `analyticsProcessingAllowed()`, `marketingAllowed()`, `preferencesProcessingAllowed()`, `saleOfDataAllowed()` uwzględniają region i ustawienia sklepu. `currentVisitorConsent()` zwraca sam wybór odwiedzającego (`'yes'`/`'no'`/`''`). `getRegion()` zwraca kod np. `"USCA"`. | [docs], [kod] |
| Zdarzenia | `visitorConsentCollected` i `consentTrackingApiLoaded` na `document`. | [kod] |
| Przechowywanie | Od 2025-09-15 Shopify nie ustawia już `_tracking_consent`. Nowej nazwy ciasteczka nie potwierdziliśmy (`_shopify_cmp`?). Dokumentacja zabrania czytania ciasteczek Shopify; tylko API. | [docs], changelog 2025-08-04 |
| GPC | W regionach opt-out GPC wymusza `sale_of_data = false` i API tego nie nadpisze. | [docs] |
| Checkout | Zgoda przechodzi na checkout na subdomenie domeny sklepu. Konta klientów muszą być na subdomenie, inaczej piksele i zgoda tam nie działają. | [docs] |

### Co Shopify blokuje sam na podstawie tej zgody

- **Web Pixels aplikacji:** każdy piksel deklaruje w `shopify.extension.toml` sekcję `[customer_privacy]`. Bez niej domyślnie wymaga zgody na marketing i analitykę i jest traktowany jako sprzedaż danych [docs, changelog 2024-05-02].
- **Piksele własne** (Settings > Customer events) mają ustawienie Permission. Domyślnie wymagają zgody na marketing i analitykę [docs].
- **Analityka Shopify:** w regionach ze zgodą nie zakłada `_shopify_s` przed akceptacją. CMP, który kasuje i odtwarza ciasteczka Shopify, zawyża liczbę sesji; poprawna droga to API [docs].
- **Skrypty wpisane w motyw** Shopify zostawia bez zmian, więc ich blokowanie należy do CMP [docs].
- **Google Consent Mode:** czy aplikacja Google & YouTube sama wysyła `gtag('consent','update')`, nie potwierdziliśmy oficjalnie. Na forum (około 10.2025) opisano wysyłanie `default: granted` [niepotwierdzone]. Do zmierzenia w fazie 0, bo nasz loader ustawia własne domyślne wartości Consent Mode.

### Natywny baner Shopify

- Ustawienie: Settings > Customer privacy > Cookie banner > menu ⋯ > Remove cookie banner, „jeśli opcja jest dostępna” [docs].
- Regiony wymagające zgody w Shopify trzeba zostawić aktywne i ustawić tak samo jak w CookieZen. Shopify zaleca opcję zbierania danych „Collected after consent”; bez niej dane idą przed zgodą [docs].
- Zewnętrzny CMP „must sync consent to Shopify through the Customer Privacy API”, inaczej piksele mogą nie ruszyć [docs].
- Programowego wyłączenia natywnego banera z aplikacji nie znaleźliśmy. Aplikacja może najwyżej wykryć, że baner dalej działa, i poprowadzić merchanta [niepotwierdzone].

### Jak wstawić skrypt na storefront

| Droga | Stan | Wniosek dla nas |
|---|---|---|
| App embed, `target: "compliance_head"` | Renderowany jako pierwszy z app embedów, przeznaczony dla banerów zgody. Procesu kwalifikacji nie znaleźliśmy [kod, docs]. | Główna droga. |
| App embed, `head` / `body` | Po `compliance_head` [docs]. | Nie dla loadera. |
| Pozycja względem `content_for_header` | App embedy i skrypty aplikacji lądują **po** `content_for_header`, niezależnie od layoutu motywu [docs, strona o zasobach first-paint]. | Skryptów wpisanych przed `content_for_header` loader nie wyprzedzi. Do potwierdzenia na wyrenderowanym HTML. |
| Embed po instalacji | Domyślnie wyłączony; merchant włącza go w edytorze motywu albo przez deep link [docs]. Embed jest per motyw, więc po publikacji nowego motywu bywa wyłączony [forum]. | Onboarding z deep linkiem i kontrola stanu w aplikacji. |
| ScriptTag API | Od 2026-10-01 nie da się tworzyć ani zmieniać ScriptTagów, od 2027-03-01 Shopify przestaje je wstrzykiwać [changelog]. | Nie używamy. Problem cudzych ScriptTagów zniknie w marcu 2027. |
| Ręczny wpis w `theme.liquid` | Działa wcześniej niż embed (może stać przed `content_for_header`), ale znika przy zmianie motywu. Aplikacja z App Store nie może sama edytować motywu [docs]. | Faza 0 i opcjonalny tryb ścisły. |
| Custom pixel | Iframe z sandboxem, bez dostępu do DOM sklepu i bez UI [docs]. | Nie na baner. Najwyżej odbiornik zdarzenia zgody dla GTM w sandboxie. |
| Checkout | Bez skryptów zewnętrznych. Baner tylko jako Checkout UI extension (`collect_buyer_consent`, `applyTrackingConsentChange`). Kroki info/shipping/payment tylko na Plus; thank-you i order status na wszystkich planach poza Starter [docs]. | Faza 3; zwykle wystarcza zgoda zebrana na storefroncie. |
| Headless/Hydrogen | `ShopifyScripts consent={{mode: 'custom-banner', setup}}`, `setup()` czeka na `setTrackingConsent` [kod]. | Faza 3, osobny przewodnik. |

## Pandectes: co warto skopiować, a czego nie

Źródła to help center i blog Pandectes, znane z wyników wyszukiwarki [vendor].

| Mechanizm Pandectes | Ocena |
|---|---|
| Synchronizacja z Customer Privacy API, plus zdarzenie `Pandectes_Consent_Update` publikowane do sandboxa custom pixeli (dla GTM uruchamianego w pikselu) | Kopiujemy oba. |
| Auto blocker na presetach usług (100+), dopasowanie po domenie skryptu, iframe i piksela; domyślnie działa przez app embed | Kopiujemy ideę. Mamy skaner i kategorie, potrzebna jest lista reguł Shopify (domena → kategoria). |
| `pandectes-rules.js` jako snippet dopisywany przez aplikację do `theme.liquid` i `password.liquid` „zaraz po `<head>`”, czyli przed `content_for_header` | Nie kopiujemy automatu. Nie wiemy, czy Pandectes ma wyjątek `write_themes`, a nowa aplikacja go raczej nie dostanie. Ten sam efekt daje ręczny „tryb ścisły” z instrukcją, co robią Consentmo i OneTrust. |
| Ręczne oznaczanie skryptów motywu: `type="javascript/blocked"` i `data-cookiecategory` | Mamy odpowiednik: `type="text/plain"` i `data-cmp-category`. Dokładamy automatyczne wskazanie, gdzie to zrobić. |
| Tryby reguł strict (opt-in) i loose (opt-out, blokada od następnej odsłony) | Bierzemy strict jako domyślny. Loose zależy od regionu, a to rozstrzyga loader. |
| Checkout UI extension z banerem (Plus) i blokiem na thank-you | Faza 3. |
| Baner jako Web Component w Shadow DOM, deklarowany TBT 0 ms | Do sprawdzenia, co ma nasz baner. Built for Shopify mierzy spadek Lighthouse. |
| Odznaka Built for Shopify (od 06.2025, według Pandectes) | Cel fazy 2, nie warunek startu. |

Jak Pandectes blokuje od środka (nadpisanie `createElement`, `MutationObserver`, przechwytywanie `document.cookie`), nie udało się ustalić ze źródeł publicznych. Pozostali dostawcy (Cookiebot, Consentmo, OneTrust, CookieYes) też ładują baner przez app embed, a wcześniejsze blokowanie dają tylko ręczną instrukcją wklejenia kodu do `theme.liquid`.

## Architektura docelowa

```
Shopify storefront <head>
  {{ content_for_header }}        skrypty Shopify, Web Pixels manager, ScriptTagi (asyncLoad do 2027-03-01)
  [app embed: compliance_head]    <script src="https://cz-cdn.com/api/cmp/loader?site_key=..."> synchronicznie
  [app embedy innych aplikacji]   zatrzymywane przez loader według reguł
  skrypty motywu po content_for_header   zatrzymywane przez loader albo oznaczone ręcznie
</head>

baner CookieZen  --decyzja-->  Shopify.customerPrivacy.setTrackingConsent(...)   bramkuje Web Pixels i analitykę Shopify
                 --decyzja-->  gtag consent update                                 jak dziś
                 --decyzja-->  Shopify.analytics.publish('cookiezen_consent', ...) dla custom pixeli z GTM [niepotwierdzone API]
```

Elementy po stronie CookieZen:

| Element | Gdzie | Zawartość |
|---|---|---|
| Most Customer Privacy API | runtime banera w `cmp-app` | Mapowanie kategorii, zapis decyzji, ponowna synchronizacja przy odsłonie. Wzór: WP Consent API (`cmp-app`, `docs/engineering/ARCHITECTURE.md`, sekcja 7.5). |
| Reguły blokowania Shopify | dane loadera w `cmp-app` | Domena → kategoria dla typowych app embedów i trackerów. |
| Aplikacja Shopify | `cmp-app`, obok integracji Shoper (do potwierdzenia, gdzie ona leży), albo osobne repo `cookiezen-shopify-app` | Szablon Shopify CLI (React Router), OAuth, sesje, ekran połączenia i stanu, webhooki zgodności, theme app extension. |
| Theme app extension | w tym samym repo co aplikacja | Jeden app embed `compliance_head`, który drukuje loader. Klucz strony z metapola aplikacji. |

Zasady z WordPressa, które przenosimy 1:1:

- HTML wstawiany przez embed jest identyczny dla każdego odwiedzającego. Shopify też cache'uje storefront, więc decyzje zależne od zgody zapadają w przeglądarce.
- Loader synchroniczny, bez `async` i `defer`, z tych samych powodów co w `cookiezen_inject_script()`: domyślne wartości Consent Mode przed gtag.
- Fail open dla nieznanych skryptów, zgodnie z algorytmem blokowania CookieZen.
- Dwa loadery na jednej stronie (embed i ręczny wpis w trybie ścisłym) nie mogą się zdublować. Embed sprawdza `window.__CMP_LOADER` i nie wstawia drugiego.

Jedna różnica na korzyść Shopify: theme app extension wydajemy sami i trafia do wszystkich sklepów bez aktualizacji po stronie merchanta. Problem „starych kopii u klientów”, który w WordPressie wymusza ostrożność z kontraktem, jest tu mniejszy. Kontraktu z loaderem i banerem nadal nie wolno zmieniać znaczeniowo, bo działa też przy ręcznych wpisach.

## Synchronizacja zgody z Shopify (wzór WP Consent API)

To można wdrożyć od razu, w runtime banera w `cmp-app`, niezależnie od aplikacji.

### Mapowanie kategorii

| Shopify | CookieZen | Uwagi |
|---|---|---|
| `analytics` | `analytics` | |
| `marketing` | `marketing` | |
| `preferences` | `preferences` (albo `functional`, sprawdzić nazwę) | |
| `sale_of_data` | `marketing` | W UE nie ma osobnej zgody na sprzedaż danych; reklama spersonalizowana jest jej odpowiednikiem. W regionach opt-out (USA) decyduje GPC i Shopify sam to egzekwuje. |

### Kiedy wołać `setTrackingConsent`

1. **Przy decyzji w banerze:** zdarzenie `cookiezen:consent` z `finalized`. To te same pola, które czyta lustro PYS, przypięte w `banner-consent-event-contract.test.ts`.
2. **Przy odsłonie, z handoffu `window.__CMP_LOADER`**, gdy `hasConsent` jest prawdą, a `currentVisitorConsent()` różni się od zapisanej decyzji CookieZen.
   - To ponowne przekazanie decyzji, którą odwiedzający już podjął. Pokrywa przypadki, gdy ciasteczko Shopify wygasło albo zostało wyczyszczone osobno, zgoda została zebrana na innej domenie sklepu, albo klient przeszedł z innego CMP.
   - Reguła Shopify „nie zapisuj bez interakcji” zabrania tylko wymyślania zgody bez decyzji. Tę interpretację trzeba potwierdzić przy przeglądzie aplikacji.
3. **Region zwolniony** (`region.exempt`): nic nie wysyłamy. Shopify w regionach bez wymogu zgody i tak przepuszcza, pod warunkiem że regiony w obu systemach są zgodne. Pilnuje tego lista kontrolna w aplikacji.

### Szkic implementacji (ES5, do przeniesienia do banera w `cmp-app`)

```js
(function () {
  try {
    var w = window;
    if (!w.Shopify) { return; } // nie Shopify: zero kosztu

    function api(cb) {
      var S = w.Shopify, done = false;
      function fire() {
        if (done || !S.customerPrivacy || typeof S.customerPrivacy.setTrackingConsent !== 'function') { return; }
        done = true; cb(S.customerPrivacy);
      }
      fire();
      if (done || typeof S.loadFeatures !== 'function') { return; }
      S.loadFeatures([{ name: 'consent-tracking-api', version: '0.1' }], fire);
      setTimeout(fire, 3000); // callback loadFeatures bywa niewywolany
    }

    function toShopify(c) {
      c = c || {};
      return {
        analytics: !!c.analytics,
        marketing: !!c.marketing,
        preferences: !!c.preferences,
        sale_of_data: !!c.marketing
      };
    }

    function same(cp, want) {
      var cur = typeof cp.currentVisitorConsent === 'function' ? cp.currentVisitorConsent() : null;
      if (!cur) { return false; }
      function yn(v) { return v ? 'yes' : 'no'; }
      return cur.analytics === yn(want.analytics) && cur.marketing === yn(want.marketing)
        && cur.preferences === yn(want.preferences) && cur.sale_of_data === yn(want.sale_of_data);
    }

    function push(categories) {
      var want = toShopify(categories);
      api(function (cp) {
        if (same(cp, want)) { return; }
        cp.setTrackingConsent(want, function () {});
      });
    }

    // 1. Decyzja w banerze
    w.addEventListener('cookiezen:consent', function (e) {
      var d = (e && e.detail) || {};
      if (d.finalized) { push(d.categories); }
    });

    // 2. Ponowna synchronizacja wczesniejszej decyzji przy odslonie
    var h = w.__CMP_LOADER;
    if (h && h.hasConsent && h.existingCategories && !(h.region && (h.region.exempt || h.region.pending))) {
      push(h.existingCategories);
    }
  } catch (e) {}
})();
```

Do sprawdzenia przed wdrożeniem:

- nazwy kategorii i znaczenie `finalized` w kontrakcie zdarzenia banera;
- czy `currentVisitorConsent()` zwraca `sale_of_data` w tym samym formacie;
- zachowanie z wieloma kluczami strony na jednej witrynie (to samo zastrzeżenie co w lustrze PYS: `h.siteKey`).

### Testy i kontrakt

- Test jednostkowy mapowania w `cmp-app`, obok testów WP Consent API.
- Nowe pole czytane z handoffu albo zdarzenia trafia do testów kontraktu w tej samej parze zmian.
- Uprząż przeglądarkowa na sklepie deweloperskim (Shopify Partners, bezpłatny development store): Playwright sprawdza, że przed zgodą Web Pixel testowej aplikacji się nie uruchamia, a po zgodzie na marketing tak. Dochodzi kontrola pozytywna: bez mostu piksel nie rusza nigdy przy „Collected after consent”.

## Blokowanie skryptów na Shopify

### Warstwy

| # | Co | Czym | Stan |
|---|---|---|---|
| A | Web Pixels (aplikacje i własne), analityka Shopify, aplikacje Google, Meta i TikTok w wersji pikselowej | Customer Privacy API: Shopify sam je wstrzymuje | Wystarczy most z poprzedniej sekcji i ustawienia prywatności sklepu. |
| B | ScriptTagi cudzych aplikacji (`asyncLoad` z `content_for_header`) | Loader. Shopify wstawia je dynamicznie po `load`, czyli po naszym loaderze, więc łapie je przechwycenie wstawiania skryptów [niepotwierdzone: zmierzyć] | Do 2027-03-01, potem znika. |
| C | App embedy innych aplikacji i skrypty motywu za `content_for_header` | Loader z `compliance_head`: `MutationObserver` w czasie parsowania i przechwycenie dynamicznego wstawiania, reguły domena → kategoria | Wymaga włączenia autoblokowania dla stron Shopify (dziś wyłączone globalnie). |
| D | Skrypty motywu przed `content_for_header` i skrypty inline | Ręczne oznaczenie `type="text/plain" data-cmp-category="..."`. Aplikacja czyta motyw (`read_themes`), wskazuje takie skrypty i podaje gotową poprawkę. | Przewodnik plus asystent w aplikacji. |
| E | Opcjonalny „tryb ścisły” | Merchant wkleja `<script src=loader>` zaraz po `<head>` w `theme.liquid`; embed wykrywa ręczny loader i nie dubluje go | Dla sklepów z hardcodowanymi trackerami, odpowiednik `pandectes-rules`. |
| F | Custom pixele z GTM | Zdarzenie zgody publikowane do sandboxa i gotowy kod pixela z `gtag('consent', ...)` | Faza 2. |
| G | Checkout | Zgoda ze storefrontu przechodzi automatycznie. Opcjonalnie Checkout UI extension z banerem: kroki płatności tylko Plus, thank-you na wszystkich planach | Faza 3. |

Decyzja do podjęcia w `cmp-app`: włączenie autoblokowania (warstwa C) dla stron z platformą Shopify to zmiana stanu produkcyjnego algorytmu blokowania. Wymaga wpisu w `docs/engineering/BLOCKING_ALGORITHM.md`, sekcja „Stan produkcyjny”, tak jak każdy wyjątek od fail open.

### Znane ograniczenia, które trzeba będzie komunikować

- Skryptu stojącego w motywie przed `content_for_header` żadna aplikacja nie zablokuje bez edycji motywu. To samo przyznają Cookiebot i Pandectes.
- Shopify zakłada swoje niezbędne ciasteczka przed zgodą; to ciasteczka „essential”, poza zakresem CMP.
- Skrypty inline nie mają adresu do dopasowania, więc zostaje ręczne oznaczenie.
- App embed jest per motyw. Po zmianie motywu baner bywa wyłączony, więc aplikacja ma to wykrywać i ostrzegać.
- Synchroniczny skrypt z zewnętrznego CDN w `<head>` obniża Lighthouse. Built for Shopify dopuszcza spadek najwyżej 10 punktów, CLS ≤ 0,1 i INP ≤ 200 ms. Trzeba zmierzyć nasz loader i baner na motywie Dawn.

## Panel Shopify czy kod we wtyczce: porównanie wariantów

| Kryterium | 1. Ręczny wpis w `theme.liquid` | 2. Publiczna aplikacja, cienka (rekomendacja) | 3. Aplikacja z pełnym panelem w adminie Shopify |
|---|---|---|---|
| Instalacja dla merchanta | Edycja kodu motywu | App Store i przełącznik embedu | App Store i przełącznik embedu |
| Wczesne blokowanie | Najlepsze (przed `content_for_header`) | Za `content_for_header`; opcjonalnie tryb ścisły | Jak w 2 |
| Przetrwa zmianę motywu | Nie | Częściowo (embed do ponownego włączenia, aplikacja ostrzega) | Jak w 2 |
| Most Customer Privacy API | Tak (w banerze) | Tak (w banerze) | Tak |
| Kanał sprzedaży | Brak | App Store, wyszukiwanie „GDPR cookie” | App Store |
| Wymogi Shopify | Żadne | Review, webhooki zgodności, billing | Jak w 2, plus Polaris i Built for Shopify dla panelu |
| Koszt budowy | Około 0 | Średni | Wysoki (druga kopia panelu) |
| Utrzymanie | Wsparcie przy każdej zmianie motywu | Wersje embedu po naszej stronie, bez akcji merchanta | Dwa panele do synchronizacji |

Wariant 2 jest odpowiednikiem wtyczki WordPress: cienka warstwa wstawiająca loader, a cała logika w loaderze, banerze i panelu CookieZen. Wariant 1 zostaje jako faza 0 i jako „tryb ścisły” w wariancie 2.

## Rozliczenia (decyzja właściciela)

- Shopify App Store wymaga, żeby opłaty za aplikację szły przez Shopify Billing (App Pricing albo Billing API). Rozliczanie poza platformą jest dozwolone tylko za zgodą Shopify; wyjątek załatwia się przez Partner Support [docs + forum].
- Aplikacja darmowa, która tylko łączy z płatnym kontem CookieZen, może zostać uznana za obchodzenie tego wymogu [forum, pracownik Shopify].

| Opcja | Plusy | Minusy |
|---|---|---|
| a) Shopify Billing dla klientów z Shopify, plany lustrzane do cennika CookieZen | Zgodne z regulaminem, płatność jednym kliknięciem z faktury Shopify | Prowizja Shopify (do sprawdzenia aktualna stawka). Backend `cmp-app` musi włączać i wyłączać stronę według webhooka `app_subscriptions/update`, czyli drugie źródło statusu subskrypcji. |
| b) Prośba o wyjątek i własny billing CookieZen | Jeden billing, jeden cennik | Niepewny wynik, ryzyko odrzucenia w review |
| c) Darmowy plan w aplikacji, płatne rozszerzenia przez Shopify Billing | Szybki start i opinie w App Store | Tak jak w a), plus decyzja, co jest darmowe |

Rekomendacja: zapytać Partner Support o wyjątek (b) równolegle z fazą 0, a planować pod (a). Status subskrypcji z Shopify mapować na ten sam stan „CMP wyłączony” co dziś, ze znacznikiem `window.__CMP_DISABLED`, żeby nie wprowadzać nowego znaczenia pól.

## Plan wdrożenia

### Faza 0: most i pilotaż (bez aplikacji)

1. Most Customer Privacy API w banerze (`cmp-app`): mapowanie, zdarzenie, ponowna synchronizacja i testy jednostkowe.
2. Sklep deweloperski z motywem Dawn i testowymi Web Pixels (Google & YouTube, Meta, własny piksel) oraz uprząż Playwright, jak `scripts/wordpress-pys-harness/`.
3. Pomiary na wyrenderowanym HTML:
   - realna kolejność `compliance_head` względem `content_for_header`;
   - czy loader łapie `asyncLoad` ScriptTagów;
   - zachowanie Google & YouTube przy Consent Mode;
   - nazwa i czas życia ciasteczka zgody Shopify;
   - wpływ na Lighthouse.
4. Przewodnik dla klientów pilotażowych: loader wklejony w `theme.liquid`, natywny baner Shopify usunięty, regiony i „Collected after consent” ustawione tak samo jak w CookieZen.
5. Zapytanie do Partner Support o billing.

Kryterium wyjścia: na sklepie testowym żaden Web Pixel nie wysyła danych przed zgodą, a po zgodzie wysyła; wyniki pomiarów są wpisane do tego dokumentu.

### Faza 1: aplikacja MVP (App Store)

1. Konto Partners i aplikacja z szablonu Shopify CLI. Zakresy minimalne: `read_themes` do kontroli stanu embedu, bez `write_themes`.
2. Theme app extension: app embed `compliance_head`, który drukuje synchroniczny `<script>` loadera z kluczem strony z metapola aplikacji. Wykrywa już obecny loader.
3. Ekran w adminie (App Bridge i Polaris), jedna strona:
   - połączenie ze stroną CookieZen (logowanie albo klucz strony; domena sklepu i domena `myshopify.com` przypisane do strony w panelu);
   - lista kontrolna: embed włączony w opublikowanym motywie (z deep linkiem), natywny baner usunięty, regiony i tryb zbierania danych, ręcznie oznaczone skrypty w motywie;
   - link do panelu CookieZen.
4. Backend w `cmp-app`:
   - OAuth i sesje;
   - webhooki zgodności (`customers/data_request`, `customers/redact`, `shop/redact`) z weryfikacją HMAC i 401 przy złym podpisie;
   - `app/uninstalled`;
   - billing według decyzji z sekcji „Rozliczenia”.
5. Panel CookieZen: platforma „Shopify” przy stronie, instrukcja instalacji i automatyczne wykrycie `myshopify.com` przez skaner.
6. Karta App Store po polsku i angielsku, polityka prywatności, zrzuty ekranu i wideo. Wzorzec redakcyjny po polsku, jak `appstore/opis.md` dla Shopera.
7. Review Shopify.

### Faza 2: blokowanie i jakość

1. Reguły blokowania Shopify (warstwy B i C) i włączenie autoblokowania dla stron Shopify po decyzji w `BLOCKING_ALGORITHM.md`.
2. Asystent motywu (warstwa D): skan `theme.liquid` i snippetów przez `read_themes`, lista trackerów i gotowe poprawki do wklejenia.
3. Tryb ścisły (warstwa E) z instrukcją i wykrywaniem dubli.
4. Zdarzenie zgody dla custom pixeli i gotowy kod pixela z Consent Mode (warstwa F). Najpierw potwierdzić `Shopify.analytics.publish` i ustawienie „Customer privacy: Not required” takiego pixela.
5. Built for Shopify: pomiary wydajności i wymogi UX.

### Faza 3: checkout i headless

1. Checkout UI extension: blok na thank-you i order status (wszystkie plany poza Starter) oraz baner na krokach płatności dla Plus (`collect_buyer_consent`).
2. Konta klientów: rozszerzenie z Customer Privacy API, przy własnej domenie kont.
3. Przewodnik Hydrogen: `ShopifyScripts consent={{mode: 'custom-banner', setup}}` z mostem CookieZen.

## Otwarte pytania do właściciela

1. Billing: Shopify Billing czy próba wyjątku (sekcja „Rozliczenia”).
2. Gdzie żyje kod aplikacji: w `cmp-app` obok Shopera czy w osobnym repo.
3. Czy włączamy autoblokowanie dla stron Shopify (warstwa C). To zmiana stanu produkcyjnego algorytmu.
4. Czy `sale_of_data` ma iść za `marketing`, czy za osobną kategorią w regionach USA.

## Źródła

Oficjalne strony znane z fragmentów wyników wyszukiwarki; daty tam, gdzie były widoczne.

**Shopify**
- https://shopify.dev/docs/api/customer-privacy
- https://shopify.dev/changelog/trackingconsent-landingpage-origreferrer-cookies-will-no-longer-be-set (2025-08-04)
- https://shopify.dev/docs/apps/build/online-store/theme-app-extensions/configuration
- https://shopify.dev/docs/storefronts/themes/best-practices/performance/load-critical-resources-before-content-for-header
- https://shopify.dev/changelog/posts/online-store-script-tags-deprecation
- https://shopify.dev/docs/apps/build/online-store/script-tag-deprecation/storefront
- https://shopify.dev/docs/apps/build/online-store/asset-legacy
- https://shopify.dev/docs/apps/build/checkout/capabilities
- https://shopify.dev/docs/api/checkout-ui-extensions/2025-07/target-apis/checkout-apis/customer-privacy-api
- https://shopify.dev/changelog/customerprivacy-api-now-available-for-customer-account-extensions (2025-05)
- https://shopify.dev/docs/api/web-pixels-api/pixel-privacy
- https://shopify.dev/changelog/pixels-now-support-more-customer-privacy-setting-configuration (2024-05-02)
- https://shopify.dev/docs/apps/launch/shopify-app-store/app-store-requirements
- https://shopify.dev/docs/apps/launch/built-for-shopify/requirements
- https://shopify.dev/docs/apps/build/compliance/privacy-law-compliance
- https://shopify.dev/docs/storefronts/headless/hydrogen/analytics/consent-3p
- https://shopify.dev/changelog/posts/hydrogen-april-2026-release (2026-04-09)
- https://help.shopify.com/en/manual/privacy-and-security/privacy/customer-privacy-settings/privacy-settings
- https://help.shopify.com/en/manual/reports-and-analytics/discrepancies/session-cookie-rotation
- https://help.shopify.com/en/manual/promoting-marketing/pixels/custom-pixels/manage
- Kod: npm `@shopify/hydrogen` 2026.10.2 (2026-10-20), `@shopify/hydrogen-react` 2026.4.4 (2026-09-29), GitHub `Shopify/ui-extensions` (`ThemeAppEmbedTarget`)

**Pandectes**
- https://help.pandectes.io/en/article/auto-blocker-setup-guide-zq5rdh/ (około 2025-05)
- https://help.pandectes.io/en/article/manual-script-blocking-guide-191jvg5/ (około 2024-12)
- https://help.pandectes.io/en/article/how-to-set-up-custom-blocking-rules-1j8kgrq/
- https://help.pandectes.io/en/article/use-pandectes-consent-event-with-shopify-custom-pixel-7l2qq3/
- https://help.pandectes.io/en/article/checkout-block-1v7pi7k/
- https://pandectes.io/blog/whats-new-in-pandectes-3-5-96/ (2023-02-02)
- https://pandectes.io/blog/whats-new-in-pandectes-4-0-0/ (2025-02-21)
- https://pandectes.io/certifications/built-for-shopify/
- https://docs.analyzify.com/pandectes-setup

**Inni dostawcy**
- https://support.cookiebot.com/hc/en-us/articles/360006184253-Shopify-installation
- https://support.consentmo.com/en/article/shopify-customer-privacy-api-integration-1284c47/
- https://support.consentmo.com/en/article/google-consent-mode-v2-integration-advanced-mode-1olxp4x/
- https://developer.onetrust.com/onetrust/docs/shopify
- https://www.cookieyes.com/blog/cookieyes-shopify-app-built-for-shopify/
