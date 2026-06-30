<?php

declare(strict_types=1);

namespace Plogins\Marks\Admin;

defined('ABSPATH') || exit;

use Plogins\Marks\Contract\HasHooks;

/**
 * Admin settings page registered as a top-level "Marks" menu.
 *
 * Stores settings in the `marks_settings` option (array): placement, the
 * automatic badge toggles (sale / new / low-stock / bestseller / discount /
 * free-shipping / out-of-stock) with custom labels and thresholds, the render
 * hints (shape / uppercase / per-context caps), plus a single store-wide manual
 * badge label and colour. All output is escaped; all input is sanitised and
 * clamped on save.
 */
final class Settings implements HasHooks
{
    private const OPTION = 'marks_settings';
    private const PAGE   = 'marks-settings';

    /** Allowed manual badge colour keys (mapped to CSS classes by the template). */
    private const STYLES = ['accent', 'success', 'warning', 'danger', 'neutral'];

    /** Allowed badge shapes (mapped to CSS classes by the template). */
    private const SHAPES = ['pill', 'square'];

    /** Incremented to give each inline-help control a unique id/anchor. */
    private int $helpSeq = 0;

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Load the settings-screen stylesheet and progressive-enhancement script,
     * only on the Marks settings page. Both ship as real files (no inline blobs)
     * and the script is deferred / loaded in the footer.
     */
    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'toplevel_page_' . self::PAGE) {
            return;
        }

        wp_enqueue_style(
            'marks-admin',
            MARKS_URL . 'assets/css/admin.css',
            [],
            \Plogins\Marks\VERSION,
        );

        wp_enqueue_script(
            'marks-admin',
            MARKS_URL . 'assets/js/admin.js',
            [],
            \Plogins\Marks\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );
    }

    public function addMenuPage(): void
    {
        add_menu_page(
            __('Marks Settings', 'plogins-marks'),
            __('Marks', 'plogins-marks'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
            'dashicons-tag',
            58,
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        // The menu uses manage_woocommerce; align the options.php save capability
        // so shop managers (not just admins with manage_options) can save.
        add_filter(
            'option_page_capability_' . self::PAGE,
            static fn (): string => 'manage_woocommerce',
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        // Edge state: without the badge engine, badges cannot render — tell the
        // merchant clearly instead of showing settings that would silently do
        // nothing.
        if (! class_exists(\WPPoland\StorefrontKit\Badge\BadgeEngine::class)) {
            $this->renderUnavailableNotice();

            return;
        }

        $settings = $this->settings();
        ?>
        <div class="wrap marks-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="marks-intro">
                <div>
                    <h2><?php esc_html_e('Product badges that boost conversions', 'plogins-marks'); ?></h2>
                    <p>
                        <?php esc_html_e('Badges highlight what makes a product worth buying, a sale, a fresh arrival, low stock or a bestseller. They are pure CSS, so they load instantly and never shift your layout. Configure them below; the live preview on the right updates as you type.', 'plogins-marks'); ?>
                    </p>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="marks-layout">
                    <div class="marks-settings">
                        <div class="marks-card">
                            <h2><?php esc_html_e('Display', 'plogins-marks'); ?></h2>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row">
                                            <?php esc_html_e('Enable badges', 'plogins-marks'); ?>
                                            <?php $this->help(__('The master switch. When off, no badges render anywhere on your storefront and the badge stylesheet is not loaded, zero front-end impact.', 'plogins-marks')); ?>
                                        </th>
                                        <td>
                                            <label for="marks_enabled">
                                                <input
                                                    type="checkbox"
                                                    id="marks_enabled"
                                                    name="<?php echo esc_attr(self::OPTION); ?>[enabled]"
                                                    value="1"
                                                    <?php checked((bool) ($settings['enabled'] ?? false), true); ?>
                                                />
                                                <?php esc_html_e('Show product badges on the storefront.', 'plogins-marks'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <?php
                                    $this->checkboxRow(
                                        'show_on_single',
                                        __('Single product page', 'plogins-marks'),
                                        __('Show badges on the product page.', 'plogins-marks'),
                                        $settings,
                                        __('Places badges over the main product image on each product\'s own page.', 'plogins-marks'),
                                    );
                                    $this->checkboxRow(
                                        'show_on_loop',
                                        __('Shop and category listings', 'plogins-marks'),
                                        __('Show badges on shop, category and tag listings.', 'plogins-marks'),
                                        $settings,
                                        __('Places badges on product thumbnails across the shop, category and tag archive pages.', 'plogins-marks'),
                                    );
                                    ?>
                                </tbody>
                            </table>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: shortcode wrapped in <code>. */
                                    esc_html__('Need badges somewhere else? Drop %s into any page, post or widget to render the current product\'s badges.', 'plogins-marks'),
                                    '<code>[marks_badges]</code>',
                                );
                                ?>
                            </p>
                        </div>

                        <div class="marks-card">
                            <h2><?php esc_html_e('Automatic badges', 'plogins-marks'); ?></h2>
                            <p class="description">
                                <?php esc_html_e('These appear on their own based on each product\'s live state, no manual tagging needed. Tick the ones you want and, optionally, give them your own wording. Leave a label blank to use the translated default.', 'plogins-marks'); ?>
                            </p>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <?php
                                    $this->autoBadgeRow('sale', __('Sale', 'plogins-marks'), __('On products currently on sale.', 'plogins-marks'), $settings, true, __('Shown automatically whenever a product has an active sale price.', 'plogins-marks'));
                                    $this->autoBadgeRow('new', __('New', 'plogins-marks'), __('On products created within the newness window.', 'plogins-marks'), $settings, true, __('Shown while a product is younger than the "Newness window" set under Thresholds.', 'plogins-marks'));
                                    $this->autoBadgeRow('low_stock', __('Low stock', 'plogins-marks'), __('On stock-managed products at or below the threshold.', 'plogins-marks'), $settings, true, __('Creates urgency. Requires WooCommerce stock management; triggers at or below the "Low-stock threshold".', 'plogins-marks'));
                                    $this->autoBadgeRow('bestseller', __('Bestseller', 'plogins-marks'), __('On products at or above the sales threshold.', 'plogins-marks'), $settings, true, __('Social proof for your top sellers. Triggers once total units sold reaches the "Bestseller threshold".', 'plogins-marks'));
                                    $this->autoBadgeRow('free_shipping', __('Free shipping', 'plogins-marks'), __('On products in a free-shipping shipping class.', 'plogins-marks'), $settings, true, __('Shown for products whose shipping class matches one listed under "Free-shipping classes".', 'plogins-marks'));
                                    $this->autoBadgeRow('out_of_stock', __('Out of stock', 'plogins-marks'), __('On products that are out of stock.', 'plogins-marks'), $settings, true, __('Sets expectations before the click. Shown when WooCommerce reports the product as out of stock.', 'plogins-marks'));
                                    // Discount-percent badge text is computed (e.g. -20%), so no label field.
                                    $this->autoBadgeRow('discount_percent', __('Discount percent', 'plogins-marks'), __('Shows the sale discount as a percentage (e.g. -20%).', 'plogins-marks'), $settings, false, __('Calculates the saving from the regular and sale price automatically, e.g. -20%. Pairs well with, or instead of, the plain Sale badge.', 'plogins-marks'));
                                    $this->checkboxRow(
                                        'hide_woocommerce_sale_flash',
                                        __('Hide theme “Sale!” flash', 'plogins-marks'),
                                        __('Replace WooCommerce’s default sale flash with the Marks sale badge only.', 'plogins-marks'),
                                        $settings,
                                        __('Avoids two sale labels on one product card. Marks badges are CSS-only, they never reflow the page. Best used together with the Sale badge above.', 'plogins-marks'),
                                    );
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="marks-card">
                            <h2><?php esc_html_e('Thresholds', 'plogins-marks'); ?></h2>
                            <p class="description">
                                <?php esc_html_e('Tune when the automatic badges above trigger. Values must be at least 1.', 'plogins-marks'); ?>
                            </p>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <?php
                                    $this->numberRow('newness_days', __('Newness window (days)', 'plogins-marks'), __('Show the New badge on products created within this many days.', 'plogins-marks'), $settings, 1, __('A product counts as "new" for this many days after it is published. 30 is a sensible default.', 'plogins-marks'));
                                    $this->numberRow('low_stock_threshold', __('Low-stock threshold', 'plogins-marks'), __('Show the Low stock badge when remaining stock is at or below this number.', 'plogins-marks'), $settings, 1, __('When stock falls to this number or below, the Low stock badge appears. Lower it to reserve the badge for true scarcity.', 'plogins-marks'));
                                    $this->numberRow('bestseller_threshold', __('Bestseller threshold', 'plogins-marks'), __('Show the Bestseller badge when total sales reach this number.', 'plogins-marks'), $settings, 1, __('Total lifetime units sold needed to earn the Bestseller badge. Set it to match what "popular" means for your store.', 'plogins-marks'));
                                    ?>
                                    <tr>
                                        <th scope="row">
                                            <label for="marks_free_shipping_classes"><?php esc_html_e('Free-shipping classes', 'plogins-marks'); ?></label>
                                            <?php $this->help(__('Enter the slugs of your WooCommerce shipping classes that ship free, separated by commas. Find slugs under WooCommerce → Settings → Shipping → Classes.', 'plogins-marks')); ?>
                                        </th>
                                        <td>
                                            <input
                                                type="text"
                                                id="marks_free_shipping_classes"
                                                name="<?php echo esc_attr(self::OPTION); ?>[free_shipping_classes]"
                                                value="<?php echo esc_attr((string) ($settings['free_shipping_classes'] ?? 'free-shipping')); ?>"
                                                class="regular-text"
                                                placeholder="free-shipping"
                                            />
                                            <p class="description"><?php esc_html_e('Comma-separated product shipping-class slugs that count as free shipping.', 'plogins-marks'); ?></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="marks-card">
                            <h2><?php esc_html_e('Appearance', 'plogins-marks'); ?></h2>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label for="marks_shape"><?php esc_html_e('Badge shape', 'plogins-marks'); ?></label>
                                            <?php $this->help(__('Pill gives soft, fully-rounded corners; Square gives a tighter, more editorial look. Themes can override the exact radius via the --marks-radius variable.', 'plogins-marks')); ?>
                                        </th>
                                        <td>
                                            <select id="marks_shape" name="<?php echo esc_attr(self::OPTION); ?>[shape]">
                                                <?php
                                                $currentShape = (string) ($settings['shape'] ?? 'pill');
                                                $shapeLabels  = [
                                                    'pill'   => __('Pill (rounded)', 'plogins-marks'),
                                                    'square' => __('Square', 'plogins-marks'),
                                                ];
                                                foreach (self::SHAPES as $shape) :
                                                    ?>
                                                    <option value="<?php echo esc_attr($shape); ?>" <?php selected($currentShape, $shape); ?>>
                                                        <?php echo esc_html($shapeLabels[$shape] ?? $shape); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php
                                    $this->checkboxRow('uppercase', __('Uppercase labels', 'plogins-marks'), __('Render badge labels in uppercase.', 'plogins-marks'), $settings, __('Uppercases every badge label and adds light letter-spacing for a bolder, more uniform look.', 'plogins-marks'));
                                    $this->numberRow('max_badges_single', __('Max badges (product page)', 'plogins-marks'), __('Maximum number of badges shown on a single product page.', 'plogins-marks'), $settings, 1, __('Caps how many badges stack on a single product page so the image stays uncluttered. Highest-priority badges win.', 'plogins-marks'));
                                    $this->numberRow('max_badges_loop', __('Max badges (listings)', 'plogins-marks'), __('Maximum number of badges shown on shop and category listings.', 'plogins-marks'), $settings, 1, __('Caps badges on the smaller thumbnails in shop and category grids. 2–3 keeps listings clean.', 'plogins-marks'));
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="marks-card">
                            <h2><?php esc_html_e('Manual badge', 'plogins-marks'); ?></h2>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: meta key wrapped in <code>. */
                                    esc_html__('Define one store-wide badge (label + colour). Leave the label empty to disable it. To show it on a specific product, set the %s product meta, handy for "Editor\'s pick" or "Staff favourite" flags.', 'plogins-marks'),
                                    '<code>_marks_manual_text</code>',
                                );
                                ?>
                            </p>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label for="marks_manual_badge_text"><?php esc_html_e('Manual badge label', 'plogins-marks'); ?></label>
                                            <?php $this->help(__('The text shown by your manual badge, e.g. "Editor\'s pick". Leave blank to turn the manual badge off entirely.', 'plogins-marks')); ?>
                                        </th>
                                        <td>
                                            <input
                                                type="text"
                                                id="marks_manual_badge_text"
                                                name="<?php echo esc_attr(self::OPTION); ?>[manual_badge_text]"
                                                value="<?php echo esc_attr((string) ($settings['manual_badge_text'] ?? '')); ?>"
                                                class="regular-text"
                                                placeholder="<?php esc_attr_e('e.g. Editor’s pick', 'plogins-marks'); ?>"
                                            />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="marks_manual_badge_style"><?php esc_html_e('Manual badge colour', 'plogins-marks'); ?></label>
                                            <?php $this->help(__('Pick a semantic colour. Accent is the Marks signature ink (a vermilion red), Success green, Warning amber, Danger red, Neutral grey. The preview on the right shows each. Exact shades are themeable via the --marks-bg-* variables.', 'plogins-marks')); ?>
                                        </th>
                                        <td>
                                            <select
                                                id="marks_manual_badge_style"
                                                name="<?php echo esc_attr(self::OPTION); ?>[manual_badge_style]"
                                            >
                                                <?php
                                                $current     = (string) ($settings['manual_badge_style'] ?? 'accent');
                                                $styleLabels = $this->styleLabels();
                                                foreach (self::STYLES as $style) :
                                                    ?>
                                                    <option value="<?php echo esc_attr($style); ?>" <?php selected($current, $style); ?>>
                                                        <?php echo esc_html($styleLabels[$style] ?? ucfirst($style)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <?php do_action('marks_admin_settings_after_manual_table', $settings); ?>
                        </div>

                        <?php submit_button(); ?>
                    </div>

                    <?php $this->renderPreviewPanel(); ?>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Sticky live-preview panel. JS keeps it in sync with the form; without JS
     * it shows a representative static set so the panel is never blank.
     */
    private function renderPreviewPanel(): void
    {
        ?>
        <aside class="marks-card marks-preview" aria-label="<?php esc_attr_e('Badge preview', 'plogins-marks'); ?>">
            <h2><?php esc_html_e('Live preview', 'plogins-marks'); ?></h2>
            <p class="marks-preview__hint">
                <?php esc_html_e('A sample of how your badges will look on a product image.', 'plogins-marks'); ?>
            </p>
            <div class="marks-preview__stage">
                <div class="marks-preview__badges">
                    <span class="marks-preview__badge marks-preview__badge--danger"><?php esc_html_e('Sale', 'plogins-marks'); ?></span>
                    <span class="marks-preview__badge marks-preview__badge--success"><?php esc_html_e('New', 'plogins-marks'); ?></span>
                    <span class="marks-preview__badge marks-preview__badge--warning"><?php esc_html_e('Low stock', 'plogins-marks'); ?></span>
                </div>
                <p class="marks-preview__empty" hidden>
                    <?php esc_html_e('No badges enabled yet, tick some on the left to see them here.', 'plogins-marks'); ?>
                </p>
            </div>
        </aside>
        <?php
    }

    /**
     * Friendly empty state shown when the badge engine is unavailable, so the
     * screen is never blank or silently inert.
     */
    private function renderUnavailableNotice(): void
    {
        ?>
        <div class="wrap marks-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="marks-empty">
                <h2><?php esc_html_e('Badges are not available yet', 'plogins-marks'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Marks could not load its badge engine. This usually means a required dependency is missing or out of date. Please reinstall or update the plugin; once the engine is present, this screen will show the full badge settings.', 'plogins-marks'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Translated, human-friendly labels for the semantic colour keys.
     *
     * @return array<string, string>
     */
    private function styleLabels(): array
    {
        return [
            'accent'  => __('Accent (blue)', 'plogins-marks'),
            'success' => __('Success (green)', 'plogins-marks'),
            'warning' => __('Warning (amber)', 'plogins-marks'),
            'danger'  => __('Danger (red)', 'plogins-marks'),
            'neutral' => __('Neutral (grey)', 'plogins-marks'),
        ];
    }

    /**
     * Render an accessible inline-help affordance: a "?" button that toggles a
     * popover describing the adjacent setting. Uses the native Popover API
     * (popover/popovertarget) and is also wired via aria-describedby so screen
     * readers announce the help text; the bundled script supplies a fallback for
     * browsers without Popover support.
     */
    private function help(string $text): void
    {
        $id = 'marks-help-' . (++$this->helpSeq);
        ?>
        <button
            type="button"
            class="marks-help"
            aria-label="<?php esc_attr_e('More information', 'plogins-marks'); ?>"
            aria-describedby="<?php echo esc_attr($id); ?>"
            aria-expanded="false"
            popovertarget="<?php echo esc_attr($id); ?>"
        >?</button>
        <div id="<?php echo esc_attr($id); ?>" class="marks-tip" role="tooltip" popover hidden>
            <?php echo esc_html($text); ?>
        </div>
        <?php
    }

    /**
     * Render a single checkbox row in the form-table.
     *
     * @param array<string, mixed> $settings
     */
    private function checkboxRow(string $key, string $label, string $help, array $settings, string $tip = ''): void
    {
        $id = 'marks_' . $key;
        ?>
        <tr>
            <th scope="row">
                <?php echo esc_html($label); ?>
                <?php if ($tip !== '') { $this->help($tip); } ?>
            </th>
            <td>
                <label for="<?php echo esc_attr($id); ?>">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($id); ?>"
                        name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]"
                        value="1"
                        <?php checked((bool) ($settings[$key] ?? false), true); ?>
                    />
                    <?php echo esc_html($help); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    /**
     * Render a number input row, clamped to a minimum.
     *
     * @param array<string, mixed> $settings
     */
    private function numberRow(string $key, string $label, string $help, array $settings, int $min, string $tip = ''): void
    {
        $id = 'marks_' . $key;
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
                <?php if ($tip !== '') { $this->help($tip); } ?>
            </th>
            <td>
                <input
                    type="number"
                    min="<?php echo esc_attr((string) $min); ?>"
                    step="1"
                    id="<?php echo esc_attr($id); ?>"
                    name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]"
                    value="<?php echo esc_attr((string) (int) ($settings[$key] ?? $min)); ?>"
                    class="small-text"
                />
                <p class="description"><?php echo esc_html($help); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render an automatic-badge row: an enable toggle plus an optional custom
     * label field. The key uses the engine's `show_<rule>_badge` / `<rule>_badge_text`
     * naming.
     *
     * @param array<string, mixed> $settings
     */
    private function autoBadgeRow(string $rule, string $label, string $help, array $settings, bool $hasLabel, string $tip = ''): void
    {
        $toggleKey = 'show_' . $rule . '_badge';
        $labelKey  = $rule . '_badge_text';
        $toggleId  = 'marks_' . $toggleKey;
        ?>
        <tr>
            <th scope="row">
                <?php echo esc_html($label); ?>
                <?php if ($tip !== '') { $this->help($tip); } ?>
            </th>
            <td>
                <label for="<?php echo esc_attr($toggleId); ?>">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($toggleId); ?>"
                        name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($toggleKey); ?>]"
                        value="1"
                        <?php checked((bool) ($settings[$toggleKey] ?? false), true); ?>
                    />
                    <?php echo esc_html($help); ?>
                </label>
                <?php if ($hasLabel) : ?>
                    <br />
                    <input
                        type="text"
                        id="<?php echo esc_attr('marks_' . $labelKey); ?>"
                        name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($labelKey); ?>]"
                        value="<?php echo esc_attr((string) ($settings[$labelKey] ?? '')); ?>"
                        class="regular-text"
                        placeholder="<?php esc_attr_e('Custom label (optional)', 'plogins-marks'); ?>"
                        aria-label="<?php
                        /* translators: %s: badge name, e.g. Sale. */
                        echo esc_attr(sprintf(__('Custom label for the %s badge', 'plogins-marks'), $label));
                        ?>"
                    />
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Sanitises, validates and clamps the submitted settings before save.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $style = isset($raw['manual_badge_style']) ? sanitize_key((string) $raw['manual_badge_style']) : 'accent';

        if (! in_array($style, self::STYLES, true)) {
            $style = 'accent';
        }

        $shape = isset($raw['shape']) ? sanitize_key((string) $raw['shape']) : 'pill';

        if (! in_array($shape, self::SHAPES, true)) {
            $shape = 'pill';
        }

        $sanitized = [
            'enabled'         => ! empty($raw['enabled']),
            'show_on_single'  => ! empty($raw['show_on_single']),
            'show_on_loop'    => ! empty($raw['show_on_loop']),

            'show_sale_badge'             => ! empty($raw['show_sale_badge']),
            'show_new_badge'              => ! empty($raw['show_new_badge']),
            'show_low_stock_badge'        => ! empty($raw['show_low_stock_badge']),
            'show_bestseller_badge'       => ! empty($raw['show_bestseller_badge']),
            'show_discount_percent_badge' => ! empty($raw['show_discount_percent_badge']),
            'show_free_shipping_badge'    => ! empty($raw['show_free_shipping_badge']),
            'show_out_of_stock_badge'     => ! empty($raw['show_out_of_stock_badge']),
            'hide_woocommerce_sale_flash' => ! empty($raw['hide_woocommerce_sale_flash']),

            'sale_badge_text'          => $this->text($raw, 'sale_badge_text'),
            'new_badge_text'           => $this->text($raw, 'new_badge_text'),
            'low_stock_badge_text'     => $this->text($raw, 'low_stock_badge_text'),
            'bestseller_badge_text'    => $this->text($raw, 'bestseller_badge_text'),
            'free_shipping_badge_text' => $this->text($raw, 'free_shipping_badge_text'),
            'out_of_stock_badge_text'  => $this->text($raw, 'out_of_stock_badge_text'),

            'newness_days'         => max(1, isset($raw['newness_days']) ? (int) $raw['newness_days'] : 30),
            'low_stock_threshold'  => max(1, isset($raw['low_stock_threshold']) ? (int) $raw['low_stock_threshold'] : 3),
            'bestseller_threshold' => max(1, isset($raw['bestseller_threshold']) ? (int) $raw['bestseller_threshold'] : 25),

            'free_shipping_classes' => $this->text($raw, 'free_shipping_classes'),

            'shape'             => $shape,
            'uppercase'         => ! empty($raw['uppercase']),
            'max_badges_single' => max(1, isset($raw['max_badges_single']) ? (int) $raw['max_badges_single'] : 4),
            'max_badges_loop'   => max(1, isset($raw['max_badges_loop']) ? (int) $raw['max_badges_loop'] : 3),

            'show_manual_badge'  => true,
            'manual_badge_text'  => $this->text($raw, 'manual_badge_text'),
            'manual_badge_style' => $style,
        ];

        return (array) apply_filters('marks_sanitize_settings', $sanitized, $raw);
    }

    /**
     * Sanitise a single text field from the raw input.
     *
     * @param array<string, mixed> $raw
     */
    private function text(array $raw, string $key): string
    {
        return isset($raw[$key]) ? sanitize_text_field((string) $raw[$key]) : '';
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
}
