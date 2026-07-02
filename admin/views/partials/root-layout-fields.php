<?php
if (! defined('ABSPATH')) {
    exit;
}

$pts_layout_defaults = Picotse_Layout_Settings::get_defaults();
$pts_layout_prefix   = isset($pts_layout_prefix) ? $pts_layout_prefix : 'params[rootLayout]';
?>
<div class="pts-root-layout">
    <h3><?php esc_html_e('Root layout (:root)', 'picot-theme-seeder'); ?></h3>
    <p class="description pts-section-intro"><?php esc_html_e('Width and spacing tokens written to :root CSS variables. Classic themes use them in style.css; block themes also map content/wide sizes to theme.json.', 'picot-theme-seeder'); ?></p>
    <table class="form-table pts-root-layout-table">
        <tr>
            <th><label for="pts-siteMaxWidth"><?php esc_html_e('Site max width', 'picot-theme-seeder'); ?></label></th>
            <td>
                <input type="text" id="pts-siteMaxWidth" name="<?php echo esc_attr($pts_layout_prefix); ?>[siteMaxWidth]" value="<?php echo esc_attr($pts_layout_defaults['siteMaxWidth']); ?>" class="regular-text">
                <p class="description"><?php esc_html_e('Maximum width of the site shell (header, main, footer).', 'picot-theme-seeder'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="pts-contentWidth"><?php esc_html_e('Content width', 'picot-theme-seeder'); ?></label></th>
            <td>
                <input type="text" id="pts-contentWidth" name="<?php echo esc_attr($pts_layout_prefix); ?>[contentWidth]" value="<?php echo esc_attr($pts_layout_defaults['contentWidth']); ?>" class="regular-text pts-sync-content-size">
                <p class="description"><?php esc_html_e('Main column width. Block themes: theme.json settings.layout.contentSize.', 'picot-theme-seeder'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="pts-wideWidth"><?php esc_html_e('Wide width', 'picot-theme-seeder'); ?></label></th>
            <td>
                <input type="text" id="pts-wideWidth" name="<?php echo esc_attr($pts_layout_prefix); ?>[wideWidth]" value="<?php echo esc_attr($pts_layout_defaults['wideWidth']); ?>" class="regular-text pts-sync-wide-size">
                <p class="description"><?php esc_html_e('Wide alignment width. Block themes: theme.json settings.layout.wideSize.', 'picot-theme-seeder'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="pts-paddingInline"><?php esc_html_e('Root padding (inline)', 'picot-theme-seeder'); ?></label></th>
            <td>
                <input type="text" id="pts-paddingInline" name="<?php echo esc_attr($pts_layout_prefix); ?>[paddingInline]" value="<?php echo esc_attr($pts_layout_defaults['paddingInline']); ?>" class="regular-text">
                <p class="description"><?php esc_html_e('Horizontal padding applied to the site shell.', 'picot-theme-seeder'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="pts-blockGap"><?php esc_html_e('Block / column gap', 'picot-theme-seeder'); ?></label></th>
            <td>
                <input type="text" id="pts-blockGap" name="<?php echo esc_attr($pts_layout_prefix); ?>[blockGap]" value="<?php echo esc_attr($pts_layout_defaults['blockGap']); ?>" class="regular-text">
                <p class="description"><?php esc_html_e('Gap between main content and sidebar, and block spacing where supported.', 'picot-theme-seeder'); ?></p>
            </td>
        </tr>
    </table>
</div>
