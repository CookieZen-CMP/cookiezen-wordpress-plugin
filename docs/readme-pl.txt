=== CookieZen ===
Contributors: cookiezen
Tags: cookie banner, cookie consent, GDPR, google consent mode, cookie notice
Requires at least: 5.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Baner zgód cookie i CMP dla RODO. Google Consent Mode v2, automatyczny skaner cookies, dowody zgód i statystyki akceptacji.

== Description ==

CookieZen zarządza zgodami odwiedzających Twoją stronę, wysyła pełne sygnały Consent Mode do Google i Microsoft, automatycznie skanuje cookies i zapisuje dowody zgód na wypadek kontroli. Wdrożenie zajmuje kilka minut.

Wtyczka łączy stronę z kontem CookieZen. Cała logika, czyli skaner cookies, klasyfikacja trackerów, sygnały Consent Mode, rejestr zgód i statystyki, działa po stronie usługi CookieZen, więc wtyczka pozostaje lekka. Wygląd i treść banera zmieniasz w panelu, bez dotykania motywu i bez wstawiania czegokolwiek do kodu szablonu.

= Co nas wyróżnia =

**Automatyczny skaner cookies**
CookieZen przechodzi po kluczowych podstronach i wykrywa pliki cookie oraz piksele śledzące, także te uruchamiane dopiero po zgodzie. Skan powtarza się automatycznie co 30 dni, więc lista nie zostaje w tyle za zmianami na stronie.

**Google Consent Mode v2 bez konfiguracji**
Wszystkie wymagane sygnały trafiają do tagów Google, zanim te zaczną zbierać dane. Obsługujemy też url_passthrough i ads_data_redaction, więc Google Ads modeluje konwersje także wtedy, gdy odwiedzający nie wyrazi zgody na cookies. Nie musisz pisać własnych poleceń gtag ani niczego zmieniać w Google Tag Managerze.

**Microsoft UET Consent Mode i zgody w Clarity**
Obsługujemy Consent Mode dla Microsoft Universal Event Tracking (UET), więc płatne kampanie w Microsoft Advertising mierzą konwersje zgodnie z decyzją użytkownika. Poprawny sygnał zgody przekazujemy również do Microsoft Clarity. Nie musisz łączyć dwóch narzędzi ani dopisywać kodu ręcznie.

**Integracja z WP Consent API**
Wtyczka WP Consent API sama nie zbiera zgód, tylko udostępnia je innym wtyczkom, więc bez menedżera zgód, który ją zasila, wtyczki zgodne z tym standardem zachowują się tak, jakby zgoda została udzielona. CookieZen przejmuje tę rolę automatycznie: decyzja odwiedzającego trafia do zgodnych wtyczek, takich jak WooCommerce, Google Site Kit, WP Statistics czy Pixel Manager, zarówno w JavaScripcie, jak i w kodzie PHP.

**Meta Pixel i Conversions API z PixelYourSite czekają na zgodę**
Jeśli korzystasz z PixelYourSite, CookieZen wstrzymuje jego piksele, zdarzenia wysyłane z serwera (Conversions API) i ciasteczka śledzące, dopóki odwiedzający nie wyrazi zgody na marketing, bez żadnej konfiguracji.

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

Otwórz stronę w oknie prywatnym przeglądarki. Przy pierwszej wizycie powinien pokazać się baner cookies, a po dokonaniu wyboru powinien zniknąć i nie wracać przy kolejnych odsłonach. W panelu CookieZen, w statystykach, decyzja pojawi się w ciągu kilku minut.

Jeśli baner się nie pokazuje, sprawdź w ustawieniach wtyczki, czy Site Key jest zapisany, i upewnij się, że w panelu CookieZen dodana domena zgadza się z adresem strony.

== Frequently Asked Questions ==

= Gdzie znajdę Site Key? =

Site Key jest w panelu CookieZen, w zakładce Integracja Twojej domeny. Załóż darmowe konto na cookiezen.pl, dodaj domenę i skopiuj klucz.

= Czy wtyczka jest darmowa? =

Tak, wtyczka jest darmowa. Do działania potrzebuje konta w usłudze CookieZen, która ma bezterminowy plan Free do 1 000 sesji miesięcznie. Wyższe plany rozszerzają limit sesji.

= Czy mogę przetestować CookieZen za darmo? =

Tak. Po rejestracji masz 14 dni pełnej wersji usługi, bez podawania danych karty. Potem wybierasz plan płatny albo przechodzisz na bezterminowy plan Free dla stron do 1 000 sesji miesięcznie.

= Czy jedno konto obsłuży kilka stron? =

Tak. Jedno konto i jeden plan obejmują kilka stron, bez osobnych kont i osobnych opłat, a limit sesji planu jest wspólny dla wszystkich. Na każdej stronie instalujesz wtyczkę i wklejasz Site Key danej domeny.

= Czym jest Google Consent Mode v2 i czy go potrzebuję? =

Google Consent Mode v2 przekazuje tagom Google informację, czy odwiedzający zgodził się na cookies analityczne i reklamowe. Google wymaga tych sygnałów dla odwiedzających z Europejskiego Obszaru Gospodarczego, jeśli chcesz dalej korzystać z reklam spersonalizowanych i pomiaru konwersji w Google Ads. CookieZen wysyła je wszystkie automatycznie, zanim tagi Google się uruchomią, więc nie musisz niczego ustawiać w Google Tag Managerze ani w kodzie gtag.

= Czy CookieZen obsługuje Microsoft Advertising (UET) i Clarity? =

Tak, automatycznie. CookieZen wysyła sygnały zgody do Microsoft Universal Event Tracking (UET) i do Microsoft Clarity, więc kampanie w Microsoft Advertising mierzą konwersje zgodnie z decyzją odwiedzającego. Nie musisz dopisywać kodu ani niczego łączyć.

= Czy sama instalacja zapewnia zgodność z RODO? =

Nie. Instalacja to pierwszy krok. Zgodność zależy od poprawnej konfiguracji banera, sklasyfikowania wykrytych cookies i dopasowania treści polityk do faktycznych praktyk na Twojej stronie.

Skrypty, które mają czekać na zgodę, trzeba jeszcze oznaczyć w kodzie strony albo warunkować zgodą w menedżerze tagów. Opisuje to dokumentacja CookieZen.

= Czy CookieZen spowolni moją stronę? =

Skrypt, który musi wykonać się jako pierwszy, jest mały i lekki. Uruchamia się przed resztą strony, ponieważ musi dotrzeć do tagów Google, zanim one wystartują. Pełny baner ładuje się asynchronicznie, więc strona na niego nie czeka. Wtyczka nie dodaje tabel w bazie danych i przy każdej odsłonie czyta tylko własne ustawienia.

= Czy CookieZen działa z WooCommerce i wtyczkami cache? =

Tak. Baner działa na każdej stronie sklepu, także w koszyku i w kasie. Gdy wtyczka WP Consent API jest aktywna, funkcje WooCommerce zgodne z tym standardem respektują decyzję odwiedzającego.

Skrypt startowy jest oznaczony tak, żeby optymalizatory, takie jak WP Rocket, LiteSpeed Cache i Cloudflare Rocket Loader, go nie opóźniały, a po aktualizacji wtyczka jednorazowo czyści page cache popularnych wtyczek. Jeśli hosting albo CDN przechowuje całe strony (np. Cloudflare APO), wyczyść ten cache samodzielnie po aktualizacji.

= Czy CookieZen integruje się z WP Consent API? =

Tak, automatycznie. Gdy wtyczka [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) jest aktywna, CookieZen pełni rolę menedżera zgód i przekazuje decyzję odwiedzającego do zgodnych wtyczek, także do sprawdzeń wykonywanych po stronie PHP. Nie wymaga to dodatkowej konfiguracji.

= Czy CookieZen współpracuje z PixelYourSite? =

Tak, bez konfiguracji: PixelYourSite czeka na zgodę na marketing w przeglądarce i na serwerze. Kod, który sam dodasz w sekcji Head & Footer PixelYourSite, nie jest tym objęty. Od PixelYourSite 11.4.3 wycofanie zgody zatrzymuje śledzenie jeszcze na tej samej stronie, w starszych wersjach od następnej. Odwiedzający spoza regionów, w których pokazujesz baner, są mierzeni jak dotąd. Gdy subskrypcja CookieZen jest nieaktywna i baner się nie wyświetla, integracja nie wstrzymuje PixelYourSite.

= Czy odwiedzający może później zmienić decyzję? =

Tak. Po pierwszej decyzji mały przycisk w rogu strony ponownie otwiera ustawienia cookies. W panelu CookieZen przeniesiesz go do drugiego rogu albo go ukryjesz; jeśli go ukryjesz, dodaj na stronie link do `#cookiezen-settings`, na przykład w stopce, żeby odwiedzający mógł dalej zmienić swój wybór.

= Czy baner jest dostępny w innych językach? =

Tak. Języki, których potrzebujesz, dodajesz w panelu, z gotowymi tekstami w 27 językach, które możesz edytować. Baner wyświetla się w języku ustawionym na stronie, o ile dodasz ten język w panelu, a w przeciwnym razie w języku domyślnym, więc działa też z wtyczkami wielojęzycznymi, które zmieniają język strony.

= Czy mogę zmienić wygląd i treść banera? =

Tak, w całości z panelu CookieZen. Zmienisz tam m.in. kolory, logo, układ, treści oraz własny CSS. Zmiany trafiają na stronę bez aktualizowania wtyczki.

= Jakie ciasteczka zapisuje CookieZen? =

Wybór odwiedzającego jest przechowywany przez rok w ciasteczku `cmp_consent_<site_key>`, z analogicznym wpisem w localStorage przeglądarki. Zawiera wybór i losowy identyfikator zgody, bez imienia, adresu e-mail i adresu IP.

Na stronach z PixelYourSite wtyczka zapisuje też `cmp_pys_<site_key>`, sesyjne ciasteczko, które mówi serwerowi, czy PixelYourSite może wysyłać zdarzenia z serwera. Nie zawiera danych osobowych i znika z końcem sesji przeglądarki albo po wycofaniu zgody.

CookieZen przechowuje też kilka małych wpisów technicznych w localStorage i sessionStorage przeglądarki, na przykład do liczenia sesji albo, jeśli pokazujesz baner tylko w wybranych regionach, do zapamiętania kraju odwiedzającego na czas wizyty. Gdy wtyczka WP Consent API jest aktywna, zapisuje też wybór odwiedzającego we własnych ciasteczkach.

= Co dokładnie wtyczka wczytuje i wysyła? =

Wtyczka dodaje do stron jeden skrypt startowy z adresu `https://cz-cdn.com/api/cmp/loader?site_key=TWOJ_SITE_KEY`. Ładuje się przy każdej wizycie, bo baner musi wystartować i przekazać sygnały zgody, zanim zrobią to narzędzia analityczne i marketingowe.

Żądanie niesie Twój Site Key, który identyfikuje stronę w CookieZen, oraz standardowe nagłówki żądania przeglądarki odwiedzającego (adres IP, User-Agent, Referer), niezbędne do dostarczenia skryptu. Adresy IP są nieodwracalnie hashowane po stronie CookieZen i nie są przechowywane w formie umożliwiającej identyfikację. Skaner cookies, klasyfikacja trackerów, geolokalizacja dla różnych ram prawnych, sygnały Google Consent Mode i rejestr zgód działają w usłudze CookieZen.

= Czy wtyczka działa bez połączenia z internetem? =

Nie. CookieZen jest usługą, czyli platformą zarządzania zgodami. Baner, klasyfikacja cookies i rejestr zgód działają w oparciu o serwery CookieZen, więc wymagane jest aktywne konto i połączenie z siecią.

== External services ==

CookieZen jest platformą zarządzania zgodami dostarczaną jako usługa. Wtyczka łączy Twoją stronę z usługą CookieZen pod adresem cz-cdn.com, żeby wyświetlić baner zgód i zapisać zgody odwiedzających; bez tego połączenia baner nie może działać. Przy każdej wizycie wysyła Twój Site Key i standardowe nagłówki żądania przeglądarki odwiedzającego (adres IP, User-Agent, Referer), a adresy IP są nieodwracalnie hashowane po stronie CookieZen. Szczegóły opisuje FAQ.

Usługę dostarcza Semavo Solutions Sp. z o.o.: [Polityka prywatności](https://cookiezen.pl/polityka-prywatnosci), [Polityka cookies](https://cookiezen.pl/polityka-cookies), [Regulamin](https://cookiezen.pl/regulamin).

== Changelog ==

= 1.0.5 =
* [Add] Integracja z PixelYourSite: dopóki odwiedzający nie wyrazi zgody na marketing, śledzenie PixelYourSite nie działa w przeglądarce, zdarzenia z serwera (Conversions API) nie są wysyłane, a ciasteczka śledzące nie są zapisywane; po zgodzie startuje na tej samej stronie; po wycofaniu zgody jego ciasteczka są usuwane, a od PixelYourSite 11.4.3 milknie jeszcze na tej samej stronie. Przy nieaktywnej subskrypcji CookieZen integracja nie wstrzymuje PixelYourSite.
* [Add] Jednorazowe czyszczenie page cache popularnych wtyczek po aktualizacji.
* [Enhancement] Skrypt loadera jest oznaczony tak, żeby optymalizatory JavaScript go nie odraczały ani nie opóźniały.

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

= 1.0.5 =
PixelYourSite czeka teraz na zgodę na marketing, w przeglądarce i na serwerze. Page cache popularnych wtyczek zostaje jednorazowo wyczyszczony po aktualizacji.

= 1.0.4 =
Pierwsze wydanie w katalogu WordPress.org. Od teraz aktualizacje instalujesz jednym kliknięciem z panelu WordPress.
