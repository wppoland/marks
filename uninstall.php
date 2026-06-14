<?php
/**
 * Uninstall cleanup for Marks.
 *
 * Runs when the plugin is deleted from wp-admin. Removes the options Marks
 * creates. The per-product manual badge meta (_marks_manual_text /
 * _marks_manual_style) is intentionally left in place: it is user content that
 * may be shared with other tools and is cheap to leave.
 *
 * @package Marks
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('marks_settings');
delete_option('marks_db_version');
