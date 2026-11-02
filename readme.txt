=== Elementor Woo Catalog Builder ===
Contributors: yourcompany
Tags: woocommerce, elementor, product catalog, ajax filter, product slider
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pixel-perfect WooCommerce product catalogs, archives and single product layouts with AJAX side-filters, built entirely inside the Elementor editor.

== Description ==

Elementor Woo Catalog Builder adds two native Elementor widgets:

1. **Product Catalog** — a fully responsive product grid or slider with five
   built-in presets (Minimal Modern, Luxury Card, Compact Grid, Editorial,
   Clean Bordered), configurable columns per breakpoint, image aspect
   ratios, hover image swap, and an optional AJAX left-sidebar filter
   (category, price range, attributes, in-stock toggle).

2. **Single Product Showcase** — a clean, modern single-product layout
   with a configurable multi-image gallery (vertical thumbnails,
   horizontal thumbnails, or swipe dots), layout alignment, and an
   optional sticky add-to-cart bar.

All options — typography, colors, borders, shadows, spacing, column
counts, and layout toggles — are controlled live from the Elementor
panel. No separate WP Admin settings screen is required.

Scripts and styles are registered globally but only **enqueued** on the
specific request where a plugin widget actually renders, keeping every
other page on the site free of the extra payload.

== Requirements ==

* WordPress 6.0+
* WooCommerce 6.0+
* Elementor 3.5+
* PHP 7.4+

== Installation ==

1. Upload the `elementor-woo-catalog-builder` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Ensure WooCommerce and Elementor are both active.
4. Edit any page with Elementor and find the widgets under the
   "Woo Catalog Builder" category in the widget panel.

== Changelog ==

= 1.0.0 =
* Initial release: Product Catalog and Single Product Showcase widgets,
  five catalog presets, AJAX sidebar filters, grid/slider toggle.
