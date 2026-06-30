<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Classic theme wizard labels and descriptions for the admin UI.
 */
class PTS_Classic_Definitions
{

    /**
     * Basic information form fields (label, input, description per row).
     *
     * @param string $variant Wizard variant: classic or block.
     * @return array<string, array<string, mixed>>
     */
    public static function get_basic_info_fields($variant = 'classic')
    {
        $is_block = ('block' === $variant);

        return array(
            'themeName' => array(
                'label'       => __('Theme Name', 'picot-theme-seeder'),
                'type'        => 'text',
                'placeholder' => $is_block ? 'My Awesome Theme' : 'My Classic Theme',
                'required'    => true,
                'description' => __('The display name in Appearance → Themes. Use your project or brand name.', 'picot-theme-seeder'),
            ),
            'themeSlug' => array(
                'label'       => __('Theme Slug', 'picot-theme-seeder'),
                'type'        => 'text',
                'placeholder' => $is_block ? 'my-awesome-theme' : 'my-classic-theme',
                'required'    => true,
                'description' => __('Folder name under wp-content/themes/ (lowercase letters, numbers, hyphens only). Cannot be changed easily later.', 'picot-theme-seeder'),
            ),
            'themeAuthor' => array(
                'label'       => __('Author', 'picot-theme-seeder'),
                'type'        => 'text',
                'description' => $is_block
                    ? __('Written into style.css theme metadata. Your name or company name.', 'picot-theme-seeder')
                    : __('Written into style.css. Your name or company name.', 'picot-theme-seeder'),
            ),
            'themeAuthorUri' => array(
                'label'       => __('Author URI', 'picot-theme-seeder'),
                'type'        => 'url',
                'placeholder' => 'https://example.com',
                'description' => __('Optional link to your website, shown in the theme header metadata.', 'picot-theme-seeder'),
            ),
            'themeDescription' => array(
                'label'       => __('Description', 'picot-theme-seeder'),
                'type'        => 'textarea',
                'rows'        => 3,
                'description' => $is_block
                    ? __('Short summary for the theme list and style.css. Shown in Site Editor theme details.', 'picot-theme-seeder')
                    : __('Short summary of the theme for the theme list and style.css header.', 'picot-theme-seeder'),
            ),
        );
    }

    /**
     * Template groups for step 3.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_template_groups()
    {
        return array(
            array(
                'title' => __('Basic Parts', 'picot-theme-seeder'),
                'items' => array(
                    self::tpl('templates.index', 'index.php', __('The main template file (Required)', 'picot-theme-seeder'), true, true),
                    self::tpl('templates.header', 'header.php', __('Document head and navigation area', 'picot-theme-seeder')),
                    self::tpl('templates.footer', 'footer.php', __('Site footer and script loading', 'picot-theme-seeder')),
                    self::tpl('templates.sidebar', 'sidebar.php', __('Widget area for the sidebar. Synced with the default column layout above; always generated when menus, widgets, or page templates are enabled so you can switch layouts later.', 'picot-theme-seeder'), false, false, false),
                    self::tpl('templates.comments', 'comments.php', __('Template for displaying comments', 'picot-theme-seeder')),
                    self::tpl('templates.searchform', 'searchform.php', __('Custom search form template', 'picot-theme-seeder')),
                    self::tpl('templates.embed', 'embed.php', __('Custom display for embedded posts', 'picot-theme-seeder')),
                ),
            ),
            array(
                'title' => __('Singular Templates', 'picot-theme-seeder'),
                'items' => array(
                    self::tpl('templates.single', 'single.php', __('Template for single blog posts', 'picot-theme-seeder')),
                    self::tpl('templates.page', 'page.php', __('Template for static pages', 'picot-theme-seeder')),
                    self::tpl('templates.singular', 'singular.php', __('Fallback for both single posts and pages', 'picot-theme-seeder')),
                    self::tpl('templates.attachment', 'attachment.php', __('Template for media attachment pages', 'picot-theme-seeder')),
                    self::tpl('templates.image', 'image.php', __('Specialized template for image attachments', 'picot-theme-seeder')),
                    self::tpl('templates.video', 'video.php', __('Specialized template for video attachments', 'picot-theme-seeder')),
                    self::tpl('templates.audio', 'audio.php', __('Specialized template for audio attachments', 'picot-theme-seeder')),
                    self::tpl('templates.privacy-policy', 'privacy-policy.php', __('Template for the privacy policy page', 'picot-theme-seeder')),
                ),
            ),
            array(
                'title' => __('Custom Page Templates', 'picot-theme-seeder'),
                'items' => array(
                    self::tpl('templates.template-full-width', 'template-full-width.php', __('Full-width layout without sidebars. Included automatically when Page is enabled; selectable per page as a custom template.', 'picot-theme-seeder'), false, false, false),
                    self::tpl('templates.template-no-sidebar', 'template-no-sidebar.php', __('Content-only layout without sidebars. Included automatically when Page is enabled; selectable per page as a custom template.', 'picot-theme-seeder'), false, false, false),
                ),
            ),
            array(
                'title' => __('Archive Templates', 'picot-theme-seeder'),
                'items' => array(
                    self::tpl('templates.archive', 'archive.php', __('General archive (categories, tags, dates)', 'picot-theme-seeder')),
                    self::tpl('templates.category', 'category.php', __('Template for category archives', 'picot-theme-seeder')),
                    self::tpl('templates.tag', 'tag.php', __('Template for tag archives', 'picot-theme-seeder')),
                    self::tpl('templates.taxonomy', 'taxonomy.php', __('Template for custom taxonomy archives', 'picot-theme-seeder')),
                    self::tpl('templates.author', 'author.php', __('Template for author-specific posts', 'picot-theme-seeder')),
                    self::tpl('templates.date', 'date.php', __('Template for date-based archives', 'picot-theme-seeder')),
                    self::tpl('templates.paged', 'paged.php', __('Template for paginated archive results', 'picot-theme-seeder')),
                ),
            ),
            array(
                'title' => __('Template Parts', 'picot-theme-seeder'),
                'items' => array(
                    self::tpl('templates.parts-content', 'template-parts/content.php', __('The primary loop content template', 'picot-theme-seeder')),
                    self::tpl('templates.parts-content-single', 'template-parts/content-single.php', __('Content template for single posts', 'picot-theme-seeder')),
                    self::tpl('templates.parts-content-page', 'template-parts/content-page.php', __('Content template for pages', 'picot-theme-seeder')),
                    self::tpl('templates.parts-content-search', 'template-parts/content-search.php', __('Content template for search results', 'picot-theme-seeder')),
                    self::tpl('templates.parts-content-none', 'template-parts/content-none.php', __('Template for when no results are found', 'picot-theme-seeder')),
                ),
            ),
            array(
                'title' => __('Other Templates', 'picot-theme-seeder'),
                'items' => array(
                    self::tpl('templates.front-page', 'front-page.php', __('The site\'s front page', 'picot-theme-seeder')),
                    self::tpl('templates.home', 'home.php', __('The blog posts index page', 'picot-theme-seeder'), false, false, false),
                    self::tpl('templates.search', 'search.php', __('Template for search results', 'picot-theme-seeder')),
                    self::tpl('templates.404', '404.php', __('Template for 404 Error pages', 'picot-theme-seeder')),
                ),
            ),
        );
    }

    /**
     * Feature groups for step 4.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_feature_groups()
    {
        return array(
            array(
                'title' => __('Core Support', 'picot-theme-seeder'),
                'items' => array(
                    self::feat('features.title-tag', __('Title Tag (Required for modern SEO)', 'picot-theme-seeder'), __('Optimized title management for better SEO', 'picot-theme-seeder')),
                    self::feat('features.post-thumbnails', __('Featured Images (Post Thumbnails)', 'picot-theme-seeder'), __('Enable post thumbnail support for posts and pages', 'picot-theme-seeder')),
                    self::feat('features.html5', __('HTML5 Support (Search form, Comment form, etc.)', 'picot-theme-seeder'), __('Output semantic HTML5 for forms and lists', 'picot-theme-seeder')),
                    self::feat('features.automatic-feed-links', __('Automatic Feed Links', 'picot-theme-seeder'), __('Add RSS feed links to the document head', 'picot-theme-seeder')),
                    self::feat('features.custom-logo', __('Custom Logo', 'picot-theme-seeder'), __('Allow logo uploads via the customizer', 'picot-theme-seeder')),
                    self::feat('features.custom-header', __('Custom Header', 'picot-theme-seeder'), __('Enable custom header image management', 'picot-theme-seeder')),
                    self::feat('features.custom-background', __('Custom Background', 'picot-theme-seeder'), __('Support for custom background colors/images', 'picot-theme-seeder')),
                    self::feat('features.post-formats', __('Post Formats', 'picot-theme-seeder'), __('Enable support for various post types like video, gallery, etc.', 'picot-theme-seeder')),
                    self::feat('features.starter-content', __('Starter Content', 'picot-theme-seeder'), __('Setup default content for new installations', 'picot-theme-seeder')),
                    self::feat('features.woocommerce', __('WooCommerce Support', 'picot-theme-seeder'), __('Basic compatibility for the WooCommerce plugin', 'picot-theme-seeder')),
                    self::feat('features.selective-refresh', __('Selective Refresh Widgets', 'picot-theme-seeder'), __('Enable faster widget updating in customizer', 'picot-theme-seeder')),
                ),
            ),
            array(
                'title' => __('Navigation & Menus', 'picot-theme-seeder'),
                'items' => array(
                    self::feat('features.menus', __('Navigation Menus', 'picot-theme-seeder'), __('Support for custom menus via the dashboard', 'picot-theme-seeder')),
                    self::feat('features.multi-menus', __('Multiple Menu Locations', 'picot-theme-seeder'), __('Register Primary, Footer, Mobile, and Social menus', 'picot-theme-seeder')),
                    self::feat('features.widgets', __('Widgets / Sidebar', 'picot-theme-seeder'), __('Enable widget-ready sidebar areas', 'picot-theme-seeder')),
                ),
            ),
            array(
                'title' => __('Block Editor (Gutenberg) Support', 'picot-theme-seeder'),
                'items' => array(
                    self::feat('features.appearance-tools', __('Appearance Tools', 'picot-theme-seeder'), __('Enable comprehensive design controls like link color and borders', 'picot-theme-seeder')),
                    self::feat('features.align-wide', __('Wide Alignment Support', 'picot-theme-seeder'), __('Enable wide and full-width block alignments', 'picot-theme-seeder')),
                    self::feat('features.wp-block-styles', __('Core Block Styles', 'picot-theme-seeder'), __('Include standard WordPress block styles', 'picot-theme-seeder')),
                    self::feat('features.responsive-embeds', __('Responsive Embeds', 'picot-theme-seeder'), __('Ensure embedded content scales correctly', 'picot-theme-seeder')),
                    self::feat('features.editor-styles', __('Editor Styles', 'picot-theme-seeder'), __('Reflect theme styles within the block editor', 'picot-theme-seeder')),
                    self::feat('features.editor-color-palette', __('Custom Color Palette', 'picot-theme-seeder'), __('Define a specific set of colors for the block editor', 'picot-theme-seeder')),
                    self::feat('features.disable-custom-colors', __('Disable Custom Colors', 'picot-theme-seeder'), __('Restrict colors to theme presets only', 'picot-theme-seeder')),
                    self::feat('features.editor-gradient-presets', __('Editor Gradient Presets', 'picot-theme-seeder'), __('Define custom gradients for blocks', 'picot-theme-seeder')),
                    self::feat('features.disable-custom-gradients', __('Disable Custom Gradients', 'picot-theme-seeder'), __('Restrict gradients to theme presets only', 'picot-theme-seeder')),
                    self::feat('features.editor-font-sizes', __('Custom Font Sizes', 'picot-theme-seeder'), __('Register specific font sizes for text-based blocks', 'picot-theme-seeder')),
                    self::feat('features.disable-custom-font-sizes', __('Disable Custom Font Sizes', 'picot-theme-seeder'), __('Restrict font sizes to theme presets only', 'picot-theme-seeder')),
                    self::feat('features.custom-line-height', __('Custom Line Height', 'picot-theme-seeder'), __('Enable line height control in the editor', 'picot-theme-seeder')),
                    self::feat('features.custom-spacing', __('Custom Spacing Controls', 'picot-theme-seeder'), __('Enable margin and padding controls for blocks', 'picot-theme-seeder')),
                    self::feat('features.custom-units', __('Custom Units', 'picot-theme-seeder'), __('Register specific CSS units for block sizes', 'picot-theme-seeder')),
                    self::feat('features.link-color', __('Link Color Control', 'picot-theme-seeder'), __('Enable link color settings in the block editor (WordPress 6.3+).', 'picot-theme-seeder')),
                    self::feat('features.border', __('Border Controls', 'picot-theme-seeder'), __('Enable border design tools for blocks in classic themes (WordPress 6.3+).', 'picot-theme-seeder')),
                    self::feat('features.editor-spacing-sizes', __('Editor Spacing Presets', 'picot-theme-seeder'), __('Register spacing size presets for the block editor (WordPress 6.6+).', 'picot-theme-seeder')),
                    self::feat('features.disable-layout-styles', __('Disable Default Layout Styles', 'picot-theme-seeder'), __('Turn off WordPress default layout block styles (WordPress 6.1+).', 'picot-theme-seeder'), false),
                    self::feat('features.widgets-block-editor', __('Block Widget Editor', 'picot-theme-seeder'), __('Use the blocks-based widget screen instead of the legacy widgets UI (WordPress 5.8+).', 'picot-theme-seeder'), false),
                    self::feat('features.remove-global-styles', __('Remove Global Styles (Inline CSS)', 'picot-theme-seeder'), __('Remove the default bloat of block-based inline CSS', 'picot-theme-seeder')),
                ),
            ),
            array(
                'title' => __('Cleanup & Optimization', 'picot-theme-seeder'),
                'items' => array(
                    self::feat('features.header-cleanup', __('Header Cleanup (Security/Bloat)', 'picot-theme-seeder'), __('Remove RSD, WLW, and shortlinks from the head', 'picot-theme-seeder')),
                    self::feat('features.remove-wp-version', __('Remove WordPress Version (Security)', 'picot-theme-seeder'), __('Hide version information for better security', 'picot-theme-seeder')),
                    self::feat('features.svg-support', __('Enable Admin-only SVG Uploads', 'picot-theme-seeder'), __('Allow SVG uploads only for administrators and reject unsafe markup.', 'picot-theme-seeder')),
                    self::feat('features.excerpt-length', __('Custom Excerpt Length (50 words)', 'picot-theme-seeder'), __('Adjust post excerpt length to 50 words', 'picot-theme-seeder')),
                    self::feat('features.disable-emojis', __('Disable Emojis (Speed)', 'picot-theme-seeder'), __('Remove unnecessary emoji script and styles', 'picot-theme-seeder')),
                    self::feat('features.load-block-assets-on-demand', __('Load Block CSS On Demand', 'picot-theme-seeder'), __('Load only styles for blocks used on the page (WordPress 6.8+). Improves performance in classic themes.', 'picot-theme-seeder'), false),
                    self::feat('features.disable-block-library-css', __('Disable Block Library CSS', 'picot-theme-seeder'), __('Option to remove the default block styles', 'picot-theme-seeder')),
                    self::feat('features.remove-jquery-migrate', __('Remove jQuery Migrate', 'picot-theme-seeder'), __('Stop loading the legacy migration script', 'picot-theme-seeder')),
                    self::feat('features.disable-heartbeat', __('Disable Heartbeat API', 'picot-theme-seeder'), __('Reduce server load by limiting background pulses', 'picot-theme-seeder')),
                    self::feat('features.lazy-load-adjust', __('Lazy Load Settings', 'picot-theme-seeder'), __('Optimization settings for native image lazy loading', 'picot-theme-seeder')),
                    self::feat('features.webp-support', __('Enable WebP Support', 'picot-theme-seeder'), __('Allow WebP image uploads and automatic quality adjustment', 'picot-theme-seeder')),
                    self::feat('features.webm-support', __('Enable WebM Support', 'picot-theme-seeder'), __('Allow WebM video uploads to the media library', 'picot-theme-seeder')),
                    self::feat('features.post-revisions-limit', __('Limit Post Revisions', 'picot-theme-seeder'), __('Restrict the number of revisions to keep for each post', 'picot-theme-seeder')),
                    self::feat('features.content-width-limit', __('Global Content Width', 'picot-theme-seeder'), __('Define a standard maximum width for embedded content', 'picot-theme-seeder')),
                ),
            ),
            array(
                'title' => __('Advanced Functions', 'picot-theme-seeder'),
                'items' => array(
                    self::feat('features.custom-pagination', __('Custom Numeric Pagination', 'picot-theme-seeder'), __('Add a numeric pagination function for archives', 'picot-theme-seeder')),
                    self::feat('features.breadcrumbs', __('Breadcrumbs Template', 'picot-theme-seeder'), __('Include a foundation for breadcrumb navigation', 'picot-theme-seeder')),
                    self::feat('features.threaded-comments', __('Threaded Comments Support', 'picot-theme-seeder'), __('Automatically load scripts for reply-to functionality', 'picot-theme-seeder')),
                    self::feat('features.scss-sources', __('SCSS Sources (assets/scss)', 'picot-theme-seeder'), __('Editable SCSS partials mirroring style.css, plus a package.json with sass build scripts', 'picot-theme-seeder')),
                ),
            ),
            array(
                'title' => __('Automatic Updates & Security', 'picot-theme-seeder'),
                'items' => array(
                    self::feat('features.auto-update-core-minor', __('Enable Minor Core Auto-updates', 'picot-theme-seeder'), __('Automatically apply minor security releases', 'picot-theme-seeder')),
                    self::feat('features.auto-update-core-major', __('Enable Major Core Auto-updates', 'picot-theme-seeder'), __('Opt-in for major version auto-updates', 'picot-theme-seeder')),
                    self::feat('features.auto-update-plugins', __('Enable Plugin Auto-updates', 'picot-theme-seeder'), __('Keep all plugins updated automatically', 'picot-theme-seeder')),
                    self::feat('features.auto-update-themes', __('Enable Theme Auto-updates', 'picot-theme-seeder'), __('Keep all themes updated automatically', 'picot-theme-seeder')),
                    self::feat('features.disable-xmlrpc', __('Disable XML-RPC (Security)', 'picot-theme-seeder'), __('Disable XML-RPC to prevent brute force attacks', 'picot-theme-seeder')),
                    self::feat('features.restrict-rest-api', __('Limit public REST user endpoints', 'picot-theme-seeder'), __('Hide public user-list endpoints without blocking the broader REST API.', 'picot-theme-seeder')),
                    self::feat('features.disable-file-edit', __('Disable Theme/Plugin File Editor', 'picot-theme-seeder'), __('Disable the dashboard file editor for security', 'picot-theme-seeder'), false),
                ),
            ),
            array(
                'title' => __('Admin Customization', 'picot-theme-seeder'),
                'items' => array(
                    self::feat('features.login-custom-url', __('Custom Login Page Credits', 'picot-theme-seeder'), __('Link the login logo to your site and change its title', 'picot-theme-seeder')),
                    self::feat('features.hide-admin-bar', __('Hide Admin Bar for Customers', 'picot-theme-seeder'), __('Disable the admin bar for non-administrator users', 'picot-theme-seeder')),
                    self::feat('features.admin-footer-text', __('Admin Footer Branding', 'picot-theme-seeder'), __('Add your custom brand text to the admin footer area', 'picot-theme-seeder')),
                    self::feat('features.custom-login-style', __('Custom Login Style', 'picot-theme-seeder'), __('Branding for the WordPress login screen', 'picot-theme-seeder')),
                    self::feat('features.dashboard-cleanup', __('Dashboard Cleanup', 'picot-theme-seeder'), __('Remove unused dashboard widgets for a cleaner UI', 'picot-theme-seeder')),
                    self::feat('features.search-limit', __('Search Results Limit', 'picot-theme-seeder'), __('Control the number of results per search page', 'picot-theme-seeder')),
                ),
            ),
        );
    }

    /**
     * @param string $id Selection key.
     * @param string $file Display filename.
     * @param string $description Help text.
     * @param bool   $required Mark as required label.
     * @param bool   $disabled Checkbox disabled state.
     * @param bool   $checked  Default checked state.
     */
    private static function tpl($id, $file, $description, $required = false, $disabled = false, $checked = true)
    {
        $name = $file;
        if ($required) {
            $name = sprintf(
                /* translators: 1: filename, 2: required note */
                __('%1$s — %2$s', 'picot-theme-seeder'),
                $file,
                __('required', 'picot-theme-seeder')
            );
        }

        return array(
            'id'          => $id,
            'name'        => $name,
            'description' => $description,
            'checked'     => $checked,
            'disabled'    => $disabled,
        );
    }

    /**
     * @param string $id Selection key.
     * @param string $name Checkbox label.
     * @param string $description Help text.
     * @param bool   $checked Default checked.
     */
    private static function feat($id, $name, $description, $checked = true)
    {
        return array(
            'id'          => $id,
            'name'        => $name,
            'description' => $description,
            'checked'     => $checked,
            'disabled'    => false,
        );
    }
}
