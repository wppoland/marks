<?php

declare(strict_types=1);

namespace Marks\Admin;

defined('ABSPATH') || exit;

use Marks\Contract\HasHooks;

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

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenuPage(): void
    {
        add_menu_page(
            __('Marks Settings', 'marks'),
            __('Marks', 'marks'),
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

        $settings = $this->settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable badges', 'marks'); ?></th>
                            <td>
                                <label for="marks_enabled">
                                    <input
                                        type="checkbox"
                                        id="marks_enabled"
                                        name="<?php echo esc_attr(self::OPTION); ?>[enabled]"
                                        value="1"
                                        <?php checked((bool) ($settings['enabled'] ?? false), true); ?>
                                    />
                                    <?php esc_html_e('Show product badges on the storefront.', 'marks'); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Placement', 'marks'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Choose where badges appear. You can also place them manually with the [marks_badges] shortcode.', 'marks'); ?>
                </p>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->checkboxRow('show_on_single', __('Single product page', 'marks'), __('Show badges on the product page.', 'marks'), $settings);
                        $this->checkboxRow('show_on_loop', __('Shop and category listings', 'marks'), __('Show badges on shop, category and tag listings.', 'marks'), $settings);
                        ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Automatic badges', 'marks'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Badges that appear on their own based on each product\'s state. CSS-only, no layout shift. Leave a label empty to use the default text.', 'marks'); ?>
                </p>

                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->autoBadgeRow('sale', __('Sale', 'marks'), __('On products currently on sale.', 'marks'), $settings, true);
                        $this->autoBadgeRow('new', __('New', 'marks'), __('On products created within the newness window.', 'marks'), $settings, true);
                        $this->autoBadgeRow('low_stock', __('Low stock', 'marks'), __('On stock-managed products at or below the threshold.', 'marks'), $settings, true);
                        $this->autoBadgeRow('bestseller', __('Bestseller', 'marks'), __('On products at or above the sales threshold.', 'marks'), $settings, true);
                        $this->autoBadgeRow('free_shipping', __('Free shipping', 'marks'), __('On products in a free-shipping shipping class.', 'marks'), $settings, true);
                        $this->autoBadgeRow('out_of_stock', __('Out of stock', 'marks'), __('On products that are out of stock.', 'marks'), $settings, true);
                        // Discount-percent badge text is computed (e.g. -20%), so no label field.
                        $this->autoBadgeRow('discount_percent', __('Discount percent', 'marks'), __('Shows the sale discount as a percentage (e.g. -20%).', 'marks'), $settings, false);
                        ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Thresholds', 'marks'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->numberRow('newness_days', __('Newness window (days)', 'marks'), __('Show the New badge on products created within this many days.', 'marks'), $settings, 1);
                        $this->numberRow('low_stock_threshold', __('Low-stock threshold', 'marks'), __('Show the Low stock badge when remaining stock is at or below this number.', 'marks'), $settings, 1);
                        $this->numberRow('bestseller_threshold', __('Bestseller threshold', 'marks'), __('Show the Bestseller badge when total sales reach this number.', 'marks'), $settings, 1);
                        ?>
                        <tr>
                            <th scope="row">
                                <label for="marks_free_shipping_classes"><?php esc_html_e('Free-shipping classes', 'marks'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="marks_free_shipping_classes"
                                    name="<?php echo esc_attr(self::OPTION); ?>[free_shipping_classes]"
                                    value="<?php echo esc_attr((string) ($settings['free_shipping_classes'] ?? 'free-shipping')); ?>"
                                    class="regular-text"
                                />
                                <p class="description"><?php esc_html_e('Comma-separated product shipping-class slugs that count as free shipping.', 'marks'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Appearance', 'marks'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="marks_shape"><?php esc_html_e('Badge shape', 'marks'); ?></label>
                            </th>
                            <td>
                                <select id="marks_shape" name="<?php echo esc_attr(self::OPTION); ?>[shape]">
                                    <?php
                                    $currentShape = (string) ($settings['shape'] ?? 'pill');
                                    $shapeLabels  = [
                                        'pill'   => __('Pill (rounded)', 'marks'),
                                        'square' => __('Square', 'marks'),
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
                        <?php $this->checkboxRow('uppercase', __('Uppercase labels', 'marks'), __('Render badge labels in uppercase.', 'marks'), $settings); ?>
                        <?php
                        $this->numberRow('max_badges_single', __('Max badges (product page)', 'marks'), __('Maximum number of badges shown on a single product page.', 'marks'), $settings, 1);
                        $this->numberRow('max_badges_loop', __('Max badges (listings)', 'marks'), __('Maximum number of badges shown on shop and category listings.', 'marks'), $settings, 1);
                        ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Manual badge', 'marks'); ?></h2>
                <p class="description">
                    <?php esc_html_e('A store-wide manual badge. Leave the label empty to disable it; set the per-product meta _marks_manual_text to show it on a product.', 'marks'); ?>
                </p>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="marks_manual_badge_text"><?php esc_html_e('Manual badge label', 'marks'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="marks_manual_badge_text"
                                    name="<?php echo esc_attr(self::OPTION); ?>[manual_badge_text]"
                                    value="<?php echo esc_attr((string) ($settings['manual_badge_text'] ?? '')); ?>"
                                    class="regular-text"
                                />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="marks_manual_badge_style"><?php esc_html_e('Manual badge colour', 'marks'); ?></label>
                            </th>
                            <td>
                                <select
                                    id="marks_manual_badge_style"
                                    name="<?php echo esc_attr(self::OPTION); ?>[manual_badge_style]"
                                >
                                    <?php
                                    $current = (string) ($settings['manual_badge_style'] ?? 'accent');
                                    foreach (self::STYLES as $style) :
                                        ?>
                                        <option value="<?php echo esc_attr($style); ?>" <?php selected($current, $style); ?>>
                                            <?php echo esc_html(ucfirst($style)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php do_action('marks_admin_settings_after_manual_table', $settings); ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a single checkbox row in the form-table.
     *
     * @param array<string, mixed> $settings
     */
    private function checkboxRow(string $key, string $label, string $help, array $settings): void
    {
        $id = 'marks_' . $key;
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
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
    private function numberRow(string $key, string $label, string $help, array $settings, int $min): void
    {
        $id = 'marks_' . $key;
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
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
    private function autoBadgeRow(string $rule, string $label, string $help, array $settings, bool $hasLabel): void
    {
        $toggleKey = 'show_' . $rule . '_badge';
        $labelKey  = $rule . '_badge_text';
        $toggleId  = 'marks_' . $toggleKey;
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
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
                        placeholder="<?php esc_attr_e('Custom label (optional)', 'marks'); ?>"
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
