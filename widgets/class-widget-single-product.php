<?php
/**
 * Single Product widget: ultra-clean product showcase with multi-image
 * gallery, alignment options and a sticky add-to-cart bar.
 *
 * @package Elementor_Woo_Catalog_Builder
 */

namespace Elementor_Woo_Catalog_Builder\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Widget_Single_Product
 */
class Widget_Single_Product extends Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'ewcb-single-product';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'Single Product Showcase', 'ewcb' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-single-product';
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
		return array( 'woocommerce', 'product', 'single', 'gallery', 'add to cart' );
	}

	/**
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( 'ewcb-catalog-frontend' );
	}

	/**
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( 'ewcb-catalog-layouts' );
	}

	/**
	 * Registers all Content + Style controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_content_source_controls();
		$this->register_content_gallery_controls();
		$this->register_content_cta_controls();
		$this->register_style_layout_controls();
		$this->register_style_gallery_controls();
		$this->register_style_typography_controls();
		$this->register_style_cta_controls();
	}

	/* ------------------------------------------------------------------ *
	 *  CONTENT TAB
	 * ------------------------------------------------------------------ */

	/**
	 * Product source (current product, or a manually chosen one for
	 * template previewing / use outside the single-product template).
	 *
	 * @return void
	 */
	private function register_content_source_controls() {
		$this->start_controls_section(
			'section_source',
			array(
				'label' => __( 'Product Source', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'product_source',
			array(
				'label'   => __( 'Product', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'current',
				'options' => array(
					'current' => __( 'Current Product (Dynamic)', 'ewcb' ),
					'manual'  => __( 'Choose Manually', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'manual_product_id',
			array(
				'label'       => __( 'Select Product', 'ewcb' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'options'     => $this->get_product_options(),
				'condition'   => array(
					'product_source' => 'manual',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Gallery layout and alignment settings.
	 *
	 * @return void
	 */
	private function register_content_gallery_controls() {
		$this->start_controls_section(
			'section_gallery_content',
			array(
				'label' => __( 'Gallery & Layout', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'layout_alignment',
			array(
				'label'   => __( 'Layout Alignment', 'ewcb' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => array(
					'image-left'  => array(
						'title' => __( 'Image Left', 'ewcb' ),
						'icon'  => 'eicon-h-align-left',
					),
					'image-right' => array(
						'title' => __( 'Image Right', 'ewcb' ),
						'icon'  => 'eicon-h-align-right',
					),
					'stacked'     => array(
						'title' => __( 'Stacked', 'ewcb' ),
						'icon'  => 'eicon-v-align-top',
					),
				),
				'default' => 'image-left',
				'toggle'  => false,
			)
		);

		$this->add_control(
			'gallery_style',
			array(
				'label'   => __( 'Gallery Style', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'thumbnails_left',
				'options' => array(
					'thumbnails_left'   => __( 'Vertical Thumbnails (Left)', 'ewcb' ),
					'thumbnails_bottom' => __( 'Horizontal Thumbnails (Bottom)', 'ewcb' ),
					'dots'              => __( 'Swipe with Dots', 'ewcb' ),
					'single'            => __( 'Main Image Only', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'image_ratio',
			array(
				'label'   => __( 'Main Image Ratio', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1/1',
				'options' => array(
					'1/1'  => __( 'Square (1:1)', 'ewcb' ),
					'4/5'  => __( 'Portrait (4:5)', 'ewcb' ),
					'3/4'  => __( 'Portrait (3:4)', 'ewcb' ),
					'4/3'  => __( 'Landscape (4:3)', 'ewcb' ),
					'auto' => __( 'Original', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'enable_zoom',
			array(
				'label'        => __( 'Enable Image Zoom', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_fields',
			array(
				'label' => __( 'Visible Elements', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		foreach ( $this->get_toggleable_fields() as $key => $label ) {
			$this->add_control(
				'show_' . $key,
				array(
					'label'        => $label,
					'type'         => Controls_Manager::SWITCHER,
					'default'      => 'yes',
					'return_value' => 'yes',
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Add-to-cart / sticky CTA settings.
	 *
	 * @return void
	 */
	private function register_content_cta_controls() {
		$this->start_controls_section(
			'section_cta_content',
			array(
				'label' => __( 'Add to Cart', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'cta_style',
			array(
				'label'   => __( 'Button Style', 'ewcb' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'minimal',
				'options' => array(
					'minimal' => __( 'Minimal', 'ewcb' ),
					'solid'   => __( 'Solid Fill', 'ewcb' ),
					'outline' => __( 'Outline', 'ewcb' ),
				),
			)
		);

		$this->add_control(
			'enable_sticky_cta',
			array(
				'label'        => __( 'Sticky Add-to-Cart Bar', 'ewcb' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'description'  => __( 'Shows a condensed add-to-cart bar fixed to the bottom of the screen once the main button scrolls out of view.', 'ewcb' ),
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'sticky_cta_position',
			array(
				'label'     => __( 'Sticky Bar Position', 'ewcb' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'bottom',
				'options'   => array(
					'bottom' => __( 'Bottom of Screen', 'ewcb' ),
					'top'    => __( 'Top of Screen', 'ewcb' ),
				),
				'condition' => array(
					'enable_sticky_cta' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ *
	 *  STYLE TAB
	 * ------------------------------------------------------------------ */

	/**
	 * Overall layout spacing / background.
	 *
	 * @return void
	 */
	private function register_style_layout_controls() {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => __( 'Layout', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'layout_background',
			array(
				'label'     => __( 'Background Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'      => __( 'Gap Between Image & Info', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 120,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => 60,
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single' => '--ewcb-single-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Gallery image border/radius/shadow.
	 *
	 * @return void
	 */
	private function register_style_gallery_controls() {
		$this->start_controls_section(
			'section_style_gallery',
			array(
				'label' => __( 'Gallery', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'image_radius',
			array(
				'label'      => __( 'Image Corner Radius', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single__main-image, {{WRAPPER}} .ewcb-single__thumb' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'thumb_border',
				'label'    => __( 'Thumbnail Border', 'ewcb' ),
				'selector' => '{{WRAPPER}} .ewcb-single__thumb',
			)
		);

		$this->add_control(
			'thumb_active_border_color',
			array(
				'label'     => __( 'Active Thumbnail Border Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single__thumb.is-active' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Title, price, description typography and colors.
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
			'heading_title_style',
			array(
				'label' => __( 'Title', 'ewcb' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ewcb-single__title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Title Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'heading_price_style',
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
				'selector' => '{{WRAPPER}} .ewcb-single__price',
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => __( 'Price Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single__price' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'heading_desc_style',
			array(
				'label'     => __( 'Short Description', 'ewcb' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .ewcb-single__description',
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => __( 'Description Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#5f5f5f',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single__description' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Add-to-cart button + sticky bar styling.
	 *
	 * @return void
	 */
	private function register_style_cta_controls() {
		$this->start_controls_section(
			'section_style_cta',
			array(
				'label' => __( 'Add to Cart Button', 'ewcb' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->start_controls_tabs( 'cta_tabs' );

		$this->start_controls_tab(
			'cta_tab_normal',
			array( 'label' => __( 'Normal', 'ewcb' ) )
		);

		$this->add_control(
			'cta_bg_color',
			array(
				'label'     => __( 'Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single__add-to-cart, {{WRAPPER}} .ewcb-sticky-cta__button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'cta_text_color',
			array(
				'label'     => __( 'Text Color', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single__add-to-cart, {{WRAPPER}} .ewcb-sticky-cta__button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'cta_tab_hover',
			array( 'label' => __( 'Hover', 'ewcb' ) )
		);

		$this->add_control(
			'cta_bg_color_hover',
			array(
				'label'     => __( 'Background', 'ewcb' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3a3a3a',
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single__add-to-cart:hover, {{WRAPPER}} .ewcb-sticky-cta__button:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'cta_radius',
			array(
				'label'      => __( 'Corner Radius', 'ewcb' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .ewcb-single__add-to-cart' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Fields that can be toggled on/off in the showcase.
	 *
	 * @return array<string,string>
	 */
	private function get_toggleable_fields() {
		return array(
			'title'            => __( 'Title', 'ewcb' ),
			'price'            => __( 'Price', 'ewcb' ),
			'rating'           => __( 'Rating', 'ewcb' ),
			'short_description' => __( 'Short Description', 'ewcb' ),
			'sku'              => __( 'SKU', 'ewcb' ),
			'stock'            => __( 'Stock Status', 'ewcb' ),
			'quantity'         => __( 'Quantity Selector', 'ewcb' ),
			'categories'       => __( 'Categories', 'ewcb' ),
		);
	}

	/**
	 * Product dropdown options for the manual-selection mode.
	 *
	 * @return array<int,string>
	 */
	private function get_product_options() {
		$options = array();

		if ( ! function_exists( 'wc_get_products' ) ) {
			return $options;
		}

		$products = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => 100,
				'return' => 'ids',
			)
		);

		foreach ( $products as $product_id ) {
			$options[ $product_id ] = get_the_title( $product_id );
		}

		return $options;
	}

	/* ------------------------------------------------------------------ *
	 *  RENDER
	 * ------------------------------------------------------------------ */

	/**
	 * Outputs the single-product showcase markup.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<p>' . esc_html__( 'WooCommerce is required for this widget.', 'ewcb' ) . '</p>';
			return;
		}

		$settings = $this->get_settings_for_display();

		\Elementor_Woo_Catalog_Builder\Plugin::instance()->enqueue_catalog_assets();

		$product_id = 'manual' === $settings['product_source'] && ! empty( $settings['manual_product_id'] )
			? absint( $settings['manual_product_id'] )
			: get_the_ID();

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			echo '<p>' . esc_html__( 'Select a product to preview this widget, or place it on a single product template.', 'ewcb' ) . '</p>';
			return;
		}

		global $post;
		$post_backup = $post;
		$post        = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		$image_id    = $product->get_image_id();
		$gallery_ids = $product->get_gallery_image_ids();
		$all_images  = array_merge( $image_id ? array( $image_id ) : array(), $gallery_ids );
		$image_ratio = $settings['image_ratio'];

		$wrapper_classes = array(
			'ewcb-single',
			'ewcb-single--' . sanitize_html_class( $settings['layout_alignment'] ),
			'ewcb-single--gallery-' . sanitize_html_class( $settings['gallery_style'] ),
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">

			<div class="ewcb-single__gallery">
				<?php if ( 'thumbnails_left' === $settings['gallery_style'] && count( $all_images ) > 1 ) : ?>
					<div class="ewcb-single__thumbs ewcb-single__thumbs--vertical">
						<?php foreach ( $all_images as $index => $img_id ) : ?>
							<button type="button" class="ewcb-single__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-full="<?php echo esc_url( wp_get_attachment_image_url( $img_id, 'woocommerce_single' ) ); ?>">
								<?php echo wp_get_attachment_image( $img_id, 'woocommerce_thumbnail' ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="ewcb-single__main-wrap">
					<div class="ewcb-single__main-image<?php echo esc_attr( 'yes' === $settings['enable_zoom'] ? ' is-zoomable' : '' ); ?>" style="--ewcb-ratio:<?php echo esc_attr( $image_ratio ); ?>;">
						<?php
						if ( $image_id ) {
							echo wp_get_attachment_image( $image_id, 'woocommerce_single', false, array( 'class' => 'ewcb-single__img', 'data-main-image' => '1' ) );
						} else {
							echo wc_placeholder_img( 'woocommerce_single' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>

					<?php if ( 'dots' === $settings['gallery_style'] && count( $all_images ) > 1 ) : ?>
						<div class="ewcb-single__dots">
							<?php foreach ( $all_images as $index => $img_id ) : ?>
								<button type="button" class="ewcb-single__dot<?php echo 0 === $index ? ' is-active' : ''; ?>" data-full="<?php echo esc_url( wp_get_attachment_image_url( $img_id, 'woocommerce_single' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number */ __( 'View image %d', 'ewcb' ), $index + 1 ) ); ?>"></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( 'thumbnails_bottom' === $settings['gallery_style'] && count( $all_images ) > 1 ) : ?>
					<div class="ewcb-single__thumbs ewcb-single__thumbs--horizontal">
						<?php foreach ( $all_images as $index => $img_id ) : ?>
							<button type="button" class="ewcb-single__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-full="<?php echo esc_url( wp_get_attachment_image_url( $img_id, 'woocommerce_single' ) ); ?>">
								<?php echo wp_get_attachment_image( $img_id, 'woocommerce_thumbnail' ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="ewcb-single__info">
				<?php if ( 'yes' === $settings['show_categories'] ) : ?>
					<div class="ewcb-single__categories"><?php echo wp_kses_post( wc_get_product_category_list( $product->get_id(), ', ' ) ); ?></div>
				<?php endif; ?>

				<?php if ( 'yes' === $settings['show_title'] ) : ?>
					<h1 class="ewcb-single__title"><?php echo esc_html( $product->get_name() ); ?></h1>
				<?php endif; ?>

				<?php if ( 'yes' === $settings['show_rating'] && wc_review_ratings_enabled() ) : ?>
					<div class="ewcb-single__rating"><?php echo wp_kses_post( wc_get_rating_html( $product->get_average_rating() ) ); ?></div>
				<?php endif; ?>

				<?php if ( 'yes' === $settings['show_price'] ) : ?>
					<div class="ewcb-single__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				<?php endif; ?>

				<?php if ( 'yes' === $settings['show_short_description'] ) : ?>
					<div class="ewcb-single__description"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
				<?php endif; ?>

				<div class="ewcb-single__meta">
					<?php if ( 'yes' === $settings['show_sku'] && $product->get_sku() ) : ?>
						<div class="ewcb-single__sku"><?php esc_html_e( 'SKU:', 'ewcb' ); ?> <?php echo esc_html( $product->get_sku() ); ?></div>
					<?php endif; ?>

					<?php if ( 'yes' === $settings['show_stock'] ) : ?>
						<div class="ewcb-single__stock ewcb-single__stock--<?php echo esc_attr( $product->get_stock_status() ); ?>">
							<?php echo wp_kses_post( wc_get_stock_html( $product ) ); ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="ewcb-single__purchase ewcb-single__purchase--<?php echo esc_attr( $settings['cta_style'] ); ?>">
					<?php
					if ( $product->is_purchasable() && $product->is_in_stock() ) {
						if ( 'yes' === $settings['show_quantity'] && $product->is_type( 'simple' ) ) {
							woocommerce_quantity_input(
								array(
									'min_value' => 1,
									'max_value' => $product->get_max_purchase_quantity() > 0 ? $product->get_max_purchase_quantity() : '',
									'input_value' => 1,
								)
							);
						}
						printf(
							'<a href="%1$s" data-quantity="1" class="ewcb-single__add-to-cart button" %2$s>%3$s</a>',
							esc_url( $product->add_to_cart_url() ),
							wc_implode_html_attributes(
								array(
									'data-product_id'  => $product->get_id(),
									'data-product_sku' => $product->get_sku(),
									'aria-label'        => $product->add_to_cart_description(),
									'rel'                => 'nofollow',
								)
							),
							esc_html( $product->single_add_to_cart_text() )
						);
					} else {
						echo '<div class="ewcb-single__unavailable">' . esc_html__( 'This product is currently unavailable.', 'ewcb' ) . '</div>';
					}
					?>
				</div>
			</div>
		</div>

		<?php if ( 'yes' === $settings['enable_sticky_cta'] && $product->is_purchasable() && $product->is_in_stock() ) : ?>
			<div class="ewcb-sticky-cta ewcb-sticky-cta--<?php echo esc_attr( $settings['sticky_cta_position'] ); ?>" data-trigger-selector=".ewcb-single__purchase">
				<?php if ( $image_id ) : ?>
					<div class="ewcb-sticky-cta__thumb"><?php echo wp_get_attachment_image( $image_id, 'woocommerce_gallery_thumbnail' ); ?></div>
				<?php endif; ?>
				<div class="ewcb-sticky-cta__info">
					<div class="ewcb-sticky-cta__title"><?php echo esc_html( $product->get_name() ); ?></div>
					<div class="ewcb-sticky-cta__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				</div>
				<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="ewcb-sticky-cta__button" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" rel="nofollow">
					<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
				</a>
			</div>
		<?php endif; ?>
		<?php

		$post = $post_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( $post ) {
			setup_postdata( $post );
		} else {
			wp_reset_postdata();
		}
	}
}
