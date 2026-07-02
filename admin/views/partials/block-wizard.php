<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="bts-app" class="bts-wrap bts-app-container">
        <!-- Sidebar / Navigation -->
        <div class="bts-sidebar">
            <ul class="bts-steps">
                <li data-step="1" class="active">1. <?php esc_html_e('Basic Info', 'picot-theme-seeder'); ?></li>
                <li data-step="2">2. <?php esc_html_e('Presets', 'picot-theme-seeder'); ?></li>
                <li data-step="3">3. <?php esc_html_e('Features', 'picot-theme-seeder'); ?></li>
                <li data-step="4">4. <?php esc_html_e('Theme JSON', 'picot-theme-seeder'); ?></li>
                <li data-step="5">5. <?php esc_html_e('Preview', 'picot-theme-seeder'); ?></li>
                <li data-step="6">6. <?php esc_html_e('Output', 'picot-theme-seeder'); ?></li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="bts-content">
            <form id="bts-form">

                <!-- Step 1: Basic Info -->
                <div class="bts-step-content active" data-step="1">
                    <h2><?php esc_html_e('Basic Information', 'picot-theme-seeder'); ?></h2>
                    <?php
                    $picotse_basic_info_prefix  = 'bts';
                    $picotse_basic_info_variant = 'block';
                    include PICOTSE_PLUGIN_DIR . 'admin/views/partials/basic-info-fields.php';
                    ?>
                    <button type="button" class="button button-primary next-step"><?php esc_html_e('Next: Presets', 'picot-theme-seeder'); ?></button>
                </div>

                <!-- Step 2: Presets -->
                <div class="bts-step-content" data-step="2">
                    <h2><?php esc_html_e('Select a Preset', 'picot-theme-seeder'); ?></h2>

                    <div class="bts-filters">
                        <button type="button" class="button bts-filter-btn active" data-filter="all"><?php esc_html_e('All', 'picot-theme-seeder'); ?></button>
                        <button type="button" class="button bts-filter-btn" data-filter="L1"><?php esc_html_e('Uses (L1)', 'picot-theme-seeder'); ?></button>
                    </div>

                    <div id="bts-preset-list" class="bts-grid">
                        <!-- Presets injected here via JS -->
                    </div>
                    <div class="bts-actions">
                        <button type="button" class="button prev-step"><?php esc_html_e('Back', 'picot-theme-seeder'); ?></button>
                        <button type="button" class="button button-primary next-step"><?php esc_html_e('Next: Features', 'picot-theme-seeder'); ?></button>
                    </div>
                </div>

                <!-- Step 3: Features -->
                <div class="bts-step-content" data-step="3">
                    <h2><?php esc_html_e('Select Features', 'picot-theme-seeder'); ?></h2>
                    <div class="bts-accordion" id="bts-features-list">
                        <h3><?php esc_html_e('Core Features', 'picot-theme-seeder'); ?></h3>
                        <div>
                            <label><input type="checkbox" class="bts-selection-cb" name="selection[features.generateThemeJson]" value="1" checked> <?php esc_html_e('Generate theme.json', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-selection-cb" name="selection[features.generateTemplates]" value="1" checked> <?php esc_html_e('Generate Templates', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-selection-cb" name="selection[features.generateParts]" value="1" checked> <?php esc_html_e('Generate Parts', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-selection-cb" name="selection[features.generateScss]" value="1" checked> <?php esc_html_e('Generate SCSS sources (assets/scss + package.json)', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-selection-cb" name="selection[features.generateStyleVariations]" value="1" checked> <?php esc_html_e('Generate Style Variations (styles/ — Dark, Natural, Vivid)', 'picot-theme-seeder'); ?></label><br>
                        </div>

                        <?php include PICOTSE_PLUGIN_DIR . 'admin/views/partials/block-layout-mode-fields.php'; ?>

                        <h3><?php esc_html_e('Templates', 'picot-theme-seeder'); ?></h3>
                        <p class="description pts-section-intro"><?php esc_html_e('HTML templates in the templates/ folder. WordPress picks the most specific file for each view (template hierarchy).', 'picot-theme-seeder'); ?></p>
                        <div id="bts-templates-list" class="pts-desc-list">
                            <!-- Template checkboxes -->
                        </div>

                        <h3><?php esc_html_e('Parts', 'picot-theme-seeder'); ?></h3>
                        <p class="description pts-section-intro"><?php esc_html_e('Reusable regions in the parts/ folder (header, footer, etc.). Referenced from templates with the template-part block.', 'picot-theme-seeder'); ?></p>
                        <p class="pts-parts-set">
                            <label>
                                <input type="checkbox" id="bts-parts-set-basic" name="selection[parts.basicSet]" value="1" data-label="<?php echo esc_attr__('Basic Template Parts Set', 'picot-theme-seeder'); ?>" checked>
                                <strong><?php esc_html_e('Basic Template Parts Set', 'picot-theme-seeder'); ?></strong>
                            </label>
                            <span class="description"><?php esc_html_e('Select all recommended parts at once. Unchecking a part below turns the set off.', 'picot-theme-seeder'); ?></span>
                        </p>
                        <div id="bts-parts-list" class="pts-desc-list">
                            <!-- Parts checkboxes -->
                        </div>
                        <p class="pts-parts-set">
                            <label>
                                <input type="checkbox" id="bts-parts-set-extended" name="selection[parts.extendedSet]" value="1" data-label="<?php echo esc_attr__('Extended Layout Parts Set', 'picot-theme-seeder'); ?>">
                                <strong><?php esc_html_e('Extended Layout Parts Set', 'picot-theme-seeder'); ?></strong>
                            </label>
                            <span class="description"><?php esc_html_e('Common site layout variations: more headers and footers, sidebars, post grids, author box, and more.', 'picot-theme-seeder'); ?></span>
                        </p>
                        <div id="bts-parts-extended-list" class="pts-desc-list">
                            <!-- Extended parts checkboxes -->
                        </div>
                        <p class="pts-parts-set">
                            <label>
                                <input type="checkbox" id="bts-parts-set-jplp" name="selection[parts.jpLpSet]" value="1" data-label="<?php echo esc_attr__('Japanese LP Parts Set', 'picot-theme-seeder'); ?>">
                                <strong><?php esc_html_e('Japanese LP Parts Set', 'picot-theme-seeder'); ?></strong>
                            </label>
                            <span class="description"><?php esc_html_e('Landing page sections common on Japanese sites: first view, problems checklist, reasons, customer voices, pricing, FAQ, conversion area, and more (Japanese placeholder text).', 'picot-theme-seeder'); ?></span>
                        </p>
                        <div id="bts-parts-jplp-list" class="pts-desc-list">
                            <!-- Japanese LP parts checkboxes -->
                        </div>
                        <p class="pts-parts-set">
                            <label>
                                <input type="checkbox" id="bts-parts-set-productlp" name="selection[parts.productLpSet]" value="1" data-label="<?php echo esc_attr__('Product LP Parts Set', 'picot-theme-seeder'); ?>">
                                <strong><?php esc_html_e('Product LP Parts Set', 'picot-theme-seeder'); ?></strong>
                            </label>
                            <span class="description"><?php esc_html_e('EC product page sections common on Japanese shopping sites such as Rakuten: sale banner, coupon, double pricing, reviews, specs, gift wrapping, shipping, and more (Japanese placeholder text).', 'picot-theme-seeder'); ?></span>
                        </p>
                        <div id="bts-parts-productlp-list" class="pts-desc-list">
                            <!-- Product LP parts checkboxes -->
                        </div>
                        <p class="pts-parts-set">
                            <label>
                                <input type="checkbox" id="bts-parts-set-layoutkit" name="selection[parts.layoutKitSet]" value="1" data-label="<?php echo esc_attr__('Common Layout Kit', 'picot-theme-seeder'); ?>">
                                <strong><?php esc_html_e('Common Layout Kit', 'picot-theme-seeder'); ?></strong>
                            </label>
                            <span class="description"><?php esc_html_e('Everyday page layouts: text + image, card grids, gallery, timeline, team, stats, steps, table, accordion, and more (Japanese placeholder text and empty image placeholders).', 'picot-theme-seeder'); ?></span>
                        </p>
                        <div id="bts-parts-layoutkit-list" class="pts-desc-list">
                            <!-- Layout kit checkboxes -->
                        </div>

                        <h3><?php esc_html_e('Patterns', 'picot-theme-seeder'); ?></h3>
                        <p class="description pts-patterns-intro"><?php esc_html_e('Reusable block sections for the Site Editor or post editor. Selected files are copied into your theme’s patterns/ folder.', 'picot-theme-seeder'); ?></p>
                        <div id="bts-patterns-list" class="pts-patterns-list">
                            <!-- Patterns checkboxes -->
                        </div>
                    </div>
                    <div class="bts-actions">
                        <button type="button" class="button prev-step"><?php esc_html_e('Back', 'picot-theme-seeder'); ?></button>
                        <button type="button" class="button button-primary next-step"><?php esc_html_e('Next: Theme JSON', 'picot-theme-seeder'); ?></button>
                    </div>
                </div>

                <!-- Step 4: Theme JSON -->
                <div class="bts-step-content" data-step="4">
                    <h2><?php esc_html_e('Theme JSON Settings', 'picot-theme-seeder'); ?></h2>
                    <p><?php esc_html_e('Configure basic settings for your theme.json.', 'picot-theme-seeder'); ?></p>
                    <div class="bts-accordion">
                        <h3><?php esc_html_e('Settings (theme.json v3)', 'picot-theme-seeder'); ?></h3>
                        <div>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][useRootPaddingAwareAlignments]" value="1" checked> <?php esc_html_e('Use Root Padding Aware Alignments', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][appearanceTools]" value="1" checked> <?php esc_html_e('Appearance Tools', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][background][backgroundImage]" value="1" checked> <?php esc_html_e('Background: Background Image', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][background][backgroundSize]" value="1" checked> <?php esc_html_e('Background: Size / Position / Repeat', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][border][color]" value="1" checked> <?php esc_html_e('Border: Color', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][border][radius]" value="1" checked> <?php esc_html_e('Border: Radius', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][border][style]" value="1" checked> <?php esc_html_e('Border: Style', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][border][width]" value="1" checked> <?php esc_html_e('Border: Width', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][dimensions][aspectRatio]" value="1" checked> <?php esc_html_e('Dimensions: Aspect Ratio', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][dimensions][height]" value="1" checked> <?php esc_html_e('Dimensions: Height', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][dimensions][minHeight]" value="1" checked> <?php esc_html_e('Dimensions: Min Height', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][dimensions][width]" value="1" checked> <?php esc_html_e('Dimensions: Width', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][position][sticky]" value="1" checked> <?php esc_html_e('Position: Sticky', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][shadow][defaultPresets]" value="1" checked> <?php esc_html_e('Shadow: Default Presets', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][spacing][blockGap]" value="1" checked> <?php esc_html_e('Spacing: Block Gap', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][spacing][margin]" value="1" checked> <?php esc_html_e('Spacing: Margin', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][spacing][padding]" value="1" checked> <?php esc_html_e('Spacing: Padding', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][spacing][customSpacingSize]" value="1" checked> <?php esc_html_e('Spacing: Custom Size', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][spacing][defaultSpacingSizes]" value="1" checked> <?php esc_html_e('Spacing: Default Presets', 'picot-theme-seeder'); ?></label><br>
                        </div>

                        <h3><?php esc_html_e('Color', 'picot-theme-seeder'); ?></h3>
                        <div>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][background]" value="1" checked> <?php esc_html_e('Background', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][text]" value="1" checked> <?php esc_html_e('Text', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][link]" value="1" checked> <?php esc_html_e('Link', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][heading]" value="1" checked> <?php esc_html_e('Heading', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][button]" value="1" checked> <?php esc_html_e('Button', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][caption]" value="1" checked> <?php esc_html_e('Caption', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][custom]" value="1" checked> <?php esc_html_e('Custom Color Picker', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][customGradient]" value="1" checked> <?php esc_html_e('Custom Gradients', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][customDuotone]" value="1" checked> <?php esc_html_e('Custom Duotone', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][defaultPalette]" value="1" checked> <?php esc_html_e('Default Palette (Core)', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][defaultGradients]" value="1" checked> <?php esc_html_e('Default Gradients (Core)', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][color][defaultDuotone]" value="1" checked> <?php esc_html_e('Default Duotone (Core)', 'picot-theme-seeder'); ?></label><br>
                        </div>

                        <h3><?php esc_html_e('Typography', 'picot-theme-seeder'); ?></h3>
                        <div>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][defaultFontSizes]" value="1" checked> <?php esc_html_e('Default Font Sizes', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][customFontSize]" value="1" checked> <?php esc_html_e('Custom Font Size', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][fontStyle]" value="1" checked> <?php esc_html_e('Font Style', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][fontWeight]" value="1" checked> <?php esc_html_e('Font Weight', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][letterSpacing]" value="1" checked> <?php esc_html_e('Letter Spacing', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][lineHeight]" value="1" checked> <?php esc_html_e('Line Height', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][textAlign]" value="1" checked> <?php esc_html_e('Text Align', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][textDecoration]" value="1" checked> <?php esc_html_e('Text Decoration', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][textTransform]" value="1" checked> <?php esc_html_e('Text Transform', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][dropCap]" value="1" checked> <?php esc_html_e('Drop Cap', 'picot-theme-seeder'); ?></label><br>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][settings][typography][fluid]" value="1" checked> <?php esc_html_e('Fluid Typography', 'picot-theme-seeder'); ?></label><br>
                        </div>

                        <h3><?php esc_html_e('WordPress 7.0+ (theme.json)', 'picot-theme-seeder'); ?></h3>
                        <div>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][picotse][borderRadiusPresets]" value="1" checked> <?php esc_html_e('Border radius presets (radiusSizes)', 'picot-theme-seeder'); ?></label><br>
                            <p class="description"><?php esc_html_e('Predefined border radius values in the editor (WordPress 6.9+).', 'picot-theme-seeder'); ?></p>
                            <label><input type="checkbox" class="bts-themejson-cb" name="params[themeJson][picotse][formElementStyles]" value="1" checked> <?php esc_html_e('Form element styles (select & textInput)', 'picot-theme-seeder'); ?></label><br>
                            <p class="description"><?php esc_html_e('Default styles for select and text inputs via styles.elements (WordPress 6.9+).', 'picot-theme-seeder'); ?></p>
                        </div>

                        <h3><?php esc_html_e('Brand Colors', 'picot-theme-seeder'); ?></h3>
                        <p class="description pts-section-intro"><?php esc_html_e('Written to theme.json as the color palette. Buttons and links use these presets, so the whole theme recolors from the Styles panel later.', 'picot-theme-seeder'); ?></p>
                        <table class="form-table pts-brand-colors-table">
                            <tr>
                                <th><label for="pts-color-primary"><?php esc_html_e('Primary color', 'picot-theme-seeder'); ?></label></th>
                                <td>
                                    <input type="color" id="pts-color-primary" name="params[brandColors][primary]" value="#0073aa">
                                    <p class="description"><?php esc_html_e('Buttons and links. The main brand color.', 'picot-theme-seeder'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="pts-color-secondary"><?php esc_html_e('Secondary color', 'picot-theme-seeder'); ?></label></th>
                                <td>
                                    <input type="color" id="pts-color-secondary" name="params[brandColors][secondary]" value="#005a87">
                                    <p class="description"><?php esc_html_e('Hover states and accents. Usually a darker shade of the primary color.', 'picot-theme-seeder'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="pts-color-background"><?php esc_html_e('Background color', 'picot-theme-seeder'); ?></label></th>
                                <td>
                                    <input type="color" id="pts-color-background" name="params[brandColors][background]" value="#ffffff">
                                    <p class="description"><?php esc_html_e('Site background color.', 'picot-theme-seeder'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="pts-color-text"><?php esc_html_e('Text color', 'picot-theme-seeder'); ?></label></th>
                                <td>
                                    <input type="color" id="pts-color-text" name="params[brandColors][text]" value="#1e1e1e">
                                    <p class="description"><?php esc_html_e('Default body text color.', 'picot-theme-seeder'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <?php include PICOTSE_PLUGIN_DIR . 'admin/views/partials/root-layout-fields.php'; ?>
                    </div>
                    <div class="bts-actions">
                        <button type="button" class="button prev-step"><?php esc_html_e('Back', 'picot-theme-seeder'); ?></button>
                        <button type="button" class="button button-primary next-step"><?php esc_html_e('Next: Preview', 'picot-theme-seeder'); ?></button>
                    </div>
                </div>

                <!-- Step 5: Preview -->
                <div class="bts-step-content" data-step="5">
                    <h2><?php esc_html_e('Preview', 'picot-theme-seeder'); ?></h2>
                    <div id="bts-preview-area">
                        <p><?php esc_html_e('Summary of generated files:', 'picot-theme-seeder'); ?></p>
                        <ul id="bts-file-list"></ul>
                    </div>
                    <div class="bts-actions">
                        <button type="button" class="button prev-step"><?php esc_html_e('Back', 'picot-theme-seeder'); ?></button>
                        <button type="button" class="button button-primary next-step"><?php esc_html_e('Next: Output', 'picot-theme-seeder'); ?></button>
                    </div>
                </div>

                <!-- Step 6: Output -->
                <div class="bts-step-content" data-step="6">
                    <h2><?php esc_html_e('Output', 'picot-theme-seeder'); ?></h2>
                    <p><?php esc_html_e('Generate your ready-to-use WordPress theme.', 'picot-theme-seeder'); ?></p>
                    <p>
                        <label><input type="radio" name="outputMode" value="direct" checked> <?php esc_html_e('Write to themes directory', 'picot-theme-seeder'); ?></label><br>
                        <label><input type="radio" name="outputMode" value="zip"> <?php esc_html_e('Download ZIP', 'picot-theme-seeder'); ?></label>
                    </p>
                    <button type="button" id="bts-generate-btn" class="button button-primary button-hero"><?php esc_html_e('Generate Theme', 'picot-theme-seeder'); ?></button>
                    <div class="bts-actions">
                        <button type="button" class="button prev-step"><?php esc_html_e('Back', 'picot-theme-seeder'); ?></button>
                    </div>
                </div>

            </form>
        </div>
</div>
