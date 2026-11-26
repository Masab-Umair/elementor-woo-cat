# Elementor Woo Catalog Builder

**Current development version: 1.7.0**

A WooCommerce extension for Elementor that brings pixel-perfect product catalogs, archives, and single product layouts directly into the Elementor editor — no separate settings screen required.

## Overview

Elementor Woo Catalog Builder adds three native Elementor widgets built specifically for WooCommerce stores that want full design control without leaving the page editor:

**Product Catalog** — A responsive product grid or slider with configurable product queries, including category, featured, on-sale, best-selling, top-rated, and selected product IDs. It includes independent styling controls for its card, image, category, title, price, action icons, and add-to-cart button.

**Archive Product Catalog** — An archive-aware version for Elementor WooCommerce shop and product-category archive templates. It displays the current archive's products while allowing the grid and product cards to be designed in Elementor.

**Single Product Showcase** — A clean single-product layout with a configurable multi-image gallery (vertical thumbnails, horizontal thumbnails, or swipe dots), layout alignment options, and an optional sticky add-to-cart bar.

Every visual option — typography, colors, borders, shadows, spacing, columns, and layout toggles — is editable live from the Elementor panel.

## Why This Exists

Most WooCommerce + Elementor integrations fall into one of two camps: rigid theme builder add-ons with limited styling control, or bloated all-in-one plugins that load their CSS and JS on every page regardless of whether their widgets are in use. This plugin was built to avoid both. Assets are registered globally but only enqueued on the exact request where a widget actually renders, and the AJAX filtering layer uses cached, indexed WooCommerce queries rather than client-side filtering of pre-loaded product data.

## Features

- Independently configurable column counts at desktop, tablet, and mobile breakpoints
- Product query controls for current archives, product categories, featured/on-sale/best-selling/top-rated products, and included/excluded product IDs
- Dedicated Archive Product Catalog widget for designing shop and product-category archive product grids
- Bottom-aligned add-to-cart buttons across cards with different title lengths, with an option to keep buttons in normal content flow
- Grid-to-slider toggle with autoplay, loop, arrows, and dot navigation
- AJAX left-sidebar filtering by category, price range, product attributes, and stock status, backed by transient-cached WooCommerce queries
- Higher-resolution product images, adjustable image aspect ratios, contain/cover/scale-down/fill fit, and image position/padding controls
- Hover image swap using a product's secondary gallery image
- Optional quick view, wishlist, and compare icons on each product card, with always-visible or hover-only display and configurable position; the built-in wishlist and compare panels render saved products (wishlist up to 50, compare up to 4) using browser-local storage and emit custom events for integrations
- Variable products can open in Quick View with WooCommerce variation selectors and add-to-cart forms, so size/flavour/attribute choices can be made without leaving the catalog
- Product cards support configurable description visibility/length, price visibility, sale (with editable label), custom and first-product-tag badges, badge corner/alignment, button label, and a landscape 16:10 restaurant-friendly default
- Card composition controls for image placement (top/bottom/left/right with a separate mobile placement), category/title/description/footer ordering, inline or stacked price/button layout, and price-first or button-first arrangement
- Quick View Designer controls for image-left/image-right/stacked layouts, content visibility, popup width, background/overlay, radius, padding, typography colors and add-to-cart button styling
- Wishlist Designer controls for grid/list layouts, configurable columns, visibility of image/price/availability, custom heading, popup width, product-card background/border/radius and text/remove-link colors
- Compare Designer controls for table or product-card layouts, visibility of comparison fields, custom heading, popup width, background/overlay, table header/border and text/accent colors
- AJAX pagination works independently from the sidebar filters, reports request errors, and keeps normal page-link navigation as a fallback if AJAX is unavailable
- Lightweight inline SVG icons without external icon-font requests, plus independent card/image/text/button styling controls
- Single product showcase with three gallery styles and a sticky add-to-cart bar
- Elementor Style tab controls for the card shell, image, text container, title, description, category, price, badges, action icons, and add-to-cart button, including typography, color, border, shadow, padding, radius, spacing, and responsive layout values
- Conditional asset loading — styles and scripts only load on pages where a widget is actually present

## Version 1.7.0 notes

- Added flexible card composition: image placement can be top, bottom, left or right, with a separate mobile placement; category, title, description and price/button group have independent order controls.
- Added inline/stacked price and add-to-cart arrangements plus price-first/button-first ordering, which supports restaurant menus and fashion/apparel catalog layouts.
- Added dedicated Quick View, Wishlist and Compare Designer sections to the Elementor Style tab. Popup style values are passed per widget to the front-end overlays.
- Added Quick View layout selection (image-left, image-right, stacked) and visibility switches for image, title, price, category, SKU, stock status and description.
- Added Wishlist grid/list layouts, column count, product-field visibility, editable heading, popup width, colors, borders and corner radius.
- Added Compare table/card layouts, per-field visibility, editable heading, popup width, overlay/background, table header/border and text/accent styling.
- Updated the AJAX settings whitelist so card layout and popup designer settings remain available after product filtering and pagination.
- Wishlist and compare selections remain browser-local; they are not synchronized between devices or user accounts.

## Version 1.6.0 notes

- Reworked product-card markup and defaults to match the supplied restaurant card reference: 16:10 cover image, compact badge, title/description hierarchy, divider, price and rounded Add action.
- Added Elementor controls for description display and word limit, price display, editable sale badge text, badge corner/alignment, per-widget custom badge text, and CTA label. The custom badge text is applied to all products in that widget; sale status remains product-driven.
- Improved AJAX pagination with nonce/error feedback, stable event delegation, page-aware fallback links, and page-specific query arguments for standalone catalog widgets.
- Added visible wishlist and comparison panels. Lists remain browser-local and are not synchronized to user accounts or other devices.
- Quick View now renders WooCommerce's variable-product selector form where applicable, allowing standard product attributes such as size or flavour to be selected in the modal. Product add-on extensions still need to be checked on the target store.
- Corrected the plugin header and internal version constant to 1.6.0.

## Tech Stack

- PHP (object-oriented, PSR-friendly structure)
- Elementor Widgets API (`Widget_Base`, `Controls_Manager`)
- WooCommerce product and query APIs
- Vanilla JavaScript (no build step, no external runtime dependencies)
- CSS with custom properties, mapped directly to Elementor style controls

## Architecture
```
elementor-woo-catalog-builder/
├── elementor-woo-catalog-builder.php Plugin bootstrap and dependency checks
├── includes/
│ ├── class-plugin.php Core controller and asset registration
│ ├── class-widgets-loader.php Widget registration
│ ├── class-ajax-filter.php Query building, caching, AJAX endpoints
│ └── class-render-helpers.php Shared product card markup
├── widgets/
│ ├── class-widget-product-catalog.php
│ └── class-widget-single-product.php
├── assets/
│ ├── css/catalog-layouts.css
│ └── js/
│ ├── catalog-frontend.js
│ └── ajax-filter.js
└── readme.md
```

Widget classes handle only Elementor controls and rendering. Query logic, caching, and markup generation live in `includes/`, so the same product card output is used whether the page is first loaded or updated via AJAX filtering.

The Product Catalog widget inherits the active WooCommerce archive's taxonomy, metadata, and product-search constraints when used in shop, product-taxonomy, or product-search templates. On regular pages it runs its own catalog query. Wishlist and compare state is browser-local; this plugin does not synchronize those lists across devices or connect to a third-party wishlist/compare service.

## Requirements

- WordPress 6.0+
- WooCommerce 6.0+
- Elementor 3.5+
- PHP 7.4+

## Contributing

Contributions, bug reports, and feature suggestions are welcome.

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes with clear, descriptive messages
4. Open a pull request describing what changed and why

Please keep PHP changes consistent with WordPress coding standards and avoid introducing external build tooling — this project intentionally ships dependency-free JS and CSS.

## License

GPLv2 or later, consistent with the WordPress plugin ecosystem.