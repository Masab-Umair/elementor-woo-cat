<?php
/**
 * Loads and registers all Elementor widgets shipped by the plugin.
 *
 * @package Elementor_Woo_Catalog_Builder
 */

namespace Elementor_Woo_Catalog_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Widgets_Loader
 */
final class Widgets_Loader {

	/**
	 * Requires each widget class file and registers an instance of it
	 * with Elementor's widgets manager.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public static function register_all( $widgets_manager ) {
		require_once EWCB_PLUGIN_DIR . 'widgets/class-widget-product-catalog.php';
		require_once EWCB_PLUGIN_DIR . 'widgets/class-widget-single-product.php';

		$widgets_manager->register( new \Elementor_Woo_Catalog_Builder\Widgets\Widget_Product_Catalog() );
		$widgets_manager->register( new \Elementor_Woo_Catalog_Builder\Widgets\Widget_Single_Product() );
	}
}
