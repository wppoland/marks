<?php

declare(strict_types=1);

namespace Marks\Admin;

defined('ABSPATH') || exit;

use Marks\Contract\HasHooks;

/**
 * Admin settings page registered as a top-level "Marks" menu.
 *
 * Stores settings in the `marks_settings` option (array): the automatic badge
 * toggles (sale / new / low-stock / bestseller) with a low-stock threshold,
 * plus a single store-wide manual badge label and colour. All output is
 * escaped; all input is sanitised and clamped on save.
 */
final class Settings implements HasHooks
{
    private const OPTION = 'marks_settings';
    private const PAGE   = 'marks-settings';

    /** Allowed manual badge colour keys (mapped to CSS classes by the template). */
    private const STYLES = ['accent', 'success', 'warning', 'danger', 'neutral'];

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

                <h2><?php esc_html_e('Automatic badges', 'marks'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Badges that appear on their own based on each product\'s state. CSS-only, no layout shift.', 'marks'); ?>
                </p>

                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->checkboxRow('show_sale_badge', __('Sale', 'marks'), __('On products currently on sale.', 'marks'), $settings);
                        $this->checkboxRow('show_new_badge', __('New', 'marks'), __('On products created within the newness window.', 'marks'), $settings);
                        $this->checkboxRow('show_low_stock_badge', __('Low stock', 'marks'), __('On stock-managed products at or below the threshold.', 'marks'), $settings);
                        $this->checkboxRow('show_bestseller_badge', __('Bestseller', 'marks'), __('On products at or above the sales threshold.', 'marks'), $settings);
                        ?>
                        <tr>
                            <th scope="row">
                                <label for="marks_low_stock_threshold"><?php esc_html_e('Low-stock threshold', 'marks'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    id="marks_low_stock_threshold"
                                    name="<?php echo esc_attr(self::OPTION); ?>[low_stock_threshold]"
                                    value="<?php echo esc_attr((string) (int) ($settings['low_stock_threshold'] ?? 3)); ?>"
                                    class="small-text"
                                />
                                <p class="description"><?php esc_html_e('Show the low-stock badge when remaining stock is at or below this number.', 'marks'); ?></p>
                            </td>
                        </tr>
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

                <?php do_action( 'marks_admin_settings_after_manual_table', $settings ); ?>

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

        $sanitized = [
            'enabled'               => ! empty($raw['enabled']),
            'show_sale_badge'       => ! empty($raw['show_sale_badge']),
            'show_new_badge'        => ! empty($raw['show_new_badge']),
            'show_low_stock_badge'  => ! empty($raw['show_low_stock_badge']),
            'show_bestseller_badge' => ! empty($raw['show_bestseller_badge']),
            'low_stock_threshold'   => max(1, isset($raw['low_stock_threshold']) ? (int) $raw['low_stock_threshold'] : 3),
            'show_manual_badge'     => true,
            'manual_badge_text'     => isset($raw['manual_badge_text']) ? sanitize_text_field((string) $raw['manual_badge_text']) : '',
            'manual_badge_style'    => $style,
        ];

        return (array) apply_filters('marks_sanitize_settings', $sanitized, $raw);
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
