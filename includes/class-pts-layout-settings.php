<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Root layout widths and spacing for generated themes.
 */
class PTS_Layout_Settings
{

    /**
     * Default layout values.
     *
     * @return array<string, string>
     */
    public static function get_defaults()
    {
        return array(
            'siteMaxWidth'   => '1280px',
            'contentWidth'   => '840px',
            'wideWidth'      => '1200px',
            'paddingInline'  => '1rem',
            'blockGap'       => '2em',
        );
    }

    /**
     * Parse layout from REST / form payload.
     *
     * @param array<string, mixed> $data Request body.
     * @return array<string, string>
     */
    public static function parse($data)
    {
        $defaults = self::get_defaults();
        $layout   = $defaults;

        $params = isset($data['params']) && is_array($data['params']) ? $data['params'] : array();

        if (! empty($params['rootLayout']) && is_array($params['rootLayout'])) {
            $layout = array_merge($layout, self::sanitize_layout($params['rootLayout']));
        }

        // Block wizard legacy fields under themeJson.settings.layout.
        if (! empty($params['themeJson']['settings']['layout']) && is_array($params['themeJson']['settings']['layout'])) {
            $legacy = $params['themeJson']['settings']['layout'];
            if (! empty($legacy['contentSize'])) {
                $layout['contentWidth'] = self::sanitize_css_size($legacy['contentSize'], $layout['contentWidth']);
            }
            if (! empty($legacy['wideSize'])) {
                $layout['wideWidth'] = self::sanitize_css_size($legacy['wideSize'], $layout['wideWidth']);
            }
        }

        return $layout;
    }

    /**
     * Default block theme column layout for generated templates.
     *
     * @return string one-column|two-column
     */
    public static function get_default_layout_mode()
    {
        return 'one-column';
    }

    /**
     * Parse block theme default column layout from REST / form payload.
     *
     * @param array<string, mixed> $data Request body.
     * @return string one-column|two-column
     */
    public static function parse_layout_mode($data)
    {
        $params = isset($data['params']) && is_array($data['params']) ? $data['params'] : array();
        $mode   = isset($params['layoutMode']) ? sanitize_key($params['layoutMode']) : self::get_default_layout_mode();

        if (! in_array($mode, array('one-column', 'two-column'), true)) {
            return self::get_default_layout_mode();
        }

        return $mode;
    }

    /**
     * @param array<string, mixed> $raw Raw layout fields.
     * @return array<string, string>
     */
    private static function sanitize_layout($raw)
    {
        $clean    = array();
        $defaults = self::get_defaults();

        foreach ($defaults as $key => $default) {
            if (! isset($raw[ $key ])) {
                continue;
            }
            $clean[ $key ] = self::sanitize_css_size((string) $raw[ $key ], $default);
        }

        return $clean;
    }

    /**
     * @param string $value User input.
     * @param string $fallback Default if invalid.
     * @return string
     */
    private static function sanitize_css_size($value, $fallback)
    {
        $value = trim(sanitize_text_field($value));
        if ($value === '') {
            return $fallback;
        }
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|%|vw|vh|ch)$/', $value)) {
            return $value;
        }
        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . 'px';
        }
        return $fallback;
    }

    /**
     * :root custom properties and layout shell styles.
     *
     * @param array<string, string> $layout Parsed layout.
     * @return string
     */
    public static function get_root_stylesheet($layout)
    {
        $site_max  = $layout['siteMaxWidth'];
        $content   = $layout['contentWidth'];
        $wide      = $layout['wideWidth'];
        $padding   = $layout['paddingInline'];
        $block_gap = $layout['blockGap'];

        $css = '/* Root layout tokens (Picot Theme Seeder) */
:root {
    --pts-site-max-width: ' . $site_max . ';
    --pts-content-width: ' . $content . ';
    --pts-wide-width: ' . $wide . ';
    --pts-padding-inline: ' . $padding . ';
    --pts-block-gap: ' . $block_gap . ';
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
    color: #1e1e1e;
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
    color: #4b5563;
}

.site-footer a:hover {
    color: #0073aa;
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
        $css .= PTS_Editor_Styles::get_content_stylesheet($layout);

        return $css;
    }
}
