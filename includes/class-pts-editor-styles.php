<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Typography and spacing aligned with the block editor defaults.
 *
 * @see wp-includes/css/dist/block-editor/default-editor-styles.css
 */
class PTS_Editor_Styles
{

    /**
     * System font stack used in the block editor canvas.
     */
    const FONT_FAMILY = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';

    /**
     * @return array<string, mixed>
     */
    public static function get_theme_json_typography_settings()
    {
        return array(
            'typography' => array(
                'defaultFontSizes' => false,
                'fontFamilies'     => array(
                    array(
                        'fontFamily' => self::FONT_FAMILY,
                        'name'       => 'System Font',
                        'slug'       => 'system-font',
                    ),
                ),
                'fontSizes'        => array(
                    array(
                        'name' => 'Small',
                        'size' => '0.875rem',
                        'slug' => 'small',
                    ),
                    array(
                        'name' => 'Medium',
                        'size' => '1.125rem',
                        'slug' => 'medium',
                    ),
                    array(
                        'name' => 'Large',
                        'size' => '1.75rem',
                        'slug' => 'large',
                    ),
                    array(
                        'name' => 'Extra Large',
                        'size' => '2.25rem',
                        'slug' => 'x-large',
                    ),
                ),
            ),
        );
    }

    /**
     * Base global styles for theme.json (matches editor body).
     *
     * @param array<string, string> $layout Parsed layout.
     * @return array<string, mixed>
     */
    public static function get_theme_json_typography_styles($layout)
    {
        $block_gap = $layout['blockGap'];

        return array(
            'typography' => array(
                'fontFamily' => 'var(--wp--preset--font-family--system-font)',
                'fontSize'   => 'var(--wp--preset--font-size--medium)',
                'lineHeight' => '1.5',
            ),
            'spacing'    => array(
                'blockGap' => $block_gap,
                'padding'  => array(
                    'left'  => $layout['paddingInline'],
                    'right' => $layout['paddingInline'],
                ),
            ),
            'color'      => array(
                'text' => '#1e1e1e',
            ),
            'elements'   => array(
                'heading' => array(
                    'typography' => array(
                        'fontWeight' => '700',
                        'lineHeight' => '1.3',
                    ),
                ),
                'h1'      => array(
                    'typography' => array(
                        'fontSize'   => '2.5em',
                        'fontWeight' => '800',
                        'lineHeight' => '1.2',
                    ),
                    'spacing'    => array(
                        'margin' => array(
                            'top'    => '2em',
                            'bottom' => '1em',
                        ),
                    ),
                ),
                'h2'      => array(
                    'typography' => array(
                        'fontSize'   => '2em',
                        'fontWeight' => '700',
                    ),
                ),
                'h3'      => array(
                    'typography' => array(
                        'fontSize' => '1.5em',
                    ),
                ),
                'h4'      => array(
                    'typography' => array(
                        'fontSize' => '1.25em',
                    ),
                ),
                'h5'      => array(
                    'typography' => array(
                        'fontSize' => '1.125em',
                    ),
                ),
                'h6'      => array(
                    'typography' => array(
                        'fontSize' => '1em',
                    ),
                ),
                'link'    => array(
                    'color' => array(
                        'text' => '#0073aa',
                    ),
                ),
            ),
        );
    }

    /**
     * Frontend / editor stylesheet fragment (classic style.css & block style.css).
     *
     * @param array<string, string> $layout Parsed layout.
     * @return string
     */
    public static function get_content_stylesheet($layout)
    {
        $block_gap = $layout['blockGap'];
        $font      = self::FONT_FAMILY;

        return '/* Typography & spacing (aligned with block editor defaults) */
:root {
    --wp--style--block-gap: ' . $block_gap . ';
    --pts-font-family: ' . $font . ';
    --pts-font-size: 18px;
    --pts-line-height: 1.5;
    --pts-line-height-paragraph: 1.8;
    --pts-color-text: #1e1e1e;
    --pts-color-link: #0073aa;
}

body {
    font-family: var(--pts-font-family);
    font-size: var(--pts-font-size);
    line-height: var(--pts-line-height);
    color: var(--pts-color-text);
    -webkit-font-smoothing: antialiased;
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
    color: #005a87;
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
     * Classic theme editor stylesheet (add_editor_style).
     *
     * @param array<string, string> $layout Parsed layout.
     * @return string
     */
    public static function get_editor_stylesheet($layout)
    {
        $content_width = $layout['contentWidth'];

        return self::get_content_stylesheet($layout) . '
/* Editor canvas (matches content width) */
.editor-styles-wrapper,
body.block-editor-page .editor-styles-wrapper {
    max-width: ' . $content_width . ';
    margin-inline: auto;
}
';
    }
}
