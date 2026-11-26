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
	var collectionListenerBound = false;
	var MAX_COMPARE_ITEMS = 4;
	var MAX_WISHLIST_ITEMS = 50;
	if ( wishlistItems.size > MAX_WISHLIST_ITEMS ) {
		wishlistItems = new Set( Array.from( wishlistItems ).slice( 0, MAX_WISHLIST_ITEMS ) );
		saveLocalSet( 'ewcb_wishlist', wishlistItems );
	}

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

	function updateCollectionCounts( scope ) {
		scope = scope || document;
		scope.querySelectorAll( '.ewcb-collection-count' ).forEach( function ( count ) {
			var type = count.getAttribute( 'data-count-type' );
			var set = 'compare' === type ? compareItems : wishlistItems;
			count.textContent = set.size;
		} );
	}

	function showCollectionNotice( message ) {
		var notice = document.querySelector( '.ewcb-collection-notice' );
		if ( ! notice ) {
			notice = document.createElement( 'div' );
			notice.className = 'ewcb-collection-notice';
			notice.setAttribute( 'role', 'status' );
			Object.assign( notice.style, {
				position: 'fixed', bottom: '24px', left: '50%', transform: 'translateX(-50%)',
				zIndex: '10005', background: '#0d111a', color: '#fff', padding: '10px 16px',
				borderRadius: '999px', fontSize: '13px', boxShadow: '0 8px 24px rgba(0,0,0,.2)'
			} );
			document.body.appendChild( notice );
		}
		notice.textContent = message;
		notice.hidden = false;
		window.clearTimeout( notice._ewcbTimer );
		notice._ewcbTimer = window.setTimeout( function () { notice.hidden = true; }, 2400 );
	}

	function ensureCollectionOverlay() {
		var overlay = document.querySelector( '.ewcb-collection-overlay' );
		if ( overlay ) {
			return overlay;
		}
		overlay = document.createElement( 'div' );
		overlay.className = 'ewcb-collection-overlay';
		overlay.innerHTML = '<section class="ewcb-collection-modal" role="dialog" aria-modal="true" aria-labelledby="ewcb-collection-title"><header class="ewcb-collection-modal__header"><h2 id="ewcb-collection-title">Saved products</h2><button type="button" class="ewcb-collection-close" aria-label="Close">×</button></header><div class="ewcb-collection-modal__body"></div></section>';
		document.body.appendChild( overlay );
		overlay.addEventListener( 'click', function ( event ) {
			if ( event.target === overlay || event.target.closest( '.ewcb-collection-close' ) ) {
				overlay.classList.remove( 'is-open' );
			}
		} );
		overlay.querySelector( '.ewcb-collection-modal__body' ).addEventListener( 'click', function ( event ) {
			var removeButton = event.target.closest( '[data-remove-id]' );
			if ( ! removeButton ) {
				return;
			}
			var id = String( removeButton.getAttribute( 'data-remove-id' ) );
			var type = removeButton.getAttribute( 'data-remove-type' );
			var targetSet = 'compare' === type ? compareItems : wishlistItems;
			targetSet.delete( id );
			saveLocalSet( 'compare' === type ? 'ewcb_compare' : 'ewcb_wishlist', targetSet );
			initIconToggles( document );
			loadCollection( type );
		} );
		return overlay;
	}

	function appendSafeText( parent, tag, className, value ) {
		var node = document.createElement( tag );
		if ( className ) { node.className = className; }
		node.textContent = value || '';
		parent.appendChild( node );
		return node;
	}

	function renderWishlist( body, items ) {
		if ( ! items.length ) {
			appendSafeText( body, 'div', 'ewcb-collection-empty', 'Your wishlist is empty. Use the heart icon on a product to save it here.' );
			return;
		}
		var list = document.createElement( 'div' );
		list.className = 'ewcb-collection-list';
		items.forEach( function ( item ) {
			var card = document.createElement( 'article' );
			card.className = 'ewcb-collection-item';
			var image = document.createElement( 'div' );
			image.innerHTML = item.image_html || '';
			card.appendChild( image );
			var title = document.createElement( 'a' );
			title.className = 'ewcb-collection-item__title';
			title.href = item.url;
			title.textContent = item.name;
			card.appendChild( title );
			var price = document.createElement( 'div' );
			price.className = 'ewcb-collection-item__price';
			price.innerHTML = item.price_html || '';
			card.appendChild( price );
			appendSafeText( card, 'div', '', item.availability );
			var remove = appendSafeText( card, 'button', 'ewcb-collection-item__remove', 'Remove from wishlist' );
			remove.type = 'button'; remove.setAttribute( 'data-remove-id', item.id ); remove.setAttribute( 'data-remove-type', 'wishlist' );
			list.appendChild( card );
		} );
		body.appendChild( list );
	}

	function renderCompare( body, items ) {
		if ( ! items.length ) {
			appendSafeText( body, 'div', 'ewcb-collection-empty', 'No products selected for comparison. Use the compare icon on up to four products.' );
			return;
		}
		var wrap = document.createElement( 'div' );
		wrap.className = 'ewcb-compare-table-wrap';
		var table = document.createElement( 'table' );
		table.className = 'ewcb-compare-table';
		var thead = document.createElement( 'thead' );
		var headRow = document.createElement( 'tr' );
		appendSafeText( headRow, 'th', '', 'Product' );
		items.forEach( function ( item ) {
			var cell = document.createElement( 'th' );
			var link = document.createElement( 'a' ); link.href = item.url; link.textContent = item.name;
			cell.appendChild( link );
			headRow.appendChild( cell );
		} );
		thead.appendChild( headRow ); table.appendChild( thead );
		var tbody = document.createElement( 'tbody' );
		var rows = [
			{ label: 'Image', key: 'image_html', html: true },
			{ label: 'Price', key: 'price_html', html: true },
			{ label: 'Category', key: 'categories' },
			{ label: 'Availability', key: 'availability' },
			{ label: 'SKU', key: 'sku' },
			{ label: 'Description', key: 'description' }
		];
		rows.forEach( function ( row ) {
			var tr = document.createElement( 'tr' ); appendSafeText( tr, 'th', '', row.label );
			items.forEach( function ( item ) {
				var td = document.createElement( 'td' );
				if ( row.html ) { td.innerHTML = item[ row.key ] || ''; } else { td.textContent = item[ row.key ] || '—'; }
				tr.appendChild( td );
			} );
			tbody.appendChild( tr );
		} );
		var removeRow = document.createElement( 'tr' ); appendSafeText( removeRow, 'th', '', 'Actions' );
		items.forEach( function ( item ) {
			var td = document.createElement( 'td' );
			var remove = appendSafeText( td, 'button', 'ewcb-collection-item__remove', 'Remove' );
			remove.type = 'button'; remove.setAttribute( 'data-remove-id', item.id ); remove.setAttribute( 'data-remove-type', 'compare' );
			removeRow.appendChild( td );
		} );
		tbody.appendChild( removeRow ); table.appendChild( tbody ); wrap.appendChild( table ); body.appendChild( wrap );
	}

	function loadCollection( type ) {
		if ( ! window.EWCB_AJAX ) {
			showCollectionNotice( 'Saved products are currently unavailable.' );
			return;
		}
		var set = 'compare' === type ? compareItems : wishlistItems;
		var overlay = ensureCollectionOverlay();
		var title = overlay.querySelector( '#ewcb-collection-title' );
		var body = overlay.querySelector( '.ewcb-collection-modal__body' );
		title.textContent = 'compare' === type ? 'Compare products' : 'Your wishlist';
		body.innerHTML = '<div class="ewcb-collection-empty">Loading saved products…</div>';
		overlay.classList.add( 'is-open' );
		var formData = new FormData();
		formData.append( 'action', 'ewcb_get_collections' );
		formData.append( 'nonce', window.EWCB_AJAX.nonce );
		formData.append( 'type', type );
		formData.append( 'product_ids', JSON.stringify( Array.from( set ) ) );
		fetch( window.EWCB_AJAX.ajax_url, { method: 'POST', body: formData, credentials: 'same-origin' } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( json ) {
				body.innerHTML = '';
				if ( ! json.success ) {
					appendSafeText( body, 'div', 'ewcb-collection-empty', json.data && json.data.message ? json.data.message : 'Could not load saved products.' );
					return;
				}
				if ( 'compare' === type ) { renderCompare( body, json.data.items || [] ); } else { renderWishlist( body, json.data.items || [] ); }
			} )
			.catch( function () {
				body.innerHTML = '';
				appendSafeText( body, 'div', 'ewcb-collection-empty', 'Could not load saved products. Please try again.' );
			} );
	}

	function initIconToggles( scope ) {
		scope = scope || document;
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
		updateCollectionCounts( scope );

		if ( collectionListenerBound ) {
			return;
		}
		collectionListenerBound = true;
		document.addEventListener( 'click', function ( event ) {
			var target = event.target;
			if ( ! target || ! target.closest ) { return; }
			var wishBtn = target.closest( '.ewcb-wishlist' );
			if ( wishBtn ) {
				var id = String( wishBtn.dataset.productId );
				if ( ! wishlistItems.has( id ) && wishlistItems.size >= MAX_WISHLIST_ITEMS ) {
					showCollectionNotice( 'You can save up to 50 products in your wishlist.' );
					return;
				}
				wishlistItems.has( id ) ? wishlistItems.delete( id ) : wishlistItems.add( id );
				saveLocalSet( 'ewcb_wishlist', wishlistItems );
				initIconToggles( document );
				document.dispatchEvent( new CustomEvent( 'ewcb:wishlist-change', { detail: { productId: id, active: wishlistItems.has( id ) } } ) );
			}

			var compareBtn = target.closest( '.ewcb-compare' );
			if ( compareBtn ) {
				var compareId = String( compareBtn.dataset.productId );
				if ( ! compareItems.has( compareId ) && compareItems.size >= MAX_COMPARE_ITEMS ) {
					showCollectionNotice( 'You can compare up to four products at a time.' );
					return;
				}
				compareItems.has( compareId ) ? compareItems.delete( compareId ) : compareItems.add( compareId );
				saveLocalSet( 'ewcb_compare', compareItems );
				initIconToggles( document );
				document.dispatchEvent( new CustomEvent( 'ewcb:compare-change', { detail: { productId: compareId, active: compareItems.has( compareId ) } } ) );
			}

			var openWishlist = target.closest( '.ewcb-open-wishlist' );
			var openCompare = target.closest( '.ewcb-open-compare' );
			if ( openWishlist ) { loadCollection( 'wishlist' ); }
			if ( openCompare ) { loadCollection( 'compare' ); }
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
						// The variable form is inserted after document ready; initialise WooCommerce's variation handler explicitly.
						if ( window.jQuery && window.jQuery.fn && window.jQuery.fn.wc_variation_form ) {
							window.jQuery( body ).find( '.variations_form' ).each( function () {
								window.jQuery( this ).wc_variation_form();
							} );
						}
						if ( window.jQuery ) {
							window.jQuery( document.body ).trigger( 'wc_fragment_refresh' );
						}
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
