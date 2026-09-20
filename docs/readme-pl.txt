=== CookieZen ===
Contributors: cookiezen
Tags: cookie banner, cookie consent, cookie notice, GDPR, privacy
Requires at least: 5.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Baner zgód cookie i CMP dla RODO. Google Consent Mode v2, automatyczny skaner cookies, dowody zgód i statystyki akceptacji.

== Description ==

CookieZen zarządza zgodami odwiedzających Twoją stronę, wysyła pełne sygnały Consent Mode do Google i Microsoft, automatycznie skanuje cookies i zapisuje dowody zgód na wypadek kontroli. Wdrożenie zajmuje kilka minut.

Wtyczka łączy stronę z kontem CookieZen jednym kluczem (Site Key). Cała logika, czyli skaner cookies, klasyfikacja trackerów, sygnały Consent Mode, rejestr zgód i statystyki, działa po stronie usługi CookieZen, więc wtyczka pozostaje lekka. Wygląd i treść banera zmieniasz w panelu, bez dotykania motywu i bez wstawiania czegokolwiek do szablonu.

= Co nas wyróżnia =

**Automatyczny skaner cookies**
CookieZen przechodzi po kluczowych podstronach i wykrywa pliki cookie oraz piksele śledzące, także te uruchamiane dopiero po zgodzie. Skan powtarza się automatycznie co 30 dni, więc lista nie zostaje w tyle za zmianami na stronie.

**Google Consent Mode v2 bez konfiguracji**
Wszystkie wymagane sygnały trafiają do tagów Google, zanim te zaczną zbierać dane. Obsługujemy też url_passthrough i ads_data_redaction, więc Google Ads modeluje konwersje także wtedy, gdy odwiedzający nie wyrazi zgody na cookies. Nie musisz pisać własnych poleceń gtag ani niczego zmieniać w Google Tag Managerze.

**Microsoft UET Consent Mode i zgody w Clarity**
Obsługujemy Consent Mode dla Microsoft Universal Event Tracking (UET), więc płatne kampanie w Microsoft Advertising mierzą konwersje zgodnie z decyzją użytkownika. Poprawny sygnał zgody przekazujemy również do Microsoft Clarity. Nie musisz łączyć dwóch narzędzi ani dopisywać kodu ręcznie.

**Integracja z WP Consent API**
Wtyczka WP Consent API sama nie zbiera zgód, tylko udostępnia je innym wtyczkom, więc bez menedżera zgód, który ją zasila, wtyczki zgodne z tym standardem zachowują się tak, jakby zgoda została udzielona. CookieZen przejmuje tę rolę automatycznie: decyzja odwiedzającego trafia do zgodnych wtyczek, takich jak WooCommerce, Google Site Kit, WP Statistics czy Pixel Manager, zarówno w JavaScripcie, jak i w kodzie PHP. Nie ma nic do włączania.

**Dowody zgód i statystyki**
Każda decyzja odwiedzającego jest zapisywana z datą, godziną, krajem i językiem, co daje Ci dowód zgody na wypadek kontroli. Adresy IP hashujemy nieodwracalnie, więc nie przechowujemy danych osobowych osób odwiedzających Twoją stronę. W panelu widzisz też współczynnik akceptacji zgód, czyli ile ruchu realnie trafia do Twojej analityki i reklam.

**Wiele domen w jednej cenie**
Prowadzisz kilka stron albo dodatkowy sklep? Zarządzasz nimi z jednego panelu w ramach jednego planu, bez osobnych kont i osobnych opłat.

**Polskie narzędzie**
Polska firma, panel i dokumentacja po polsku, wsparcie po polsku. Znamy specyfikę rynku i wystawiamy fakturę VAT w PLN.

= Cennik =

Wtyczka jest darmowa. Plany usługi CookieZen różnią się wyłącznie limitem sesji, a nie zakresem funkcji: skaner cookies, Consent Mode v2, rejestr zgód i statystyki dostajesz w komplecie od pierwszego dnia, także na bezterminowym planie Free do 1 000 sesji miesięcznie. Plany płatne zaczynają się od 49 zł netto miesięcznie za 50 000 sesji, możesz je zmienić lub anulować w dowolnym momencie, a rejestracja nie wymaga podania danych karty.

== Installation ==

1. Zainstaluj i włącz wtyczkę CookieZen.
2. Załóż konto na [cookiezen.pl](https://cookiezen.pl/register?utm_source=wordpress&utm_medium=plugin&utm_campaign=installation) i dodaj swoją domenę.
3. Skopiuj Site Key z zakładki Integracja w panelu CookieZen.
4. Wejdź w Ustawienia > CookieZen, wklej Site Key i zapisz zmiany.

Baner pojawia się na wszystkich podstronach i nie wymaga wstawiania niczego do motywu.

= Jak sprawdzić, że działa =

Otwórz stronę w oknie prywatnym przeglądarki. Przy pierwszej wizycie powinien pokazać się baner, a po dokonaniu wyboru powinien zniknąć i nie wracać przy kolejnych odsłonach. W panelu CookieZen, w statystykach, decyzja pojawi się w ciągu kilku minut.

Jeśli baner się nie pokazuje, sprawdź w ustawieniach wtyczki, czy Site Key jest zapisany, i upewnij się, że w panelu CookieZen dodana domena zgadza się z adresem strony.

== Frequently Asked Questions ==

= Gdzie znajdę Site Key? =

Site Key jest w panelu CookieZen, w zakładce Integracja Twojej domeny. Załóż darmowe konto na cookiezen.pl, dodaj domenę i skopiuj klucz.

= Czy wtyczka jest darmowa? =

Tak, wtyczka jest darmowa. Do działania potrzebuje konta w usłudze CookieZen, która ma bezterminowy plan Free do 1 000 sesji miesięcznie. Wyższe plany rozszerzają limit sesji.

= Czy CookieZen integruje się z WP Consent API? =

Tak, automatycznie. Gdy wtyczka [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) jest aktywna, CookieZen pełni rolę menedżera zgód i przekazuje decyzję odwiedzającego do zgodnych wtyczek, także do sprawdzeń wykonywanych po stronie PHP. Nie ma nic do skonfigurowania.

= Czy wtyczka działa bez połączenia z internetem? =

Nie. CookieZen jest usługą, czyli platformą zarządzania zgodami. Baner, klasyfikacja cookies i rejestr zgód działają w oparciu o serwery CookieZen, więc wymagane jest aktywne konto i połączenie z siecią.

= Czy mogę zmienić wygląd i treść banera? =

Tak, w całości z panelu CookieZen. Zmienisz tam m.in. kolory, logo, układ, treści oraz własny CSS. Zmiany trafiają na stronę bez aktualizowania wtyczki.

= Czy sama instalacja zapewnia zgodność z RODO? =

Nie. Instalacja to pierwszy krok. Zgodność zależy od poprawnej konfiguracji banera, sklasyfikowania wykrytych cookies i dopasowania treści polityk do faktycznych praktyk na Twojej stronie.

Skrypty, które mają czekać na zgodę, trzeba jeszcze oznaczyć w kodzie strony albo warunkować zgodą w menedżerze tagów. Opisuje to dokumentacja CookieZen.

== External services ==

Ta wtyczka łączy Twoją stronę z usługą CookieZen, aby dostarczyć baner zgód i zarządzać zgodami odwiedzających. Bez tego połączenia baner nie może działać, ponieważ CookieZen jest platformą zarządzania zgodami dostarczaną jako usługa.

**Do czego dochodzi połączenie**

Wtyczka wstawia na Twojej stronie skrypt startowy z adresu:

`https://cz-cdn.com/api/cmp/loader?site_key=TWOJ_SITE_KEY`

Skrypt ładuje się przy każdej wizycie, ponieważ baner musi zainicjować się i przekazać sygnały zgody, zanim uruchomią się narzędzia analityczne i marketingowe.

**Jakie dane są przesyłane**

* Site Key, czyli identyfikator Twojej strony w systemie CookieZen, podany przez Ciebie w ustawieniach wtyczki.
* Standardowe nagłówki żądania HTTP przeglądarki odwiedzającego (adres IP, User-Agent, Referer), niezbędne do dostarczenia skryptu. Adresy IP są nieodwracalnie hashowane po stronie CookieZen i nie są przechowywane w formie umożliwiającej identyfikację.

**Dlaczego usługa zewnętrzna jest niezbędna**

Cała logika platformy, czyli automatyczny skaner cookies, klasyfikacja trackerów, geolokalizacja dla różnych ram prawnych, sygnały Google Consent Mode v2 oraz rejestr dowodów zgód, realizowana jest przez backend CookieZen.

Usługę dostarcza Semavo Solutions Sp. z o.o.
Polityka prywatności: https://cookiezen.pl/polityka-prywatnosci
Polityka cookies: https://cookiezen.pl/polityka-cookies
Regulamin: https://cookiezen.pl/regulamin

== Ciasteczka tworzone przez CookieZen ==

Wtyczka zapisuje jedno ciasteczko, `cmp_consent_<site_key>`, w którym przechowuje wybór odwiedzającego w kategoriach analityka, marketing i preferencje. Ciasteczko jest ważne rok i nie zawiera danych osobowych.

Analogiczny wpis zapisywany jest w `localStorage` przeglądarki (`cmp:<site_key>`).

== Changelog ==

= 1.0.4 =
* Pierwsze wydanie w katalogu WordPress.org. Wcześniejsze wersje były dystrybuowane ręcznie jako plik ZIP pobierany z panelu CookieZen.
* [Add] Interfejs wtyczki jest przetłumaczalny, z polskim tłumaczeniem w zestawie.
* [Fix] Adres strony wtyczki w nagłówku prowadzi do działającej strony.
* [Fix] Adres skryptu startowego jest escapowany przy wypisywaniu do kodu strony.

= 1.0.3 =
* [Fix] Integracja z WP Consent API działa także po stronie PHP. Wtyczka deklaruje tryb zgody optin przez filtr wp_get_consent_type, dzięki czemu funkcja wp_has_consent() respektuje decyzję odwiedzającego również w kodzie serwerowym. Wcześniej działała wyłącznie warstwa JavaScript, a sprawdzenia po stronie PHP przepuszczały skrypty mimo odmowy zgody.

= 1.0.2 =
* [Add] Integracja przez Site Key, czyli wstawianie skryptu startowego CookieZen w sekcji head.
* [Add] Automatyczna integracja z WP Consent API w roli menedżera zgód.
* [Add] Link Ustawienia na liście wtyczek, prowadzący do konfiguracji.
* [Enhancement] Dobór priorytetu wstawiania skryptu pod kątem zgodności z motywami, w tym z Elementor Pro.

== Upgrade Notice ==

= 1.0.4 =
Pierwsze wydanie w katalogu WordPress.org. Od teraz aktualizacje instalujesz jednym kliknięciem z panelu WordPress.
