<?php
/**
 * Shared markup helpers used by both the server-rendered grid and the
 * AJAX filter response, so output is always identical between the two.
 *
 * @package Elementor_Woo_Catalog_Builder
 */

namespace Elementor_Woo_Catalog_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Render_Helpers
 */
final class Render_Helpers {

	/**
	 * Renders a single product card. Expects the global $post / $product
	 * context to already be set up (i.e. called inside a WP_Query loop
	 * after the_post()).
	 *
	 * @param array $settings Sanitized widget settings controlling card features.
	 * @return void
	 */
	public static function render_product_card( $settings ) {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( ! $product || ! $product->is_visible() ) {
			return;
		}

		$layout      = ! empty( $settings['layout_preset'] ) ? $settings['layout_preset'] : 'minimal_modern';
		$ratio       = ! empty( $settings['image_ratio'] ) ? $settings['image_ratio'] : '1/1';
		$hover_swap  = ! empty( $settings['hover_image_swap'] );
		$gallery_ids = $product->get_gallery_image_ids();
		$second_img  = $hover_swap && ! empty( $gallery_ids ) ? wp_get_attachment_image( $gallery_ids[0], 'woocommerce_thumbnail', false, array( 'class' => 'ewcb-card__img ewcb-card__img--hover' ) ) : '';

		$card_classes = array( 'ewcb-card', 'ewcb-card--' . sanitize_html_class( $layout ) );
		if ( $hover_swap && $second_img ) {
			$card_classes[] = 'ewcb-card--has-hover-img';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
			<div class="ewcb-card__media" style="--ewcb-ratio:<?php echo esc_attr( $ratio ); ?>;">
				<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="ewcb-card__media-link" aria-label="<?php echo esc_attr( $product->get_name() ); ?>">
					<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'ewcb-card__img ewcb-card__img--primary' ) ) ); ?>
					<?php echo wp_kses_post( $second_img ); ?>
				</a>

				<?php if ( $product->is_on_sale() ) : ?>
					<span class="ewcb-card__badge ewcb-card__badge--sale"><?php esc_html_e( 'Sale', 'ewcb' ); ?></span>
				<?php endif; ?>

				<div class="ewcb-card__actions">
					<?php if ( ! empty( $settings['show_quick_view'] ) ) : ?>
						<button type="button" class="ewcb-icon-btn ewcb-quick-view" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" aria-label="<?php esc_attr_e( 'Quick view', 'ewcb' ); ?>">
							<?php self::icon( 'eye' ); ?>
						</button>
					<?php endif; ?>

					<?php if ( ! empty( $settings['show_wishlist'] ) ) : ?>
						<button type="button" class="ewcb-icon-btn ewcb-wishlist" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" aria-label="<?php esc_attr_e( 'Add to wishlist', 'ewcb' ); ?>" aria-pressed="false">
							<?php self::icon( 'heart' ); ?>
						</button>
					<?php endif; ?>

					<?php if ( ! empty( $settings['show_compare'] ) ) : ?>
						<button type="button" class="ewcb-icon-btn ewcb-compare" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" aria-label="<?php esc_attr_e( 'Add to compare', 'ewcb' ); ?>" aria-pressed="false">
							<?php self::icon( 'compare' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>

			<div class="ewcb-card__body">
				<?php
				$categories = wc_get_product_category_list( $product->get_id(), ', ' );
				if ( $categories ) :
					?>
					<div class="ewcb-card__cat"><?php echo wp_kses_post( $categories ); ?></div>
				<?php endif; ?>

				<h3 class="ewcb-card__title">
					<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
				</h3>

				<div class="ewcb-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>

				<div class="ewcb-card__cart">
					<?php
					echo wp_kses_post(
						apply_filters(
							'woocommerce_loop_add_to_cart_link',
							sprintf(
								'<a href="%s" data-quantity="1" class="ewcb-add-to-cart button %s" %s>%s</a>',
								esc_url( $product->add_to_cart_url() ),
								esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? 'button' : '' ),
								wc_implode_html_attributes(
									array(
										'data-product_id'  => $product->get_id(),
										'data-product_sku' => $product->get_sku(),
										'aria-label'       => $product->add_to_cart_description(),
										'rel'               => 'nofollow',
									)
								),
								esc_html( $product->add_to_cart_text() )
							),
							$product
						)
					);
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs a minimal inline SVG line icon. Kept dependency-free (no
	 * external icon font requests) while remaining crisp at any size.
	 *
	 * @param string $name One of: cart, wishlist, heart, compare, filter, eye, close, chevron-left, chevron-right, star.
	 * @return void
	 */
	public static function icon( $name ) {
		$icons = array(
			'heart'         => '<path d="M12 21s-7.5-4.6-10-9.1C.4 8.7 1.9 5 5.4 5c2 0 3.3 1 4.6 2.6C11.3 6 12.6 5 14.6 5 18.1 5 19.6 8.7 22 11.9 19.5 16.4 12 21 12 21z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
			'compare'       => '<path d="M7 3v14M7 17l-3-3M7 17l3-3M17 21V7M17 7l-3 3M17 7l3 3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
			'eye'           => '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.5"/>',
			'cart'          => '<path d="M3 4h2l2.4 12.2a2 2 0 002 1.8h8.2a2 2 0 002-1.7L21 8H6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="21" r="1.4" fill="currentColor"/><circle cx="18" cy="21" r="1.4" fill="currentColor"/>',
			'filter'        => '<path d="M4 6h16M7 12h10M10 18h4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
			'close'         => '<path d="M5 5l14 14M19 5L5 19" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
			'chevron-left'  => '<path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
			'chevron-right' => '<path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
			'star'          => '<path d="M12 2l2.9 6.6 7.1.6-5.4 4.7 1.7 7-6.3-3.9L5.7 21l1.7-7-5.4-4.7 7.1-.6L12 2z" fill="currentColor"/>',
		);

		if ( ! isset( $icons[ $name ] ) ) {
			return;
		}

		printf(
			'<svg class="ewcb-icon ewcb-icon--%1$s" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">%2$s</svg>',
			esc_attr( $name ),
			$icons[ $name ] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, trusted inline SVG paths.
		);
	}
}
