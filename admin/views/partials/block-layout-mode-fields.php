<?php
if (! defined('ABSPATH')) {
    exit;
}

$pts_layout_mode = isset($pts_layout_mode) ? $pts_layout_mode : PTS_Layout_Settings::get_default_layout_mode();
?>
<fieldset class="pts-layout-mode">
    <legend><?php esc_html_e('Default column layout', 'picot-theme-seeder'); ?></legend>
    <p class="description pts-section-intro"><?php esc_html_e('Sets the initial layout for generated templates. Sidebar files and per-page layout templates are still included so you can switch layouts later.', 'picot-theme-seeder'); ?></p>
    <label>
        <input type="radio" name="params[layoutMode]" value="one-column" class="bts-layout-mode-radio" <?php checked($pts_layout_mode, 'one-column'); ?>>
        <?php esc_html_e('1 column (main content only)', 'picot-theme-seeder'); ?>
    </label>
    <br>
    <label>
        <input type="radio" name="params[layoutMode]" value="two-column" class="bts-layout-mode-radio" <?php checked($pts_layout_mode, 'two-column'); ?>>
        <?php esc_html_e('2 columns (main content + sidebar)', 'picot-theme-seeder'); ?>
    </label>
</fieldset>
