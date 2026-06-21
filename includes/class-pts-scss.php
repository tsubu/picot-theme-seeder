<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * SCSS sources (assets/scss) for generated themes.
 *
 * The generated style.css works without any build step; these files are the
 * editable mirror of the same styles. Compiling them regenerates style.css
 * (and style-editor.css for classic themes):
 *
 *   npm install && npm run build:css
 *
 * NOTE: the classic partials mirror the CSS emitted by
 * PTS_Layout_Settings::get_root_stylesheet(), PTS_Editor_Styles and
 * CTS_Generator::get_base_styles(). Keep them in sync when editing either side.
 */
class PTS_Scss
{

    /**
     * Convert a style.css header comment into a loud comment so it survives
     * compressed Sass output.
     *
     * @param string $style_header Header block starting with "/*".
     * @return string
     */
    public static function loud_header($style_header)
    {
        return preg_replace('/^\/\*/', '/*!', $style_header, 1);
    }

    /**
     * package.json with sass build / watch scripts.
     *
     * "sass assets/scss:." compiles every non-partial file in assets/scss
     * into the theme root (style.scss -> style.css, style-editor.scss ->
     * style-editor.css).
     *
     * @param string $theme_slug Theme slug.
     * @return string
     */
    public static function get_package_json($theme_slug)
    {
        $package = array(
            'name'            => $theme_slug,
            'version'         => '1.0.0',
            'private'         => true,
            'scripts'         => array(
                'build:css' => 'sass assets/scss:. --no-source-map --style=expanded',
                'watch:css' => 'sass --watch assets/scss:. --no-source-map --style=expanded',
            ),
            'devDependencies' => array(
                'sass' => '^1.77.0',
            ),
        );

        return json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /**
     * SCSS sources for a block (FSE) theme.
     *
     * theme.json stays the design source of truth; the optional SCSS sources are for
     * CSS that theme.json cannot express.
     *
     * @param string                $style_header style.css header comment.
     * @param array<string, string> $layout       Parsed layout tokens.
     * @return array<string, string> filename => content.
     */
    public static function get_block_files($style_header, $layout)
    {
        $style  = self::loud_header($style_header) . "\n";
        $style .= "// Source for style.css — compile with: npm run build:css (see package.json).\n";
        $style .= "// Layout, spacing and typography are defined in theme.json.\n";
        $style .= "// Bootstrap is loaded from functions.php, so use these files for helper\n";
        $style .= "// rules and component tweaks that theme.json cannot express.\n\n";
        $style .= "@use 'custom';\n";

        $variables  = "// Design tokens, kept in sync with theme.json (theme.json is the source of truth).\n";
        $variables .= "\$site-max-width: {$layout['siteMaxWidth']};\n";
        $variables .= "\$content-width: {$layout['contentWidth']};\n";
        $variables .= "\$wide-width: {$layout['wideWidth']};\n";
        $variables .= "\$padding-inline: {$layout['paddingInline']};\n";
        $variables .= "\$block-gap: {$layout['blockGap']};\n";

        $custom  = "@use 'variables' as *;\n\n";
        $custom .= ".pts-bootstrap-shell .wp-block-post-template {\n";
        $custom .= "    list-style: none;\n";
        $custom .= "    padding-left: 0;\n";
        $custom .= "}\n\n";
        $custom .= ".pts-bootstrap-card,\n";
        $custom .= ".card,\n";
        $custom .= ".pts-bootstrap-sidebar,\n";
        $custom .= ".pts-selected-content.card,\n";
        $custom .= ".pts-category-featured.card {\n";
        $custom .= "    border-radius: 1rem;\n";
        $custom .= "}\n";

        return array(
            'style.scss'      => $style,
            '_variables.scss' => $variables,
            '_custom.scss'    => $custom,
        );
    }

    /**
     * SCSS sources for a classic theme.
     *
     * @param string                $style_header   style.css header comment.
     * @param array<string, string> $layout         Parsed layout tokens.
     * @param bool                  $include_editor Also generate style-editor.scss.
     * @return array<string, string> filename => content.
     */
    public static function get_classic_files($style_header, $layout, $include_editor = false)
    {
        $style  = self::loud_header($style_header) . "\n";
        $style .= "// Source for style.css — compile with: npm run build:css (see package.json).\n";
        $style .= "// Edit _variables.scss for widths / colors / fonts, the other partials for rules.\n\n";
        $style .= "@use 'layout';\n";
        $style .= "@use 'typography';\n";
        $style .= "@use 'components';\n";

        $files = array(
            'style.scss'       => $style,
            '_variables.scss'  => self::get_classic_variables($layout),
            '_layout.scss'     => self::get_classic_layout(),
            '_typography.scss' => self::get_classic_typography(),
            '_components.scss' => self::get_classic_components(),
        );

        if ($include_editor) {
            $editor  = "// Source for style-editor.css (loaded via add_editor_style).\n";
            $editor .= "// Shares the content styles so the editor matches the frontend.\n\n";
            $editor .= "@use 'variables' as *;\n";
            $editor .= "@use 'typography';\n\n";
            $editor .= "/* Editor canvas (matches content width) */\n";
            $editor .= ".editor-styles-wrapper,\n";
            $editor .= "body.block-editor-page .editor-styles-wrapper {\n";
            $editor .= "    max-width: \$content-width;\n";
            $editor .= "    margin-inline: auto;\n";
            $editor .= "}\n";

            $files['style-editor.scss'] = $editor;
        }

        return $files;
    }

    /**
     * @param array<string, string> $layout Parsed layout tokens.
     * @return string
     */
    private static function get_classic_variables($layout)
    {
        $font = PTS_Editor_Styles::FONT_FAMILY;

        return '// Layout tokens — edit here, then recompile (npm run build:css).
$site-max-width: ' . $layout['siteMaxWidth'] . ';
$content-width: ' . $layout['contentWidth'] . ';
$wide-width: ' . $layout['wideWidth'] . ';
$padding-inline: ' . $layout['paddingInline'] . ';
$block-gap: ' . $layout['blockGap'] . ';

// Typography (quoted string: interpolate with #{...} to keep inner quotes)
$font-family: \'' . $font . '\';
$font-size: 18px;
$line-height: 1.5;
$line-height-paragraph: 1.8;

// Colors
$color-text: #1e1e1e;
$color-link: #0073aa;
$color-link-hover: #005a87;
$color-border: #e5e7eb;
$color-surface: #f9fafb;
$color-muted: #6b7280;
$color-muted-dark: #4b5563;
$color-description: #757575;
';
    }

    /**
     * Site shell — mirrors PTS_Layout_Settings::get_root_stylesheet().
     *
     * @return string
     */
    private static function get_classic_layout()
    {
        return '@use \'variables\' as *;

/* Root layout tokens (Picot Theme Seeder) */
:root {
    --pts-site-max-width: #{$site-max-width};
    --pts-content-width: #{$content-width};
    --pts-wide-width: #{$wide-width};
    --pts-padding-inline: #{$padding-inline};
    --pts-block-gap: #{$block-gap};
}

/* Bootstrap site shell — templates use .container / .row / .col-* */
.site-shell > .container {
    max-width: min(100%, var(--pts-site-max-width));
}

.site-header {
    border-bottom: none;
}

.site-header > .container {
    padding-top: 0.375rem;
    padding-bottom: 0.375rem;
}

.site-shell {
    padding-top: 0;
}

.site-header .site-header-bar {
    gap: 0.5rem;
}

.site-header .site-title,
.site-header .navbar-brand,
.site-header a[rel="home"] {
    font-size: 1.0625rem;
    font-weight: 700;
    line-height: 1.2;
    color: $color-text;
    text-decoration: none;
}

.site-header .site-description {
    display: none;
}

.site-header .custom-logo-link img,
.site-header img.custom-logo {
    max-height: 2rem;
    width: auto;
}

.site-footer a {
    color: $color-muted-dark;

    &:hover {
        color: $color-link;
    }
}

#primary.site-main {
    min-width: 0;
}

#secondary,
#secondary .card,
#secondary .card-body,
#pts-sidebar-offcanvas .offcanvas-body {
    min-width: 0;
    max-width: 100%;
}

.site-main.full-width,
.site-main.no-sidebar,
.site-main.site-main-fullwidth {
    width: 100%;
}

';
    }

    /**
     * Content typography — mirrors PTS_Editor_Styles::get_content_stylesheet().
     *
     * @return string
     */
    private static function get_classic_typography()
    {
        return '@use \'variables\' as *;

/* Typography & spacing (aligned with block editor defaults) */
:root {
    --wp--style--block-gap: #{$block-gap};
    --pts-font-family: #{$font-family};
    --pts-font-size: #{$font-size};
    --pts-line-height: #{$line-height};
    --pts-line-height-paragraph: #{$line-height-paragraph};
    --pts-color-text: #{$color-text};
    --pts-color-link: #{$color-link};
}

body {
    font-family: var(--pts-font-family);
    font-size: var(--pts-font-size);
    line-height: var(--pts-line-height);
    color: var(--pts-color-text);
    -webkit-font-smoothing: antialiased;
    overflow-x: clip;
    max-width: 100%;
}

.site-main,
.entry-content,
.wp-site-blocks {
    font-family: inherit;
    font-size: inherit;
    line-height: inherit;
    color: inherit;
}

/* Block gap between sibling blocks (editor: --wp--style--block-gap: 2em) */
.site-main > * + *,
.entry-content > * + *,
.wp-site-blocks > * + *,
.wp-block-group > * + *,
.wp-block-post-content > * + * {
    margin-block-start: var(--wp--style--block-gap, var(--pts-block-gap, 2em));
}

.site-main > :first-child,
.entry-content > :first-child,
.wp-site-blocks > :first-child {
    margin-block-start: 0;
}

.entry-content p,
.site-main p,
.wp-block-paragraph {
    line-height: var(--pts-line-height-paragraph);
}

.entry-title,
.wp-block-post-title,
.editor-post-title__block {
    font-size: 2.5em;
    font-weight: 800;
    line-height: 1.2;
    margin-top: 2em;
    margin-bottom: 1em;
}

.entry-content h1,
.site-main h1,
.wp-block-heading[style*="level-1"],
h1.wp-block-heading {
    font-size: 2.5em;
    font-weight: 800;
    line-height: 1.2;
}

.entry-content h2,
.site-main h2,
h2.wp-block-heading {
    font-size: 2em;
    font-weight: 700;
    line-height: 1.3;
}

.entry-content h3,
.site-main h3,
h3.wp-block-heading {
    font-size: 1.5em;
    font-weight: 700;
    line-height: 1.4;
}

.entry-content h4,
.site-main h4,
h4.wp-block-heading {
    font-size: 1.25em;
    font-weight: 700;
}

.entry-content h5,
.site-main h5,
h5.wp-block-heading {
    font-size: 1.125em;
    font-weight: 700;
}

.entry-content h6,
.site-main h6,
h6.wp-block-heading {
    font-size: 1em;
    font-weight: 700;
}

.entry-content a:not(.btn),
.site-main a:not(.wp-element-button):not(.btn) {
    color: var(--pts-color-link);
}

.entry-content a:not(.btn):hover,
.site-main a:hover:not(.wp-element-button):not(.btn) {
    color: $color-link-hover;
}

.widget-title {
    font-size: 1.125em;
    font-weight: 600;
    line-height: 1.4;
    margin-bottom: 0.75em;
}
';
    }

    /**
     * Components — mirrors CTS_Generator::get_base_styles().
     *
     * @return string
     */
    private static function get_classic_components()
    {
        return '@use \'variables\' as *;

/* Accessibility + content fallbacks */

/* Skip link */
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

    &:focus {
        position: fixed;
        z-index: 100000;
        top: 5px;
        left: 5px;
        width: auto;
        height: auto;
        padding: 0.75rem 1rem;
        clip: auto;
        background: #f3f4f6;
        color: $color-link;
        text-decoration: none;
        font-weight: 600;
    }
}

/* Content area: images and captions (the_content() output) */
.entry-content img,
.post-thumbnail img {
    max-width: 100%;
    height: auto;
}

.entry-caption {
    font-size: 0.875rem;
    color: $color-muted;
    margin-top: 0.5rem;
}

/* Widget list reset */
.widget ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

/* Search form (Bootstrap classes are added in generated markup) */
.search-form {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: 0.5rem;
    max-width: 100%;
    min-width: 0;

    .search-label {
        min-width: 0;
        flex: 1 1 0%;
    }

    .search-field {
        min-width: 0;
        flex: 1 1 0%;
        width: auto;
        padding: 0.4rem 0.6rem;
        border: 1px solid #d1d5db;
        border-radius: 0;
    }

    .search-submit {
        padding: 0.4rem 1rem;
        border: 1px solid $color-link;
        background: $color-link;
        color: #ffffff;
        cursor: pointer;
        white-space: nowrap;
        flex: 0 0 auto;

        &:hover {
            background: $color-link-hover;
        }
    }
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
    border: 1px solid $color-link;
    background: $color-link;
    color: #ffffff;
    cursor: pointer;

    &:hover {
        background: $color-link-hover;
        border-color: $color-link-hover;
        color: #ffffff;
    }
}

/* Posts navigation / pagination */
.navigation {
    margin-block: 2rem;

    .nav-links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
    }

    &.posts-navigation .nav-links {
        justify-content: space-between;
    }

    .page-numbers.current {
        font-weight: 700;
    }
}

/* Page templates without sidebar use the full layout width */
.site-main.full-width,
.site-main.no-sidebar {
    flex-basis: 100%;
}

/* Front page sections (front-page.php) */
.front-posts-grid > .front-card {
    width: 100%;
    max-width: 100%;
}

.front-card-thumb img {
    display: block;
    width: 100%;
    height: auto;
}

.card,
.entry-card {
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

.front-card .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.08);
    }
}

h3.front-card-title {
    font-size: 1.125em;
    margin: 0.75rem 0 0.25rem;
}

.front-card-excerpt {
    font-size: 0.9375em;
    color: $color-muted-dark;
}
';
    }
}
