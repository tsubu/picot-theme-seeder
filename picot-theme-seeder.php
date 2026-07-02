<?php

/**
 * Plugin Name: Picot Theme Seeder
 * Description: Generate complete, ready-to-use Block (FSE) or Classic WordPress themes from a visual admin wizard.
 * Version: 1.0.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Author: PICOT
 * Author URI: https://picot.tokyo/aio/
 * Text Domain: picot-theme-seeder
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('ABSPATH')) {
    exit;
}

define('PICOTSE_VERSION', '1.0.0');
define('PICOTSE_MIN_WP_VERSION', '7.0');
define('PICOTSE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PICOTSE_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once PICOTSE_PLUGIN_DIR . 'includes/class-pts-layout-settings.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-pts-editor-styles.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-pts-scss.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-pts-screenshot.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-pts-vendor-assets.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-pts-animejs.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-pts-classic-definitions.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-pts-admin.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-bts-generator.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-bts-theme-json.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-bts-zip.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-cts-generator.php';
require_once PICOTSE_PLUGIN_DIR . 'includes/class-cts-zip.php';

/**
 * Whether the site meets the minimum WordPress version.
 *
 * Uses core is_wp_version_compatible() so RC/beta strings (e.g. 7.0-RC5) compare correctly.
 *
 * @return bool
 */
function picotse_meets_wp_requirements()
{
    if (function_exists('is_wp_version_compatible')) {
        return is_wp_version_compatible(PICOTSE_MIN_WP_VERSION);
    }

    global $wp_version;
    $raw = ! empty($wp_version) ? $wp_version : get_bloginfo('version');
    list($version) = explode('-', $raw);

    return version_compare($version, PICOTSE_MIN_WP_VERSION, '>=');
}

/**
 * Admin notice when WordPress is too old.
 */
function picotse_admin_notice_wp_version()
{
    if (! current_user_can('activate_plugins')) {
        return;
    }

    echo '<div class="notice notice-error"><p>';
    $current = function_exists('wp_get_wp_version') ? wp_get_wp_version() : get_bloginfo('version');

    echo esc_html(
        sprintf(
            /* translators: 1: minimum WordPress version, 2: detected version string */
            __('Picot Theme Seeder requires WordPress %1$s or later. This site is running %2$s.', 'picot-theme-seeder'),
            PICOTSE_MIN_WP_VERSION,
            $current
        )
    );
    echo '</p></div>';
}

/**
 * Initialize the plugin.
 */
function picotse_init()
{
    if (! picotse_meets_wp_requirements()) {
        add_action('admin_notices', 'picotse_admin_notice_wp_version');
        return;
    }

    $admin = new Picotse_Admin();
    $admin->init();
}
add_action('plugins_loaded', 'picotse_init');
