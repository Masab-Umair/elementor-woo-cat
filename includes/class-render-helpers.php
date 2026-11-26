<?php
/**
 * Shared markup helpers used by the initial catalog render and AJAX responses.
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
	 * Render a single WooCommerce product card.
	 * Always resolves the product from the current queried post. This avoids
	 * accidentally reusing a stale global $product when custom WP_Query loops
	 * or AJAX pagination are rendered after another WooCommerce loop.
	 *
	 * @param array $settings Card settings from Elementor or the AJAX whitelist.
	 * @return void
	 */
	public static function render_product_card( $settings ) {
		$product_id = absint( get_the_ID() );
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product instanceof \WC_Product || ! $product->is_visible() ) {
			return;
		}

		// Keep WooCommerce template callbacks consistent with the product being rendered.
		$GLOBALS['product'] = $product;

		$defaults = array(
			'image_ratio'              => '16/10',
			'show_category'            => true,
			'show_description'         => true,
			'description_words'        => 20,
			'show_price'               => true,
			'show_sale_badge'          => true,
			'sale_badge_label'         => __( 'Sale', 'ewcb' ),
			'badge_position'           => 'top-left',
			'custom_badge_text'        => '',
			'show_product_tag_badge'  => false,
			'hover_image_swap'         => false,
			'image_hover_zoom'         => true,
			'show_quick_view'          => false,
			'show_wishlist'            => false,
			'show_compare'             => false,
			'action_visibility'        => 'always',
			'action_position'          => 'top-right',
			'action_icon_layout'       => 'vertical',
			'show_cart_icon'           => false,
			'cart_button_width'        => 'full',
			'cart_vertical_alignment'  => 'bottom',
			'button_alignment'         => 'center',
			'card_button_label'        => '',
		);
		$settings = wp_parse_args( $settings, $defaults );

		$allowed_ratios = array( '1/1', '4/5', '3/4', '16/10', '16/9', '4/3', 'auto' );
		$ratio          = in_array( $settings['image_ratio'], $allowed_ratios, true ) ? $settings['image_ratio'] : '16/10';
		$hover_swap     = ! empty( $settings['hover_image_swap'] );
		$gallery_ids    = $product->get_gallery_image_ids();
		$second_img     = $hover_swap && ! empty( $gallery_ids )
			? wp_get_attachment_image(
				$gallery_ids[0],
				'woocommerce_single',
				false,
				array(
					'class'    => 'ewcb-card__img ewcb-card__img--hover',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			)
			: '';

		$card_classes = array( 'ewcb-card' );
		if ( ! empty( $settings['image_hover_zoom'] ) ) {
			$card_classes[] = 'ewcb-card--hover-zoom';
		}
		if ( 'bottom' === $settings['cart_vertical_alignment'] ) {
			$card_classes[] = 'ewcb-card--cart-bottom';
		}
		if ( $hover_swap && $second_img ) {
			$card_classes[] = 'ewcb-card--has-hover-img';
		}

		$action_position = in_array( $settings['action_position'], array( 'top-right', 'top-left', 'bottom-right', 'bottom-left' ), true ) ? $settings['action_position'] : 'top-right';
		$action_layout   = 'horizontal' === $settings['action_icon_layout'] ? 'horizontal' : 'vertical';
		$button_align    = in_array( $settings['button_alignment'], array( 'left', 'center', 'right' ), true ) ? $settings['button_alignment'] : 'center';

		$short_description = $product->get_short_description();
		if ( empty( $short_description ) ) {
			$short_description = $product->get_description();
		}
		$description = wp_trim_words(
			wp_strip_all_tags( strip_shortcodes( $short_description ) ),
			max( 5, min( 60, absint( $settings['description_words'] ) ) ),
			'…'
		);
		$custom_badge = sanitize_text_field( $settings['custom_badge_text'] );
		$sale_badge_label = sanitize_text_field( $settings['sale_badge_label'] );
		$badge_position = in_array( $settings['badge_position'], array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' ), true ) ? $settings['badge_position'] : 'top-left';
		$product_tag_badge = '';
		if ( ! empty( $settings['show_product_tag_badge'] ) ) {
			$product_tag_ids = $product->get_tag_ids();
			if ( ! empty( $product_tag_ids[0] ) ) {
				$product_tag = get_term( absint( $product_tag_ids[0] ), 'product_tag' );
				if ( $product_tag && ! is_wp_error( $product_tag ) ) {
					$product_tag_badge = $product_tag->name;
				}
			}
		}
		?>
		<article class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>">
			<div class="ewcb-card__media" style="--ewcb-ratio:<?php echo esc_attr( $ratio ); ?>;">
				<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="ewcb-card__media-link" aria-label="<?php echo esc_attr( $product->get_name() ); ?>">
					<?php
					echo wp_kses_post(
						$product->get_image(
							'woocommerce_single',
							array(
								'class'    => 'ewcb-card__img ewcb-card__img--primary',
								'loading'  => 'lazy',
								'decoding' => 'async',
							)
						)
					);
					echo wp_kses_post( $second_img );
					?>
				</a>

				<?php if ( ( ! empty( $settings['show_sale_badge'] ) && $product->is_on_sale() && '' !== $sale_badge_label ) || '' !== $custom_badge || '' !== $product_tag_badge ) : ?>
					<div class="ewcb-card__badges ewcb-card__badges--<?php echo esc_attr( $badge_position ); ?>">
						<?php if ( ! empty( $settings['show_sale_badge'] ) && $product->is_on_sale() && '' !== $sale_badge_label ) : ?>
							<span class="ewcb-card__badge ewcb-card__badge--sale"><?php echo esc_html( $sale_badge_label ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $custom_badge ) : ?>
							<span class="ewcb-card__badge ewcb-card__badge--custom"><?php echo esc_html( $custom_badge ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $product_tag_badge ) : ?>
							<span class="ewcb-card__badge ewcb-card__badge--tag"><?php echo esc_html( $product_tag_badge ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $settings['show_quick_view'] ) || ! empty( $settings['show_wishlist'] ) || ! empty( $settings['show_compare'] ) ) : ?>
					<div class="ewcb-card__actions<?php echo 'hover' === $settings['action_visibility'] ? ' ewcb-card__actions--hover' : ''; ?> ewcb-card__actions--<?php echo esc_attr( $action_position ); ?> ewcb-card__actions--<?php echo esc_attr( $action_layout ); ?>">
						<?php if ( ! empty( $settings['show_quick_view'] ) ) : ?>
							<button type="button" class="ewcb-icon-btn ewcb-quick-view" data-product-id="<?php echo esc_attr( $product_id ); ?>" aria-label="<?php esc_attr_e( 'Quick view', 'ewcb' ); ?>">
								<?php self::icon( 'eye' ); ?>
							</button>
						<?php endif; ?>
						<?php if ( ! empty( $settings['show_wishlist'] ) ) : ?>
							<button type="button" class="ewcb-icon-btn ewcb-wishlist" data-product-id="<?php echo esc_attr( $product_id ); ?>" aria-label="<?php esc_attr_e( 'Add to wishlist', 'ewcb' ); ?>" aria-pressed="false">
								<?php self::icon( 'heart' ); ?>
							</button>
						<?php endif; ?>
						<?php if ( ! empty( $settings['show_compare'] ) ) : ?>
							<button type="button" class="ewcb-icon-btn ewcb-compare" data-product-id="<?php echo esc_attr( $product_id ); ?>" aria-label="<?php esc_attr_e( 'Add to compare', 'ewcb' ); ?>" aria-pressed="false">
								<?php self::icon( 'compare' ); ?>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="ewcb-card__body">
				<?php
				$categories = wc_get_product_category_list( $product_id, ', ' );
				if ( $categories && ! empty( $settings['show_category'] ) ) :
					?>
					<div class="ewcb-card__cat"><?php echo wp_kses_post( $categories ); ?></div>
				<?php endif; ?>

				<h3 class="ewcb-card__title">
					<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
				</h3>

				<?php if ( ! empty( $settings['show_description'] ) && '' !== $description ) : ?>
					<div class="ewcb-card__description"><?php echo esc_html( $description ); ?></div>
				<?php endif; ?>

				<div class="ewcb-card__footer ewcb-card__footer--<?php echo 'inline' === $settings['cart_button_width'] ? 'inline' : 'stacked'; ?>">
					<?php if ( ! empty( $settings['show_price'] ) ) : ?>
						<div class="ewcb-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
					<?php endif; ?>

					<div class="ewcb-card__cart ewcb-card__cart--align-<?php echo esc_attr( $button_align ); ?><?php echo 'inline' === $settings['cart_button_width'] ? ' ewcb-card__cart--inline' : ''; ?>">

					<?php
					$cart_icon = '';
					if ( ! empty( $settings['show_cart_icon'] ) ) {
						ob_start();
						self::icon( 'cart' );
						$cart_icon = ob_get_clean();
					}

					$allowed_html             = wp_kses_allowed_html( 'post' );
					$allowed_html['svg']      = array( 'class' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'aria-hidden' => true, 'focusable' => true );
					$allowed_html['path']     = array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true );
					$allowed_html['circle']   = array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true );
					$is_variable_configurator = $product->is_type( 'variable' ) && ! empty( $settings['show_quick_view'] ) && $product->is_purchasable() && $product->is_in_stock();
					$custom_label_allowed = $product->is_type( array( 'simple', 'variation' ) ) || $is_variable_configurator;
					$button_text = '' !== trim( $settings['card_button_label'] ) && $custom_label_allowed && $product->is_purchasable() && $product->is_in_stock()
						? sanitize_text_field( $settings['card_button_label'] )
						: $product->add_to_cart_text();
					if ( $is_variable_configurator ) :
						?>
						<button type="button" class="ewcb-add-to-cart ewcb-quick-view" data-product-id="<?php echo esc_attr( $product_id ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Choose options for %s', 'ewcb' ), $product->get_name() ) ); ?>">
							<?php if ( $cart_icon ) { echo wp_kses( $cart_icon, $allowed_html ); } ?>
							<span class="ewcb-add-to-cart__text"><?php echo esc_html( $button_text ); ?></span>
						</button>
					<?php else :
						$classes = array( 'ewcb-add-to-cart', 'button', 'product_type_' . $product->get_type() );
						if ( $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' ) ) {
							$classes[] = 'add_to_cart_button';
							$classes[] = 'ajax_add_to_cart';
						}
						$button_url = $product->is_purchasable() && $product->is_in_stock() ? $product->add_to_cart_url() : get_permalink( $product_id );
						// Configurable products keep WooCommerce's action wording unless the CTA opens the in-card configurator.
						if ( $product->is_type( array( 'variable', 'grouped', 'external' ) ) ) {
							$button_text = $product->add_to_cart_text();
						}
						$attributes = array(
							'href'             => esc_url( $button_url ),
							'data-quantity'    => '1',
							'data-product_id'  => (string) $product_id,
							'data-product_sku' => $product->get_sku(),
							'aria-label'       => $product->add_to_cart_description(),
							'rel'              => 'nofollow',
							'class'            => implode( ' ', $classes ),
						);
						$attribute_html = wc_implode_html_attributes( $attributes );
						$button_html    = sprintf(
							'<a %1$s>%2$s<span class="ewcb-add-to-cart__text">%3$s</span></a>',
							$attribute_html,
							$cart_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG from this class.
							esc_html( $button_text )
						);

						/**
						 * Allow WooCommerce extensions to adjust the rendered loop button.
						 *
						 * @param string      $button_html Button markup.
						 * @param \WC_Product $product    Current product.
						 */
						$button_html = apply_filters( 'woocommerce_loop_add_to_cart_link', $button_html, $product );

						echo wp_kses( $button_html, $allowed_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized by wp_kses.
					endif;

					?>
					</div>
				</div>
			</div>
		</article>
		<?php
	}

	/**
	 * Outputs a minimal inline SVG line icon.
	 *
	 * @param string $name Icon key.
	 * @return void
	 */
	public static function icon( $name ) {
		$icons = array(
			'heart'         => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
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
