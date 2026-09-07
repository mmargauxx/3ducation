/**
 * Cookiebanner + het vrijgeven van geblokkeerde tracking-scripts.
 *
 * functions.php print de <dialog id="cookie-consent"> (gesloten) en herschrijft
 * tracking-<script>s naar type="text/plain" data-3du-consent="<categorie>".
 * Dit script:
 *  - leest de keuze uit de cookie 3ducation_cookie_consent (6 maanden; een
 *    andere data-consent-id, dus een nieuwe revisie, maakt een oude keuze
 *    ongeldig en toont de banner opnieuw);
 *  - toont de banner (niet-modaal: de site blijft bruikbaar, Escape sluit
 *    niets zolang er geen keuze is) en verwerkt aanvaarden/weigeren/zelf kiezen;
 *  - zet Google Consent Mode om en maakt de geblokkeerde scripts van de
 *    toegestane categorieën weer echt, in documentvolgorde;
 *  - laat de keuze opnieuw maken via elke link/knop met data-cookie-settings
 *    of href="#cookie-instellingen"; wordt een categorie ingetrokken, dan
 *    verwijderen we de bekende tracking-cookies en herladen we de pagina,
 *    want een script dat al draait kun je niet meer stoppen;
 *  - biedt de WP Consent API-functies (wp_has_consent) aan voor plugins die
 *    daarop letten, zolang de echte API-plugin er niet is.
 */
( function () {
	var dialog = document.getElementById( 'cookie-consent' );
	if ( ! dialog || typeof dialog.show !== 'function' ) {
		return;
	}

	var COOKIE = '3ducation_cookie_consent';
	var MAX_AGE = 182 * 24 * 60 * 60; // 6 maanden.
	var consentId = dialog.getAttribute( 'data-consent-id' ) || '1';
	var categories = ( dialog.getAttribute( 'data-consent-cats' ) || '' ).split( ',' ).filter( Boolean );

	var layers = {
		intro: dialog.querySelector( '[data-consent-layer="intro"]' ),
		settings: dialog.querySelector( '[data-consent-layer="settings"]' )
	};
	var closeButton = dialog.querySelector( '[data-consent-action="close"]' );
	var switches = dialog.querySelectorAll( '.cookie-consent__switch:not([disabled])' );

	/* ---- opslag ---------------------------------------------------- */

	function readChoice() {
		var match = document.cookie.match( new RegExp( '(?:^|; )' + COOKIE + '=([^;]*)' ) );
		if ( ! match ) {
			return null;
		}
		try {
			var data = JSON.parse( decodeURIComponent( match[ 1 ] ) );
			if ( ! data || data.id !== consentId || typeof data.c !== 'object' ) {
				return null;
			}
			return data;
		} catch ( e ) {
			return null;
		}
	}

	function writeChoice( granted ) {
		var data = { v: 1, id: consentId, t: new Date().toISOString().slice( 0, 10 ), c: granted };
		var secure = 'https:' === window.location.protocol ? '; Secure' : '';
		document.cookie = COOKIE + '=' + encodeURIComponent( JSON.stringify( data ) ) + '; Max-Age=' + MAX_AGE + '; Path=/; SameSite=Lax' + secure;
	}

	function emptyChoice() {
		var granted = {};
		categories.forEach( function ( cat ) {
			granted[ cat ] = false;
		} );
		return granted;
	}

	function allChoice( value ) {
		var granted = {};
		categories.forEach( function ( cat ) {
			granted[ cat ] = value;
		} );
		return granted;
	}

	var stored = readChoice();
	var hasChoice = !! stored;
	var granted = hasChoice ? stored.c : emptyChoice();
	var activated = {};

	/* ---- consent doorgeven --------------------------------------- */

	function updateConsentMode( g ) {
		window.dataLayer = window.dataLayer || [];
		function gtag() {
			window.dataLayer.push( arguments );
		}
		gtag( 'consent', 'update', {
			analytics_storage: g.statistics ? 'granted' : 'denied',
			ad_storage: g.marketing ? 'granted' : 'denied',
			ad_user_data: g.marketing ? 'granted' : 'denied',
			ad_personalization: g.marketing ? 'granted' : 'denied'
		} );
	}

	function announce( g ) {
		try {
			document.dispatchEvent( new CustomEvent( '3ducation:consent', { detail: g } ) );
			categories.forEach( function ( cat ) {
				var detail = {};
				detail[ cat ] = g[ cat ] ? 'allow' : 'deny';
				document.dispatchEvent( new CustomEvent( 'wp_listen_for_consent_change', { detail: detail } ) );
			} );
		} catch ( e ) {}
	}

	/* ---- geblokkeerde scripts vrijgeven ------------------------- */

	/**
	 * Maakt van een geblokkeerd script een echt script. Zelfde attributen
	 * (id, async, defer, data-*), zonder type="text/plain".
	 */
	function rebuild( old ) {
		var fresh = document.createElement( 'script' );
		Array.prototype.forEach.call( old.attributes, function ( attr ) {
			if ( 'type' === attr.name || 'data-3du-consent' === attr.name || 'data-3du-type' === attr.name || 'data-3du-done' === attr.name ) {
				return;
			}
			fresh.setAttribute( attr.name, attr.value );
		} );
		if ( old.hasAttribute( 'data-3du-type' ) ) {
			fresh.type = old.getAttribute( 'data-3du-type' );
		}
		if ( old.getAttribute( 'src' ) ) {
			// Dynamisch ingevoegde scripts zijn standaard async; alleen echte
			// async-scripts mogen dat blijven, de rest houdt de documentvolgorde.
			fresh.async = old.hasAttribute( 'async' );
		} else {
			fresh.text = old.textContent;
		}
		return fresh;
	}

	/**
	 * Voert een lijst geblokkeerde scripts uit zoals de browser het origineel
	 * zou doen: inline meteen, een gewoon src-script wachten we af (een later
	 * inline script mag ervan afhangen), een async-script niet.
	 */
	function runQueue( list, done ) {
		if ( ! list.length ) {
			done();
			return;
		}
		var old = list.shift();
		var fresh = rebuild( old );
		var blocking = !! old.getAttribute( 'src' ) && ! old.hasAttribute( 'async' );
		var stepped = false;
		function next() {
			if ( stepped ) {
				return;
			}
			stepped = true;
			runQueue( list, done );
		}
		if ( blocking ) {
			fresh.addEventListener( 'load', next );
			fresh.addEventListener( 'error', next );
		}
		old.parentNode.insertBefore( fresh, old );
		if ( ! blocking ) {
			next();
		}
	}

	function activate( g ) {
		var nodes = Array.prototype.filter.call( document.querySelectorAll( 'script[data-3du-consent]' ), function ( old ) {
			return g[ old.getAttribute( 'data-3du-consent' ) ] && ! old.hasAttribute( 'data-3du-done' );
		} );
		if ( ! nodes.length ) {
			return;
		}
		nodes.forEach( function ( old ) {
			old.setAttribute( 'data-3du-done', '1' );
			activated[ old.getAttribute( 'data-3du-consent' ) ] = true;
		} );
		// defer- en module-scripts draaien in het origineel pas na de rest.
		var deferred = nodes.filter( function ( old ) {
			return old.getAttribute( 'src' ) && ( old.hasAttribute( 'defer' ) || 'module' === old.getAttribute( 'data-3du-type' ) );
		} );
		var normal = nodes.filter( function ( old ) {
			return -1 === deferred.indexOf( old );
		} );
		runQueue( normal, function () {
			runQueue( deferred, function () {} );
		} );
	}

	function clearTrackingCookies() {
		var names = document.cookie.split( ';' ).map( function ( part ) {
			return part.split( '=' )[ 0 ].trim();
		} );
		var pattern = /^(_ga|_gid|_gat|_gcl|_fbp|_fbc|_hj|_clck|_clsk|sbjs_|tk_|_pin_|_tt_|_uet)/;
		var host = window.location.hostname;
		var domains = [ '' ];
		var parts = host.split( '.' );
		while ( parts.length > 1 ) {
			domains.push( '.' + parts.join( '.' ) );
			parts.shift();
		}
		names.forEach( function ( name ) {
			if ( ! pattern.test( name ) ) {
				return;
			}
			domains.forEach( function ( domain ) {
				document.cookie = name + '=; Max-Age=0; Path=/' + ( domain ? '; Domain=' + domain : '' );
			} );
		} );
	}

	function apply( next ) {
		var revoked = categories.some( function ( cat ) {
			return activated[ cat ] && ! next[ cat ];
		} );
		granted = next;
		hasChoice = true;
		writeChoice( next );
		updateConsentMode( next );
		announce( next );
		closeDialog();
		if ( revoked ) {
			clearTrackingCookies();
			window.location.reload();
			return;
		}
		activate( next );
	}

	/* ---- banner ---------------------------------------------------- */

	function showLayer( name ) {
		layers.intro.hidden = 'intro' !== name;
		layers.settings.hidden = 'settings' !== name;
		closeButton.hidden = ! hasChoice;
		if ( 'settings' === name ) {
			Array.prototype.forEach.call( switches, function ( input ) {
				input.checked = !! granted[ input.name ];
			} );
		}
		var heading = layers[ name ].querySelector( '.cookie-consent__title' );
		if ( heading ) {
			heading.focus( { preventScroll: true } );
		}
	}

	function openDialog( layer ) {
		if ( ! dialog.open ) {
			dialog.show();
		}
		showLayer( layer );
	}

	function closeDialog() {
		if ( dialog.open ) {
			dialog.close();
		}
	}

	function readSwitches() {
		var next = emptyChoice();
		Array.prototype.forEach.call( switches, function ( input ) {
			if ( input.name in next ) {
				next[ input.name ] = input.checked;
			}
		} );
		return next;
	}

	dialog.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-consent-action]' );
		if ( ! button ) {
			return;
		}
		switch ( button.getAttribute( 'data-consent-action' ) ) {
			case 'accept':
				apply( allChoice( true ) );
				break;
			case 'reject':
				apply( allChoice( false ) );
				break;
			case 'save':
				apply( readSwitches() );
				break;
			case 'settings':
				showLayer( 'settings' );
				break;
			case 'close':
				if ( hasChoice ) {
					closeDialog();
				}
				break;
		}
	} );

	// Niet-modaal: Escape doet standaard niets. Alleen sluiten als er al een keuze is.
	dialog.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && hasChoice ) {
			closeDialog();
		}
	} );

	function openSettings( event ) {
		if ( event ) {
			event.preventDefault();
		}
		openDialog( hasChoice ? 'settings' : 'intro' );
	}

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '[data-cookie-settings], a[href$="#cookie-instellingen"]' );
		if ( trigger ) {
			openSettings( event );
		}
	} );

	/* ---- publieke API + WP Consent API-shim ----------------------- */

	window.threeducationCookies = {
		open: openSettings,
		has: function ( cat ) {
			return 'necessary' === cat || !! granted[ cat ];
		},
		consent: function () {
			return granted;
		}
	};

	if ( 'function' !== typeof window.wp_has_consent ) {
		window.wp_consent_type = 'optin';
		window.wp_has_consent = function ( cat ) {
			if ( 'functional' === cat || 'preferences' === cat ) {
				return true;
			}
			if ( 'statistics-anonymous' === cat ) {
				cat = 'statistics';
			}
			return !! granted[ cat ];
		};
		// Google Analytics for WooCommerce controleert naast wp_has_consent()
		// ook de cookie-helper van de WP Consent API. Ontbreekt die, dan
		// crasht zijn script en verstuurt het geen enkel e-commerce-event.
		// Zolang er geen keuze is, geeft de helper '' terug (= geen cookie),
		// daarna 'allow'/'deny' zoals de echte API.
		window.consent_api = window.consent_api || { cookie_prefix: 'wp_consent' };
		window.consent_api_get_cookie = function ( name ) {
			var prefix = window.consent_api.cookie_prefix + '_';
			var cat = String( name ).indexOf( prefix ) === 0 ? String( name ).slice( prefix.length ) : String( name );
			if ( ! hasChoice ) {
				return '';
			}
			return window.wp_has_consent( cat ) ? 'allow' : 'deny';
		};
	}

	/* ---- start ----------------------------------------------------- */

	if ( hasChoice ) {
		updateConsentMode( granted );
		activate( granted );
		// Scripts die na dit bestand in de footer staan, bestaan nu nog niet.
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', function () {
				activate( granted );
			} );
		}
	} else {
		openDialog( 'intro' );
	}
}() );
