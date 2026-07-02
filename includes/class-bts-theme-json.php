<?php

/**
 * Theme JSON Builder
 */
class Picotse_Block_Theme_JSON
{
    public function build($params)
    {
        $theme_json = array(
            '$schema' => 'https://schemas.wp.org/trunk/theme.json',
            'version' => 3,
            'settings' => array(),
            'styles'  => array(),
        );

        if (isset($params['themeJson']['settings'])) {
            $input_settings = $params['themeJson']['settings'];

            // Recursive function to map "1" to true
            $processed_settings = $this->process_settings($input_settings);

            // Merge into main settings
            $theme_json['settings'] = array_merge($theme_json['settings'], $processed_settings);
        }

        $picotse = array();
        if (isset($params['themeJson']['picotse']) && is_array($params['themeJson']['picotse'])) {
            $picotse = $this->process_picotse_flags($params['themeJson']['picotse']);
        }

        if (! empty($picotse['borderRadiusPresets'])) {
            if (! isset($theme_json['settings']['border']) || ! is_array($theme_json['settings']['border'])) {
                $theme_json['settings']['border'] = array();
            }
            $theme_json['settings']['border']['radiusSizes'] = $this->get_default_border_radius_sizes();
        }

        if (! empty($picotse['formElementStyles'])) {
            $form_elements = $this->get_default_form_element_styles();
            if (! isset($theme_json['styles']['elements']) || ! is_array($theme_json['styles']['elements'])) {
                $theme_json['styles']['elements'] = array();
            }
            $theme_json['styles']['elements'] = array_replace_recursive(
                $theme_json['styles']['elements'],
                $form_elements
            );
        }

        $layout = Picotse_Layout_Settings::parse(array('params' => $params));

        $theme_json['settings'] = array_replace_recursive(
            Picotse_Editor_Styles::get_theme_json_typography_settings(),
            $theme_json['settings']
        );

        $theme_json['settings']['layout'] = array(
            'contentSize' => $layout['contentWidth'],
            'wideSize'    => $layout['wideWidth'],
        );

        if (! isset($theme_json['settings']['custom']) || ! is_array($theme_json['settings']['custom'])) {
            $theme_json['settings']['custom'] = array();
        }
        $theme_json['settings']['custom']['layout'] = array(
            'site-max' => $layout['siteMaxWidth'],
        );

        $theme_json['styles'] = array_replace_recursive(
            Picotse_Editor_Styles::get_theme_json_typography_styles($layout),
            $theme_json['styles']
        );

        // Brand color palette: presets + wire links / buttons / body colors to
        // them, so the whole look recolors from the Styles UI in one place.
        $brand = $this->get_brand_colors($params);
        if (! empty($brand)) {
            $theme_json['settings']['color']['palette'] = array(
                array('name' => 'Primary', 'slug' => 'primary', 'color' => $brand['primary']),
                array('name' => 'Secondary', 'slug' => 'secondary', 'color' => $brand['secondary']),
                array('name' => 'Background', 'slug' => 'background', 'color' => $brand['background']),
                array('name' => 'Text', 'slug' => 'text', 'color' => $brand['text']),
            );

            $theme_json['styles'] = array_replace_recursive(
                $theme_json['styles'],
                array(
                    'color'    => array(
                        'background' => 'var(--wp--preset--color--background)',
                        'text'       => 'var(--wp--preset--color--text)',
                    ),
                    'elements' => array(
                        'link'   => array(
                            'color' => array(
                                'text' => 'var(--wp--preset--color--primary)',
                            ),
                            ':hover' => array(
                                'color' => array(
                                    'text' => 'var(--wp--preset--color--secondary)',
                                ),
                            ),
                        ),
                        'button' => array(
                            'border' => array(
                                'radius' => '999px',
                            ),
                            'color' => array(
                                'background' => 'var(--wp--preset--color--primary)',
                                'text'       => '#ffffff',
                            ),
                            ':hover' => array(
                                'color' => array(
                                    'background' => 'var(--wp--preset--color--secondary)',
                                    'text'       => '#ffffff',
                                ),
                            ),
                        ),
                    ),
                )
            );
        }

        return $theme_json;
    }

    /**
     * Sanitized brand colors from the wizard (#rrggbb each), with defaults
     * matching the previous fixed look.
     *
     * @param array<string, mixed> $params Wizard params.
     * @return array<string, string>
     */
    private function get_brand_colors($params)
    {
        $defaults = array(
            'primary'    => '#0073aa',
            'secondary'  => '#005a87',
            'background' => '#ffffff',
            'text'       => '#1e1e1e',
        );

        $raw   = isset($params['brandColors']) && is_array($params['brandColors']) ? $params['brandColors'] : array();
        $clean = array();

        foreach ($defaults as $key => $default) {
            $value = isset($raw[ $key ]) ? trim((string) $raw[ $key ]) : '';
            $clean[ $key ] = preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $default;
        }

        return $clean;
    }

    /**
     * Border radius presets (WordPress 6.9+ theme.json).
     *
     * @return array<int, array<string, string>>
     */
    private function get_default_border_radius_sizes()
    {
        return array(
            array(
                'name' => 'Small',
                'slug' => 'small',
                'size' => '4px',
            ),
            array(
                'name' => 'Medium',
                'slug' => 'medium',
                'size' => '8px',
            ),
            array(
                'name' => 'Large',
                'slug' => 'large',
                'size' => '16px',
            ),
            array(
                'name' => 'Full',
                'slug' => 'full',
                'size' => '9999px',
            ),
        );
    }

    /**
     * Form element styles for select and text inputs (WordPress 6.9+ theme.json).
     *
     * @return array<string, array<string, mixed>>
     */
    private function get_default_form_element_styles()
    {
        $shared = array(
            'border'  => array(
                'color'  => '#d5dae2',
                'style'  => 'solid',
                'width'  => '1px',
                'radius' => '0',
            ),
            'color'   => array(
                'background' => '#ffffff',
                'text'       => '#1e1e1e',
            ),
            'spacing' => array(
                'padding' => array(
                    'top'    => '0.5rem',
                    'bottom' => '0.5rem',
                    'left'   => '0.75rem',
                    'right'  => '0.75rem',
                ),
            ),
        );

        return array(
            'select'    => $shared,
            'textInput' => $shared,
        );
    }

    /**
     * @param array<string, mixed> $picotse Raw picotse flags from the wizard.
     * @return array<string, bool>
     */
    private function process_picotse_flags($picotse)
    {
        $clean = array();
        foreach ($picotse as $key => $value) {
            $clean[ $key ] = ( $value === '1' || $value === true );
        }
        return $clean;
    }

    /**
     * @param array<string, mixed> $settings Settings tree from the wizard.
     * @return array<string, mixed>
     */
    private function process_settings($settings)
    {
        $clean = array();
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $clean[ $key ] = $this->process_settings($value);
            } else {
                // If checkbox value is "1", convert to boolean true
                // If it's a text input (like layout sizes), keep as is
                if ($value === '1') {
                    $clean[ $key ] = true;
                } else {
                    $clean[ $key ] = sanitize_text_field($value);
                }
            }
        }
        return $clean;
    }
}
