=== AppetitQR - Digital QR Menus & Commission-Free Ordering for Restaurants ===
Contributors: sakurapixel
Tags: restaurant, menu, qr code, food menu, ordering
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPL-3.0-only
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Embed your AppetitQR digital menu on any WordPress page with a shortcode.

== Description ==

[appetitqr.com](https://appetitqr.com/)

AppetitQR gives restaurants a digital QR menu and commission-free ordering. This plugin brings that
same menu into your own WordPress site, so guests can browse it without leaving your pages.

Paste one shortcode and the plugin pulls your live menu, your template's colors and your custom
labels straight from your AppetitQR account:

`[wp_appetitqr api_key="apq_your_key_here"]`

= What it renders =

* Your categories and products, in the order you set in AppetitQR
* Prices, sale prices and portion variations in your account's currency
* Allergens, dietary tags and nutritional values
* A product popup with the full description and gallery
* Live search across the menu
* Your restaurant's about text, address, phone and opening hours
* An optional cart that hands the order to you over WhatsApp or a phone call

= Built for real pages =

The menu is fetched on your server and cached, then rendered into the page HTML. That means it is
visible to search engines and readable even with JavaScript disabled — the script only adds search,
the category jump-nav, the product popup and the cart on top.

Because the shortcode lives inside a page you already designed, the menu adapts to its container
instead of taking over the screen: product details and the cart open as in-page overlays rather than
full-page navigations.

= Commission-free =

Orders never pass through this plugin or through AppetitQR's servers. The cart is stored in the
visitor's own browser and handed to you as a message over WhatsApp or a phone call, so there is no
per-order fee and no payment processing in between.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/sakura-pixel-menu-embed-for-appetitqr` and activate it.
2. In your AppetitQR dashboard go to **Locations → your location → Settings → Integrations**.
3. Click **Generate API Key** and copy the shortcode shown underneath it.
4. Paste the shortcode into any WordPress page or post.

Optionally visit **AppetitQR** in the WordPress admin menu to test a key, adjust how long menus are
cached, or clear the cache after changing your menu.

== Frequently Asked Questions ==

= Do I need an AppetitQR account? =

Yes. The plugin displays a menu managed in AppetitQR, it does not create menus itself.

= How quickly do menu changes appear? =

Menus are cached for 15 minutes by default. Change the lifetime, or clear the cache immediately, from
the plugin's settings screen.

= What happens if AppetitQR is unreachable? =

The last successful copy of the menu keeps being served, so your page never breaks during an outage.
Editors see a small notice explaining that a cached copy is on screen, visitors do not.

= Can I put two different locations on one site? =

Yes. Use a separate shortcode with each location's own API key, on the same page or on different ones.

= Is my API key sensitive? =

It only grants read access to that location's menu, which is already public on your AppetitQR menu
page. Even so, treat it as private and revoke it from the dashboard if it leaks.

== Shortcode attributes ==

* `api_key` — required, from your AppetitQR dashboard
* `lang` — menu language, defaults to your account's default language
* `show_search` — `1` or `0`, defaults to `1`
* `show_info` — `1` or `0`, defaults to `1`
* `show_cart` — `1` or `0`, defaults to `1`
* `show_images` — `1` or `0`, defaults to `1`
* `columns` — `1`–`4`, defaults to `3`
* `dinein` — `true` or `false`, defaults to `false`. See below.

== Dine-in mode ==

`[wp_appetitqr api_key="apq_your_key_here" dinein="true"]`

Turns the page into a table menu. Guests build a wishlist and show it to their server, and every
ordering feature is switched off. No WhatsApp or phone ordering, no
minimum order. Each visit starts a new list, and previous lists stay available on the device so a
table can look back at what they saved earlier.

Put it on a separate page from your ordering menu and point your table QR codes at that page. The
list also has to be enabled for dine-in on the location in your AppetitQR dashboard.

== Changelog ==

= 1.0.0 =
* Initial release.
