# Elementor Woo Catalog Builder

A WooCommerce extension for Elementor that brings pixel-perfect product catalogs, archives, and single product layouts directly into the Elementor editor — no separate settings screen required.

## Overview

Elementor Woo Catalog Builder adds two native Elementor widgets built specifically for WooCommerce stores that want full design control without leaving the page editor:

**Product Catalog** — A responsive product grid or slider with five built-in presets (Minimal Modern, Luxury Card, Compact Grid, Editorial, Clean Bordered), per-breakpoint column control, adjustable image aspect ratios, hover image swap, and an optional AJAX sidebar filter for category, price range, attributes, and stock status.

**Single Product Showcase** — A clean single-product layout with a configurable multi-image gallery (vertical thumbnails, horizontal thumbnails, or swipe dots), layout alignment options, and an optional sticky add-to-cart bar.

Every visual option — typography, colors, borders, shadows, spacing, columns, and layout toggles — is editable live from the Elementor panel.

## Why This Exists

Most WooCommerce + Elementor integrations fall into one of two camps: rigid theme builder add-ons with limited styling control, or bloated all-in-one plugins that load their CSS and JS on every page regardless of whether their widgets are in use. This plugin was built to avoid both. Assets are registered globally but only enqueued on the exact request where a widget actually renders, and the AJAX filtering layer uses cached, indexed WooCommerce queries rather than client-side filtering of pre-loaded product data.

## Features

- Five distinct catalog presets selectable per widget instance
- Independent column counts for desktop, tablet, and mobile
- Grid-to-slider toggle with autoplay, loop, arrows, and dot navigation
- AJAX left-sidebar filtering by category, price range, product attributes, and stock status, backed by transient-cached WooCommerce queries
- Adjustable image aspect ratios with cover/contain fit control
- Hover image swap using a product's secondary gallery image
- Quick view, wishlist, and compare icons on each product card
- Single product showcase with three gallery styles and a sticky add-to-cart bar
- Full Elementor Style tab coverage: typography, color, border, shadow, and spacing controls for every element
- Conditional asset loading — styles and scripts only load on pages where a widget is actually present

## Tech Stack

- PHP (object-oriented, PSR-friendly structure)
- Elementor Widgets API (`Widget_Base`, `Controls_Manager`)
- WooCommerce product and query APIs
- Vanilla JavaScript (no build step, no external runtime dependencies)
- CSS with custom properties, mapped directly to Elementor style controls

## Architecture

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


Widget classes handle only Elementor controls and rendering. Query logic, caching, and markup generation live in `includes/`, so the same product card output is used whether the page is first loaded or updated via AJAX filtering.

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