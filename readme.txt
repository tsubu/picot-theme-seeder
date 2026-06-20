=== Picot Theme Seeder ===
Contributors: tsubu
Donate link: https://picot.tokyo/aio/
Tags: theme, block theme, classic theme, fse, generator
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate Block (FSE) or Classic WordPress theme skeletons from a single admin wizard. Requires WordPress 7.0 or later.

== Description ==

Picot Theme Seeder is a WordPress plugin developed by Toshifumi Tsuburaya (PICOT) that helps developers and site builders create production-ready theme skeletons in minutes—without copying boilerplate by hand.

Choose your theme type once, configure templates and features step by step, then write the theme directly to `wp-content/themes/` or download a ZIP archive.

= Key Features =

* **Unified wizard**: One admin screen for both Block (FSE) and Classic themes.
* **Block theme (FSE) generation**: `theme.json`, HTML templates, template parts, and 15+ block patterns (hero, CTA, pricing, FAQ, contact, and more).
* **Classic theme generation**: Bootstrap-based `style.css`, `functions.php`, PHP templates, template parts, optional SCSS sources, and theme supports (menus, thumbnails, HTML5, security helpers, block editor options, auto-updates, and more).
* **Preset configurations**: Quick-start bundles for block themes (Bare Minimum, Editorial Blog, Marketing / Landing, Business / Corporate, Design System Starter, All-in-One) and classic themes (Full Features, Starter Classic, Professional Blog, Core Performance & Security, Business / Corporate, Modern Starter, Portfolio / Agency, E-Commerce Starter).
* **Granular control**: Pick individual templates, parts, patterns, and `functions.php` features with inline descriptions for each option.
* **Flexible output**: Write directly to the themes directory (default) or download a ZIP file.
* **Japanese-ready admin UI**: Translation files included for the plugin settings screen.
* **WordPress 7.0+ theme APIs**: Block themes can include border radius presets and form element styles in `theme.json`; classic themes can opt into on-demand block CSS, link color, border controls, and editor spacing presets.

= Block Theme Wizard =

1. **Basic information** — Theme name, slug, author, and description.
2. **Presets** — Apply a recommended selection of templates, parts, and patterns.
3. **Features** — Choose templates, template parts, block patterns, and generation options (`theme.json`, style variations, SCSS sources).
4. **Theme JSON** — Layout widths, brand colors, typography settings, plus WordPress 7.0+ options (border radius presets, form element styles).
5. **Preview** — Review selected items before generating.
6. **Output** — Install to `wp-content/themes/` or download ZIP.

= Classic Theme Wizard =

1. **Basic information** — Theme metadata, root layout settings, and help text under each field.
2. **Presets** — Full Features, Starter Classic, Professional Blog, Core Performance & Security, Business / Corporate, Modern Starter, Portfolio / Agency, or E-Commerce Starter bundles.
3. **Templates** — Core PHP templates (header, footer, archives, singles, pages, 404, etc.).
4. **Theme features** — Optional code injected into `functions.php`.
5. **Preview** — Review the file list and enabled features before generating.
6. **Output** — Install to `wp-content/themes/` or download ZIP.

This plugin does **not** connect to external APIs or third-party services. All files are generated locally on your server.

== Installation ==

1. Use **WordPress 7.0 or later**.
2. Upload the `picot-theme-seeder` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Open **Theme Seeder** in the admin menu (dashicon: art).
5. Choose **Block Theme (FSE)** or **Classic Theme**, then click **Continue**.
6. Complete each wizard step and click **Generate Theme** on the final screen.

The generated theme folder appears under `wp-content/themes/{your-theme-slug}/`. Activate it from **Appearance → Themes**.

== Frequently Asked Questions ==

= What WordPress version is required? =

WordPress **7.0 or later**. The plugin does not load on older versions and shows an admin notice instead.

= Who can generate themes? =

Only users with the `manage_options` capability (typically Administrators) can access the wizard and generate themes.

= What is the default output location? =

By default, the plugin writes files directly to `wp-content/themes/`. You can switch to **Download ZIP** on the last step if you prefer to install the theme manually.

= Does the Home template get selected by default? =

For both block and classic themes, **Front Page** is checked by default. **Home** (blog posts index) is unchecked by default because many sites use a static front page; enable it when you need a separate blog index template.

= Does the generated classic theme include Bootstrap? =

Yes. Generated classic themes load **Bootstrap 5.3.8** from a CDN in `functions.php`. Templates use Bootstrap layout classes (`.container`, `.row`, `.col-*`, navbar, cards). Optional SCSS sources under `assets/scss/` mirror `style.css` for local builds (`npm run build:css`).

= Are the block patterns translated? =

Pattern **files** use English placeholder content suitable for customization. The **plugin admin UI** supports Japanese via included language files.

= Will generating a theme overwrite an existing theme? =

No. If a folder with the same slug already exists under `wp-content/themes/`, generation stops with an error. Use a unique slug or remove/back up the existing theme first.

== Screenshots ==

1. Choose Block Theme (FSE) or Classic Theme on the first screen.
2. Block theme wizard — preset selection and template checkboxes with descriptions.
3. Classic theme wizard — basic information with per-field help text.
4. Classic theme wizard — template and features selection.
5. Output step — write to themes directory or download ZIP.

== Changelog ==

= 1.0.0 =
* Initial release: Block and Classic theme generators in one plugin (WordPress 7.0+).
* Block themes: presets, templates, parts, patterns, and `theme.json` generation (including border radius presets and form element styles).
* Classic themes: Bootstrap layout, PHP templates, `functions.php` features (link color, border, editor spacing, on-demand block CSS, automatic updates), optional SCSS, and preview step.
* Default output: write directly to `wp-content/themes/`.
* Japanese translation for the admin interface.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
