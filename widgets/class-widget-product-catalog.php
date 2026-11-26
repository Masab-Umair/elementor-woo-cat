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
		$this->register_content_query_controls();
		$this->register_content_filter_controls();
		$this->register_content_card_controls();
		$this->register_style_grid_controls();
		$this->register_style_card_controls();
		$this->register_style_image_controls();
		$this->register_style_text_container_controls();
		$this->register_style_typography_controls();
		$this->register_style_card_detail_controls();
		$this->register_style_modal_controls();
		$this->register_style_filter_controls();
	}

	/* ------------------------------------------------------------------ *
	 *  CONTENT TAB
	 * ------------------------------------------------------------------ */

	/**
	 * Grid/slider toggle, ordering, pagination and spacing.
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
				'type'           => Controls_Manager::NUMBER,
				'min'            => 1,
				'default'        => 4,
				'tablet_default' => 3,
				'mobile_default' => 2,
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
					'rating'      => __( 'Rating', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Sort Direction', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default' => __( 'Automatic', 'ewcb' ),
					'ASC'     => __( 'Ascending', 'ewcb' ),
					'DESC'    => __( 'Descending', 'ewcb' ),
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
	 * Product query source and selection controls.
	 *
	 * @return void
	 */
	private function register_content_query_controls() {
		if ( $this->is_archive_catalog_widget() ) {
			return;
		}

		$this->start_controls_section(
			'section_query',
			array(
				'label' => __( 'Product Query', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'query_source',
			array(
				'label'       => __( 'Query Source', 'ewcb' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'    => __( 'Automatic (use archive when available)', 'ewcb' ),
					'archive' => __( 'Current Product Archive', 'ewcb' ),
					'custom'  => __( 'Custom Query', 'ewcb' ),
				),
				'description' => __( 'Use the current shop/category archive products, or configure a separate product query below.', 'ewcb' ),
			)
		);

		$this->add_control(
			'query_mode',
			array(
				'label'     => __( 'Products to Include', 'ewcb' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'all',
				'options'   => array(
					'all'         => __( 'All Products', 'ewcb' ),
					'category'    => __( 'Products from Categories', 'ewcb' ),
					'featured'    => __( 'Featured Products', 'ewcb' ),
					'on_sale'     => __( 'On Sale', 'ewcb' ),
					'best_selling' => __( 'Best Selling', 'ewcb' ),
					'top_rated'   => __( 'Top Rated', 'ewcb' ),
				),
				'condition' => array( 'query_source!' => 'archive' ),
			)
		);

		$this->add_control(
			'query_categories',
			array(
				'label'       => __( 'Categories', 'ewcb' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $this->get_product_category_options(),
				'condition'   => array(
					'query_source!' => 'archive',
					'query_mode'    => 'category',
				),
			)
		);

		$this->add_control(
			'include_products',
			array(
				'label'       => __( 'Include Product IDs', 'ewcb' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( 'Optional comma-separated product IDs. When set, only these products are queried.', 'ewcb' ),
				'condition'   => array( 'query_source!' => 'archive' ),
			)
		);

		$this->add_control(
			'exclude_products',
			array(
				'label'       => __( 'Exclude Product IDs', 'ewcb' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( 'Optional comma-separated product IDs to leave out of the results.', 'ewcb' ),
				'condition'   => array( 'query_source!' => 'archive' ),
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
	 * Image, category, action icon and add-to-cart display options.
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
				'default' => '16/10',
				'options' => array(
					'16/10' => __( 'Landscape (16:10)', 'ewcb' ),
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
					'scale-down' => __( 'Scale Down', 'ewcb' ),
					'fill'    => __( 'Fill (may distort)', 'ewcb' ),
				),
				'condition' => array(
					'image_ratio!' => 'auto',
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__media .ewcb-card__img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_position',
			array(
				'label'     => __( 'Image Position', 'ewcb' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'center center',
				'options'   => array(
					'center center' => __( 'Center Center', 'ewcb' ),
					'center top'    => __( 'Center Top', 'ewcb' ),
					'center bottom' => __( 'Center Bottom', 'ewcb' ),
					'left center'   => __( 'Left Center', 'ewcb' ),
					'right center'  => __( 'Right Center', 'ewcb' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__media .ewcb-card__img' => 'object-position: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'show_description',
			array(
				'label'        => __( 'Show Short Description', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'description_words',
			array(
				'label'     => __( 'Description Word Limit', 'ewcb' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 5,
				'max'       => 60,
				'default'   => 20,
				'condition' => array( 'show_description' => 'yes' ),
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => __( 'Show Price', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_sale_badge',
			array(
				'label'        => __( 'Show Sale Badge', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'sale_badge_label',
			array(
				'label'       => __( 'Sale Badge Text', 'ewcb' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Sale', 'ewcb' ),
				'placeholder' => __( 'Sale, Offer, Special Price', 'ewcb' ),
				'condition'   => array( 'show_sale_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'badge_position',
			array(
				'label'   => __( 'Badge Position', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'top-left',
				'options' => array(
					'top-left'     => __( 'Top Left', 'ewcb' ),
					'top-right'    => __( 'Top Right', 'ewcb' ),
					'bottom-left'  => __( 'Bottom Left', 'ewcb' ),
					'bottom-right' => __( 'Bottom Right', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'badge_alignment',
			array(
				'label'   => __( 'Badge Alignment', 'ewcb' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'left',
				'options' => array(
					'left'   => array( 'title' => __( 'Left', 'ewcb' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => __( 'Center', 'ewcb' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => __( 'Right', 'ewcb' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors_dictionary' => array(
					'left'   => 'flex-start',
					'center' => 'center',
					'right'  => 'flex-end',
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__badges' => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'show_product_tag_badge',
			array(
				'label'        => __( 'Show First Product Tag as Badge', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => __( 'Manage each product label through WooCommerce Product Tags.', 'ewcb' ),
			)
		);

		$this->add_control(
			'custom_badge_text',
			array(
				'label'       => __( 'Custom Badge Text', 'ewcb' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'e.g. Bestseller, Chef\'s Pick, New', 'ewcb' ),
				'label_block' => true,
				'description' => __( 'Optional badge shown on every product card in this widget. Leave empty to show only sale badges.', 'ewcb' ),
			)
		);

		$this->add_control(
			'card_button_label',
			array(
				'label'       => __( 'Button Label Override', 'ewcb' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Add', 'ewcb' ),
				'placeholder' => __( 'Leave empty for WooCommerce default', 'ewcb' ),
				'label_block' => true,
				'description' => __( 'For example, use “Add” for a restaurant menu or “Choose options” for configurable products.', 'ewcb' ),
			)
		);

		$this->add_control(
			'show_category',
			array(
				'label'        => __( 'Show Product Category', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
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
			'image_hover_zoom',
			array(
				'label'        => __( 'Zoom Image on Hover', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
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

		$this->add_control(
			'action_visibility',
			array(
				'label'   => __( 'Action Icons Visibility', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'always',
				'options' => array(
					'always' => __( 'Always Visible', 'ewcb' ),
					'hover'  => __( 'On Hover', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'action_position',
			array(
				'label'   => __( 'Action Icons Position', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'top-right',
				'options' => array(
					'top-right'    => __( 'Top Right', 'ewcb' ),
					'top-left'     => __( 'Top Left', 'ewcb' ),
					'bottom-right' => __( 'Bottom Right', 'ewcb' ),
					'bottom-left'  => __( 'Bottom Left', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'action_icon_layout',
			array(
				'label'   => __( 'Action Icons Layout', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'vertical',
				'options' => array(
					'vertical'   => __( 'Vertical', 'ewcb' ),
					'horizontal' => __( 'Horizontal', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'show_cart_icon',
			array(
				'label'        => __( 'Show Cart Icon in Button', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'cart_button_width',
			array(
				'label'   => __( 'Add to Cart Width', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'inline',
				'options' => array(
					'full'   => __( 'Full Width', 'ewcb' ),
					'inline' => __( 'Inline', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'cart_vertical_alignment',
			array(
				'label'       => __( 'Button Vertical Alignment', 'ewcb' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'bottom',
				'options'     => array(
					'bottom' => __( 'Align Buttons at Bottom', 'ewcb' ),
					'flow'   => __( 'Place After Product Details', 'ewcb' ),
				),
				'description' => __( 'Bottom alignment keeps add-to-cart buttons level when product titles have different lengths.', 'ewcb' ),
			)
		);


		$this->add_control(
			'card_image_placement',
			array(
				'label' => __( 'Image Placement', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'top',
				'options' => array(
					'top' => __( 'Top', 'ewcb' ),
					'bottom' => __( 'Bottom', 'ewcb' ),
					'left' => __( 'Left (Horizontal Card)', 'ewcb' ),
					'right' => __( 'Right (Horizontal Card)', 'ewcb' ),
				),
				'description' => __( 'Horizontal image placement automatically stacks above the content on small screens unless a mobile placement is selected.', 'ewcb' ),
			)
		);

		$this->add_control(
			'card_image_placement_mobile',
			array(
				'label' => __( 'Image Placement on Mobile', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'top',
				'options' => array(
					'top' => __( 'Top', 'ewcb' ),
					'bottom' => __( 'Bottom', 'ewcb' ),
					'left' => __( 'Left', 'ewcb' ),
					'right' => __( 'Right', 'ewcb' ),
				),
			)
		);

		$this->add_responsive_control(
			'card_category_order',
			array(
				'label' => __( 'Category Order', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => '1',
				'options' => array( '1' => '1 — First', '2' => '2', '3' => '3', '4' => '4', '5' => '5 — Last' ),
				'selectors' => array( '{{WRAPPER}} .ewcb-card__cat' => 'order: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'card_title_order',
			array(
				'label' => __( 'Title Order', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => '2',
				'options' => array( '1' => '1 — First', '2' => '2', '3' => '3', '4' => '4', '5' => '5 — Last' ),
				'selectors' => array( '{{WRAPPER}} .ewcb-card__title' => 'order: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'card_description_order',
			array(
				'label' => __( 'Description Order', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => '3',
				'options' => array( '1' => '1 — First', '2' => '2', '3' => '3', '4' => '4', '5' => '5 — Last' ),
				'selectors' => array( '{{WRAPPER}} .ewcb-card__description' => 'order: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'card_footer_order',
			array(
				'label' => __( 'Price / Button Group Order', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => '4',
				'options' => array( '1' => '1 — First', '2' => '2', '3' => '3', '4' => '4', '5' => '5 — Last' ),
				'selectors' => array( '{{WRAPPER}} .ewcb-card__footer' => 'order: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'card_footer_layout',
			array(
				'label' => __( 'Price / Button Arrangement', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'inline',
				'options' => array(
					'inline' => __( 'Side by Side', 'ewcb' ),
					'stacked' => __( 'Stacked Vertically', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'price_button_order',
			array(
				'label' => __( 'Price / Button Order', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'price-first',
				'options' => array(
					'price-first' => __( 'Price then Button', 'ewcb' ),
					'button-first' => __( 'Button then Price', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'card_content_alignment',
			array(
				'label' => __( 'Card Content Alignment', 'ewcb' ),
				'type' => Controls_Manager::CHOOSE,
				'default' => 'left',
				'options' => array(
					'left' => array( 'title' => __( 'Left', 'ewcb' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => __( 'Center', 'ewcb' ), 'icon' => 'eicon-text-align-center' ),
					'right' => array( 'title' => __( 'Right', 'ewcb' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors_dictionary' => array( 'left' => 'left', 'center' => 'center', 'right' => 'right' ),
				'selectors' => array( '{{WRAPPER}} .ewcb-card__body' => 'text-align: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'card_image_width',
			array(
				'label' => __( 'Horizontal Image Width', 'ewcb' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range' => array( '%' => array( 'min' => 25, 'max' => 70 ) ),
				'default' => array( 'unit' => '%', 'size' => 42 ),
				'selectors' => array( '{{WRAPPER}} .ewcb-card--image-left .ewcb-card__media, {{WRAPPER}} .ewcb-card--image-right .ewcb-card__media' => 'flex-basis: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control( 'quickview_show_image', array( 'label' => __( 'Quick View: Show Image', 'ewcb' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'quickview_show_title', array( 'label' => __( 'Quick View: Show Title', 'ewcb' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'quickview_show_price', array( 'label' => __( 'Quick View: Show Price', 'ewcb' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control(
			'quickview_show_category',
			array( 'label' => __( 'Quick View: Show Category', 'ewcb' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' )
		);
		$this->add_control(
			'quickview_show_sku',
			array( 'label' => __( 'Quick View: Show SKU', 'ewcb' ), 'type' => Controls_Manager::SWITCHER, 'default' => '', 'return_value' => 'yes' )
		);
		$this->add_control(
			'quickview_show_stock',
			array( 'label' => __( 'Quick View: Show Stock Status', 'ewcb' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' )
		);
		$this->add_control(
			'quickview_show_description',
			array( 'label' => __( 'Quick View: Show Description', 'ewcb' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' )
		);
		$this->add_control(
			'quickview_layout',
			array(
				'label' => __( 'Quick View Layout', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'image-left',
				'options' => array( 'image-left' => __( 'Image Left', 'ewcb' ), 'image-right' => __( 'Image Right', 'ewcb' ), 'stacked' => __( 'Image Above Details', 'ewcb' ) ),
			)
		);

		$this->add_control(
			'wishlist_layout',
			array(
				'label' => __( 'Wishlist Layout', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array( 'grid' => __( 'Product Grid', 'ewcb' ), 'list' => __( 'Product List', 'ewcb' ) ),
			)
		);
		$this->add_control(
			'wishlist_columns',
			array( 'label' => __( 'Wishlist Columns', 'ewcb' ), 'type' => Controls_Manager::SELECT, 'default' => '3', 'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ), 'condition' => array( 'wishlist_layout' => 'grid' ) )
		);
		$this->add_control(
			'compare_layout',
			array(
				'label' => __( 'Compare Layout', 'ewcb' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'table',
				'options' => array( 'table' => __( 'Comparison Table', 'ewcb' ), 'cards' => __( 'Product Cards', 'ewcb' ) ),
			)
		);
		foreach ( array( 'image' => __( 'Image', 'ewcb' ), 'price' => __( 'Price', 'ewcb' ), 'category' => __( 'Category', 'ewcb' ), 'stock' => __( 'Availability', 'ewcb' ), 'sku' => __( 'SKU', 'ewcb' ), 'description' => __( 'Description', 'ewcb' ) ) as $field => $label ) {
			$this->add_control(
				'compare_show_' . $field,
				array( 'label' => sprintf( __( 'Compare: Show %s', 'ewcb' ), $label ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' )
			);
		}

		$this->add_control( 'wishlist_show_image', array( 'label' => __( 'Wishlist: Show Image', 'ewcb' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'wishlist_show_price', array( 'label' => __( 'Wishlist: Show Price', 'ewcb' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'wishlist_show_stock', array( 'label' => __( 'Wishlist: Show Availability', 'ewcb' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'wishlist_title_text', array( 'label' => __( 'Wishlist Heading', 'ewcb' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Your Wishlist', 'ewcb' ) ) );
		$this->add_control( 'compare_title_text', array( 'label' => __( 'Compare Heading', 'ewcb' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Compare Products', 'ewcb' ) ) );

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
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
			'card_border_hover_color',
			array(
				'label'     => __( 'Border Color (Hover)', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d6dce7',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card:hover' => 'border-color: {{VALUE}};',
				),
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

		$this->add_control(
			'icon_button_background',
			array(
				'label'     => __( 'Action Icon Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-icon-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'icon_hover_color',
			array(
				'label'     => __( 'Action Icon Hover Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ewcb-icon-btn:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'icon_hover_background',
			array(
				'label'     => __( 'Action Icon Hover Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ewcb-icon-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'icon_button_border',
				'selector' => '{{WRAPPER}} .ewcb-icon-btn',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'icon_button_shadow',
				'selector' => '{{WRAPPER}} .ewcb-icon-btn',
			)
		);

		$this->add_control(
			'action_icon_gap',
			array(
				'label'      => __( 'Action Icon Spacing', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 32 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__actions' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'action_icon_inset',
			array(
				'label'      => __( 'Distance from Image Edge', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 48 ),
					'%'  => array( 'min' => 0, 'max' => 20 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__actions' => '--ewcb-action-inset: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'icon_size',
			array(
				'label'      => __( 'Action Icon Size', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 12, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 18 ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-icon-btn .ewcb-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'icon_button_size',
			array(
				'label'      => __( 'Action Button Size', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 24, 'max' => 64 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 36 ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-icon-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'icon_button_radius',
			array(
				'label'      => __( 'Action Button Corner Radius', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 32 ),
					'%'  => array( 'min' => 0, 'max' => 50 ),
				),
				'default'   => array( 'unit' => '%', 'size' => 50 ),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-icon-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Image area styling.
	 *
	 * @return void
	 */
	private function register_style_image_controls() {
		$this->start_controls_section(
			'section_style_image',
			array(
				'label' => __( 'Product Image', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'image_background',
			array(
				'label'     => __( 'Image Area Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f4f4f4',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__media' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_padding',
			array(
				'label'      => __( 'Image Padding', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__media-link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_radius',
			array(
				'label'      => __( 'Image Corner Radius', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__media, {{WRAPPER}} .ewcb-card__media-link, {{WRAPPER}} .ewcb-card__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'image_border',
				'selector' => '{{WRAPPER}} .ewcb-card__media',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'image_shadow',
				'selector' => '{{WRAPPER}} .ewcb-card__media',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Product text area styling independent from the image and card shell.
	 *
	 * @return void
	 */
	private function register_style_text_container_controls() {
		$this->start_controls_section(
			'section_style_text_container',
			array(
				'label' => __( 'Product Text Container', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'text_container_background',
			array(
				'label'     => __( 'Background Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__body' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_container_padding',
			array(
				'label'      => __( 'Padding', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_container_margin',
			array(
				'label'      => __( 'Margin', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__body' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'text_container_gap',
			array(
				'label'      => __( 'Content Gap', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__body' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'text_container_alignment',
			array(
				'label'   => __( 'Text Alignment', 'ewcb' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => array(
					'left'   => array( 'title' => __( 'Left', 'ewcb' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => __( 'Center', 'ewcb' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => __( 'Right', 'ewcb' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__body' => 'text-align: {{VALUE}};',
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

		$this->add_responsive_control(
			'title_padding',
			array(
				'label'      => __( 'Title Padding', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_margin',
			array(
				'label'      => __( 'Title Margin', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'title_background',
			array(
				'label'     => __( 'Title Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__title' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'title_border',
				'selector' => '{{WRAPPER}} .ewcb-card__title',
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

		$this->add_responsive_control(
			'price_padding',
			array(
				'label'      => __( 'Price Padding', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__price' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'price_margin',
			array(
				'label'      => __( 'Price Margin', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'price_background',
			array(
				'label'     => __( 'Price Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__price' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'price_border',
				'selector' => '{{WRAPPER}} .ewcb-card__price',
			)
		);

		$this->add_control(
			'heading_category',
			array(
				'label'     => __( 'Category', 'ewcb' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'category_typography',
				'selector' => '{{WRAPPER}} .ewcb-card__cat',
			)
		);

		$this->add_control(
			'category_color',
			array(
				'label'     => __( 'Category Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#999999',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__cat' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'category_padding',
			array(
				'label'      => __( 'Category Padding', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__cat' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'category_margin',
			array(
				'label'      => __( 'Category Margin', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__cat' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'category_background',
			array(
				'label'     => __( 'Category Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__cat' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'category_border',
				'selector' => '{{WRAPPER}} .ewcb-card__cat',
			)
		);

		$this->add_control(
			'heading_add_to_cart',
			array(
				'label'     => __( 'Add to Cart Button', 'ewcb' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .ewcb-add-to-cart',
			)
		);

		$this->add_control(
			'button_background',
			array(
				'label'     => __( 'Button Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ff1e2b',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-add-to-cart' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Button Text Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-add-to-cart' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_background_hover',
			array(
				'label'     => __( 'Button Background (Hover)', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e00f1b',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-add-to-cart:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_text_color_hover',
			array(
				'label'     => __( 'Button Text Color (Hover)', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-add-to-cart:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .ewcb-add-to-cart',
			)
		);

		$this->add_control(
			'button_radius',
			array(
				'label'      => __( 'Button Corner Radius', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-add-to-cart' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Button Padding', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-add-to-cart' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_margin',
			array(
				'label'      => __( 'Button Margin', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-add-to-cart' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_icon_size',
			array(
				'label'      => __( 'Button Icon Size', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 12, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 18 ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-add-to-cart .ewcb-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_alignment',
			array(
				'label'   => __( 'Button Alignment', 'ewcb' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'center',
				'options' => array(
					'left'   => array( 'title' => __( 'Left', 'ewcb' ), 'icon' => 'eicon-h-align-left' ),
					'center' => array( 'title' => __( 'Center', 'ewcb' ), 'icon' => 'eicon-h-align-center' ),
					'right'  => array( 'title' => __( 'Right', 'ewcb' ), 'icon' => 'eicon-h-align-right' ),
				),
				'selectors_dictionary' => array(
					'left'   => 'flex-start',
					'center' => 'center',
					'right'  => 'flex-end',
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__cart' => 'justify-content: {{VALUE}};',
					'{{WRAPPER}} .ewcb-add-to-cart' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Additional description and badge styling for the reusable product card.
	 *
	 * @return void
	 */
	private function register_style_card_detail_controls() {
		$this->start_controls_section(
			'section_style_card_details',
			array(
				'label' => __( 'Card Description & Badge', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'footer_border_color',
			array(
				'label'     => __( 'Price / Button Divider Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e4e8f0',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__footer' => 'border-top-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'footer_border_width',
			array(
				'label'      => __( 'Divider Thickness', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 5 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 1 ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__footer' => 'border-top-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'footer_padding_top',
			array(
				'label'      => __( 'Footer Top Spacing', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__footer' => 'padding-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'footer_gap',
			array(
				'label'      => __( 'Price / Button Gap', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 32 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 12 ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__footer' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'label'    => __( 'Description Typography', 'ewcb' ),
				'selector' => '{{WRAPPER}} .ewcb-card__description',
			)
		);

		$this->add_control(
			'description_color',
			array(
				'label'     => __( 'Description Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#667085',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__description' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'description_margin',
			array(
				'label'      => __( 'Description Margin', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'description_line_height',
			array(
				'label'      => __( 'Description Line Height', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'em', 'px' ),
				'range'      => array(
					'em' => array( 'min' => 1, 'max' => 2.5, 'step' => 0.1 ),
					'px' => array( 'min' => 14, 'max' => 36 ),
				),
				'default'    => array( 'unit' => 'em', 'size' => 1.45 ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__description' => 'line-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_badge_background',
			array(
				'label'     => __( 'Custom Badge Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0062ff',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__badge--custom' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_badge_color',
			array(
				'label'     => __( 'Custom Badge Text Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__badge--custom' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'product_tag_badge_background',
			array(
				'label'     => __( 'Product Tag Badge Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0062ff',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__badge--tag' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'product_tag_badge_color',
			array(
				'label'     => __( 'Product Tag Badge Text Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-card__badge--tag' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'label'    => __( 'Badge Typography', 'ewcb' ),
				'selector' => '{{WRAPPER}} .ewcb-card__badge',
			)
		);

		$this->add_control(
			'badge_gap',
			array(
				'label'      => __( 'Spacing Between Badges', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 6 ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__badges' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'badge_padding',
			array(
				'label'      => __( 'Badge Padding', 'ewcb' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array( 'top' => 4, 'right' => 9, 'bottom' => 4, 'left' => 9, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'badge_radius',
			array(
				'label'      => __( 'Badge Corner Radius', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ), '%' => array( 'min' => 0, 'max' => 50 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 6 ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__badge' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'badge_inset',
			array(
				'label'      => __( 'Badge Distance from Edge', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array(
					'{{WRAPPER}} .ewcb-card__badges' => '--ewcb-badge-inset: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Styles for the Quick View, Wishlist and Compare overlays.
	 * Values are passed to the frontend overlay because these dialogs are
	 * rendered at document level rather than inside the Elementor wrapper.
	 *
	 * @return void
	 */
	private function register_style_modal_controls() {
		$this->start_controls_section(
			'section_style_quickview',
			array(
				'label' => __( 'Quick View Designer', 'ewcb' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_quick_view' => 'yes' ),
			)
		);
		$this->add_control( 'quickview_width', array( 'label' => __( 'Popup Width', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 480, 'max' => 1200 ) ), 'default' => array( 'unit' => 'px', 'size' => 860 ) ) );
		$this->add_control( 'quickview_background', array( 'label' => __( 'Popup Background', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff' ) );
		$this->add_control( 'quickview_overlay_color', array( 'label' => __( 'Overlay Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(13,17,26,0.68)' ) );
		$this->add_control( 'quickview_radius', array( 'label' => __( 'Popup Corner Radius', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'unit' => 'px', 'size' => 16 ) ) );
		$this->add_control( 'quickview_padding', array( 'label' => __( 'Popup Padding', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 10, 'max' => 60 ) ), 'default' => array( 'unit' => 'px', 'size' => 32 ) ) );
		$this->add_control( 'quickview_title_color', array( 'label' => __( 'Title Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#111827' ) );
		$this->add_control( 'quickview_title_size', array( 'label' => __( 'Title Font Size', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 16, 'max' => 40 ) ), 'default' => array( 'unit' => 'px', 'size' => 26 ) ) );
		$this->add_control( 'quickview_price_color', array( 'label' => __( 'Price Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#111827' ) );
		$this->add_control( 'quickview_price_size', array( 'label' => __( 'Price Font Size', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 14, 'max' => 32 ) ), 'default' => array( 'unit' => 'px', 'size' => 20 ) ) );
		$this->add_control( 'quickview_button_background', array( 'label' => __( 'Add to Cart Background', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#ff1e2b' ) );
		$this->add_control( 'quickview_button_color', array( 'label' => __( 'Add to Cart Text Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff' ) );
		$this->add_control( 'quickview_button_size', array( 'label' => __( 'Button Font Size', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 11, 'max' => 22 ) ), 'default' => array( 'unit' => 'px', 'size' => 14 ) ) );
		$this->add_control( 'quickview_button_radius', array( 'label' => __( 'Button Corner Radius', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ), 'default' => array( 'unit' => 'px', 'size' => 24 ) ) );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_wishlist',
			array(
				'label' => __( 'Wishlist Designer', 'ewcb' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_wishlist' => 'yes' ),
			)
		);
		$this->add_control( 'wishlist_width', array( 'label' => __( 'Popup Width', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 480, 'max' => 1200 ) ), 'default' => array( 'unit' => 'px', 'size' => 920 ) ) );
		$this->add_control( 'wishlist_background', array( 'label' => __( 'Popup Background', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff' ) );
		$this->add_control( 'wishlist_overlay_color', array( 'label' => __( 'Overlay Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(13,17,26,0.68)' ) );
		$this->add_control( 'wishlist_card_background', array( 'label' => __( 'Product Card Background', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff' ) );
		$this->add_control( 'wishlist_border_color', array( 'label' => __( 'Border Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#e4e8f0' ) );
		$this->add_control( 'wishlist_radius', array( 'label' => __( 'Product Card Radius', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 30 ) ), 'default' => array( 'unit' => 'px', 'size' => 12 ) ) );
		$this->add_control( 'wishlist_title_color', array( 'label' => __( 'Product Title Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#111827' ) );
		$this->add_control( 'wishlist_title_size', array( 'label' => __( 'Product Title Font Size', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 12, 'max' => 24 ) ), 'default' => array( 'unit' => 'px', 'size' => 15 ) ) );
		$this->add_control( 'wishlist_price_color', array( 'label' => __( 'Price Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#111827' ) );
		$this->add_control( 'wishlist_price_size', array( 'label' => __( 'Price Font Size', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 12, 'max' => 24 ) ), 'default' => array( 'unit' => 'px', 'size' => 15 ) ) );
		$this->add_control( 'wishlist_remove_color', array( 'label' => __( 'Remove Link Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#b42318' ) );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_compare',
			array(
				'label' => __( 'Compare Designer', 'ewcb' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_compare' => 'yes' ),
			)
		);
		$this->add_control( 'compare_width', array( 'label' => __( 'Popup Width', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 480, 'max' => 1400 ) ), 'default' => array( 'unit' => 'px', 'size' => 1100 ) ) );
		$this->add_control( 'compare_background', array( 'label' => __( 'Popup Background', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff' ) );
		$this->add_control( 'compare_overlay_color', array( 'label' => __( 'Overlay Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(13,17,26,0.68)' ) );
		$this->add_control( 'compare_header_background', array( 'label' => __( 'Table Header Background', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#f7f8fa' ) );
		$this->add_control( 'compare_border_color', array( 'label' => __( 'Table Border Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#e4e8f0' ) );
		$this->add_control( 'compare_text_color', array( 'label' => __( 'Text Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#344054' ) );
		$this->add_control( 'compare_font_size', array( 'label' => __( 'Table / Card Font Size', 'ewcb' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 11, 'max' => 18 ) ), 'default' => array( 'unit' => 'px', 'size' => 13 ) ) );
		$this->add_control( 'compare_accent_color', array( 'label' => __( 'Heading / Accent Color', 'ewcb' ), 'type' => Controls_Manager::COLOR, 'default' => '#0d111a' ) );
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

	/**
	 * Builds a term ID => category label map for product query controls.
	 *
	 * @return array<int,string>
	 */
	private function get_product_category_options() {
		$options = array();
		$terms   = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Whether this widget is dedicated to using the current product archive.
	 *
	 * @return bool
	 */
	protected function is_archive_catalog_widget() {
		return false;
	}

	/**
	 * Captures the active WooCommerce archive constraints for this widget.
	 *
	 * @return array
	 */
	protected function get_archive_query_context() {
		global $wp_query;

		if ( ! $wp_query instanceof \WP_Query ) {
			return array();
		}

		$settings = $this->get_settings_for_display();
		if ( ! $this->is_archive_catalog_widget() && 'custom' === $settings['query_source'] ) {
			return array();
		}

		$is_shop_archive = function_exists( 'is_shop' ) && is_shop();
		$is_product_taxonomy = function_exists( 'is_product_taxonomy' ) && is_product_taxonomy();
		$post_type = isset( $wp_query->query_vars['post_type'] ) ? $wp_query->query_vars['post_type'] : '';
		$is_product_search = function_exists( 'is_search' ) && is_search()
			&& ( 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) );

		if ( ! $is_shop_archive && ! $is_product_taxonomy && ! $is_product_search ) {
			if ( ! $this->is_archive_catalog_widget() && 'archive' !== $settings['query_source'] ) {
				return array();
			}

			$queried_object = get_queried_object();
			if ( $queried_object instanceof \WP_Term && taxonomy_exists( $queried_object->taxonomy ) ) {
				return array(
					'is_archive' => true,
					'tax_query' => array(
						array(
							'taxonomy' => $queried_object->taxonomy,
							'field'    => 'term_id',
							'terms'    => array( absint( $queried_object->term_id ) ),
						),
					),
				);
			}
			return array();
		}

		$context = array( 'is_archive' => true );
		if ( isset( $wp_query->tax_query->queries ) && is_array( $wp_query->tax_query->queries ) ) {
			$context['tax_query'] = $wp_query->tax_query->queries;
		}
		if ( isset( $wp_query->meta_query->queries ) && is_array( $wp_query->meta_query->queries ) ) {
			$context['meta_query'] = $wp_query->meta_query->queries;
		}
		if ( $is_product_search && ! empty( $wp_query->query_vars['s'] ) ) {
			$context['search'] = sanitize_text_field( $wp_query->query_vars['s'] );
		}

		return $context;
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
		$has_ajax_pagination = 'grid' === $settings['display_mode'] && 'yes' === $settings['show_pagination'];
		$has_quick_view = 'yes' === $settings['show_quick_view'];
		$has_collections = 'yes' === $settings['show_wishlist'] || 'yes' === $settings['show_compare'];
		if ( $filters_enabled || $has_ajax_pagination || $has_quick_view || $has_collections ) {
			\Elementor_Woo_Catalog_Builder\Plugin::instance()->enqueue_filter_assets();
		}

		$card_settings = array(
			'products_per_page' => $settings['products_per_page'],
			'orderby'           => $settings['orderby'],
			'order'             => $settings['order'],
			'query_source'      => isset( $settings['query_source'] ) ? $settings['query_source'] : 'auto',
			'query_mode'        => isset( $settings['query_mode'] ) ? $settings['query_mode'] : 'all',
			'query_categories'  => isset( $settings['query_categories'] ) ? $settings['query_categories'] : array(),
			'include_products'  => isset( $settings['include_products'] ) ? $settings['include_products'] : '',
			'exclude_products'  => isset( $settings['exclude_products'] ) ? $settings['exclude_products'] : '',
			'image_ratio'       => $settings['image_ratio'],
			'show_description'  => 'yes' === $settings['show_description'],
			'description_words' => isset( $settings['description_words'] ) ? absint( $settings['description_words'] ) : 20,
			'show_price'        => 'yes' === $settings['show_price'],
			'show_sale_badge'   => 'yes' === $settings['show_sale_badge'],
			'sale_badge_label'  => isset( $settings['sale_badge_label'] ) ? $settings['sale_badge_label'] : __( 'Sale', 'ewcb' ),
			'badge_position'    => isset( $settings['badge_position'] ) ? $settings['badge_position'] : 'top-left',
			'custom_badge_text' => isset( $settings['custom_badge_text'] ) ? $settings['custom_badge_text'] : '',
			'show_product_tag_badge' => 'yes' === $settings['show_product_tag_badge'],
			'card_button_label' => isset( $settings['card_button_label'] ) ? $settings['card_button_label'] : '',
			'show_category'     => 'yes' === $settings['show_category'],
			'show_quick_view'   => 'yes' === $settings['show_quick_view'],
			'show_wishlist'     => 'yes' === $settings['show_wishlist'],
			'show_compare'      => 'yes' === $settings['show_compare'],
			'hover_image_swap'  => 'yes' === $settings['hover_image_swap'],
			'image_hover_zoom'  => 'yes' === $settings['image_hover_zoom'],
			'action_visibility' => $settings['action_visibility'],
			'action_position'   => $settings['action_position'],
			'action_icon_layout' => $settings['action_icon_layout'],
			'show_cart_icon'    => 'yes' === $settings['show_cart_icon'],
			'cart_button_width' => $settings['cart_button_width'],
			'cart_vertical_alignment' => $settings['cart_vertical_alignment'],
			'button_alignment' => $settings['button_alignment'],
			'card_image_placement' => isset( $settings['card_image_placement'] ) ? $settings['card_image_placement'] : 'top',
			'card_image_placement_mobile' => isset( $settings['card_image_placement_mobile'] ) ? $settings['card_image_placement_mobile'] : 'top',
			'card_footer_layout' => isset( $settings['card_footer_layout'] ) ? $settings['card_footer_layout'] : 'inline',
			'price_button_order' => isset( $settings['price_button_order'] ) ? $settings['price_button_order'] : 'price-first',
			'quickview_layout' => isset( $settings['quickview_layout'] ) ? $settings['quickview_layout'] : 'image-left',
			'quickview_show_image' => ! isset( $settings['quickview_show_image'] ) || 'yes' === $settings['quickview_show_image'],
			'quickview_show_title' => ! isset( $settings['quickview_show_title'] ) || 'yes' === $settings['quickview_show_title'],
			'quickview_show_price' => ! isset( $settings['quickview_show_price'] ) || 'yes' === $settings['quickview_show_price'],
			'quickview_show_category' => isset( $settings['quickview_show_category'] ) ? 'yes' === $settings['quickview_show_category'] : true,
			'quickview_show_sku' => isset( $settings['quickview_show_sku'] ) && 'yes' === $settings['quickview_show_sku'],
			'quickview_show_stock' => ! isset( $settings['quickview_show_stock'] ) || 'yes' === $settings['quickview_show_stock'],
			'quickview_show_description' => ! isset( $settings['quickview_show_description'] ) || 'yes' === $settings['quickview_show_description'],
			'wishlist_layout' => isset( $settings['wishlist_layout'] ) ? $settings['wishlist_layout'] : 'grid',
			'wishlist_columns' => isset( $settings['wishlist_columns'] ) ? absint( $settings['wishlist_columns'] ) : 3,
			'wishlist_show_image' => ! isset( $settings['wishlist_show_image'] ) || 'yes' === $settings['wishlist_show_image'],
			'wishlist_show_price' => ! isset( $settings['wishlist_show_price'] ) || 'yes' === $settings['wishlist_show_price'],
			'wishlist_show_stock' => ! isset( $settings['wishlist_show_stock'] ) || 'yes' === $settings['wishlist_show_stock'],
			'wishlist_title_text' => isset( $settings['wishlist_title_text'] ) ? sanitize_text_field( $settings['wishlist_title_text'] ) : __( 'Your Wishlist', 'ewcb' ),
			'compare_layout' => isset( $settings['compare_layout'] ) ? $settings['compare_layout'] : 'table',
			'compare_title_text' => isset( $settings['compare_title_text'] ) ? sanitize_text_field( $settings['compare_title_text'] ) : __( 'Compare Products', 'ewcb' ),
		);
		$slider_size = static function ( $key, $fallback ) use ( $settings ) {
			return isset( $settings[ $key ]['size'] ) ? absint( $settings[ $key ]['size'] ) : $fallback;
		};
		$css_color = static function ( $key, $fallback ) use ( $settings ) {
			$value = isset( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : $fallback;
			return preg_match( '/^(#[0-9a-fA-F]{3,8}|rgba?\([0-9.,%\s]+\))$/', $value ) ? $value : $fallback;
		};
		foreach ( array( 'quickview_width' => 860, 'quickview_radius' => 16, 'quickview_padding' => 32, 'quickview_button_radius' => 24, 'quickview_title_size' => 26, 'quickview_price_size' => 20, 'quickview_button_size' => 14, 'wishlist_width' => 920, 'wishlist_radius' => 12, 'wishlist_title_size' => 15, 'wishlist_price_size' => 15, 'compare_width' => 1100, 'compare_font_size' => 13 ) as $key => $fallback ) {
			$card_settings[ $key ] = $slider_size( $key, $fallback );
		}
		foreach ( array(
			'quickview_background' => '#ffffff', 'quickview_overlay_color' => 'rgba(13,17,26,0.68)', 'quickview_title_color' => '#111827', 'quickview_price_color' => '#111827', 'quickview_button_background' => '#ff1e2b', 'quickview_button_color' => '#ffffff',
			'wishlist_background' => '#ffffff', 'wishlist_overlay_color' => 'rgba(13,17,26,0.68)', 'wishlist_card_background' => '#ffffff', 'wishlist_border_color' => '#e4e8f0', 'wishlist_title_color' => '#111827', 'wishlist_price_color' => '#111827', 'wishlist_remove_color' => '#b42318',
			'compare_background' => '#ffffff', 'compare_overlay_color' => 'rgba(13,17,26,0.68)', 'compare_header_background' => '#f7f8fa', 'compare_border_color' => '#e4e8f0', 'compare_text_color' => '#344054', 'compare_accent_color' => '#0d111a',
		) as $key => $fallback ) {
			$card_settings[ $key ] = $css_color( $key, $fallback );
		}
		foreach ( array( 'compare_show_image', 'compare_show_price', 'compare_show_category', 'compare_show_stock', 'compare_show_sku', 'compare_show_description' ) as $key ) {
			$card_settings[ $key ] = ! isset( $settings[ $key ] ) || 'yes' === $settings[ $key ];
		}
		if ( $this->is_archive_catalog_widget() ) {
			$card_settings['query_source'] = 'archive';
			$card_settings['query_mode']   = 'all';
		}

		$ajax_filter     = \Elementor_Woo_Catalog_Builder\Ajax_Filter::instance();
		$archive_context = $this->get_archive_query_context();
		$query_settings  = $card_settings;
		$ajax_settings   = $card_settings;
		$page_param = 'ewcb_page_' . sanitize_key( $this->get_id() );
		$has_page_override = isset( $_GET[ $page_param ] ) && ! is_array( $_GET[ $page_param ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public catalog page navigation.
		$requested_page = $has_page_override ? absint( wp_unslash( $_GET[ $page_param ] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public catalog page navigation.
		$initial_page = max( 1, $requested_page );
		if ( $archive_context ) {
			global $wp_query;
			$query_settings['archive_context'] = $archive_context;
			$ajax_settings['archive_context']  = $ajax_filter->create_archive_context_token( $archive_context );
			if ( ! $has_page_override && ! empty( $wp_query->query_vars['paged'] ) ) {
				$initial_page = max( 1, absint( $wp_query->query_vars['paged'] ) );
			}
		}
		$query = $ajax_filter->build_query( array(), $query_settings, $initial_page );
		if ( $query->max_num_pages > 0 && $initial_page > $query->max_num_pages ) {
			$initial_page = (int) $query->max_num_pages;
			$query        = $ajax_filter->build_query( array(), $query_settings, $initial_page );
		}

		$wrapper_classes = array(
			'ewcb-layout',
			$filters_enabled ? 'ewcb-layout--with-filters' : 'ewcb-layout--full',
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">

			<?php if ( $filters_enabled ) : ?>
				<?php $this->render_sidebar_filters( $settings, $archive_context ); ?>
			<?php endif; ?>

			<div class="ewcb-catalog-main">
				<div
					class="ewcb-grid ewcb-grid--<?php echo esc_attr( $settings['display_mode'] ); ?>"
					data-widget-id="<?php echo esc_attr( $this->get_id() ); ?>"
					data-display-mode="<?php echo esc_attr( $settings['display_mode'] ); ?>"
					data-settings="<?php echo esc_attr( wp_json_encode( $ajax_settings ) ); ?>"
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
					<nav class="ewcb-pagination" data-max-pages="<?php echo esc_attr( $query->max_num_pages ); ?>" data-current-page="<?php echo esc_attr( $initial_page ); ?>" data-page-param="<?php echo esc_attr( $page_param ); ?>" aria-label="<?php esc_attr_e( 'Product pagination', 'ewcb' ); ?>">
						<a class="ewcb-pagination__prev<?php echo $initial_page <= 1 ? ' is-disabled' : ''; ?>" href="<?php echo esc_url( add_query_arg( $page_param, max( 1, $initial_page - 1 ) ) ); ?>" aria-label="<?php esc_attr_e( 'Previous products', 'ewcb' ); ?>" aria-disabled="<?php echo $initial_page <= 1 ? 'true' : 'false'; ?>"><?php Render_Helpers::icon( 'chevron-left' ); ?></a>
						<span class="ewcb-pagination__status">
							<?php
							printf(
								/* translators: 1: current page 2: total pages */
								esc_html__( 'Page %1$s of %2$s', 'ewcb' ),
								'<span class="ewcb-pagination__current">' . esc_html( $initial_page ) . '</span>',
								'<span class="ewcb-pagination__total">' . esc_html( $query->max_num_pages ) . '</span>'
							);
							?>
						</span>
						<a class="ewcb-pagination__next<?php echo $initial_page >= $query->max_num_pages ? ' is-disabled' : ''; ?>" href="<?php echo esc_url( $initial_page >= $query->max_num_pages ? add_query_arg( $page_param, $query->max_num_pages ) : add_query_arg( $page_param, $initial_page + 1 ) ); ?>" aria-label="<?php esc_attr_e( 'Next products', 'ewcb' ); ?>" aria-disabled="<?php echo $initial_page >= $query->max_num_pages ? 'true' : 'false'; ?>"><?php Render_Helpers::icon( 'chevron-right' ); ?></a>
					</nav>
				<?php endif; ?>
				<?php if ( 'yes' === $settings['show_wishlist'] || 'yes' === $settings['show_compare'] ) : ?>
					<div class="ewcb-collections" aria-label="<?php esc_attr_e( 'Saved products', 'ewcb' ); ?>">
						<?php if ( 'yes' === $settings['show_wishlist'] ) : ?>
							<button type="button" class="ewcb-collection-trigger ewcb-open-wishlist" data-type="wishlist"><?php esc_html_e( 'Wishlist', 'ewcb' ); ?> <span class="ewcb-collection-count" data-count-type="wishlist">0</span></button>
						<?php endif; ?>
						<?php if ( 'yes' === $settings['show_compare'] ) : ?>
							<button type="button" class="ewcb-collection-trigger ewcb-open-compare" data-type="compare"><?php esc_html_e( 'Compare', 'ewcb' ); ?> <span class="ewcb-collection-count" data-count-type="compare">0</span></button>
						<?php endif; ?>
					</div>
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
	private function render_sidebar_filters( $settings, $archive_context = array() ) {
		$bounds = array( 'min' => 0, 'max' => 0 );
		if ( 'yes' === $settings['filter_price'] ) {
			$ajax_filter = \Elementor_Woo_Catalog_Builder\Ajax_Filter::instance();
			$bounds      = $ajax_filter->get_price_bounds( array(), $archive_context );
		}
		$price_step = pow( 10, - wc_get_price_decimals() );
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
							<input type="range" class="ewcb-price-min" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>" step="<?php echo esc_attr( $price_step ); ?>" value="<?php echo esc_attr( $bounds['min'] ); ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'ewcb' ); ?>" />
							<input type="range" class="ewcb-price-max" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>" step="<?php echo esc_attr( $price_step ); ?>" value="<?php echo esc_attr( $bounds['max'] ); ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'ewcb' ); ?>" />
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

/**
 * Archive-aware catalog for Elementor WooCommerce archive templates.
 */
class Widget_Archive_Product_Catalog extends Widget_Product_Catalog {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'ewcb-archive-product-catalog';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'Archive Product Catalog', 'ewcb' );
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'woocommerce', 'archive', 'category', 'products', 'catalog', 'shop' );
	}

	/**
	 * Always use the current product archive query.
	 *
	 * @return bool
	 */
	protected function is_archive_catalog_widget() {
		return true;
	}
}
