/**
 * Cal.com boekingspopup (element-click embed).
 *
 * Elke knop met data-cal-link opent bij klik de Cal.com-agenda in een popup;
 * de href blijft staan als fallback wanneer het script niet laadt. De loader
 * hieronder is Cal.com's officiele embed-snippet (alleen anders geformatteerd).
 * De init is data-gedreven zodat een pattern geen inline <script> hoeft te
 * renderen — dat zou de blokvalidatie in de editor breken.
 *
 * De merkkleur komt uit de theme.json-token --wp--preset--color--cyan, niet uit
 * een hardcoded hex, zodat de popup meekleurt als de token verandert.
 */
( function ( C, A, L ) {
	var p = function ( a, ar ) {
		a.q.push( ar );
	};
	var d = C.document;
	C.Cal = C.Cal || function () {
		var cal = C.Cal;
		var ar  = arguments;
		if ( ! cal.loaded ) {
			cal.ns = {};
			cal.q  = cal.q || [];
			d.head.appendChild( d.createElement( 'script' ) ).src = A;
			cal.loaded = true;
		}
		if ( ar[ 0 ] === L ) {
			var api = function () {
				p( api, arguments );
			};
			var namespace = ar[ 1 ];
			api.q = api.q || [];
			if ( typeof namespace === 'string' ) {
				cal.ns[ namespace ] = cal.ns[ namespace ] || api;
				p( cal.ns[ namespace ], ar );
				p( cal, [ 'initNamespace', namespace ] );
			} else {
				p( cal, ar );
			}
			return;
		}
		p( cal, ar );
	};
} )( window, 'https://app.cal.com/embed/embed.js', 'init' );

( function () {
	var triggers = document.querySelectorAll( '[data-cal-link][data-cal-namespace]' );
	if ( ! triggers.length ) {
		return;
	}

	var styles = getComputedStyle( document.documentElement );

	window.Cal.config = window.Cal.config || {};
	window.Cal.config.forwardQueryParams = true;

	// Per namespace een keer initialiseren; meerdere knoppen kunnen dezelfde
	// agenda openen. embed.js bindt zelf de klik-handler op [data-cal-link].
	var done = {};
	Array.prototype.forEach.call( triggers, function ( el ) {
		var ns = el.getAttribute( 'data-cal-namespace' );
		if ( ! ns || done[ ns ] ) {
			return;
		}
		done[ ns ] = true;

		// Accent per agenda, uit de theme.json-token die de knop meegeeft.
		var token = el.getAttribute( 'data-cal-brand' ) || 'cyan';
		var brand = styles.getPropertyValue( '--wp--preset--color--' + token ).trim() || '#0fb1bf';

		window.Cal( 'init', ns, { origin: 'https://app.cal.com' } );

		window.Cal.ns[ ns ]( 'ui', {
			cssVarsPerTheme: {
				light: { 'cal-brand': brand },
				dark: { 'cal-brand': brand },
			},
			hideEventTypeDetails: false,
			layout: 'month_view',
		} );
	} );

	// Zodra embed.js geladen is, neemt de popup het over en is de href alleen nog
	// ruis: de browser toont hem in de statusbalk en middenklik/"open in nieuw
	// tabblad" zou de bezoeker alsnog wegleiden. Pas hier weghalen — laadt het
	// script niet (adblocker, cookiebanner, offline), dan blijft de link werken.
	var loader = document.querySelector( 'script[src="https://app.cal.com/embed/embed.js"]' );
	if ( ! loader ) {
		return;
	}

	loader.addEventListener( 'load', function () {
		Array.prototype.forEach.call( triggers, function ( el ) {
			if ( ! el.hasAttribute( 'href' ) ) {
				return;
			}
			el.removeAttribute( 'href' );
			el.removeAttribute( 'target' );
			el.removeAttribute( 'rel' );

			// Een <a> zonder href valt uit de toetsenbordvolgorde; als knop
			// terugzetten zodat Tab + Enter/Spatie blijven werken.
			el.setAttribute( 'role', 'button' );
			el.setAttribute( 'tabindex', '0' );
			el.addEventListener( 'keydown', function ( event ) {
				if ( 'Enter' === event.key || ' ' === event.key ) {
					event.preventDefault();
					el.click();
				}
			} );
		} );
	} );
} )();
