<?php
/**
 * Product Catalog widget: AJAX-filterable, grid/slider WooCommerce
 * archive block fully controlled from the Elementor panel.
 *
 * @package Elementor_Woo_Catalog_Builder
 */

namespace Elementor_Woo_Catalog_Builder\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor_Woo_Catalog_Builder\Plugin;
use Elementor_Woo_Catalog_Builder\Render_Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Widget_Product_Catalog
 */
class Widget_Product_Catalog extends Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'ewcb-product-catalog';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'Product Catalog', 'ewcb' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-woocommerce';
	}

	/**
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'ewcb-category' );
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'woocommerce', 'products', 'catalog', 'grid', 'shop', 'archive', 'filter', 'slider' );
	}

	/**
	 * Declares the scripts this widget depends on so Elementor loads
	 * them in the editor/preview context automatically.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( 'ewcb-catalog-frontend', 'ewcb-ajax-filter' );
	}

	/**
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( 'ewcb-catalog-layouts' );
	}

	/**
	 * Registers all Content + Style tab controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_content_layout_controls();
		$this->register_content_filter_controls();
		$this->register_content_card_controls();
		$this->register_style_grid_controls();
		$this->register_style_card_controls();
		$this->register_style_typography_controls();
		$this->register_style_filter_controls();
	}

	/* ------------------------------------------------------------------ *
	 *  CONTENT TAB
	 * ------------------------------------------------------------------ */

	/**
	 * Layout preset, columns, grid/slider toggle, ordering, pagination.
	 *
	 * @return void
	 */
	private function register_content_layout_controls() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'layout_preset',
			array(
				'label'   => __( 'Catalog Preset', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'minimal_modern',
				'options' => array(
					'minimal_modern'  => __( 'Minimal Modern', 'ewcb' ),
					'luxury_card'     => __( 'Luxury Card', 'ewcb' ),
					'compact_grid'    => __( 'Compact Grid', 'ewcb' ),
					'editorial'       => __( 'Editorial', 'ewcb' ),
					'clean_bordered'  => __( 'Clean Bordered', 'ewcb' ),
				),
				'prefix_class' => 'ewcb-preset-',
			)
		);

		$this->add_control(
			'display_mode',
			array(
				'label'        => __( 'Display Mode', 'ewcb' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => array(
					'grid'   => array(
						'title' => __( 'Grid', 'ewcb' ),
						'icon'  => 'eicon-gallery-grid',
					),
					'slider' => array(
						'title' => __( 'Slider', 'ewcb' ),
						'icon'  => 'eicon-slider-push',
					),
				),
				'default'      => 'grid',
				'toggle'       => false,
				'label_block'  => false,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => __( 'Columns', 'ewcb' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '4',
				'tablet_default' => '3',
				'mobile_default' => '2',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'selectors'      => array(
					'{{WRAPPER}} .ewcb-grid' => '--ewcb-columns: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'row_gap',
			array(
				'label'      => __( 'Row Gap', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => 28,
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-grid' => '--ewcb-row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'column_gap',
			array(
				'label'      => __( 'Column Gap', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => 24,
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-grid' => '--ewcb-col-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'products_per_page',
			array(
				'label'   => __( 'Products Per Page', 'ewcb' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 48,
				'default' => 12,
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => __( 'Order By', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'menu_order',
				'options' => array(
					'menu_order'  => __( 'Default Sorting', 'ewcb' ),
					'popularity'  => __( 'Popularity', 'ewcb' ),
					'newest'      => __( 'Newest First', 'ewcb' ),
					'price_low'   => __( 'Price: Low to High', 'ewcb' ),
					'price_high'  => __( 'Price: High to Low', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'show_pagination',
			array(
				'label'        => __( 'Show Pagination', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'condition'    => array(
					'display_mode' => 'grid',
				),
			)
		);

		$this->end_controls_section();

		// Slider-specific settings.
		$this->start_controls_section(
			'section_slider',
			array(
				'label'     => __( 'Slider Settings', 'ewcb' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'display_mode' => 'slider',
				),
			)
		);

		$this->add_control(
			'slider_autoplay',
			array(
				'label'   => __( 'Autoplay', 'ewcb' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'slider_autoplay_speed',
			array(
				'label'     => __( 'Autoplay Speed (ms)', 'ewcb' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 4000,
				'condition' => array(
					'slider_autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'slider_loop',
			array(
				'label'   => __( 'Infinite Loop', 'ewcb' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'slider_arrows',
			array(
				'label'   => __( 'Show Arrows', 'ewcb' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'slider_dots',
			array(
				'label'   => __( 'Show Dots', 'ewcb' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * AJAX sidebar filter toggle and which filter blocks to expose.
	 *
	 * @return void
	 */
	private function register_content_filter_controls() {
		$this->start_controls_section(
			'section_filters',
			array(
				'label' => __( 'AJAX Side Filters', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_filters',
			array(
				'label'        => __( 'Enable Sidebar Filters', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'label_on'     => __( 'On', 'ewcb' ),
				'label_off'    => __( 'Off', 'ewcb' ),
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'filter_category',
			array(
				'label'     => __( 'Category Filter', 'ewcb' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'enable_filters' => 'yes' ),
			)
		);

		$this->add_control(
			'filter_price',
			array(
				'label'     => __( 'Price Range Filter', 'ewcb' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'enable_filters' => 'yes' ),
			)
		);

		$this->add_control(
			'filter_attributes',
			array(
				'label'       => __( 'Attribute Filters', 'ewcb' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $this->get_product_attribute_options(),
				'condition'   => array( 'enable_filters' => 'yes' ),
				'description' => __( 'Select which product attributes (e.g. Color, Size) to expose as filters.', 'ewcb' ),
			)
		);

		$this->add_control(
			'filter_stock',
			array(
				'label'     => __( 'In-Stock Toggle', 'ewcb' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'enable_filters' => 'yes' ),
			)
		);

		$this->add_control(
			'filter_sidebar_width',
			array(
				'label'      => __( 'Sidebar Width', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 180,
						'max' => 400,
					),
					'%'  => array(
						'min' => 15,
						'max' => 40,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => 260,
				),
				'condition' => array( 'enable_filters' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-layout' => '--ewcb-sidebar-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'filter_collapsible_mobile',
			array(
				'label'        => __( 'Collapsible on Mobile', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'condition'    => array( 'enable_filters' => 'yes' ),
				'description'  => __( 'Shows the filters inside a slide-out drawer on small screens.', 'ewcb' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Image aspect ratio, hover image swap, quick view / wishlist / compare toggles.
	 *
	 * @return void
	 */
	private function register_content_card_controls() {
		$this->start_controls_section(
			'section_card_content',
			array(
				'label' => __( 'Product Card', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'image_ratio',
			array(
				'label'   => __( 'Image Aspect Ratio', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1/1',
				'options' => array(
					'1/1'  => __( 'Square (1:1)', 'ewcb' ),
					'4/5'  => __( 'Portrait (4:5)', 'ewcb' ),
					'3/4'  => __( 'Portrait (3:4)', 'ewcb' ),
					'16/9' => __( 'Landscape (16:9)', 'ewcb' ),
					'4/3'  => __( 'Landscape (4:3)', 'ewcb' ),
					'auto' => __( 'Original', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'image_fit',
			array(
				'label'     => __( 'Image Fit', 'ewcb' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'   => __( 'Cover', 'ewcb' ),
					'contain' => __( 'Contain', 'ewcb' ),
				),
				'condition' => array(
					'image_ratio!' => 'auto',
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'hover_image_swap',
			array(
				'label'        => __( 'Hover Image Swap', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'description'  => __( 'Fades to the product\'s second gallery image on hover, when available.', 'ewcb' ),
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_quick_view',
			array(
				'label'        => __( 'Quick View Icon', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_wishlist',
			array(
				'label'        => __( 'Wishlist Icon', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_compare',
			array(
				'label'        => __( 'Compare Icon', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ *
	 *  STYLE TAB
	 * ------------------------------------------------------------------ */

	/**
	 * Grid/background spacing styles.
	 *
	 * @return void
	 */
	private function register_style_grid_controls() {
		$this->start_controls_section(
			'section_style_grid',
			array(
				'label' => __( 'Grid / Container', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'grid_background',
			array(
				'label'     => __( 'Background Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ewcb-layout' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'grid_padding',
			array(
				'label'      => __( 'Padding', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-layout' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Card background, border, radius, shadow and hover elevation.
	 *
	 * @return void
	 */
	private function register_style_card_controls() {
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => __( 'Product Card', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_background',
			array(
				'label'     => __( 'Card Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => __( 'Card Padding', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_radius',
			array(
				'label'      => __( 'Corner Radius', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card, {{WRAPPER}} .ewcb-card__media' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .ewcb-card',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .ewcb-card',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow_hover',
				'label'    => __( 'Box Shadow (Hover)', 'ewcb' ),
				'selector' => '{{WRAPPER}} .ewcb-card:hover',
			)
		);

		$this->add_control(
			'card_hover_lift',
			array(
				'label'        => __( 'Lift on Hover', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'selectors_dictionary' => array(
					'yes' => 'translateY(-4px)',
					''    => 'none',
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card:hover' => 'transform: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'sale_badge_color',
			array(
				'label'     => __( 'Sale Badge Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e53935',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__badge--sale' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Action Icons Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-icon-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Title and price typography + colors.
	 *
	 * @return void
	 */
	private function register_style_typography_controls() {
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => __( 'Typography', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_title',
			array(
				'label' => __( 'Product Title', 'ewcb' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ewcb-card__title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Title Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__title a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'heading_price',
			array(
				'label'     => __( 'Price', 'ewcb' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'price_typography',
				'selector' => '{{WRAPPER}} .ewcb-card__price',
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => __( 'Price Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6b6b6b',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__price' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Sidebar filter panel styling.
	 *
	 * @return void
	 */
	private function register_style_filter_controls() {
		$this->start_controls_section(
			'section_style_filters',
			array(
				'label'     => __( 'Sidebar Filters', 'ewcb' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'enable_filters' => 'yes' ),
			)
		);

		$this->add_control(
			'filter_bg',
			array(
				'label'     => __( 'Background Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ewcb-filters' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'filter_label_typography',
				'label'    => __( 'Filter Label Typography', 'ewcb' ),
				'selector' => '{{WRAPPER}} .ewcb-filters__label',
			)
		);

		$this->add_control(
			'filter_accent_color',
			array(
				'label'     => __( 'Accent Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-filters input[type="checkbox"]' => 'accent-color: {{VALUE}};',
					'{{WRAPPER}} .ewcb-filters__price-slider .noUi-connect' => 'background: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Builds a taxonomy => label map of global WooCommerce product
	 * attributes for the "Attribute Filters" multi-select.
	 *
	 * @return array<string,string>
	 */
	private function get_product_attribute_options() {
		$options = array();

		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return $options;
		}

		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$taxonomy               = wc_attribute_taxonomy_name( $attribute->attribute_name );
			$options[ $taxonomy ] = $attribute->attribute_label;
		}

		return $options;
	}

	/* ------------------------------------------------------------------ *
	 *  RENDER
	 * ------------------------------------------------------------------ */

	/**
	 * Outputs the widget markup: optional sidebar filters + product grid/slider.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<p>' . esc_html__( 'WooCommerce is required for this widget.', 'ewcb' ) . '</p>';
			return;
		}

		$settings = $this->get_settings_for_display();

		// Conditionally load the base catalog assets now that the widget is actually rendering.
		\Elementor_Woo_Catalog_Builder\Plugin::instance()->enqueue_catalog_assets();

		$filters_enabled = 'yes' === $settings['enable_filters'];
		if ( $filters_enabled ) {
			\Elementor_Woo_Catalog_Builder\Plugin::instance()->enqueue_filter_assets();
		}

		$card_settings = array(
			'layout_preset'     => $settings['layout_preset'],
			'products_per_page' => $settings['products_per_page'],
			'orderby'           => $settings['orderby'],
			'image_ratio'       => $settings['image_ratio'],
			'show_quick_view'   => 'yes' === $settings['show_quick_view'],
			'show_wishlist'     => 'yes' === $settings['show_wishlist'],
			'show_compare'      => 'yes' === $settings['show_compare'],
			'hover_image_swap'  => 'yes' === $settings['hover_image_swap'],
		);

		$ajax_filter = \Elementor_Woo_Catalog_Builder\Ajax_Filter::instance();
		$query       = $ajax_filter->build_query( array(), $card_settings, 1 );

		$wrapper_classes = array(
			'ewcb-layout',
			$filters_enabled ? 'ewcb-layout--with-filters' : 'ewcb-layout--full',
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">

			<?php if ( $filters_enabled ) : ?>
				<?php $this->render_sidebar_filters( $settings ); ?>
			<?php endif; ?>

			<div class="ewcb-catalog-main">
				<div
					class="ewcb-grid ewcb-grid--<?php echo esc_attr( $settings['display_mode'] ); ?>"
					data-widget-id="<?php echo esc_attr( $this->get_id() ); ?>"
					data-display-mode="<?php echo esc_attr( $settings['display_mode'] ); ?>"
					data-settings="<?php echo esc_attr( wp_json_encode( $card_settings ) ); ?>"
					<?php if ( 'slider' === $settings['display_mode'] ) : ?>
						data-autoplay="<?php echo esc_attr( 'yes' === $settings['slider_autoplay'] ? '1' : '0' ); ?>"
						data-autoplay-speed="<?php echo esc_attr( $settings['slider_autoplay_speed'] ); ?>"
						data-loop="<?php echo esc_attr( 'yes' === $settings['slider_loop'] ? '1' : '0' ); ?>"
						data-arrows="<?php echo esc_attr( 'yes' === $settings['slider_arrows'] ? '1' : '0' ); ?>"
						data-dots="<?php echo esc_attr( 'yes' === $settings['slider_dots'] ? '1' : '0' ); ?>"
					<?php endif; ?>
				>
					<?php
					if ( $query->have_posts() ) {
						while ( $query->have_posts() ) {
							$query->the_post();
							Render_Helpers::render_product_card( $card_settings );
						}
						wp_reset_postdata();
					} else {
						echo '<div class="ewcb-no-products">' . esc_html__( 'No products found.', 'ewcb' ) . '</div>';
					}
					?>
				</div>

				<?php if ( 'grid' === $settings['display_mode'] && 'yes' === $settings['show_pagination'] && $query->max_num_pages > 1 ) : ?>
					<nav class="ewcb-pagination" data-max-pages="<?php echo esc_attr( $query->max_num_pages ); ?>" data-current-page="1" aria-label="<?php esc_attr_e( 'Product pagination', 'ewcb' ); ?>">
						<button type="button" class="ewcb-pagination__prev" disabled><?php Render_Helpers::icon( 'chevron-left' ); ?></button>
						<span class="ewcb-pagination__status">
							<?php
							printf(
								/* translators: 1: current page 2: total pages */
								esc_html__( 'Page %1$s of %2$s', 'ewcb' ),
								'<span class="ewcb-pagination__current">1</span>',
								'<span class="ewcb-pagination__total">' . esc_html( $query->max_num_pages ) . '</span>'
							);
							?>
						</span>
						<button type="button" class="ewcb-pagination__next"><?php Render_Helpers::icon( 'chevron-right' ); ?></button>
					</nav>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs the left-side AJAX filter panel markup.
	 *
	 * @param array $settings Widget settings_for_display().
	 * @return void
	 */
	private function render_sidebar_filters( $settings ) {
		$bounds = array( 'min' => 0, 'max' => 0 );
		if ( 'yes' === $settings['filter_price'] ) {
			$ajax_filter = \Elementor_Woo_Catalog_Builder\Ajax_Filter::instance();
			$ref         = new \ReflectionMethod( $ajax_filter, 'get_price_bounds' );
			$ref->setAccessible( true );
			$bounds = $ref->invoke( $ajax_filter, array() );
		}
		?>
		<aside class="ewcb-filters<?php echo esc_attr( 'yes' === $settings['filter_collapsible_mobile'] ? ' ewcb-filters--collapsible' : '' ); ?>" data-widget-id="<?php echo esc_attr( $this->get_id() ); ?>">

			<div class="ewcb-filters__mobile-toggle">
				<button type="button" class="ewcb-filters__toggle-btn">
					<?php Render_Helpers::icon( 'filter' ); ?>
					<?php esc_html_e( 'Filters', 'ewcb' ); ?>
				</button>
			</div>

			<div class="ewcb-filters__panel">
				<div class="ewcb-filters__panel-header">
					<span class="ewcb-filters__label ewcb-filters__title"><?php esc_html_e( 'Filters', 'ewcb' ); ?></span>
					<button type="button" class="ewcb-filters__close" aria-label="<?php esc_attr_e( 'Close filters', 'ewcb' ); ?>">
						<?php Render_Helpers::icon( 'close' ); ?>
					</button>
				</div>

				<?php if ( 'yes' === $settings['filter_category'] ) : ?>
					<div class="ewcb-filters__group" data-filter-type="category">
						<div class="ewcb-filters__label"><?php esc_html_e( 'Category', 'ewcb' ); ?></div>
						<?php
						$terms = get_terms(
							array(
								'taxonomy'   => 'product_cat',
								'hide_empty' => true,
							)
						);
						if ( ! is_wp_error( $terms ) ) :
							foreach ( $terms as $term ) :
								?>
								<label class="ewcb-filters__option">
									<input type="checkbox" name="category[]" value="<?php echo esc_attr( $term->slug ); ?>" />
									<span><?php echo esc_html( $term->name ); ?></span>
									<span class="ewcb-filters__count">(<?php echo esc_html( $term->count ); ?>)</span>
								</label>
								<?php
							endforeach;
						endif;
						?>
					</div>
				<?php endif; ?>

				<?php if ( 'yes' === $settings['filter_price'] ) : ?>
					<div class="ewcb-filters__group" data-filter-type="price">
						<div class="ewcb-filters__label"><?php esc_html_e( 'Price', 'ewcb' ); ?></div>
						<div class="ewcb-filters__price-slider"
							data-min="<?php echo esc_attr( $bounds['min'] ); ?>"
							data-max="<?php echo esc_attr( $bounds['max'] ); ?>">
							<input type="range" class="ewcb-price-min" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>" value="<?php echo esc_attr( $bounds['min'] ); ?>" />
							<input type="range" class="ewcb-price-max" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>" value="<?php echo esc_attr( $bounds['max'] ); ?>" />
						</div>
						<div class="ewcb-filters__price-values">
							<span class="ewcb-price-min-label"><?php echo wp_kses_post( wc_price( $bounds['min'] ) ); ?></span>
							<span>&ndash;</span>
							<span class="ewcb-price-max-label"><?php echo wp_kses_post( wc_price( $bounds['max'] ) ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $settings['filter_attributes'] ) ) : ?>
					<?php foreach ( $settings['filter_attributes'] as $taxonomy ) : ?>
						<?php
						if ( ! taxonomy_exists( $taxonomy ) ) {
							continue;
						}
						$attr_terms = get_terms(
							array(
								'taxonomy'   => $taxonomy,
								'hide_empty' => true,
							)
						);
						if ( is_wp_error( $attr_terms ) || empty( $attr_terms ) ) {
							continue;
						}
						$tax_object = get_taxonomy( $taxonomy );
						?>
						<div class="ewcb-filters__group" data-filter-type="attribute" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
							<div class="ewcb-filters__label"><?php echo esc_html( $tax_object ? $tax_object->labels->singular_name : $taxonomy ); ?></div>
							<?php foreach ( $attr_terms as $term ) : ?>
								<label class="ewcb-filters__option">
									<input type="checkbox" name="attributes[<?php echo esc_attr( $taxonomy ); ?>][]" value="<?php echo esc_attr( $term->slug ); ?>" />
									<span><?php echo esc_html( $term->name ); ?></span>
									<span class="ewcb-filters__count">(<?php echo esc_html( $term->count ); ?>)</span>
								</label>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ( 'yes' === $settings['filter_stock'] ) : ?>
					<div class="ewcb-filters__group" data-filter-type="stock">
						<label class="ewcb-filters__option ewcb-filters__option--toggle">
							<input type="checkbox" name="in_stock_only" value="1" />
							<span><?php esc_html_e( 'In stock only', 'ewcb' ); ?></span>
						</label>
					</div>
				<?php endif; ?>

				<button type="button" class="ewcb-filters__reset"><?php esc_html_e( 'Reset Filters', 'ewcb' ); ?></button>
			</div>
		</aside>
		<?php
	}
}
