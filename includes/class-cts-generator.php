<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Core Generator Logic for Classic Themes
 */
class CTS_Generator
{
    /**
     * @param string $path
     * @param string $content
     * @return true|WP_Error
     */
    private function write_file($path, $content)
    {
        if (false === file_put_contents($path, $content)) {
            /* translators: %s: file name. */
            return new WP_Error('write_failed', sprintf(__('Could not write file: %s', 'picot-theme-seeder'), basename($path)));
        }

        return true;
    }

    public function generate($data)
    {
        PTS_Admin::cleanup_stale_temp_dirs('classic');

        // 1. Prepare Data
        $theme_name = isset($data['themeName']) ? sanitize_text_field($data['themeName']) : 'My Classic Theme';
        $theme_slug = isset($data['themeSlug']) ? sanitize_title($data['themeSlug']) : 'my-classic-theme';
        $author = isset($data['themeAuthor']) ? sanitize_text_field($data['themeAuthor']) : '';
        $author_uri = isset($data['themeAuthorUri']) ? esc_url_raw($data['themeAuthorUri']) : '';
        $description = isset($data['themeDescription']) ? sanitize_textarea_field($data['themeDescription']) : '';

        $selection = isset($data['selection']) ? $data['selection'] : array();
        $layout    = PTS_Layout_Settings::parse($data);

        // 2-column layout is the default when menus or widgets are enabled.
        if (! empty($selection['features.menus']) || ! empty($selection['features.widgets'])) {
            $selection['templates.sidebar'] = 1;
        }

        // 2. Setup Temp Directory
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/pts_temp/classic/' . uniqid();
        $theme_dir = $base_dir . '/' . $theme_slug;

        if (! wp_mkdir_p($theme_dir)) {
            return new WP_Error('mkdir_failed', __('Could not create temporary directory.', 'picot-theme-seeder'));
        }

        // 3. Generate style.css (Required) - WP Theme Handbook: header + base styles
        $style_header = "/*\n";
        $style_header .= "Theme Name: " . $theme_name . "\n";
        $style_header .= "Theme URI: https://developer.wordpress.org/themes/\n";
        $style_header .= "Author: " . $author . "\n";
        if ($author_uri) {
            $style_header .= "Author URI: " . $author_uri . "\n";
        }
        $style_header .= "Description: " . $description . "\n";
        $style_header .= "Version: 1.0.0\n";
        $style_header .= "License: GNU General Public License v2 or later\n";
        $style_header .= "License URI: https://www.gnu.org/licenses/gpl-2.0.html\n";
        $style_header .= "*/\n";

        $style_content = $style_header . "\n";
        $style_content .= PTS_Layout_Settings::get_root_stylesheet($layout);
        $style_content .= $this->get_base_styles();
        $result = $this->write_file($theme_dir . '/style.css', $style_content);
        if (is_wp_error($result)) {
            return $result;
        }

        wp_mkdir_p($theme_dir . '/assets/js');
        $result = $this->write_file($theme_dir . '/assets/js/animate-init.js', PTS_Animejs::get_init_js());
        if (is_wp_error($result)) {
            return $result;
        }

        // 4. Generate index.php (Required)
        $main_col      = $this->main_column_class($selection);
        $index_content = $this->get_template_header($theme_name);
        $index_content .= "<?php get_header(); ?>\n\n";
        $index_content .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
        $index_content .= "    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>\n";
        $index_content .= "        <article id=\"post-<?php the_ID(); ?>\" <?php post_class('entry-card card border-0 shadow-sm mb-4'); ?>>\n";
        $index_content .= "            <div class=\"card-body p-4 p-lg-5\">\n";
        $index_content .= "            <header class=\"entry-header mb-3\"> <?php the_title('<h1 class=\"entry-title display-6 fw-bold mb-0\">', '</h1>'); ?> </header>\n";
        $index_content .= "            <div class=\"entry-content\"> <?php the_content(); ?> </div>\n";
        $index_content .= "            </div>\n";
        $index_content .= "        </article>\n";
        $index_content .= "    <?php endwhile; the_posts_navigation(); else : get_template_part('template-parts/content', 'none'); endif; ?>\n";
        $index_content .= "</main>\n\n";
        $index_content .= $this->sidebar_call($selection);
        $index_content .= "<?php get_footer(); ?>\n";
        $result = $this->write_file($theme_dir . '/index.php', $index_content);
        if (is_wp_error($result)) {
            return $result;
        }

        // 5. Generate Basic Theme Files if selected
        $templates = array(
            'header', 'footer', 'sidebar', 'comments', 'searchform', 'embed',
            'single', 'page', 'singular', 'attachment', 'image', 'video', 'audio', 'privacy-policy',
            'archive', 'category', 'tag', 'taxonomy', 'author', 'date', 'paged',
            'front-page', 'home', 'search', '404',
            'template-full-width', 'template-no-sidebar',
            'parts-content', 'parts-content-single', 'parts-content-page', 'parts-content-search', 'parts-content-none'
        );

        foreach ($templates as $t) {
            if (isset($selection['templates.' . $t]) && $selection['templates.' . $t]) {
                if (strpos($t, 'parts-') === 0) {
                    $part_name = str_replace('parts-', '', $t);
                    $part_dir = $theme_dir . '/template-parts';
                    if (!is_dir($part_dir)) {
                        wp_mkdir_p($part_dir);
                    }
                    $content = $this->get_template_content($t, $theme_name, $theme_slug, $selection);
                        $result = $this->write_file($part_dir . '/' . $part_name . '.php', $content);
                        if (is_wp_error($result)) {
                            return $result;
                        }
                        // get_post_type() で 'post' が使われるため、content-single と同内容で content-post.php も出力
                        if ($t === 'parts-content-single') {
                            $result = $this->write_file($part_dir . '/content-post.php', $content);
                            if (is_wp_error($result)) {
                                return $result;
                            }
                        }
                    } else {
                        $content = $this->get_template_content($t, $theme_name, $theme_slug, $selection);
                        $result = $this->write_file($theme_dir . '/' . $t . '.php', $content);
                        if (is_wp_error($result)) {
                            return $result;
                        }
                    }
                }
            }

        if (! empty($selection['templates.sidebar'])) {
            $part_dir = $theme_dir . '/template-parts';
            if (! is_dir($part_dir)) {
                wp_mkdir_p($part_dir);
            }
            $func_prefix = str_replace('-', '_', $theme_slug);
            $result = $this->write_file($part_dir . '/sidebar-nav.php', $this->get_sidebar_nav_template_content($func_prefix));
            if (is_wp_error($result)) {
                return $result;
            }
            $result = $this->write_file($part_dir . '/sidebar-widgets.php', $this->get_sidebar_widgets_template_content());
            if (is_wp_error($result)) {
                return $result;
            }
        }

        // Embed template requires minimal header/footer variants.
        if (! empty($selection['templates.embed'])) {
            foreach (array('header-embed', 'footer-embed') as $embed_tpl) {
                $embed_content = $this->get_template_content($embed_tpl, $theme_name, $theme_slug, $selection);
                $result        = $this->write_file($theme_dir . '/' . $embed_tpl . '.php', $embed_content);
                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        // 8. Generate functions.php
        $func_prefix = str_replace('-', '_', $theme_slug);
        $functions_content = "<?php\n\n";
        $functions_content .= "/**\n * " . $theme_name . " theme functions and definitions\n */\n\n";
        $functions_content .= "// Multibyte-safe: use UTF-8 so Japanese and other languages work correctly.\n";
        $functions_content .= "if (function_exists('mb_internal_encoding')) {\n";
        $functions_content .= "    mb_internal_encoding('UTF-8');\n";
        $functions_content .= "}\n\n";
        $functions_content .= "/**\n * Multibyte-safe string length (UTF-8). Use when replacing English with Japanese etc.\n */\n";
        $functions_content .= "if (!function_exists('{$func_prefix}_strlen')) {\n";
        $functions_content .= "    function {$func_prefix}_strlen(\$str) {\n";
        $functions_content .= "        return function_exists('mb_strlen') ? mb_strlen(\$str, 'UTF-8') : strlen(\$str);\n";
        $functions_content .= "    }\n";
        $functions_content .= "}\n\n";
        $functions_content .= "/**\n * Multibyte-safe substring (UTF-8). Use when replacing English with Japanese etc.\n */\n";
        $functions_content .= "if (!function_exists('{$func_prefix}_substr')) {\n";
        $functions_content .= "    function {$func_prefix}_substr(\$str, \$start, \$length = null) {\n";
        $functions_content .= "        if (function_exists('mb_substr')) {\n";
        $functions_content .= "            return \$length !== null ? mb_substr(\$str, \$start, \$length, 'UTF-8') : mb_substr(\$str, \$start, null, 'UTF-8');\n";
        $functions_content .= "        }\n";
        $functions_content .= "        return \$length !== null ? substr(\$str, \$start, \$length) : substr(\$str, \$start);\n";
        $functions_content .= "    }\n";
        $functions_content .= "}\n\n";

        // Main Theme Setup
        $functions_content .= "if (!function_exists('{$func_prefix}_setup')) :\n";
        $functions_content .= "function {$func_prefix}_setup() {\n";
        if (isset($selection['features.title-tag']) && $selection['features.title-tag']) {
            $functions_content .= "    add_theme_support('title-tag');\n";
        }
        if (isset($selection['features.post-thumbnails']) && $selection['features.post-thumbnails']) {
            $functions_content .= "    add_theme_support('post-thumbnails');\n";
        }
        if (isset($selection['features.html5']) && $selection['features.html5']) {
            $functions_content .= "    add_theme_support('html5', array('comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script'));\n";
        }
        if (isset($selection['features.automatic-feed-links']) && $selection['features.automatic-feed-links']) {
            $functions_content .= "    add_theme_support('automatic-feed-links');\n";
        }
        if (isset($selection['features.custom-logo']) && $selection['features.custom-logo']) {
            $functions_content .= "    add_theme_support('custom-logo', array('height' => 250, 'width' => 250, 'flex-width' => true, 'flex-height' => true));\n";
        }
        if (isset($selection['features.custom-header']) && $selection['features.custom-header']) {
            $functions_content .= "    add_theme_support('custom-header', array('default-image' => '', 'width' => 1000, 'height' => 250, 'flex-height' => true));\n";
        }
        if (isset($selection['features.custom-background']) && $selection['features.custom-background']) {
            $functions_content .= "    add_theme_support('custom-background', array('default-color' => 'ffffff', 'default-image' => ''));\n";
        }
        if (isset($selection['features.post-formats']) && $selection['features.post-formats']) {
            $functions_content .= "    add_theme_support('post-formats', array('aside', 'gallery', 'quote', 'image', 'video', 'audio', 'link'));\n";
        }

        // Gutenberg Support
        if (isset($selection['features.align-wide']) && $selection['features.align-wide']) {
            $functions_content .= "    add_theme_support('align-wide');\n";
        }
        if (isset($selection['features.wp-block-styles']) && $selection['features.wp-block-styles']) {
            $functions_content .= "    add_theme_support('wp-block-styles');\n";
        }
        if (isset($selection['features.responsive-embeds']) && $selection['features.responsive-embeds']) {
            $functions_content .= "    add_theme_support('responsive-embeds');\n";
        }
        if (isset($selection['features.editor-styles']) && $selection['features.editor-styles']) {
            $functions_content .= "    add_theme_support('editor-styles');\n";
            $functions_content .= "    add_editor_style('style-editor.css');\n";
            
            $editor_style = "/* Editor Styles (aligned with frontend) */\n";
            $editor_style .= PTS_Editor_Styles::get_editor_stylesheet($layout);
            $result = $this->write_file($theme_dir . '/style-editor.css', $editor_style);
            if (is_wp_error($result)) {
                return $result;
            }
        }
        if (isset($selection['features.appearance-tools']) && $selection['features.appearance-tools']) {
            $functions_content .= "    add_theme_support('appearance-tools');\n";
        }
        if (isset($selection['features.editor-color-palette']) && $selection['features.editor-color-palette']) {
            $functions_content .= "    add_theme_support('editor-color-palette', array(\n";
            $functions_content .= "        array('name' => esc_html('Primary'), 'slug' => 'primary', 'color' => '#0073aa'),\n";
            $functions_content .= "        array('name' => esc_html('Secondary'), 'slug' => 'secondary', 'color' => '#23282d'),\n";
            $functions_content .= "        array('name' => esc_html('White'), 'slug' => 'white', 'color' => '#ffffff'),\n";
            $functions_content .= "    ));\n";
        }
        if (isset($selection['features.disable-custom-colors']) && $selection['features.disable-custom-colors']) {
            $functions_content .= "    add_theme_support('disable-custom-colors');\n";
        }
        if (isset($selection['features.editor-font-sizes']) && $selection['features.editor-font-sizes']) {
            $functions_content .= "    add_theme_support('editor-font-sizes', array(\n";
            $functions_content .= "        array('name' => esc_html('Small'), 'size' => '0.875rem', 'slug' => 'small'),\n";
            $functions_content .= "        array('name' => esc_html('Medium'), 'size' => '1.125rem', 'slug' => 'medium'),\n";
            $functions_content .= "        array('name' => esc_html('Large'), 'size' => '1.75rem', 'slug' => 'large'),\n";
            $functions_content .= "        array('name' => esc_html('Extra Large'), 'size' => '2.25rem', 'slug' => 'x-large'),\n";
            $functions_content .= "    ));\n";
        }
        if (isset($selection['features.disable-custom-font-sizes']) && $selection['features.disable-custom-font-sizes']) {
            $functions_content .= "    add_theme_support('disable-custom-font-sizes');\n";
        }

        if (isset($selection['features.editor-gradient-presets']) && $selection['features.editor-gradient-presets']) {
            $functions_content .= "    add_theme_support('editor-gradient-presets', array(\n";
            $functions_content .= "        array('name' => esc_html('Vivid Cyan Blue to Vivid Purple'), 'gradient' => 'linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%)', 'slug' => 'vivid-cyan-blue-to-vivid-purple'),\n";
            $functions_content .= "        array('name' => esc_html('Vivid Red to Lumber'), 'gradient' => 'linear-gradient(135deg,rgb(207,46,46) 0%,rgb(209,179,145) 100%)', 'slug' => 'vivid-red-to-lumber'),\n";
            $functions_content .= "    ));\n";
        }
        if (isset($selection['features.disable-custom-gradients']) && $selection['features.disable-custom-gradients']) {
            $functions_content .= "    add_theme_support('disable-custom-gradients');\n";
        }
        if (isset($selection['features.custom-line-height']) && $selection['features.custom-line-height']) {
            $functions_content .= "    add_theme_support('custom-line-height');\n";
        }
        if (isset($selection['features.custom-spacing']) && $selection['features.custom-spacing']) {
            $functions_content .= "    add_theme_support('custom-spacing');\n";
        }
        if (isset($selection['features.custom-units']) && $selection['features.custom-units']) {
            $functions_content .= "    add_theme_support('custom-units', array('px', 'rem', 'em', '%', 'vh', 'vw'));\n";
        }
        if (isset($selection['features.link-color']) && $selection['features.link-color']) {
            $functions_content .= "    add_theme_support('link-color');\n";
        }
        if (isset($selection['features.border']) && $selection['features.border']) {
            $functions_content .= "    add_theme_support('border');\n";
        }
        if (isset($selection['features.editor-spacing-sizes']) && $selection['features.editor-spacing-sizes']) {
            $functions_content .= "    add_theme_support('editor-spacing-sizes', array(\n";
            $functions_content .= "        array('name' => esc_html('Small'), 'size' => '0.5rem', 'slug' => 'small'),\n";
            $functions_content .= "        array('name' => esc_html('Medium'), 'size' => '1rem', 'slug' => 'medium'),\n";
            $functions_content .= "        array('name' => esc_html('Large'), 'size' => '1.5rem', 'slug' => 'large'),\n";
            $functions_content .= "        array('name' => esc_html('X-Large'), 'size' => '2rem', 'slug' => 'x-large'),\n";
            $functions_content .= "    ));\n";
        }
        if (isset($selection['features.disable-layout-styles']) && $selection['features.disable-layout-styles']) {
            $functions_content .= "    add_theme_support('disable-layout-styles');\n";
        }
        if (isset($selection['features.widgets-block-editor']) && $selection['features.widgets-block-editor']) {
            $functions_content .= "    add_theme_support('widgets-block-editor');\n";
        }
        if (isset($selection['features.starter-content']) && $selection['features.starter-content']) {
            $functions_content .= "    add_theme_support('starter-content', array());\n";
        }
        if (isset($selection['features.woocommerce']) && $selection['features.woocommerce']) {
            $functions_content .= "    add_theme_support('woocommerce');\n";
        }
        if (isset($selection['features.selective-refresh']) && $selection['features.selective-refresh']) {
            $functions_content .= "    add_theme_support('customize-selective-refresh-widgets');\n";
        }

        // Menus
        if (isset($selection['features.menus']) && $selection['features.menus']) {
            if (isset($selection['features.multi-menus']) && $selection['features.multi-menus']) {
                $functions_content .= "    register_nav_menus(array(\n";
                $functions_content .= "        'primary' => esc_html('Primary Menu'),\n";
                $functions_content .= "        'footer'  => esc_html('Footer Menu'),\n";
                $functions_content .= "        'mobile'  => esc_html('Mobile Menu'),\n";
                $functions_content .= "        'social'  => esc_html('Social Links'),\n";
                $functions_content .= "    ));\n";
            } else {
                $functions_content .= "    register_nav_menus(array('primary' => esc_html('Primary Menu')));\n";
            }
        }

        $functions_content .= "}\n";
        $functions_content .= "endif;\n";
        $functions_content .= "add_action('after_setup_theme', '{$func_prefix}_setup');\n\n";

        if (isset($selection['features.menus']) && $selection['features.menus']) {
            $functions_content .= "/**\n * Auto-assign primary menu when no location is set.\n */\n";
            $functions_content .= "if (!function_exists('{$func_prefix}_assign_primary_menu')) :\n";
            $functions_content .= "function {$func_prefix}_assign_primary_menu() {\n";
            $functions_content .= "    \$locations = get_nav_menu_locations();\n";
            $functions_content .= "    if (!empty(\$locations['primary'])) {\n";
            $functions_content .= "        return;\n";
            $functions_content .= "    }\n";
            $functions_content .= "    \$menus = wp_get_nav_menus();\n";
            $functions_content .= "    if (empty(\$menus)) {\n";
            $functions_content .= "        return;\n";
            $functions_content .= "    }\n";
            $functions_content .= "    \$chosen = null;\n";
            $functions_content .= "    foreach (\$menus as \$menu) {\n";
            $functions_content .= "        if ('head_navi' === \$menu->slug || 'head_navi' === \$menu->name) {\n";
            $functions_content .= "            \$chosen = \$menu;\n";
            $functions_content .= "            break;\n";
            $functions_content .= "        }\n";
            $functions_content .= "    }\n";
            $functions_content .= "    if (!\$chosen) {\n";
            $functions_content .= "        \$chosen = \$menus[0];\n";
            $functions_content .= "    }\n";
            $functions_content .= "    \$locations['primary'] = (int) \$chosen->term_id;\n";
            $functions_content .= "    set_theme_mod('nav_menu_locations', \$locations);\n";
            $functions_content .= "}\n";
            $functions_content .= "endif;\n";
            $functions_content .= "add_action('after_setup_theme', '{$func_prefix}_assign_primary_menu', 20);\n\n";
        }

        if ((isset($selection['features.menus']) && $selection['features.menus'])
            || (isset($selection['templates.sidebar']) && $selection['templates.sidebar'])) {
            $functions_content .= "/**\n * Build primary navigation markup once per request.\n */\n";
            $functions_content .= "if (!function_exists('{$func_prefix}_get_primary_nav_html')) :\n";
            $functions_content .= "function {$func_prefix}_get_primary_nav_html() {\n";
            $functions_content .= "    static \$html = null;\n";
            $functions_content .= "    if (null !== \$html) {\n";
            $functions_content .= "        return \$html;\n";
            $functions_content .= "    }\n";
            $functions_content .= "    \$locations = get_nav_menu_locations();\n";
            $functions_content .= "    \$args = array(\n";
            $functions_content .= "        'menu_class'  => 'nav flex-column gap-1',\n";
            $functions_content .= "        'container'   => false,\n";
            $functions_content .= "        'fallback_cb' => 'wp_page_menu',\n";
            $functions_content .= "        'depth'       => 2,\n";
            $functions_content .= "        'echo'        => false,\n";
            $functions_content .= "        'menu_id'     => 'pts-primary-nav',\n";
            $functions_content .= "    );\n";
            $functions_content .= "    if (!empty(\$locations['primary'])) {\n";
            $functions_content .= "        \$args['menu'] = (int) \$locations['primary'];\n";
            $functions_content .= "    } else {\n";
            $functions_content .= "        \$args['theme_location'] = 'primary';\n";
            $functions_content .= "    }\n";
            $functions_content .= "    \$html = wp_nav_menu(\$args);\n";
            $functions_content .= "    return is_string(\$html) ? \$html : '';\n";
            $functions_content .= "}\n";
            $functions_content .= "endif;\n\n";
            $functions_content .= "/**\n * Output the primary navigation menu.\n */\n";
            $functions_content .= "if (!function_exists('{$func_prefix}_primary_nav')) :\n";
            $functions_content .= "function {$func_prefix}_primary_nav(\$instance = 'main') {\n";
            $functions_content .= "    \$html = {$func_prefix}_get_primary_nav_html();\n";
            $functions_content .= "    if ('main' !== \$instance) {\n";
            $functions_content .= "        \$html = str_replace('id=\"pts-primary-nav\"', 'id=\"pts-primary-nav-' . esc_attr(\$instance) . '\"', \$html);\n";
            $functions_content .= "        \$html = preg_replace('/\\\\bid=\"menu-item-(\\\\d+)\"/', 'id=\"menu-item-$1-' . esc_attr(\$instance) . '\"', \$html);\n";
            $functions_content .= "    }\n";
            $functions_content .= "    echo \$html;\n";
            $functions_content .= "}\n";
            $functions_content .= "endif;\n\n";
        }

        if (isset($selection['features.content-width-limit']) && $selection['features.content-width-limit']) {
            $functions_content .= "/**\n * Set the content width in pixels\n */\n";
            $functions_content .= "function {$func_prefix}_content_width() {\n";
            $functions_content .= "    \$GLOBALS['content_width'] = apply_filters('{$func_prefix}_content_width', 800);\n";
            $functions_content .= "}\n";
            $functions_content .= "add_action('after_setup_theme', '{$func_prefix}_content_width', 0);\n\n";
        }

        // Widgets
        if (isset($selection['features.widgets']) && $selection['features.widgets']) {
            $functions_content .= "/**\n * Register widget area\n */\n";
            $functions_content .= "function {$func_prefix}_widgets_init() {\n";
            $functions_content .= "    register_sidebar(array(\n";
            $functions_content .= "        'name'          => esc_html('Sidebar'),\n";
            $functions_content .= "        'id'            => 'sidebar-1',\n";
            $functions_content .= "        'description'   => esc_html('Add widgets here.'),\n";
            $functions_content .= "        'before_widget' => '<section id=\"%1\$s\" class=\"widget %2\$s\">',\n";
            $functions_content .= "        'after_widget'  => '</section>',\n";
            $functions_content .= "        'before_title'  => '<h2 class=\"widget-title\">',\n";
            $functions_content .= "        'after_title'   => '</h2>',\n";
            $functions_content .= "    ));\n";
            $functions_content .= "}\n";
            $functions_content .= "add_action('widgets_init', '{$func_prefix}_widgets_init');\n\n";
        }

        // Enqueue Scripts
        $functions_content .= "/**\n * Enqueue scripts and styles\n */\n";
        $functions_content .= "function {$func_prefix}_scripts() {\n";
        $functions_content .= "    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css', array(), '5.3.8');\n";
        $functions_content .= "    wp_enqueue_style('{$theme_slug}-style', get_stylesheet_uri(), array('bootstrap'), '1.0.0');\n";
        $functions_content .= "    wp_enqueue_script('bootstrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js', array(), '5.3.8', true);\n";
        $functions_content .= PTS_Animejs::get_enqueue_script_line();
        $functions_content .= PTS_Animejs::get_enqueue_init_line($theme_slug, "'1.0.0'");
        if (isset($selection['features.threaded-comments']) && $selection['features.threaded-comments']) {
            $functions_content .= "    if (is_singular() && comments_open() && get_option('thread_comments')) {\n";
            $functions_content .= "        wp_enqueue_script('comment-reply');\n";
            $functions_content .= "    }\n";
        }
        $functions_content .= "}\n";
        $functions_content .= "add_action('wp_enqueue_scripts', '{$func_prefix}_scripts');\n\n";

        // Utilities & Cleanup
        if (isset($selection['features.remove-wp-version']) && $selection['features.remove-wp-version']) {
            $functions_content .= "// Remove WP Version\nremove_action('wp_head', 'wp_generator');\nadd_filter('the_generator', '__return_empty_string');\n\n";
        }

        if (isset($selection['features.header-cleanup']) && $selection['features.header-cleanup']) {
            $functions_content .= "// Header Cleanup\nremove_action('wp_head', 'rsd_link');\nremove_action('wp_head', 'wlwmanifest_link');\nremove_action('wp_head', 'wp_shortlink_wp_head');\nremove_action('wp_head', 'rest_output_link_wp_head');\n\n";
        }

        if (isset($selection['features.disable-emojis']) && $selection['features.disable-emojis']) {
            $functions_content .= "// Disable Emojis\nfunction {$func_prefix}_disable_emojis() {\n";
            $functions_content .= "    remove_action('wp_head', 'print_emoji_detection_script', 7);\n";
            $functions_content .= "    remove_action('admin_print_scripts', 'print_emoji_detection_script');\n";
            $functions_content .= "    remove_action('wp_print_styles', 'print_emoji_styles');\n";
            $functions_content .= "    remove_action('admin_print_styles', 'print_emoji_styles');\n";
            $functions_content .= "    remove_filter('the_content_feed', 'wp_staticize_emoji');\n";
            $functions_content .= "    remove_filter('comment_text_rss', 'wp_staticize_emoji');\n";
            $functions_content .= "    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');\n";
            $functions_content .= "}\n";
            $functions_content .= "add_action('init', '{$func_prefix}_disable_emojis');\n\n";
        }

        if (isset($selection['features.load-block-assets-on-demand']) && $selection['features.load-block-assets-on-demand']) {
            $functions_content .= "// Load block library CSS only for blocks rendered on the page (WordPress 6.8+)\n";
            $functions_content .= "add_filter('should_load_separate_core_block_assets', '__return_true');\n\n";
        }

        if (isset($selection['features.disable-block-library-css']) && $selection['features.disable-block-library-css']) {
            $functions_content .= "// Disable Block Library CSS\nadd_action('wp_enqueue_scripts', function() {\n    wp_dequeue_style('wp-block-library');\n    wp_dequeue_style('wp-block-library-theme');\n    wp_dequeue_style('wc-block-style');\n}, 100);\n\n";
        }

        if (isset($selection['features.remove-global-styles']) && $selection['features.remove-global-styles']) {
            $functions_content .= "// Remove Global Styles (Inline CSS)\n";
            $functions_content .= "add_action('init', function() {\n";
            $functions_content .= "    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');\n";
            $functions_content .= "    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);\n";
            $functions_content .= "});\n";
            $functions_content .= "add_action('wp_enqueue_scripts', function() {\n";
            $functions_content .= "    wp_dequeue_style('global-styles');\n";
            $functions_content .= "}, 100);\n\n";
        }

        if (isset($selection['features.remove-jquery-migrate']) && $selection['features.remove-jquery-migrate']) {
            $functions_content .= "// Remove jQuery Migrate\nadd_action('wp_default_scripts', function(\$scripts) {\n    if (!is_admin() && isset(\$scripts->registered['jquery'])) {\n        \$script = \$scripts->registered['jquery'];\n        if (\$script->deps) {\n            \$script->deps = array_diff(\$script->deps, array('jquery-migrate'));\n        }\n    }\n});\n\n";
        }

        if (isset($selection['features.disable-heartbeat']) && $selection['features.disable-heartbeat']) {
            $functions_content .= "// Disable Heartbeat API on the front end only (admin needs it for autosave/post lock).\n";
            $functions_content .= "add_action('init', function() {\n";
            $functions_content .= "    if (is_admin()) {\n";
            $functions_content .= "        return;\n";
            $functions_content .= "    }\n";
            $functions_content .= "    wp_deregister_script('heartbeat');\n";
            $functions_content .= "    wp_dequeue_script('heartbeat');\n";
            $functions_content .= "}, 1);\n\n";
        }

        if (isset($selection['features.lazy-load-adjust']) && $selection['features.lazy-load-adjust']) {
            $functions_content .= "// Enable Lazy Loading\nadd_filter('wp_lazy_loading_enabled', '__return_true');\n\n";
        }

        // MIME Types Support (SVG, WebP, WebM)
        $mime_types_to_add = array();
        if (isset($selection['features.svg-support']) && $selection['features.svg-support']) {
            $mime_types_to_add['svg'] = 'image/svg+xml';
        }
        if (isset($selection['features.webp-support']) && $selection['features.webp-support']) {
            $mime_types_to_add['webp'] = 'image/webp';
        }
        if (isset($selection['features.webm-support']) && $selection['features.webm-support']) {
            $mime_types_to_add['webm'] = 'video/webm';
        }

        if (!empty($mime_types_to_add)) {
            $functions_content .= "// Custom MIME Types Support\n";
            $functions_content .= "function {$func_prefix}_mime_types(\$mimes) {\n";
            if (isset($mime_types_to_add['svg'])) {
                $functions_content .= "    if (current_user_can('manage_options')) {\n";
                $functions_content .= "        \$mimes['svg'] = 'image/svg+xml';\n";
                $functions_content .= "    }\n";
                unset($mime_types_to_add['svg']);
            }
            foreach ($mime_types_to_add as $ext => $mime) {
                $functions_content .= "    \$mimes['{$ext}'] = '{$mime}';\n";
            }
            $functions_content .= "    return \$mimes;\n";
            $functions_content .= "}\n";
            $functions_content .= "add_filter('upload_mimes', '{$func_prefix}_mime_types');\n\n";
        }

        if (isset($selection['features.svg-support']) && $selection['features.svg-support']) {
            $functions_content .= "// Admin-only SVG uploads with basic validation.\n";
            $functions_content .= "function {$func_prefix}_check_svg_filetype(\$data, \$file, \$filename, \$mimes) {\n";
            $functions_content .= "    \$ext = strtolower(pathinfo(\$filename, PATHINFO_EXTENSION));\n";
            $functions_content .= "    if ('svg' === \$ext && current_user_can('manage_options')) {\n";
            $functions_content .= "        \$data['ext']  = 'svg';\n";
            $functions_content .= "        \$data['type'] = 'image/svg+xml';\n";
            $functions_content .= "    }\n";
            $functions_content .= "    return \$data;\n";
            $functions_content .= "}\n";
            $functions_content .= "add_filter('wp_check_filetype_and_ext', '{$func_prefix}_check_svg_filetype', 10, 4);\n\n";

            $functions_content .= "function {$func_prefix}_validate_svg_upload(\$file) {\n";
            $functions_content .= "    \$ext = strtolower(pathinfo(\$file['name'], PATHINFO_EXTENSION));\n";
            $functions_content .= "    if ('svg' !== \$ext) {\n";
            $functions_content .= "        return \$file;\n";
            $functions_content .= "    }\n";
            $functions_content .= "    if (! current_user_can('manage_options')) {\n";
            $functions_content .= "        \$file['error'] = esc_html('Only administrators can upload SVG files.');\n";
            $functions_content .= "        return \$file;\n";
            $functions_content .= "    }\n";
            $functions_content .= "    \$svg = file_get_contents(\$file['tmp_name']);\n";
            $functions_content .= "    if (false === \$svg) {\n";
            $functions_content .= "        \$file['error'] = esc_html('The SVG file could not be read.');\n";
            $functions_content .= "        return \$file;\n";
            $functions_content .= "    }\n";
            $functions_content .= "    if (! preg_match('/<svg\\b/i', \$svg) || preg_match('/<(script|foreignObject)\\b|on[a-z]+\\s*=|javascript:|<!ENTITY/i', \$svg)) {\n";
            $functions_content .= "        \$file['error'] = esc_html('This SVG file contains unsupported or unsafe markup.');\n";
            $functions_content .= "    }\n";
            $functions_content .= "    return \$file;\n";
            $functions_content .= "}\n";
            $functions_content .= "add_filter('wp_handle_upload_prefilter', '{$func_prefix}_validate_svg_upload');\n\n";
        }

        if (isset($selection['features.webp-support']) && $selection['features.webp-support']) {
            $functions_content .= "// WebP Quality Adjustment\nadd_filter('wp_editor_set_quality', function(\$quality) { return 82; });\n\n";
        }

        if (isset($selection['features.excerpt-length']) && $selection['features.excerpt-length']) {
            $functions_content .= "// Custom Excerpt Length (multibyte-safe)\n";
            $functions_content .= "add_filter('excerpt_length', function(\$length) { return 50; }, 999);\n\n";
        }

        if (isset($selection['features.post-revisions-limit']) && $selection['features.post-revisions-limit']) {
            $functions_content .= "// Limit Post Revisions\nadd_filter('wp_revisions_to_keep', function(\$num) { return 5; });\n\n";
        }

        if (isset($selection['features.custom-pagination']) && $selection['features.custom-pagination']) {
            $functions_content .= "/**\n * Custom numeric pagination\n */\n";
            $functions_content .= "function {$func_prefix}_pagination() {\n";
            $functions_content .= "    if (is_singular()) return;\n";
            $functions_content .= "    global \$wp_query;\n";
            $functions_content .= "    if (\$wp_query->max_num_pages <= 1) return;\n";
            $functions_content .= "    \$paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;\n";
            $functions_content .= "    \$max   = intval(\$wp_query->max_num_pages);\n";
            $functions_content .= "    \$links = array();\n";
            $functions_content .= "    if (\$paged >= 1) \$links[] = \$paged;\n";
            $functions_content .= "    if (\$paged >= 3) {\n";
            $functions_content .= "        \$links[] = \$paged - 1;\n";
            $functions_content .= "        \$links[] = \$paged - 2;\n";
            $functions_content .= "    }\n";
            $functions_content .= "    if ((\$paged + 2) <= \$max) {\n";
            $functions_content .= "        \$links[] = \$paged + 1;\n";
            $functions_content .= "        \$links[] = \$paged + 2;\n";
            $functions_content .= "    }\n";
            $functions_content .= "    echo '<nav class=\"navigation pagination\"><div class=\"nav-links\">' . \"\\n\";\n";
            $functions_content .= "    if (get_previous_posts_link()) printf('%s' . \"\\n\", get_previous_posts_link());\n";
            $functions_content .= "    sort(\$links);\n";
            $functions_content .= "    foreach ((array) \$links as \$link) {\n";
            $functions_content .= "        \$class = \$paged == \$link ? ' class=\"page-numbers current\"' : ' class=\"page-numbers\"';\n";
            $functions_content .= "        printf('<a href=\"%s\"%s>%s</a>' . \"\\n\", get_pagenum_link(\$link), \$class, \$link);\n";
            $functions_content .= "    }\n";
            $functions_content .= "    if (get_next_posts_link()) printf('%s' . \"\\n\", get_next_posts_link());\n";
            $functions_content .= "    echo '</div></nav>' . \"\\n\";\n";
            $functions_content .= "}\n\n";
        }

        if (isset($selection['features.breadcrumbs']) && $selection['features.breadcrumbs']) {
            $functions_content .= "/**\n * Breadcrumb navigation\n */\n";
            $functions_content .= "function {$func_prefix}_breadcrumb() {\n";
            $functions_content .= "    if (is_front_page()) return;\n";
            $functions_content .= "    echo '<nav class=\"breadcrumb\">';\n";
            $functions_content .= "    echo '<a href=\"' . home_url() . '\" rel=\"nofollow\">' . esc_html('Home') . '</a>';\n";
            $functions_content .= "    if (is_category() || is_single()) {\n";
            $functions_content .= "        echo \" &nbsp;&nbsp;&#187;&nbsp;&nbsp; \";\n";
            $functions_content .= "        the_category(' &bull; ');\n";
            $functions_content .= "        if (is_single()) {\n";
            $functions_content .= "            echo \" &nbsp;&nbsp;&#187;&nbsp;&nbsp; \";\n";
            $functions_content .= "            the_title();\n";
            $functions_content .= "        }\n";
            $functions_content .= "    } elseif (is_page()) {\n";
            $functions_content .= "        echo \" &nbsp;&nbsp;&#187;&nbsp;&nbsp; \";\n";
            $functions_content .= "        the_title();\n";
            $functions_content .= "    } elseif (is_search()) {\n";
            $functions_content .= "        echo \" &nbsp;&nbsp;&#187;&nbsp;&nbsp; \";\n";
            $functions_content .= "        printf(esc_html('Search Results for: %s'), esc_html(get_search_query()));\n";
            $functions_content .= "    }\n";
            $functions_content .= "    echo '</nav>';\n";
            $functions_content .= "}\n\n";
        }

        if (isset($selection['features.auto-update-core-minor']) && $selection['features.auto-update-core-minor']) {
            $functions_content .= "// Enable minor core auto-updates\nadd_filter('allow_minor_auto_core_updates', '__return_true');\n\n";
        }
        if (isset($selection['features.auto-update-core-major']) && $selection['features.auto-update-core-major']) {
            $functions_content .= "// Enable major core auto-updates\nadd_filter('allow_major_auto_core_updates', '__return_true');\n\n";
        }
        if (isset($selection['features.auto-update-plugins']) && $selection['features.auto-update-plugins']) {
            $functions_content .= "// Enable automatic plugin updates\nadd_filter('auto_update_plugin', '__return_true');\n\n";
        }
        if (isset($selection['features.auto-update-themes']) && $selection['features.auto-update-themes']) {
            $functions_content .= "// Enable automatic theme updates\nadd_filter('auto_update_theme', '__return_true');\n\n";
        }

        // Security
        if (isset($selection['features.disable-xmlrpc']) && $selection['features.disable-xmlrpc']) {
            $functions_content .= "// Disable XML-RPC\nadd_filter('xmlrpc_enabled', '__return_false');\n\n";
        }
        if (isset($selection['features.disable-file-edit']) && $selection['features.disable-file-edit']) {
            $functions_content .= "// Disable File Editor\nif (!defined('DISALLOW_FILE_EDIT')) {\n    define('DISALLOW_FILE_EDIT', true);\n}\n\n";
        }

        if (isset($selection['features.custom-login-style']) && $selection['features.custom-login-style']) {
            $functions_content .= "// Custom Login Style\nadd_action('login_enqueue_scripts', function() {\n    echo '<style type=\"text/css\">#login h1 a { background-image: none !important; text-indent: 0 !important; width: auto !important; height: auto !important; }</style>';\n});\n\n";
        }

        if (isset($selection['features.dashboard-cleanup']) && $selection['features.dashboard-cleanup']) {
            $functions_content .= "// Dashboard Cleanup\nadd_action('wp_dashboard_setup', function() {\n    remove_meta_box('dashboard_primary', 'dashboard', 'side');\n    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');\n});\n\n";
        }

        if (isset($selection['features.search-limit']) && $selection['features.search-limit']) {
            $functions_content .= "// Search Results Limit\nadd_action('pre_get_posts', function(\$query) {\n    if (!is_admin() && \$query->is_main_query() && \$query->is_search()) {\n        \$query->set('posts_per_page', 10);\n    }\n});\n\n";
        }

        if (isset($selection['features.restrict-rest-api']) && $selection['features.restrict-rest-api']) {
            $functions_content .= "// Reduce public user enumeration in the REST API without blocking core front-end features.\n";
            $functions_content .= "add_filter('rest_endpoints', function(\$endpoints) {\n";
            $functions_content .= "    if (is_user_logged_in()) {\n";
            $functions_content .= "        return \$endpoints;\n";
            $functions_content .= "    }\n";
            $functions_content .= "    unset(\$endpoints['/wp/v2/users']);\n";
            $functions_content .= "    unset(\$endpoints['/wp/v2/users/(?P<id>[\\\\d]+)']);\n";
            $functions_content .= "    return \$endpoints;\n";
            $functions_content .= "});\n\n";
        }

        if (isset($selection['features.login-custom-url']) && $selection['features.login-custom-url']) {
            $functions_content .= "// Custom Login Credits\n";
            $functions_content .= "add_filter('login_headerurl', function() { return home_url(); });\n";
            $functions_content .= "add_filter('login_headertext', function() { return get_bloginfo('name'); });\n\n";
        }

        if (isset($selection['features.hide-admin-bar']) && $selection['features.hide-admin-bar']) {
            $functions_content .= "// Hide Admin Bar for non-admins\n";
            $functions_content .= "add_filter('show_admin_bar', function(\$show) {\n";
            $functions_content .= "    return (current_user_can('manage_options')) ? \$show : false;\n";
            $functions_content .= "});\n\n";
        }

        if (isset($selection['features.admin-footer-text']) && $selection['features.admin-footer-text']) {
            $functions_content .= "// Admin Footer Branding\n";
            $functions_content .= "add_filter('admin_footer_text', function() {\n";
            $functions_content .= "    echo 'Generated by " . $theme_slug . " Starter. Created with <span class=\"dashicons dashicons-heart\"></span>';\n";
            $functions_content .= "});\n\n";
        }

        $result = $this->write_file($theme_dir . '/functions.php', $functions_content);
        if (is_wp_error($result)) {
            return $result;
        }

        // SCSS sources (assets/scss) + package.json for easier customization
        if (isset($selection['features.scss-sources']) && $selection['features.scss-sources']) {
            $scss_dir = $theme_dir . '/assets/scss';
            wp_mkdir_p($scss_dir);

            $include_editor = isset($selection['features.editor-styles']) && $selection['features.editor-styles'];
            foreach (PTS_Scss::get_classic_files($style_header, $layout, $include_editor) as $scss_file => $scss_content) {
                $result = $this->write_file($scss_dir . '/' . $scss_file, $scss_content);
                if (is_wp_error($result)) {
                    return $result;
                }
            }
            $result = $this->write_file($theme_dir . '/package.json', PTS_Scss::get_package_json($theme_slug));
            if (is_wp_error($result)) {
                return $result;
            }
        }

        // screenshot.png so the Appearance > Themes card is not blank
        PTS_Screenshot::create($theme_dir . '/screenshot.png', $theme_name);

        // Output Handling
        $output_mode = isset($data['outputMode']) ? $data['outputMode'] : 'direct';
        if ($output_mode === 'direct') {
            $destination = WP_CONTENT_DIR . '/themes/' . $theme_slug;
            if (file_exists($destination)) {
                return new WP_Error('theme_exists', __('Theme directory already exists.', 'picot-theme-seeder'));
            }

            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
            global $wp_filesystem;

            if ($wp_filesystem->move($theme_dir, $destination)) {
                return array(
                    'success' => true,
                    'message' => __('Theme generated successfully.', 'picot-theme-seeder'),
                );
            } else {
                return new WP_Error('move_failed', __('Could not move theme to the themes directory. Check permissions.', 'picot-theme-seeder'));
            }
        } else {
            $zip_file = $base_dir . '/' . $theme_slug . '.zip';
            $zipper = new CTS_Zip();
            $result = $zipper->create_zip($theme_dir, $zip_file);
            if (is_wp_error($result)) return $result;
            $zip_url = PTS_Admin::create_temp_download_url($zip_file, $theme_slug);
            if (is_wp_error($zip_url)) {
                return $zip_url;
            }
            return array('zipUrl' => $zip_url, 'log' => __('Theme generated successfully.', 'picot-theme-seeder'));
        }
    }

    /**
     * @param array<string, mixed> $selection Wizard selection flags.
     */
    private function has_sidebar($selection)
    {
        return ! empty($selection['templates.sidebar'])
            || ! empty($selection['features.menus'])
            || ! empty($selection['features.widgets']);
    }

    /**
     * @param array<string, mixed> $selection Wizard selection flags.
     */
    private function sidebar_call($selection)
    {
        return $this->has_sidebar($selection) ? "<?php get_sidebar(); ?>\n" : '';
    }

    /**
     * Bootstrap column class for the main content area.
     *
     * @param array<string, mixed> $selection Wizard selection flags.
     * @param bool                 $full_width Force full-width column.
     */
    private function main_column_class($selection, $full_width = false)
    {
        if ($full_width || ! $this->has_sidebar($selection)) {
            return 'col-12';
        }

        return 'col-12 col-lg-8';
    }

    /**
     * Sidebar column markup: primary menu + optional widgets.
     *
     * @return string
     */
    private function get_sidebar_template_content($theme_slug)
    {
        $func_prefix = str_replace('-', '_', $theme_slug);

        return "<?php\n/**\n * Sidebar column with the primary menu and optional widgets.\n */\n?>\n<aside id=\"secondary\" class=\"widget-area col-12 col-lg-4 d-none d-lg-block\">\n    <div class=\"card border-0 shadow-sm\">\n        <div class=\"card-body d-grid gap-4\">\n            <?php get_template_part('template-parts/sidebar', 'nav', array('instance' => 'sidebar')); ?>\n            <?php get_template_part('template-parts/sidebar', 'widgets'); ?>\n        </div>\n    </div>\n</aside>\n";
    }

    /**
     * @return string
     */
    private function get_sidebar_nav_template_content($func_prefix)
    {
        return "<?php\n/**\n * Primary navigation for the sidebar / mobile offcanvas.\n *\n * @var array \$args Template part arguments.\n */\n?>\n<?php\n\$nav_instance = isset(\$args['instance']) ? \$args['instance'] : 'sidebar';\n?>\n<nav class=\"sidebar-navigation\" aria-label=\"<?php echo esc_attr('Primary menu'); ?>\">\n    <?php {$func_prefix}_primary_nav(\$nav_instance); ?>\n</nav>\n";
    }

    /**
     * @return string
     */
    private function get_sidebar_widgets_template_content()
    {
        return "<?php\n/**\n * Sidebar widget area shared by desktop column and mobile offcanvas.\n */\n?>\n<?php if (is_active_sidebar('sidebar-1')) : ?>\n    <?php dynamic_sidebar('sidebar-1'); ?>\n<?php endif; ?>\n";
    }

    /**
     * @param array<string, mixed> $selection Wizard selection flags.
     */
    private function get_header_branding_markup()
    {
        return "            <div class=\"site-branding d-flex flex-column\">\n"
            . "                <?php if (has_custom_logo()) : the_custom_logo(); else : ?>\n"
            . "                <a class=\"site-title text-decoration-none fw-bold fs-4\" href=\"<?php echo esc_url(home_url('/')); ?>\" rel=\"home\"><?php bloginfo('name'); ?></a>\n"
            . "                <?php if (get_bloginfo('description', 'display')) : ?><span class=\"site-description text-body-secondary small\"><?php bloginfo('description'); ?></span><?php endif; ?>\n"
            . "                <?php endif; ?>\n"
            . "            </div>\n";
    }

    /**
     * Header for 2-column layout: branding + mobile burger menu (desktop nav stays in sidebar).
     *
     * @param string $func_prefix Theme function prefix.
     */
    private function get_header_with_sidebar_nav_markup($func_prefix)
    {
        return "        <div class=\"navbar navbar-light px-0 w-100\">\n"
            . "            <div class=\"site-header-bar d-flex align-items-center justify-content-between gap-3 w-100\">\n"
            . "                <div class=\"site-branding d-flex flex-column\">\n"
            . "                    <?php if (has_custom_logo()) : the_custom_logo(); else : ?>\n"
            . "                    <a class=\"site-title text-decoration-none fw-bold\" href=\"<?php echo esc_url(home_url('/')); ?>\" rel=\"home\"><?php bloginfo('name'); ?></a>\n"
            . "                    <?php if (get_bloginfo('description', 'display')) : ?><span class=\"site-description text-body-secondary small\"><?php bloginfo('description'); ?></span><?php endif; ?>\n"
            . "                    <?php endif; ?>\n"
            . "                </div>\n"
            . "                <button class=\"navbar-toggler d-lg-none flex-shrink-0\" type=\"button\" data-bs-toggle=\"offcanvas\" data-bs-target=\"#pts-sidebar-offcanvas\" aria-controls=\"pts-sidebar-offcanvas\" aria-label=\"<?php echo esc_attr('Toggle navigation'); ?>\">\n"
            . "                    <span class=\"navbar-toggler-icon\"></span>\n"
            . "                </button>\n"
            . "            </div>\n"
            . "        </div>\n"
            . "    </div>\n"
            . "</header>\n\n"
            . "<div class=\"offcanvas offcanvas-start d-lg-none\" tabindex=\"-1\" id=\"pts-sidebar-offcanvas\" aria-labelledby=\"pts-sidebar-offcanvas-label\">\n"
            . "    <div class=\"offcanvas-header border-bottom\">\n"
            . "        <h2 class=\"offcanvas-title h5 mb-0\" id=\"pts-sidebar-offcanvas-label\"><?php bloginfo('name'); ?></h2>\n"
            . "        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"offcanvas\" aria-label=\"<?php echo esc_attr('Close'); ?>\"></button>\n"
            . "    </div>\n"
            . "    <div class=\"offcanvas-body d-grid gap-4\">\n"
            . "        <?php get_template_part('template-parts/sidebar', 'nav', array('instance' => 'mobile')); ?>\n"
            . "        <?php get_template_part('template-parts/sidebar', 'widgets'); ?>\n"
            . "    </div>\n"
            . "</div>\n\n"
            . "<div class=\"site-shell pb-4\">\n"
            . "    <?php if (!is_front_page()) : ?>\n"
            . "    <div class=\"container\">\n"
            . "        <div class=\"row g-4\">\n"
            . "        <?php endif; ?>\n";
    }

    /**
     * @param array<string, mixed> $selection Wizard selection flags.
     */
    private function get_template_content($type, $theme_name, $theme_slug, $selection = array())
    {
        $main_col = $this->main_column_class($selection);

        switch ($type) {
            case 'header':
                $c = "<!DOCTYPE html>\n<html <?php language_attributes(); ?>>\n<head>\n";
                $c .= "    <meta charset=\"<?php bloginfo('charset'); ?>\">\n";
                $c .= "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
                $c .= "    <?php wp_head(); ?>\n";
                $c .= "</head>\n<body <?php body_class(); ?>>\n<?php wp_body_open(); ?>\n";
                $c .= "<a class=\"skip-link screen-reader-text\" href=\"#primary\"><?php echo esc_html('Skip to content'); ?></a>\n";
                $c .= "<header id=\"masthead\" class=\"site-header bg-white sticky-top\">\n";
                $c .= "    <div class=\"container\">\n";
                if ($this->has_sidebar($selection)) {
                    $func_prefix = str_replace('-', '_', $theme_slug);
                    $c .= $this->get_header_with_sidebar_nav_markup($func_prefix);
                    return $c;
                } else {
                    $c .= "        <div class=\"navbar navbar-expand-lg navbar-light px-0\">\n";
                    $c .= "            <div class=\"navbar-brand d-flex flex-column me-4 mb-0\">\n";
                    $c .= "                <?php if (has_custom_logo()) : the_custom_logo(); else : ?>\n";
                    $c .= "                <a class=\"site-title text-decoration-none fw-bold\" href=\"<?php echo esc_url(home_url('/')); ?>\" rel=\"home\"><?php bloginfo('name'); ?></a>\n";
                    $c .= "                <?php if (get_bloginfo('description', 'display')) : ?><span class=\"site-description text-body-secondary small\"><?php bloginfo('description'); ?></span><?php endif; ?>\n";
                    $c .= "        <?php endif; ?>\n";
                    $c .= "            </div>\n";
                    $c .= "            <button class=\"navbar-toggler\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#primary-menu\" aria-controls=\"primary-menu\" aria-expanded=\"false\" aria-label=\"<?php echo esc_attr('Toggle navigation'); ?>\">\n";
                    $c .= "                <span class=\"navbar-toggler-icon\"></span>\n";
                    $c .= "            </button>\n";
                    $c .= "            <div id=\"primary-menu\" class=\"collapse navbar-collapse justify-content-end\">\n";
                    $c .= "                <?php wp_nav_menu(array('theme_location' => 'primary', 'menu_class' => 'navbar-nav ms-auto mb-2 mb-lg-0 gap-lg-2', 'container' => false, 'fallback_cb' => false, 'depth' => 2)); ?>\n";
                    $c .= "            </div>\n";
                    $c .= "        </div>\n";
                }
                $c .= "    </div>\n";
                $c .= "</header>\n";
                $c .= "<div class=\"site-shell pb-4\">\n";
                $c .= "    <?php if (!is_front_page()) : ?>\n";
                $c .= "    <div class=\"container\">\n";
                $c .= "        <div class=\"row g-4\">\n";
                $c .= "        <?php endif; ?>\n";
                return $c;

            case 'footer':
                $c = "        <?php if (!is_front_page()) : ?>\n";
                $c .= "        </div>\n";
                $c .= "    </div>\n";
                $c .= "        <?php endif; ?>\n";
                $c .= "</div>\n";
                $c .= "<footer id=\"colophon\" class=\"site-footer border-top bg-body-tertiary py-4 mt-5\">\n";
                $c .= "    <div class=\"container text-center small text-body-secondary\">\n";
                $c .= "        <p class=\"mb-0\">&copy; <?php echo esc_html(gmdate('Y')); ?> <a class=\"text-body-secondary text-decoration-none\" href=\"<?php echo esc_url(home_url('/')); ?>\"><?php echo esc_html(get_bloginfo('name')); ?></a> <?php echo esc_html('All rights reserved.'); ?></p>\n";
                $c .= "    </div>\n";
                $c .= "</footer>\n<?php wp_footer(); ?>\n</body>\n</html>";
                return $c;

            case 'comments':
                $c = "<?php\nif (post_password_required()) { return; }\n?>\n<div id=\"comments\" class=\"comments-area\">\n    <?php if (have_comments()) : ?>\n        <h2 class=\"comments-title\">\n            <?php\n            \$comment_count = get_comments_number();\n            if (1 === \$comment_count) {\n                printf(esc_html('One thought on %s'), '<span>' . esc_html(get_the_title()) . '</span>');\n            } else {\n                printf(esc_html('%1\$s thoughts on %2\$s'), number_format_i18n(\$comment_count), '<span>' . esc_html(get_the_title()) . '</span>');\n            }\n            ?>\n        </h2>\n        <ol class=\"comment-list\">\n            <?php wp_list_comments(array('style' => 'ol', 'short_ping' => true)); ?>\n        </ol>\n        <?php the_comments_navigation(); ?>\n    <?php endif; ?>\n    <?php if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')) : ?>\n        <p class=\"no-comments\"><?php echo esc_html('Comments are closed.'); ?></p>\n    <?php endif; ?>\n    <?php comment_form(); ?>\n</div>\n";
                return $c;
 
            case 'searchform':
                $c = "<form role=\"search\" method=\"get\" class=\"search-form d-flex flex-nowrap gap-2\" action=\"<?php echo esc_url(home_url('/')); ?>\">\n";
                $c .= "    <label class=\"search-label flex-grow-1\">\n";
                $c .= "        <span class=\"screen-reader-text\"><?php echo esc_html('Search for:'); ?></span>\n";
                $c .= "        <input type=\"search\" class=\"search-field form-control\" placeholder=\"<?php echo esc_attr('Search &hellip;'); ?>\" value=\"<?php echo esc_attr(get_search_query()); ?>\" name=\"s\" />\n";
                $c .= "    </label>\n";
                $c .= "    <button type=\"submit\" class=\"search-submit btn btn-primary\"><?php echo esc_html('Search'); ?></button>\n";
                $c .= "</form>";
                return $c;

            case 'header-embed':
                $c  = "<!DOCTYPE html>\n<html <?php language_attributes(); ?> class=\"no-js\">\n<head>\n";
                $c .= "    <meta charset=\"<?php bloginfo('charset'); ?>\">\n";
                $c .= "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
                $c .= "    <?php wp_head(); ?>\n";
                $c .= "</head>\n<body <?php body_class(); ?>>\n<?php wp_body_open(); ?>\n";
                return $c;

            case 'footer-embed':
                return "<?php wp_footer(); ?>\n</body>\n</html>\n";

            case 'embed':
                $c = "<?php\nget_header('embed');\nif (have_posts()) : while (have_posts()) : the_post(); ?>\n<div <?php post_class('wp-embed'); ?>>\n    <?php the_embed_site_title(); ?>\n    <?php if (has_post_thumbnail()) : ?>\n        <div class=\"wp-embed-featured-image\">\n            <a href=\"<?php the_permalink(); ?>\">\n                <?php the_post_thumbnail('medium'); ?>\n            </a>\n        </div>\n    <?php endif; ?>\n    <p class=\"wp-embed-heading\">\n        <a href=\"<?php the_permalink(); ?>\">\n            <?php the_title(); ?>\n        </a>\n    </p>\n    <div class=\"wp-embed-excerpt\"><?php the_excerpt(); ?></div>\n    <div class=\"wp-embed-footer\">\n        <?php the_embed_standard_status(); ?>\n    </div>\n</div>\n<?php endwhile; endif;\nget_footer('embed');\n";
                return $c;

            case 'image':
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <?php while (have_posts()) : the_post(); ?>\n";
                $c .= "        <article id=\"post-<?php the_ID(); ?>\" <?php post_class('entry-card card border-0 shadow-sm mb-4'); ?>>\n";
                $c .= "            <div class=\"card-body p-4 p-lg-5\">\n";
                $c .= "            <header class=\"entry-header mb-3\"> <?php the_title('<h1 class=\"entry-title display-6 fw-bold mb-0\">', '</h1>'); ?> </header>\n";
                $c .= "            <div class=\"entry-content\">\n";
                $c .= "                <div class=\"entry-attachment\">\n";
                $c .= "                    <?php echo wp_get_attachment_image(get_the_ID(), 'full', false, array('class' => 'img-fluid')); ?>\n";
                $c .= "                    <?php if (has_excerpt()) : ?>\n";
                $c .= "                        <div class=\"entry-caption\"> <?php the_excerpt(); ?> </div>\n";
                $c .= "                    <?php endif; ?>\n";
                $c .= "                </div>\n";
                $c .= "                <?php the_content(); ?>\n";
                $c .= "            </div>\n";
                $c .= "            </div>\n";
                $c .= "        </article>\n";
                $c .= "    <?php endwhile; ?>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>\n";
                return $c;

            case 'video':
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <?php while (have_posts()) : the_post(); ?>\n";
                $c .= "        <article id=\"post-<?php the_ID(); ?>\" <?php post_class('entry-card card border-0 shadow-sm mb-4'); ?>>\n";
                $c .= "            <div class=\"card-body p-4 p-lg-5\">\n";
                $c .= "            <header class=\"entry-header mb-3\"> <?php the_title('<h1 class=\"entry-title display-6 fw-bold mb-0\">', '</h1>'); ?> </header>\n";
                $c .= "            <div class=\"entry-content\">\n";
                $c .= "                <div class=\"entry-attachment-video ratio ratio-16x9 mb-4\">\n";
                $c .= "                    <?php echo wp_video_shortcode(array('src' => wp_get_attachment_url())); ?>\n";
                $c .= "                </div>\n";
                $c .= "                <?php the_content(); ?>\n";
                $c .= "            </div>\n";
                $c .= "            </div>\n";
                $c .= "        </article>\n";
                $c .= "    <?php endwhile; ?>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>\n";
                return $c;

            case 'audio':
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <?php while (have_posts()) : the_post(); ?>\n";
                $c .= "        <article id=\"post-<?php the_ID(); ?>\" <?php post_class('entry-card card border-0 shadow-sm mb-4'); ?>>\n";
                $c .= "            <div class=\"card-body p-4 p-lg-5\">\n";
                $c .= "            <header class=\"entry-header mb-3\"> <?php the_title('<h1 class=\"entry-title display-6 fw-bold mb-0\">', '</h1>'); ?> </header>\n";
                $c .= "            <div class=\"entry-content\">\n";
                $c .= "                <div class=\"entry-attachment-audio mb-4\">\n";
                $c .= "                    <?php echo wp_audio_shortcode(array('src' => wp_get_attachment_url())); ?>\n";
                $c .= "                </div>\n";
                $c .= "                <?php the_content(); ?>\n";
                $c .= "            </div>\n";
                $c .= "            </div>\n";
                $c .= "        </article>\n";
                $c .= "    <?php endwhile; ?>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>\n";
                return $c;

            case 'privacy-policy':
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <?php while (have_posts()) : the_post(); ?>\n";
                $c .= "        <article id=\"post-<?php the_ID(); ?>\" <?php post_class('entry-card card border-0 shadow-sm mb-4'); ?>>\n";
                $c .= "            <div class=\"card-body p-4 p-lg-5\">\n";
                $c .= "            <header class=\"entry-header mb-3\"> <?php the_title('<h1 class=\"entry-title display-6 fw-bold mb-0\">', '</h1>'); ?> </header>\n";
                $c .= "            <div class=\"entry-content\"> <?php the_content(); ?> </div>\n";
                $c .= "            </div>\n";
                $c .= "        </article>\n";
                $c .= "    <?php endwhile; ?>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>\n";
                return $c;

            case 'attachment':
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <?php while (have_posts()) : the_post(); ?>\n";
                $c .= "        <article id=\"post-<?php the_ID(); ?>\" <?php post_class(); ?>>\n";
                $c .= "            <header class=\"entry-header\"> <?php the_title('<h1 class=\"entry-title\">', '</h1>'); ?> </header>\n";
                $c .= "            <div class=\"entry-content\">\n";
                $c .= "                <div class=\"entry-attachment\">\n";
                $c .= "                    <?php echo wp_get_attachment_image(get_the_ID(), 'large'); ?>\n";
                $c .= "                    <?php if (has_excerpt()) : ?>\n";
                $c .= "                        <div class=\"entry-caption\"><?php the_excerpt(); ?></div>\n";
                $c .= "                    <?php endif; ?>\n";
                $c .= "                </div>\n";
                $c .= "                <?php the_content(); ?>\n";
                $c .= "            </div>\n";
                $c .= "        </article>\n";
                $c .= "    <?php endwhile; ?>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>\n";
                return $c;

            case 'single':
            case 'page':
            case 'singular':
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <?php while (have_posts()) : the_post();\n";
                $c .= "        get_template_part('template-parts/content', get_post_type());\n";
                $c .= "        if (comments_open() || get_comments_number()) : comments_template(); endif;\n";
                $c .= "    endwhile; ?>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>";
                return $c;
 
            case 'front-page':
                // Standard landing layout: full-width hero, then 2-column content + sidebar.
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<section class=\"front-hero front-hero--full py-5 text-center bg-body-tertiary mb-5 pts-animate-hero\">\n";
                $c .= "    <div class=\"container\">\n";
                $c .= "    <h1 class=\"front-hero-title display-4 fw-bold pts-animate-hero__item\"><?php bloginfo('name'); ?></h1>\n";
                $c .= "    <?php if (get_bloginfo('description', 'display')) : ?>\n";
                $c .= "        <p class=\"front-hero-text lead text-body-secondary mx-auto pts-animate-hero__item\"><?php bloginfo('description'); ?></p>\n";
                $c .= "    <?php endif; ?>\n";
                $c .= "    <p class=\"front-hero-actions pts-animate-hero__item\">\n";
                $c .= "        <a class=\"front-button btn btn-primary btn-lg px-4\" href=\"<?php echo esc_url(home_url('/')); ?>\"><?php echo esc_html('Get started'); ?></a>\n";
                $c .= "    </p>\n";
                $c .= "    </div>\n";
                $c .= "</section>\n\n";
                $c .= "<div class=\"container\">\n";
                $c .= "<div class=\"row g-4\">\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col} pts-dynamic-page\">\n";
                $c .= "    <section class=\"front-latest mb-5\">\n";
                $c .= "        <div class=\"d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2 pts-animate pts-animate-fade-up\">\n";
                $c .= "            <h2 class=\"front-section-title h2 mb-0\"><?php echo esc_html('Latest posts'); ?></h2>\n";
                $c .= "            <a class=\"btn btn-outline-primary\" href=\"<?php echo esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/')); ?>\"><?php echo esc_html('View all'); ?></a>\n";
                $c .= "        </div>\n";
                $c .= "        <div class=\"row g-4 front-posts-grid pts-animate-stagger\" data-pts-animate=\"fade-up\">\n";
                $c .= "            <?php\n";
                $c .= "            \$front_latest = new WP_Query(array('posts_per_page' => 3, 'ignore_sticky_posts' => true));\n";
                $c .= "            if (\$front_latest->have_posts()) :\n";
                $c .= "                while (\$front_latest->have_posts()) : \$front_latest->the_post(); ?>\n";
                $c .= "                    <article id=\"post-<?php the_ID(); ?>\" <?php post_class('front-card col-12'); ?>>\n";
                $c .= "                        <div class=\"card h-100 border-0 shadow-sm\">\n";
                $c .= "                        <?php if (has_post_thumbnail()) : ?>\n";
                $c .= "                            <a class=\"front-card-thumb card-img-top overflow-hidden\" href=\"<?php the_permalink(); ?>\"><?php the_post_thumbnail('medium_large', array('class' => 'img-fluid')); ?></a>\n";
                $c .= "                        <?php endif; ?>\n";
                $c .= "                        <div class=\"card-body d-flex flex-column\">\n";
                $c .= "                        <h3 class=\"front-card-title h5 card-title\"><a class=\"stretched-link text-decoration-none\" href=\"<?php the_permalink(); ?>\"><?php the_title(); ?></a></h3>\n";
                $c .= "                        <div class=\"front-card-excerpt card-text text-body-secondary\"><?php the_excerpt(); ?></div>\n";
                $c .= "                        </div>\n";
                $c .= "                        </div>\n";
                $c .= "                    </article>\n";
                $c .= "                <?php endwhile;\n";
                $c .= "                wp_reset_postdata();\n";
                $c .= "            else : ?>\n";
                $c .= "                <p><?php echo esc_html('No posts yet.'); ?></p>\n";
                $c .= "            <?php endif; ?>\n";
                $c .= "        </div>\n";
                $c .= "    </section>\n\n";
                $c .= "    <section class=\"front-cta py-5 px-4 text-center bg-primary-subtle pts-animate pts-animate-zoom-in\">\n";
                $c .= "        <h2 class=\"front-section-title h2\"><?php echo esc_html('Ready to get in touch?'); ?></h2>\n";
                $c .= "        <p class=\"front-hero-actions\">\n";
                $c .= "            <a class=\"front-button btn btn-primary btn-lg px-4\" href=\"<?php echo esc_url(home_url('/')); ?>\"><?php echo esc_html('Contact us'); ?></a>\n";
                $c .= "        </p>\n";
                $c .= "    </section>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "</div>\n";
                $c .= "</div>\n\n";
                $c .= "<?php get_footer(); ?>";
                return $c;
 
            case 'home':
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <?php if (have_posts()) : ?>\n";
                $c .= "        <header class=\"page-header mb-4 pb-3 border-bottom\">\n";
                $c .= "            <h1 class=\"page-title display-6 fw-bold mb-0\"><?php single_post_title(); ?></h1>\n";
                $c .= "        </header>\n";
                $c .= "        <?php while (have_posts()) : the_post();\n";
                $c .= "            get_template_part('template-parts/content', get_post_type());\n";
                $c .= "        endwhile; the_posts_navigation(); else : get_template_part('template-parts/content', 'none'); endif; ?>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>";
                return $c;
 
            case 'paged':
            case 'archive':
            case 'category':
            case 'tag':
            case 'taxonomy':
            case 'author':
            case 'date':
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <?php if (have_posts()) : ?>\n";
                $c .= "        <header class=\"page-header mb-4 pb-3 border-bottom\">\n";
                $c .= "            <?php the_archive_title('<h1 class=\"page-title display-6 fw-bold mb-2\">', '</h1>'); ?>\n";
                $c .= "            <?php the_archive_description('<div class=\"archive-description text-body-secondary\">', '</div>'); ?>\n";
                $c .= "        </header>\n";
                $c .= "        <?php while (have_posts()) : the_post(); ?>\n";
                $c .= "            <?php get_template_part('template-parts/content', get_post_type()); ?>\n";
                $c .= "        <?php endwhile; the_posts_navigation(); else : get_template_part('template-parts/content', 'none'); endif; ?>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>";
                return $c;

            case 'parts-content':
                $c = "<article id=\"post-<?php the_ID(); ?>\" <?php post_class(is_singular() ? 'entry-card card border-0 shadow-sm mb-4' : 'entry-card card border-0 shadow-sm mb-4'); ?>>\n    <div class=\"card-body p-4 p-lg-5\">\n    <header class=\"entry-header mb-3\">\n        <?php if (is_singular()) : the_title('<h1 class=\"entry-title display-6 fw-bold mb-0\">', '</h1>'); else : the_title('<h2 class=\"entry-title h3 mb-0\"><a class=\"text-decoration-none stretched-link\" href=\"' . esc_url(get_permalink()) . '\" rel=\"bookmark\">', '</a></h2>'); endif; ?>\n    </header>\n    <?php if (has_post_thumbnail()) : ?>\n        <div class=\"post-thumbnail mb-4\"> <?php the_post_thumbnail('large', array('class' => 'img-fluid')); ?> </div>\n    <?php endif; ?>\n    <div class=\"entry-content\">\n        <?php if (is_singular()) : the_content(); else : the_excerpt(); endif; ?>\n    </div>\n    </div>\n</article>\n";
                return $c;

            case 'parts-content-single':
                $c = "<article id=\"post-<?php the_ID(); ?>\" <?php post_class('entry-card card border-0 shadow-sm mb-4'); ?>>\n    <div class=\"card-body p-4 p-lg-5\">\n    <header class=\"entry-header mb-3\"> <?php the_title('<h1 class=\"entry-title display-6 fw-bold mb-0\">', '</h1>'); ?> </header>\n    <?php if (has_post_thumbnail()) : ?><div class=\"post-thumbnail mb-4\"><?php the_post_thumbnail('large', array('class' => 'img-fluid')); ?></div><?php endif; ?>\n    <div class=\"entry-content\"> <?php the_content(); ?> </div>\n    </div>\n</article>\n";
                return $c;

            case 'parts-content-page':
                $c = "<article id=\"post-<?php the_ID(); ?>\" <?php post_class('entry-card card border-0 shadow-sm mb-4'); ?>>\n    <div class=\"card-body p-4 p-lg-5\">\n    <header class=\"entry-header mb-3\"> <?php the_title('<h1 class=\"entry-title display-6 fw-bold mb-0\">', '</h1>'); ?> </header>\n    <?php if (has_post_thumbnail()) : ?><div class=\"post-thumbnail mb-4\"><?php the_post_thumbnail('large', array('class' => 'img-fluid')); ?></div><?php endif; ?>\n    <div class=\"entry-content\"> <?php the_content(); ?> </div>\n    </div>\n</article>\n";
                return $c;

            case 'parts-content-search':
                $c = "<article id=\"post-<?php the_ID(); ?>\" <?php post_class('entry-card card border-0 shadow-sm mb-4'); ?>>\n    <div class=\"card-body p-4\">\n    <header class=\"entry-header mb-3\"> <?php the_title('<h2 class=\"entry-title h4 mb-0\"><a class=\"text-decoration-none stretched-link\" href=\"' . esc_url(get_permalink()) . '\" rel=\"bookmark\">', '</a></h2>'); ?> </header>\n    <div class=\"entry-summary text-body-secondary\"> <?php the_excerpt(); ?> </div>\n    </div>\n</article>\n";
                return $c;

            case 'parts-content-none':
                $c = "<section class=\"no-results not-found\">\n    <header class=\"page-header\"> <h1 class=\"page-title\"><?php echo esc_html('Nothing Found'); ?></h1> </header>\n    <div class=\"page-content\">\n        <?php if (is_home() && current_user_can('publish_posts')) : ?>\n            <p><?php printf(wp_kses_post('Ready to publish your first post? <a href=\"%1\$s\">Get started here</a>.'), esc_url(admin_url('post-new.php'))); ?></p>\n        <?php elseif (is_search()) : ?>\n            <p><?php echo esc_html('Sorry, but nothing matched your search terms. Please try again with some different keywords.'); ?></p>\n            <?php get_search_form(); ?>\n        <?php else : ?>\n            <p><?php echo esc_html('It seems we can\'t find what you\'re looking for. Perhaps searching can help.'); ?></p>\n            <?php get_search_form(); ?>\n        <?php endif; ?>\n    </div>\n</section>\n";
                return $c;

            case 'sidebar':
                return $this->get_sidebar_template_content($theme_slug);

            case '404':
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <section class=\"error-404 not-found entry-card card border-0 shadow-sm\">\n";
                $c .= "        <div class=\"card-body p-4 p-lg-5\">\n";
                $c .= "        <header class=\"page-header mb-3\"><h1 class=\"page-title display-6 fw-bold mb-0\"><?php echo esc_html('Oops! That page can\\'t be found.'); ?></h1></header>\n";
                $c .= "        <div class=\"page-content\"><p><?php echo esc_html('It looks like nothing was found at this location. Maybe try a search?'); ?></p><?php get_search_form(); ?></div>\n";
                $c .= "        </div>\n";
                $c .= "    </section>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>";
                return $c;

            case 'search':
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <?php if (have_posts()) : ?>\n";
                $c .= "        <header class=\"page-header\"><h1 class=\"page-title\"><?php printf(esc_html('Search Results for: %s'), '<span>' . esc_html(get_search_query()) . '</span>'); ?></h1></header>\n";
                $c .= "        <?php while (have_posts()) : the_post();\n";
                $c .= "            get_template_part('template-parts/content', 'search');\n";
                $c .= "        endwhile; the_posts_navigation(); ?>\n";
                $c .= "    <?php else : ?>\n";
                $c .= "        <p><?php echo esc_html('No results found.'); ?></p>\n";
                $c .= "        <?php get_search_form(); ?>\n";
                $c .= "    <?php endif; ?>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>";
                return $c;

            case 'template-full-width':
                $c = "<?php\n/**\n * Template Name: Full Width Page\n */\nget_header(); ?>\n";
                $c .= "<main id=\"primary\" class=\"site-main full-width\">\n";
                $c .= "    <?php while (have_posts()) : the_post(); get_template_part('template-parts/content', 'page'); endwhile; ?>\n";
                $c .= "</main>\n<?php get_footer(); ?>";
                return $c;

            case 'template-no-sidebar':
                $c = "<?php\n/**\n * Template Name: No Sidebar Page\n */\nget_header(); ?>\n";
                $c .= "<main id=\"primary\" class=\"site-main no-sidebar\">\n";
                $c .= "    <?php while (have_posts()) : the_post(); get_template_part('template-parts/content', 'page'); endwhile; ?>\n";
                $c .= "</main>\n<?php get_footer(); ?>";
                return $c;

            default:
                // Universal Loop for other templates
                $c = "<?php get_header(); ?>\n\n";
                $c .= "<main id=\"primary\" class=\"site-main {$main_col}\">\n";
                $c .= "    <header class=\"page-header\">\n";
                $c .= "        <?php ";
                if (in_array($type, ['archive', 'category', 'tag', 'taxonomy', 'author', 'date'])) {
                    $c .= "the_archive_title('<h1 class=\"page-title\">', '</h1>'); the_archive_description('<div class=\"archive-description\">', '</div>');";
                } else {
                    $c .= "if (is_front_page() && is_home()) : ?> <h1 class=\"page-title\"><?php bloginfo('name'); ?></h1> <?php endif; ";
                }
                $c .= "?>\n";
                $c .= "    </header>\n\n";
                $c .= "    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>\n";
                $c .= "        <article id=\"post-<?php the_ID(); ?>\" <?php post_class(); ?>>\n";
                $c .= "            <header class=\"entry-header\"> <?php the_title('<h2 class=\"entry-title\">', '</h2>'); ?> </header>\n";
                $c .= "            <div class=\"entry-content\"> <?php the_content(); ?> </div>\n";
                $c .= "        </article>\n";
                $c .= "    <?php endwhile; the_posts_navigation(); endif; ?>\n";
                $c .= "</main>\n\n";
                $c .= $this->sidebar_call($selection);
                $c .= "<?php get_footer(); ?>";
                return $c;
        }
    }

    private function get_template_header($theme_name)
    {
        return "<?php\n/**\n * Template for " . $theme_name . "\n */\n?>\n";
    }

    /**
     * Minimal Bootstrap-oriented polish and WordPress-specific fallbacks.
     */
    private function get_base_styles()
    {
        return '/* Bootstrap-oriented theme polish and WordPress-specific fallbacks. */

/* Skip link - accessibility */
.screen-reader-text {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
.screen-reader-text:focus {
    position: fixed;
    z-index: 100000;
    top: 5px;
    left: 5px;
    width: auto;
    height: auto;
    padding: 0.75rem 1rem;
    clip: auto;
    background: #f3f4f6;
    color: #0073aa;
    text-decoration: none;
    font-weight: 600;
}

/* Content area: images and captions (the_content() output) */
.entry-content img,
.post-thumbnail img {
    max-width: 100%;
    height: auto;
}
.entry-caption {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

.entry-card {
    overflow: hidden;
    border-radius: 1rem;
}

.card {
    border-radius: 1rem;
}

/* Rounded corners: buttons and cards only */
.form-control,
.form-select,
.post-thumbnail img,
.entry-content img,
.front-hero,
.front-cta {
    border-radius: 0;
}

.entry-card .entry-title a {
    color: inherit;
}

.entry-card .entry-title a:hover {
    color: #0d6efd;
}

/* Widget list reset */
.widget ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

/* Search form */
.search-form {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: 0.5rem;
    max-width: 100%;
    min-width: 0;
}
.search-form .search-label {
    min-width: 0;
    flex: 1 1 0%;
}
.search-form .search-field {
    min-width: 0;
    flex: 1 1 0%;
    width: auto;
    padding: 0.4rem 0.6rem;
    border: 1px solid #d1d5db;
    border-radius: 0;
}
.search-form .search-submit {
    padding: 0.4rem 1rem;
    border: 1px solid #0073aa;
    background: #0073aa;
    color: #ffffff;
    cursor: pointer;
    white-space: nowrap;
    flex: 0 0 auto;
}
.search-form .search-submit:hover {
    background: #005a87;
}

/* Block Search widget (sidebar / offcanvas) */
#secondary .wp-block-search,
#pts-sidebar-offcanvas .wp-block-search {
    max-width: 100%;
    min-width: 0;
}

#secondary .wp-block-search__label,
#pts-sidebar-offcanvas .wp-block-search__label {
    display: none;
}

#secondary .wp-block-search__inside-wrapper,
#pts-sidebar-offcanvas .wp-block-search__inside-wrapper {
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    gap: 0.5rem;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    margin-left: 0;
}

#secondary .wp-block-search__input,
#pts-sidebar-offcanvas .wp-block-search__input {
    min-width: 0 !important;
    flex: 1 1 0%;
    width: auto;
    border-radius: 0;
    box-sizing: border-box;
}

#secondary .wp-block-search__button,
#pts-sidebar-offcanvas .wp-block-search__button {
    white-space: nowrap;
    flex: 0 0 auto;
    margin-left: 0;
    padding: 0.4rem 1rem;
    border: 1px solid #0073aa;
    background: #0073aa;
    color: #ffffff;
    cursor: pointer;
}

#secondary .wp-block-search__button:hover,
#pts-sidebar-offcanvas .wp-block-search__button:hover {
    background: #005a87;
    border-color: #005a87;
    color: #ffffff;
}

/* Posts navigation / pagination */
.navigation {
    margin-block: 2rem;
}
.site-shell > .container > .row {
    align-items: flex-start;
}

/* Front page: hero background spans full page width */
.front-hero--full {
    width: 100%;
    max-width: 100%;
}

.front-hero-text {
    max-width: 42rem;
}

.sidebar-navigation a,
.mobile-navigation a {
    color: var(--pts-color-link);
    text-decoration: none;
    padding: 0.375rem 0;
    display: block;
}

.sidebar-navigation a:hover,
.mobile-navigation a:hover {
    color: #005a87;
}

.sidebar-navigation .current-menu-item > a,
.sidebar-navigation .current_page_item > a,
.mobile-navigation .current-menu-item > a,
.mobile-navigation .current_page_item > a {
    color: var(--pts-color-text);
    font-weight: 600;
}

#pts-sidebar-offcanvas .sidebar-navigation .sub-menu {
    display: block;
    position: static;
    visibility: visible;
    opacity: 1;
    width: auto;
    height: auto;
    overflow: visible;
    padding-left: 1rem;
    margin: 0;
    list-style: none;
}

.site-header .navbar-toggler {
    border: 1px solid rgba(0, 0, 0, 0.1);
    padding: 0.25rem 0.5rem;
}

.site-header .navbar-toggler-icon {
    width: 1.25em;
    height: 1.25em;
    background-image: url("data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 30 30%27%3e%3cpath stroke=%27rgba%280, 0, 0, 0.75%29%27 stroke-linecap=%27round%27 stroke-miterlimit=%2710%27 stroke-width=%272%27 d=%27M4 7h22M4 15h22M4 23h22%27/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 100%;
}

.site-header .navbar-toggler:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 115, 170, 0.25);
}

.front-card-title a,
.front-card-title .stretched-link {
    color: var(--pts-color-link);
}

.front-card-title a:hover {
    color: #005a87;
}

.site-footer .text-body-secondary a {
    color: inherit;
}

.site-footer .text-body-secondary a:hover {
    color: var(--pts-color-link);
}

.widget-area a:not(.wp-element-button):not(.btn) {
    color: var(--pts-color-link);
}

.widget-area a:not(.wp-element-button):not(.btn):hover {
    color: #005a87;
}

#primary.site-main {
    min-width: 0;
}

.site-main.full-width,
.site-main.no-sidebar,
.site-main.site-main-fullwidth {
    width: 100%;
}

.navigation .nav-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;
    align-items: center;
}

.navigation .page-numbers,
.navigation a {
    text-decoration: none;
}

.front-posts-grid > .front-card {
    width: 100%;
    max-width: 100%;
}

.front-card .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.front-card .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 1rem 2rem rgba(0,0,0,.08) !important;
}

.front-card-thumb img {
    aspect-ratio: 16 / 9;
    object-fit: cover;
}

.page-header .page-title {
    margin-bottom: 0;
}

.archive-description,
.entry-summary,
.front-card-excerpt {
    color: #6c757d;
}

.entry-content > *:first-child,
.page-content > *:first-child {
    margin-top: 0;
}
' . PTS_Animejs::get_stylesheet_css();
    }
}
