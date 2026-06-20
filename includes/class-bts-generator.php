<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Core Generator Logic
 */
class BTS_Generator
{
    /** @var array<string, mixed> */
    private $generation_selection = array();

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
        PTS_Admin::cleanup_stale_temp_dirs('block');

        // 1. Prepare Data
        $theme_name = isset($data['themeName']) ? sanitize_text_field($data['themeName']) : 'My Theme';
        $theme_slug = isset($data['themeSlug']) ? sanitize_title($data['themeSlug']) : 'my-theme';
        $author = isset($data['themeAuthor']) ? sanitize_text_field($data['themeAuthor']) : '';
        $author_uri = isset($data['themeAuthorUri']) ? esc_url_raw($data['themeAuthorUri']) : '';
        $description = isset($data['themeDescription']) ? sanitize_textarea_field($data['themeDescription']) : '';

        $selection = isset($data['selection']) ? $data['selection'] : array();
        $params = isset($data['params']) ? $data['params'] : array();
        $layout    = PTS_Layout_Settings::parse($data);

        // "Basic Template Parts Set": one key that enables every recommended part.
        if (! empty($selection['parts.basicSet'])) {
            foreach (array_keys($this->get_parts_map()) as $part_key) {
                $selection[ $part_key ] = 1;
            }
        }

        // "Extended Layout Parts Set": common site layout variations.
        if (! empty($selection['parts.extendedSet'])) {
            foreach (array_keys($this->get_extended_parts_map()) as $part_key) {
                $selection[ $part_key ] = 1;
            }
        }

        // "Japanese LP Parts Set": landing page sections common on Japanese sites.
        if (! empty($selection['parts.jpLpSet'])) {
            foreach (array_keys($this->get_jp_lp_parts_map()) as $part_key) {
                $selection[ $part_key ] = 1;
            }
        }

        // "Product LP Parts Set": EC product page sections (Rakuten-style).
        if (! empty($selection['parts.productLpSet'])) {
            foreach (array_keys($this->get_product_lp_parts_map()) as $part_key) {
                $selection[ $part_key ] = 1;
            }
        }

        // "Common Layout Kit": generic everyday page layouts.
        if (! empty($selection['parts.layoutKitSet'])) {
            foreach (array_keys($this->get_layout_kit_parts_map()) as $part_key) {
                $selection[ $part_key ] = 1;
            }
        }

        // Block themes default to a single-column layout. Enable the sidebar part only
        // when the user selects it or chooses a sidebar template variant.
        // Sidebar templates reference the sidebar part, so make sure it exists.
        if (! empty($selection['templates.singleWithSidebar']) || ! empty($selection['templates.pageWithSidebar'])) {
            $selection['parts.sidebar'] = 1;
        }

        $this->generation_selection = $selection;

        // Without single.html, singular views fall back to index.html (excerpt list only).
        if (! empty($selection['features.generateTemplates'])) {
            $selection['templates.single'] = 1;
        }

        // 2. Setup Temp Directory
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/pts_temp/block/' . uniqid();
        $theme_dir = $base_dir . '/' . $theme_slug;

        if (! wp_mkdir_p($theme_dir)) {
            return new WP_Error('mkdir_failed', __('Could not create temporary directory.', 'picot-theme-seeder'));
        }

        // 3. Generate style.css (Required)
        // Block theme: layout / spacing / typography live in theme.json, so the
        // stylesheet stays empty and is easy to design on top of.
        $style_header = "/*\n";
        $style_header .= "Theme Name: " . $theme_name . "\n";
        $style_header .= "Author: " . $author . "\n";
        if ($author_uri) {
            $style_header .= "Author URI: " . $author_uri . "\n";
        }
        $style_header .= "Description: " . $description . "\n";
        $style_header .= "Version: 1.0.0\n";
        $style_header .= "*/\n";

        $style_content = $style_header . "\n";
        $style_content .= "/* Layout, spacing and typography are defined in theme.json. Bootstrap is loaded from functions.php. */\n";
        $style_content .= ":root {\n";
        $style_content .= "    --pts-root-padding-inline: {$layout['paddingInline']};\n";
        $style_content .= "    --pts-section-gap: calc(var(--wp--style--block-gap, var(--pts-block-gap, 3rem)) * 1.8);\n";
        $style_content .= "}\n";
        $style_content .= ".wp-site-blocks > .wp-block-group.is-layout-constrained,\n";
        $style_content .= ".wp-site-blocks > main.wp-block-group {\n";
        $style_content .= "    margin-block-start: 0;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-shell .wp-block-post-template {\n";
        $style_content .= "    list-style: none;\n";
        $style_content .= "    padding-left: 0;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-card,\n";
        $style_content .= ".card,\n";
        $style_content .= ".pts-bootstrap-sidebar,\n";
        $style_content .= ".pts-selected-content.card,\n";
        $style_content .= ".pts-category-featured.card {\n";
        $style_content .= "    border-radius: 1rem;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-card .wp-block-post-title,\n";
        $style_content .= ".pts-singular-flow .wp-block-post-title {\n";
        $style_content .= "    line-height: 1.35;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-single-stack > * + *,\n";
        $style_content .= ".pts-singular-flow > * + *,\n";
        $style_content .= ".pts-singular-flow .wp-block-post-content > * + *,\n";
        $style_content .= ".pts-bootstrap-card .wp-block-post-content > * + *,\n";
        $style_content .= ".pts-selected-content .wp-block-group > * + * {\n";
        $style_content .= "    margin-block-start: 1rem;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-card .wp-block-post-excerpt {\n";
        $style_content .= "    color: var(--wp--preset--color--text, #495057);\n";
        $style_content .= "    font-size: 0.95rem;\n";
        $style_content .= "}\n";
        $style_content .= ".card .wp-block-post-featured-image img,\n";
        $style_content .= ".pts-bootstrap-card .wp-block-post-featured-image img {\n";
        $style_content .= "    border-radius: 0.75rem;\n";
        $style_content .= "    aspect-ratio: 16 / 9;\n";
        $style_content .= "    object-fit: cover;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-singular-flow img,\n";
        $style_content .= ".pts-singular-flow .wp-caption,\n";
        $style_content .= ".pts-singular-flow figure,\n";
        $style_content .= ".pts-singular-flow .wp-caption img {\n";
        $style_content .= "    max-width: 100%;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-singular-flow .wp-caption,\n";
        $style_content .= ".pts-singular-flow figure.wp-caption {\n";
        $style_content .= "    width: auto !important;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-singular-flow .alignnone,\n";
        $style_content .= ".pts-singular-flow .aligncenter,\n";
        $style_content .= ".pts-singular-flow .alignleft,\n";
        $style_content .= ".pts-singular-flow .alignright {\n";
        $style_content .= "    max-width: 100%;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-shell table,\n";
        $style_content .= ".pts-singular-flow table,\n";
        $style_content .= ".wp-block-table table {\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "    border-collapse: collapse;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-shell th,\n";
        $style_content .= ".pts-bootstrap-shell td,\n";
        $style_content .= ".pts-singular-flow th,\n";
        $style_content .= ".pts-singular-flow td,\n";
        $style_content .= ".wp-block-table th,\n";
        $style_content .= ".wp-block-table td {\n";
        $style_content .= "    border: 1px solid #dee2e6;\n";
        $style_content .= "    padding: 0.75rem;\n";
        $style_content .= "    vertical-align: top;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-shell thead th,\n";
        $style_content .= ".pts-singular-flow thead th,\n";
        $style_content .= ".wp-block-table thead th {\n";
        $style_content .= "    background: rgba(0, 0, 0, 0.04);\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-post-grid {\n";
        $style_content .= "    align-items: stretch;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-category-featured .wp-block-post,\n";
        $style_content .= ".pts-selected-content .wp-block-column {\n";
        $style_content .= "    height: 100%;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-category-featured .wp-block-post-title a,\n";
        $style_content .= ".pts-selected-content .wp-block-button__link {\n";
        $style_content .= "    text-decoration: none;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-sidebar ul,\n";
        $style_content .= ".pts-category-featured ul {\n";
        $style_content .= "    padding-left: 1rem;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-sidebar li + li,\n";
        $style_content .= ".pts-category-featured li + li {\n";
        $style_content .= "    margin-top: 0.75rem;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-card .wp-block-post-navigation-link a {\n";
        $style_content .= "    text-decoration: none;\n";
        $style_content .= "    font-weight: 600;\n";
        $style_content .= "}\n";
        $style_content .= "@media (min-width: 768px) {\n";
        $style_content .= "    .pts-post-navigation-columns .post-navigation-link-next {\n";
        $style_content .= "        text-align: right;\n";
        $style_content .= "    }\n";
        $style_content .= "}\n";
        $style_content .= "@media (max-width: 767.98px) {\n";
        $style_content .= "    .pts-post-navigation-columns .post-navigation-link-next,\n";
        $style_content .= "    .pts-post-navigation-columns .post-navigation-link-next.has-text-align-right {\n";
        $style_content .= "        text-align: left;\n";
        $style_content .= "    }\n";
        $style_content .= "}\n";
        $style_content .= ".wp-block-post-comments-form input:not([type=submit]),\n";
        $style_content .= ".wp-block-post-comments-form textarea {\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "    border: 1px solid #ced4da;\n";
        $style_content .= "    border-radius: 0;\n";
        $style_content .= "    padding: 0.75rem 0.875rem;\n";
        $style_content .= "}\n";
        $style_content .= ".wp-block-post-comments-form input[type=submit] {\n";
        $style_content .= "    border-radius: 999px;\n";
        $style_content .= "    padding-inline: 1.25rem;\n";
        $style_content .= "}\n";
        $style_content .= "html {\n";
        $style_content .= "    overflow-x: clip;\n";
        $style_content .= "}\n";
        $style_content .= "header.wp-block-template-part {\n";
        $style_content .= "    position: sticky;\n";
        $style_content .= "    top: var(--wp-admin--admin-bar--height, 0px);\n";
        $style_content .= "    z-index: 1030;\n";
        $style_content .= "    box-sizing: border-box;\n";
        $style_content .= "    width: calc(100% + (2 * var(--pts-root-padding-inline, 1rem)));\n";
        $style_content .= "    max-width: none;\n";
        $style_content .= "    margin-inline: calc(-1 * var(--pts-root-padding-inline, 1rem));\n";
        $style_content .= "    background-color: var(--wp--preset--color--background, #fff);\n";
        $style_content .= "}\n";
        $style_content .= ".site-header {\n";
        $style_content .= "    margin-bottom: 0;\n";
        $style_content .= "    border-bottom: none;\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "    max-width: none;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .navbar {\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "    --pts-header-padding-y: 0.444rem;\n";
        $style_content .= "    padding-block: var(--pts-header-padding-y) !important;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .navbar.py-4 {\n";
        $style_content .= "    --pts-header-padding-y: 0.667rem;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .navbar-brand {\n";
        $style_content .= "    margin-bottom: 0;\n";
        $style_content .= "}\n";
        $style_content .= "@media (min-width: 992px) {\n";
        $style_content .= "    .site-header .navbar-brand.me-lg-4 {\n";
        $style_content .= "        margin-inline-end: 0.667rem !important;\n";
        $style_content .= "    }\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .site-header-bar {\n";
        $style_content .= "    min-width: 0;\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .site-branding {\n";
        $style_content .= "    min-width: 0;\n";
        $style_content .= "    flex: 0 1 auto;\n";
        $style_content .= "    text-align: left;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .site-branding .wp-block-site-title {\n";
        $style_content .= "    text-align: left;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .navbar-toggler {\n";
        $style_content .= "    flex-shrink: 0;\n";
        $style_content .= "    border: 1px solid rgba(0, 0, 0, 0.1);\n";
        $style_content .= "    padding: 0.25rem 0.5rem;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .navbar-toggler-icon {\n";
        $style_content .= "    width: 1.25em;\n";
        $style_content .= "    height: 1.25em;\n";
        $style_content .= "    background-image: url(\"data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 30 30%27%3e%3cpath stroke=%27rgba%280, 0, 0, 0.75%29%27 stroke-linecap=%27round%27 stroke-miterlimit=%2710%27 stroke-width=%272%27 d=%27M4 7h22M4 15h22M4 23h22%27/%3e%3c/svg%3e\");\n";
        $style_content .= "    background-repeat: no-repeat;\n";
        $style_content .= "    background-position: center;\n";
        $style_content .= "    background-size: 100%;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .navbar-toggler:focus {\n";
        $style_content .= "    box-shadow: 0 0 0 0.2rem rgba(0, 115, 170, 0.25);\n";
        $style_content .= "}\n";
        $style_content .= "#pts-sidebar-offcanvas .offcanvas-body {\n";
        $style_content .= "    min-width: 0;\n";
        $style_content .= "    max-width: 100%;\n";
        $style_content .= "}\n";
        $style_content .= "#pts-sidebar-offcanvas .wp-block-search {\n";
        $style_content .= "    max-width: 100%;\n";
        $style_content .= "    min-width: 0;\n";
        $style_content .= "}\n";
        $style_content .= "#pts-sidebar-offcanvas .wp-block-search__label {\n";
        $style_content .= "    display: none;\n";
        $style_content .= "}\n";
        $style_content .= "#pts-sidebar-offcanvas .wp-block-search__inside-wrapper {\n";
        $style_content .= "    display: flex;\n";
        $style_content .= "    flex-wrap: nowrap;\n";
        $style_content .= "    align-items: stretch;\n";
        $style_content .= "    gap: 0.5rem;\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "    max-width: 100%;\n";
        $style_content .= "    min-width: 0;\n";
        $style_content .= "    margin-left: 0;\n";
        $style_content .= "}\n";
        $style_content .= "#pts-sidebar-offcanvas .wp-block-search__input {\n";
        $style_content .= "    min-width: 0 !important;\n";
        $style_content .= "    flex: 1 1 0%;\n";
        $style_content .= "    width: auto;\n";
        $style_content .= "    border-radius: 0;\n";
        $style_content .= "    box-sizing: border-box;\n";
        $style_content .= "}\n";
        $style_content .= "#pts-sidebar-offcanvas .wp-block-search__button {\n";
        $style_content .= "    white-space: nowrap;\n";
        $style_content .= "    flex: 0 0 auto;\n";
        $style_content .= "    margin-left: 0;\n";
        $style_content .= "    padding: 0.4rem 1rem;\n";
        $style_content .= "    border: 1px solid var(--wp--preset--color--primary, #0073aa);\n";
        $style_content .= "    background: var(--wp--preset--color--primary, #0073aa);\n";
        $style_content .= "    color: #ffffff;\n";
        $style_content .= "    cursor: pointer;\n";
        $style_content .= "}\n";
        $style_content .= "#pts-sidebar-offcanvas .wp-block-search__button:hover {\n";
        $style_content .= "    background: #005a87;\n";
        $style_content .= "    border-color: #005a87;\n";
        $style_content .= "    color: #ffffff;\n";
        $style_content .= "}\n";
        $style_content .= ".mobile-navigation .wp-block-navigation-item__content,\n";
        $style_content .= ".pts-sidebar-nav .wp-block-navigation-item__content {\n";
        $style_content .= "    display: block;\n";
        $style_content .= "    padding-block: 0.375rem;\n";
        $style_content .= "    text-decoration: none;\n";
        $style_content .= "    font-weight: 500;\n";
        $style_content .= "}\n";
        $style_content .= ".mobile-navigation .wp-block-navigation-item__content:hover,\n";
        $style_content .= ".pts-sidebar-nav .wp-block-navigation-item__content:hover {\n";
        $style_content .= "    color: #005a87;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .pts-header-nav.wp-block-navigation {\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .pts-header-nav .wp-block-navigation__container {\n";
        $style_content .= "    gap: 0.333rem;\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "}\n";
        $style_content .= ".site-header .pts-header-nav .wp-block-navigation-item__content {\n";
        $style_content .= "    font-weight: 500;\n";
        $style_content .= "    text-decoration: none;\n";
        $style_content .= "}\n";
        $style_content .= "@media (min-width: 992px) {\n";
        $style_content .= "    .site-header .pts-header-nav .wp-block-navigation__container {\n";
        $style_content .= "        justify-content: flex-end;\n";
        $style_content .= "    }\n";
        $style_content .= "    .site-header .pts-header-nav.is-content-justification-center .wp-block-navigation__container {\n";
        $style_content .= "        justify-content: center;\n";
        $style_content .= "    }\n";
        $style_content .= "}\n";
        $style_content .= "@media (max-width: 991.98px) {\n";
        $style_content .= "    .site-header .pts-header-nav .wp-block-navigation-item {\n";
        $style_content .= "        width: 100%;\n";
        $style_content .= "    }\n";
        $style_content .= "    .site-header .pts-header-nav .wp-block-navigation-item__content {\n";
        $style_content .= "        display: block;\n";
        $style_content .= "        padding-block: 0.222rem;\n";
        $style_content .= "    }\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-shell.container {\n";
        $style_content .= "    padding-top: 0;\n";
        $style_content .= "}\n";
        $style_content .= "/* Section spacing below content blocks (header & footer excluded) */\n";
        $style_content .= ".wp-site-blocks > section.pts-hero-section,\n";
        $style_content .= ".pts-dynamic-page > * {\n";
        $style_content .= "    margin-bottom: var(--pts-section-gap);\n";
        $style_content .= "}\n";
        $style_content .= ".pts-page-section > * + * {\n";
        $style_content .= "    margin-block-start: 1.25rem;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-dynamic-page .wp-block-separator {\n";
        $style_content .= "    border: 0;\n";
        $style_content .= "    height: 0;\n";
        $style_content .= "    margin-block: var(--pts-section-gap);\n";
        $style_content .= "    opacity: 0;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-footer {\n";
        $style_content .= "    margin-top: 0;\n";
        $style_content .= "}\n";
        $style_content .= ".wp-site-blocks .btn,\n";
        $style_content .= ":root :where(.wp-element-button, .wp-block-button__link),\n";
        $style_content .= ".wp-block-post-comments-form input[type=submit],\n";
        $style_content .= ".wp-block-search__button,\n";
        $style_content .= ".wp-block-file__button {\n";
        $style_content .= "    border-radius: 999px;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-shell.container > .alignfull,\n";
        $style_content .= ".pts-bootstrap-shell.container > .wp-block-group.alignfull,\n";
        $style_content .= ".pts-bootstrap-shell.container > section.alignfull {\n";
        $style_content .= "    box-sizing: border-box;\n";
        $style_content .= "    width: auto;\n";
        $style_content .= "    max-width: none;\n";
        $style_content .= "    margin-inline: calc(50% - 50vw);\n";
        $style_content .= "}\n";
        $style_content .= ".pts-bootstrap-shell.container > section.alignfull .alignfull,\n";
        $style_content .= ".pts-bootstrap-shell.container > .alignfull .alignfull {\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "    max-width: 100%;\n";
        $style_content .= "    margin-inline: 0;\n";
        $style_content .= "}\n";
        $style_content .= "section.pts-hero-section {\n";
        $style_content .= "    box-sizing: border-box;\n";
        $style_content .= "    width: calc(100% + (2 * var(--pts-root-padding-inline, 1rem)));\n";
        $style_content .= "    max-width: none;\n";
        $style_content .= "    margin-inline: calc(-1 * var(--pts-root-padding-inline, 1rem));\n";
        $style_content .= "}\n";
        $style_content .= "section.pts-hero-section .wp-block-cover.alignfull {\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "    max-width: none;\n";
        $style_content .= "    margin-inline: 0;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-full-width.alignfull,\n";
        $style_content .= ".pts-hero-full-width.wp-block-cover,\n";
        $style_content .= ".pts-hero-edge.alignfull {\n";
        $style_content .= "    border-radius: 0;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-full-width.wp-block-cover .wp-block-cover__image-background {\n";
        $style_content .= "    object-fit: cover;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-full-width.wp-block-cover .wp-block-cover__inner-container {\n";
        $style_content .= "    text-align: center;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-edge.alignfull {\n";
        $style_content .= "    padding-inline: max(1rem, env(safe-area-inset-left)) max(1rem, env(safe-area-inset-right));\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-crossfade {\n";
        $style_content .= "    position: relative;\n";
        $style_content .= "    min-height: clamp(20rem, 50vh, 32rem);\n";
        $style_content .= "    overflow: hidden;\n";
        $style_content .= "    border-radius: 0;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-crossfade__slides {\n";
        $style_content .= "    position: absolute;\n";
        $style_content .= "    inset: 0;\n";
        $style_content .= "    margin: 0 !important;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-crossfade__slide {\n";
        $style_content .= "    position: absolute;\n";
        $style_content .= "    inset: 0;\n";
        $style_content .= "    margin: 0;\n";
        $style_content .= "    opacity: 0;\n";
        $style_content .= "    transition: opacity 1.2s ease-in-out;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-crossfade__slide.is-active {\n";
        $style_content .= "    opacity: 1;\n";
        $style_content .= "    z-index: 1;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-crossfade__slide img {\n";
        $style_content .= "    width: 100%;\n";
        $style_content .= "    height: 100%;\n";
        $style_content .= "    object-fit: cover;\n";
        $style_content .= "    display: block;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-crossfade__content {\n";
        $style_content .= "    position: relative;\n";
        $style_content .= "    z-index: 2;\n";
        $style_content .= "    display: flex;\n";
        $style_content .= "    flex-direction: column;\n";
        $style_content .= "    justify-content: center;\n";
        $style_content .= "    min-height: inherit;\n";
        $style_content .= "}\n";
        $style_content .= ".pts-hero-crossfade__content::before {\n";
        $style_content .= "    content: \"\";\n";
        $style_content .= "    position: absolute;\n";
        $style_content .= "    inset: 0;\n";
        $style_content .= "    background: rgba(0, 0, 0, 0.4);\n";
        $style_content .= "    z-index: -1;\n";
        $style_content .= "}\n";
        $style_content .= "@media (prefers-reduced-motion: reduce) {\n";
        $style_content .= "    .pts-hero-crossfade__slide {\n";
        $style_content .= "        transition: none;\n";
        $style_content .= "    }\n";
        $style_content .= "}\n";
        $style_content .= PTS_Animejs::get_stylesheet_css();
        $result = $this->write_file($theme_dir . '/style.css', $style_content);
        if (is_wp_error($result)) {
            return $result;
        }

        wp_mkdir_p($theme_dir . '/assets/js');
        $result = $this->write_file($theme_dir . '/assets/js/animate-init.js', PTS_Animejs::get_init_js());
        if (is_wp_error($result)) {
            return $result;
        }

        // 4. Generate theme.json
        if (! empty($selection['features.generateThemeJson'])) {
            $theme_json_builder = new BTS_Theme_JSON();
            $theme_json_data = $theme_json_builder->build($params);

            // Register generated parts so the Site Editor shows them in the right areas.
            if (! empty($selection['features.generateParts'])) {
                $template_parts = $this->get_template_parts_meta($selection);
                if (! empty($template_parts)) {
                    $theme_json_data['templateParts'] = $template_parts;
                }
            }

            // Register sidebar templates as selectable custom templates.
            $custom_templates = array();
            if (! empty($selection['templates.singleWithSidebar'])) {
                $custom_templates[] = array(
                    'name'      => 'single-with-sidebar',
                    'title'     => '投稿（サイドバー付き）',
                    'postTypes' => array('post'),
                );
            }
            if (! empty($selection['templates.pageWithSidebar'])) {
                $custom_templates[] = array(
                    'name'      => 'page-with-sidebar',
                    'title'     => '固定ページ（サイドバー付き）',
                    'postTypes' => array('page'),
                );
            }
            if (! empty($selection['templates.pageLp'])) {
                $custom_templates[] = array(
                    'name'      => 'page-lp',
                    'title'     => 'LP（ヘッダー・フッターなし）',
                    'postTypes' => array('page'),
                );
            }
            if (! empty($custom_templates)) {
                $theme_json_data['customTemplates'] = $custom_templates;
            }

            $result = $this->write_file(
                $theme_dir . '/theme.json',
                json_encode($theme_json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
            if (is_wp_error($result)) {
                return $result;
            }

            // 4.5 Style variations (styles/*.json). The base theme.json wires
            // links / buttons / body colors to palette presets, so swapping
            // the palette here recolors the whole theme from the Styles UI.
            if (! empty($selection['features.generateStyleVariations'])) {
                wp_mkdir_p($theme_dir . '/styles');
                foreach ($this->get_style_variations() as $variation_file => $variation) {
                    $result = $this->write_file(
                        $theme_dir . '/styles/' . $variation_file,
                        json_encode($variation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    );
                    if (is_wp_error($result)) {
                        return $result;
                    }
                }
            }
        }

        // 5. Generate Templates
        if (! empty($selection['features.generateTemplates'])) {
            wp_mkdir_p($theme_dir . '/templates');
            $this->generate_templates($theme_dir . '/templates', $selection);
        }

        // 6. Generate Parts
        if (! empty($selection['features.generateParts'])) {
            wp_mkdir_p($theme_dir . '/parts');
            $this->generate_parts($theme_dir . '/parts', $selection);

            if (! empty($selection['parts.heroCrossfade'])) {
                wp_mkdir_p($theme_dir . '/assets/js');
                $result = $this->write_file(
                    $theme_dir . '/assets/js/pts-hero-crossfade.js',
                    $this->get_hero_crossfade_js()
                );
                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        // 7. Generate Patterns (slug / category rewritten to the theme slug)
        wp_mkdir_p($theme_dir . '/patterns');
        $this->generate_patterns($theme_dir . '/patterns', $selection, $theme_slug);

        // 7.2 functions.php: template part areas + pattern category, so the
        // Site Editor and inserter stay organized.
        $functions_php = $this->get_functions_php($theme_slug, $theme_name, $selection);
        if ($functions_php !== '') {
            $result = $this->write_file($theme_dir . '/functions.php', $functions_php);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        // 7.4 screenshot.png (uses the brand primary color when provided)
        $brand_primary = isset($params['brandColors']['primary']) ? (string) $params['brandColors']['primary'] : '#0073aa';
        PTS_Screenshot::create($theme_dir . '/screenshot.png', $theme_name, $brand_primary);

        // 7.5 Generate SCSS sources (assets/scss) + package.json
        if (! empty($selection['features.generateScss'])) {
            $scss_dir = $theme_dir . '/assets/scss';
            wp_mkdir_p($scss_dir);

            $layout = PTS_Layout_Settings::parse($data);
            foreach (PTS_Scss::get_block_files($style_header, $layout) as $scss_file => $scss_content) {
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

        // 8. Output Handling
        $output_mode = isset($data['outputMode']) ? $data['outputMode'] : 'direct';

        if ($output_mode === 'direct') {
            $destination = WP_CONTENT_DIR . '/themes/' . $theme_slug;
            if (file_exists($destination)) {
                return new WP_Error(
                    'theme_exists',
                    sprintf(
                        /* translators: %s: theme slug */
                        __('Theme directory already exists: %s', 'picot-theme-seeder'),
                        $theme_slug
                    )
                );
            }

            // Move from temp to themes dir using WP_Filesystem
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
            global $wp_filesystem;

            if ($wp_filesystem->move($theme_dir, $destination)) {
                return array(
                    'success' => true,
                    'message' => __('Theme generated successfully.', 'picot-theme-seeder'),
                    'zipUrl'  => null,
                );
            } else {
                return new WP_Error('move_failed', __('Could not move theme to the themes directory. Check permissions.', 'picot-theme-seeder'));
            }
        } else {
            // ZIP download output.
            $zip_file = $base_dir . '/' . $theme_slug . '.zip';
            $zipper = new BTS_Zip();
            $result = $zipper->create_zip($theme_dir, $zip_file);

            if (is_wp_error($result)) {
                return $result;
            }

            // Return URL
            $zip_url = PTS_Admin::create_temp_download_url($zip_file, $theme_slug);
            if (is_wp_error($zip_url)) {
                return $zip_url;
            }

            return array(
                'zipUrl' => $zip_url,
                'log' => __('Theme generated successfully.', 'picot-theme-seeder'),
            );
        }
    }

    private function generate_templates($dir, $selection)
    {
        // Map selection keys to file names
        $map = array(
            'templates.index' => 'index.html',
            'templates.frontPage' => 'front-page.html',
            'templates.home' => 'home.html',
            'templates.single' => 'single.html',
            'templates.singleWithSidebar' => 'single-with-sidebar.html',
            'templates.page' => 'page.html',
            'templates.pageWithSidebar' => 'page-with-sidebar.html',
            'templates.pageLp' => 'page-lp.html',
            'templates.singular' => 'singular.html',
            'templates.archive' => 'archive.html',
            'templates.search' => 'search.html',
            'templates.404' => '404.html',
        );

        foreach ($map as $key => $file) {
            if (! empty($selection[$key])) {
                // LP canvas: no header/footer so visitors stay on the page.
                if ('page-lp.html' === $file) {
                    $content = '<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->' . "\n"
                        . '<main class="wp-block-group">' . "\n"
                        . '    <!-- wp:post-content {"layout":{"type":"constrained"}} /-->' . "\n"
                        . '</main>' . "\n"
                        . '<!-- /wp:group -->' . "\n";
                } elseif ('front-page.html' === $file) {
                    $content = $this->wrap_front_page_shell($this->get_template_main_content($file, $selection), $selection);
                } else {
                    $content = $this->wrap_in_shell($this->get_template_main_content($file, $selection), $selection);
                }
                file_put_contents($dir . '/' . $file, $content);
            }
        }
    }

    /**
     * Whether the generated theme uses the default 2-column main + sidebar layout.
     *
     * @param array<string, mixed> $selection Wizard selection flags.
     */
    private function uses_two_column_layout($selection)
    {
        return ! empty($selection['parts.sidebar']);
    }

    /**
     * Wrap template inner markup in Bootstrap 8/4 columns with the sidebar part.
     *
     * @param string $main_inner Block markup placed in the main column.
     * @return string
     */
    private function wrap_main_in_columns($main_inner)
    {
        $indented = preg_replace('/^/m', '            ', trim($main_inner));

        return '    <!-- wp:columns {"align":"wide","className":"row g-4"} -->' . "\n"
            . '    <div class="wp-block-columns alignwide row g-4">' . "\n"
            . '        <!-- wp:column {"width":"66.66%"} -->' . "\n"
            . '        <div class="wp-block-column col-lg-8" style="flex-basis:66.66%">' . "\n"
            . $indented . "\n"
            . '        </div>' . "\n"
            . '        <!-- /wp:column -->' . "\n\n"
            . '        <!-- wp:column {"width":"33.33%"} -->' . "\n"
            . '        <div class="wp-block-column col-lg-4" style="flex-basis:33.33%">' . "\n"
            . '            <!-- wp:template-part {"slug":"sidebar","tagName":"aside","area":"sidebar"} /-->' . "\n"
            . '        </div>' . "\n"
            . '        <!-- /wp:column -->' . "\n"
            . '    </div>' . "\n"
            . '    <!-- /wp:columns -->';
    }

    /**
     * Header for 2-column layout: site title + offcanvas burger (desktop nav lives in sidebar).
     *
     * @return string
     */
    private function get_site_title_only_header_markup()
    {
        return $this->get_two_column_header_shell_markup() . $this->get_sidebar_offcanvas_markup();
    }

    /**
     * Compact header bar with branding and a mobile offcanvas toggler.
     *
     * @return string
     */
    private function get_two_column_header_shell_markup()
    {
        return '<!-- wp:group {"className":"site-header border-bottom bg-white sticky-top","layout":{"type":"default"}} -->
<div class="wp-block-group site-header border-bottom bg-white sticky-top">
    <!-- wp:group {"className":"container py-3","layout":{"type":"constrained"}} -->
    <div class="wp-block-group container py-3">
        <!-- wp:group {"className":"site-header-bar d-flex align-items-center justify-content-between gap-3 w-100","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
        <div class="wp-block-group site-header-bar d-flex align-items-center justify-content-between gap-3 w-100">
            <!-- wp:group {"className":"site-branding d-flex flex-column","layout":{"type":"default"}} -->
            <div class="wp-block-group site-branding d-flex flex-column">
                <!-- wp:site-title {"textAlign":"left","className":"fw-bold fs-4 mb-0"} /-->

                <!-- wp:site-tagline {"className":"text-body-secondary small"} /-->
            </div>
            <!-- /wp:group -->

            <!-- wp:html -->
<button class="navbar-toggler d-lg-none flex-shrink-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#pts-sidebar-offcanvas" aria-controls="pts-sidebar-offcanvas" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
<!-- /wp:html -->
        </div>
        <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';
    }

    /**
     * Mobile offcanvas panel (same interaction as classic themes).
     *
     * @return string
     */
    private function get_sidebar_offcanvas_markup()
    {
        return '<!-- wp:html -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="pts-sidebar-offcanvas" aria-labelledby="pts-sidebar-offcanvas-label">
<!-- /wp:html -->
<!-- wp:group {"className":"offcanvas-header border-bottom","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="offcanvas-header border-bottom">
    <!-- wp:site-title {"level":2,"className":"offcanvas-title h5 mb-0","fontSize":"medium"} /-->

    <!-- wp:html -->
<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
<!-- wp:html -->
<div class="offcanvas-body d-grid gap-4">
<!-- /wp:html -->
<!-- wp:group {"className":"mobile-navigation","layout":{"type":"constrained"}} -->
<div class="wp-block-group mobile-navigation">
    <!-- wp:navigation {"overlayMenu":"never","className":"pts-mobile-nav flex-column gap-1","layout":{"type":"flex","orientation":"vertical"}} /-->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"pts-offcanvas-widgets d-grid gap-4","layout":{"type":"constrained"}} -->
<div class="wp-block-group pts-offcanvas-widgets d-grid gap-4">
    <!-- wp:heading {"level":2,"fontSize":"medium","className":"h5 mb-0"} -->
    <h2 class="wp-block-heading h5 has-medium-font-size mb-0">Recent Posts</h2>
    <!-- /wp:heading -->

    <!-- wp:latest-posts {"displayPostDate":true,"className":"small"} /-->
</div>
<!-- /wp:group -->
<!-- wp:html -->
</div>
</div>
<!-- /wp:html -->';
    }

    /**
     * Shared template shell: header part > constrained <main> > footer part.
     * Every template uses the same structure so designers can restyle one place.
     *
     * @param string               $main_inner Block markup placed inside <main>.
     * @param array<string, mixed> $selection  Wizard selection flags.
     * @return string
     */
    private function wrap_in_shell($main_inner, $selection = array())
    {
        if ($this->uses_two_column_layout($selection)) {
            $main_inner = $this->wrap_main_in_columns($main_inner);
        }

        $shell  = '<!-- wp:template-part {"slug":"header","tagName":"header","area":"header","className":"pts-sticky-header"} /-->' . "\n\n";
        $shell .= '<!-- wp:group {"tagName":"main","className":"pts-bootstrap-shell container pb-5 pts-dynamic-page","layout":{"type":"constrained"}} -->' . "\n";
        $shell .= '<main class="wp-block-group pts-bootstrap-shell container pb-5 pts-dynamic-page">' . "\n";
        $shell .= $main_inner . "\n";
        $shell .= '</main>' . "\n";
        $shell .= '<!-- /wp:group -->' . "\n\n";
        $shell .= '<!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->' . "\n";

        return $shell;
    }

    /**
     * Front page shell: header, full-bleed hero (outside container), then main content.
     *
     * @param string               $main_inner Block markup inside <main> (without hero).
     * @param array<string, mixed> $selection  Wizard selection.
     * @return string
     */
    private function wrap_front_page_shell($main_inner, $selection)
    {
        $hero = ! empty($selection['parts.heroFullWidth'])
            ? '<!-- wp:template-part {"slug":"hero-full-width","area":"pts-section","tagName":"section","className":"pts-hero-section"} /-->' . "\n\n"
            : $this->get_hero_full_width_markup() . "\n\n";

        if ($this->uses_two_column_layout($selection)) {
            $main_inner = $this->wrap_main_in_columns($main_inner);
        }

        $shell  = '<!-- wp:template-part {"slug":"header","tagName":"header","area":"header","className":"pts-sticky-header"} /-->' . "\n\n";
        $shell .= $hero;
        $shell .= '<!-- wp:group {"tagName":"main","className":"pts-bootstrap-shell container pb-5 pts-dynamic-page","layout":{"type":"constrained"}} -->' . "\n";
        $shell .= '<main class="wp-block-group pts-bootstrap-shell container pb-5 pts-dynamic-page">' . "\n";
        $shell .= $main_inner . "\n";
        $shell .= '</main>' . "\n";
        $shell .= '<!-- /wp:group -->' . "\n\n";
        $shell .= '<!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->' . "\n";

        return $shell;
    }

    /**
     * Inherited query loop with pagination and a no-results fallback.
     *
     * @return string
     */
    private function get_query_loop_markup()
    {
        return '    <!-- wp:query {"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true}} -->
    <div class="wp-block-query">
        <!-- wp:post-template {"className":"d-grid gap-4 pts-animate-stagger","layout":{"type":"default"}} -->
        <!-- wp:group {"className":"pts-bootstrap-card card border-0 shadow-sm p-4","layout":{"type":"constrained"}} -->
        <div class="wp-block-group pts-bootstrap-card card border-0 shadow-sm p-4">
            <!-- wp:post-title {"isLink":true,"className":"h3 mb-2"} /-->
            <!-- wp:post-date {"className":"text-body-secondary small mb-3"} /-->
            <!-- wp:post-excerpt {"moreText":"Read more"} /-->
        </div>
        <!-- /wp:group -->
        <!-- /wp:post-template -->

        <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"space-between"}} -->
        <!-- wp:query-pagination-previous {"label":"Previous"} /-->
        <!-- wp:query-pagination-numbers /-->
        <!-- wp:query-pagination-next {"label":"Next"} /-->
        <!-- /wp:query-pagination -->

        <!-- wp:query-no-results -->
        <!-- wp:paragraph -->
        <p>Nothing found.</p>
        <!-- /wp:paragraph -->
        <!-- /wp:query-no-results -->
    </div>
    <!-- /wp:query -->';
    }

    /**
     * Main-area markup per template file.
     *
     * @param string $file      Template file name.
     * @param array  $selection Wizard selection (used to reference optional parts).
     * @return string
     */
    private function get_template_main_content($file, $selection)
    {
        $post_meta = ! empty($selection['parts.postMeta'])
            ? '    <!-- wp:template-part {"slug":"post-meta"} /-->'
            : '    <!-- wp:post-date /-->';

        $comments = ! empty($selection['parts.comments'])
            ? "\n\n" . '    <!-- wp:template-part {"slug":"comments"} /-->'
            : '';

        $post_nav = ! empty($selection['parts.postNavigation'])
            ? "\n\n" . '    <!-- wp:template-part {"slug":"post-navigation"} /-->'
            : '';

        switch ($file) {
            case 'front-page.html':
                // Hero is rendered outside <main> via wrap_front_page_shell().
                return '
    <!-- wp:group {"className":"pts-page-section","layout":{"type":"constrained"}} -->
    <div class="wp-block-group pts-page-section">
        <!-- wp:heading {"textAlign":"center","className":"pts-animate pts-animate-fade-up mb-4"} -->
        <h2 class="wp-block-heading has-text-align-center pts-animate pts-animate-fade-up mb-4">What we offer</h2>
        <!-- /wp:heading -->

        <!-- wp:columns {"className":"row g-4 pts-animate-stagger","layout":{"type":"default"}} -->
        <div class="wp-block-columns row g-4 pts-animate-stagger">
        <!-- wp:column -->
        <div class="wp-block-column col-md-4">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading h4">Feature one</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>Explain the first thing your site or business does best.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column col-md-4">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading h4">Feature two</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>Describe a second strength, service, or benefit.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column col-md-4">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading h4">Feature three</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>Add a third reason visitors should stay and explore.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
        </div>
        <!-- /wp:columns -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"className":"pts-page-section","layout":{"type":"constrained"}} -->
    <div class="wp-block-group pts-page-section">
        <!-- wp:group {"className":"d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pts-animate pts-animate-fade-up","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
        <div class="wp-block-group d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pts-animate pts-animate-fade-up">
            <!-- wp:heading {"level":2,"className":"mb-0"} -->
            <h2 class="wp-block-heading mb-0">Latest posts</h2>
            <!-- /wp:heading -->

            <!-- wp:buttons -->
            <div class="wp-block-buttons">
                <!-- wp:button {"className":"is-style-outline"} -->
                <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button btn btn-outline-secondary">View all</a></div>
                <!-- /wp:button -->
            </div>
            <!-- /wp:buttons -->
        </div>
        <!-- /wp:group -->

        <!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
        <div class="wp-block-query">
            <!-- wp:post-template {"className":"pts-bootstrap-post-grid gap-4 pts-animate-stagger","layout":{"type":"grid","columnCount":3}} -->
            <!-- wp:group {"className":"pts-bootstrap-card card h-100 border-0 shadow-sm p-3","layout":{"type":"constrained"}} -->
            <div class="wp-block-group pts-bootstrap-card card h-100 border-0 shadow-sm p-3">
                <!-- wp:post-featured-image {"isLink":true} /-->

                <!-- wp:post-title {"isLink":true,"fontSize":"medium","className":"h4"} /-->

                <!-- wp:post-excerpt {"moreText":"Read more","excerptLength":18} /-->

                <!-- wp:post-date {"className":"text-body-secondary small"} /-->
            </div>
            <!-- /wp:group -->
            <!-- /wp:post-template -->

            <!-- wp:query-no-results -->
            <!-- wp:paragraph -->
            <p>No posts yet.</p>
            <!-- /wp:paragraph -->
            <!-- /wp:query-no-results -->
        </div>
        <!-- /wp:query -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"className":"pts-bootstrap-cta py-5 px-4 text-center bg-primary-subtle pts-animate pts-animate-zoom-in","layout":{"type":"constrained"}} -->
    <div class="wp-block-group pts-bootstrap-cta py-5 px-4 text-center bg-primary-subtle pts-animate pts-animate-zoom-in">
        <!-- wp:heading {"textAlign":"center"} -->
        <h2 class="wp-block-heading has-text-align-center mb-3">Ready to get in touch?</h2>
        <!-- /wp:heading -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button -->
            <div class="wp-block-button"><a class="wp-block-button__link wp-element-button btn btn-primary btn-lg px-4">Contact us</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->';

            case 'single-with-sidebar.html':
            case 'single.html':
                return '    <!-- wp:group {"className":"pts-single-stack","layout":{"type":"constrained"}} -->
    <div class="wp-block-group pts-single-stack">
    <!-- wp:group {"className":"pts-singular-flow","layout":{"type":"constrained"}} -->
    <div class="wp-block-group pts-singular-flow">
    <!-- wp:post-title {"className":"display-5 fw-bold mb-4"} /-->

' . str_replace('wp:post-date', 'wp:post-date {"className":"text-body-secondary small"}', $post_meta) . "\n\n"
                    . "    <!-- wp:post-featured-image {\"className\":\"mb-4\"} /-->\n\n"
                    . '    <!-- wp:post-content {"layout":{"type":"constrained"}} /-->
    </div>
    <!-- /wp:group -->'
                    . $post_nav
                    . $comments . '
    </div>
    <!-- /wp:group -->';

            case 'page-with-sidebar.html':
            case 'page.html':
            case 'singular.html':
                return '    <!-- wp:group {"className":"pts-singular-flow","layout":{"type":"constrained"}} -->
    <div class="wp-block-group pts-singular-flow">
    <!-- wp:post-title {"className":"display-5 fw-bold mb-4"} /-->

    <!-- wp:post-featured-image {"className":"mb-4"} /-->

    <!-- wp:post-content {"layout":{"type":"constrained"}} /-->
    </div>
    <!-- /wp:group -->';

            case 'archive.html':
                return '    <!-- wp:group {"className":"mb-5","layout":{"type":"constrained"}} -->
    <div class="wp-block-group mb-5">
    <!-- wp:query-title {"type":"archive","className":"display-6 fw-bold mb-2"} /-->

    <!-- wp:term-description {"className":"text-body-secondary"} /-->
    </div>
    <!-- /wp:group -->' . "\n\n"
                    . $this->get_query_loop_markup();

            case 'search.html':
                return '    <!-- wp:group {"className":"mb-5","layout":{"type":"constrained"}} -->
    <div class="wp-block-group mb-5">
    <!-- wp:query-title {"type":"search","className":"display-6 fw-bold mb-3"} /-->

    <!-- wp:search {"label":"Search","showLabel":false,"buttonText":"Search","className":"mb-0"} /-->
    </div>
    <!-- /wp:group -->' . "\n\n"
                    . $this->get_query_loop_markup();

            case '404.html':
                return '    <!-- wp:heading {"level":1} -->
    <h1 class="wp-block-heading">Page not found</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The page you are looking for does not exist. Try a search instead.</p>
    <!-- /wp:paragraph -->

    <!-- wp:search {"label":"Search","showLabel":false,"buttonText":"Search"} /-->';

            case 'index.html':
            case 'home.html':
            default:
                return '    <!-- wp:group {"className":"mb-5","layout":{"type":"constrained"}} -->
    <div class="wp-block-group mb-5">
    <!-- wp:heading {"level":1,"className":"display-6 fw-bold mb-0"} -->
    <h1 class="wp-block-heading display-6 fw-bold mb-0">Latest posts</h1>
    <!-- /wp:heading -->
    </div>
    <!-- /wp:group -->' . "\n\n" . $this->get_query_loop_markup();
        }
    }

    /**
     * Style variations written to styles/*.json. Each one only swaps the
     * color palette (same slugs as the base theme.json), plus small contrast
     * fixes, so it shows up in Site Editor > Styles > Browse styles.
     *
     * @return array<string, array<string, mixed>>
     */
    private function get_style_variations()
    {
        $schema = 'https://schemas.wp.org/trunk/theme.json';

        return array(
            'dark.json'    => array(
                '$schema'  => $schema,
                'version'  => 3,
                'title'    => 'ダーク',
                'settings' => array(
                    'color' => array(
                        'palette' => array(
                            array('name' => 'Primary', 'slug' => 'primary', 'color' => '#61a5e8'),
                            array('name' => 'Secondary', 'slug' => 'secondary', 'color' => '#93c5fd'),
                            array('name' => 'Background', 'slug' => 'background', 'color' => '#16181d'),
                            array('name' => 'Text', 'slug' => 'text', 'color' => '#e5e7eb'),
                        ),
                    ),
                ),
                'styles'   => array(
                    'elements' => array(
                        'button' => array(
                            'color'  => array(
                                'text' => '#16181d',
                            ),
                            ':hover' => array(
                                'color' => array(
                                    'text' => '#16181d',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'natural.json' => array(
                '$schema'  => $schema,
                'version'  => 3,
                'title'    => 'ナチュラル',
                'settings' => array(
                    'color' => array(
                        'palette' => array(
                            array('name' => 'Primary', 'slug' => 'primary', 'color' => '#2f6e4e'),
                            array('name' => 'Secondary', 'slug' => 'secondary', 'color' => '#245640'),
                            array('name' => 'Background', 'slug' => 'background', 'color' => '#faf8f3'),
                            array('name' => 'Text', 'slug' => 'text', 'color' => '#2a2620'),
                        ),
                    ),
                ),
            ),
            'vivid.json'   => array(
                '$schema'  => $schema,
                'version'  => 3,
                'title'    => 'ビビッド',
                'settings' => array(
                    'color' => array(
                        'palette' => array(
                            array('name' => 'Primary', 'slug' => 'primary', 'color' => '#7c3aed'),
                            array('name' => 'Secondary', 'slug' => 'secondary', 'color' => '#5b21b6'),
                            array('name' => 'Background', 'slug' => 'background', 'color' => '#ffffff'),
                            array('name' => 'Text', 'slug' => 'text', 'color' => '#1e1b29'),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Custom template part area definitions (slug => label / description /
     * icon / tag). Core provides header, footer, and uncategorized; these
     * extra areas keep the 90 possible parts organized in the Site Editor.
     *
     * @return array<string, array<string, string>>
     */
    private function get_custom_area_definitions()
    {
        return array(
            'sidebar'     => array(
                'label'       => 'サイドバー',
                'description' => 'メインコンテンツの横に表示する補助エリアのパーツです。',
                'icon'        => 'sidebar',
                'area_tag'    => 'aside',
            ),
            'pts-post'    => array(
                'label'       => '投稿パーツ',
                'description' => '投稿・固定ページで使うメタ情報・コメント・著者・関連記事などのパーツです。',
                'icon'        => 'symbol-filled',
                'area_tag'    => 'div',
            ),
            'pts-loop'    => array(
                'label'       => '投稿リスト',
                'description' => '投稿一覧・アーカイブで使うループや見出しのパーツです。',
                'icon'        => 'symbol-filled',
                'area_tag'    => 'div',
            ),
            'pts-section' => array(
                'label'       => '汎用セクション',
                'description' => 'どのページにも挿入できる汎用レイアウトセクションです。',
                'icon'        => 'symbol-filled',
                'area_tag'    => 'section',
            ),
            'pts-lp'      => array(
                'label'       => 'LPセクション',
                'description' => 'ランディングページを構成するセクションです。上から順に並べて使います。',
                'icon'        => 'symbol-filled',
                'area_tag'    => 'section',
            ),
            'pts-product' => array(
                'label'       => '商品LPセクション',
                'description' => 'EC向け商品紹介ページを構成するセクションです。',
                'icon'        => 'symbol-filled',
                'area_tag'    => 'section',
            ),
        );
    }

    /**
     * Theme functions.php: registers the custom template part areas used by
     * the selected parts, and a pattern category named after the theme.
     * Returns '' when neither is needed.
     *
     * @param string $theme_slug Theme slug.
     * @param string $theme_name Theme display name.
     * @param array  $selection  Wizard selection.
     * @return string
     */
    private function get_functions_php($theme_slug, $theme_name, $selection)
    {
        $func_prefix = str_replace('-', '_', sanitize_title($theme_slug));
        $sections    = array();

        $bootstrap_php  = "/**\n * Load Bootstrap so generated parts and templates can use the same utility\n * and component classes out of the box.\n */\n";
        $bootstrap_php .= "function {$func_prefix}_enqueue_assets() {\n";
        $bootstrap_php .= "    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css', array(), '5.3.8');\n";
        $bootstrap_php .= "    wp_enqueue_style('{$theme_slug}-style', get_stylesheet_uri(), array('bootstrap'), wp_get_theme()->get('Version'));\n";
        $bootstrap_php .= "    wp_enqueue_script('bootstrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js', array(), '5.3.8', true);\n";
        $bootstrap_php .= PTS_Animejs::get_enqueue_script_line();
        $bootstrap_php .= PTS_Animejs::get_enqueue_init_line($theme_slug);
        if (! empty($selection['parts.heroCrossfade'])) {
            $bootstrap_php .= "    wp_enqueue_script('{$theme_slug}-hero-crossfade', get_template_directory_uri() . '/assets/js/pts-hero-crossfade.js', array(), wp_get_theme()->get('Version'), true);\n";
        }
        $bootstrap_php .= "}\n";
        $bootstrap_php .= "add_action('wp_enqueue_scripts', '{$func_prefix}_enqueue_assets');\n";

        $sections[] = $bootstrap_php;
        $sections[] = $this->get_copyright_shortcode_php($func_prefix);

        // Custom template part areas (only when parts are generated).
        if (! empty($selection['features.generateParts'])) {
            $definitions = $this->get_custom_area_definitions();

            $used = array();
            foreach ($this->get_template_parts_meta($selection) as $part) {
                if (isset($definitions[ $part['area'] ])) {
                    $used[ $part['area'] ] = $definitions[ $part['area'] ];
                }
            }

            if (! empty($used)) {
                $php  = "/**\n * Register custom template part areas so parts stay grouped by purpose\n * in the Site Editor (Appearance > Editor > Patterns).\n */\n";
                $php .= "function {$func_prefix}_template_part_areas(array \$areas) {\n";

                foreach ($used as $slug => $def) {
                    $php .= "    \$areas[] = array(\n";
                    $php .= "        'area'        => '" . $slug . "',\n";
                    $php .= "        'label'       => '" . $def['label'] . "',\n";
                    $php .= "        'description' => '" . $def['description'] . "',\n";
                    $php .= "        'icon'        => '" . $def['icon'] . "',\n";
                    $php .= "        'area_tag'    => '" . $def['area_tag'] . "',\n";
                    $php .= "    );\n";
                }

                $php .= "    return \$areas;\n";
                $php .= "}\n";
                $php .= "add_filter('default_wp_template_part_areas', '{$func_prefix}_template_part_areas');\n";

                $sections[] = $php;
            }
        }

        // Pattern category named after the theme (only when patterns exist).
        $has_patterns = false;
        foreach ($selection as $key => $value) {
            if (0 === strpos($key, 'patterns.') && ! empty($value)) {
                $has_patterns = true;
                break;
            }
        }

        if ($has_patterns) {
            $label = str_replace(array('\\', "'"), array('\\\\', "\\'"), $theme_name);

            $php  = "/**\n * Register the theme's block pattern category for the inserter.\n */\n";
            $php .= "function {$func_prefix}_pattern_categories() {\n";
            $php .= "    register_block_pattern_category(\n";
            $php .= "        '" . $theme_slug . "',\n";
            $php .= "        array('label' => '" . $label . "')\n";
            $php .= "    );\n";
            $php .= "}\n";
            $php .= "add_action('init', '{$func_prefix}_pattern_categories');\n";

            $sections[] = $php;
        }

        if (! empty($selection['features.generateTemplates'])) {
            $sections[] = $this->get_dedupe_featured_image_php($func_prefix);
        }

        $has_header_nav = false;
        if (! empty($selection['features.generateParts'])) {
            foreach ($this->get_template_parts_meta($selection) as $part) {
                if (($part['area'] ?? '') === 'header') {
                    $has_header_nav = true;
                    break;
                }
            }
        }
        if ($has_header_nav) {
            $sections[] = $this->get_grouped_header_navigation_php($func_prefix);
        }

        if (empty($sections)) {
            return '';
        }

        $header  = "<?php\n";
        $header .= "/**\n * Theme functions.\n */\n\n";
        $header .= "if (! defined('ABSPATH')) {\n    exit;\n}\n\n";

        return $header . implode("\n", $sections);
    }

    /**
     * PHP snippet: dynamic footer copyright shortcode.
     *
     * @param string $func_prefix Theme function prefix.
     * @return string
     */
    private function get_copyright_shortcode_php($func_prefix)
    {
        $php  = "/**\n * Default footer copyright notice.\n */\n";
        $php .= "function {$func_prefix}_copyright_shortcode() {\n";
        $php .= "    \$site_name = sprintf(\n";
        $php .= "        '<a class=\"pts-copyright-site-name text-body-secondary text-decoration-none\" href=\"%1\$s\">%2\$s</a>',\n";
        $php .= "        esc_url(home_url('/')),\n";
        $php .= "        esc_html(get_bloginfo('name'))\n";
        $php .= "    );\n";
        $php .= "    return sprintf(\n";
        $php .= "        '&copy; %1\$s %2\$s All rights reserved.',\n";
        $php .= "        esc_html(gmdate('Y')),\n";
        $php .= "        \$site_name\n";
        $php .= "    );\n";
        $php .= "}\n";
        $php .= "add_shortcode('pts_copyright', '{$func_prefix}_copyright_shortcode');\n";

        return $php;
    }

    /**
     * Block markup for the default copyright shortcode.
     *
     * @param string $align Optional block alignment (center, left, etc.).
     * @return string
     */
    private function get_footer_copyright_markup($align = '')
    {
        if ($align !== '') {
            return "<!-- wp:shortcode {\"align\":\"{$align}\"} -->\n[pts_copyright]\n<!-- /wp:shortcode -->";
        }

        return "<!-- wp:shortcode -->\n[pts_copyright]\n<!-- /wp:shortcode -->";
    }

    /**
     * PHP snippet: hide template featured image when the same attachment is already in post content.
     *
     * @param string $func_prefix Theme function prefix.
     * @return string
     */
    private function get_dedupe_featured_image_php($func_prefix)
    {
        $php  = "/**\n * Avoid duplicate hero images on single posts.\n *\n * single.html renders core/post-featured-image, but many posts also place\n * the same attachment as the first core/image block in post content.\n */\n";
        $php .= "function {$func_prefix}_dedupe_post_featured_image(\$block_content, \$block) {\n";
        $php .= "    if ((\$block['blockName'] ?? '') !== 'core/post-featured-image' || ! is_singular('post')) {\n";
        $php .= "        return \$block_content;\n";
        $php .= "    }\n\n";
        $php .= "    \$post = get_post();\n";
        $php .= "    if (! \$post || ! has_post_thumbnail(\$post) || ! has_blocks(\$post->post_content)) {\n";
        $php .= "        return \$block_content;\n";
        $php .= "    }\n\n";
        $php .= "    \$thumb_id = (int) get_post_thumbnail_id(\$post);\n";
        $php .= "    if (! \$thumb_id) {\n";
        $php .= "        return \$block_content;\n";
        $php .= "    }\n\n";
        $php .= "    foreach (parse_blocks(\$post->post_content) as \$parsed_block) {\n";
        $php .= "        if ((\$parsed_block['blockName'] ?? '') !== 'core/image') {\n";
        $php .= "            continue;\n";
        $php .= "        }\n\n";
        $php .= "        if ((int) (\$parsed_block['attrs']['id'] ?? 0) === \$thumb_id) {\n";
        $php .= "            return '';\n";
        $php .= "        }\n";
        $php .= "    }\n\n";
        $php .= "    return \$block_content;\n";
        $php .= "}\n";
        $php .= "add_filter('render_block', '{$func_prefix}_dedupe_post_featured_image', 10, 2);\n";

        return $php;
    }

    /**
     * PHP snippet: group site pages into four header navigation items (wp_navigation).
     *
     * @param string $func_prefix Theme function prefix.
     * @return string
     */
    private function get_grouped_header_navigation_php($func_prefix)
    {
        $php  = "if (! defined('{$func_prefix}_HEADER_NAV_VERSION')) {\n";
        $php .= "    define('{$func_prefix}_HEADER_NAV_VERSION', '1');\n";
        $php .= "}\n\n";

        $php .= "/**\n * Build navigation blocks from pages, grouped into four top-level items.\n */\n";
        $php .= "function {$func_prefix}_finalize_navigation_block(array \$block) {\n";
        $php .= "    if (! empty(\$block['innerBlocks'])) {\n";
        $php .= "        \$block['innerContent'] = array_map('serialize_block', \$block['innerBlocks']);\n";
        $php .= "    } else {\n";
        $php .= "        \$block['innerContent'] = array();\n";
        $php .= "    }\n\n";
        $php .= "    return \$block;\n";
        $php .= "}\n\n";

        $php .= "function {$func_prefix}_navigation_link_block(\$page) {\n";
        $php .= "    return {$func_prefix}_finalize_navigation_block(array(\n";
        $php .= "        'blockName'   => 'core/navigation-link',\n";
        $php .= "        'attrs'       => array(\n";
        $php .= "            'label' => get_the_title(\$page),\n";
        $php .= "            'url'   => get_permalink(\$page),\n";
        $php .= "            'kind'  => 'post-type',\n";
        $php .= "            'type'  => 'page',\n";
        $php .= "            'id'    => (int) \$page->ID,\n";
        $php .= "        ),\n";
        $php .= "        'innerBlocks' => array(),\n";
        $php .= "    ));\n";
        $php .= "}\n\n";

        $php .= "function {$func_prefix}_navigation_submenu_block(\$page, array \$children_of) {\n";
        $php .= "    \$inner = array();\n";
        $php .= "    foreach (\$children_of[ \$page->ID ] ?? array() as \$child) {\n";
        $php .= "        if (! empty(\$children_of[ \$child->ID ])) {\n";
        $php .= "            \$inner[] = {$func_prefix}_navigation_submenu_block(\$child, \$children_of);\n";
        $php .= "        } else {\n";
        $php .= "            \$inner[] = {$func_prefix}_navigation_link_block(\$child);\n";
        $php .= "        }\n";
        $php .= "    }\n\n";
        $php .= "    return {$func_prefix}_finalize_navigation_block(array(\n";
        $php .= "        'blockName'   => 'core/navigation-submenu',\n";
        $php .= "        'attrs'       => array(\n";
        $php .= "            'label' => get_the_title(\$page),\n";
        $php .= "            'url'   => get_permalink(\$page),\n";
        $php .= "            'kind'  => 'post-type',\n";
        $php .= "            'type'  => 'page',\n";
        $php .= "            'id'    => (int) \$page->ID,\n";
        $php .= "        ),\n";
        $php .= "        'innerBlocks' => \$inner,\n";
        $php .= "    ));\n";
        $php .= "}\n\n";

        $php .= "function {$func_prefix}_navigation_orphan_submenu(\$label, array \$pages) {\n";
        $php .= "    \$inner = array();\n";
        $php .= "    foreach (\$pages as \$page) {\n";
        $php .= "        \$inner[] = {$func_prefix}_navigation_link_block(\$page);\n";
        $php .= "    }\n\n";
        $php .= "    \$url = ! empty(\$pages) ? get_permalink(\$pages[0]) : home_url('/');\n\n";
        $php .= "    return {$func_prefix}_finalize_navigation_block(array(\n";
        $php .= "        'blockName'   => 'core/navigation-submenu',\n";
        $php .= "        'attrs'       => array(\n";
        $php .= "            'label' => \$label,\n";
        $php .= "            'url'   => \$url,\n";
        $php .= "            'kind'  => 'custom',\n";
        $php .= "        ),\n";
        $php .= "        'innerBlocks' => \$inner,\n";
        $php .= "    ));\n";
        $php .= "}\n\n";

        $php .= "function {$func_prefix}_build_grouped_header_navigation_blocks(\$max_top = 4) {\n";
        $php .= "    \$pages = get_pages(array(\n";
        $php .= "        'sort_column' => 'menu_order,post_title',\n";
        $php .= "        'post_status' => 'publish',\n";
        $php .= "    ));\n\n";
        $php .= "    if (empty(\$pages)) {\n";
        $php .= "        return array();\n";
        $php .= "    }\n\n";
        $php .= "    \$children_of = array();\n";
        $php .= "    \$top_level     = array();\n\n";
        $php .= "    foreach (\$pages as \$page) {\n";
        $php .= "        \$parent = (int) \$page->post_parent;\n";
        $php .= "        if (0 === \$parent) {\n";
        $php .= "            \$top_level[] = \$page;\n";
        $php .= "        } else {\n";
        $php .= "            \$children_of[ \$parent ][] = \$page;\n";
        $php .= "        }\n";
        $php .= "    }\n\n";
        $php .= "    \$blocks           = array();\n";
        $php .= "    \$with_children    = array();\n";
        $php .= "    \$without_children = array();\n\n";
        $php .= "    foreach (\$top_level as \$page) {\n";
        $php .= "        if (! empty(\$children_of[ \$page->ID ])) {\n";
        $php .= "            \$with_children[] = \$page;\n";
        $php .= "        } else {\n";
        $php .= "            \$without_children[] = \$page;\n";
        $php .= "        }\n";
        $php .= "    }\n\n";
        $php .= "    foreach (\$with_children as \$page) {\n";
        $php .= "        if (count(\$blocks) >= \$max_top) {\n";
        $php .= "            break;\n";
        $php .= "        }\n";
        $php .= "        \$blocks[] = {$func_prefix}_navigation_submenu_block(\$page, \$children_of);\n";
        $php .= "    }\n\n";
        $php .= "    \$remaining = \$max_top - count(\$blocks);\n";
        $php .= "    if (\$remaining <= 0 || empty(\$without_children)) {\n";
        $php .= "        return array_slice(\$blocks, 0, \$max_top);\n";
        $php .= "    }\n\n";
        $php .= "    if (count(\$without_children) <= \$remaining) {\n";
        $php .= "        foreach (\$without_children as \$page) {\n";
        $php .= "            \$blocks[] = {$func_prefix}_navigation_link_block(\$page);\n";
        $php .= "        }\n";
        $php .= "        return array_slice(\$blocks, 0, \$max_top);\n";
        $php .= "    }\n\n";
        $php .= "    \$chunk_size = (int) ceil(count(\$without_children) / \$remaining);\n";
        $php .= "    \$chunks     = array_chunk(\$without_children, max(1, \$chunk_size));\n";
        $php .= "    \$labels     = array('Pages', 'More');\n\n";
        $php .= "    foreach (\$chunks as \$index => \$chunk) {\n";
        $php .= "        if (count(\$blocks) >= \$max_top || empty(\$chunk)) {\n";
        $php .= "            break;\n";
        $php .= "        }\n";
        $php .= "        if (1 === count(\$chunk)) {\n";
        $php .= "            \$blocks[] = {$func_prefix}_navigation_link_block(\$chunk[0]);\n";
        $php .= "            continue;\n";
        $php .= "        }\n";
        $php .= "        \$label    = \$labels[ \$index ] ?? 'More';\n";
        $php .= "        \$blocks[] = {$func_prefix}_navigation_orphan_submenu(\$label, \$chunk);\n";
        $php .= "    }\n\n";
        $php .= "    return array_slice(\$blocks, 0, \$max_top);\n";
        $php .= "}\n\n";

        $php .= "function {$func_prefix}_navigation_needs_regroup(\$content) {\n";
        $php .= "    if (! is_string(\$content) || \$content === '') {\n";
        $php .= "        return true;\n";
        $php .= "    }\n";
        $php .= "    if (strpos(\$content, 'page-list') !== false) {\n";
        $php .= "        return true;\n";
        $php .= "    }\n";
        $php .= "    if (get_theme_mod('pts_header_navigation_version') !== {$func_prefix}_HEADER_NAV_VERSION) {\n";
        $php .= "        return true;\n";
        $php .= "    }\n\n";
        $php .= "    \$blocks = array_values(array_filter(parse_blocks(\$content), static function (\$block) {\n";
        $php .= "        return ! empty(\$block['blockName']);\n";
        $php .= "    }));\n\n";
        $php .= "    return count(\$blocks) !== 4;\n";
        $php .= "}\n\n";

        $php .= "function {$func_prefix}_setup_grouped_header_navigation(\$force = false) {\n";
        $php .= "    \$blocks = {$func_prefix}_build_grouped_header_navigation_blocks();\n";
        $php .= "    if (empty(\$blocks)) {\n";
        $php .= "        return;\n";
        $php .= "    }\n\n";
        $php .= "    \$content = serialize_blocks(\$blocks);\n";
        $php .= "    \$nav_id  = (int) get_theme_mod('pts_header_navigation_id');\n\n";
        $php .= "    if (! \$force && \$nav_id) {\n";
        $php .= "        \$post = get_post(\$nav_id);\n";
        $php .= "        if (\$post && 'wp_navigation' === \$post->post_type\n";
        $php .= "            && ! {$func_prefix}_navigation_needs_regroup(\$post->post_content)) {\n";
        $php .= "            return;\n";
        $php .= "        }\n";
        $php .= "    }\n\n";
        $php .= "    if (\$nav_id) {\n";
        $php .= "        \$post = get_post(\$nav_id);\n";
        $php .= "        if (\$post && 'wp_navigation' === \$post->post_type) {\n";
        $php .= "            wp_update_post(array(\n";
        $php .= "                'ID'           => \$nav_id,\n";
        $php .= "                'post_content' => \$content,\n";
        $php .= "            ));\n";
        $php .= "            set_theme_mod('pts_header_navigation_version', {$func_prefix}_HEADER_NAV_VERSION);\n";
        $php .= "            return;\n";
        $php .= "        }\n";
        $php .= "    }\n\n";
        $php .= "    \$existing = get_posts(array(\n";
        $php .= "        'post_type'      => 'wp_navigation',\n";
        $php .= "        'post_status'    => 'publish',\n";
        $php .= "        'posts_per_page' => 1,\n";
        $php .= "        'orderby'        => 'date',\n";
        $php .= "        'order'          => 'DESC',\n";
        $php .= "    ));\n\n";
        $php .= "    if (! empty(\$existing)) {\n";
        $php .= "        \$nav_id = (int) \$existing[0]->ID;\n";
        $php .= "        wp_update_post(array(\n";
        $php .= "            'ID'           => \$nav_id,\n";
        $php .= "            'post_content' => \$content,\n";
        $php .= "        ));\n";
        $php .= "    } else {\n";
        $php .= "        \$nav_id = wp_insert_post(array(\n";
        $php .= "            'post_type'    => 'wp_navigation',\n";
        $php .= "            'post_title'   => _x('Navigation', 'Title of a Navigation menu'),\n";
        $php .= "            'post_name'    => 'navigation',\n";
        $php .= "            'post_status'  => 'publish',\n";
        $php .= "            'post_content' => \$content,\n";
        $php .= "        ), true);\n";
        $php .= "        if (is_wp_error(\$nav_id)) {\n";
        $php .= "            return;\n";
        $php .= "        }\n";
        $php .= "    }\n\n";
        $php .= "    set_theme_mod('pts_header_navigation_id', (int) \$nav_id);\n";
        $php .= "    set_theme_mod('pts_header_navigation_version', {$func_prefix}_HEADER_NAV_VERSION);\n";
        $php .= "}\n\n";

        $php .= "function {$func_prefix}_reset_grouped_header_navigation() {\n";
        $php .= "    remove_theme_mod('pts_header_navigation_version');\n";
        $php .= "    {$func_prefix}_setup_grouped_header_navigation(true);\n";
        $php .= "}\n";
        $php .= "add_action('after_switch_theme', '{$func_prefix}_reset_grouped_header_navigation');\n";
        $php .= "add_action('after_setup_theme', '{$func_prefix}_setup_grouped_header_navigation', 20);\n\n";

        $php .= "function {$func_prefix}_apply_header_navigation_ref_to_blocks(array \$blocks, \$nav_id, &\$changed) {\n";
        $php .= "    foreach (\$blocks as &\$block) {\n";
        $php .= "        if ((\$block['blockName'] ?? '') === 'core/navigation'\n";
        $php .= "            && strpos(\$block['attrs']['className'] ?? '', 'pts-header-nav') !== false) {\n";
        $php .= "            \$block['attrs']['ref'] = \$nav_id;\n";
        $php .= "            \$block['innerBlocks']  = array();\n";
        $php .= "            \$block['innerHTML']    = '';\n";
        $php .= "            \$block['innerContent'] = array();\n";
        $php .= "            \$changed               = true;\n";
        $php .= "        }\n\n";
        $php .= "        if (! empty(\$block['innerBlocks'])) {\n";
        $php .= "            \$block['innerBlocks'] = {$func_prefix}_apply_header_navigation_ref_to_blocks(\$block['innerBlocks'], \$nav_id, \$changed);\n";
        $php .= "        }\n";
        $php .= "    }\n\n";
        $php .= "    return \$blocks;\n";
        $php .= "}\n\n";

        $php .= "function {$func_prefix}_inject_header_navigation_ref(\$template, \$id, \$template_type) {\n";
        $php .= "    if ('wp_template_part' !== \$template_type || ! \$template) {\n";
        $php .= "        return \$template;\n";
        $php .= "    }\n\n";
        $php .= "    if (! in_array(\$template->slug, array('header', 'header-centered', 'header-with-button'), true)) {\n";
        $php .= "        return \$template;\n";
        $php .= "    }\n\n";
        $php .= "    \$nav_id = (int) get_theme_mod('pts_header_navigation_id');\n";
        $php .= "    if (! \$nav_id) {\n";
        $php .= "        return \$template;\n";
        $php .= "    }\n\n";
        $php .= "    \$changed = false;\n";
        $php .= "    \$blocks  = {$func_prefix}_apply_header_navigation_ref_to_blocks(parse_blocks(\$template->content), \$nav_id, \$changed);\n\n";
        $php .= "    if (\$changed) {\n";
        $php .= "        \$template->content = serialize_blocks(\$blocks);\n";
        $php .= "    }\n\n";
        $php .= "    return \$template;\n";
        $php .= "}\n";
        $php .= "add_filter('get_block_template', '{$func_prefix}_inject_header_navigation_ref', 10, 3);\n";

        return $php;
    }

    /**
     * Full-width hero cover block with a background image and centered content.
     *
     * @param string $indent Optional leading whitespace for each line (template embedding).
     * @return string
     */
    private function get_hero_full_width_markup($indent = '')
    {
        $markup = <<<'HTML'
<!-- wp:cover {"dimRatio":40,"minHeight":60,"minHeightUnit":"vh","align":"full","className":"pts-hero-full-width pts-animate-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull pts-hero-full-width pts-animate-hero" style="min-height:60vh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container">
    <!-- wp:heading {"textAlign":"center","level":1,"className":"pts-animate-hero__item display-5 fw-bold text-white"} -->
    <h1 class="wp-block-heading has-text-align-center pts-animate-hero__item display-5 fw-bold text-white">A clear headline for your site</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","className":"pts-animate-hero__item lead mb-4 text-white"} -->
    <p class="has-text-align-center pts-animate-hero__item lead mb-4 text-white">Introduce your site in one or two sentences. Replace the cover background image from the block toolbar.</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"className":"pts-animate-hero__item"} -->
    <div class="wp-block-buttons pts-animate-hero__item">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button btn btn-light btn-lg px-4">Get started</a></div>
        <!-- /wp:button -->

        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button btn btn-outline-light btn-lg px-4">Learn more</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->
HTML;

        if ($indent === '') {
            return $markup;
        }

        return preg_replace('/^/m', $indent, $markup);
    }

    /**
     * Front-end script for the crossfade hero template part.
     *
     * @return string
     */
    private function get_hero_crossfade_js()
    {
        return <<<'JS'
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    document.querySelectorAll('.pts-hero-crossfade').forEach(function (hero) {
        var slides = hero.querySelectorAll('.pts-hero-crossfade__slide');
        if (slides.length < 2) {
            return;
        }

        var interval = parseInt(hero.getAttribute('data-interval') || '6000', 10);
        if (! interval || interval < 1000) {
            interval = 6000;
        }

        var index = 0;
        for (var i = 0; i < slides.length; i++) {
            if (slides[i].classList.contains('is-active')) {
                index = i;
                break;
            }
        }

        setInterval(function () {
            slides[index].classList.remove('is-active');
            index = (index + 1) % slides.length;
            slides[index].classList.add('is-active');
        }, interval);
    });
})();
JS;
    }

    /**
     * theme.json templateParts entries for the selected parts.
     *
     * @param array $selection Wizard selection.
     * @return array<int, array<string, string>>
     */
    private function get_template_parts_meta($selection)
    {
        $meta = array(
            'parts.header'           => array('name' => 'header', 'title' => 'Header', 'area' => 'header'),
            'parts.headerCentered'   => array('name' => 'header-centered', 'title' => 'Header (Centered)', 'area' => 'header'),
            'parts.headerWithButton' => array('name' => 'header-with-button', 'title' => 'Header (With Button)', 'area' => 'header'),
            'parts.footer'           => array('name' => 'footer', 'title' => 'Footer', 'area' => 'footer'),
            'parts.footerColumns'    => array('name' => 'footer-columns', 'title' => 'Footer (Columns)', 'area' => 'footer'),
            'parts.sidebar'          => array('name' => 'sidebar', 'title' => 'Sidebar', 'area' => 'sidebar'),
            'parts.comments'         => array('name' => 'comments', 'title' => 'Comments', 'area' => 'pts-post'),
            'parts.postMeta'         => array('name' => 'post-meta', 'title' => 'Post Meta', 'area' => 'pts-post'),
            'parts.postNavigation'   => array('name' => 'post-navigation', 'title' => 'Post Navigation', 'area' => 'pts-post'),
            'parts.loop'             => array('name' => 'loop', 'title' => 'Post Loop', 'area' => 'pts-loop'),
            'parts.cta'              => array('name' => 'cta', 'title' => 'Call to Action', 'area' => 'pts-section'),
            'parts.heroFullWidth'    => array('name' => 'hero-full-width', 'title' => 'Hero (Full-width Image)', 'area' => 'pts-section'),
            'parts.heroEdge'         => array('name' => 'hero-edge', 'title' => 'Hero (Edge to Edge)', 'area' => 'pts-section'),
            'parts.heroCrossfade'    => array('name' => 'hero-crossfade', 'title' => 'Hero (Crossfade Background)', 'area' => 'pts-section'),
            'parts.categoryFeaturedList' => array('name' => 'category-featured-list', 'title' => 'Category Featured + 3 Links', 'area' => 'pts-section'),
            'parts.selectedPostPage' => array('name' => 'selected-post-page', 'title' => 'Selected Post + Page', 'area' => 'pts-section'),
            // Extended layout parts.
            'parts.headerMinimal'    => array('name' => 'header-minimal', 'title' => 'Header (Minimal)', 'area' => 'header'),
            'parts.headerWithSearch' => array('name' => 'header-with-search', 'title' => 'Header (With Search)', 'area' => 'header'),
            'parts.headerWithSocial' => array('name' => 'header-with-social', 'title' => 'Header (With Social)', 'area' => 'header'),
            'parts.headerTagline'    => array('name' => 'header-tagline', 'title' => 'Header (With Tagline)', 'area' => 'header'),
            'parts.headerSplit'      => array('name' => 'header-split', 'title' => 'Header (Split)', 'area' => 'header'),
            'parts.footerMinimal'    => array('name' => 'footer-minimal', 'title' => 'Footer (Minimal)', 'area' => 'footer'),
            'parts.footerMenu'       => array('name' => 'footer-menu', 'title' => 'Footer (With Menu)', 'area' => 'footer'),
            'parts.footerSocial'     => array('name' => 'footer-social', 'title' => 'Footer (Social)', 'area' => 'footer'),
            'parts.footerMega'       => array('name' => 'footer-mega', 'title' => 'Footer (Mega)', 'area' => 'footer'),
            'parts.footerCta'        => array('name' => 'footer-cta', 'title' => 'Footer (CTA)', 'area' => 'footer'),
            'parts.sidebarBlog'      => array('name' => 'sidebar-blog', 'title' => 'Sidebar (Blog)', 'area' => 'sidebar'),
            'parts.sidebarCta'       => array('name' => 'sidebar-cta', 'title' => 'Sidebar (CTA)', 'area' => 'sidebar'),
            'parts.loopGrid'         => array('name' => 'loop-grid', 'title' => 'Post Loop (Grid)', 'area' => 'pts-loop'),
            'parts.loopCompact'      => array('name' => 'loop-compact', 'title' => 'Post Loop (Compact)', 'area' => 'pts-loop'),
            'parts.archiveHeader'    => array('name' => 'archive-header', 'title' => 'Archive Header', 'area' => 'pts-loop'),
            'parts.pageHeader'       => array('name' => 'page-header', 'title' => 'Page Header', 'area' => 'pts-post'),
            'parts.authorBox'        => array('name' => 'author-box', 'title' => 'Author Box', 'area' => 'pts-post'),
            'parts.relatedPosts'     => array('name' => 'related-posts', 'title' => 'Related Posts', 'area' => 'pts-post'),
            'parts.socialLinks'      => array('name' => 'social-links', 'title' => 'Social Links', 'area' => 'pts-section'),
            // Japanese LP parts.
            'parts.lpHero'           => array('name' => 'lp-hero', 'title' => 'LP ファーストビュー', 'area' => 'pts-lp'),
            'parts.lpProblems'       => array('name' => 'lp-problems', 'title' => 'LP お悩みチェック', 'area' => 'pts-lp'),
            'parts.lpEmpathy'        => array('name' => 'lp-empathy', 'title' => 'LP 共感・問題提起', 'area' => 'pts-lp'),
            'parts.lpSolution'       => array('name' => 'lp-solution', 'title' => 'LP 解決策', 'area' => 'pts-lp'),
            'parts.lpReasons'        => array('name' => 'lp-reasons', 'title' => 'LP 選ばれる理由', 'area' => 'pts-lp'),
            'parts.lpAchievements'   => array('name' => 'lp-achievements', 'title' => 'LP 実績数値', 'area' => 'pts-lp'),
            'parts.lpVoice'          => array('name' => 'lp-voice', 'title' => 'LP お客様の声', 'area' => 'pts-lp'),
            'parts.lpBeforeAfter'    => array('name' => 'lp-before-after', 'title' => 'LP ビフォーアフター', 'area' => 'pts-lp'),
            'parts.lpPricing'        => array('name' => 'lp-pricing', 'title' => 'LP 料金プラン', 'area' => 'pts-lp'),
            'parts.lpComparison'     => array('name' => 'lp-comparison', 'title' => 'LP 比較表', 'area' => 'pts-lp'),
            'parts.lpFlow'           => array('name' => 'lp-flow', 'title' => 'LP ご利用の流れ', 'area' => 'pts-lp'),
            'parts.lpFaq'            => array('name' => 'lp-faq', 'title' => 'LP よくある質問', 'area' => 'pts-lp'),
            'parts.lpCampaign'       => array('name' => 'lp-campaign', 'title' => 'LP キャンペーン', 'area' => 'pts-lp'),
            'parts.lpBenefits'       => array('name' => 'lp-benefits', 'title' => 'LP 特典', 'area' => 'pts-lp'),
            'parts.lpGuarantee'      => array('name' => 'lp-guarantee', 'title' => 'LP 保証', 'area' => 'pts-lp'),
            'parts.lpMessage'        => array('name' => 'lp-message', 'title' => 'LP 代表メッセージ', 'area' => 'pts-lp'),
            'parts.lpCompany'        => array('name' => 'lp-company', 'title' => 'LP 会社概要', 'area' => 'pts-lp'),
            'parts.lpAccess'         => array('name' => 'lp-access', 'title' => 'LP アクセス', 'area' => 'pts-lp'),
            'parts.lpNews'           => array('name' => 'lp-news', 'title' => 'LP お知らせ', 'area' => 'pts-lp'),
            'parts.lpCta'            => array('name' => 'lp-cta', 'title' => 'LP CVエリア', 'area' => 'pts-lp'),
            // Product LP parts (EC / Rakuten-style).
            'parts.productHero'       => array('name' => 'product-hero', 'title' => '商品LP ファーストビュー', 'area' => 'pts-product'),
            'parts.productBadges'     => array('name' => 'product-badges', 'title' => '商品LP 安心バッジ', 'area' => 'pts-product'),
            'parts.productRanking'    => array('name' => 'product-ranking', 'title' => '商品LP ランキング受賞', 'area' => 'pts-product'),
            'parts.productMedia'      => array('name' => 'product-media', 'title' => '商品LP メディア掲載', 'area' => 'pts-product'),
            'parts.productSale'       => array('name' => 'product-sale', 'title' => '商品LP タイムセール', 'area' => 'pts-product'),
            'parts.productCoupon'     => array('name' => 'product-coupon', 'title' => '商品LP クーポン', 'area' => 'pts-product'),
            'parts.productPrice'      => array('name' => 'product-price', 'title' => '商品LP 価格訴求', 'area' => 'pts-product'),
            'parts.productFeatures'   => array('name' => 'product-features', 'title' => '商品LP こだわりポイント', 'area' => 'pts-product'),
            'parts.productUsage'      => array('name' => 'product-usage', 'title' => '商品LP 使い方ステップ', 'area' => 'pts-product'),
            'parts.productScenes'     => array('name' => 'product-scenes', 'title' => '商品LP おすすめシーン', 'area' => 'pts-product'),
            'parts.productVariations' => array('name' => 'product-variations', 'title' => '商品LP バリエーション', 'area' => 'pts-product'),
            'parts.productSet'        => array('name' => 'product-set', 'title' => '商品LP セット内容', 'area' => 'pts-product'),
            'parts.productSpec'       => array('name' => 'product-spec', 'title' => '商品LP 商品仕様', 'area' => 'pts-product'),
            'parts.productReviews'    => array('name' => 'product-reviews', 'title' => '商品LP レビュー', 'area' => 'pts-product'),
            'parts.productStory'      => array('name' => 'product-story', 'title' => '商品LP 開発ストーリー', 'area' => 'pts-product'),
            'parts.productGift'       => array('name' => 'product-gift', 'title' => '商品LP ギフト対応', 'area' => 'pts-product'),
            'parts.productShipping'   => array('name' => 'product-shipping', 'title' => '商品LP 配送・送料', 'area' => 'pts-product'),
            'parts.productReturns'    => array('name' => 'product-returns', 'title' => '商品LP 返品・交換', 'area' => 'pts-product'),
            'parts.productNotes'      => array('name' => 'product-notes', 'title' => '商品LP 注意事項', 'area' => 'pts-product'),
            'parts.productCart'       => array('name' => 'product-cart', 'title' => '商品LP 購入エリア', 'area' => 'pts-product'),
            // Common layout kit parts.
            'parts.layoutSectionHeader' => array('name' => 'layout-section-header', 'title' => 'レイアウト セクション見出し', 'area' => 'pts-section'),
            'parts.layoutTextImage'     => array('name' => 'layout-text-image', 'title' => 'レイアウト テキスト＋画像', 'area' => 'pts-section'),
            'parts.layoutImageText'     => array('name' => 'layout-image-text', 'title' => 'レイアウト 画像＋テキスト', 'area' => 'pts-section'),
            'parts.layoutTwoColumnText' => array('name' => 'layout-two-column-text', 'title' => 'レイアウト 2カラム本文', 'area' => 'pts-section'),
            'parts.layoutThreeCards'    => array('name' => 'layout-three-cards', 'title' => 'レイアウト 3カラムカード', 'area' => 'pts-section'),
            'parts.layoutFourCards'     => array('name' => 'layout-four-cards', 'title' => 'レイアウト 4カラムカード', 'area' => 'pts-section'),
            'parts.layoutFeatureList'   => array('name' => 'layout-feature-list', 'title' => 'レイアウト 特徴リスト', 'area' => 'pts-section'),
            'parts.layoutGallery'       => array('name' => 'layout-gallery', 'title' => 'レイアウト ギャラリー', 'area' => 'pts-section'),
            'parts.layoutFullImage'     => array('name' => 'layout-full-image', 'title' => 'レイアウト 全幅画像', 'area' => 'pts-section'),
            'parts.layoutTimeline'      => array('name' => 'layout-timeline', 'title' => 'レイアウト 沿革・年表', 'area' => 'pts-section'),
            'parts.layoutTeam'          => array('name' => 'layout-team', 'title' => 'レイアウト チーム紹介', 'area' => 'pts-section'),
            'parts.layoutLogos'         => array('name' => 'layout-logos', 'title' => 'レイアウト ロゴ一覧', 'area' => 'pts-section'),
            'parts.layoutStats'         => array('name' => 'layout-stats', 'title' => 'レイアウト 数値ハイライト', 'area' => 'pts-section'),
            'parts.layoutQuote'         => array('name' => 'layout-quote', 'title' => 'レイアウト 引用', 'area' => 'pts-section'),
            'parts.layoutBannerCta'     => array('name' => 'layout-banner-cta', 'title' => 'レイアウト 帯バナーCTA', 'area' => 'pts-section'),
            'parts.layoutContact'       => array('name' => 'layout-contact', 'title' => 'レイアウト お問い合わせ案内', 'area' => 'pts-section'),
            'parts.layoutSteps'         => array('name' => 'layout-steps', 'title' => 'レイアウト ステップ', 'area' => 'pts-section'),
            'parts.layoutTable'         => array('name' => 'layout-table', 'title' => 'レイアウト 表組み', 'area' => 'pts-section'),
            'parts.layoutAccordion'     => array('name' => 'layout-accordion', 'title' => 'レイアウト アコーディオン', 'area' => 'pts-section'),
            'parts.layoutMainAside'     => array('name' => 'layout-main-aside', 'title' => 'レイアウト 本文＋サイドノート', 'area' => 'pts-section'),
        );

        $template_parts = array();
        foreach ($meta as $key => $part) {
            if (! empty($selection[$key])) {
                $template_parts[] = $part;
            }
        }

        return $template_parts;
    }

    /**
     * Optional sidebar part (not part of the basic template parts set).
     *
     * @return array<string, string>
     */
    private function get_sidebar_part_map()
    {
        return array(
            'parts.sidebar' => 'sidebar.html',
        );
    }

    /**
     * Selection key => part file name. Also defines the "Basic Template Parts Set".
     *
     * @return array<string, string>
     */
    private function get_parts_map()
    {
        return array(
            'parts.header'           => 'header.html',
            'parts.headerCentered'   => 'header-centered.html',
            'parts.headerWithButton' => 'header-with-button.html',
            'parts.footer'           => 'footer.html',
            'parts.footerColumns'    => 'footer-columns.html',
            'parts.comments'         => 'comments.html',
            'parts.postMeta'         => 'post-meta.html',
            'parts.postNavigation'   => 'post-navigation.html',
            'parts.loop'             => 'loop.html',
            'parts.cta'              => 'cta.html',
            'parts.heroFullWidth'    => 'hero-full-width.html',
            'parts.heroEdge'         => 'hero-edge.html',
            'parts.heroCrossfade'    => 'hero-crossfade.html',
            'parts.categoryFeaturedList' => 'category-featured-list.html',
            'parts.selectedPostPage' => 'selected-post-page.html',
        );
    }

    /**
     * Selection key => part file name for the "Extended Layout Parts Set":
     * common header / footer / sidebar / loop / section layouts.
     *
     * @return array<string, string>
     */
    private function get_extended_parts_map()
    {
        return array(
            'parts.headerMinimal'    => 'header-minimal.html',
            'parts.headerWithSearch' => 'header-with-search.html',
            'parts.headerWithSocial' => 'header-with-social.html',
            'parts.headerTagline'    => 'header-tagline.html',
            'parts.headerSplit'      => 'header-split.html',
            'parts.footerMinimal'    => 'footer-minimal.html',
            'parts.footerMenu'       => 'footer-menu.html',
            'parts.footerSocial'     => 'footer-social.html',
            'parts.footerMega'       => 'footer-mega.html',
            'parts.footerCta'        => 'footer-cta.html',
            'parts.sidebarBlog'      => 'sidebar-blog.html',
            'parts.sidebarCta'       => 'sidebar-cta.html',
            'parts.loopGrid'         => 'loop-grid.html',
            'parts.loopCompact'      => 'loop-compact.html',
            'parts.archiveHeader'    => 'archive-header.html',
            'parts.pageHeader'       => 'page-header.html',
            'parts.authorBox'        => 'author-box.html',
            'parts.relatedPosts'     => 'related-posts.html',
            'parts.socialLinks'      => 'social-links.html',
        );
    }

    /**
     * Selection key => part file name for the "Japanese LP Parts Set":
     * landing page sections common on Japanese sites (first view, problems,
     * reasons, voices, pricing, FAQ, conversion area, and so on).
     *
     * @return array<string, string>
     */
    private function get_jp_lp_parts_map()
    {
        return array(
            'parts.lpHero'        => 'lp-hero.html',
            'parts.lpProblems'    => 'lp-problems.html',
            'parts.lpEmpathy'     => 'lp-empathy.html',
            'parts.lpSolution'    => 'lp-solution.html',
            'parts.lpReasons'     => 'lp-reasons.html',
            'parts.lpAchievements' => 'lp-achievements.html',
            'parts.lpVoice'       => 'lp-voice.html',
            'parts.lpBeforeAfter' => 'lp-before-after.html',
            'parts.lpPricing'     => 'lp-pricing.html',
            'parts.lpComparison'  => 'lp-comparison.html',
            'parts.lpFlow'        => 'lp-flow.html',
            'parts.lpFaq'         => 'lp-faq.html',
            'parts.lpCampaign'    => 'lp-campaign.html',
            'parts.lpBenefits'    => 'lp-benefits.html',
            'parts.lpGuarantee'   => 'lp-guarantee.html',
            'parts.lpMessage'     => 'lp-message.html',
            'parts.lpCompany'     => 'lp-company.html',
            'parts.lpAccess'      => 'lp-access.html',
            'parts.lpNews'        => 'lp-news.html',
            'parts.lpCta'         => 'lp-cta.html',
        );
    }

    /**
     * Selection key => part file name for the "Product LP Parts Set":
     * EC product page sections common on Japanese shopping sites such as
     * Rakuten (sale banner, double pricing, reviews, specs, shipping, etc.).
     *
     * @return array<string, string>
     */
    private function get_product_lp_parts_map()
    {
        return array(
            'parts.productHero'       => 'product-hero.html',
            'parts.productBadges'     => 'product-badges.html',
            'parts.productRanking'    => 'product-ranking.html',
            'parts.productMedia'      => 'product-media.html',
            'parts.productSale'       => 'product-sale.html',
            'parts.productCoupon'     => 'product-coupon.html',
            'parts.productPrice'      => 'product-price.html',
            'parts.productFeatures'   => 'product-features.html',
            'parts.productUsage'      => 'product-usage.html',
            'parts.productScenes'     => 'product-scenes.html',
            'parts.productVariations' => 'product-variations.html',
            'parts.productSet'        => 'product-set.html',
            'parts.productSpec'       => 'product-spec.html',
            'parts.productReviews'    => 'product-reviews.html',
            'parts.productStory'      => 'product-story.html',
            'parts.productGift'       => 'product-gift.html',
            'parts.productShipping'   => 'product-shipping.html',
            'parts.productReturns'    => 'product-returns.html',
            'parts.productNotes'      => 'product-notes.html',
            'parts.productCart'       => 'product-cart.html',
        );
    }

    /**
     * Selection key => part file name for the "Common Layout Kit":
     * everyday page layouts (text + image, cards, gallery, table, steps...).
     *
     * @return array<string, string>
     */
    private function get_layout_kit_parts_map()
    {
        return array(
            'parts.layoutSectionHeader' => 'layout-section-header.html',
            'parts.layoutTextImage'     => 'layout-text-image.html',
            'parts.layoutImageText'     => 'layout-image-text.html',
            'parts.layoutTwoColumnText' => 'layout-two-column-text.html',
            'parts.layoutThreeCards'    => 'layout-three-cards.html',
            'parts.layoutFourCards'     => 'layout-four-cards.html',
            'parts.layoutFeatureList'   => 'layout-feature-list.html',
            'parts.layoutGallery'       => 'layout-gallery.html',
            'parts.layoutFullImage'     => 'layout-full-image.html',
            'parts.layoutTimeline'      => 'layout-timeline.html',
            'parts.layoutTeam'          => 'layout-team.html',
            'parts.layoutLogos'         => 'layout-logos.html',
            'parts.layoutStats'         => 'layout-stats.html',
            'parts.layoutQuote'         => 'layout-quote.html',
            'parts.layoutBannerCta'     => 'layout-banner-cta.html',
            'parts.layoutContact'       => 'layout-contact.html',
            'parts.layoutSteps'         => 'layout-steps.html',
            'parts.layoutTable'         => 'layout-table.html',
            'parts.layoutAccordion'     => 'layout-accordion.html',
            'parts.layoutMainAside'     => 'layout-main-aside.html',
        );
    }

    private function generate_parts($dir, $selection)
    {
        $map = array_merge(
            $this->get_sidebar_part_map(),
            $this->get_parts_map(),
            $this->get_extended_parts_map(),
            $this->get_jp_lp_parts_map(),
            $this->get_product_lp_parts_map(),
            $this->get_layout_kit_parts_map()
        );

        foreach ($map as $key => $file) {
            if (! empty($selection[$key])) {
                file_put_contents($dir . '/' . $file, $this->get_part_content($file));
            }
        }
    }

    /**
     * Bootstrap navbar toggler for the default header menu.
     *
     * @return string
     */
    private function get_header_navbar_toggler_markup()
    {
        return '<!-- wp:html -->
<button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#pts-primary-nav" aria-controls="pts-primary-nav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
<!-- /wp:html -->';
    }

    /**
     * Header navigation shell; grouped page links live in wp_navigation (Site Editor).
     *
     * @param string $class_name Navigation block class names.
     * @param string $justify    Flex justification for the navigation layout.
     * @return string
     */
    private function get_default_header_navigation_markup($class_name = 'pts-header-nav navbar-nav ms-lg-auto gap-lg-3 mb-2 mb-lg-0', $justify = 'right')
    {
        return '<!-- wp:navigation {"overlayMenu":"never","className":"' . $class_name . '","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"' . $justify . '"}} /-->';
    }

    /**
     * Default header with a Bootstrap collapse menu.
     *
     * @param string $nav_markup Navigation block markup.
     * @return string
     */
    private function get_default_header_shell_markup($nav_markup)
    {
        $toggler = $this->get_header_navbar_toggler_markup();

        return '<!-- wp:group {"className":"site-header bg-white sticky-top","layout":{"type":"default"}} -->
<div class="wp-block-group site-header bg-white sticky-top">
    <!-- wp:group {"className":"container navbar navbar-expand-lg navbar-light py-3 px-0","layout":{"type":"default"}} -->
    <div class="wp-block-group container navbar navbar-expand-lg navbar-light py-3 px-0">
        <!-- wp:site-title {"className":"navbar-brand fw-bold mb-0 me-lg-4"} /-->

        ' . $toggler . '

        <!-- wp:group {"anchor":"pts-primary-nav","className":"collapse navbar-collapse justify-content-lg-end","layout":{"type":"default"}} -->
        <div id="pts-primary-nav" class="wp-block-group collapse navbar-collapse justify-content-lg-end">
            ' . $nav_markup . '
        </div>
        <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';
    }

    /**
     * Block markup for each generated part.
     *
     * @param string $file Part file name.
     * @return string
     */
    private function get_part_content($file)
    {
        switch ($file) {
            case 'header.html':
                if ($this->uses_two_column_layout($this->generation_selection)) {
                    return $this->get_site_title_only_header_markup();
                }

                return $this->get_default_header_shell_markup(
                    $this->get_default_header_navigation_markup()
                );

            case 'header-centered.html':
                $toggler = $this->get_header_navbar_toggler_markup();
                $nav     = $this->get_default_header_navigation_markup(
                    'pts-header-nav navbar-nav mx-lg-auto gap-lg-3 mb-2 mb-lg-0 is-content-justification-center',
                    'center'
                );

                return '<!-- wp:group {"className":"site-header bg-white sticky-top","layout":{"type":"default"}} -->
<div class="wp-block-group site-header bg-white sticky-top">
    <!-- wp:group {"className":"container navbar navbar-expand-lg navbar-light py-4 px-0","layout":{"type":"default"}} -->
    <div class="wp-block-group container navbar navbar-expand-lg navbar-light py-4 px-0">
        ' . $toggler . '

        <!-- wp:group {"anchor":"pts-primary-nav","className":"collapse navbar-collapse","layout":{"type":"default"}} -->
        <div id="pts-primary-nav" class="wp-block-group collapse navbar-collapse">
            <!-- wp:group {"className":"d-grid gap-3 text-center mx-lg-auto","layout":{"type":"constrained"}} -->
            <div class="wp-block-group d-grid gap-3 text-center mx-lg-auto">
                <!-- wp:site-title {"textAlign":"center","className":"fw-bold mb-0"} /-->

                ' . $nav . '
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'header-with-button.html':
                $nav = $this->get_default_header_navigation_markup(
                    'pts-header-nav navbar-nav ms-lg-auto gap-lg-3 mb-2 mb-lg-0'
                );
                $toggler = $this->get_header_navbar_toggler_markup();

                return '<!-- wp:group {"className":"site-header bg-white sticky-top","layout":{"type":"default"}} -->
<div class="wp-block-group site-header bg-white sticky-top">
    <!-- wp:group {"className":"container navbar navbar-expand-lg navbar-light py-3 px-0","layout":{"type":"default"}} -->
    <div class="wp-block-group container navbar navbar-expand-lg navbar-light py-3 px-0">
        <!-- wp:site-title {"className":"navbar-brand fw-bold mb-0 me-lg-4"} /-->

        ' . $toggler . '

        <!-- wp:group {"anchor":"pts-primary-nav","className":"collapse navbar-collapse justify-content-lg-end","layout":{"type":"default"}} -->
        <div id="pts-primary-nav" class="wp-block-group collapse navbar-collapse justify-content-lg-end">
            <!-- wp:group {"className":"d-flex flex-column flex-lg-row align-items-lg-center gap-3 ms-lg-auto","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"right"}} -->
            <div class="wp-block-group d-flex flex-column flex-lg-row align-items-lg-center gap-3 ms-lg-auto">
                ' . $nav . '

                <!-- wp:buttons -->
                <div class="wp-block-buttons">
                    <!-- wp:button -->
                    <div class="wp-block-button"><a class="wp-block-button__link wp-element-button btn btn-primary px-4">Contact</a></div>
                    <!-- /wp:button -->
                </div>
                <!-- /wp:buttons -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'footer.html':
                return '<!-- wp:group {"className":"pts-bootstrap-footer py-4","layout":{"type":"constrained"}} -->
<div class="wp-block-group pts-bootstrap-footer py-4">
    <!-- wp:group {"align":"wide","className":"container text-center small text-body-secondary","layout":{"type":"constrained"}} -->
    <div class="wp-block-group alignwide container text-center small text-body-secondary">
        ' . $this->get_footer_copyright_markup() . '
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'footer-columns.html':
                return '<!-- wp:group {"className":"pts-bootstrap-footer py-5 bg-body-tertiary","layout":{"type":"constrained"}} -->
<div class="wp-block-group pts-bootstrap-footer py-5 bg-body-tertiary">
    <!-- wp:columns {"align":"wide","className":"container row g-4"} -->
    <div class="wp-block-columns alignwide container row g-4">
        <!-- wp:column -->
        <div class="wp-block-column col-md-4">
            <!-- wp:site-title {"level":0,"className":"h5 mb-3"} /-->

            <!-- wp:paragraph {"fontSize":"small"} -->
            <p class="has-small-font-size text-body-secondary">A short description of your site.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column col-md-4">
            <!-- wp:heading {"level":2,"fontSize":"medium"} -->
            <h2 class="wp-block-heading has-medium-font-size h6 text-uppercase">Links</h2>
            <!-- /wp:heading -->

            <!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"}} /-->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column col-md-4">
            <!-- wp:heading {"level":2,"fontSize":"medium"} -->
            <h2 class="wp-block-heading has-medium-font-size h6 text-uppercase">Contact</h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"fontSize":"small"} -->
            <p class="has-small-font-size text-body-secondary">Add your address, email, or phone number here.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->

    ' . $this->get_footer_copyright_markup('center') . '
</div>
<!-- /wp:group -->';

            case 'sidebar.html':
                return '<!-- wp:group {"className":"pts-bootstrap-sidebar card border-0 shadow-sm d-none d-lg-block","layout":{"type":"constrained"}} -->
<div class="wp-block-group pts-bootstrap-sidebar card border-0 shadow-sm d-none d-lg-block">
    <!-- wp:group {"className":"card-body d-grid gap-4","layout":{"type":"constrained"}} -->
    <div class="wp-block-group card-body d-grid gap-4">
        <!-- wp:group {"className":"d-none d-lg-block","layout":{"type":"constrained"}} -->
        <div class="wp-block-group d-none d-lg-block">
        <!-- wp:navigation {"overlayMenu":"never","className":"pts-sidebar-nav flex-column gap-1","layout":{"type":"flex","orientation":"vertical"}} /-->
        </div>
        <!-- /wp:group -->

        <!-- wp:heading {"level":2,"fontSize":"medium","className":"h5"} -->
        <h2 class="wp-block-heading h5 has-medium-font-size mb-0">Recent Posts</h2>
        <!-- /wp:heading -->

        <!-- wp:latest-posts {"displayPostDate":true,"className":"small"} /-->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'comments.html':
                return '<!-- wp:group {"className":"pts-bootstrap-card card border-0 shadow-sm p-4","layout":{"type":"constrained"}} -->
<div class="wp-block-group pts-bootstrap-card card border-0 shadow-sm p-4">
    <!-- wp:comments -->
<div class="wp-block-comments">
    <!-- wp:comments-title /-->

    <!-- wp:comment-template -->
    <!-- wp:group {"className":"d-flex flex-wrap gap-2 align-items-center small text-body-secondary","layout":{"type":"flex","flexWrap":"wrap"}} -->
    <div class="wp-block-group d-flex flex-wrap gap-2 align-items-center small text-body-secondary">
        <!-- wp:comment-author-name /-->

        <!-- wp:comment-date /-->
    </div>
    <!-- /wp:group -->
    <!-- wp:comment-content /-->
    <!-- wp:comment-reply-link /-->
    <!-- /wp:comment-template -->

    <!-- wp:comments-pagination -->
    <!-- wp:comments-pagination-previous /-->
    <!-- wp:comments-pagination-numbers /-->
    <!-- wp:comments-pagination-next /-->
    <!-- /wp:comments-pagination -->

    <!-- wp:post-comments-form /-->
</div>
<!-- /wp:comments -->
</div>
<!-- /wp:group -->';

            case 'post-meta.html':
                return '<!-- wp:group {"className":"d-flex flex-wrap align-items-center gap-3 small text-body-secondary mb-4","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group d-flex flex-wrap align-items-center gap-3 small text-body-secondary mb-4">
    <!-- wp:post-date {"className":"mb-0"} /-->

    <!-- wp:post-author-name {"className":"mb-0"} /-->

    <!-- wp:post-terms {"term":"category","className":"mb-0"} /-->
</div>
<!-- /wp:group -->';

            case 'post-navigation.html':
                return '<!-- wp:group {"className":"pts-bootstrap-card card border-0 shadow-sm p-4 d-grid gap-3","layout":{"type":"constrained"}} -->
<div class="wp-block-group pts-bootstrap-card card border-0 shadow-sm p-4 d-grid gap-3">
    <!-- wp:paragraph {"fontSize":"small"} -->
    <p class="has-small-font-size text-body-secondary text-uppercase fw-semibold mb-0">Continue reading</p>
    <!-- /wp:paragraph -->

    <!-- wp:columns {"className":"pts-post-navigation-columns row g-3 align-items-start"} -->
    <div class="wp-block-columns pts-post-navigation-columns row g-3 align-items-start">
        <!-- wp:column {"className":"d-none d-md-block col-md-6"} -->
        <div class="wp-block-column d-none d-md-block col-md-6">
            <!-- wp:post-navigation-link {"type":"previous","showTitle":true,"arrow":"arrow"} /-->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"className":"col-12 col-md-6 text-md-end"} -->
        <div class="wp-block-column col-12 col-md-6 text-md-end">
            <!-- wp:post-navigation-link {"textAlign":"right","showTitle":true,"arrow":"arrow"} /-->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'loop.html':
                return $this->get_query_loop_markup();

            case 'hero-full-width.html':
                return $this->get_hero_full_width_markup();

            case 'hero-crossfade.html':
                return '<!-- wp:group {"align":"full","className":"pts-hero-crossfade","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull pts-hero-crossfade" data-interval="6000">
    <!-- wp:group {"className":"pts-hero-crossfade__slides","layout":{"type":"default"}} -->
    <div class="wp-block-group pts-hero-crossfade__slides" aria-hidden="true">
        <!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"pts-hero-crossfade__slide is-active"} -->
        <figure class="wp-block-image size-full pts-hero-crossfade__slide is-active"><img alt="" /></figure>
        <!-- /wp:image -->

        <!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"pts-hero-crossfade__slide"} -->
        <figure class="wp-block-image size-full pts-hero-crossfade__slide"><img alt="" /></figure>
        <!-- /wp:image -->

        <!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"pts-hero-crossfade__slide"} -->
        <figure class="wp-block-image size-full pts-hero-crossfade__slide"><img alt="" /></figure>
        <!-- /wp:image -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"className":"pts-hero-crossfade__content py-5 text-center text-white pts-animate-hero","layout":{"type":"constrained"}} -->
    <div class="wp-block-group pts-hero-crossfade__content py-5 text-center text-white pts-animate-hero">
        <!-- wp:heading {"textAlign":"center","level":1,"className":"pts-animate-hero__item display-5 fw-bold"} -->
        <h1 class="wp-block-heading has-text-align-center pts-animate-hero__item display-5 fw-bold">Headline over fading backgrounds</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","className":"pts-animate-hero__item lead mb-4"} -->
        <p class="has-text-align-center pts-animate-hero__item lead mb-4">Replace the background images in the slides group. They crossfade every few seconds with an ease-in-out transition.</p>
        <!-- /wp:paragraph -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"className":"pts-animate-hero__item"} -->
        <div class="wp-block-buttons pts-animate-hero__item">
            <!-- wp:button -->
            <div class="wp-block-button"><a class="wp-block-button__link wp-element-button btn btn-light btn-lg px-4">Get started</a></div>
            <!-- /wp:button -->

            <!-- wp:button {"className":"is-style-outline"} -->
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button btn btn-outline-light btn-lg px-4">Learn more</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'hero-edge.html':
                return '<!-- wp:group {"align":"full","className":"pts-hero-edge py-5 text-center bg-primary text-white","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull pts-hero-edge py-5 text-center bg-primary text-white">
    <!-- wp:group {"align":"wide","className":"pts-animate-hero","layout":{"type":"constrained"}} -->
    <div class="wp-block-group alignwide pts-animate-hero">
        <!-- wp:heading {"textAlign":"center","level":1,"className":"pts-animate-hero__item display-4 fw-bold"} -->
        <h1 class="wp-block-heading has-text-align-center pts-animate-hero__item display-4 fw-bold">Headline that spans the viewport</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","className":"pts-animate-hero__item lead mb-4"} -->
        <p class="has-text-align-center pts-animate-hero__item lead mb-4">Background and content reach the screen edges. Swap colors or add a cover image to match your brand.</p>
        <!-- /wp:paragraph -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"className":"pts-animate-hero__item"} -->
        <div class="wp-block-buttons pts-animate-hero__item">
            <!-- wp:button {"className":"is-style-fill"} -->
            <div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button btn btn-light btn-lg px-4">Primary action</a></div>
            <!-- /wp:button -->

            <!-- wp:button {"className":"is-style-outline"} -->
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button btn btn-outline-light btn-lg px-4">Secondary action</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'cta.html':
                return '<!-- wp:group {"className":"pts-bootstrap-cta py-5 px-4 text-center bg-primary-subtle","layout":{"type":"constrained"}} -->
<div class="wp-block-group pts-bootstrap-cta py-5 px-4 text-center bg-primary-subtle">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center mb-3">Ready to get started?</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center lead mb-4">A short supporting line inviting visitors to act.</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button btn btn-primary btn-lg px-4">Contact us</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->';

            case 'category-featured-list.html':
                return '<!-- wp:group {"className":"pts-category-featured card border-0 shadow-sm p-4 p-lg-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group pts-category-featured card border-0 shadow-sm p-4 p-lg-5">
    <!-- wp:group {"className":"d-grid gap-2 mb-4","layout":{"type":"constrained"}} -->
    <div class="wp-block-group d-grid gap-2 mb-4">
    <!-- wp:paragraph {"fontSize":"small"} -->
    <p class="has-small-font-size text-body-secondary text-uppercase fw-semibold mb-0">Category focus</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2,"className":"h2 mb-0"} -->
    <h2 class="wp-block-heading h2 mb-0">Latest feature and quick links</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"small"} -->
    <p class="has-small-font-size text-body-secondary mb-0">Choose the same category in each Query Loop block setting to keep the featured article and link list aligned.</p>
    <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:query {"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"list"}} -->
    <div class="wp-block-query mb-4">
        <!-- wp:post-template -->
        <!-- wp:group {"className":"card border-0 bg-body-tertiary overflow-hidden","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
        <div class="wp-block-group card border-0 bg-body-tertiary overflow-hidden">
            <!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","className":"mb-0"} /-->

            <!-- wp:group {"className":"p-4 d-grid gap-3","layout":{"type":"constrained"}} -->
            <div class="wp-block-group p-4 d-grid gap-3">
            <!-- wp:post-title {"isLink":true,"level":3,"className":"h3 mb-0"} /-->

            <!-- wp:post-date {"className":"small text-body-secondary"} /-->

            <!-- wp:post-excerpt {"moreText":"Read more","showMoreOnNewLine":false} /-->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:group -->
        <!-- /wp:post-template -->
    </div>
    <!-- /wp:query -->

    <!-- wp:group {"className":"mt-5","layout":{"type":"constrained"}} -->
    <div class="wp-block-group mt-5">
    <!-- wp:heading {"level":3,"fontSize":"medium","className":"h5 mb-3"} -->
    <h3 class="wp-block-heading has-medium-font-size h5 mb-3">More from this category</h3>
    <!-- /wp:heading -->

    <!-- wp:query {"query":{"perPage":3,"pages":0,"offset":1,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"list"}} -->
    <div class="wp-block-query">
        <!-- wp:post-template -->
        <!-- wp:group {"className":"d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center py-3 mb-3","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
        <div class="wp-block-group d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center py-3 mb-3">
            <!-- wp:post-title {"isLink":true,"level":4,"fontSize":"small","className":"h6 mb-0"} /-->

            <!-- wp:post-date {"fontSize":"small","className":"text-body-secondary mb-0"} /-->
        </div>
        <!-- /wp:group -->
        <!-- /wp:post-template -->
    </div>
    <!-- /wp:query -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'selected-post-page.html':
                return '<!-- wp:group {"className":"pts-selected-content card border-0 shadow-sm p-4 p-lg-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group pts-selected-content card border-0 shadow-sm p-4 p-lg-5">
    <!-- wp:group {"className":"d-grid gap-2 mb-4","layout":{"type":"constrained"}} -->
    <div class="wp-block-group d-grid gap-2 mb-4">
    <!-- wp:paragraph {"fontSize":"small"} -->
    <p class="has-small-font-size text-body-secondary text-uppercase fw-semibold mb-0">Picked content</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2,"className":"h2 mb-0"} -->
    <h2 class="wp-block-heading h2 mb-0">Highlight one post and one page</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"small"} -->
    <p class="has-small-font-size text-body-secondary mb-0">Replace the sample links and copy below with the exact post and page you want visitors to discover.</p>
    <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:columns {"className":"row g-4"} -->
    <div class="wp-block-columns row g-4">
        <!-- wp:column -->
        <div class="wp-block-column col-md-6">
            <!-- wp:group {"className":"card h-100 border-0 bg-body-tertiary p-4 d-grid gap-3","layout":{"type":"constrained"}} -->
            <div class="wp-block-group card h-100 border-0 bg-body-tertiary p-4 d-grid gap-3">
                <!-- wp:paragraph {"fontSize":"small"} -->
                <p class="has-small-font-size text-body-secondary text-uppercase fw-semibold mb-0">Featured post</p>
                <!-- /wp:paragraph -->

                <!-- wp:heading {"level":3,"className":"h3 mb-0"} -->
                <h3 class="wp-block-heading h3 mb-0">Chosen article title</h3>
                <!-- /wp:heading -->

                <!-- wp:paragraph -->
                <p class="mb-0">Add a short summary or lead text for the post you want visitors to read first.</p>
                <!-- /wp:paragraph -->

                <!-- wp:buttons -->
                <div class="wp-block-buttons">
                    <!-- wp:button -->
                    <div class="wp-block-button"><a class="wp-block-button__link wp-element-button btn btn-primary px-4" href="/sample-post/">Open the article</a></div>
                    <!-- /wp:button -->
                </div>
                <!-- /wp:buttons -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column col-md-6">
            <!-- wp:group {"className":"card h-100 border-0 bg-body-tertiary p-4 d-grid gap-3","layout":{"type":"constrained"}} -->
            <div class="wp-block-group card h-100 border-0 bg-body-tertiary p-4 d-grid gap-3">
                <!-- wp:paragraph {"fontSize":"small"} -->
                <p class="has-small-font-size text-body-secondary text-uppercase fw-semibold mb-0">Featured page</p>
                <!-- /wp:paragraph -->

                <!-- wp:heading {"level":3,"className":"h3 mb-0"} -->
                <h3 class="wp-block-heading h3 mb-0">Chosen page title</h3>
                <!-- /wp:heading -->

                <!-- wp:paragraph -->
                <p class="mb-0">Use this space to explain why this static page matters, such as a service, company profile, or contact page.</p>
                <!-- /wp:paragraph -->

                <!-- wp:buttons -->
                <div class="wp-block-buttons">
                    <!-- wp:button {"className":"is-style-outline"} -->
                    <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button btn btn-outline-primary px-4" href="/sample-page/">Open the page</a></div>
                    <!-- /wp:button -->
                </div>
                <!-- /wp:buttons -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            default:
                return $this->get_extended_part_content($file);
        }
    }

    /**
     * Block markup for the extended layout parts.
     *
     * @param string $file Part file name.
     * @return string
     */
    private function get_extended_part_content($file)
    {
        switch ($file) {
            case 'header-minimal.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
    <!-- wp:site-title {"textAlign":"center"} /-->
</div>
<!-- /wp:group -->';

            case 'header-with-search.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
    <!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
    <div class="wp-block-group alignwide">
        <!-- wp:site-title /-->

        <!-- wp:navigation {"layout":{"type":"flex","flexWrap":"wrap"}} /-->

        <!-- wp:search {"label":"Search","showLabel":false,"buttonText":"Search","buttonPosition":"button-inside"} /-->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'header-with-social.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
    <!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
    <div class="wp-block-group alignwide">
        <!-- wp:site-title /-->

        <!-- wp:navigation {"layout":{"type":"flex","flexWrap":"wrap"}} /-->

        <!-- wp:social-links -->
        <ul class="wp-block-social-links">
            <!-- wp:social-link {"url":"https://wordpress.org","service":"wordpress"} /-->

            <!-- wp:social-link {"url":"https://x.com","service":"x"} /-->

            <!-- wp:social-link {"url":"https://instagram.com","service":"instagram"} /-->
        </ul>
        <!-- /wp:social-links -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'header-tagline.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
    <!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
    <div class="wp-block-group alignwide">
        <!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}} -->
        <div class="wp-block-group">
            <!-- wp:site-title /-->

            <!-- wp:site-tagline /-->
        </div>
        <!-- /wp:group -->

        <!-- wp:navigation {"layout":{"type":"flex","flexWrap":"wrap"}} /-->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'header-split.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
    <!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
    <div class="wp-block-group alignwide">
        <!-- wp:navigation {"layout":{"type":"flex","flexWrap":"wrap"}} /-->

        <!-- wp:site-title /-->

        <!-- wp:buttons -->
        <div class="wp-block-buttons">
            <!-- wp:button -->
            <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Contact</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'footer-minimal.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
    ' . $this->get_footer_copyright_markup('center') . '
</div>
<!-- /wp:group -->';

            case 'footer-menu.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
    <!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
    <div class="wp-block-group alignwide">
        <!-- wp:site-title {"level":0} /-->

        <!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","flexWrap":"wrap"}} /-->
    </div>
    <!-- /wp:group -->

    ' . $this->get_footer_copyright_markup('center') . '
</div>
<!-- /wp:group -->';

            case 'footer-social.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
    <!-- wp:social-links {"layout":{"type":"flex","justifyContent":"center"}} -->
    <ul class="wp-block-social-links">
        <!-- wp:social-link {"url":"https://wordpress.org","service":"wordpress"} /-->

        <!-- wp:social-link {"url":"https://x.com","service":"x"} /-->

        <!-- wp:social-link {"url":"https://instagram.com","service":"instagram"} /-->

        <!-- wp:social-link {"url":"https://youtube.com","service":"youtube"} /-->
    </ul>
    <!-- /wp:social-links -->

    ' . $this->get_footer_copyright_markup('center') . '
</div>
<!-- /wp:group -->';

            case 'footer-mega.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--40)">
    <!-- wp:columns {"align":"wide"} -->
    <div class="wp-block-columns alignwide">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:site-title {"level":0} /-->

            <!-- wp:paragraph {"fontSize":"small"} -->
            <p class="has-small-font-size">A short description of your site.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":2,"fontSize":"medium"} -->
            <h2 class="wp-block-heading has-medium-font-size">Links</h2>
            <!-- /wp:heading -->

            <!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"}} /-->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":2,"fontSize":"medium"} -->
            <h2 class="wp-block-heading has-medium-font-size">Resources</h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"fontSize":"small"} -->
            <p class="has-small-font-size">Add links to docs, FAQ, or support pages here.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":2,"fontSize":"medium"} -->
            <h2 class="wp-block-heading has-medium-font-size">Contact</h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"fontSize":"small"} -->
            <p class="has-small-font-size">Add your address, email, or phone number here.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->

    <!-- wp:separator -->
    <hr class="wp-block-separator has-alpha-channel-opacity"/>
    <!-- /wp:separator -->

    <!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
    <div class="wp-block-group alignwide">
        ' . $this->get_footer_copyright_markup() . '

        <!-- wp:social-links -->
        <ul class="wp-block-social-links">
            <!-- wp:social-link {"url":"https://wordpress.org","service":"wordpress"} /-->

            <!-- wp:social-link {"url":"https://x.com","service":"x"} /-->
        </ul>
        <!-- /wp:social-links -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'footer-cta.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--40)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">Ready to get started?</h2>
    <!-- /wp:heading -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Contact us</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

    <!-- wp:separator -->
    <hr class="wp-block-separator has-alpha-channel-opacity"/>
    <!-- /wp:separator -->

    ' . $this->get_footer_copyright_markup('center') . '
</div>
<!-- /wp:group -->';

            case 'sidebar-blog.html':
                return '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:search {"label":"Search","showLabel":false,"buttonText":"Search"} /-->

    <!-- wp:heading {"level":2,"fontSize":"medium"} -->
    <h2 class="wp-block-heading has-medium-font-size">Recent Posts</h2>
    <!-- /wp:heading -->

    <!-- wp:latest-posts {"displayPostDate":true} /-->

    <!-- wp:heading {"level":2,"fontSize":"medium"} -->
    <h2 class="wp-block-heading has-medium-font-size">Categories</h2>
    <!-- /wp:heading -->

    <!-- wp:categories /-->

    <!-- wp:heading {"level":2,"fontSize":"medium"} -->
    <h2 class="wp-block-heading has-medium-font-size">Archives</h2>
    <!-- /wp:heading -->

    <!-- wp:archives /-->
</div>
<!-- /wp:group -->';

            case 'sidebar-cta.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
    <!-- wp:heading {"level":2,"fontSize":"medium"} -->
    <h2 class="wp-block-heading has-medium-font-size">Need help?</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"small"} -->
    <p class="has-small-font-size">A short message guiding visitors to your main offer.</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Contact us</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->';

            case 'loop-grid.html':
                return '<!-- wp:query {"query":{"perPage":9,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true}} -->
<div class="wp-block-query">
    <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
    <!-- wp:post-featured-image {"isLink":true} /-->

    <!-- wp:post-title {"isLink":true,"fontSize":"medium"} /-->

    <!-- wp:post-date /-->
    <!-- /wp:post-template -->

    <!-- wp:query-pagination -->
    <!-- wp:query-pagination-previous /-->
    <!-- wp:query-pagination-numbers /-->
    <!-- wp:query-pagination-next /-->
    <!-- /wp:query-pagination -->

    <!-- wp:query-no-results -->
    <!-- wp:paragraph -->
    <p>Nothing found.</p>
    <!-- /wp:paragraph -->
    <!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->';

            case 'loop-compact.html':
                return '<!-- wp:query {"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true}} -->
<div class="wp-block-query">
    <!-- wp:post-template -->
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
    <div class="wp-block-group">
        <!-- wp:post-date /-->

        <!-- wp:post-title {"isLink":true,"fontSize":"medium"} /-->
    </div>
    <!-- /wp:group -->
    <!-- /wp:post-template -->

    <!-- wp:query-pagination -->
    <!-- wp:query-pagination-previous /-->
    <!-- wp:query-pagination-numbers /-->
    <!-- wp:query-pagination-next /-->
    <!-- /wp:query-pagination -->

    <!-- wp:query-no-results -->
    <!-- wp:paragraph -->
    <p>Nothing found.</p>
    <!-- /wp:paragraph -->
    <!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->';

            case 'archive-header.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:query-title {"type":"archive"} /-->

    <!-- wp:term-description /-->
</div>
<!-- /wp:group -->';

            case 'page-header.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:post-title {"textAlign":"center"} /-->
</div>
<!-- /wp:group -->';

            case 'author-box.html':
                return '<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group">
    <!-- wp:avatar {"size":60} /-->

    <!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}} -->
    <div class="wp-block-group">
        <!-- wp:post-author-name /-->

        <!-- wp:post-author-biography /-->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'related-posts.html':
                return '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Related posts</h2>
    <!-- /wp:heading -->

    <!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"exclude","inherit":false}} -->
    <div class="wp-block-query">
        <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
        <!-- wp:post-featured-image {"isLink":true} /-->

        <!-- wp:post-title {"isLink":true,"fontSize":"medium"} /-->
        <!-- /wp:post-template -->
    </div>
    <!-- /wp:query -->
</div>
<!-- /wp:group -->';

            case 'social-links.html':
                return '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:social-links {"layout":{"type":"flex","justifyContent":"center"}} -->
    <ul class="wp-block-social-links">
        <!-- wp:social-link {"url":"https://wordpress.org","service":"wordpress"} /-->

        <!-- wp:social-link {"url":"https://x.com","service":"x"} /-->

        <!-- wp:social-link {"url":"https://instagram.com","service":"instagram"} /-->

        <!-- wp:social-link {"url":"https://youtube.com","service":"youtube"} /-->
    </ul>
    <!-- /wp:social-links -->
</div>
<!-- /wp:group -->';

            default:
                return $this->get_jp_lp_part_content($file);
        }
    }

    /**
     * Block markup for the Japanese LP parts (placeholder copy in Japanese).
     *
     * @param string $file Part file name.
     * @return string
     */
    private function get_jp_lp_part_content($file)
    {
        switch ($file) {
            case 'lp-hero.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">〇〇でお悩みの方へ</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center","level":1} -->
    <h1 class="wp-block-heading has-text-align-center">あなたの課題を、〇〇がまるごと解決します</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">導入実績〇〇件。専門スタッフが初回相談から導入後まで伴走サポートします。</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">無料で相談する</a></div>
        <!-- /wp:button -->

        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">資料をダウンロード</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※ご相談は無料です。しつこい営業は一切ありません。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'lp-problems.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">こんなお悩みはありませんか？</h2>
    <!-- /wp:heading -->

    <!-- wp:list -->
    <ul class="wp-block-list"><!-- wp:list-item -->
<li>✔ 何から手を付ければよいか分からない</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>✔ 自己流で進めてきたが、成果が出ていない</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>✔ 担当者が忙しく、いつも後回しになっている</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>✔ 外注したいが、どこに頼めばよいか分からない</li>
<!-- /wp:list-item --></ul>
    <!-- /wp:list -->
</div>
<!-- /wp:group -->';

            case 'lp-empathy.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">そのお悩み、放置していませんか？</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">課題を先送りにするほど、機会損失は積み重なっていきます。<br>いま動き出すことが、半年後の大きな差になります。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'lp-solution.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">＼ そのお悩み ／</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">〇〇がすべて解決します</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">〇〇は、はじめての方でも安心して使えるオールインワンのサービスです。<br>面倒な作業はおまかせいただき、本来の業務に集中できます。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'lp-reasons.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">選ばれる3つの理由</h2>
    <!-- /wp:heading -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading">理由01　専門チームの伴走支援</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>経験豊富な専任担当者が、導入から運用まで一貫してサポートします。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading">理由02　明瞭な料金体系</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>初期費用と月額のみのシンプルな料金。追加費用は事前にご案内します。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading">理由03　豊富な実績</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>業種を問わず〇〇件の支援実績。ノウハウを御社に合わせて提供します。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'lp-achievements.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">数字で見る実績</h2>
    <!-- /wp:heading -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3,"fontSize":"x-large"} -->
            <h3 class="wp-block-heading has-text-align-center has-x-large-font-size">1,200件</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
            <p class="has-text-align-center has-small-font-size">累計支援実績</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3,"fontSize":"x-large"} -->
            <h3 class="wp-block-heading has-text-align-center has-x-large-font-size">98%</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
            <p class="has-text-align-center has-small-font-size">お客様満足度</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3,"fontSize":"x-large"} -->
            <h3 class="wp-block-heading has-text-align-center has-x-large-font-size">95%</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
            <p class="has-text-align-center has-small-font-size">契約継続率</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※2026年5月時点・自社調べ</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'lp-voice.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">お客様の声</h2>
    <!-- /wp:heading -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:quote -->
            <blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>「導入から3か月で問い合わせ数が2倍に。もっと早く相談すればよかったです。」</p>
<!-- /wp:paragraph --><cite>東京都・小売業・40代</cite></blockquote>
            <!-- /wp:quote -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:quote -->
            <blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>「専任の担当者が丁寧に伴走してくれるので、社内に知見がなくても安心でした。」</p>
<!-- /wp:paragraph --><cite>大阪府・製造業・50代</cite></blockquote>
            <!-- /wp:quote -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:quote -->
            <blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>「費用が明瞭で稟議が通しやすかったです。経営層への説明もスムーズでした。」</p>
<!-- /wp:paragraph --><cite>福岡県・サービス業・30代</cite></blockquote>
            <!-- /wp:quote -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'lp-before-after.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">導入前と導入後の変化</h2>
    <!-- /wp:heading -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">Before</h3>
            <!-- /wp:heading -->

            <!-- wp:list -->
            <ul class="wp-block-list"><!-- wp:list-item -->
<li>作業が属人化し、担当者しか分からない</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>毎月のレポート作成に丸2日かかる</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>成果が出ているのか判断できない</li>
<!-- /wp:list-item --></ul>
            <!-- /wp:list -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">After</h3>
            <!-- /wp:heading -->

            <!-- wp:list -->
            <ul class="wp-block-list"><!-- wp:list-item -->
<li>手順が標準化され、誰でも対応できる</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>レポートは自動化され、作業は30分に短縮</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>数値で効果を確認しながら改善できる</li>
<!-- /wp:list-item --></ul>
            <!-- /wp:list -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'lp-pricing.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">料金プラン</h2>
    <!-- /wp:heading -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">ライト</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"x-large"} -->
            <p class="has-text-align-center has-x-large-font-size"><strong>月額9,800円</strong></p>
            <!-- /wp:paragraph -->

            <!-- wp:list -->
            <ul class="wp-block-list"><!-- wp:list-item -->
<li>基本機能すべて</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>メールサポート</li>
<!-- /wp:list-item --></ul>
            <!-- /wp:list -->

            <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
            <div class="wp-block-buttons">
                <!-- wp:button {"className":"is-style-outline"} -->
                <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">このプランを選ぶ</a></div>
                <!-- /wp:button -->
            </div>
            <!-- /wp:buttons -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">スタンダード（人気No.1）</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"x-large"} -->
            <p class="has-text-align-center has-x-large-font-size"><strong>月額29,800円</strong></p>
            <!-- /wp:paragraph -->

            <!-- wp:list -->
            <ul class="wp-block-list"><!-- wp:list-item -->
<li>ライトの全機能</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>専任担当者による月次ミーティング</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>電話・チャットサポート</li>
<!-- /wp:list-item --></ul>
            <!-- /wp:list -->

            <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
            <div class="wp-block-buttons">
                <!-- wp:button -->
                <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">このプランを選ぶ</a></div>
                <!-- /wp:button -->
            </div>
            <!-- /wp:buttons -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">プレミアム</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"x-large"} -->
            <p class="has-text-align-center has-x-large-font-size"><strong>月額59,800円</strong></p>
            <!-- /wp:paragraph -->

            <!-- wp:list -->
            <ul class="wp-block-list"><!-- wp:list-item -->
<li>スタンダードの全機能</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>運用代行・コンサルティング</li>
<!-- /wp:list-item --></ul>
            <!-- /wp:list -->

            <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
            <div class="wp-block-buttons">
                <!-- wp:button {"className":"is-style-outline"} -->
                <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">このプランを選ぶ</a></div>
                <!-- /wp:button -->
            </div>
            <!-- /wp:buttons -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※価格はすべて税込です。年間契約で2か月分お得になります。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'lp-comparison.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">他社サービスとの比較</h2>
    <!-- /wp:heading -->

    <!-- wp:table -->
    <figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td><strong>比較項目</strong></td><td><strong>当社</strong></td><td><strong>A社</strong></td><td><strong>B社</strong></td></tr><tr><td>料金</td><td>◎ 月額9,800円〜</td><td>○ 月額15,000円〜</td><td>△ 月額30,000円〜</td></tr><tr><td>サポート体制</td><td>◎ 専任担当者</td><td>△ メールのみ</td><td>○ 共有窓口</td></tr><tr><td>導入スピード</td><td>◎ 最短3日</td><td>○ 2週間</td><td>○ 1か月</td></tr><tr><td>契約期間の縛り</td><td>◎ なし</td><td>△ 1年</td><td>△ 6か月</td></tr></tbody></table></figure>
    <!-- /wp:table -->
</div>
<!-- /wp:group -->';

            case 'lp-flow.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">ご利用の流れ</h2>
    <!-- /wp:heading -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">STEP 1</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>お問い合わせ</strong><br>フォームから24時間受付。1営業日以内にご連絡します。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">STEP 2</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>ヒアリング</strong><br>現状の課題とご要望をオンラインで伺います（約30分）。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">STEP 3</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>ご提案・お見積り</strong><br>最適なプランと費用を分かりやすくご提示します。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">STEP 4</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>ご契約・開始</strong><br>最短3日でスタート。導入後も専任担当が伴走します。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'lp-faq.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">よくあるご質問</h2>
    <!-- /wp:heading -->

    <!-- wp:details -->
    <details class="wp-block-details"><summary>Q. 契約期間の縛りはありますか？</summary><!-- wp:paragraph -->
<p>A. ありません。月単位でいつでも解約いただけます。</p>
<!-- /wp:paragraph --></details>
    <!-- /wp:details -->

    <!-- wp:details -->
    <details class="wp-block-details"><summary>Q. 導入までどのくらいかかりますか？</summary><!-- wp:paragraph -->
<p>A. お申し込みから最短3営業日でご利用を開始できます。</p>
<!-- /wp:paragraph --></details>
    <!-- /wp:details -->

    <!-- wp:details -->
    <details class="wp-block-details"><summary>Q. 専門知識がなくても使えますか？</summary><!-- wp:paragraph -->
<p>A. はい。専任担当者が初期設定から運用までサポートしますのでご安心ください。</p>
<!-- /wp:paragraph --></details>
    <!-- /wp:details -->

    <!-- wp:details -->
    <details class="wp-block-details"><summary>Q. 支払い方法は何が選べますか？</summary><!-- wp:paragraph -->
<p>A. クレジットカード・銀行振込・請求書払いに対応しています。</p>
<!-- /wp:paragraph --></details>
    <!-- /wp:details -->
</div>
<!-- /wp:group -->';

            case 'lp-campaign.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">＼ 期間限定キャンペーン実施中 ／</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">今なら初期費用0円</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">〇月〇日（金）までにお申し込みの方限定。この機会をお見逃しなく。</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">キャンペーンに申し込む</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->';

            case 'lp-benefits.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">今だけの3つの特典</h2>
    <!-- /wp:heading -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">特典01</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center">初期設定を無料で代行（通常50,000円）</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">特典02</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center">活用ノウハウをまとめた特別ガイドブック進呈</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">特典03</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center">個別相談会（60分）に無料ご招待</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'lp-guarantee.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">安心の30日間返金保証</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">万が一サービスにご満足いただけなかった場合は、ご利用開始から30日以内であれば全額返金いたします。<br>まずはリスクなしでお試しください。</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※返金条件の詳細は利用規約をご確認ください。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'lp-message.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">代表メッセージ</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>はじめまして。株式会社サンプル代表の山田太郎です。私たちは「すべての企業に、成果につながる仕組みを」を理念に、これまで多くのお客様の課題解決に取り組んでまいりました。</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph -->
    <p>はじめての方でも安心してご相談いただけるよう、押し売りは一切いたしません。まずはお気軽にお話をお聞かせください。</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"right"} -->
    <p class="has-text-align-right">株式会社サンプル　代表取締役　山田 太郎</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'lp-company.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">会社概要</h2>
    <!-- /wp:heading -->

    <!-- wp:table -->
    <figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td><strong>会社名</strong></td><td>株式会社サンプル</td></tr><tr><td><strong>所在地</strong></td><td>〒100-0000 東京都千代田区サンプル町1-2-3 サンプルビル5F</td></tr><tr><td><strong>設立</strong></td><td>2015年4月</td></tr><tr><td><strong>代表者</strong></td><td>代表取締役　山田 太郎</td></tr><tr><td><strong>事業内容</strong></td><td>〇〇サービスの企画・開発・運用支援</td></tr></tbody></table></figure>
    <!-- /wp:table -->
</div>
<!-- /wp:group -->';

            case 'lp-access.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">アクセス</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">〒100-0000 東京都千代田区サンプル町1-2-3 サンプルビル5F<br>東京メトロ「サンプル駅」3番出口より徒歩3分<br>受付時間：平日 9:00〜18:00（土日祝休み）</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※ここに地図（Google マップの埋め込みなど）を配置してください。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'lp-news.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">お知らせ</h2>
    <!-- /wp:heading -->

    <!-- wp:query {"query":{"perPage":5,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
    <div class="wp-block-query">
        <!-- wp:post-template -->
        <!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
        <div class="wp-block-group">
            <!-- wp:post-date /-->

            <!-- wp:post-title {"isLink":true,"fontSize":"medium"} /-->
        </div>
        <!-- /wp:group -->
        <!-- /wp:post-template -->

        <!-- wp:query-no-results -->
        <!-- wp:paragraph -->
        <p>お知らせはまだありません。</p>
        <!-- /wp:paragraph -->
        <!-- /wp:query-no-results -->
    </div>
    <!-- /wp:query -->
</div>
<!-- /wp:group -->';

            case 'lp-cta.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">まずはお気軽にご相談ください</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","fontSize":"x-large"} -->
    <p class="has-text-align-center has-x-large-font-size"><strong>TEL 0120-000-000</strong></p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">受付時間：平日 9:00〜18:00（土日祝休み）</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">無料相談フォームへ</a></div>
        <!-- /wp:button -->

        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">資料をダウンロード</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->';

            default:
                return $this->get_product_lp_part_content($file);
        }
    }

    /**
     * Block markup for the product LP parts (EC / Rakuten-style sections,
     * placeholder copy in Japanese).
     *
     * @param string $file Part file name.
     * @return string
     */
    private function get_product_lp_part_content($file)
    {
        switch ($file) {
            case 'product-hero.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">＼ 楽天ランキング第1位獲得 ／</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center","level":1} -->
    <h1 class="wp-block-heading has-text-align-center">〇〇（商品名）公式オンラインショップ</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">累計販売数〇〇万個突破。リピーター続出の人気商品です。</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center","fontSize":"x-large"} -->
    <p class="has-text-align-center has-x-large-font-size"><strong>特別価格 3,980円（税込・送料無料）</strong></p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">カートに入れる</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->';

            case 'product-badges.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>送料無料</strong><br>全国どこでも</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>あす楽対応</strong><br>正午までの注文で翌日着</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>安心の国内生産</strong><br>国内工場で製造</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>ギフト対応</strong><br>ラッピング無料</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'product-ranking.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">🏆 ランキング受賞実績</h2>
    <!-- /wp:heading -->

    <!-- wp:list -->
    <ul class="wp-block-list"><!-- wp:list-item -->
<li>🥇 楽天ランキング 〇〇部門 第1位（2026年5月度）</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>🥇 デイリーランキング 3週連続 第1位</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>🥈 総合ランキング 第2位（2026年4月度）</li>
<!-- /wp:list-item --></ul>
    <!-- /wp:list -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※集計期間・部門の詳細は各ランキングページをご確認ください。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'product-media.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">メディア・SNSで話題沸騰中</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">テレビ・雑誌・人気インフルエンサーの投稿など、各種メディアで多数ご紹介いただいています。</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※ここに掲載メディアのロゴや SNS 投稿の埋め込みを配置してください。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'product-sale.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">＼ お買い物マラソン開催中 ／</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">期間限定タイムセール 33%OFF</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center"><strong>〇月〇日（金）01:59まで。</strong>セール終了後は通常価格に戻ります。</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">セール価格で購入する</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->';

            case 'product-coupon.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">🎫 このページ限定クーポン配布中</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">今すぐ使える<strong>500円OFFクーポン</strong>を配布中。獲得してからご購入ください。</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">クーポンを獲得する</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※おひとり様1回限り。他のクーポンとの併用はできません。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'product-price.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">通常価格 <s>5,980円</s> のところ…</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center","fontSize":"x-large"} -->
    <p class="has-text-align-center has-x-large-font-size"><strong>特別価格 3,980円（税込）</strong></p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center"><strong>33%OFF・送料無料・ポイント10倍</strong></p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※通常価格は当店平常時の販売価格です。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'product-features.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">選ばれる3つのこだわり</h2>
    <!-- /wp:heading -->

    <!-- wp:heading {"level":3} -->
    <h3 class="wp-block-heading">POINT 01　厳選素材を贅沢に使用</h3>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>産地から厳選した素材のみを使用。品質に一切妥協していません。</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":3} -->
    <h3 class="wp-block-heading">POINT 02　職人による丁寧な仕上げ</h3>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>熟練の職人がひとつひとつ手作業で検品。安心してお使いいただけます。</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":3} -->
    <h3 class="wp-block-heading">POINT 03　使いやすさを追求した設計</h3>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>お客様の声をもとに改良を重ね、毎日使いたくなる使い心地を実現しました。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'product-usage.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">お使い方はかんたん3ステップ</h2>
    <!-- /wp:heading -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">STEP 1</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>開封する</strong><br>パッケージから取り出します。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">STEP 2</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>セットする</strong><br>本体に取り付けるだけ。工具は不要です。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3} -->
            <h3 class="wp-block-heading has-text-align-center">STEP 3</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center"} -->
            <p class="has-text-align-center"><strong>使い始める</strong><br>すぐにお使いいただけます。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'product-scenes.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">こんな方・こんなシーンにおすすめ</h2>
    <!-- /wp:heading -->

    <!-- wp:list -->
    <ul class="wp-block-list"><!-- wp:list-item -->
<li>✔ 毎日の家事の時短をしたい方に</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>✔ 母の日・父の日・敬老の日の贈り物に</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>✔ 新生活・引っ越し祝いのプレゼントに</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>✔ 自分へのご褒美に</li>
<!-- /wp:list-item --></ul>
    <!-- /wp:list -->
</div>
<!-- /wp:group -->';

            case 'product-variations.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">選べるカラー・サイズ</h2>
    <!-- /wp:heading -->

    <!-- wp:table -->
    <figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td><strong>カラー</strong></td><td>ホワイト／ブラック／ベージュ／ネイビー（全4色）</td></tr><tr><td><strong>サイズ</strong></td><td>S（約20cm）／M（約25cm）／L（約30cm）</td></tr></tbody></table></figure>
    <!-- /wp:table -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※カラー・サイズは購入ページの選択肢からお選びください。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'product-set.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">セット内容</h2>
    <!-- /wp:heading -->

    <!-- wp:list -->
    <ul class="wp-block-list"><!-- wp:list-item -->
<li>本体 × 1</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>交換用パーツ × 2</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>専用収納ケース × 1</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>取扱説明書（保証書付き）× 1</li>
<!-- /wp:list-item --></ul>
    <!-- /wp:list -->
</div>
<!-- /wp:group -->';

            case 'product-spec.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">商品仕様</h2>
    <!-- /wp:heading -->

    <!-- wp:table -->
    <figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td><strong>品名</strong></td><td>〇〇（商品名）</td></tr><tr><td><strong>内容量</strong></td><td>250g</td></tr><tr><td><strong>サイズ</strong></td><td>約 W25 × D10 × H8cm</td></tr><tr><td><strong>素材</strong></td><td>ステンレス・天然木</td></tr><tr><td><strong>原産国</strong></td><td>日本</td></tr><tr><td><strong>保存方法</strong></td><td>直射日光・高温多湿を避けて保管してください</td></tr></tbody></table></figure>
    <!-- /wp:table -->
</div>
<!-- /wp:group -->';

            case 'product-reviews.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">お客様レビュー</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","fontSize":"x-large"} -->
    <p class="has-text-align-center has-x-large-font-size"><strong>★4.8</strong></p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">レビュー件数 1,234件（2026年5月時点）</p>
    <!-- /wp:paragraph -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:quote -->
            <blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>★★★★★<br>「想像以上の品質でした。毎日使っています。ギフト包装も丁寧で大満足です。」</p>
<!-- /wp:paragraph --><cite>50代・女性</cite></blockquote>
            <!-- /wp:quote -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:quote -->
            <blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>★★★★★<br>「注文の翌日に届きました。母の日のプレゼントにしたところ、とても喜ばれました。」</p>
<!-- /wp:paragraph --><cite>30代・男性</cite></blockquote>
            <!-- /wp:quote -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:quote -->
            <blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>★★★★☆<br>「リピート購入です。使い心地が良く、友人にもすすめています。」</p>
<!-- /wp:paragraph --><cite>40代・女性</cite></blockquote>
            <!-- /wp:quote -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※個人の感想であり、効果・効能を保証するものではありません。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'product-story.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">開発ストーリー</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>「毎日使うものだからこそ、本当に良いものを届けたい」。その想いから、この商品の開発は始まりました。</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph -->
    <p>試作を重ねること2年。素材の配合や形状を何度も見直し、お客様モニターの声を反映して、ようやく納得のいく仕上がりにたどり着きました。職人とともに作り上げた自信作を、ぜひお手に取ってお確かめください。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'product-gift.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">🎁 ギフト対応について</h2>
    <!-- /wp:heading -->

    <!-- wp:list -->
    <ul class="wp-block-list"><!-- wp:list-item -->
<li>ラッピング無料（リボン・包装紙からお選びいただけます）</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>のし対応無料（御祝・内祝・御中元・御歳暮など）</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>メッセージカード対応（ご注文時にご記入ください）</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>金額の分かる明細書は同梱しません</li>
<!-- /wp:list-item --></ul>
    <!-- /wp:list -->
</div>
<!-- /wp:group -->';

            case 'product-shipping.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">配送・送料について</h2>
    <!-- /wp:heading -->

    <!-- wp:table -->
    <figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td><strong>送料</strong></td><td>全国一律無料（沖縄・離島も無料）</td></tr><tr><td><strong>配送方法</strong></td><td>宅配便（ヤマト運輸／佐川急便）</td></tr><tr><td><strong>お届け目安</strong></td><td>ご注文から2〜4営業日でお届け</td></tr><tr><td><strong>あす楽</strong></td><td>正午までのご注文で翌日お届け（対象地域のみ）</td></tr><tr><td><strong>日時指定</strong></td><td>ご注文時に指定可能</td></tr></tbody></table></figure>
    <!-- /wp:table -->
</div>
<!-- /wp:group -->';

            case 'product-returns.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">返品・交換について</h2>
    <!-- /wp:heading -->

    <!-- wp:list -->
    <ul class="wp-block-list"><!-- wp:list-item -->
<li>商品到着後7日以内にご連絡いただいた場合に承ります</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>不良品・誤配送の場合は送料当店負担で交換いたします</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>お客様都合の返品は未開封・未使用品に限ります（送料お客様負担）</li>
<!-- /wp:list-item --></ul>
    <!-- /wp:list -->

    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">※詳細は当店の返品ポリシーをご確認ください。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'product-notes.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">ご購入前にご確認ください</h2>
    <!-- /wp:heading -->

    <!-- wp:list -->
    <ul class="wp-block-list"><!-- wp:list-item -->
<li>お使いのモニターにより、実際の色味と異なって見える場合があります</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>手作業による製造のため、サイズ・重量に多少の個体差があります</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>ご注文が集中した場合、お届けまでお時間をいただくことがあります</li>
<!-- /wp:list-item --></ul>
    <!-- /wp:list -->
</div>
<!-- /wp:group -->';

            case 'product-cart.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">＼ 在庫わずか・お早めに ／</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">ご注文はこちら</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","fontSize":"x-large"} -->
    <p class="has-text-align-center has-x-large-font-size"><strong>特別価格 3,980円（税込・送料無料）</strong></p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">ポイント10倍・あす楽対応・ギフト包装無料</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">カートに入れる</a></div>
        <!-- /wp:button -->

        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">お気に入りに登録する</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->';

            default:
                return $this->get_layout_kit_part_content($file);
        }
    }

    /**
     * Block markup for the common layout kit parts (generic page sections,
     * placeholder copy in Japanese, empty image placeholders).
     *
     * @param string $file Part file name.
     * @return string
     */
    private function get_layout_kit_part_content($file)
    {
        switch ($file) {
            case 'layout-section-header.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--30)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">セクション見出しが入ります</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">セクションの内容を紹介するリード文が入ります。1〜2文程度で簡潔にまとめます。</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

            case 'layout-text-image.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:columns {"verticalAlignment":"center"} -->
    <div class="wp-block-columns are-vertically-aligned-center">
        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading">見出しテキストが入ります</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>ここに本文テキストが入ります。サービスや商品の説明、会社の紹介など、画像と組み合わせて伝えたい内容を記載します。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-image-text.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:columns {"verticalAlignment":"center"} -->
    <div class="wp-block-columns are-vertically-aligned-center">
        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading">見出しテキストが入ります</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>ここに本文テキストが入ります。画像を左に配置した、もっとも基本的な紹介レイアウトです。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-two-column-text.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading">見出しが入ります</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>ここに本文テキストが入ります。2カラム構成で文章量の多い内容を読みやすく整理できます。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading">見出しが入ります</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>ここに本文テキストが入ります。左右で対になる内容や、2つのトピックを並べる際に使います。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-three-cards.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->

            <!-- wp:heading {"level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-medium-font-size">カードの見出し</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>カードの説明文が入ります。画像・見出し・本文のセットです。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->

            <!-- wp:heading {"level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-medium-font-size">カードの見出し</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>カードの説明文が入ります。サービス・事例・記事などの紹介に使えます。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->

            <!-- wp:heading {"level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-medium-font-size">カードの見出し</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>カードの説明文が入ります。3つ並べるともっとも収まりが良い構成です。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-four-cards.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:columns {"align":"wide"} -->
    <div class="wp-block-columns alignwide">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-medium-font-size">項目01</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>短い説明文が入ります。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-medium-font-size">項目02</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>短い説明文が入ります。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-medium-font-size">項目03</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>短い説明文が入ります。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-medium-font-size">項目04</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>短い説明文が入ります。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-feature-list.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:list -->
            <ul class="wp-block-list"><!-- wp:list-item -->
<li>✔ 特徴やメリットの項目が入ります</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>✔ 特徴やメリットの項目が入ります</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>✔ 特徴やメリットの項目が入ります</li>
<!-- /wp:list-item --></ul>
            <!-- /wp:list -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:list -->
            <ul class="wp-block-list"><!-- wp:list-item -->
<li>✔ 特徴やメリットの項目が入ります</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>✔ 特徴やメリットの項目が入ります</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>✔ 特徴やメリットの項目が入ります</li>
<!-- /wp:list-item --></ul>
            <!-- /wp:list -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-gallery.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->

            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->

            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->

            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-full-image.html':
                return '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:image {"align":"full","sizeSlug":"large","linkDestination":"none"} -->
    <figure class="wp-block-image alignfull size-large"><img alt="" /></figure>
    <!-- /wp:image -->
</div>
<!-- /wp:group -->';

            case 'layout-timeline.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">沿革</h2>
    <!-- /wp:heading -->

    <!-- wp:table -->
    <figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td><strong>2015年4月</strong></td><td>会社設立</td></tr><tr><td><strong>2018年10月</strong></td><td>本社を移転、新サービスを開始</td></tr><tr><td><strong>2021年6月</strong></td><td>累計取引社数500社を突破</td></tr><tr><td><strong>2024年1月</strong></td><td>新工場が稼働開始</td></tr><tr><td><strong>2026年4月</strong></td><td>海外展開を開始</td></tr></tbody></table></figure>
    <!-- /wp:table -->
</div>
<!-- /wp:group -->';

            case 'layout-team.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">スタッフ紹介</h2>
    <!-- /wp:heading -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->

            <!-- wp:heading {"textAlign":"center","level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-text-align-center has-medium-font-size">山田 太郎</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
            <p class="has-text-align-center has-small-font-size">代表取締役</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->

            <!-- wp:heading {"textAlign":"center","level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-text-align-center has-medium-font-size">佐藤 花子</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
            <p class="has-text-align-center has-small-font-size">デザイナー</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->

            <!-- wp:heading {"textAlign":"center","level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-text-align-center has-medium-font-size">鈴木 一郎</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
            <p class="has-text-align-center has-small-font-size">エンジニア</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-logos.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">多くの企業・団体にご利用いただいています</p>
    <!-- /wp:paragraph -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-stats.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3,"fontSize":"x-large"} -->
            <h3 class="wp-block-heading has-text-align-center has-x-large-font-size">10年</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
            <p class="has-text-align-center has-small-font-size">業界経験</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3,"fontSize":"x-large"} -->
            <h3 class="wp-block-heading has-text-align-center has-x-large-font-size">500社</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
            <p class="has-text-align-center has-small-font-size">取引実績</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3,"fontSize":"x-large"} -->
            <h3 class="wp-block-heading has-text-align-center has-x-large-font-size">98%</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
            <p class="has-text-align-center has-small-font-size">顧客満足度</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"textAlign":"center","level":3,"fontSize":"x-large"} -->
            <h3 class="wp-block-heading has-text-align-center has-x-large-font-size">24時間</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
            <p class="has-text-align-center has-small-font-size">サポート対応</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-quote.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:pullquote -->
    <figure class="wp-block-pullquote"><blockquote><p>印象に残るキーメッセージや、お客様の声の代表的な一文をここに大きく掲載します。</p><cite>出典・発言者名</cite></blockquote></figure>
    <!-- /wp:pullquote -->
</div>
<!-- /wp:group -->';

            case 'layout-banner-cta.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
    <!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
    <div class="wp-block-group alignwide">
        <!-- wp:heading {"level":3} -->
        <h3 class="wp-block-heading">行動を促す一文がここに入ります</h3>
        <!-- /wp:heading -->

        <!-- wp:buttons -->
        <div class="wp-block-buttons">
            <!-- wp:button -->
            <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">詳しく見る</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->';

            case 'layout-contact.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">お問い合わせ</h2>
    <!-- /wp:heading -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-medium-font-size">お電話でのお問い合わせ</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p><strong>0120-000-000</strong><br>受付時間：平日 9:00〜18:00</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-medium-font-size">フォームでのお問い合わせ</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>24時間受付。2営業日以内にご返信します。</p>
            <!-- /wp:paragraph -->

            <!-- wp:buttons -->
            <div class="wp-block-buttons">
                <!-- wp:button -->
                <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">お問い合わせフォームへ</a></div>
                <!-- /wp:button -->
            </div>
            <!-- /wp:buttons -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            case 'layout-steps.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">手順・ステップ</h2>
    <!-- /wp:heading -->

    <!-- wp:list {"ordered":true} -->
    <ol class="wp-block-list"><!-- wp:list-item -->
<li><strong>ステップの見出し</strong> — 手順の説明文が入ります。</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>ステップの見出し</strong> — 手順の説明文が入ります。</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>ステップの見出し</strong> — 手順の説明文が入ります。</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>ステップの見出し</strong> — 手順の説明文が入ります。</li>
<!-- /wp:list-item --></ol>
    <!-- /wp:list -->
</div>
<!-- /wp:group -->';

            case 'layout-table.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">概要・一覧</h2>
    <!-- /wp:heading -->

    <!-- wp:table -->
    <figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td><strong>項目名</strong></td><td>内容が入ります</td></tr><tr><td><strong>項目名</strong></td><td>内容が入ります</td></tr><tr><td><strong>項目名</strong></td><td>内容が入ります</td></tr><tr><td><strong>項目名</strong></td><td>内容が入ります</td></tr></tbody></table></figure>
    <!-- /wp:table -->
</div>
<!-- /wp:group -->';

            case 'layout-accordion.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:details -->
    <details class="wp-block-details"><summary>開閉する見出しが入ります</summary><!-- wp:paragraph -->
<p>クリックで開閉する本文が入ります。詳細情報や補足説明に使います。</p>
<!-- /wp:paragraph --></details>
    <!-- /wp:details -->

    <!-- wp:details -->
    <details class="wp-block-details"><summary>開閉する見出しが入ります</summary><!-- wp:paragraph -->
<p>クリックで開閉する本文が入ります。長いページを整理するのに便利です。</p>
<!-- /wp:paragraph --></details>
    <!-- /wp:details -->

    <!-- wp:details -->
    <details class="wp-block-details"><summary>開閉する見出しが入ります</summary><!-- wp:paragraph -->
<p>クリックで開閉する本文が入ります。</p>
<!-- /wp:paragraph --></details>
    <!-- /wp:details -->
</div>
<!-- /wp:group -->';

            case 'layout-main-aside.html':
                return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column {"width":"66.66%"} -->
        <div class="wp-block-column" style="flex-basis:66.66%">
            <!-- wp:heading {"level":3} -->
            <h3 class="wp-block-heading">メインコンテンツの見出し</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p>ここにメインの本文テキストが入ります。本文を広く取り、右側に補足情報を添える定番の構成です。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"width":"33.33%"} -->
        <div class="wp-block-column" style="flex-basis:33.33%">
            <!-- wp:heading {"level":3,"fontSize":"medium"} -->
            <h3 class="wp-block-heading has-medium-font-size">補足情報</h3>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"fontSize":"small"} -->
            <p class="has-small-font-size">注釈・関連リンク・ポイントなどの補足が入ります。</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

            default:
                return '';
        }
    }

    private function generate_patterns($dir, $selection, $theme_slug)
    {
        $map = array(
            'patterns.hero'         => 'hero.php',
            'patterns.cta'          => 'cta.php',
            'patterns.pricing'      => 'pricing.php',
            'patterns.features'     => 'features.php',
            'patterns.about'        => 'about.php',
            'patterns.testimonials' => 'testimonials.php',
            'patterns.faq'          => 'faq.php',
            'patterns.team'         => 'team.php',
            'patterns.contact'      => 'contact.php',
            'patterns.gallery'      => 'gallery.php',
            'patterns.newsletter'   => 'newsletter.php',
            'patterns.stats'        => 'stats.php',
            'patterns.logos'        => 'logos.php',
            'patterns.latestPosts'  => 'latest-posts.php',
            'patterns.textImage'    => 'text-image.php',
        );

        foreach ($map as $key => $file) {
            if (! empty($selection[$key])) {
                $source = BTS_PLUGIN_DIR . 'patterns/' . $file;
                if (file_exists($source)) {
                    // Rewrite the placeholder slug prefix / category to the
                    // theme slug so patterns are namespaced per theme and the
                    // category registered in functions.php matches.
                    $content = file_get_contents($source);
                    $content = str_replace(
                        array('Slug: themekickstarter/', 'Categories: themekickstarter'),
                        array('Slug: ' . $theme_slug . '/', 'Categories: ' . $theme_slug),
                        $content
                    );
                    file_put_contents($dir . '/' . $file, $content);
                } else {
                    // Fallback stub if file missing
                    $content = "<?php\n/**\n * Title: " . $key . "\n * Slug: " . $theme_slug . "/" . basename($file, '.php') . "\n * Categories: " . $theme_slug . "\n */\n?>";
                    $content .= "<!-- wp:paragraph --><p>Pattern: " . $file . "</p><!-- /wp:paragraph -->";
                    file_put_contents($dir . '/' . $file, $content);
                }
            }
        }
    }
}
