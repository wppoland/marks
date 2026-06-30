<?php
/**
 * Autoloading: prefer Composer's vendor autoloader (the optimized classmap).
 * Fall back to a minimal PSR-4 autoloader so the plugin still boots if vendor/
 * is somehow absent.
 *
 * @package Marks
 */

declare(strict_types=1);

namespace Plogins\Marks;

defined('ABSPATH') || exit;

$marks_composer = __DIR__ . '/vendor/autoload.php';
if (is_readable($marks_composer)) {
    require_once $marks_composer;
    return;
}

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Plogins\\Marks\\'           => __DIR__ . '/src/',
        'WPPoland\\StorefrontKit\\'    => __DIR__ . '/lib/storefront-kit/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relative = substr($class, $len);
        $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_readable($file)) {
            require_once $file;
        }
        return;
    }
});
