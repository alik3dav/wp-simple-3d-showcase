=== WP 3D Showcase ===
Contributors: your-wporg-username
Tags: 3d, glb, viewer, threejs, product-showcase
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show self-hosted GLB models with orbit controls, explode view, fullscreen mode, and part inspection.

== Description ==

WP 3D Showcase lets you publish interactive GLB viewers without relying on remote runtime dependencies. The plugin bundles its JavaScript locally, uses WordPress media attachments for model and HDRI files, and provides a shortcode-first workflow that is ready for WordPress.org review.

Features include:

* Self-contained front-end bundle with no CDN or import-map dependencies.
* Orbit, zoom, and pan controls powered by bundled Three.js assets.
* Fullscreen, reset view, auto-rotate, explode view, and focus mode controls.
* Double-click part selection with hover feedback and a compact part details card.
* Automatic GLB mesh-part detection in the editor for explode configuration.
* Global HDRI lighting and per-item visual settings.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install it through **Plugins > Add New**.
2. Activate **WP 3D Showcase**.
3. Upload `.glb` and optional `.hdr` files to the WordPress Media Library.
4. Create a new **3D Showcase** item and select a GLB file in the **3D Model Settings** metabox.
5. Insert the shortcode `[wp3ds_viewer id="123"]` or `[wp3ds_viewer slug="example-item"]` into any post or page.

== Frequently Asked Questions ==

= Can I use external model URLs? =

No. The plugin only accepts self-hosted Media Library attachments for GLB and HDRI files so the viewer stays self-contained and WordPress.org-friendly.

= How do I configure explode view parts? =

After selecting a GLB file, the editor detects mesh parts automatically. You can then adjust each part name, description, characteristics, and explode direction in the metabox.

= Does the plugin support Gutenberg blocks? =

Not yet. The current release is shortcode-first, but the codebase is organized so block support can be added later.

== Screenshots ==

1. Front-end 3D viewer with toolbar controls and part details card.
2. 3D Showcase editor metabox with GLB selection and explode-part configuration.
3. Settings screen for HDRI lighting and interaction colors.

== Changelog ==

= 1.1.0 =
* Refactored the plugin for WordPress.org readiness.
* Replaced remote Three.js import maps with local bundled assets.
* Hardened Media Library handling, sanitization, escaping, REST permissions, and plugin metadata.
* Added a Vite-based build pipeline and third-party license notices.
