=== Picot Theme Seeder ===
Contributors: tsubu
Donate link: https://github.com/tsubu/picot-theme-seeder
Tags: theme, block theme, classic theme, fse, generator
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate complete, ready-to-use Block (FSE) or Classic WordPress themes from a visual admin wizard.

== Description ==

Picot Theme Seeder is a visual WordPress theme generator developed by Toshifumi Tsuburaya (PICOT).
It allows site owners and administrators to create complete, ready-to-use Block (FSE) or Classic WordPress themes through a step-by-step admin interface, without writing or modifying code.
Users can configure theme information, presets, templates, patterns, theme.json settings, colors, typography, and layout options, then generate a working theme directly under wp-content/themes/ or download it as a ZIP archive.

= Key Features =

* Unified wizard: One admin screen for both Block (FSE) and Classic themes.
* Block theme (FSE) generation: theme.json, HTML templates, template parts, and 15 or more block patterns (hero, CTA, pricing, FAQ, contact, and more).
* Classic theme generation: Bootstrap-based style.css, functions.php, PHP templates, template parts, optional SCSS sources, and theme supports (menus, thumbnails, HTML5, security helpers, block editor options, auto-updates, and more).
* Preset configurations: Quick-start bundles for block themes (Bare Minimum, Editorial Blog, Marketing / Landing, Business / Corporate, Design System Starter, All-in-One) and classic themes (Full Features, Starter Classic, Professional Blog, Core Performance and Security, Business / Corporate, Modern Starter, Portfolio / Agency, E-Commerce Starter).
* Granular control: Pick individual templates, parts, patterns, and functions.php features with inline descriptions for each option.
* Flexible output: Write directly to the themes directory (default) or download a ZIP file.
* Japanese-ready admin UI: Translation files included for the plugin settings screen.
* WordPress 7.0 or later theme APIs: Block themes can include border radius presets and form element styles in theme.json. Classic themes can opt into on-demand block CSS, link color, border controls, and editor spacing presets.

= Block Theme Wizard =

1. Basic information: Theme name, slug, author, and description.
2. Presets: Apply a recommended selection of templates, parts, and patterns.
3. Features: Choose templates, template parts, block patterns, and generation options (theme.json, style variations, SCSS sources).
4. Theme JSON: Layout widths, brand colors, typography settings, plus WordPress 7.0 or later options (border radius presets, form element styles).
5. Preview: Review selected items before generating.
6. Output: Install to wp-content/themes/ or download ZIP.

= Classic Theme Wizard =

1. Basic information: Theme metadata, root layout settings, and help text under each field.
2. Presets: Full Features, Starter Classic, Professional Blog, Core Performance and Security, Business / Corporate, Modern Starter, Portfolio / Agency, or E-Commerce Starter bundles.
3. Templates: Core PHP templates (header, footer, archives, singles, pages, 404, and so on).
4. Theme features: Optional code injected into functions.php.
5. Preview: Review the file list and enabled features before generating.
6. Output: Install to wp-content/themes/ or download ZIP.

This plugin does not connect to external APIs or third-party services. All files are generated locally on your server.

== Installation ==

1. Use WordPress 7.0 or later and PHP 8.3 or later.
2. Upload the picot-theme-seeder folder to the /wp-content/plugins/ directory, or install the plugin through the WordPress plugins screen.
3. Activate the plugin through the Plugins menu in WordPress.
4. Open Theme Seeder in the admin menu (dashicon: art).
5. Choose Block Theme (FSE) or Classic Theme, then click Continue.
6. Complete each wizard step and click Generate Theme on the final screen.

The generated theme folder appears under wp-content/themes/{your-theme-slug}/. Activate it from Appearance > Themes.

== Frequently Asked Questions ==

= What WordPress version is required? =

WordPress 7.0 or later. The plugin does not load on older versions and shows an admin notice instead.

= What PHP version is required? =

PHP 8.3 or later.

= Is this a theme boilerplate, framework, or library? =

No.
Picot Theme Seeder is not a framework, library, or code-only boilerplate. It provides a graphical administration interface that generates complete WordPress themes.
Users do not need to edit the plugin code or write theme files manually. The generated theme can be activated and used immediately as a functional WordPress theme.

= Who can generate themes? =

Only users with the manage_options capability (typically Administrators) can access the wizard and generate themes.

= What is the default output location? =

By default, the plugin writes files directly to wp-content/themes/. You can switch to Download ZIP on the last step if you prefer to install the theme manually.

= Does the Home template get selected by default? =

For both block and classic themes, Front Page is checked by default. Home (blog posts index) is unchecked by default because many sites use a static front page. Enable it when you need a separate blog index template.

= Does the generated classic theme include Bootstrap? =

Yes. Generated classic themes load Bootstrap 5.3.8 from a CDN in functions.php. Templates use Bootstrap layout classes such as .container, .row, .col-*, navbar, and cards. Optional SCSS sources under assets/scss/ mirror style.css for local builds (npm run build:css).

= Are the block patterns translated? =

Pattern files use English placeholder content suitable for customization. The plugin admin UI supports Japanese via included language files.

= Will generating a theme overwrite an existing theme? =

No. If a folder with the same slug already exists under wp-content/themes/, generation stops with an error. Use a unique slug or remove or back up the existing theme first.

== Screenshots ==

1. Choose Block Theme (FSE) or Classic Theme on the first screen.
2. Block theme wizard: preset selection and template checkboxes with descriptions.
3. Classic theme wizard: basic information with per-field help text.
4. Classic theme wizard: template and features selection.
5. Output step: write to themes directory or download ZIP.
6. A generated theme running on a WordPress site.

== Changelog ==

= 1.0.0 =
* Initial release: Block and Classic theme generators in one plugin (WordPress 7.0 or later).
* Block themes: presets, templates, parts, patterns, and theme.json generation (including border radius presets and form element styles).
* Classic themes: Bootstrap layout, PHP templates, functions.php features (link color, border, editor spacing, on-demand block CSS, automatic updates), optional SCSS, and preview step.
* Default output: write directly to wp-content/themes/.
* Japanese translation for the admin interface.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
