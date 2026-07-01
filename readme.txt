=== Plogins Marks - Product Badges for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, product badges, sale badge, new badge, low stock
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

CSS-only product badges for WooCommerce: Sale, New, Low stock, Bestseller and a manual badge. No JavaScript, no layout shift.

== Description ==

Marks puts badges on your WooCommerce products. Some appear on their own from each
product's current state (**Sale**, **New**, **Low stock**, **Bestseller**, and a few
others), and you can also set one manual badge per product using a store-wide label
and colour.

The badges are drawn with CSS and sit over the product image, so they add no
JavaScript and don't shift the layout when the page loads. They show on the single
product page and on shop, category and tag listings.

The code is open and lives at https://github.com/wppoland/plogins-marks if you want to read
it, file a bug or send a patch.

Configuration lives under a top-level **Marks** admin menu: a global on/off
toggle, placement controls, per-rule toggles with custom labels, thresholds, an
appearance section (shape, uppercase, per-context caps) and the manual badge
label and colour. Settings are sanitised and clamped on save.

= Documentation and links =

* **Documentation** - https://plogins.com/marks/docs/
* **Plugin page** - https://plogins.com/marks/
* **Source code** - https://github.com/wppoland/plogins-marks
* **Bug reports and feature requests** - https://github.com/wppoland/plogins-marks/issues


= Features =

* Automatic badges: Sale, New, Low stock, Bestseller, Discount percent, Free shipping, Out of stock.
* Custom label text for every automatic badge.
* Configurable thresholds: newness window, low-stock level, bestseller sales.
* Free-shipping detection by product shipping class.
* Placement controls: single product page and/or shop and category listings.
* Appearance controls: pill or square shape, uppercase, and a per-context badge cap.
* `[marks_badges]` shortcode to place badges anywhere.
* A single manual badge (label + colour) shown per product via meta.
* CSS-only rendering: no JavaScript, no layout shift.
* Global on/off toggle and per-rule toggles.
* Translation ready (POT included) and clean uninstall.
* HPOS and cart/checkout blocks compatible.

= The [marks_badges] shortcode =

Use `[marks_badges]` to drop a product's badges into any page, post or widget. With
no attributes it uses the current product (inside a loop or on a single product
page). Pass `id` to target a specific product and `context` (`single` or `loop`) to
pick the render style:

`[marks_badges id="123" context="loop"]`

== Installation ==

1. Upload the plugin to `/wp-content/plugins/marks`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to the **Marks** menu, enable badges, and choose which automatic badges to show.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes.

= When does the "New" badge show? =

On products created within the newness window (30 days by default).

= When does the "Low stock" badge show? =

On stock-managed products whose remaining quantity is at or below the configured
low-stock threshold.

= How do I add a manual badge to a single product? =

Set the manual badge label and colour on the Marks settings screen, then set the
product meta `_marks_manual_text` (and optionally `_marks_manual_style`) on the
products that should display it.


= Does this plugin work on WordPress Multisite? =

Yes. This plugin is compatible with WordPress Multisite. Network activate it or activate it on individual sites; each site keeps its own settings and data.

== Screenshots ==

1. Automatic badges on a shop listing.
2. The Marks settings screen.

== External Services ==

Marks does not connect to any external services. Badges are computed and drawn entirely on your own server from WooCommerce data already in your database (price, stock, sales, product dates and shipping class), and the badge CSS is served from the plugin's own assets.

The plugin stores its configuration in two WordPress options (`marks_settings` and `marks_db_version`) and reads per-product post meta (`_marks_manual_text` and `_marks_manual_style`) for the manual badge. No visitor or store data is sent anywhere, and Marks does not call home, load remote scripts or fonts, or send email.

== Changelog ==

= 0.3.1 =
* Renamed to Plogins Marks for WooCommerce for a more distinctive plugin name.

= 0.3.0 =
* New: optional hide of the default WooCommerce sale flash when the Marks sale badge is enabled.
* Admin settings help tooltips polish.

= 0.2.0 =
* Add Discount percent, Free shipping and Out of stock automatic badges.
* Add custom label text for every automatic badge.
* Add configurable newness-window and bestseller thresholds, plus free-shipping class detection.
* Add placement controls (single product page and/or listings).
* Add appearance controls: badge shape, uppercase and per-context badge caps.
* Add the `[marks_badges]` shortcode for manual placement.
* Add a translation template (languages/marks.pot) and an uninstall cleanup.

= 0.1.0 =
* Initial release: automatic Sale / New / Low stock / Bestseller badges, a manual badge, and a settings screen. CSS-only.
