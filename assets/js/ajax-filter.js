/**
 * Elementor Woo Catalog Builder — AJAX Side-Filter Logic
 *
 * Reads the current filter state from a widget's `.ewcb-filters` panel,
 * posts it to `ewcb_filter_products`, and swaps the resulting markup
 * into the matching `.ewcb-grid`. Runs entirely client-side with no
 * page reloads; depends on catalog-frontend.js for shared init helpers.
 */
( function () {
	'use strict';

	function debounce( fn, wait ) {
		var t;
		return function () {
			var args = arguments;
			var ctx  = this;
			clearTimeout( t );
			t = setTimeout( function () {
				fn.apply( ctx, args );
			}, wait );
		};
	}

	/**
	 * Reads every checked/filled control inside a filter panel into a
	 * plain filter-state object matching the shape the PHP side expects.
	 *
	 * @param {HTMLElement} panel .ewcb-filters element.
	 * @return {Object}
	 */
	function readFilterState( panel ) {
		var state = {
			category: [],
			attributes: {},
			price_min: '',
			price_max: '',
			in_stock_only: false,
		};

		panel.querySelectorAll( '[data-filter-type="category"] input[type="checkbox"]:checked' ).forEach( function ( cb ) {
			state.category.push( cb.value );
		} );

		panel.querySelectorAll( '[data-filter-type="attribute"]' ).forEach( function ( group ) {
			var taxonomy = group.getAttribute( 'data-taxonomy' );
			var values   = [];
			group.querySelectorAll( 'input[type="checkbox"]:checked' ).forEach( function ( cb ) {
				values.push( cb.value );
			} );
			if ( values.length ) {
				state.attributes[ taxonomy ] = values;
			}
		} );

		var priceSlider = panel.querySelector( '.ewcb-filters__price-slider' );
		if ( priceSlider ) {
			var minInput = priceSlider.querySelector( '.ewcb-price-min' );
			var maxInput = priceSlider.querySelector( '.ewcb-price-max' );
			if ( minInput && maxInput ) {
				state.price_min = minInput.value;
				state.price_max = maxInput.value;
			}
		}

		var stockBox = panel.querySelector( '[data-filter-type="stock"] input[type="checkbox"]' );
		if ( stockBox ) {
			state.in_stock_only = stockBox.checked;
		}

		return state;
	}

	/**
	 * Fetches filtered results and swaps them into the grid.
	 *
	 * @param {HTMLElement} layout  .ewcb-layout wrapper containing both filters and grid.
	 * @param {number}      paged   Page number to request.
	 * @return {void}
	 */
	function fetchAndRender( layout, paged ) {
		var grid = layout.querySelector( '.ewcb-grid' );
		var panel = layout.querySelector( '.ewcb-filters' );
		if ( ! grid ) {
			return;
		}
		if ( ! window.EWCB_AJAX || ! window.EWCB_AJAX.ajax_url || ! window.EWCB_AJAX.nonce ) {
			markPaginationFallback( layout );
			showRequestError( grid, 'AJAX pagination is unavailable. Use the page links to continue.' );
			return;
		}

		var settings = {};
		try {
			settings = JSON.parse( grid.getAttribute( 'data-settings' ) || '{}' );
		} catch ( e ) {
			markPaginationFallback( layout );
			showRequestError( grid, 'Catalog settings could not be read. Use the page link to continue.' );
			return;
		}

		var filters = panel ? readFilterState( panel ) : {};
		var requestId = ( Number( layout.dataset.ewcbRequestId ) || 0 ) + 1;
		layout.dataset.ewcbRequestId = requestId;

		grid.classList.add( 'is-loading' );
		grid.setAttribute( 'aria-busy', 'true' );
		var previousError = grid.querySelector( '.ewcb-request-error' );
		if ( previousError ) {
			previousError.remove();
		}

		var formData = new FormData();
		formData.append( 'action', 'ewcb_filter_products' );
		formData.append( 'nonce', window.EWCB_AJAX.nonce );
		formData.append( 'settings', JSON.stringify( settings ) );
		formData.append( 'filters', JSON.stringify( filters ) );
		formData.append( 'paged', paged || 1 );
		formData.append( 'include_price_bounds', panel && panel.querySelector( '.ewcb-filters__price-slider' ) ? '1' : '0' );

		fetch( window.EWCB_AJAX.ajax_url, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
		} )
			.then( function ( res ) {
				return res.text().then( function ( text ) {
					var payload;
					try { payload = JSON.parse( text ); } catch ( error ) {
						throw new Error( 'The server returned an invalid response (HTTP ' + res.status + '). Check the WordPress/PHP error log.' );
					}
					if ( ! res.ok ) {
						throw new Error( payload && payload.data && payload.data.message ? payload.data.message : 'Catalog request failed with status ' + res.status );
					}
					return payload;
				} );
			} )
			.then( function ( json ) {
				if ( Number( layout.dataset.ewcbRequestId ) !== requestId ) {
					return;
				}
				grid.classList.remove( 'is-loading' );
				grid.setAttribute( 'aria-busy', 'false' );
				if ( ! json || ! json.success || ! json.data || 'string' !== typeof json.data.html ) {
					markPaginationFallback( layout );
					showRequestError( grid, json && json.data && json.data.message ? json.data.message : 'The catalog could not load this page. Use the page link to continue.' );
					return;
				}

				grid.innerHTML = json.data.html;
				grid.scrollLeft = 0;

				updatePagination( layout, json.data );
				updatePriceBounds( layout, json.data.price_range );

				if ( window.EWCB_INIT ) {
					window.EWCB_INIT( layout );
				}

				grid.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			} )
			.catch( function ( error ) {
				if ( Number( layout.dataset.ewcbRequestId ) !== requestId ) {
					return;
				}
				grid.classList.remove( 'is-loading' );
				grid.setAttribute( 'aria-busy', 'false' );
				markPaginationFallback( layout );
				showRequestError( grid, error && error.message ? error.message : '' );
			} );
	}

	function markPaginationFallback( layout ) {
		var nav = layout.querySelector( '.ewcb-pagination' );
		if ( ! nav ) { return; }
		nav.querySelectorAll( '.ewcb-pagination__prev, .ewcb-pagination__next' ).forEach( function ( link ) {
			link.dataset.ewcbFallback = '1';
		} );
	}

	function buildPageUrl( pageParam, page ) {
		try {
			var url = new URL( window.location.href );
			// Keep an explicit page=1 override so fallback links also work on
			// pretty-permalink WooCommerce archive pages (/page/2/).
			url.searchParams.set( pageParam, String( Math.max( 1, page ) ) );
			return url.toString();
		} catch ( error ) {
			return window.location.href;
		}
	}

	function showRequestError( grid, message ) {
		var error = document.createElement( 'div' );
		error.className = 'ewcb-request-error';
		error.setAttribute( 'role', 'alert' );
		error.textContent = message || ( window.EWCB_AJAX && window.EWCB_AJAX.request_error ? window.EWCB_AJAX.request_error : 'We could not update the products. Please try again.' );
		grid.appendChild( error );
	}

	/**
	 * Refreshes the pagination control's state after a filter request.
	 *
	 * @param {HTMLElement} layout
	 * @param {Object}      data   AJAX response payload.
	 * @return {void}
	 */
	function updatePagination( layout, data ) {
		var nav = layout.querySelector( '.ewcb-pagination' );
		if ( ! nav ) {
			return;
		}

		if ( data.max_pages <= 1 ) {
			nav.style.display = 'none';
			return;
		}
		nav.style.display = '';

		nav.setAttribute( 'data-current-page', data.paged );
		nav.setAttribute( 'data-max-pages', data.max_pages );

		var currentEl = nav.querySelector( '.ewcb-pagination__current' );
		var totalEl   = nav.querySelector( '.ewcb-pagination__total' );
		if ( currentEl ) {
			currentEl.textContent = data.paged;
		}
		if ( totalEl ) {
			totalEl.textContent = data.max_pages;
		}

		var prevBtn = nav.querySelector( '.ewcb-pagination__prev' );
		var nextBtn = nav.querySelector( '.ewcb-pagination__next' );
		var pageParam = nav.getAttribute( 'data-page-param' ) || '';
		if ( prevBtn ) {
			var prevDisabled = data.paged <= 1;
			prevBtn.classList.toggle( 'is-disabled', prevDisabled );
			prevBtn.setAttribute( 'aria-disabled', prevDisabled ? 'true' : 'false' );
			prevBtn.href = buildPageUrl( pageParam, Math.max( 1, data.paged - 1 ) );
			delete prevBtn.dataset.ewcbFallback;
		}
		if ( nextBtn ) {
			var nextDisabled = data.paged >= data.max_pages;
			nextBtn.classList.toggle( 'is-disabled', nextDisabled );
			nextBtn.setAttribute( 'aria-disabled', nextDisabled ? 'true' : 'false' );
			nextBtn.href = buildPageUrl( pageParam, Math.min( data.max_pages, data.paged + 1 ) );
			delete nextBtn.dataset.ewcbFallback;
		}
	}

	/**
	 * Adapts the price slider's min/max bounds to the active
	 * category/attribute filters so the range always reflects what's
	 * actually purchasable within the current selection.
	 *
	 * @param {HTMLElement} layout
	 * @param {Object}      range {min, max}
	 * @return {void}
	 */
	function updatePriceBounds( layout, range ) {
		if ( ! range ) {
			return;
		}
		var slider = layout.querySelector( '.ewcb-filters__price-slider' );
		if ( ! slider ) {
			return;
		}
		var minInput = slider.querySelector( '.ewcb-price-min' );
		var maxInput = slider.querySelector( '.ewcb-price-max' );
		slider.setAttribute( 'data-min', range.min );
		slider.setAttribute( 'data-max', range.max );
		if ( minInput ) {
			minInput.min = range.min;
			minInput.max = range.max;
			minInput.value = Math.max( range.min, Math.min( range.max, Number( minInput.value ) ) );
			minInput.disabled = Number( range.min ) === Number( range.max );
		}
		if ( maxInput ) {
			maxInput.min = range.min;
			maxInput.max = range.max;
			maxInput.value = Math.max( range.min, Math.min( range.max, Number( maxInput.value ) ) );
			maxInput.disabled = Number( range.min ) === Number( range.max );
		}
		if ( minInput && maxInput && Number( minInput.value ) > Number( maxInput.value ) ) {
			minInput.value = maxInput.value;
		}
		syncPriceLabels( layout.querySelector( '.ewcb-filters' ) );
	}

	/**
	 * Wires all interactive behavior for one `.ewcb-layout--with-filters`
	 * instance: change listeners, pagination clicks, reset, mobile drawer.
	 *
	 * @param {HTMLElement} layout
	 * @return {void}
	 */
	function initFilterLayout( layout ) {
		if ( layout.dataset.ewcbFilterInit ) {
			return;
		}
		layout.dataset.ewcbFilterInit = '1';

		var panel = layout.querySelector( '.ewcb-filters' );
		var debouncedFetch = debounce( function () {
			fetchAndRender( layout, 1 );
		}, 350 );

		if ( panel ) {
			panel.addEventListener( 'change', function ( e ) {
				if ( e.target.matches( 'input[type="checkbox"]' ) ) {
					fetchAndRender( layout, 1 );
				}
			} );

			panel.addEventListener( 'input', function ( e ) {
				if ( e.target.matches( '.ewcb-price-min, .ewcb-price-max' ) ) {
					var slider = e.target.closest( '.ewcb-filters__price-slider' );
					var minInput = slider.querySelector( '.ewcb-price-min' );
					var maxInput = slider.querySelector( '.ewcb-price-max' );
					if ( e.target.matches( '.ewcb-price-min' ) && Number( minInput.value ) > Number( maxInput.value ) ) {
						minInput.value = maxInput.value;
					} else if ( e.target.matches( '.ewcb-price-max' ) && Number( maxInput.value ) < Number( minInput.value ) ) {
						maxInput.value = minInput.value;
					}
					syncPriceLabels( panel );
					debouncedFetch();
				}
			} );

			var resetBtn = panel.querySelector( '.ewcb-filters__reset' );
			if ( resetBtn ) {
				resetBtn.addEventListener( 'click', function () {
					panel.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( cb ) {
						cb.checked = false;
					} );
					var slider = panel.querySelector( '.ewcb-filters__price-slider' );
					if ( slider ) {
						var minInput = slider.querySelector( '.ewcb-price-min' );
						var maxInput = slider.querySelector( '.ewcb-price-max' );
						if ( minInput ) {
							minInput.value = slider.getAttribute( 'data-min' );
						}
						if ( maxInput ) {
							maxInput.value = slider.getAttribute( 'data-max' );
						}
						syncPriceLabels( panel );
					}
					fetchAndRender( layout, 1 );
				} );
			}

			// Mobile collapsible drawer.
			var toggleBtn = panel.querySelector( '.ewcb-filters__toggle-btn' );
			var closeBtn  = panel.querySelector( '.ewcb-filters__close' );
			if ( toggleBtn ) {
				toggleBtn.addEventListener( 'click', function () {
					panel.classList.add( 'is-open' );
				} );
			}
			if ( closeBtn ) {
				closeBtn.addEventListener( 'click', function () {
					panel.classList.remove( 'is-open' );
				} );
			}
		}

		layout.addEventListener( 'click', function ( e ) {
			var target = e.target;
			if ( ! target || ! target.closest ) { return; }
			var prev = target.closest( '.ewcb-pagination__prev' );
			var next = target.closest( '.ewcb-pagination__next' );
			if ( ! prev && ! next ) { return; }
			var link = prev || next;
			var nav = link.closest( '.ewcb-pagination' );
			if ( ! nav || 'true' === link.getAttribute( 'aria-disabled' ) ) {
				e.preventDefault();
				return;
			}
			// If a previous AJAX request failed, let the anchor perform a full page load.
			if ( '1' === link.dataset.ewcbFallback || ! window.EWCB_AJAX || ! window.EWCB_AJAX.ajax_url || ! window.EWCB_AJAX.nonce ) {
				return;
			}
			e.preventDefault();
			var current = parseInt( nav.getAttribute( 'data-current-page' ), 10 ) || 1;
			var max     = parseInt( nav.getAttribute( 'data-max-pages' ), 10 ) || 1;
			var targetPage = prev ? Math.max( 1, current - 1 ) : Math.min( max, current + 1 );
			if ( targetPage !== current ) { fetchAndRender( layout, targetPage ); }
		} );
	}

	function syncPriceLabels( panel ) {
		if ( ! panel ) {
			return;
		}
		var slider = panel.querySelector( '.ewcb-filters__price-slider' );
		if ( ! slider ) {
			return;
		}
		var minInput = slider.querySelector( '.ewcb-price-min' );
		var maxInput = slider.querySelector( '.ewcb-price-max' );
		var minLabel = panel.querySelector( '.ewcb-price-min-label' );
		var maxLabel = panel.querySelector( '.ewcb-price-max-label' );
		if ( minInput && minLabel ) {
			minLabel.textContent = formatPrice( minInput.value );
		}
		if ( maxInput && maxLabel ) {
			maxLabel.textContent = formatPrice( maxInput.value );
		}
	}

	function formatPrice( value ) {
		var config = window.EWCB_AJAX || {};
		try {
			return new Intl.NumberFormat( config.locale || undefined, {
				style: 'currency',
				currency: config.currency || 'USD',
				minimumFractionDigits: Number( config.price_decimals ) || 0,
				maximumFractionDigits: Number( config.price_decimals ) || 0,
			} ).format( Number( value ) );
		} catch ( e ) {
			return value;
		}
	}

	function initAll( scope ) {
		scope = scope || document;
		scope.querySelectorAll( '.ewcb-layout' ).forEach( initFilterLayout );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll( document );
		} );
	} else {
		initAll( document );
	}

	if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/ewcb-product-catalog.default', function ( $scope ) {
			initAll( $scope[ 0 ] || $scope );
		} );
	}
} )();
