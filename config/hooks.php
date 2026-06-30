<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Marks\Contract\HasHooks.
 *
 * @package Marks
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Marks\Admin\Settings;
use Marks\Service\MarksService;

defined('ABSPATH') || exit;

return is_admin()
    ? [
        MarksService::class,
        Settings::class,
    ]
    : [
        MarksService::class,
    ];
