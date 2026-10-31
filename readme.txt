=== CookieZen ===
Contributors: cookiezen
Tags: cookie banner, cookie consent, GDPR, google consent mode, cookie notice
Requires at least: 5.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookie consent banner and GDPR consent platform with Google Consent Mode v2, an automatic cookie scanner and stored consent records.

== Description ==

CookieZen manages the consent of everyone who visits your site, sends the full set of Consent Mode signals to Google and Microsoft, scans your pages for cookies and stores every consent decision in case of an audit. Setting it up takes a few minutes.

The plugin connects your site to a CookieZen account. All of the logic, meaning the cookie scanner, tracker classification, Consent Mode signals, the consent register and the statistics, runs on the CookieZen service, so the plugin itself stays small. You change the look and the wording of the banner in the panel, without touching your theme and without pasting anything into your template code.

= What makes CookieZen different =

**Automatic cookie scanner**
CookieZen crawls the key pages of your site and detects cookies and tracking pixels, including the ones that only fire after consent is given. The scan repeats every 30 days, so the list does not fall behind the changes you make to the site.

**Google Consent Mode v2 with no configuration**
Every required signal reaches Google tags before they start collecting data. url_passthrough and ads_data_redaction are supported as well, so Google Ads can still model conversions when a visitor declines cookies. You do not have to write your own gtag calls or change anything in Google Tag Manager.

**Microsoft UET Consent Mode and consent in Clarity**
Consent Mode for Microsoft Universal Event Tracking (UET) is supported, so paid campaigns in Microsoft Advertising measure conversions in line with the visitor's decision. The correct consent signal is passed to Microsoft Clarity too. There is nothing to connect and no code to add by hand.

**WP Consent API integration**
The WP Consent API plugin does not collect consent itself, it only exposes it to other plugins, so without a consent manager feeding it, plugins that follow the standard behave as if consent had been granted. CookieZen takes that role automatically: the visitor's decision reaches compatible plugins such as WooCommerce, Google Site Kit, WP Statistics or Pixel Manager, both in JavaScript and in PHP code.

**Meta Pixel and Conversions API from PixelYourSite wait for consent**
If you use PixelYourSite, CookieZen holds its pixels, its server-side events (Conversions API) and its tracking cookies until the visitor gives marketing consent, with no configuration.

**Consent records and statistics**
Every decision is stored with its date, time, country and language, which gives you proof of consent in case of an audit. IP addresses are irreversibly hashed, so no personal data about the people visiting your site is kept. The panel also shows your consent acceptance rate, meaning how much of your traffic actually reaches your analytics and your ads.

**Several domains on one plan**
Running more than one site, or a shop next to a company page? You manage them from a single panel within one plan, without separate accounts and separate fees.

**A Polish product**
A Polish company, a panel and documentation in Polish, and support in Polish. We know this market and we issue a VAT invoice in PLN.

= Pricing =

The plugin is free. CookieZen plans differ only in their session limit, not in what they include: the cookie scanner, Consent Mode v2, the consent register and the statistics are all there from day one, including on the permanent Free plan for up to 1,000 sessions per month. Paid plans start at 49 PLN net per month for 50,000 sessions, you can change or cancel them at any time, and signing up does not require card details.

== Installation ==

1. Install and activate the CookieZen plugin.
2. Create an account at [cookiezen.pl](https://cookiezen.pl/register?utm_source=wordpress&utm_medium=plugin&utm_campaign=installation) and add your domain.
3. Copy the Site Key from the Integration tab in the CookieZen panel.
4. Go to Settings > CookieZen, paste the Site Key and save the changes.

The banner appears on every page of the site and does not require adding anything to your theme.

= How to check that it works =

Open your site in a private browser window. The cookie banner should appear on the first visit, and once you make a choice it should disappear and stay away on the following page views. The decision shows up in the statistics in the CookieZen panel within a few minutes.

If the banner does not appear, check that the Site Key is saved in the plugin settings and make sure the domain added in the CookieZen panel matches the address of your site.

== Frequently Asked Questions ==

= Where do I find the Site Key? =

The Site Key is in the CookieZen panel, in the Integration tab of your domain. Create a free account at cookiezen.pl, add your domain and copy the key.

= Is the plugin free? =

Yes, the plugin is free. It needs an account in the CookieZen service, which has a permanent Free plan for up to 1,000 sessions per month. Higher plans raise the session limit.

= Can I try CookieZen for free? =

Yes. After signing up you get 14 days of the full service, with no card details required. After that you choose a paid plan or switch to the permanent Free plan for sites with up to 1,000 sessions per month.

= Can I use one account for several sites? =

Yes. One account and one plan cover several sites, without separate accounts and separate fees, and the plan's session limit is shared by all of them. On each site you install the plugin and paste the Site Key of that domain.

= What is Google Consent Mode v2 and do I need it? =

Google Consent Mode v2 tells Google tags whether a visitor agreed to analytics and advertising cookies. Google requires these signals for visitors from the European Economic Area if you want to keep using personalised advertising and conversion measurement in Google Ads. CookieZen sends all of them automatically, before Google tags start, so there is nothing to set up in Google Tag Manager or in your gtag code.

= Does CookieZen support Microsoft Advertising (UET) and Clarity? =

Yes, automatically. CookieZen sends the consent signals to Microsoft Universal Event Tracking (UET) and to Microsoft Clarity, so your Microsoft Advertising campaigns measure conversions in line with the visitor's decision. There is no code to add and nothing to connect.

= Does installing the plugin make my site GDPR compliant? =

No. Installing it is the first step. Compliance depends on configuring the banner correctly, classifying the cookies that were found, and on whether your policies describe what actually happens on your site.

Scripts that are meant to wait for consent also have to be marked in your page code or gated by consent in your tag manager. The CookieZen documentation covers this.

= Will CookieZen slow down my site? =

The script that has to run first is small and lightweight. It runs before the rest of the page because it has to reach Google tags before they start. The full banner loads asynchronously, so your page does not wait for it. The plugin adds no database tables and on each page view reads only its own settings.

= Does CookieZen work with WooCommerce and caching plugins? =

Yes. The banner works on every page of your shop, including the cart and the checkout. When the WP Consent API plugin is active, WooCommerce features that follow that standard respect the visitor's decision.

The loader is marked so that optimizers such as WP Rocket, LiteSpeed Cache and Cloudflare Rocket Loader do not delay it, and after an update the plugin clears the page cache of common caching plugins once. If your host or CDN caches whole pages (for example Cloudflare APO), clear that cache yourself after updating.

= Does CookieZen work with the WP Consent API? =

Yes, automatically. When the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) plugin is active, CookieZen acts as the consent manager and passes the visitor's decision to compatible plugins, including the checks made in PHP. It needs no extra configuration.

= Does CookieZen work with PixelYourSite? =

Yes, with no configuration: PixelYourSite waits for marketing consent in the browser and on the server. Code you add yourself in PixelYourSite's Head & Footer section is not covered. With PixelYourSite 11.4.3 or newer, withdrawing consent stops its tracking on the same page, with older versions from the next page. Visitors outside the regions where you show the banner are measured as usual. While your CookieZen subscription is inactive and the banner is not shown, the integration does not hold PixelYourSite back.

= Can visitors change their decision later? =

Yes. After the first decision, a small button in the corner of the page opens the cookie settings again. You can move it to the other corner or hide it in the CookieZen panel; if you hide it, add a link to `#cookiezen-settings` on your site, for example in the footer, so that visitors can still change their choice.

= Is the banner available in other languages? =

Yes. You add the languages you need in the panel, with ready-made texts in 27 languages that you can edit. The banner shows the language set on the page, as long as you have added that language in the panel, and otherwise your default language, so it also follows multilingual plugins that switch the page language.

= Can I change how the banner looks and what it says? =

Yes, entirely from the CookieZen panel. You can change the colors, the logo, the layout, the wording and your own CSS. The changes reach your site without updating the plugin.

= What cookies does CookieZen set? =

The visitor's choice is stored in the `cmp_consent_<site_key>` cookie for one year, with an equivalent entry in the browser's localStorage. It holds the choice and a random consent ID, with no name, e-mail address or IP address.

On sites with PixelYourSite the plugin also sets `cmp_pys_<site_key>`, a session cookie that tells the server whether PixelYourSite may send server-side events. It contains no personal data and is removed when the browser session ends or when consent is withdrawn.

CookieZen also keeps a few small technical entries in the browser's localStorage and sessionStorage, for example to count sessions or, if you show the banner only in selected regions, to remember the visitor's country during a visit. When the WP Consent API plugin is active, that plugin also stores the visitor's choice in its own cookies.

= What exactly does the plugin load and send? =

The plugin adds one loader script to your pages, from `https://cz-cdn.com/api/cmp/loader?site_key=YOUR_SITE_KEY`. It loads on every visit, because the banner has to start and pass the consent signals before your analytics and marketing tools do.

The request carries your Site Key, which identifies your site in CookieZen, and the standard request headers of the visitor's browser (IP address, User-Agent, Referer) that are needed to deliver the script. IP addresses are irreversibly hashed on the CookieZen side and are not stored in a form that allows identification. The cookie scanner, tracker classification, geolocation for different legal frameworks, Google Consent Mode signals and the consent register all run on the CookieZen service.

= Does the plugin work without an internet connection? =

No. CookieZen is a service, a consent management platform. The banner, the cookie classification and the consent register rely on CookieZen servers, so an active account and a network connection are required.

== External services ==

CookieZen is a consent management platform delivered as a service. The plugin connects your site to the CookieZen service at cz-cdn.com to show the consent banner and record your visitors' consent; without that connection the banner cannot work. On every visit it sends your Site Key and the standard request headers of the visitor's browser (IP address, User-Agent, Referer), and IP addresses are irreversibly hashed on the CookieZen side. The FAQ describes it in more detail.

The service is provided by Semavo Solutions Sp. z o.o.: [Privacy policy](https://cookiezen.pl/polityka-prywatnosci), [Cookie policy](https://cookiezen.pl/polityka-cookies), [Terms of service](https://cookiezen.pl/regulamin).

== Changelog ==

= 1.0.5 =
* [Add] PixelYourSite integration: until the visitor gives marketing consent, PixelYourSite's tracking does not run in the browser, its server-side events (Conversions API) are not sent and its tracking cookies are not set; after consent it starts on the same page; after consent is withdrawn its cookies are removed and, with PixelYourSite 11.4.3 or newer, it stops on the same page. While your CookieZen subscription is inactive, the integration does not hold PixelYourSite back.
* [Add] The page cache of common caching plugins is cleared once after an update.
* [Enhancement] The loader script is marked so that JavaScript optimizers do not defer or delay it.

= 1.0.4 =
* First release in the WordPress.org plugin directory. Earlier versions were distributed by hand, as a ZIP file downloaded from the CookieZen panel.
* [Add] The plugin interface is translatable, with a Polish translation included.
* [Fix] The plugin page address in the header now points to a working page.
* [Fix] The loader URL is escaped when it is printed into the page.

= 1.0.3 =
* [Fix] The WP Consent API integration now works on the PHP side as well. The plugin declares the optin consent type through the wp_get_consent_type filter, so wp_has_consent() respects the visitor's decision in server-side code too. Previously only the JavaScript layer worked, and PHP-side checks let scripts through despite a refusal.

= 1.0.2 =
* [Add] Integration through a Site Key, meaning the CookieZen loader script is inserted in the head section.
* [Add] Automatic WP Consent API integration in the consent manager role.
* [Add] A Settings link on the plugins list, leading to the configuration.
* [Enhancement] The script insertion priority was tuned for theme compatibility, including Elementor Pro.

== Upgrade Notice ==

= 1.0.5 =
PixelYourSite now waits for marketing consent, in the browser and on the server. The page cache of common caching plugins is cleared once after the update.

= 1.0.4 =
First release in the WordPress.org plugin directory. From now on you install updates with one click from your WordPress admin.
