<?php
/**
 * Default settings, merged under the option key `marks_settings`.
 *
 * The feature ships enabled with the common automatic badges on. The merchant
 * tunes which badges show, their labels, the thresholds and how badges render
 * (shape, case, caps, placement) from the Marks admin screen, and may define a
 * single store-wide manual badge (label + colour), opt-in per product via meta.
 *
 * @package Marks
 *
 * @return array<string, mixed>
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return [
    'enabled' => true,

    // Where badges render.
    'show_on_single' => true,
    'show_on_loop'   => true,

    // Automatic badge toggles.
    'show_sale_badge'             => true,
    'hide_woocommerce_sale_flash' => false,
    'show_new_badge'              => true,
    'show_low_stock_badge'        => true,
    'show_bestseller_badge'       => true,
    'show_discount_percent_badge' => false,
    'show_free_shipping_badge'    => false,
    'show_out_of_stock_badge'     => true,

    // Custom labels (empty = use the built-in translated default).
    'sale_badge_text'         => '',
    'new_badge_text'          => '',
    'low_stock_badge_text'    => '',
    'bestseller_badge_text'   => '',
    'free_shipping_badge_text' => '',
    'out_of_stock_badge_text' => '',

    // Automatic badge thresholds.
    'newness_days'         => 30,
    'low_stock_threshold'  => 3,
    'bestseller_threshold' => 25,

    // Free-shipping detection: comma-separated product shipping-class slugs.
    'free_shipping_classes' => 'free-shipping',

    // Manual badge (a single store-wide label/colour, opt-in per product via meta).
    'show_manual_badge'  => true,
    'manual_badge_text'  => '',
    'manual_badge_style' => 'accent',

    // The storefront-kit BadgeEngine also supports an optional "secondary" manual
    // badge that defaults to ON when unset. Marks does not expose that feature, so
    // disable it explicitly here — otherwise the engine would read an unprefixed
    // `secondary_text` product meta and could surface an unintended badge if that
    // generic key happens to exist (e.g. from an import or another plugin).
    'show_secondary_badge' => false,

    // Render hints consumed by the CSS-only template.
    'shape'             => 'pill',
    'uppercase'         => false,
    'max_badges_single' => 4,
    'max_badges_loop'   => 3,
];
