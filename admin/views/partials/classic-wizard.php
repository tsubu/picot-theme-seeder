<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="cts-app" class="cts-wrap cts-app-container">
        <!-- Sidebar / Navigation -->
        <div class="cts-sidebar">
            <ul class="cts-steps">
                <li data-step="1" class="active">1. <?php esc_html_e('Basic Info', 'picot-theme-seeder'); ?></li>
                <li data-step="2">2. <?php esc_html_e('Presets', 'picot-theme-seeder'); ?></li>
                <li data-step="3">3. <?php esc_html_e('Templates', 'picot-theme-seeder'); ?></li>
                <li data-step="4">4. <?php esc_html_e('Features', 'picot-theme-seeder'); ?></li>
                <li data-step="5">5. <?php esc_html_e('Preview', 'picot-theme-seeder'); ?></li>
                <li data-step="6">6. <?php esc_html_e('Output', 'picot-theme-seeder'); ?></li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="cts-content">
            <form id="cts-form">

                <!-- Step 1: Basic Info -->
                <div class="cts-step-content active" data-step="1">
                    <h2><?php esc_html_e('Basic Information', 'picot-theme-seeder'); ?></h2>
                    <?php
                    $picotse_basic_info_prefix  = 'cts';
                    $picotse_basic_info_variant = 'classic';
                    include PICOTSE_PLUGIN_DIR . 'admin/views/partials/basic-info-fields.php';
                    ?>
                    <?php include PICOTSE_PLUGIN_DIR . 'admin/views/partials/root-layout-fields.php'; ?>
                    <button type="button" class="button button-primary next-step"><?php esc_html_e('Next: Presets', 'picot-theme-seeder'); ?></button>
                </div>

                <!-- Step 2: Presets -->
                <div class="cts-step-content" data-step="2">
                    <h2><?php esc_html_e('Select a Preset', 'picot-theme-seeder'); ?></h2>
                    <div id="cts-preset-list" class="cts-grid">
                        <!-- Presets injected here via JS -->
                    </div>
                    <div class="cts-actions">
                        <button type="button" class="button prev-step"><?php esc_html_e('Back', 'picot-theme-seeder'); ?></button>
                        <button type="button" class="button button-primary next-step"><?php esc_html_e('Next: Templates', 'picot-theme-seeder'); ?></button>
                    </div>
                </div>

                <!-- Step 3: Templates -->
                <div class="cts-step-content" data-step="3">
                    <h2><?php esc_html_e('Select Templates', 'picot-theme-seeder'); ?></h2>
                    <?php include PICOTSE_PLUGIN_DIR . 'admin/views/partials/block-layout-mode-fields.php'; ?>
                    <p class="description pts-section-intro"><?php esc_html_e('PHP template files in your theme folder. WordPress uses the template hierarchy to pick the best match for each view.', 'picot-theme-seeder'); ?></p>
                    <div id="cts-templates-panel" class="pts-desc-list"></div>
                    <div class="cts-actions">
                        <button type="button" class="button prev-step"><?php esc_html_e('Back', 'picot-theme-seeder'); ?></button>
                        <button type="button" class="button button-primary next-step"><?php esc_html_e('Next: Features', 'picot-theme-seeder'); ?></button>
                    </div>
                </div>


                <!-- Step 4: Features -->
                <div class="cts-step-content" data-step="4">
                    <h2><?php esc_html_e('Theme Features (functions.php)', 'picot-theme-seeder'); ?></h2>
                    <p class="description pts-section-intro"><?php esc_html_e('Optional code added to functions.php (theme support, menus, security, block editor, etc.).', 'picot-theme-seeder'); ?></p>
                    <div id="cts-features-panel" class="pts-desc-list"></div>
                    <div class="cts-actions">
                        <button type="button" class="button prev-step"><?php esc_html_e('Back', 'picot-theme-seeder'); ?></button>
                        <button type="button" class="button button-primary next-step"><?php esc_html_e('Next: Preview', 'picot-theme-seeder'); ?></button>
                    </div>
                </div>

                <!-- Step 5: Preview -->
                <div class="cts-step-content" data-step="5">
                    <h2><?php esc_html_e('Preview', 'picot-theme-seeder'); ?></h2>
                    <div id="cts-preview-area">
                        <p><strong><?php esc_html_e('Summary of generated files:', 'picot-theme-seeder'); ?></strong></p>
                        <ul id="cts-file-list" class="cts-preview-list"></ul>
                        
                        <p><strong><?php esc_html_e('Enabled Features:', 'picot-theme-seeder'); ?></strong></p>
                        <ul id="cts-feature-list" class="cts-preview-list"></ul>
                    </div>
                    <div class="cts-actions">
                        <button type="button" class="button prev-step"><?php esc_html_e('Back', 'picot-theme-seeder'); ?></button>
                        <button type="button" class="button button-primary next-step"><?php esc_html_e('Next: Output', 'picot-theme-seeder'); ?></button>
                    </div>
                </div>

                <!-- Step 6: Output -->
                <div class="cts-step-content" data-step="6">
                    <h2><?php esc_html_e('Output', 'picot-theme-seeder'); ?></h2>
                    <p>
                        <label><input type="radio" name="outputMode" value="zip"> <?php esc_html_e('Download ZIP', 'picot-theme-seeder'); ?></label><br>
                        <label><input type="radio" name="outputMode" value="direct" checked> <?php esc_html_e('Write to themes directory', 'picot-theme-seeder'); ?></label>
                    </p>
                    <button type="button" id="cts-generate-btn" class="button button-primary button-hero"><?php esc_html_e('Generate Theme', 'picot-theme-seeder'); ?></button>
                    <div class="cts-actions">
                        <button type="button" class="button prev-step"><?php esc_html_e('Back', 'picot-theme-seeder'); ?></button>
                    </div>
                </div>

            </form>
        </div>
</div>
