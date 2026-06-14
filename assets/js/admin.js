/**
 * Marks — admin settings enhancements (progressive, dependency-free).
 *
 * 1. Inline help: each "?" button is wired to an accessible popover. Where the
 *    native Popover API exists it is used; otherwise a small show/hide fallback
 *    keeps it keyboard- and screen-reader-operable via aria-expanded.
 * 2. Live preview: reflects the merchant's label, shape, case and colour choices
 *    in real time so they can see the result before saving. Updates are
 *    debounced and use event delegation.
 *
 * The script is loaded with `defer` and degrades gracefully: with JS disabled,
 * all settings still save and the description text remains visible.
 */
( function () {
	'use strict';

	var root = document.querySelector( '.marks-admin' );

	if ( ! root ) {
		return;
	}

	var supportsPopover =
		typeof HTMLElement !== 'undefined' &&
		HTMLElement.prototype.hasOwnProperty( 'popover' );

	/* ---- Inline help popovers ---------------------------------------- */

	function closeAllFallback( except ) {
		root.querySelectorAll( '.marks-help[aria-expanded="true"]' ).forEach(
			function ( btn ) {
				if ( btn === except ) {
					return;
				}
				btn.setAttribute( 'aria-expanded', 'false' );
				var tip = document.getElementById(
					btn.getAttribute( 'aria-describedby' )
				);
				if ( tip ) {
					tip.hidden = true;
				}
			}
		);
	}

	root.addEventListener( 'click', function ( event ) {
		var btn = event.target.closest( '.marks-help' );

		if ( ! btn || supportsPopover ) {
			return;
		}

		// Native Popover handles its own toggling; this is the fallback only.
		var tip = document.getElementById( btn.getAttribute( 'aria-describedby' ) );

		if ( ! tip ) {
			return;
		}

		var open = btn.getAttribute( 'aria-expanded' ) === 'true';
		closeAllFallback( btn );
		btn.setAttribute( 'aria-expanded', String( ! open ) );
		tip.hidden = open;
	} );

	if ( ! supportsPopover ) {
		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				closeAllFallback( null );
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! event.target.closest( '.marks-help, .marks-tip' ) ) {
				closeAllFallback( null );
			}
		} );
	}

	/* ---- Live preview ------------------------------------------------ */

	var preview = root.querySelector( '.marks-preview__badges' );

	if ( ! preview ) {
		return;
	}

	var field = function ( name ) {
		return root.querySelector( '[name="marks_settings[' + name + ']"]' );
	};

	function labelFor( key, fallback ) {
		var input = field( key + '_badge_text' );
		var custom = input && input.value.trim();
		return custom || fallback;
	}

	function isOn( key ) {
		var input = field( 'show_' + key + '_badge' );
		return !! ( input && input.checked );
	}

	var rules = [
		{ key: 'sale', style: 'danger', fallback: 'Sale' },
		{ key: 'new', style: 'success', fallback: 'New' },
		{ key: 'low_stock', style: 'warning', fallback: 'Low stock' },
		{ key: 'bestseller', style: 'accent', fallback: 'Bestseller' },
		{ key: 'free_shipping', style: 'neutral', fallback: 'Free shipping' },
		{ key: 'out_of_stock', style: 'neutral', fallback: 'Out of stock' },
	];

	function render() {
		var shape = field( 'shape' );
		var upper = field( 'uppercase' );
		var manualText = field( 'manual_badge_text' );
		var manualStyle = field( 'manual_badge_style' );

		preview.parentElement.classList.toggle(
			'marks-preview--square',
			shape && shape.value === 'square'
		);
		preview.parentElement.classList.toggle(
			'marks-preview--upper',
			!! ( upper && upper.checked )
		);

		var html = '';

		rules.forEach( function ( rule ) {
			if ( isOn( rule.key ) ) {
				html += badge( labelFor( rule.key, rule.fallback ), rule.style );
			}
		} );

		var discount = field( 'show_discount_percent_badge' );
		if ( discount && discount.checked ) {
			html += badge( '-20%', 'danger' );
		}

		if ( manualText && manualText.value.trim() ) {
			html += badge(
				manualText.value.trim(),
				manualStyle ? manualStyle.value : 'accent'
			);
		}

		var emptyEl = root.querySelector( '.marks-preview__empty' );

		if ( html === '' ) {
			preview.innerHTML = '';
			if ( emptyEl ) {
				emptyEl.hidden = false;
			}
			return;
		}

		if ( emptyEl ) {
			emptyEl.hidden = true;
		}
		preview.innerHTML = html;
	}

	function badge( text, style ) {
		var span = document.createElement( 'span' );
		span.className =
			'marks-preview__badge marks-preview__badge--' + style;
		span.textContent = text;
		return span.outerHTML;
	}

	var debounce;
	function schedule() {
		window.clearTimeout( debounce );
		debounce = window.setTimeout( render, 120 );
	}

	root.addEventListener( 'input', schedule );
	root.addEventListener( 'change', schedule );

	render();
} )();
