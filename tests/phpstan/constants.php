<?php
/**
 * Constants needed by PHPStan to analyse the plugin without bootstrapping WordPress.
 *
 * @package Marks
 */

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (! defined('MARKS_DIR')) {
        define('MARKS_DIR', '/tmp/marks/');
    }
    if (! defined('MARKS_URL')) {
        define('MARKS_URL', 'https://example.test/wp-content/plugins/marks/');
    }
}

namespace Marks {
    if (! defined('Marks\\VERSION')) {
        define('Marks\\VERSION', '0.2.0');
    }
    if (! defined('Marks\\PLUGIN_FILE')) {
        define('Marks\\PLUGIN_FILE', '/tmp/marks/marks.php');
    }
}
