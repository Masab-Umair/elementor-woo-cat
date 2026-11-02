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

		$settings = isset( $_POST['settings'] ) ? wp_unslash( (array) $_POST['settings'] ) : array();
		$settings = $this->sanitize_widget_settings( $settings );

		$filters = isset( $_POST['filters'] ) ? wp_unslash( (array) $_POST['filters'] ) : array();
		$filters = $this->sanitize_filters( $filters );

		$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

		$cache_key = 'ewcb_' . md5( wp_json_encode( $filters ) . wp_json_encode( $settings ) . $paged );
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
			'price_range' => $this->get_price_bounds( $filters ),
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
	 * @param array $settings Sanitized widget settings (columns, per_page, orderby, etc).
	 * @param int   $paged    Current page number.
	 * @return \WP_Query
	 */
	public function build_query( $filters, $settings, $paged = 1 ) {
		$per_page = ! empty( $settings['products_per_page'] ) ? absint( $settings['products_per_page'] ) : 12;

		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => max( 1, $paged ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		);

		$tax_query  = array( 'relation' => 'AND' );
		$meta_query = array( 'relation' => 'AND' );

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

		switch ( ! empty( $settings['orderby'] ) ? $settings['orderby'] : 'menu_order' ) {
			case 'price_low':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['order']    = 'ASC';
				break;
			case 'price_high':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['order']    = 'DESC';
				break;
			case 'newest':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
			case 'popularity':
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;
			default:
				$args['orderby'] = array( 'menu_order' => 'ASC', 'title' => 'ASC' );
		}

		/**
		 * Filters the final WP_Query arguments before the catalog query runs.
		 *
		 * @param array $args    WP_Query arguments.
		 * @param array $filters Sanitized filter state.
		 */
		$args = apply_filters( 'ewcb_catalog_query_args', $args, $filters );

		return new \WP_Query( $args );
	}

	/**
	 * Computes the min/max product price across the current taxonomy/
	 * attribute/stock filters (ignoring the price filter itself) so the
	 * price slider's bounds can adapt to the active category.
	 *
	 * @param array $filters Sanitized filter state.
	 * @return array{min: float, max: float}
	 */
	private function get_price_bounds( $filters ) {
		global $wpdb;

		$cache_key = 'ewcb_bounds_' . md5( wp_json_encode( array( $filters['category'] ?? '', $filters['attributes'] ?? array() ) ) );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Reuse build_query's tax logic via a lightweight bounds-only query.
		$bounds_filters              = $filters;
		$bounds_filters['price_min'] = '';
		$bounds_filters['price_max'] = '';
		$bounds_filters['in_stock_only'] = false;

		$query = $this->build_query( $bounds_filters, array( 'products_per_page' => -1 ), 1 );
		$ids   = $query->posts ? wp_list_pluck( $query->posts, 'ID' ) : array();

		if ( empty( $ids ) ) {
			$bounds = array(
				'min' => 0,
				'max' => 0,
			);
		} else {
			$ids_sql = implode( ',', array_map( 'absint', $ids ) );
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
	 * Whitelists and sanitizes the submitted filter state.
	 *
	 * @param array $raw Raw $_POST['filters'] data.
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
	 * Whitelists and sanitizes the submitted widget settings echoed back
	 * from the editor (only what's needed to render a consistent card).
	 *
	 * @param array $raw Raw $_POST['settings'] data.
	 * @return array
	 */
	private function sanitize_widget_settings( $raw ) {
		return array(
			'layout_preset'      => isset( $raw['layout_preset'] ) ? sanitize_key( $raw['layout_preset'] ) : 'minimal_modern',
			'products_per_page'  => isset( $raw['products_per_page'] ) ? absint( $raw['products_per_page'] ) : 12,
			'orderby'            => isset( $raw['orderby'] ) ? sanitize_key( $raw['orderby'] ) : 'menu_order',
			'image_ratio'        => isset( $raw['image_ratio'] ) ? sanitize_text_field( $raw['image_ratio'] ) : '1/1',
			'show_quick_view'    => ! empty( $raw['show_quick_view'] ),
			'show_wishlist'      => ! empty( $raw['show_wishlist'] ),
			'show_compare'       => ! empty( $raw['show_compare'] ),
			'hover_image_swap'   => ! empty( $raw['hover_image_swap'] ),
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
