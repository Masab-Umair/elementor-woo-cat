/**
 * Elementor Woo Catalog Builder — Core Frontend Behaviors
 *
 * Handles: product grid sliders, single-product gallery switching,
 * sticky add-to-cart bar, quick view modal, and wishlist/compare icon
 * state. No external dependencies; written in plain ES2017+ JS.
 *
 * Re-runs automatically inside the Elementor editor whenever a widget
 * is added or refreshed, via the `elementor/frontend/init` hook below.
 */
( function () {
	'use strict';

	/* -------------------------------------------------------------- *
	 * Utilities
	 * -------------------------------------------------------------- */

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

	function getLocalSet( key ) {
		try {
			var raw = window.localStorage.getItem( key );
			return raw ? new Set( JSON.parse( raw ) ) : new Set();
		} catch ( e ) {
			return new Set();
		}
	}

	function saveLocalSet( key, set ) {
		try {
			window.localStorage.setItem( key, JSON.stringify( Array.from( set ) ) );
		} catch ( e ) {
			/* localStorage unavailable (private mode, etc) — fail silently. */
		}
	}

	/* -------------------------------------------------------------- *
	 * Product grid sliders
	 * -------------------------------------------------------------- */

	function initSliders( scope ) {
		var sliders = scope.querySelectorAll( '.ewcb-grid--slider' );

		sliders.forEach( function ( slider ) {
			if ( slider.dataset.ewcbSliderInit ) {
				return;
			}
			slider.dataset.ewcbSliderInit = '1';

			var wrapper   = slider.closest( '.ewcb-catalog-main' ) || slider.parentElement;
			var showArrows = '1' === slider.dataset.arrows;
			var showDots   = '1' === slider.dataset.dots;
			var loop       = '1' === slider.dataset.loop;
			var autoplay   = '1' === slider.dataset.autoplay;
			var speed      = parseInt( slider.dataset.autoplaySpeed, 10 ) || 4000;

			var cards = Array.prototype.slice.call( slider.children );
			if ( loop && cards.length > 1 ) {
				// Simple loop illusion: clone first/last card to the opposite ends.
				var firstClone = cards[ 0 ].cloneNode( true );
				var lastClone  = cards[ cards.length - 1 ].cloneNode( true );
				firstClone.setAttribute( 'aria-hidden', 'true' );
				lastClone.setAttribute( 'aria-hidden', 'true' );
				slider.appendChild( firstClone );
				slider.insertBefore( lastClone, slider.firstChild );
			}

			var nav = null;
			if ( showArrows ) {
				nav = document.createElement( 'div' );
				nav.className = 'ewcb-slider-nav';
				nav.innerHTML =
					'<button type="button" class="ewcb-slider-nav__arrow ewcb-slider-nav__prev" aria-label="Previous">' +
					'<svg viewBox="0 0 24 24" width="18" height="18"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>' +
					'<button type="button" class="ewcb-slider-nav__arrow ewcb-slider-nav__next" aria-label="Next">' +
					'<svg viewBox="0 0 24 24" width="18" height="18"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
				wrapper.insertBefore( nav, slider.nextSibling );

				var scrollByCard = function ( dir ) {
					var card = slider.querySelector( '.ewcb-card' );
					var w    = card ? card.getBoundingClientRect().width + 24 : 300;
					slider.scrollBy( { left: dir * w, behavior: 'smooth' } );
				};

				nav.querySelector( '.ewcb-slider-nav__prev' ).addEventListener( 'click', function () {
					scrollByCard( -1 );
				} );
				nav.querySelector( '.ewcb-slider-nav__next' ).addEventListener( 'click', function () {
					scrollByCard( 1 );
				} );
			}

			if ( showDots && cards.length > 1 ) {
				var dotsWrap = document.createElement( 'div' );
				dotsWrap.className = 'ewcb-slider-dots';
				cards.forEach( function ( card, i ) {
					var dot = document.createElement( 'button' );
					dot.type = 'button';
					dot.className = 'ewcb-slider-dots__dot' + ( 0 === i ? ' is-active' : '' );
					dot.addEventListener( 'click', function () {
						card.scrollIntoView( { behavior: 'smooth', inline: 'start', block: 'nearest' } );
					} );
					dotsWrap.appendChild( dot );
				} );
				wrapper.insertBefore( dotsWrap, nav ? nav.nextSibling : slider.nextSibling );

				slider.addEventListener(
					'scroll',
					debounce( function () {
						var scrollLeft = slider.scrollLeft;
						var closest     = 0;
						var closestDist = Infinity;
						cards.forEach( function ( card, i ) {
							var dist = Math.abs( card.offsetLeft - scrollLeft );
							if ( dist < closestDist ) {
								closestDist = dist;
								closest     = i;
							}
						} );
						dotsWrap.querySelectorAll( '.ewcb-slider-dots__dot' ).forEach( function ( d, i ) {
							d.classList.toggle( 'is-active', i === closest );
						} );
					}, 100 ),
					{ passive: true }
				);
			}

			// Drag-to-scroll for mouse users.
			var isDown = false, startX, scrollStart;
			slider.addEventListener( 'mousedown', function ( e ) {
				isDown = true;
				startX = e.pageX;
				scrollStart = slider.scrollLeft;
				slider.style.cursor = 'grabbing';
			} );
			[ 'mouseleave', 'mouseup' ].forEach( function ( evt ) {
				slider.addEventListener( evt, function () {
					isDown = false;
					slider.style.cursor = '';
				} );
			} );
			slider.addEventListener( 'mousemove', function ( e ) {
				if ( ! isDown ) {
					return;
				}
				e.preventDefault();
				slider.scrollLeft = scrollStart - ( e.pageX - startX );
			} );

			if ( autoplay && cards.length > 1 ) {
				setInterval( function () {
					var atEnd = slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 4;
					if ( atEnd ) {
						slider.scrollTo( { left: 0, behavior: 'smooth' } );
					} else {
						var card = slider.querySelector( '.ewcb-card' );
						var w    = card ? card.getBoundingClientRect().width + 24 : 300;
						slider.scrollBy( { left: w, behavior: 'smooth' } );
					}
				}, speed );
			}
		} );
	}

	/* -------------------------------------------------------------- *
	 * Single-product gallery (thumbnail / dot switching)
	 * -------------------------------------------------------------- */

	function initSingleGallery( scope ) {
		scope.querySelectorAll( '.ewcb-single' ).forEach( function ( single ) {
			if ( single.dataset.ewcbGalleryInit ) {
				return;
			}
			single.dataset.ewcbGalleryInit = '1';

			var mainImg = single.querySelector( '[data-main-image]' );
			if ( ! mainImg ) {
				return;
			}

			var switchImage = function ( trigger, collection ) {
				var full = trigger.getAttribute( 'data-full' );
				if ( ! full ) {
					return;
				}
				mainImg.style.opacity = '0';
				setTimeout( function () {
					mainImg.src = full;
					mainImg.style.opacity = '1';
				}, 120 );
				collection.forEach( function ( el ) {
					el.classList.toggle( 'is-active', el === trigger );
				} );
			};

			var thumbs = Array.prototype.slice.call( single.querySelectorAll( '.ewcb-single__thumb' ) );
			thumbs.forEach( function ( thumb ) {
				thumb.addEventListener( 'click', function () {
					switchImage( thumb, thumbs );
				} );
			} );

			var dots = Array.prototype.slice.call( single.querySelectorAll( '.ewcb-single__dot' ) );
			dots.forEach( function ( dot ) {
				dot.addEventListener( 'click', function () {
					switchImage( dot, dots );
				} );
			} );
		} );
	}

	/* -------------------------------------------------------------- *
	 * Sticky Add-to-Cart bar
	 * -------------------------------------------------------------- */

	function initStickyCta( scope ) {
		scope.querySelectorAll( '.ewcb-sticky-cta' ).forEach( function ( bar ) {
			if ( bar.dataset.ewcbStickyInit ) {
				return;
			}
			bar.dataset.ewcbStickyInit = '1';

			var triggerSelector = bar.getAttribute( 'data-trigger-selector' );
			var trigger = triggerSelector ? document.querySelector( triggerSelector ) : null;
			if ( ! trigger || ! ( 'IntersectionObserver' in window ) ) {
				bar.classList.add( 'is-visible' );
				return;
			}

			var observer = new IntersectionObserver(
				function ( entries ) {
					entries.forEach( function ( entry ) {
						bar.classList.toggle( 'is-visible', ! entry.isIntersecting );
					} );
				},
				{ threshold: 0 }
			);
			observer.observe( trigger );
		} );
	}

	/* -------------------------------------------------------------- *
	 * Wishlist / Compare icon state (client-side persistence only)
	 * -------------------------------------------------------------- */

	function initIconToggles( scope ) {
		var wishlist = getLocalSet( 'ewcb_wishlist' );
		var compare  = getLocalSet( 'ewcb_compare' );

		function applyState() {
			scope.querySelectorAll( '.ewcb-wishlist' ).forEach( function ( btn ) {
				btn.classList.toggle( 'is-active', wishlist.has( btn.dataset.productId ) );
			} );
			scope.querySelectorAll( '.ewcb-compare' ).forEach( function ( btn ) {
				btn.classList.toggle( 'is-active', compare.has( btn.dataset.productId ) );
			} );
		}

		scope.addEventListener( 'click', function ( e ) {
			var wishBtn = e.target.closest( '.ewcb-wishlist' );
			if ( wishBtn ) {
				var id = wishBtn.dataset.productId;
				wishlist.has( id ) ? wishlist.delete( id ) : wishlist.add( id );
				saveLocalSet( 'ewcb_wishlist', wishlist );
				applyState();
				document.dispatchEvent( new CustomEvent( 'ewcb:wishlist-change', { detail: { productId: id, active: wishlist.has( id ) } } ) );
			}

			var compareBtn = e.target.closest( '.ewcb-compare' );
			if ( compareBtn ) {
				var cid = compareBtn.dataset.productId;
				compare.has( cid ) ? compare.delete( cid ) : compare.add( cid );
				saveLocalSet( 'ewcb_compare', compare );
				applyState();
				document.dispatchEvent( new CustomEvent( 'ewcb:compare-change', { detail: { productId: cid, active: compare.has( cid ) } } ) );
			}
		} );

		applyState();
	}

	/* -------------------------------------------------------------- *
	 * Quick View modal
	 * -------------------------------------------------------------- */

	function ensureQuickViewOverlay() {
		var overlay = document.querySelector( '.ewcb-quickview-overlay' );
		if ( overlay ) {
			return overlay;
		}
		overlay = document.createElement( 'div' );
		overlay.className = 'ewcb-quickview-overlay';
		overlay.innerHTML =
			'<div class="ewcb-quickview-modal">' +
			'<button type="button" class="ewcb-quickview-close" aria-label="Close">' +
			'<svg viewBox="0 0 24 24" width="20" height="20"><path d="M5 5l14 14M19 5L5 19" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>' +
			'</button><div class="ewcb-quickview-body"></div></div>';
		document.body.appendChild( overlay );

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay || e.target.closest( '.ewcb-quickview-close' ) ) {
				overlay.classList.remove( 'is-open' );
			}
		} );

		return overlay;
	}

	function initQuickView( scope ) {
		scope.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.ewcb-quick-view' );
			if ( ! btn || ! window.EWCB_AJAX ) {
				return;
			}

			var overlay = ensureQuickViewOverlay();
			var body    = overlay.querySelector( '.ewcb-quickview-body' );
			body.innerHTML = '<p style="padding:40px;text-align:center;">' + 'Loading…' + '</p>';
			overlay.classList.add( 'is-open' );

			var formData = new FormData();
			formData.append( 'action', 'ewcb_quick_view' );
			formData.append( 'nonce', window.EWCB_AJAX.nonce );
			formData.append( 'product_id', btn.dataset.productId );

			fetch( window.EWCB_AJAX.ajax_url, { method: 'POST', body: formData, credentials: 'same-origin' } )
				.then( function ( res ) {
					return res.json();
				} )
				.then( function ( json ) {
					if ( json.success ) {
						body.innerHTML = json.data.html;
					} else {
						body.innerHTML = '<p style="padding:40px;text-align:center;">' + ( json.data && json.data.message ? json.data.message : 'Unable to load product.' ) + '</p>';
					}
				} )
				.catch( function () {
					body.innerHTML = '<p style="padding:40px;text-align:center;">Something went wrong.</p>';
				} );
		} );
	}

	/* -------------------------------------------------------------- *
	 * Boot
	 * -------------------------------------------------------------- */

	function initAll( scope ) {
		scope = scope || document;
		initSliders( scope );
		initSingleGallery( scope );
		initStickyCta( scope );
		initIconToggles( scope );
		initQuickView( scope );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll( document );
		} );
	} else {
		initAll( document );
	}

	// Re-init when a widget is added/refreshed inside the Elementor editor preview.
	if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/ewcb-product-catalog.default', function ( $scope ) {
			initAll( $scope[ 0 ] || $scope );
		} );
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/ewcb-single-product.default', function ( $scope ) {
			initAll( $scope[ 0 ] || $scope );
		} );
	}

	// Expose for the AJAX filter script to re-run behaviors on new grid HTML.
	window.EWCB_INIT = initAll;
} )();
