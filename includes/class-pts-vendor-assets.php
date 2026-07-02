<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Bundled third-party assets copied into generated themes (no remote CDN).
 */
class Picotse_Vendor_Assets
{
    const BOOTSTRAP_VERSION = '5.3.8';

    const ANIMEJS_VERSION = '4.4.1';

    /**
     * @return string Absolute path to plugin vendor directory.
     */
    public static function get_source_dir()
    {
        return PICOTSE_PLUGIN_DIR . 'assets/vendor/';
    }

    /**
     * Copy Bootstrap and Anime.js into a generated theme directory.
     *
     * @param string $theme_dir Absolute theme root path.
     * @return true|WP_Error
     */
    public static function copy_to_theme($theme_dir)
    {
        $files = array(
            'bootstrap/bootstrap.min.css',
            'bootstrap/bootstrap.bundle.min.js',
            'animejs/anime.umd.min.js',
        );

        foreach ($files as $relative) {
            $source = self::get_source_dir() . $relative;
            $dest   = $theme_dir . '/assets/vendor/' . $relative;

            if (! file_exists($source)) {
                /* translators: %s: vendor file path. */
                return new WP_Error('vendor_missing', sprintf(__('Bundled vendor file is missing: %s', 'picot-theme-seeder'), $relative));
            }

            wp_mkdir_p(dirname($dest));

            if (! copy($source, $dest)) {
                /* translators: %s: vendor file path. */
                return new WP_Error('vendor_copy_failed', sprintf(__('Could not copy vendor file: %s', 'picot-theme-seeder'), $relative));
            }
        }

        return true;
    }

    /**
     * PHP lines for wp_enqueue_* using theme-local vendor files.
     *
     * @param string $theme_slug     Theme slug.
     * @param string $style_version  PHP expression for stylesheet version.
     * @param string $script_version PHP expression for script version.
     * @return string
     */
    public static function get_enqueue_php($theme_slug, $style_version = "'1.0.0'", $script_version = "'1.0.0'")
    {
        $bootstrap_style_handle = $theme_slug . '-bootstrap';
        $bootstrap_script_handle = $theme_slug . '-bootstrap-bundle';
        $anime_handle            = $theme_slug . '-animejs';
        $vendor_uri              = "get_template_directory_uri() . '/assets/vendor'";

        $php  = "    wp_enqueue_style('{$bootstrap_style_handle}', {$vendor_uri} . '/bootstrap/bootstrap.min.css', array(), '" . self::BOOTSTRAP_VERSION . "');\n";
        $php .= "    wp_enqueue_style('{$theme_slug}-style', get_stylesheet_uri(), array('{$bootstrap_style_handle}'), {$style_version});\n";
        $php .= "    wp_enqueue_script('{$bootstrap_script_handle}', {$vendor_uri} . '/bootstrap/bootstrap.bundle.min.js', array(), '" . self::BOOTSTRAP_VERSION . "', true);\n";
        $php .= "    wp_enqueue_script('{$anime_handle}', {$vendor_uri} . '/animejs/anime.umd.min.js', array(), '" . self::ANIMEJS_VERSION . "', true);\n";
        $php .= "    wp_enqueue_script('{$theme_slug}-animate-init', get_template_directory_uri() . '/assets/js/animate-init.js', array('{$anime_handle}'), {$script_version}, true);\n";

        return $php;
    }

    /**
     * PHP for custom login screen styling via wp_add_inline_style().
     *
     * @param string $func_prefix Function prefix derived from theme slug.
     * @param string $theme_slug  Theme slug.
     * @return string
     */
    public static function get_login_inline_style_php($func_prefix, $theme_slug)
    {
        $handle = $theme_slug . '-login';

        $php  = "/**\n * Custom login screen styling.\n */\n";
        $php .= "function {$func_prefix}_login_styles() {\n";
        $php .= "    wp_register_style('{$handle}', false, array(), '1.0.0');\n";
        $php .= "    wp_enqueue_style('{$handle}');\n";
        $php .= "    wp_add_inline_style(\n";
        $php .= "        '{$handle}',\n";
        $php .= "        '#login h1 a { background-image: none !important; text-indent: 0 !important; width: auto !important; height: auto !important; }'\n";
        $php .= "    );\n";
        $php .= "}\n";
        $php .= "add_action('login_enqueue_scripts', '{$func_prefix}_login_styles');\n\n";

        return $php;
    }
}
