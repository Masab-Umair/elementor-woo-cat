<?php
/**
 * Core plugin controller.
 *
 * @package Elementor_Woo_Catalog_Builder
 */

namespace Elementor_Woo_Catalog_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Singleton bootstrap: registers the Elementor widget category, loads
 * widgets, wires conditional asset enqueueing, and boots the AJAX
 * filter handler.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Tracks whether an EWCB widget was rendered on the current request
	 * so we only print assets when actually needed (server-side guard
	 * in addition to the editor/preview checks below).
	 *
	 * @var bool
	 */
	public $assets_required = false;

	/**
	 * Get (and lazily create) the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — wires all hooks. Kept private; use ::instance().
	 */
	private function __construct() {
		require_once EWCB_PLUGIN_DIR . 'includes/class-render-helpers.php';
		require_once EWCB_PLUGIN_DIR . 'includes/class-ajax-filter.php';
		require_once EWCB_PLUGIN_DIR . 'includes/class-widgets-loader.php';

		add_action( 'elementor/elements/categories_registered', array( $this, 'register_widget_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Conditional, scoped asset registration (not enqueue — enqueue happens per-widget in render()).
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		// Always register assets in the editor/preview context so widgets render correctly while being edited.
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'force_enqueue_assets' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'force_enqueue_assets' ) );

		Ajax_Filter::instance();
	}

	/**
	 * Registers a dedicated "WooCommerce Catalog" widget category so the
	 * plugin's widgets are grouped together in the Elementor panel.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_widget_category( $elements_manager ) {
		$elements_manager->add_category(
			'ewcb-category',
			array(
				'title' => __( 'Woo Catalog Builder', 'ewcb' ),
				'icon'  => 'eicon-woocommerce',
			)
		);
	}

	/**
	 * Registers all plugin widgets with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		Widgets_Loader::register_all( $widgets_manager );
	}

	/**
	 * Registers (but does not enqueue) the plugin's CSS/JS so that
	 * individual widgets can enqueue them only when they actually render
	 * on the page — this keeps pages without the widgets completely free
	 * of the extra payload.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'ewcb-catalog-layouts',
			EWCB_PLUGIN_URL . 'assets/css/catalog-layouts.css',
			array(),
			EWCB_VERSION
		);

		wp_register_script(
			'ewcb-catalog-frontend',
			EWCB_PLUGIN_URL . 'assets/js/catalog-frontend.js',
			array(),
			EWCB_VERSION,
			true
		);

		wp_register_script(
			'ewcb-ajax-filter',
			EWCB_PLUGIN_URL . 'assets/js/ajax-filter.js',
			array( 'ewcb-catalog-frontend' ),
			EWCB_VERSION,
			true
		);

		wp_localize_script(
			'ewcb-ajax-filter',
			'EWCB_AJAX',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'ewcb_filter_nonce' ),
				'currency'        => get_woocommerce_currency(),
				'locale'           => str_replace( '_', '-', get_locale() ),
				'price_decimals'   => wc_get_price_decimals(),
				'request_error'    => __( 'We could not update the products. Please try again.', 'ewcb' ),
			)
		);
	}

	/**
	 * Forces asset registration inside the Elementor editor/preview so
	 * widgets always render correctly while being edited, regardless of
	 * whether the page would otherwise qualify for conditional loading.
	 *
	 * @return void
	 */
	public function force_enqueue_assets() {
		$this->register_assets();
		$this->enqueue_catalog_assets();
		$this->enqueue_filter_assets();
	}

	/**
	 * Enqueues the base catalog CSS/JS. Called from a widget's render()
	 * method the moment that widget is actually output, so pages without
	 * any EWCB widget never load this payload.
	 *
	 * @return void
	 */
	public function enqueue_catalog_assets() {
		wp_enqueue_style( 'ewcb-catalog-layouts' );
		wp_enqueue_script( 'ewcb-catalog-frontend' );
	}

	/**
	 * Enqueues the AJAX filter script. Called only by widgets that have
	 * the sidebar filter toggle switched on.
	 *
	 * @return void
	 */
	public function enqueue_filter_assets() {
		wp_enqueue_script( 'ewcb-ajax-filter' );
	}
}
