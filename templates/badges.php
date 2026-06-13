<?php
/**
 * CSS-only product badge group, rendered on the storefront by the
 * storefront-kit BadgeEngine. No JavaScript, no layout shift.
 *
 * @package Marks
 *
 * @var list<\WPPoland\StorefrontKit\Badge\Badge> $badges
 * @var string $context  Render context: `single` or `loop`.
 * @var string $shape    Badge shape hint: `pill` or `square`.
 * @var bool   $uppercase Whether labels render uppercase.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to the template include scope, not true globals.

if (empty($badges) || ! is_array($badges)) {
    return;
}

$shape     = isset($shape) && $shape === 'square' ? 'square' : 'pill';
$context   = isset($context) && $context === 'loop' ? 'loop' : 'single';
$uppercase = ! empty($uppercase);

$groupClasses = sprintf(
    'marks-badges marks-badges--%s marks-badges--%s%s',
    sanitize_html_class($context),
    sanitize_html_class($shape),
    $uppercase ? ' marks-badges--upper' : '',
);
?>
<div class="<?php echo esc_attr($groupClasses); ?>">
    <?php foreach ($badges as $badge) : ?>
        <span class="marks-badge marks-badge--<?php echo esc_attr(sanitize_html_class($badge->style)); ?>">
            <?php echo esc_html($badge->text); ?>
        </span>
    <?php endforeach; ?>
</div>
