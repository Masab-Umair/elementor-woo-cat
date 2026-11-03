<?php
/**
 * Plugin Name:       Elementor Woo Catalog Builder
 * Plugin URI:         https://example.com/elementor-woo-catalog-builder
 * Description:        Pixel-perfect WooCommerce product catalogs, archives, single product layouts, AJAX side-filters and interactive grids/sliders — fully controllable inside the Elementor Editor.
 * Version:             1.2
 * Requires at least:   6.0
 * Requires PHP:        7.4
 * Author:              Masab
 * Author URI:          https://masab.vercel.app
 * Text Domain:         ewcb
 * Domain Path:         /languages
 * Requires Plugins:    woocommerce, elementor
 *
 * @package Elementor_Woo_Catalog_Builder
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin constants.
 */
define( 'EWCB_VERSION', '1.0.0' );
define( 'EWCB_PLUGIN_FILE', __FILE__ );
define( 'EWCB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'EWCB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EWCB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EWCB_MINIMUM_ELEMENTOR_VERSION', '3.5.0' );
define( 'EWCB_MINIMUM_PHP_VERSION', '7.4' );
define( 'EWCB_MINIMUM_WC_VERSION', '6.0.0' );

/**
 * Bootstraps the plugin after all plugins are loaded so we can safely
 * detect WooCommerce and Elementor.
 *
 * @return void
 */
function ewcb_bootstrap() {

	// Load translations early.
	load_plugin_textdomain( 'ewcb', false, dirname( EWCB_PLUGIN_BASENAME ) . '/languages' );

	if ( ! ewcb_passes_requirements() ) {
		return;
	}

	require_once EWCB_PLUGIN_DIR . 'includes/class-plugin.php';

	// Boot the singleton plugin controller.
	\Elementor_Woo_Catalog_Builder\Plugin::instance();
}
add_action( 'plugins_loaded', 'ewcb_bootstrap', 20 );

/**
 * Verifies PHP version, WooCommerce and Elementor are active and recent
 * enough. Surfaces an admin notice for each failed check instead of
 * fataling the site.
 *
 * @return bool
 */
function ewcb_passes_requirements() {
	$ok = true;

	if ( version_compare( PHP_VERSION, EWCB_MINIMUM_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'ewcb_notice_min_php' );
		$ok = false;
	}

	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'ewcb_notice_missing_elementor' );
		$ok = false;
	} elseif ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, EWCB_MINIMUM_ELEMENTOR_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'ewcb_notice_min_elementor' );
		$ok = false;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'ewcb_notice_missing_woocommerce' );
		$ok = false;
	} elseif ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, EWCB_MINIMUM_WC_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'ewcb_notice_min_wc' );
		$ok = false;
	}

	return $ok;
}

/**
 * Shared notice renderer.
 *
 * @param string $message Already-escaped HTML message.
 * @return void
 */
function ewcb_render_notice( $message ) {
	printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', wp_kses_post( $message ) );
}

function ewcb_notice_min_php() {
	ewcb_render_notice(
		sprintf(
			/* translators: 1: Required PHP version 2: Current PHP version */
			esc_html__( 'Elementor Woo Catalog Builder requires PHP %1$s or greater. Your server runs PHP %2$s.', 'ewcb' ),
			EWCB_MINIMUM_PHP_VERSION,
			PHP_VERSION
		)
	);
}

function ewcb_notice_missing_elementor() {
	ewcb_render_notice( esc_html__( 'Elementor Woo Catalog Builder requires Elementor to be installed and activated.', 'ewcb' ) );
}

function ewcb_notice_min_elementor() {
	ewcb_render_notice(
		sprintf(
			/* translators: %s: Required Elementor version */
			esc_html__( 'Elementor Woo Catalog Builder requires Elementor version %s or greater.', 'ewcb' ),
			EWCB_MINIMUM_ELEMENTOR_VERSION
		)
	);
}

function ewcb_notice_missing_woocommerce() {
	ewcb_render_notice( esc_html__( 'Elementor Woo Catalog Builder requires WooCommerce to be installed and activated.', 'ewcb' ) );
}

function ewcb_notice_min_wc() {
	ewcb_render_notice(
		sprintf(
			/* translators: %s: Required WooCommerce version */
			esc_html__( 'Elementor Woo Catalog Builder requires WooCommerce version %s or greater.', 'ewcb' ),
			EWCB_MINIMUM_WC_VERSION
		)
	);
}

/**
 * Clean up transients on deactivation. We intentionally do not remove
 * any user content or settings — only transient query caches.
 *
 * @return void
 */
function ewcb_deactivate() {
	global $wpdb;
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '\_transient\_ewcb\_%'
		 OR option_name LIKE '\_transient\_timeout\_ewcb\_%'"
	);
}
register_deactivation_hook( __FILE__, 'ewcb_deactivate' );
