=== Marks ===
Contributors: wppoland
Tags: woocommerce, product badges, sale badge, new badge, low stock
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatic and manual product badges for WooCommerce — CSS-only, no layout shift.

== Description ==

Marks adds product badges to your WooCommerce storefront. Automatic badges appear
on their own based on each product's state — **Sale**, **New**, **Low stock** and
**Bestseller** — and you can attach a single manual badge to individual products
via a store-wide label and colour.

Badges render with plain CSS — no JavaScript — and are positioned over the product
image so they never reflow the layout (no Cumulative Layout Shift). They show on
both the single product page and shop/category listings.

Configuration lives under a top-level **Marks** admin menu: a global on/off
toggle, placement controls, per-rule toggles with custom labels, thresholds, an
appearance section (shape, uppercase, per-context caps) and the manual badge
label and colour. Settings are sanitised and clamped on save.

= Features =

* Automatic badges: Sale, New, Low stock, Bestseller, Discount percent, Free shipping, Out of stock.
* Custom label text for every automatic badge.
* Configurable thresholds: newness window, low-stock level, bestseller sales.
* Free-shipping detection by product shipping class.
* Placement controls: single product page and/or shop and category listings.
* Appearance controls: pill or square shape, uppercase, and a per-context badge cap.
* `[marks_badges]` shortcode to place badges anywhere.
* A single manual badge (label + colour) shown per product via meta.
* CSS-only rendering — no JavaScript, no layout shift.
* Global on/off toggle and per-rule toggles.
* Translation ready (POT included) and clean uninstall.
* HPOS and cart/checkout blocks compatible.

= The [marks_badges] shortcode =

Place a product's badge group anywhere with `[marks_badges]`. It uses the current
product in product loops and on the single product page. Pass `id` to target a
specific product and `context` (`single` or `loop`) to switch the render style:

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

== Screenshots ==

1. Automatic badges on a shop listing.
2. The Marks settings screen.

== Changelog ==

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
