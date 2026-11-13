# Elementor Woo Catalog Builder

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
- Optional quick view, wishlist, and compare icons on each product card, with always-visible or hover-only display and configurable position; wishlist and compare selections are stored in the visitor's browser and emit custom events for integrations
- Lightweight inline SVG icons without external icon-font requests, plus independent card/image/text/button styling controls
- Single product showcase with three gallery styles and a sticky add-to-cart bar
- Elementor Style tab controls for the image, text container, title, category, price, action icons, and add-to-cart button, including typography, color, border, shadow, padding, and margins
- Conditional asset loading — styles and scripts only load on pages where a widget is actually present

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