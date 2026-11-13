<?php
/**
 * Handles the AJAX side-filter requests for the product catalog widget.
 *
 * @package Elementor_Woo_Catalog_Builder
 */

namespace Elementor_Woo_Catalog_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ajax_Filter
 *
 * Builds a WP_Query from the posted filter state (category, price range,
 * attributes, stock status) and returns freshly rendered grid HTML plus
 * pagination metadata. Query results are cached in short-lived transients
 * keyed by the normalized filter signature so repeated/identical filter
 * combinations are served instantly.
 */
final class Ajax_Filter {

	/**
	 * Singleton instance.
	 *
	 * @var Ajax_Filter|null
	 */
	private static $instance = null;

	/**
	 * Transient lifetime, in seconds (5 minutes).
	 *
	 * @var int
	 */
	const CACHE_TTL = 300;

	/**
	 * Get (and lazily create) the singleton instance.
	 *
	 * @return Ajax_Filter
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — registers AJAX hooks for logged-in and guest users.
	 */
	private function __construct() {
		add_action( 'wp_ajax_ewcb_filter_products', array( $this, 'handle_filter_request' ) );
		add_action( 'wp_ajax_nopriv_ewcb_filter_products', array( $this, 'handle_filter_request' ) );

		add_action( 'wp_ajax_ewcb_quick_view', array( $this, 'handle_quick_view_request' ) );
		add_action( 'wp_ajax_nopriv_ewcb_quick_view', array( $this, 'handle_quick_view_request' ) );

		// Invalidate cached filter results whenever stock/price data changes.
		add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'flush_cache' ) );
		add_action( 'woocommerce_update_product', array( __CLASS__, 'flush_cache' ) );
		add_action( 'save_post_product', array( __CLASS__, 'flush_cache' ) );
	}

	/**
	 * AJAX callback: validates the request, builds the product query from
	 * the submitted filter state, and streams back rendered grid markup.
	 *
	 * @return void
	 */
	public function handle_filter_request() {
		check_ajax_referer( 'ewcb_filter_nonce', 'nonce' );

		$settings = $this->get_request_data( 'settings' );
		if ( isset( $settings['archive_context'] ) && null === $this->decode_archive_context( $settings['archive_context'] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'The archive context is no longer valid. Refresh the page and try again.', 'ewcb' ) ),
				400
			);
		}
		$settings = $this->sanitize_widget_settings( $settings );

		$filters = $this->get_request_data( 'filters' );
		$filters = $this->sanitize_filters( $filters );

		$paged                = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
		$include_price_bounds = isset( $_POST['include_price_bounds'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['include_price_bounds'] ) );

		$cache_key = 'ewcb_' . md5( wp_json_encode( $filters ) . wp_json_encode( $settings ) . $paged . (int) $include_price_bounds );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			wp_send_json_success( $cached );
		}

		$query = $this->build_query( $filters, $settings, $paged );

		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				Render_Helpers::render_product_card( $settings );
			}
			wp_reset_postdata();
		} else {
			echo '<div class="ewcb-no-products">' . esc_html__( 'No products match the selected filters.', 'ewcb' ) . '</div>';
		}
		$grid_html = ob_get_clean();

		$response = array(
			'html'        => $grid_html,
			'found'       => (int) $query->found_posts,
			'max_pages'   => (int) $query->max_num_pages,
			'paged'       => $paged,
			'price_range' => $include_price_bounds
				? $this->get_price_bounds( $filters, $settings['archive_context'] )
				: null,
		);

		set_transient( $cache_key, $response, self::CACHE_TTL );

		wp_send_json_success( $response );
	}

	/**
	 * AJAX callback: returns a compact HTML fragment (image, title,
	 * price, short description, add-to-cart) for the Quick View modal.
	 *
	 * @return void
	 */
	public function handle_quick_view_request() {
		check_ajax_referer( 'ewcb_filter_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product || ! $product->is_visible() ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'ewcb' ) ) );
		}

		ob_start();
		?>
		<div class="ewcb-quickview">
			<div class="ewcb-quickview__media">
				<?php echo wp_kses_post( $product->get_image( 'woocommerce_single' ) ); ?>
			</div>
			<div class="ewcb-quickview__info">
				<h2 class="ewcb-single__title"><?php echo esc_html( $product->get_name() ); ?></h2>
				<div class="ewcb-single__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				<div class="ewcb-single__description"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
				<?php if ( $product->is_purchasable() && $product->is_in_stock() ) : ?>
					<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="ewcb-single__add-to-cart button" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" rel="nofollow">
						<?php echo esc_html( $product->add_to_cart_text() ); ?>
					</a>
				<?php else : ?>
					<div class="ewcb-single__unavailable"><?php esc_html_e( 'Currently unavailable.', 'ewcb' ); ?></div>
				<?php endif; ?>
				<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="ewcb-quickview__full-link"><?php esc_html_e( 'View full details', 'ewcb' ); ?></a>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Builds the WP_Query used for both the AJAX response and the
	 * initial server-rendered grid, so results are always consistent.
	 *
	 * @param array $filters  Sanitized filter state.
	 * @param array $settings Sanitized widget settings and optional archive context.
	 * @param int   $paged    Current page number.
	 * @return \WP_Query
	 */
	public function build_query( $filters, $settings, $paged = 1 ) {
		$requested_per_page = isset( $settings['products_per_page'] ) ? (int) $settings['products_per_page'] : 12;
		$per_page           = -1 === $requested_per_page ? -1 : min( 48, max( 1, $requested_per_page ) );

		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => max( 1, $paged ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		);
		if ( isset( $settings['fields'] ) && 'ids' === $settings['fields'] ) {
			$args['fields']        = 'ids';
			$args['no_found_rows'] = true;
		}

		$tax_query  = array( 'relation' => 'AND' );
		$meta_query = array( 'relation' => 'AND' );
		$archive_context = isset( $settings['archive_context'] ) && is_array( $settings['archive_context'] )
			? $settings['archive_context']
			: array();
		if ( isset( $settings['query_source'] ) && 'archive' === $settings['query_source'] && empty( $archive_context['is_archive'] ) ) {
			$args['post__in'] = array( 0 );
		}
		if ( ! empty( $archive_context['tax_query'] ) && is_array( $archive_context['tax_query'] ) ) {
			$tax_query[] = $archive_context['tax_query'];
		}
		if ( ! empty( $archive_context['meta_query'] ) && is_array( $archive_context['meta_query'] ) ) {
			$meta_query[] = $archive_context['meta_query'];
		}
		if ( ! empty( $archive_context['search'] ) ) {
			$args['s'] = $archive_context['search'];
		}

		$visibility = wc_get_product_visibility_term_ids();
		$query_source = isset( $settings['query_source'] ) ? $settings['query_source'] : 'auto';
		$query_mode   = 'archive' === $query_source ? 'all' : ( isset( $settings['query_mode'] ) ? $settings['query_mode'] : 'all' );
		$product_ids = static function ( $value ) {
			$values = is_array( $value ) ? $value : preg_split( '/[\s,]+/', (string) $value );
			return array_values( array_unique( array_filter( array_map( 'absint', $values ) ) ) );
		};
		$selected_ids = 'archive' !== $query_source && isset( $settings['include_products'] ) ? $product_ids( $settings['include_products'] ) : array();
		$excluded_ids = 'archive' !== $query_source && isset( $settings['exclude_products'] ) ? $product_ids( $settings['exclude_products'] ) : array();

		if ( $selected_ids ) {
			$args['post__in'] = array_values( array_unique( $selected_ids ) );
		}
		if ( $excluded_ids ) {
			$args['post__not_in'] = array_values( array_unique( $excluded_ids ) );
		}

		if ( 'category' === $query_mode ) {
			$category_ids = isset( $settings['query_categories'] ) ? array_filter( array_map( 'absint', (array) $settings['query_categories'] ) ) : array();
			$tax_query[] = $category_ids
				? array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => array_values( array_unique( $category_ids ) ),
					'include_children' => true,
				)
				: array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => array( 0 ),
				);
		} elseif ( 'featured' === $query_mode ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array( ! empty( $visibility['featured'] ) ? $visibility['featured'] : 0 ),
			);
		} elseif ( 'on_sale' === $query_mode ) {
			$sale_ids = array_map( 'absint', wc_get_product_ids_on_sale() );
			$matching_sale_ids = $selected_ids ? array_values( array_intersect( $selected_ids, $sale_ids ) ) : $sale_ids;
			$args['post__in'] = $matching_sale_ids ? $matching_sale_ids : array( 0 );
		}

		$excluded_visibility_terms = array();
		if ( ! empty( $visibility['exclude-from-catalog'] ) ) {
			$excluded_visibility_terms[] = $visibility['exclude-from-catalog'];
		}
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $visibility['outofstock'] ) ) {
			$excluded_visibility_terms[] = $visibility['outofstock'];
		}
		if ( $excluded_visibility_terms ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $excluded_visibility_terms,
				'operator' => 'NOT IN',
			);
		}

		// Category filter.
		if ( ! empty( $filters['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => (array) $filters['category'],
			);
		}

		// Attribute filters (color, size, or any registered pa_* taxonomy).
		if ( ! empty( $filters['attributes'] ) && is_array( $filters['attributes'] ) ) {
			foreach ( $filters['attributes'] as $taxonomy => $terms ) {
				$taxonomy = sanitize_key( $taxonomy );
				if ( empty( $terms ) || ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', (array) $terms ),
				);
			}
		}

		// Price range filter — uses the indexed _price meta key WooCommerce maintains.
		if ( isset( $filters['price_min'], $filters['price_max'] ) && '' !== $filters['price_min'] && '' !== $filters['price_max'] ) {
			$meta_query[] = array(
				'key'     => '_price',
				'value'   => array( (float) $filters['price_min'], (float) $filters['price_max'] ),
				'compare' => 'BETWEEN',
				'type'    => 'DECIMAL(10,2)',
			);
		}

		// In-stock toggle.
		if ( ! empty( $filters['in_stock_only'] ) ) {
			$meta_query[] = array(
				'key'   => '_stock_status',
				'value' => 'instock',
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$sort_order = isset( $settings['order'] ) && in_array( $settings['order'], array( 'ASC', 'DESC' ), true ) ? $settings['order'] : null;
		switch ( ! empty( $settings['orderby'] ) ? $settings['orderby'] : 'menu_order' ) {
			case 'price_low':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['order']    = $sort_order ? $sort_order : 'ASC';
				break;
			case 'price_high':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['order']    = $sort_order ? $sort_order : 'DESC';
				break;
			case 'newest':
				$args['orderby'] = 'date';
				$args['order']   = $sort_order ? $sort_order : 'DESC';
				break;
			case 'popularity':
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = 'meta_value_num';
				$args['order']    = $sort_order ? $sort_order : 'DESC';
				break;
			case 'rating':
				$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = 'meta_value_num';
				$args['order']    = $sort_order ? $sort_order : 'DESC';
				break;
			default:
				$sort_order      = $sort_order ? $sort_order : 'ASC';
				$args['orderby'] = array( 'menu_order' => $sort_order, 'title' => $sort_order );
		}

		if ( 'best_selling' === $query_mode ) {
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
		} elseif ( 'top_rated' === $query_mode ) {
			$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
		}

		/**
		 * Filters the final WP_Query arguments before the catalog query runs.
		 *
		 * @param array $args    WP_Query arguments.
		 * @param array $filters Sanitized catalog filter state.
		 */
		$args = apply_filters( 'ewcb_catalog_query_args', $args, $filters );

		return new \WP_Query( $args );
	}

	/**
	 * Computes min/max product prices for the current archive, taxonomy,
	 * attribute, and stock filters, ignoring the active price filter.
	 *
	 * @param array $filters Sanitized filter state.
	 * @param array $archive_context Active WooCommerce archive constraints.
	 * @return array{min: float, max: float}
	 */
	public function get_price_bounds( $filters, $archive_context = array() ) {
		global $wpdb;

		$cache_key = 'ewcb_bounds_' . md5(
			wp_json_encode(
				array(
					$filters['category'] ?? '',
					$filters['attributes'] ?? array(),
					! empty( $filters['in_stock_only'] ),
					$archive_context,
				)
			)
		);
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Reuse build_query's tax logic via a lightweight bounds-only query.
		$bounds_filters              = $filters;
		$bounds_filters['price_min'] = '';
		$bounds_filters['price_max'] = '';

		$query = $this->build_query(
			$bounds_filters,
			array(
				'products_per_page' => -1,
				'fields'            => 'ids',
				'archive_context'   => $archive_context,
			),
			1
		);
		$ids   = $query->posts ? array_map( 'absint', $query->posts ) : array();

		if ( empty( $ids ) ) {
			$bounds = array(
				'min' => 0,
				'max' => 0,
			);
		} else {
			$ids_sql = implode( ',', $ids );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$row = $wpdb->get_row(
				"SELECT MIN(CAST(meta_value AS DECIMAL(10,2))) AS min_price, MAX(CAST(meta_value AS DECIMAL(10,2))) AS max_price
				 FROM {$wpdb->postmeta}
				 WHERE meta_key = '_price' AND post_id IN ({$ids_sql})"
			);

			$bounds = array(
				'min' => $row ? (float) $row->min_price : 0,
				'max' => $row ? (float) $row->max_price : 0,
			);
		}

		set_transient( $cache_key, $bounds, self::CACHE_TTL );

		return $bounds;
	}

	/**
	 * Reads and decodes a JSON-encoded AJAX request property.
	 *
	 * @param string $key Request property name.
	 * @return array
	 */
	private function get_request_data( $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return array();
		}

		$data = wp_unslash( $_POST[ $key ] );
		if ( is_array( $data ) ) {
			return $data;
		}

		if ( ! is_string( $data ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid catalog request data.', 'ewcb' ) ),
				400
			);
		}

		$decoded = json_decode( $data, true );
		if ( ! is_array( $decoded ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid catalog request data.', 'ewcb' ) ),
				400
			);
		}

		return $decoded;
	}

	/**
	 * Creates a signed token for archive query data passed through the widget markup.
	 *
	 * @param array $context Public WooCommerce archive query context.
	 * @return string
	 */
	public function create_archive_context_token( $context ) {
		$payload = rtrim( strtr( base64_encode( wp_json_encode( $context ) ), '+/', '-_' ), '=' );
		$token   = $payload . '.' . hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );

		return $token;
	}

	/**
	 * Verifies and decodes the signed archive query context from widget settings.
	 *
	 * @param string $token Signed archive context.
	 * @return array|null
	 */
	private function decode_archive_context( $token ) {
		if ( ! is_string( $token ) || strlen( $token ) > 20000 || false === strpos( $token, '.' ) ) {
			return null;
		}

		list( $payload, $signature ) = explode( '.', $token, 2 );
		$expected_signature          = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return null;
		}

		$encoded_payload = strtr( $payload, '-_', '+/' );
		$padding         = strlen( $encoded_payload ) % 4;
		if ( $padding ) {
			$encoded_payload .= str_repeat( '=', 4 - $padding );
		}

		$decoded = base64_decode( $encoded_payload, true );
		$context = false !== $decoded ? json_decode( $decoded, true ) : null;
		if ( ! is_array( $context ) ) {
			return null;
		}

		$sanitized = array();
		if ( ! empty( $context['is_archive'] ) ) {
			$sanitized['is_archive'] = true;
		}
		if ( isset( $context['tax_query'] ) && is_array( $context['tax_query'] ) ) {
			$sanitized['tax_query'] = $context['tax_query'];
		}
		if ( isset( $context['meta_query'] ) && is_array( $context['meta_query'] ) ) {
			$sanitized['meta_query'] = $context['meta_query'];
		}
		if ( isset( $context['search'] ) && is_string( $context['search'] ) ) {
			$sanitized['search'] = sanitize_text_field( $context['search'] );
		}

		return $sanitized;
	}

	/**
	 * Whitelists and sanitizes the submitted filter state.
	 *
	 * @param array $raw Raw filter data.
	 * @return array
	 */
	private function sanitize_filters( $raw ) {
		$filters = array(
			'category'      => array(),
			'attributes'    => array(),
			'price_min'     => '',
			'price_max'     => '',
			'in_stock_only' => false,
		);

		if ( ! empty( $raw['category'] ) ) {
			$filters['category'] = array_map( 'sanitize_title', (array) $raw['category'] );
		}

		if ( ! empty( $raw['attributes'] ) && is_array( $raw['attributes'] ) ) {
			foreach ( $raw['attributes'] as $taxonomy => $terms ) {
				$filters['attributes'][ sanitize_key( $taxonomy ) ] = array_map( 'sanitize_title', (array) $terms );
			}
		}

		if ( isset( $raw['price_min'] ) && is_numeric( $raw['price_min'] ) ) {
			$filters['price_min'] = (float) $raw['price_min'];
		}

		if ( isset( $raw['price_max'] ) && is_numeric( $raw['price_max'] ) ) {
			$filters['price_max'] = (float) $raw['price_max'];
		}

		$filters['in_stock_only'] = ! empty( $raw['in_stock_only'] );

		return $filters;
	}

	/**
	 * Whitelists and sanitizes widget settings sent from the browser.
	 *
	 * @param array $raw Raw widget settings.
	 * @return array
	 */
	private function sanitize_widget_settings( $raw ) {
		$products_per_page = isset( $raw['products_per_page'] ) ? absint( $raw['products_per_page'] ) : 12;
		$ids = static function ( $value ) {
			$values = is_array( $value ) ? $value : preg_split( '/[\s,]+/', (string) $value );
			return array_values( array_unique( array_filter( array_map( 'absint', $values ) ) ) );
		};
		$order = isset( $raw['order'] ) && is_string( $raw['order'] ) ? strtoupper( sanitize_text_field( $raw['order'] ) ) : '';

		return array(
			'products_per_page'  => min( 48, max( 1, $products_per_page ) ),
			'orderby'            => isset( $raw['orderby'] ) ? sanitize_key( $raw['orderby'] ) : 'menu_order',
			'order'              => in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'default',
			'query_source'       => isset( $raw['query_source'] ) && in_array( $raw['query_source'], array( 'archive', 'custom' ), true ) ? $raw['query_source'] : 'auto',
			'query_mode'         => isset( $raw['query_mode'] ) && in_array( $raw['query_mode'], array( 'all', 'category', 'featured', 'on_sale', 'best_selling', 'top_rated' ), true ) ? $raw['query_mode'] : 'all',
			'query_categories'   => isset( $raw['query_categories'] ) ? $ids( $raw['query_categories'] ) : array(),
			'include_products'   => isset( $raw['include_products'] ) ? $ids( $raw['include_products'] ) : array(),
			'exclude_products'   => isset( $raw['exclude_products'] ) ? $ids( $raw['exclude_products'] ) : array(),
			'image_ratio'        => isset( $raw['image_ratio'] ) ? sanitize_text_field( $raw['image_ratio'] ) : '1/1',
			'archive_context'    => isset( $raw['archive_context'] ) ? $this->decode_archive_context( $raw['archive_context'] ) : array(),
			'show_category'      => ! isset( $raw['show_category'] ) || ! empty( $raw['show_category'] ),
			'show_quick_view'    => ! empty( $raw['show_quick_view'] ),
			'show_wishlist'      => ! empty( $raw['show_wishlist'] ),
			'show_compare'       => ! empty( $raw['show_compare'] ),
			'hover_image_swap'   => ! empty( $raw['hover_image_swap'] ),
			'image_hover_zoom'  => ! isset( $raw['image_hover_zoom'] ) || ! empty( $raw['image_hover_zoom'] ),
			'action_visibility'  => isset( $raw['action_visibility'] ) && 'hover' === $raw['action_visibility'] ? 'hover' : 'always',
			'action_position'    => isset( $raw['action_position'] ) && in_array( $raw['action_position'], array( 'top-right', 'top-left', 'bottom-right', 'bottom-left' ), true ) ? $raw['action_position'] : 'top-right',
			'action_icon_layout' => isset( $raw['action_icon_layout'] ) && 'horizontal' === $raw['action_icon_layout'] ? 'horizontal' : 'vertical',
			'show_cart_icon'     => ! empty( $raw['show_cart_icon'] ),
			'cart_button_width'  => isset( $raw['cart_button_width'] ) && 'inline' === $raw['cart_button_width'] ? 'inline' : 'full',
			'cart_vertical_alignment' => isset( $raw['cart_vertical_alignment'] ) && 'flow' === $raw['cart_vertical_alignment'] ? 'flow' : 'bottom',
			'button_alignment'   => isset( $raw['button_alignment'] ) && in_array( $raw['button_alignment'], array( 'left', 'center', 'right' ), true ) ? $raw['button_alignment'] : 'center',
		);
	}

	/**
	 * Flushes all cached AJAX filter responses. Hooked to product save/
	 * stock-change events so filter results never go stale beyond a
	 * single edit.
	 *
	 * @return void
	 */
	public static function flush_cache() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '\_transient\_ewcb\_%'
			 OR option_name LIKE '\_transient\_timeout\_ewcb\_%'"
		);
	}
}
