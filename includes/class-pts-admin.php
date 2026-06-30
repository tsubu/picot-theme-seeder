<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Unified admin for Picot Theme Seeder.
 */
class PTS_Admin
{

    public function init()
    {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('admin_post_pts_download_zip', array(__CLASS__, 'handle_zip_download'));
    }

    public function register_routes()
    {
        register_rest_route(
            'pts/v1',
            '/generate',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'handle_generate_theme_rest'),
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            )
        );
    }

    public function handle_generate_theme_rest($request)
    {
        $data       = $request->get_json_params();
        $theme_type = isset($data['themeType']) ? sanitize_key($data['themeType']) : '';

        if (! in_array($theme_type, array('block', 'classic'), true)) {
            return new WP_Error(
                'invalid_theme_type',
                __('Invalid theme type. Choose Block or Classic.', 'picot-theme-seeder'),
                array('status' => 400)
            );
        }

        ob_start();
        if ($theme_type === 'classic') {
            $generator = new CTS_Generator();
        } else {
            $generator = new BTS_Generator();
        }
        $result = $generator->generate($data);
        ob_get_clean();

        if (is_wp_error($result)) {
            return new WP_Error('generation_failed', $result->get_error_message(), array('status' => 500));
        }

        return rest_ensure_response($result);
    }

    public function add_menu_page()
    {
        add_menu_page(
            __('Picot Theme Seeder', 'picot-theme-seeder'),
            __('Theme Seeder', 'picot-theme-seeder'),
            'manage_options',
            'picot-theme-seeder',
            array($this, 'render_page'),
            'dashicons-art',
            60
        );
    }

    public function enqueue_assets($hook)
    {
        if ('toplevel_page_picot-theme-seeder' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'pts-admin-style',
            PTS_PLUGIN_URL . 'admin/style.css',
            array(),
            PTS_VERSION
        );
        wp_enqueue_script(
            'pts-block-app',
            PTS_PLUGIN_URL . 'admin/block-app.js',
            array('jquery'),
            PTS_VERSION,
            true
        );
        wp_enqueue_script(
            'pts-classic-app',
            PTS_PLUGIN_URL . 'admin/classic-app.js',
            array('jquery'),
            PTS_VERSION,
            true
        );
        wp_enqueue_script(
            'pts-admin-app',
            PTS_PLUGIN_URL . 'admin/app.js',
            array('jquery', 'pts-block-app', 'pts-classic-app'),
            PTS_VERSION,
            true
        );

        wp_localize_script(
            'pts-admin-app',
            'ptsData',
            array(
                'restUrl'   => rest_url('pts/v1/generate'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'block'     => $this->get_block_localize_data(),
                'classic'   => $this->get_classic_localize_data(),
                'strings'   => array(
                    'chooseType'     => __('Please choose a theme type to continue.', 'picot-theme-seeder'),
                    'blockTitle'     => __('Block Theme (FSE)', 'picot-theme-seeder'),
                    'blockDesc'      => __('Full Site Editing theme with theme.json, HTML templates, template parts, and block patterns.', 'picot-theme-seeder'),
                    'classicTitle'   => __('Classic Theme', 'picot-theme-seeder'),
                    'classicDesc'    => __('PHP templates, functions.php, style.css, and optional theme features.', 'picot-theme-seeder'),
                    'changeType'     => __('Change theme type', 'picot-theme-seeder'),
                    'continue'       => __('Continue', 'picot-theme-seeder'),
                ),
            )
        );
    }

    /**
     * Translated block preset labels keyed by preset id (for presets.json).
     *
     * @return array<string, array{name: string, description: string}>
     */
    private function get_block_preset_strings()
    {
        return array(
            'preset.l1.bare' => array(
                'name'        => __('Bare Minimum', 'picot-theme-seeder'),
                'description' => __('Just the essentials: theme.json, a base template shell, and the required header/footer parts.', 'picot-theme-seeder'),
            ),
            'preset.l1.blog_standard' => array(
                'name'        => __('Editorial Blog', 'picot-theme-seeder'),
                'description' => __('A practical publishing setup with post templates, post metadata, comments, and latest-post patterns.', 'picot-theme-seeder'),
            ),
            'preset.l1.marketing' => array(
                'name'        => __('Marketing / Landing', 'picot-theme-seeder'),
                'description' => __('A conversion-focused business site with landing-page templates, reusable sections, and style variations.', 'picot-theme-seeder'),
            ),
            'preset.l1.business' => array(
                'name'        => __('Business / Corporate', 'picot-theme-seeder'),
                'description' => __('A polished company-site starter with page templates, common layout parts, and trust-building patterns.', 'picot-theme-seeder'),
            ),
            'preset.l1.design_system' => array(
                'name'        => __('Design System Starter', 'picot-theme-seeder'),
                'description' => __('A strong foundation for custom projects with theme.json, SCSS sources, layout kits, and multiple style directions.', 'picot-theme-seeder'),
            ),
            'preset.l1.all' => array(
                'name'        => __('All-in-One', 'picot-theme-seeder'),
                'description' => __('A kitchen-sink starter with nearly every template, part set, pattern, and optional source file enabled.', 'picot-theme-seeder'),
            ),
        );
    }

    /**
     * Translated block preset category labels keyed by category id.
     *
     * @return array<string, string>
     */
    private function get_block_category_strings()
    {
        return array(
            'L1' => __('Uses (L1)', 'picot-theme-seeder'),
        );
    }

    private function get_block_localize_data()
    {
        $presets_file   = PTS_PLUGIN_DIR . 'includes/presets.json';
        $presets_data   = array();
        $decoded        = array();
        $preset_strings = $this->get_block_preset_strings();
        $category_strings = $this->get_block_category_strings();

        if (file_exists($presets_file)) {
            $json    = file_get_contents($presets_file);
            $decoded = json_decode($json, true);
            if (is_array($decoded) && isset($decoded['presets'])) {
                foreach ($decoded['presets'] as $p) {
                    $id      = isset($p['id']) ? $p['id'] : '';
                    $strings = isset($preset_strings[ $id ]) ? $preset_strings[ $id ] : array();
                    $presets_data[] = array(
                        'id'          => $id,
                        'category'    => isset($p['category']) ? $p['category'] : '',
                        'name'        => isset($strings['name']) ? $strings['name'] : (isset($p['name']) ? $p['name'] : ''),
                        'description' => isset($strings['description']) ? $strings['description'] : (isset($p['description']) ? $p['description'] : ''),
                        'apply'       => isset($p['apply']) ? $p['apply'] : array(),
                    );
                }
            }
        }

        $category_labels = array();
        if (! empty($decoded['categories']) && is_array($decoded['categories'])) {
            foreach ($decoded['categories'] as $cat) {
                $cat_id = isset($cat['id']) ? $cat['id'] : '';
                $category_labels[ $cat_id ] = isset($category_strings[ $cat_id ])
                    ? $category_strings[ $cat_id ]
                    : (isset($cat['name']) ? $cat['name'] : '');
            }
        }

        return array(
            'presets'        => $presets_data,
            'categoryLabels' => $category_labels,
            'definitions'    => $this->get_block_definitions(),
            'strings'        => array(
                'noPresets'     => __('No presets found for this category.', 'picot-theme-seeder'),
                'fillRequired'  => __('Please fill in Theme Name and Slug.', 'picot-theme-seeder'),
                'generating'    => __('Generating...', 'picot-theme-seeder'),
                'generateBtn'   => __('Generate Theme', 'picot-theme-seeder'),
                'success'       => __('Theme generated successfully.', 'picot-theme-seeder'),
                'error'         => __('Error: ', 'picot-theme-seeder'),
                'unknownError'  => __('Unknown error', 'picot-theme-seeder'),
                'layoutOneColumn' => __('Default layout: 1 column', 'picot-theme-seeder'),
                'layoutTwoColumn' => __('Default layout: 2 columns', 'picot-theme-seeder'),
            ),
        );
    }

    /**
     * Translated labels for block theme feature checkboxes (passed to JS).
     *
     * @return array<string, array<int, array<string, string>>>
     */
    private function get_block_definitions()
    {
        return array(
            'templates'      => $this->get_block_template_definitions(),
            'parts'          => $this->get_block_part_definitions(),
            'partsExtended'  => $this->get_block_extended_part_definitions(),
            'partsJpLp'      => $this->get_block_jp_lp_part_definitions(),
            'partsProductLp' => $this->get_block_product_lp_part_definitions(),
            'partsLayoutKit' => $this->get_block_layout_kit_part_definitions(),
            'patterns'       => $this->get_block_pattern_definitions(),
        );
    }

    /**
     * Block template checkbox labels and descriptions.
     *
     * @return array<int, array<string, string>>
     */
    private function get_block_template_definitions()
    {
        $items = array(
            array(
                'id'          => 'templates.index',
                'file'        => 'index.html',
                'name'        => __('Index', 'picot-theme-seeder'),
                'suffix'      => __('required', 'picot-theme-seeder'),
                'description' => __('Fallback template for all views when no more specific template exists.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'templates.frontPage',
                'file'        => 'front-page.html',
                'name'        => __('Front Page', 'picot-theme-seeder'),
                'description' => __('Site front landing page (hero, features, latest posts, CTA). Always wins on the front page, regardless of Reading settings.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'templates.home',
                'file'        => 'home.html',
                'name'        => __('Home', 'picot-theme-seeder'),
                'description' => __('Blog posts index when the front page shows latest posts.', 'picot-theme-seeder'),
                'checked'     => false,
            ),
            array(
                'id'          => 'templates.single',
                'file'        => 'single.html',
                'name'        => __('Single', 'picot-theme-seeder'),
                'description' => __('Layout for a single blog post.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'templates.singleWithSidebar',
                'file'        => 'single-with-sidebar.html',
                'name'        => __('Single (With Sidebar)', 'picot-theme-seeder'),
                'description' => __('Two-column single post with the sidebar part on the right. Included automatically when Single is enabled; selectable per post as a custom template.', 'picot-theme-seeder'),
                'checked'     => false,
            ),
            array(
                'id'          => 'templates.page',
                'file'        => 'page.html',
                'name'        => __('Page', 'picot-theme-seeder'),
                'description' => __('Layout for static pages.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'templates.pageWithSidebar',
                'file'        => 'page-with-sidebar.html',
                'name'        => __('Page (With Sidebar)', 'picot-theme-seeder'),
                'description' => __('Two-column page with the sidebar part on the right. Included automatically when Page is enabled; selectable per page as a custom template.', 'picot-theme-seeder'),
                'checked'     => false,
            ),
            array(
                'id'          => 'templates.pageLp',
                'file'        => 'page-lp.html',
                'name'        => __('LP Page (Blank Canvas)', 'picot-theme-seeder'),
                'description' => __('No header or footer — a full canvas for landing pages built from the LP parts. Selectable per page as a custom template.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'templates.singular',
                'file'        => 'singular.html',
                'name'        => __('Singular', 'picot-theme-seeder'),
                'description' => __('Fallback for single posts, pages, and custom post types.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'templates.archive',
                'file'        => 'archive.html',
                'name'        => __('Archive', 'picot-theme-seeder'),
                'description' => __('Category, tag, author, and date archive listings.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'templates.search',
                'file'        => 'search.html',
                'name'        => __('Search', 'picot-theme-seeder'),
                'description' => __('Search results page.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'templates.404',
                'file'        => '404.html',
                'name'        => __('404', 'picot-theme-seeder'),
                'description' => __('Page shown when the requested URL is not found.', 'picot-theme-seeder'),
            ),
        );

        return $this->format_block_file_definitions($items);
    }

    /**
     * Block template part checkbox labels and descriptions.
     *
     * @return array<int, array<string, string>>
     */
    private function get_block_part_definitions()
    {
        $items = array(
            array(
                'id'          => 'parts.header',
                'file'        => 'header.html',
                'name'        => __('Header', 'picot-theme-seeder'),
                'description' => __('Site title and navigation; included via template-part in templates.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.headerCentered',
                'file'        => 'header-centered.html',
                'name'        => __('Header (Centered)', 'picot-theme-seeder'),
                'description' => __('Alternative header with centered title and menu. Swap via the Replace menu in the Site Editor.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.headerWithButton',
                'file'        => 'header-with-button.html',
                'name'        => __('Header (With Button)', 'picot-theme-seeder'),
                'description' => __('Alternative header with navigation and a call-to-action button. Swap via the Replace menu in the Site Editor.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.footer',
                'file'        => 'footer.html',
                'name'        => __('Footer', 'picot-theme-seeder'),
                'description' => __('Site footer area, typically copyright and links.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.footerColumns',
                'file'        => 'footer-columns.html',
                'name'        => __('Footer (Columns)', 'picot-theme-seeder'),
                'description' => __('Alternative three-column footer with links and contact info. Swap via the Replace menu in the Site Editor.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.sidebar',
                'file'        => 'sidebar.html',
                'name'        => __('Sidebar', 'picot-theme-seeder'),
                'description' => __('Optional sidebar column for widgets or secondary content. Synced with the default column layout above; always generated when post/page templates are enabled so you can switch layouts later.', 'picot-theme-seeder'),
                'checked'     => false,
                'basicSet'    => false,
            ),
            array(
                'id'          => 'parts.comments',
                'file'        => 'comments.html',
                'name'        => __('Comments', 'picot-theme-seeder'),
                'description' => __('Comment list and reply form for single posts.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.postMeta',
                'file'        => 'post-meta.html',
                'name'        => __('Post Meta', 'picot-theme-seeder'),
                'description' => __('Post date, author, categories, and similar metadata.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.postNavigation',
                'file'        => 'post-navigation.html',
                'name'        => __('Post Navigation', 'picot-theme-seeder'),
                'description' => __('Previous / next post links shown below single posts.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.loop',
                'file'        => 'loop.html',
                'name'        => __('Post Loop', 'picot-theme-seeder'),
                'description' => __('Reusable post list with pagination, for custom templates and pages.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.cta',
                'file'        => 'cta.html',
                'name'        => __('Call to Action', 'picot-theme-seeder'),
                'description' => __('Reusable call-to-action band with heading, text, and a button.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.heroFullWidth',
                'file'        => 'hero-full-width.html',
                'name'        => __('Hero (Full-width Image)', 'picot-theme-seeder'),
                'description' => __('Full-viewport cover hero with a background image and centered headline, text, and buttons.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.heroEdge',
                'file'        => 'hero-edge.html',
                'name'        => __('Hero (Edge to Edge)', 'picot-theme-seeder'),
                'description' => __('Hero section that spans the full viewport width, including background and wide content.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.heroCrossfade',
                'file'        => 'hero-crossfade.html',
                'name'        => __('Hero (Crossfade Background)', 'picot-theme-seeder'),
                'description' => __('Hero with multiple background images that crossfade smoothly using an ease-in-out transition. Add or remove images in the slides group.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.categoryFeaturedList',
                'file'        => 'category-featured-list.html',
                'name'        => __('Category Featured + 3 Links', 'picot-theme-seeder'),
                'description' => __('Shows one featured latest post from a chosen category, followed by three compact latest-post links from the same category.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.selectedPostPage',
                'file'        => 'selected-post-page.html',
                'name'        => __('Selected Post + Page', 'picot-theme-seeder'),
                'description' => __('A two-card section for highlighting one chosen blog post and one chosen static page with editable links.', 'picot-theme-seeder'),
            ),
        );

        return $this->format_block_file_definitions($items);
    }

    /**
     * Extended layout part checkbox labels and descriptions
     * ("Extended Layout Parts Set" — common site layout variations, off by default).
     *
     * @return array<int, array<string, string>>
     */
    private function get_block_extended_part_definitions()
    {
        $items = array(
            array(
                'id'          => 'parts.headerMinimal',
                'file'        => 'header-minimal.html',
                'name'        => __('Header (Minimal)', 'picot-theme-seeder'),
                'description' => __('Slim header with the centered site title only.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.headerWithSearch',
                'file'        => 'header-with-search.html',
                'name'        => __('Header (With Search)', 'picot-theme-seeder'),
                'description' => __('Header with navigation and a search box.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.headerWithSocial',
                'file'        => 'header-with-social.html',
                'name'        => __('Header (With Social)', 'picot-theme-seeder'),
                'description' => __('Header with navigation and social media icons.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.headerTagline',
                'file'        => 'header-tagline.html',
                'name'        => __('Header (With Tagline)', 'picot-theme-seeder'),
                'description' => __('Header showing the site title with tagline next to the navigation.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.headerSplit',
                'file'        => 'header-split.html',
                'name'        => __('Header (Split)', 'picot-theme-seeder'),
                'description' => __('Three-zone header: navigation, centered site title, and a button.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.footerMinimal',
                'file'        => 'footer-minimal.html',
                'name'        => __('Footer (Minimal)', 'picot-theme-seeder'),
                'description' => __('Single centered copyright line.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.footerMenu',
                'file'        => 'footer-menu.html',
                'name'        => __('Footer (With Menu)', 'picot-theme-seeder'),
                'description' => __('Footer with site title, navigation, and copyright.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.footerSocial',
                'file'        => 'footer-social.html',
                'name'        => __('Footer (Social)', 'picot-theme-seeder'),
                'description' => __('Centered social media icons above the copyright line.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.footerMega',
                'file'        => 'footer-mega.html',
                'name'        => __('Footer (Mega)', 'picot-theme-seeder'),
                'description' => __('Four-column footer with links, resources, contact, and a bottom bar.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.footerCta',
                'file'        => 'footer-cta.html',
                'name'        => __('Footer (CTA)', 'picot-theme-seeder'),
                'description' => __('Footer leading with a call-to-action, then the copyright line.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.sidebarBlog',
                'file'        => 'sidebar-blog.html',
                'name'        => __('Sidebar (Blog)', 'picot-theme-seeder'),
                'description' => __('Blog sidebar with search, recent posts, categories, and archives.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.sidebarCta',
                'file'        => 'sidebar-cta.html',
                'name'        => __('Sidebar (CTA)', 'picot-theme-seeder'),
                'description' => __('Sidebar box with a short message and a button.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.loopGrid',
                'file'        => 'loop-grid.html',
                'name'        => __('Post Loop (Grid)', 'picot-theme-seeder'),
                'description' => __('Three-column post grid with featured images and pagination.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.loopCompact',
                'file'        => 'loop-compact.html',
                'name'        => __('Post Loop (Compact)', 'picot-theme-seeder'),
                'description' => __('Compact date and title list, ideal for news updates.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.archiveHeader',
                'file'        => 'archive-header.html',
                'name'        => __('Archive Header', 'picot-theme-seeder'),
                'description' => __('Archive title band with the term description.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.pageHeader',
                'file'        => 'page-header.html',
                'name'        => __('Page Header', 'picot-theme-seeder'),
                'description' => __('Centered page title band for posts and pages.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.authorBox',
                'file'        => 'author-box.html',
                'name'        => __('Author Box', 'picot-theme-seeder'),
                'description' => __('Author avatar, name, and biography for single posts.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.relatedPosts',
                'file'        => 'related-posts.html',
                'name'        => __('Related Posts', 'picot-theme-seeder'),
                'description' => __('Heading with a three-column grid of recent posts.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.socialLinks',
                'file'        => 'social-links.html',
                'name'        => __('Social Links', 'picot-theme-seeder'),
                'description' => __('Centered row of social media icons, usable anywhere.', 'picot-theme-seeder'),
            ),
        );

        foreach ($items as &$item) {
            $item['checked'] = false;
        }
        unset($item);

        return $this->format_block_file_definitions($items);
    }

    /**
     * Japanese LP part checkbox labels and descriptions
     * ("Japanese LP Parts Set" — landing page sections, off by default).
     *
     * @return array<int, array<string, string>>
     */
    private function get_block_jp_lp_part_definitions()
    {
        $items = array(
            array(
                'id'          => 'parts.lpHero',
                'file'        => 'lp-hero.html',
                'name'        => __('LP First View', 'picot-theme-seeder'),
                'description' => __('Catch copy, sub copy, and two CTA buttons for the top of a landing page.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpProblems',
                'file'        => 'lp-problems.html',
                'name'        => __('LP Problems Checklist', 'picot-theme-seeder'),
                'description' => __('"Do you have these problems?" heading with a checklist.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpEmpathy',
                'file'        => 'lp-empathy.html',
                'name'        => __('LP Empathy', 'picot-theme-seeder'),
                'description' => __('Empathy section warning about leaving problems unresolved.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpSolution',
                'file'        => 'lp-solution.html',
                'name'        => __('LP Solution', 'picot-theme-seeder'),
                'description' => __('"Our service solves it all" section introducing the product.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpReasons',
                'file'        => 'lp-reasons.html',
                'name'        => __('LP Three Reasons', 'picot-theme-seeder'),
                'description' => __('"Three reasons we are chosen" in three columns.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpAchievements',
                'file'        => 'lp-achievements.html',
                'name'        => __('LP Achievements', 'picot-theme-seeder'),
                'description' => __('Big numbers band: track record, satisfaction, retention.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpVoice',
                'file'        => 'lp-voice.html',
                'name'        => __('LP Customer Voices', 'picot-theme-seeder'),
                'description' => __('Three customer testimonials with attributes.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpBeforeAfter',
                'file'        => 'lp-before-after.html',
                'name'        => __('LP Before / After', 'picot-theme-seeder'),
                'description' => __('Two-column comparison of before and after adopting the service.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpPricing',
                'file'        => 'lp-pricing.html',
                'name'        => __('LP Pricing Plans', 'picot-theme-seeder'),
                'description' => __('Three pricing plans with features and buttons.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpComparison',
                'file'        => 'lp-comparison.html',
                'name'        => __('LP Comparison Table', 'picot-theme-seeder'),
                'description' => __('Comparison table against competitor services.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpFlow',
                'file'        => 'lp-flow.html',
                'name'        => __('LP Service Flow', 'picot-theme-seeder'),
                'description' => __('Four-step flow from inquiry to start.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpFaq',
                'file'        => 'lp-faq.html',
                'name'        => __('LP FAQ', 'picot-theme-seeder'),
                'description' => __('Frequently asked questions with expandable answers.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpCampaign',
                'file'        => 'lp-campaign.html',
                'name'        => __('LP Campaign Banner', 'picot-theme-seeder'),
                'description' => __('Limited-time campaign banner with deadline and button.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpBenefits',
                'file'        => 'lp-benefits.html',
                'name'        => __('LP Benefits', 'picot-theme-seeder'),
                'description' => __('Three limited-time bonuses in columns.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpGuarantee',
                'file'        => 'lp-guarantee.html',
                'name'        => __('LP Guarantee', 'picot-theme-seeder'),
                'description' => __('Money-back guarantee section to remove purchase anxiety.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpMessage',
                'file'        => 'lp-message.html',
                'name'        => __('LP Owner Message', 'picot-theme-seeder'),
                'description' => __('Message from the company representative with signature.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpCompany',
                'file'        => 'lp-company.html',
                'name'        => __('LP Company Profile', 'picot-theme-seeder'),
                'description' => __('Company overview table: name, address, founding, business.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpAccess',
                'file'        => 'lp-access.html',
                'name'        => __('LP Access', 'picot-theme-seeder'),
                'description' => __('Address, station access, business hours, and a map placeholder.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpNews',
                'file'        => 'lp-news.html',
                'name'        => __('LP News', 'picot-theme-seeder'),
                'description' => __('Date and title list of the latest posts as announcements.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.lpCta',
                'file'        => 'lp-cta.html',
                'name'        => __('LP Conversion Area', 'picot-theme-seeder'),
                'description' => __('Closing conversion area with phone number, hours, and buttons.', 'picot-theme-seeder'),
            ),
        );

        foreach ($items as &$item) {
            $item['checked'] = false;
        }
        unset($item);

        return $this->format_block_file_definitions($items);
    }

    /**
     * Product LP part checkbox labels and descriptions
     * ("Product LP Parts Set" — EC / Rakuten-style sections, off by default).
     *
     * @return array<int, array<string, string>>
     */
    private function get_block_product_lp_part_definitions()
    {
        $items = array(
            array(
                'id'          => 'parts.productHero',
                'file'        => 'product-hero.html',
                'name'        => __('Product Hero', 'picot-theme-seeder'),
                'description' => __('Ranking badge, product name, price, and an add-to-cart button.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productBadges',
                'file'        => 'product-badges.html',
                'name'        => __('Product Trust Badges', 'picot-theme-seeder'),
                'description' => __('Free shipping, next-day delivery, domestic production, gift wrapping.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productRanking',
                'file'        => 'product-ranking.html',
                'name'        => __('Product Ranking Awards', 'picot-theme-seeder'),
                'description' => __('List of ranking awards with period disclaimers.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productMedia',
                'file'        => 'product-media.html',
                'name'        => __('Product Media Mentions', 'picot-theme-seeder'),
                'description' => __('"Featured in media and SNS" section with a logo placeholder.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productSale',
                'file'        => 'product-sale.html',
                'name'        => __('Product Time Sale', 'picot-theme-seeder'),
                'description' => __('Limited-time sale banner with deadline and buy button.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productCoupon',
                'file'        => 'product-coupon.html',
                'name'        => __('Product Coupon', 'picot-theme-seeder'),
                'description' => __('Page-limited coupon banner with a claim button.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productPrice',
                'file'        => 'product-price.html',
                'name'        => __('Product Price Appeal', 'picot-theme-seeder'),
                'description' => __('Regular price with strikethrough, special price, discount and points.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productFeatures',
                'file'        => 'product-features.html',
                'name'        => __('Product Selling Points', 'picot-theme-seeder'),
                'description' => __('POINT 01-03 sections highlighting product quality.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productUsage',
                'file'        => 'product-usage.html',
                'name'        => __('Product Usage Steps', 'picot-theme-seeder'),
                'description' => __('Easy three-step usage guide in columns.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productScenes',
                'file'        => 'product-scenes.html',
                'name'        => __('Product Recommended For', 'picot-theme-seeder'),
                'description' => __('Checklist of recommended users and gift occasions.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productVariations',
                'file'        => 'product-variations.html',
                'name'        => __('Product Variations', 'picot-theme-seeder'),
                'description' => __('Color and size variation table.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productSet',
                'file'        => 'product-set.html',
                'name'        => __('Product Set Contents', 'picot-theme-seeder'),
                'description' => __('List of everything included in the package.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productSpec',
                'file'        => 'product-spec.html',
                'name'        => __('Product Specs Table', 'picot-theme-seeder'),
                'description' => __('Specification table: name, size, material, country of origin.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productReviews',
                'file'        => 'product-reviews.html',
                'name'        => __('Product Reviews', 'picot-theme-seeder'),
                'description' => __('Star rating, review count, and three customer reviews.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productStory',
                'file'        => 'product-story.html',
                'name'        => __('Product Story', 'picot-theme-seeder'),
                'description' => __('Development story building trust and attachment.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productGift',
                'file'        => 'product-gift.html',
                'name'        => __('Product Gift Wrapping', 'picot-theme-seeder'),
                'description' => __('Wrapping, noshi, and message card support details.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productShipping',
                'file'        => 'product-shipping.html',
                'name'        => __('Product Shipping Info', 'picot-theme-seeder'),
                'description' => __('Shipping fee, carriers, delivery time, and same-day options table.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productReturns',
                'file'        => 'product-returns.html',
                'name'        => __('Product Returns Policy', 'picot-theme-seeder'),
                'description' => __('Conditions for returns and exchanges.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productNotes',
                'file'        => 'product-notes.html',
                'name'        => __('Product Purchase Notes', 'picot-theme-seeder'),
                'description' => __('Pre-purchase notes such as color differences on monitors.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.productCart',
                'file'        => 'product-cart.html',
                'name'        => __('Product Cart Area', 'picot-theme-seeder'),
                'description' => __('Closing purchase area with price, perks, and cart buttons.', 'picot-theme-seeder'),
            ),
        );

        foreach ($items as &$item) {
            $item['checked'] = false;
        }
        unset($item);

        return $this->format_block_file_definitions($items);
    }

    /**
     * Common layout kit checkbox labels and descriptions
     * ("Common Layout Kit" — everyday page layouts, off by default).
     *
     * @return array<int, array<string, string>>
     */
    private function get_block_layout_kit_part_definitions()
    {
        $items = array(
            array(
                'id'          => 'parts.layoutSectionHeader',
                'file'        => 'layout-section-header.html',
                'name'        => __('Layout: Section Header', 'picot-theme-seeder'),
                'description' => __('Centered section heading with a lead paragraph.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutTextImage',
                'file'        => 'layout-text-image.html',
                'name'        => __('Layout: Text + Image', 'picot-theme-seeder'),
                'description' => __('Text on the left, image placeholder on the right.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutImageText',
                'file'        => 'layout-image-text.html',
                'name'        => __('Layout: Image + Text', 'picot-theme-seeder'),
                'description' => __('Image placeholder on the left, text on the right.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutTwoColumnText',
                'file'        => 'layout-two-column-text.html',
                'name'        => __('Layout: Two Column Text', 'picot-theme-seeder'),
                'description' => __('Two headed text columns side by side.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutThreeCards',
                'file'        => 'layout-three-cards.html',
                'name'        => __('Layout: Three Cards', 'picot-theme-seeder'),
                'description' => __('Three cards with image, heading, and text.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutFourCards',
                'file'        => 'layout-four-cards.html',
                'name'        => __('Layout: Four Cards', 'picot-theme-seeder'),
                'description' => __('Four compact heading and text columns.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutFeatureList',
                'file'        => 'layout-feature-list.html',
                'name'        => __('Layout: Feature List', 'picot-theme-seeder'),
                'description' => __('Two-column checklist of features or benefits.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutGallery',
                'file'        => 'layout-gallery.html',
                'name'        => __('Layout: Gallery', 'picot-theme-seeder'),
                'description' => __('Six image placeholders in a three-column grid.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutFullImage',
                'file'        => 'layout-full-image.html',
                'name'        => __('Layout: Full-width Image', 'picot-theme-seeder'),
                'description' => __('Single full-width image placeholder.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutTimeline',
                'file'        => 'layout-timeline.html',
                'name'        => __('Layout: Timeline', 'picot-theme-seeder'),
                'description' => __('Company history table with years and events.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutTeam',
                'file'        => 'layout-team.html',
                'name'        => __('Layout: Team', 'picot-theme-seeder'),
                'description' => __('Three member profiles with photo, name, and role.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutLogos',
                'file'        => 'layout-logos.html',
                'name'        => __('Layout: Logos', 'picot-theme-seeder'),
                'description' => __('Row of client or partner logo placeholders.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutStats',
                'file'        => 'layout-stats.html',
                'name'        => __('Layout: Stats', 'picot-theme-seeder'),
                'description' => __('Four big numbers with captions.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutQuote',
                'file'        => 'layout-quote.html',
                'name'        => __('Layout: Quote', 'picot-theme-seeder'),
                'description' => __('Large pull quote with attribution.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutBannerCta',
                'file'        => 'layout-banner-cta.html',
                'name'        => __('Layout: Banner CTA', 'picot-theme-seeder'),
                'description' => __('Slim banner with a heading and a button side by side.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutContact',
                'file'        => 'layout-contact.html',
                'name'        => __('Layout: Contact Info', 'picot-theme-seeder'),
                'description' => __('Phone and form contact options in two columns.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutSteps',
                'file'        => 'layout-steps.html',
                'name'        => __('Layout: Steps', 'picot-theme-seeder'),
                'description' => __('Numbered step-by-step list.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutTable',
                'file'        => 'layout-table.html',
                'name'        => __('Layout: Table', 'picot-theme-seeder'),
                'description' => __('Generic two-column item and description table.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutAccordion',
                'file'        => 'layout-accordion.html',
                'name'        => __('Layout: Accordion', 'picot-theme-seeder'),
                'description' => __('Three expandable details sections.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'parts.layoutMainAside',
                'file'        => 'layout-main-aside.html',
                'name'        => __('Layout: Main + Aside', 'picot-theme-seeder'),
                'description' => __('Wide main text column with a narrow side note.', 'picot-theme-seeder'),
            ),
        );

        foreach ($items as &$item) {
            $item['checked'] = false;
        }
        unset($item);

        return $this->format_block_file_definitions($items);
    }

    /**
     * Build display name with filename (and optional suffix) for block definitions.
     *
     * @param array<int, array<string, string>> $items Raw definition rows.
     * @return array<int, array<string, string>>
     */
    private function format_block_file_definitions($items)
    {
        foreach ($items as &$item) {
            if (! array_key_exists('checked', $item)) {
                $item['checked'] = true;
            }
            if (! empty($item['suffix'])) {
                $item['name'] = sprintf(
                    /* translators: 1: label, 2: filename, 3: note (e.g. required) */
                    __('%1$s (%2$s) — %3$s', 'picot-theme-seeder'),
                    $item['name'],
                    $item['file'],
                    $item['suffix']
                );
            } else {
                $item['name'] = sprintf(
                    /* translators: 1: label, 2: filename */
                    __('%1$s (%2$s)', 'picot-theme-seeder'),
                    $item['name'],
                    $item['file']
                );
            }
            unset($item['file'], $item['suffix']);
        }
        unset($item);

        return $items;
    }

    /**
     * Block pattern checkbox labels and short descriptions for the admin UI.
     *
     * @return array<int, array<string, string>>
     */
    private function get_block_pattern_definitions()
    {
        $items = array(
            array(
                'id'          => 'patterns.hero',
                'file'        => 'hero.php',
                'name'        => __('Hero Section', 'picot-theme-seeder'),
                'description' => __('Large top banner with headline, intro text, and a primary button.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.cta',
                'file'        => 'cta.php',
                'name'        => __('Call to Action', 'picot-theme-seeder'),
                'description' => __('Encourage visitors to contact you or take the next step.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.pricing',
                'file'        => 'pricing.php',
                'name'        => __('Pricing Table', 'picot-theme-seeder'),
                'description' => __('Two-column plan comparison (e.g. Basic / Pro).', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.features',
                'file'        => 'features.php',
                'name'        => __('Features List', 'picot-theme-seeder'),
                'description' => __('Three columns highlighting product or service features.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.about',
                'file'        => 'about.php',
                'name'        => __('About Section', 'picot-theme-seeder'),
                'description' => __('Company or personal introduction in two columns.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.textImage',
                'file'        => 'text-image.php',
                'name'        => __('Text with Image', 'picot-theme-seeder'),
                'description' => __('Headline and copy beside an image block.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.testimonials',
                'file'        => 'testimonials.php',
                'name'        => __('Testimonials', 'picot-theme-seeder'),
                'description' => __('Customer quotes in a two-column layout.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.team',
                'file'        => 'team.php',
                'name'        => __('Team', 'picot-theme-seeder'),
                'description' => __('Team member names and roles in three columns.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.stats',
                'file'        => 'stats.php',
                'name'        => __('Stats', 'picot-theme-seeder'),
                'description' => __('Key numbers (clients, support, experience, etc.).', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.logos',
                'file'        => 'logos.php',
                'name'        => __('Logo Cloud', 'picot-theme-seeder'),
                'description' => __('“Trusted by” row for client or partner logos.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.gallery',
                'file'        => 'gallery.php',
                'name'        => __('Image Gallery', 'picot-theme-seeder'),
                'description' => __('Empty gallery block ready for your photos.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.latestPosts',
                'file'        => 'latest-posts.php',
                'name'        => __('Latest Posts', 'picot-theme-seeder'),
                'description' => __('Grid of the three most recent blog posts.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.faq',
                'file'        => 'faq.php',
                'name'        => __('FAQ', 'picot-theme-seeder'),
                'description' => __('Question and answer pairs you can duplicate.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.contact',
                'file'        => 'contact.php',
                'name'        => __('Contact', 'picot-theme-seeder'),
                'description' => __('Address, email, and space for a contact form.', 'picot-theme-seeder'),
            ),
            array(
                'id'          => 'patterns.newsletter',
                'file'        => 'newsletter.php',
                'name'        => __('Newsletter', 'picot-theme-seeder'),
                'description' => __('Email signup call-to-action with subscribe button.', 'picot-theme-seeder'),
            ),
        );

        foreach ($items as &$item) {
            $item['name'] = sprintf(
                /* translators: 1: pattern title, 2: pattern filename */
                __('%1$s (%2$s)', 'picot-theme-seeder'),
                $item['name'],
                $item['file']
            );
            unset($item['file']);
        }
        unset($item);

        return $items;
    }

    private function get_classic_localize_data()
    {
        $presets_data = array(
            array(
                'id'          => 'classic.all',
                'name'        => __('Full Features', 'picot-theme-seeder'),
                'description' => __('Select all available templates and features for a complete theme.', 'picot-theme-seeder'),
                'apply'       => array(
                    'full'      => true,
                    'selection' => array(),
                ),
            ),
            array(
                'id'          => 'classic.basic',
                'name'        => __('Starter Classic', 'picot-theme-seeder'),
                'description' => __('A clean baseline with the key structural files, menus, widgets, and modern HTML support.', 'picot-theme-seeder'),
                'apply'       => array(
                    'selection' => array(
                        'templates.index'           => true,
                        'templates.header'          => true,
                        'templates.footer'          => true,
                        'templates.sidebar'         => true,
                        'features.title-tag'        => true,
                        'features.post-thumbnails'  => true,
                        'features.html5'            => true,
                        'features.menus'            => true,
                        'features.widgets'          => true,
                        'features.scss-sources'     => true,
                    ),
                ),
            ),
            array(
                'id'          => 'classic.blog',
                'name'        => __('Professional Blog', 'picot-theme-seeder'),
                'description' => __('A publishing-focused setup with search, comments, archives, pagination, and content template parts.', 'picot-theme-seeder'),
                'apply'       => array(
                    'selection' => array(
                        'templates.header'                 => true,
                        'templates.footer'                 => true,
                        'templates.sidebar'                => true,
                        'templates.comments'               => true,
                        'templates.searchform'             => true,
                        'templates.single'                 => true,
                        'templates.page'                   => true,
                        'templates.archive'                => true,
                        'templates.category'               => true,
                        'templates.tag'                    => true,
                        'templates.author'                 => true,
                        'templates.date'                   => true,
                        'templates.paged'                  => true,
                        'templates.search'                 => true,
                        'templates.404'                    => true,
                        'templates.parts-content'          => true,
                        'templates.parts-content-single'   => true,
                        'templates.parts-content-page'     => true,
                        'templates.parts-content-search' => true,
                        'templates.parts-content-none'     => true,
                        'features.title-tag'               => true,
                        'features.post-thumbnails'         => true,
                        'features.html5'                   => true,
                        'features.automatic-feed-links'    => true,
                        'features.menus'                   => true,
                        'features.multi-menus'             => true,
                        'features.widgets'                 => true,
                        'features.excerpt-length'          => true,
                        'features.custom-pagination'       => true,
                        'features.breadcrumbs'             => true,
                        'features.threaded-comments'       => true,
                        'features.post-revisions-limit'    => true,
                        'features.header-cleanup'          => true,
                        'features.remove-wp-version'       => true,
                        'features.scss-sources'            => true,
                    ),
                ),
            ),
            array(
                'id'          => 'classic.performance',
                'name'        => __('Core Performance & Security', 'picot-theme-seeder'),
                'description' => __('A lean output preset focused on cleanup, lighter front-end assets, and safe media defaults.', 'picot-theme-seeder'),
                'apply'       => array(
                    'selection' => array(
                        'templates.header'                      => true,
                        'templates.footer'                      => true,
                        'features.title-tag'                    => true,
                        'features.html5'                        => true,
                        'features.header-cleanup'               => true,
                        'features.remove-wp-version'            => true,
                        'features.disable-emojis'               => true,
                        'features.remove-jquery-migrate'        => true,
                        'features.load-block-assets-on-demand'    => true,
                        'features.disable-heartbeat'            => true,
                        'features.lazy-load-adjust'             => true,
                        'features.webp-support'                 => true,
                        'features.svg-support'                  => false,
                        'features.disable-xmlrpc'               => true,
                        'features.restrict-rest-api'            => false,
                        'features.post-revisions-limit'         => true,
                        'features.remove-global-styles'         => true,
                    ),
                ),
            ),
            array(
                'id'          => 'classic.business',
                'name'        => __('Business / Corporate', 'picot-theme-seeder'),
                'description' => __('A company-site preset with front-page support, flexible page templates, branding, and client-friendly admin touches.', 'picot-theme-seeder'),
                'apply'       => array(
                    'selection' => array(
                        'templates.header'                => true,
                        'templates.footer'                => true,
                        'templates.sidebar'               => true,
                        'templates.page'                  => true,
                        'templates.template-full-width'   => true,
                        'templates.template-no-sidebar'   => true,
                        'templates.privacy-policy'        => true,
                        'templates.front-page'            => true,
                        'features.title-tag'              => true,
                        'features.post-thumbnails'        => true,
                        'features.html5'                  => true,
                        'features.custom-logo'            => true,
                        'features.menus'                  => true,
                        'features.multi-menus'            => true,
                        'features.breadcrumbs'            => true,
                        'features.login-custom-url'       => true,
                        'features.hide-admin-bar'         => true,
                        'features.admin-footer-text'      => true,
                        'features.custom-login-style'     => true,
                        'features.dashboard-cleanup'      => true,
                        'features.content-width-limit'    => true,
                        'features.auto-update-core-minor' => true,
                        'features.scss-sources'           => true,
                    ),
                ),
            ),
            array(
                'id'          => 'classic.modern',
                'name'        => __('Modern Block Friendly', 'picot-theme-seeder'),
                'description' => __('A classic theme tuned for the block editor with richer design controls, presets, and editor styling.', 'picot-theme-seeder'),
                'apply'       => array(
                    'selection' => array(
                        'templates.header'                  => true,
                        'templates.footer'                  => true,
                        'templates.page'                    => true,
                        'templates.single'                  => true,
                        'features.title-tag'                => true,
                        'features.post-thumbnails'          => true,
                        'features.html5'                    => true,
                        'features.appearance-tools'         => true,
                        'features.align-wide'               => true,
                        'features.wp-block-styles'          => true,
                        'features.responsive-embeds'        => true,
                        'features.editor-styles'            => true,
                        'features.editor-color-palette'     => true,
                        'features.disable-custom-colors'    => true,
                        'features.editor-gradient-presets'  => true,
                        'features.disable-custom-gradients' => true,
                        'features.editor-font-sizes'        => true,
                        'features.disable-custom-font-sizes' => true,
                        'features.custom-line-height'       => true,
                        'features.custom-spacing'           => true,
                        'features.custom-units'             => true,
                        'features.link-color'               => true,
                        'features.border'                   => true,
                        'features.editor-spacing-sizes'     => true,
                        'features.lazy-load-adjust'         => true,
                        'features.webp-support'             => true,
                        'features.scss-sources'             => true,
                    ),
                ),
            ),
            array(
                'id'          => 'classic.portfolio',
                'name'        => __('Portfolio / Agency', 'picot-theme-seeder'),
                'description' => __('A presentation-oriented preset for studios and freelancers with page templates, galleries, and branded polish.', 'picot-theme-seeder'),
                'apply'       => array(
                    'selection' => array(
                        'templates.header'                => true,
                        'templates.footer'                => true,
                        'templates.page'                  => true,
                        'templates.front-page'            => true,
                        'templates.template-full-width'   => true,
                        'templates.template-no-sidebar'   => true,
                        'templates.image'                 => true,
                        'templates.attachment'            => true,
                        'features.title-tag'              => true,
                        'features.post-thumbnails'        => true,
                        'features.html5'                  => true,
                        'features.custom-logo'            => true,
                        'features.menus'                  => true,
                        'features.multi-menus'            => true,
                        'features.appearance-tools'       => true,
                        'features.align-wide'             => true,
                        'features.wp-block-styles'        => true,
                        'features.responsive-embeds'      => true,
                        'features.editor-styles'          => true,
                        'features.custom-spacing'         => true,
                        'features.custom-units'           => true,
                        'features.link-color'             => true,
                        'features.content-width-limit'    => true,
                        'features.login-custom-url'       => true,
                        'features.custom-login-style'     => true,
                        'features.scss-sources'           => true,
                    ),
                ),
            ),
            array(
                'id'          => 'classic.ecommerce',
                'name'        => __('E-Commerce Ready', 'picot-theme-seeder'),
                'description' => __('A practical WooCommerce starter with sidebar support, menus, widget areas, and conservative security defaults.', 'picot-theme-seeder'),
                'apply'       => array(
                    'selection' => array(
                        'templates.header'                => true,
                        'templates.footer'                => true,
                        'templates.sidebar'               => true,
                        'templates.page'                  => true,
                        'features.title-tag'              => true,
                        'features.post-thumbnails'        => true,
                        'features.woocommerce'            => true,
                        'features.selective-refresh'      => true,
                        'features.menus'                  => true,
                        'features.multi-menus'            => true,
                        'features.widgets'                => true,
                        'features.disable-xmlrpc'         => true,
                        'features.restrict-rest-api'      => false,
                        'features.remove-wp-version'      => true,
                        'features.auto-update-core-minor' => true,
                        'features.auto-update-plugins'    => true,
                        'features.scss-sources'           => true,
                    ),
                ),
            ),
        );

        return array(
            'presets'         => $presets_data,
            'categoryLabels'  => array('C1' => __('Classic', 'picot-theme-seeder')),
            'templateGroups'  => PTS_Classic_Definitions::get_template_groups(),
            'featureGroups'   => PTS_Classic_Definitions::get_feature_groups(),
            'strings'         => array(
                'noPresets'     => __('No presets found.', 'picot-theme-seeder'),
                'fillRequired'  => __('Please fill in Theme Name and Slug.', 'picot-theme-seeder'),
                'success'       => __('Theme generated successfully.', 'picot-theme-seeder'),
                'error'         => __('Error: ', 'picot-theme-seeder'),
                'unknownError'  => __('Unknown error', 'picot-theme-seeder'),
                'generating'    => __('Generating...', 'picot-theme-seeder'),
                'generateBtn'   => __('Generate Theme', 'picot-theme-seeder'),
                'select'        => __('Select', 'picot-theme-seeder'),
                'layoutOneColumn' => __('Default layout: 1 column', 'picot-theme-seeder'),
                'layoutTwoColumn' => __('Default layout: 2 columns', 'picot-theme-seeder'),
            ),
        );
    }

    public function render_page()
    {
        include PTS_PLUGIN_DIR . 'admin/views/main.php';
    }

    /**
     * Create a protected admin download URL for a generated ZIP.
     *
     * @param string $zip_file   Absolute ZIP path.
     * @param string $theme_slug Theme slug for the download filename.
     * @return string|WP_Error
     */
    public static function create_temp_download_url($zip_file, $theme_slug)
    {
        if (! is_string($zip_file) || '' === $zip_file || ! file_exists($zip_file)) {
            return new WP_Error('zip_missing', __('Generated ZIP file was not found.', 'picot-theme-seeder'));
        }

        $token = strtolower(wp_generate_password(20, false, false));
        $data  = array(
            'zip_file'   => $zip_file,
            'theme_slug' => sanitize_title($theme_slug),
            'created_at' => time(),
        );

        set_transient('pts_zip_' . $token, $data, 15 * MINUTE_IN_SECONDS);

        // Raw ampersands for JavaScript redirects (wp_nonce_url HTML-encodes & as &amp;).
        $url = add_query_arg(
            array(
                'action' => 'pts_download_zip',
                'token'  => $token,
            ),
            admin_url('admin-post.php')
        );

        return add_query_arg(
            '_wpnonce',
            wp_create_nonce('pts_download_zip_' . $token),
            $url
        );
    }

    /**
     * Download handler for generated ZIP files.
     */
    public static function handle_zip_download()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to download this file.', 'picot-theme-seeder'), 403);
        }

        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
        if ('' === $token) {
            wp_die(esc_html__('Missing download token.', 'picot-theme-seeder'), 400);
        }

        check_admin_referer('pts_download_zip_' . $token);

        $data = get_transient('pts_zip_' . $token);
        if (! is_array($data) || empty($data['zip_file'])) {
            wp_die(esc_html__('This download link has expired.', 'picot-theme-seeder'), 410);
        }

        $zip_file   = $data['zip_file'];
        $theme_slug = ! empty($data['theme_slug']) ? sanitize_title($data['theme_slug']) : 'theme';

        if (! file_exists($zip_file) || ! is_readable($zip_file)) {
            delete_transient('pts_zip_' . $token);
            wp_die(esc_html__('The generated ZIP file is no longer available.', 'picot-theme-seeder'), 410);
        }

        nocache_headers();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $theme_slug . '.zip"');
        header('Content-Length: ' . (string) filesize($zip_file));
        header('X-Content-Type-Options: nosniff');

        $handle = fopen($zip_file, 'rb');
        if (false === $handle) {
            wp_die(esc_html__('Could not read the generated ZIP file.', 'picot-theme-seeder'), 500);
        }

        while (! feof($handle)) {
            echo fread($handle, 8192); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary ZIP download response.
        }
        fclose($handle);

        delete_transient('pts_zip_' . $token);
        self::delete_tree(dirname($zip_file));
        exit;
    }

    /**
     * Best-effort cleanup for stale temp directories.
     *
     * @param string $type block|classic
     * @return void
     */
    public static function cleanup_stale_temp_dirs($type)
    {
        $type = ('classic' === $type) ? 'classic' : 'block';
        $base = trailingslashit(wp_upload_dir()['basedir']) . 'pts_temp/' . $type;

        if (! is_dir($base)) {
            return;
        }

        $entries = glob($base . '/*');
        if (! is_array($entries)) {
            return;
        }

        $threshold = time() - DAY_IN_SECONDS;
        foreach ($entries as $entry) {
            if (@filemtime($entry) < $threshold) {
                self::delete_tree($entry);
            }
        }
    }

    /**
     * Delete Site Editor customizations so regenerated theme files take effect.
     *
     * @param string               $theme_slug Theme slug.
     * @param array<string>|null   $slugs      Optional template/part slugs; null clears all custom entries for the theme.
     * @return int Number of database records removed.
     */
    public static function clear_custom_block_templates($theme_slug, $slugs = null)
    {
        $theme_slug = sanitize_title($theme_slug);
        if ('' === $theme_slug) {
            return 0;
        }

        $deleted = 0;

        foreach (array('wp_template', 'wp_template_part') as $post_type) {
            $templates = get_block_templates(
                array(
                    'theme' => $theme_slug,
                ),
                $post_type
            );

            if (! is_array($templates)) {
                continue;
            }

            foreach ($templates as $template) {
                if ('custom' !== $template->source || empty($template->wp_id)) {
                    continue;
                }

                if (is_array($slugs) && ! in_array($template->slug, $slugs, true)) {
                    continue;
                }

                if (wp_delete_post((int) $template->wp_id, true)) {
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            wp_clean_themes_cache();
        }

        return $deleted;
    }

    /**
     * Remove a directory tree.
     *
     * @param string $path Absolute file or directory path.
     * @return void
     */
    public static function delete_tree($path)
    {
        if (! is_string($path) || '' === $path || ! file_exists($path)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        if (! $wp_filesystem) {
            return;
        }

        if ($wp_filesystem->is_file($path) || is_link($path)) {
            wp_delete_file($path);
            return;
        }

        $wp_filesystem->rmdir($path, true);
    }
}
