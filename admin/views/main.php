<?php

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap pts-wrap">
    <h1><?php esc_html_e('Picot Theme Seeder', 'picot-theme-seeder'); ?></h1>
    <p class="pts-intro"><?php esc_html_e('Create complete, ready-to-use Block (FSE) or Classic WordPress themes from a visual admin wizard.', 'picot-theme-seeder'); ?></p>

    <div id="pts-step-type" class="pts-step-type">
        <h2><?php esc_html_e('Choose Theme Type', 'picot-theme-seeder'); ?></h2>
        <p class="description"><?php esc_html_e('Select the kind of theme you want to generate. You can change this later before generating files.', 'picot-theme-seeder'); ?></p>

        <div class="pts-type-grid">
            <button type="button" class="pts-type-card" data-theme-type="block">
                <span class="dashicons dashicons-layout"></span>
                <strong><?php esc_html_e('Block Theme (FSE)', 'picot-theme-seeder'); ?></strong>
                <span><?php esc_html_e('theme.json, HTML templates, template parts, block patterns', 'picot-theme-seeder'); ?></span>
            </button>
            <button type="button" class="pts-type-card" data-theme-type="classic">
                <span class="dashicons dashicons-editor-code"></span>
                <strong><?php esc_html_e('Classic Theme', 'picot-theme-seeder'); ?></strong>
                <span><?php esc_html_e('PHP templates, functions.php, style.css, sidebars', 'picot-theme-seeder'); ?></span>
            </button>
        </div>

        <p id="pts-type-hint" class="pts-type-hint" hidden></p>
        <p class="pts-type-actions">
            <button type="button" id="pts-continue-btn" class="button button-primary button-hero" disabled>
                <?php esc_html_e('Continue', 'picot-theme-seeder'); ?>
            </button>
        </p>
    </div>

    <div id="pts-wizard-block" class="pts-wizard-panel" hidden>
        <p class="pts-wizard-toolbar">
            <button type="button" class="button pts-change-type"><?php esc_html_e('Change theme type', 'picot-theme-seeder'); ?></button>
            <span class="pts-badge pts-badge-block"><?php esc_html_e('Block Theme', 'picot-theme-seeder'); ?></span>
        </p>
        <?php include PICOTSE_PLUGIN_DIR . 'admin/views/partials/block-wizard.php'; ?>
    </div>

    <div id="pts-wizard-classic" class="pts-wizard-panel" hidden>
        <p class="pts-wizard-toolbar">
            <button type="button" class="button pts-change-type"><?php esc_html_e('Change theme type', 'picot-theme-seeder'); ?></button>
            <span class="pts-badge pts-badge-classic"><?php esc_html_e('Classic Theme', 'picot-theme-seeder'); ?></span>
        </p>
        <?php include PICOTSE_PLUGIN_DIR . 'admin/views/partials/classic-wizard.php'; ?>
    </div>
</div>
