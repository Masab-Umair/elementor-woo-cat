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
		if ( ! grid || ! window.EWCB_AJAX ) {
			return;
		}

		var settings = {};
		try {
			settings = JSON.parse( grid.getAttribute( 'data-settings' ) || '{}' );
		} catch ( e ) {
			settings = {};
		}

		var filters = panel ? readFilterState( panel ) : {};

		grid.classList.add( 'is-loading' );

		var formData = new FormData();
		formData.append( 'action', 'ewcb_filter_products' );
		formData.append( 'nonce', window.EWCB_AJAX.nonce );
		formData.append( 'settings', JSON.stringify( settings ) );
		formData.append( 'filters', JSON.stringify( filters ) );
		formData.append( 'paged', paged || 1 );

		fetch( window.EWCB_AJAX.ajax_url, { method: 'POST', body: formData, credentials: 'same-origin' } )
			.then( function ( res ) {
				return res.json();
			} )
			.then( function ( json ) {
				grid.classList.remove( 'is-loading' );
				if ( ! json.success ) {
					return;
				}

				grid.innerHTML = json.data.html;

				updatePagination( layout, json.data );
				updatePriceBounds( layout, json.data.price_range );

				if ( window.EWCB_INIT ) {
					window.EWCB_INIT( layout );
				}

				grid.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			} )
			.catch( function () {
				grid.classList.remove( 'is-loading' );
			} );
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
		if ( prevBtn ) {
			prevBtn.disabled = data.paged <= 1;
		}
		if ( nextBtn ) {
			nextBtn.disabled = data.paged >= data.max_pages;
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
		if ( minInput ) {
			minInput.min = range.min;
			minInput.max = range.max;
		}
		if ( maxInput ) {
			maxInput.min = range.min;
			maxInput.max = range.max;
		}
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

		var nav = layout.querySelector( '.ewcb-pagination' );
		if ( nav ) {
			nav.addEventListener( 'click', function ( e ) {
				var prev = e.target.closest( '.ewcb-pagination__prev' );
				var next = e.target.closest( '.ewcb-pagination__next' );
				if ( ! prev && ! next ) {
					return;
				}
				var current = parseInt( nav.getAttribute( 'data-current-page' ), 10 ) || 1;
				var max     = parseInt( nav.getAttribute( 'data-max-pages' ), 10 ) || 1;
				var target  = prev ? Math.max( 1, current - 1 ) : Math.min( max, current + 1 );
				if ( target !== current ) {
					fetchAndRender( layout, target );
				}
			} );
		}
	}

	function syncPriceLabels( panel ) {
		var slider = panel.querySelector( '.ewcb-filters__price-slider' );
		if ( ! slider ) {
			return;
		}
		var minInput = slider.querySelector( '.ewcb-price-min' );
		var maxInput = slider.querySelector( '.ewcb-price-max' );
		var minLabel = panel.querySelector( '.ewcb-price-min-label' );
		var maxLabel = panel.querySelector( '.ewcb-price-max-label' );
		if ( minInput && minLabel ) {
			minLabel.textContent = minInput.value;
		}
		if ( maxInput && maxLabel ) {
			maxLabel.textContent = maxInput.value;
		}
	}

	function initAll( scope ) {
		scope = scope || document;
		scope.querySelectorAll( '.ewcb-layout--with-filters' ).forEach( initFilterLayout );
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
