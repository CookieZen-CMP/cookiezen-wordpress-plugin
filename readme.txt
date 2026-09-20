=== CookieZen ===
Contributors: cookiezen
Tags: cookie banner, cookie consent, cookie notice, GDPR, privacy
Requires at least: 5.5
Tested up to: 7.1.1
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookie consent banner and GDPR consent platform with Google Consent Mode v2, an automatic cookie scanner and stored consent records.

== Description ==

CookieZen manages the consent of everyone who visits your site, sends the full set of Consent Mode signals to Google and Microsoft, scans your pages for cookies and stores every consent decision in case of an audit. Setting it up takes a few minutes.

The plugin connects your site to a CookieZen account with a single key (Site Key). All of the logic, meaning the cookie scanner, tracker classification, Consent Mode signals, the consent register and the statistics, runs on the CookieZen service, so the plugin itself stays small. You change the look and the wording of the banner in the panel, without touching your theme and without pasting anything into your templates.

= What makes CookieZen different =

**Automatic cookie scanner**
CookieZen crawls the key pages of your site and detects cookies and tracking pixels, including the ones that only fire after consent is given. The scan repeats every 30 days, so the list does not fall behind the changes you make to the site.

**Google Consent Mode v2 with no configuration**
Every required signal reaches Google tags before they start collecting data. url_passthrough and ads_data_redaction are supported as well, so Google Ads can still model conversions when a visitor declines cookies. You do not have to write your own gtag calls or change anything in Google Tag Manager.

**Microsoft UET Consent Mode and consent in Clarity**
Consent Mode for Microsoft Universal Event Tracking (UET) is supported, so paid campaigns in Microsoft Advertising measure conversions in line with the visitor's decision. The correct consent signal is passed to Microsoft Clarity too. There is nothing to connect and no code to add by hand.

**WP Consent API integration**
The WP Consent API plugin does not collect consent itself, it only exposes it to other plugins, so without a consent manager feeding it, plugins that follow the standard behave as if consent had been granted. CookieZen takes that role automatically: the visitor's decision reaches compatible plugins such as WooCommerce, Google Site Kit, WP Statistics or Pixel Manager, both in JavaScript and in PHP code. There is nothing to switch on.

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

Open your site in a private browser window. The banner should appear on the first visit, and once you make a choice it should disappear and stay away on the following page views. The decision shows up in the statistics in the CookieZen panel within a few minutes.

If the banner does not appear, check that the Site Key is saved in the plugin settings and make sure the domain added in the CookieZen panel matches the address of your site.

== Frequently Asked Questions ==

= Where do I find the Site Key? =

The Site Key is in the CookieZen panel, in the Integration tab of your domain. Create a free account at cookiezen.pl, add your domain and copy the key.

= Is the plugin free? =

Yes, the plugin is free. It needs an account in the CookieZen service, which has a permanent Free plan for up to 1,000 sessions per month. Higher plans raise the session limit.

= Does CookieZen work with the WP Consent API? =

Yes, automatically. When the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) plugin is active, CookieZen acts as the consent manager and passes the visitor's decision to compatible plugins, including the checks made in PHP. There is nothing to configure.

= Does the plugin work without an internet connection? =

No. CookieZen is a service, a consent management platform. The banner, the cookie classification and the consent register rely on CookieZen servers, so an active account and a network connection are required.

= Can I change how the banner looks and what it says? =

Yes, entirely from the CookieZen panel. You can change the colors, the logo, the layout, the wording and your own CSS. The changes reach your site without updating the plugin.

= Does installing the plugin make my site GDPR compliant? =

No. Installing it is the first step. Compliance depends on configuring the banner correctly, classifying the cookies that were found, and on whether your policies describe what actually happens on your site.

Scripts that are meant to wait for consent also have to be marked in your page code or gated by consent in your tag manager. The CookieZen documentation covers this.

== External services ==

This plugin connects your site to the CookieZen service in order to deliver the consent banner and manage the consent of your visitors. Without that connection the banner cannot work, because CookieZen is a consent management platform delivered as a service.

**What the connection is**

The plugin inserts a loader script on your site from this address:

`https://cz-cdn.com/api/cmp/loader?site_key=YOUR_SITE_KEY`

The script loads on every visit, because the banner has to initialise and pass the consent signals before your analytics and marketing tools start.

**What data is sent**

* The Site Key, which is the identifier of your site in the CookieZen system, entered by you in the plugin settings.
* The standard HTTP request headers of the visitor's browser (IP address, User-Agent, Referer), needed to deliver the script. IP addresses are irreversibly hashed on the CookieZen side and are not stored in a form that allows identification.

**Why the external service is required**

All of the platform logic, meaning the automatic cookie scanner, tracker classification, geolocation for different legal frameworks, Google Consent Mode v2 signals and the register of consent records, runs on the CookieZen backend.

The service is provided by Semavo Solutions Sp. z o.o.
Privacy policy: https://cookiezen.pl/polityka-prywatnosci
Cookie policy: https://cookiezen.pl/polityka-cookies
Terms of service: https://cookiezen.pl/regulamin

== Cookies set by CookieZen ==

The plugin sets a single cookie, `cmp_consent_<site_key>`, which stores the visitor's choice across the analytics, marketing and preferences categories. The cookie lasts one year and contains no personal data.

An equivalent entry is written to the browser's `localStorage` (`cmp:<site_key>`).

== Changelog ==

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

= 1.0.4 =
First release in the WordPress.org plugin directory. From now on you install updates with one click from your WordPress admin.
