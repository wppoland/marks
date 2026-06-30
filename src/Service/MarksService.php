<?php

declare(strict_types=1);

namespace Plogins\Marks\Service;

use Plogins\Marks\Contract\HasHooks;
use WPPoland\StorefrontKit\Badge\BadgeEngine;

defined('ABSPATH') || exit;

/**
 * Wires {@see BadgeEngine} with this plugin's text-domain ('plogins-marks'), option
 * prefix ('marks_') and product meta keys, and renders badges through a CSS-only
 * template. This class supplies localisation, option storage, meta-key naming
 * and the front-end stylesheet.
 */
final class MarksService implements HasHooks
{
    private const OPTION = 'marks_settings';

    /** Product meta keys for the (optional) per-product manual badge. */
    private const META_MANUAL_TEXT  = '_marks_manual_text';
    private const META_MANUAL_STYLE = '_marks_manual_style';

    /**
     * Prefixed meta key for the engine's optional "secondary" manual badge.
     * Marks keeps that feature disabled (see config/defaults.php), but mapping the
     * key here ensures the engine never reads an unprefixed/generic meta key even
     * if the setting is toggled on by a filter.
     */
    private const META_SECONDARY_TEXT = '_marks_secondary_text';

    private ?BadgeEngine $engine = null;

    public function __construct()
    {
        // When the badge engine is available, wire it with this plugin's
        // text-domain / option prefix / meta keys. Otherwise leave the service
        // inert (see registerHooks()).
        if (! class_exists(BadgeEngine::class)) {
            return;
        }

        $this->engine = new BadgeEngine(
            'badges',
            [
                'sale'          => __('Sale', 'plogins-marks'),
                'new'           => __('New', 'plogins-marks'),
                'low_stock'     => __('Low stock', 'plogins-marks'),
                'bestseller'    => __('Bestseller', 'plogins-marks'),
                'free_shipping' => __('Free shipping', 'plogins-marks'),
                'out_of_stock'  => __('Out of stock', 'plogins-marks'),
            ],
            [
                'manual_text'    => self::META_MANUAL_TEXT,
                'manual_style'   => self::META_MANUAL_STYLE,
                'secondary_text' => self::META_SECONDARY_TEXT,
            ],
            fn (): bool => $this->isEnabled(),
            fn (): array => $this->settings(),
            fn (\WC_Product $product, string $key): mixed => $product->get_meta($key),
            function (string $template, array $context): void {
                $this->renderTemplate($template, $context);
            },
        );
    }

    public function registerHooks(): void
    {
        if ($this->engine instanceof BadgeEngine) {
            $this->engine->registerHooks();
            add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
            add_shortcode('marks_badges', [$this, 'renderShortcode']);
            add_filter('woocommerce_sale_flash', [$this, 'maybeHideNativeSaleFlash'], 10, 3);

            return;
        }

        // The badge engine is unavailable; no hooks are registered until it is
        // present.
    }

    public function enqueueAssets(): void
    {
        if (! $this->isEnabled() || ! (is_product() || is_shop() || is_product_category() || is_product_tag())) {
            return;
        }

        wp_enqueue_style(
            'marks',
            MARKS_URL . 'assets/css/badges.css',
            [],
            \Plogins\Marks\VERSION,
        );
    }

    /**
     * When the merchant prefers a single sale treatment, hide WooCommerce's
     * default “Sale!” flash so only the Marks badge shows.
     */
    public function maybeHideNativeSaleFlash(mixed $html, mixed $post, mixed $product): mixed
    {
        if (! $this->isEnabled()) {
            return $html;
        }

        $settings = $this->settings();

        if (empty($settings['hide_woocommerce_sale_flash']) || empty($settings['show_sale_badge'])) {
            return $html;
        }

        return false;
    }

    /**
     * Shortcode `[marks_badges]` — render a product's badge group anywhere.
     *
     * Attributes:
     *  - `id`      Product ID. Defaults to the current global product, then to
     *              the queried object on a single product page.
     *  - `context` Render context: `single` (default) or `loop`.
     *
     * @param array<string, mixed>|string $atts
     */
    public function renderShortcode(mixed $atts): string
    {
        if (! $this->engine instanceof BadgeEngine || ! $this->isEnabled()) {
            return '';
        }

        $atts = shortcode_atts(
            [
                'id'      => 0,
                'context' => 'single',
            ],
            is_array($atts) ? $atts : [],
            'marks_badges',
        );

        $product = $this->resolveProduct((int) $atts['id']);

        if (! $product instanceof \WC_Product) {
            return '';
        }

        $context = $atts['context'] === 'loop' ? 'loop' : 'single';
        $badges  = $this->engine->getBadges($product, $context);

        if ($badges === []) {
            return '';
        }

        wp_enqueue_style(
            'marks',
            MARKS_URL . 'assets/css/badges.css',
            [],
            \Plogins\Marks\VERSION,
        );

        $settings = $this->settings();

        ob_start();
        $this->renderTemplate('badges', [
            'badges'    => $badges,
            'context'   => $context,
            'product'   => $product,
            'shape'     => (string) ($settings['shape'] ?? 'pill'),
            'uppercase' => (bool) ($settings['uppercase'] ?? false),
        ]);

        return (string) ob_get_clean();
    }

    /**
     * Resolve a product for the shortcode: explicit ID, then the current global
     * product, then the queried product on a single-product page.
     */
    private function resolveProduct(int $id): ?\WC_Product
    {
        if ($id > 0) {
            $product = wc_get_product($id);

            return $product instanceof \WC_Product ? $product : null;
        }

        global $product;

        if ($product instanceof \WC_Product) {
            return $product;
        }

        if (function_exists('is_product') && is_product()) {
            $queried = wc_get_product(get_queried_object_id());

            return $queried instanceof \WC_Product ? $queried : null;
        }

        return null;
    }

    private function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? false);
    }

    /**
     * Stored settings merged over packaged defaults.
     *
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $stored = get_option(self::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require MARKS_DIR . 'config/defaults.php';

        return array_merge($defaults, $stored);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderTemplate(string $template, array $context): void
    {
        $file = MARKS_DIR . 'templates/' . $template . '.php';

        if (! is_readable($file)) {
            return;
        }

        extract($context, EXTR_SKIP);
        require $file;
    }
}
