# Marks

Marks adds product badges to your WooCommerce storefront. Automatic badges appear on their own based on each product's state — Sale, New, Low stock, Bestseller and more — and you can attach a single manual badge to individual products with a store-wide label and colour.

## Features

- Automatic badges: Sale, New, Low stock, Bestseller, Discount percent, Free shipping and Out of stock.
- Custom label text for every automatic badge, with configurable thresholds.
- Placement controls for the single product page and shop/category listings.
- Appearance controls: pill or square shape, uppercase, and a per-context badge cap.
- A single manual badge (label + colour) per product, plus the `[marks_badges]` shortcode.
- CSS-only rendering — no JavaScript, no layout shift. HPOS and cart/checkout blocks compatible.

## Installation

1. Upload the plugin to `/wp-content/plugins/marks`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to the **Marks** menu, enable badges, and choose which automatic badges to show.

## Frequently Asked Questions

**When does the "New" badge show?**
On products created within the newness window (30 days by default).

**How do I add a manual badge to a single product?**
Set the manual badge label and colour on the Marks settings screen, then set the product meta `_marks_manual_text` (and optionally `_marks_manual_style`) on the products that should display it.

---

Built by WPPoland — https://plogins.com

License: GPL-2.0-or-later
