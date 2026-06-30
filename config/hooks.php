<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Plogins\Marks\Contract\HasHooks.
 *
 * @package Marks
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Plogins\Marks\Admin\Settings;
use Plogins\Marks\Service\MarksService;

defined('ABSPATH') || exit;

return is_admin()
    ? [
        MarksService::class,
        Settings::class,
    ]
    : [
        MarksService::class,
    ];
