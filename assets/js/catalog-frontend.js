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

	var wishlistItems = getLocalSet( 'ewcb_wishlist' );
	var compareItems  = getLocalSet( 'ewcb_compare' );
	var iconToggleListenerBound = false;
	var quickViewListenerBound = false;

	/* -------------------------------------------------------------- *
	 * Product grid sliders
	 * -------------------------------------------------------------- */

	function initSliders( scope ) {
		var sliders = scope.querySelectorAll( '.ewcb-grid--slider' );

		sliders.forEach( function ( slider ) {
			var wrapper    = slider.closest( '.ewcb-catalog-main' ) || slider.parentElement;
			var showArrows = '1' === slider.dataset.arrows;
			var showDots   = '1' === slider.dataset.dots;
			var loop       = '1' === slider.dataset.loop;
			var autoplay   = '1' === slider.dataset.autoplay;
			var speed      = parseInt( slider.dataset.autoplaySpeed, 10 ) || 4000;

			function getCards() {
				return Array.prototype.filter.call( slider.children, function ( child ) {
					return child.classList.contains( 'ewcb-card' );
				} );
			}

			function getStep() {
				var card = getCards()[ 0 ];
				if ( ! card ) {
					return 300;
				}
				var gap = parseFloat( window.getComputedStyle( slider ).columnGap ) || 0;
				return card.getBoundingClientRect().width + gap;
			}

			function goTo( index ) {
				var cards = getCards();
				if ( cards[ index ] ) {
					var offset = cards[ index ].getBoundingClientRect().left - slider.getBoundingClientRect().left;
					slider.scrollTo( { left: slider.scrollLeft + offset, behavior: 'smooth' } );
				}
			}

			function move( direction ) {
				var maxScroll = slider.scrollWidth - slider.clientWidth;
				if ( loop && direction < 0 && slider.scrollLeft <= 1 ) {
					slider.scrollTo( { left: maxScroll, behavior: 'smooth' } );
				} else if ( loop && direction > 0 && slider.scrollLeft >= maxScroll - 4 ) {
					slider.scrollTo( { left: 0, behavior: 'smooth' } );
				} else {
					slider.scrollBy( { left: direction * getStep(), behavior: 'smooth' } );
				}
			}

			var nav = null;
			if ( showArrows && ! slider.dataset.ewcbSliderInit ) {
				nav = document.createElement( 'div' );
				nav.className = 'ewcb-slider-nav';
				nav.innerHTML =
					'<button type="button" class="ewcb-slider-nav__arrow ewcb-slider-nav__prev" aria-label="Previous">' +
					'<svg viewBox="0 0 24 24" width="18" height="18"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>' +
					'<button type="button" class="ewcb-slider-nav__arrow ewcb-slider-nav__next" aria-label="Next">' +
					'<svg viewBox="0 0 24 24" width="18" height="18"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
				wrapper.insertBefore( nav, slider.nextSibling );

				nav.querySelector( '.ewcb-slider-nav__prev' ).addEventListener( 'click', function () {
					move( -1 );
				} );
				nav.querySelector( '.ewcb-slider-nav__next' ).addEventListener( 'click', function () {
					move( 1 );
				} );
			}

			var dotsWrap = wrapper.querySelector( '.ewcb-slider-dots' );
			if ( showDots && ! dotsWrap ) {
				var dotsWrap = document.createElement( 'div' );
				dotsWrap.className = 'ewcb-slider-dots';
				dotsWrap.addEventListener( 'click', function ( event ) {
					var dot = event.target.closest( '[data-slide-index]' );
					if ( dot ) {
						goTo( parseInt( dot.dataset.slideIndex, 10 ) );
					}
				} );
				wrapper.insertBefore( dotsWrap, nav ? nav.nextSibling : slider.nextSibling );
			}

			if ( ! slider.dataset.ewcbSliderInit ) {
				slider.addEventListener(
					'scroll',
					debounce( function () {
						var cards = getCards();
						var closest     = 0;
						var closestDist = Infinity;
						cards.forEach( function ( card, i ) {
							var dist = Math.abs( card.getBoundingClientRect().left - slider.getBoundingClientRect().left );
							if ( dist < closestDist ) {
								closestDist = dist;
								closest     = i;
							}
						} );
						if ( dotsWrap ) {
							dotsWrap.querySelectorAll( '.ewcb-slider-dots__dot' ).forEach( function ( d, i ) {
								d.classList.toggle( 'is-active', i === closest );
							} );
						}
					}, 100 ),
					{ passive: true }
				);

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

				if ( autoplay ) {
					window.setInterval( function () {
						if ( getCards().length > 1 ) {
							move( 1 );
						}
					}, speed );
				}

				slider.dataset.ewcbSliderInit = '1';
			}

			if ( dotsWrap ) {
				var cards = getCards();
				dotsWrap.innerHTML = '';
				dotsWrap.style.display = cards.length < 2 ? 'none' : '';
				cards.forEach( function ( card, index ) {
					var dot = document.createElement( 'button' );
					dot.type = 'button';
					dot.className = 'ewcb-slider-dots__dot' + ( 0 === index ? ' is-active' : '' );
					dot.setAttribute( 'aria-label', 'Go to slide ' + ( index + 1 ) );
					dot.dataset.slideIndex = index;
					dotsWrap.appendChild( dot );
				} );
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
				var requestId = ( Number( single.dataset.ewcbImageRequest ) || 0 ) + 1;
				single.dataset.ewcbImageRequest = requestId;
				mainImg.style.opacity = '0';
				var preload = new Image();
				preload.onload = function () {
					if ( Number( single.dataset.ewcbImageRequest ) !== requestId ) {
						return;
					}
					mainImg.removeAttribute( 'srcset' );
					mainImg.removeAttribute( 'sizes' );
					mainImg.src = full;
					window.setTimeout( function () {
						mainImg.style.opacity = '1';
					}, 50 );
				};
				preload.onerror = function () {
					if ( Number( single.dataset.ewcbImageRequest ) === requestId ) {
						mainImg.style.opacity = '1';
					}
				};
				preload.src = full;
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

			if ( dots.length > 1 ) {
				var mainWrap = single.querySelector( '.ewcb-single__main-wrap' );
				var touchStartX = null;
				if ( mainWrap ) {
					mainWrap.addEventListener( 'touchstart', function ( event ) {
						touchStartX = event.changedTouches[ 0 ].clientX;
					}, { passive: true } );
					mainWrap.addEventListener( 'touchend', function ( event ) {
						if ( null === touchStartX ) {
							return;
						}
						var distance = event.changedTouches[ 0 ].clientX - touchStartX;
						touchStartX = null;
						if ( Math.abs( distance ) < 40 ) {
							return;
						}
						var current = dots.findIndex( function ( dot ) {
							return dot.classList.contains( 'is-active' );
						} );
						var direction = distance < 0 ? 1 : -1;
						var next = ( current + direction + dots.length ) % dots.length;
						switchImage( dots[ next ], dots );
					}, { passive: true } );
				}
			}
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
			var widget = bar.closest( '.elementor-widget' );
			var trigger = triggerSelector && widget ? widget.querySelector( triggerSelector ) : null;
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
		function applyState() {
			scope.querySelectorAll( '.ewcb-wishlist' ).forEach( function ( btn ) {
				var active = wishlistItems.has( btn.dataset.productId );
				btn.classList.toggle( 'is-active', active );
				btn.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );
			scope.querySelectorAll( '.ewcb-compare' ).forEach( function ( btn ) {
				var active = compareItems.has( btn.dataset.productId );
				btn.classList.toggle( 'is-active', active );
				btn.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );
		}

		applyState();

		if ( iconToggleListenerBound ) {
			return;
		}
		iconToggleListenerBound = true;
		document.addEventListener( 'click', function ( e ) {
			var wishBtn = e.target.closest( '.ewcb-wishlist' );
			if ( wishBtn ) {
				var id = wishBtn.dataset.productId;
				wishlistItems.has( id ) ? wishlistItems.delete( id ) : wishlistItems.add( id );
				saveLocalSet( 'ewcb_wishlist', wishlistItems );
				initIconToggles( document );
				document.dispatchEvent( new CustomEvent( 'ewcb:wishlist-change', { detail: { productId: id, active: wishlistItems.has( id ) } } ) );
			}

			var compareBtn = e.target.closest( '.ewcb-compare' );
			if ( compareBtn ) {
				var cid = compareBtn.dataset.productId;
				compareItems.has( cid ) ? compareItems.delete( cid ) : compareItems.add( cid );
				saveLocalSet( 'ewcb_compare', compareItems );
				initIconToggles( document );
				document.dispatchEvent( new CustomEvent( 'ewcb:compare-change', { detail: { productId: cid, active: compareItems.has( cid ) } } ) );
			}
		} );
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
		if ( quickViewListenerBound ) {
			return;
		}
		quickViewListenerBound = true;
		document.addEventListener( 'click', function ( e ) {
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
