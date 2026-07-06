=== CookieZen ===
Contributors: cookiezen
Tags: cookie banner, cookie consent, cookie notice, GDPR, privacy
Requires at least: 5.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Baner zgód cookie i CMP dla RODO oraz Google Consent Mode v2. Automatyczne blokowanie skryptów, skaner cookies i dowody zgód.

== Description ==

CookieZen to polska platforma zarządzania zgodami (Consent Management Platform, CMP), która w kilka minut dodaje do Twojej witryny WordPress w pełni konfigurowalny baner cookie zgodny z RODO, dyrektywą ePrivacy oraz CCPA.

Wtyczka łączy Twoją stronę z kontem CookieZen za pomocą jednego klucza (Site Key). Cała logika — skanowanie cookies, klasyfikacja trackerów, blokowanie skryptów przed zgodą, zapis dowodów zgód i sygnały Google Consent Mode v2 — działa po stronie usługi CookieZen, więc wtyczka pozostaje lekka i szybka.

CookieZen to rozwiązanie stworzone z myślą o polskim rynku: polski panel, polska dokumentacja, polski support, faktura VAT i cennik w złotówkach. Sprawdza się zarówno na blogu, jak i w sklepie WooCommerce czy na dużej stronie firmowej.

**Dlaczego CookieZen**

* **Wdrożenie w 5 minut** — instalujesz wtyczkę, wklejasz Site Key z panelu CookieZen i baner jest od razu aktywny. Bez kodowania.
* **Automatyczny skaner cookies** — CookieZen przechodzi po kluczowych podstronach i wykrywa cookies oraz piksele śledzące (Google Analytics, Meta Pixel, TikTok), także te uruchamiane dopiero po zgodzie. Skan powtarza się automatycznie co 30 dni.
* **Blokowanie przed zgodą** — skrypty śledzące są blokowane, zanim użytkownik wyrazi zgodę — dokładnie tak, jak wymaga RODO.
* **Google Consent Mode v2** — wszystkie wymagane sygnały (`ad_storage`, `analytics_storage`, `ad_user_data`, `ad_personalization`) wysyłane są zanim załaduje się Google Tag Manager.
* **Bezpieczny dla e-commerce** — bramki płatności (Przelewy24, Stripe, PayPal), widgety dostawców (InPost, Furgonetka), reCAPTCHA i formularze działają zawsze. CookieZen blokuje tylko znane trackery, nie rozbija checkoutów.
* **Zero danych osobowych** — adresy IP są nieodwracalnie hashowane, nie przechowujemy danych osobowych odwiedzających.
* **Dowody zgód (Consent Proof)** — każda decyzja użytkownika rejestrowana z metadanymi (data, czas, kraj, język) — gotowe na kontrolę UODO.
* **Optymalizacja pod SEO** — boty wyszukiwarek dostają minimalną wersję skryptu bez interfejsu, więc baner nie psuje Core Web Vitals ani pozycjonowania.

== Features ==

**Zarządzanie zgodami**

* Baner zgód z opcjami Akceptuj / Odrzuć / Ustawienia dla RODO i CCPA.
* Automatyczne blokowanie skryptów i cookies niezbędnych do momentu wyrażenia zgody.
* Centrum preferencji — użytkownik zarządza zgodami wg kategorii (analityka, marketing, preferencje).
* Rejestr zgód z pełnymi metadanymi (Consent Proof) na potrzeby audytu i kontroli UODO.
* Google Consent Mode v2 — sygnały wysyłane przed załadowaniem GTM.
* Wsparcie dla Microsoft Advertising (Bing Ads) i Microsoft Clarity.

**Integracja z WordPress**

* **Automatyczna integracja z WP Consent API** — CookieZen pełni rolę menedżera zgód, a zgodne wtyczki (WooCommerce, Google Site Kit, WP Statistics, Pixel Manager) automatycznie respektują wybór dokonany w banerze, bez pisania kodu. Integracja aktywuje się sama, gdy na stronie działa wtyczka [WP Consent API](https://wordpress.org/plugins/wp-consent-api/).
* Działa z Google Tag Manager i każdym innym CMS-em lub builderem stron.

**Personalizacja i wydajność**

* Pełna personalizacja banera (kolory, teksty, pozycja) z poziomu panelu CookieZen.
* Loader ładowany z serwerów Edge (CDN), skompresowany do kilku kilobajtów.
* Wielojęzyczność — baner automatycznie w języku przeglądarki użytkownika.

**Skaner i statystyki**

* Automatyczny skaner cookies z powtórką co 30 dni.
* Statystyki akceptacji zgód w panelu (ilu zaakceptowało, odrzuciło, wybrało kategorie).
* Jedno konto, wiele domen — zarządzanie wszystkimi stronami z jednego panelu.

== Premium Features ==

CookieZen działa w modelu freemium. Plan Free (do 1 000 sesji miesięcznie) jest bezpłatny i bezterminowy. Wyższe plany odblokowują większe limity sesji i dodatkowe możliwości. [Zobacz cennik i porównaj plany](https://cookiezen.pl/?utm_source=wordpress&utm_medium=plugin&utm_campaign=premium_features#cennik).

* **Starter** — do 50 000 sesji / miesiąc.
* **Pro** — do 150 000 sesji / miesiąc.
* **Business** — do 400 000 sesji / miesiąc, rozliczenie nadwyżek w modelu pay-as-you-go.

Wszystkie plany obejmują automatyczny skaner, dowody zgód, Google Consent Mode v2 oraz polski support.

== Installation ==

= Instalacja z katalogu WordPress =

1. W panelu WordPress przejdź do **Wtyczki → Dodaj wtyczkę**.
2. Wyszukaj „CookieZen" i kliknij **Zainstaluj**, a następnie **Włącz**.

= Instalacja ręczna =

1. Pobierz plik ZIP wtyczki.
2. W panelu WordPress przejdź do **Wtyczki → Dodaj wtyczkę → Wyślij wtyczkę na serwer**.
3. Wybierz pobrany plik ZIP, kliknij **Zainstaluj teraz**, a po instalacji **Włącz wtyczkę**.

= Konfiguracja (Site Key) =

1. Załóż konto na [cookiezen.pl](https://cookiezen.pl/?utm_source=wordpress&utm_medium=plugin&utm_campaign=installation) i dodaj swoją domenę.
2. Skopiuj **Site Key** z panelu CookieZen (zakładka Integracja).
3. W panelu WordPress przejdź do **Ustawienia → CookieZen**, wklej Site Key i zapisz.
4. Baner zgód pojawi się na Twojej stronie automatycznie.

== Frequently Asked Questions ==

= Skąd wziąć Site Key? =

Site Key znajdziesz w panelu CookieZen, w zakładce Integracja swojej domeny. Załóż darmowe konto na cookiezen.pl, dodaj domenę i skopiuj klucz.

= Czy wtyczka jest darmowa? =

Tak. Plan Free (do 1 000 sesji miesięcznie) jest bezpłatny i bezterminowy. Wyższe plany rozszerzają limity sesji — szczegóły w cenniku na cookiezen.pl.

= Czy CookieZen spowalnia stronę? =

Nie. Loader jest skompresowany do kilku kilobajtów i ładuje się z serwerów Edge (CDN). Boty wyszukiwarek dostają minimalną wersję bez interfejsu, więc baner nie wpływa na Core Web Vitals ani na pozycjonowanie.

= Czy CookieZen integruje się z WP Consent API? =

Tak. Gdy na stronie aktywna jest wtyczka [WP Consent API](https://wordpress.org/plugins/wp-consent-api/), CookieZen automatycznie pełni rolę menedżera zgód. Wybór użytkownika w banerze (analityka, marketing, preferencje) jest przekazywany do zgodnych wtyczek (WooCommerce, Google Site Kit, WP Statistics, Pixel Manager), które respektują go bez dodatkowej konfiguracji. Integracja jest automatyczna — nie ma nic do włączania.

= Czy wtyczka działa bez połączenia z internetem? =

Nie. CookieZen jest usługą (Consent Management Platform) — baner, klasyfikacja cookies i zapis zgód działają w oparciu o serwery CookieZen. Wymagane jest aktywne konto i połączenie z internetem.

= Czy sama instalacja wtyczki zapewnia zgodność z RODO? =

Nie. Instalacja wtyczki to pierwszy krok. Zgodność zależy od poprawnej konfiguracji banera, sklasyfikowania cookies i dopasowania treści do faktycznych praktyk na Twojej stronie. Zapoznaj się z uwagą poniżej.

== External services ==

Ta wtyczka łączy Twoją stronę z usługą CookieZen, aby dostarczyć baner zgód i zarządzać zgodami użytkowników. Bez tego połączenia baner nie może działać — CookieZen jest platformą typu Consent Management Platform (usługą SaaS).

**Do czego dochodzi połączenie**

Wtyczka wstrzykuje na Twojej stronie skrypt (loader) z adresu:

`https://cz-cdn.com/api/cmp/loader?site_key=TWOJ_SITE_KEY`

Skrypt ładuje się przy każdej wizycie na stronie, ponieważ baner zgód musi zainicjować się i zablokować skrypty śledzące zanim uruchomią się inne wtyczki marketingowe.

**Jakie dane są przesyłane**

* **Site Key** — identyfikator Twojej strony w systemie CookieZen (podany przez Ciebie w ustawieniach wtyczki).
* Standardowe nagłówki żądania HTTP przeglądarki odwiedzającego (adres IP, User-Agent, Referer), niezbędne do dostarczenia loadera. Adresy IP są nieodwracalnie hashowane po stronie CookieZen i nie są przechowywane w formie umożliwiającej identyfikację.

**Dlaczego usługa zewnętrzna jest niezbędna**

Cała logika CMP — automatyczny skaner cookies, klasyfikacja trackerów, geolokalizacja dla różnych ram prawnych, sygnały Google Consent Mode v2 oraz zapis dowodów zgód — realizowana jest przez backend CookieZen.

Usługa jest dostarczana przez Semavo Solutions Sp. z o.o.
Polityka prywatności: https://cookiezen.pl/polityka-prywatnosci
Polityka cookies: https://cookiezen.pl/polityka-cookies
Regulamin: https://cookiezen.pl/regulamin

== Ciasteczka tworzone przez CookieZen ==

Wtyczka (poprzez loader) zapisuje wyłącznie ciasteczka niezbędne do zapamiętania decyzji użytkownika o zgodach:

* `cmp_consent_<site_key>` — przechowuje wybór użytkownika dotyczący zgód (kategorie: analityka, marketing, preferencje). Ważność: 1 rok. Nie zawiera danych osobowych.

Analogiczny wpis zapisywany jest w `localStorage` przeglądarki (`cmp:<site_key>`), aby przy powrocie na stronę nie wyświetlać banera ponownie.

= UWAGA: SAMA INSTALACJA TEJ WTYCZKI NIE SPRAWIA AUTOMATYCZNIE, ŻE TWOJA STRONA JEST ZGODNA Z RODO ORAZ CCPA. KAŻDA STRONA UŻYWA INNYCH COOKIES, WIĘC MUSISZ POPRAWNIE SKONFIGUROWAĆ BANER I SKLASYFIKOWAĆ COOKIES ORAZ ZADBAĆ O ODPOWIEDNIE ZAPISY W POLITYCE PRYWATNOŚCI I POLITYCE COOKIES. =

== Screenshots ==

1. Ustawienia wtyczki CookieZen w panelu WordPress — pole Site Key.
2. Baner zgód CookieZen na stronie odwiedzającego.
3. Panel CookieZen — statystyki akceptacji zgód i lista wykrytych cookies.

== Changelog ==

= 1.0.2 =
* Pierwsze wydanie w oficjalnym katalogu WordPress.org (wcześniejsze wersje dystrybuowane ręcznie jako plik ZIP z panelu CookieZen).
* [Add] Integracja przez Site Key — wstrzykiwanie loadera CookieZen w sekcji &lt;head&gt;.
* [Add] Automatyczna integracja z WP Consent API (rola menedżera zgód).
* [Add] Link „Ustawienia" na liście wtyczek prowadzący do konfiguracji.
* [Enhancement] Optymalizacja priorytetu wstrzykiwania skryptu dla zgodności z motywami (m.in. Elementor Pro).

== Upgrade Notice ==

= 1.0.2 =
Pierwsze wydanie w katalogu WordPress.org. Od teraz aktualizacje wtyczki instalujesz jednym kliknięciem z panelu WordPress.
