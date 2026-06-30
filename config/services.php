<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container.
 *
 * @package Marks
 */

declare(strict_types=1);

use Plogins\Marks\Admin\Settings;
use Plogins\Marks\Container;
use Plogins\Marks\Migrator;
use Plogins\Marks\Service\MarksService;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    $c->singleton(MarksService::class, static fn (): MarksService => new MarksService());

    // Admin (only needed in wp-admin context).
    if (is_admin()) {
        $c->singleton(Settings::class, static fn (): Settings => new Settings());
    }
};
