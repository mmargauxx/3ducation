<?php
/**
 * Plugin Name: 3DUCATION versiepaden voor plugin- en core-bestanden
 * Description: Zet het versienummer van elk script en stylesheet van plugins en WordPress zelf in het pad in plaats van in ?ver=, zodat de Varnish-cache van EasyHost na een update nooit een oude kopie kan geven.
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Waarom: de cache van EasyHost bewaart statische bestanden 30 dagen en kijkt
 * alleen naar het pad, niet naar de querystring. Na een plugin-update krijgt
 * de browser daardoor een mix van oude en nieuwe scripts en blijft de
 * winkelwagen/checkout op het grijze skelet hangen (09-10-2026 na WooCommerce
 * 11.2.0, opnieuw 13-11-2026 na 11.3.0). Het thema lost dit voor zijn eigen
 * bestanden al op met /assets/v<versie>/…; dit bestand doet hetzelfde voor
 * alles onder wp-content/plugins, wp-includes en wp-admin:
 *
 *   /wp-content/plugins/woocommerce/…/wc-blocks-data.js?ver=797abc
 *   wordt
 *   /_v/797abc/wp-content/plugins/woocommerce/…/wc-blocks-data.js
 *
 * Een nieuwe versie is dus een nieuw pad, waar de cache nog niets van heeft.
 *
 * VEREIST een regel in de .htaccess in de hoofdmap van de site, BOVEN de
 * eerste "RewriteRule . /index.php" (eronder vangt WordPress het pad eerst op
 * en geeft een 404). Op live is dat boven "# BEGIN Webhosting WordPress", het
 * blok van EasyHost dat nog vóór "# BEGIN WordPress" staat; helemaal bovenaan
 * het bestand is altijd goed:
 *
 *   # BEGIN 3ducation-versiepaden
 *   <IfModule mod_rewrite.c>
 *   RewriteEngine On
 *   RewriteCond %{REQUEST_URI} !\.php
 *   RewriteRule ^_v/[A-Za-z0-9._-]+/((?:wp-content/plugins|wp-includes|wp-admin)/.+)$ /$1 [L]
 *   </IfModule>
 *   # END 3ducation-versiepaden
 *
 * Beveiliging tegen een kapotte site: zolang dat blok niet in de .htaccess
 * staat (of onder die WordPress-regel staat), herschrijft dit bestand niets en
 * toont het beheerders een melding. Uitzetten zonder het bestand te
 * verwijderen: define( 'THREEDUCATION_VERSIEPADEN_UIT', true ); in
 * wp-config.php.
 *
 * Wat het niet dekt: bestanden die een script zelf later bijlaadt vanaf een
 * vast plugin-pad (sommige WooCommerce-chunks) en afbeeldingen. Scripts die
 * hun eigen locatie uit hun URL afleiden laden hun chunks wél via het
 * versiepad; de regel in .htaccess laat daarom elk niet-PHP-bestand door.
 *
 * Thema-bestanden blijven ongemoeid: die hebben hun eigen versiepad.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden en het blok
 * hierboven in de .htaccess zetten. De volgorde maakt niet uit: zonder het
 * blok doet het bestand niets.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;

/**
 * Of de rewrite-regel in de .htaccess staat, boven het WordPress-blok.
 *
 * @return bool
 */
function threeducation_versiepaden_regel_aanwezig() {
	static $aanwezig = null;
	if ( null !== $aanwezig ) {
		return $aanwezig;
	}
	$aanwezig = false;
	$bestand  = ABSPATH . '.htaccess';
	if ( is_readable( $bestand ) ) {
		$inhoud = (string) file_get_contents( $bestand ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$eigen  = strpos( $inhoud, '# BEGIN 3ducation-versiepaden' );
		// De eerste regel die onbekende paden naar WordPress stuurt. Op live
		// staat die al in het blok "# BEGIN Webhosting WordPress" van EasyHost,
		// boven het gewone WordPress-blok.
		$wp     = preg_match( '/^\s*RewriteRule\s+\.\s+\/index\.php/m', $inhoud, $m, PREG_OFFSET_CAPTURE ) ? $m[0][1] : false;
		// Een '#' ervoor zetten schakelt de regel uit; dan ook hier uit.
		$regel    = (bool) preg_match( '/^\s*RewriteRule\s+\^_v\//m', $inhoud );
		$aanwezig = false !== $eigen && $regel && ( false === $wp || $eigen < $wp );
	}
	return $aanwezig;
}

/**
 * Of er herschreven mag worden.
 *
 * @return bool
 */
function threeducation_versiepaden_actief() {
	static $actief = null;
	if ( null === $actief ) {
		$actief = ! ( defined( 'THREEDUCATION_VERSIEPADEN_UIT' ) && THREEDUCATION_VERSIEPADEN_UIT )
			// Alleen voor een site in de hoofdmap van het domein, zoals live.
			&& '' === trim( (string) wp_parse_url( site_url(), PHP_URL_PATH ), '/' )
			&& threeducation_versiepaden_regel_aanwezig();
		$actief = (bool) apply_filters( 'threeducation_versiepaden_actief', $actief );
	}
	return $actief;
}

/**
 * Verplaats ?ver= naar het pad.
 *
 * @param string $src URL van het script of stylesheet.
 * @return string
 */
function threeducation_versiepaden_src( $src ) {
	if ( ! is_string( $src ) || '' === $src || ! threeducation_versiepaden_actief() ) {
		return $src;
	}

	$delen = wp_parse_url( $src );
	if ( empty( $delen['path'] ) || empty( $delen['query'] ) ) {
		return $src;
	}

	// Alleen de eigen site (of een URL zonder host).
	if ( ! empty( $delen['host'] ) && strtolower( $delen['host'] ) !== strtolower( (string) wp_parse_url( site_url(), PHP_URL_HOST ) ) ) {
		return $src;
	}

	if ( ! preg_match( '#^/(?:wp-content/plugins|wp-includes|wp-admin)/.+\.(?:js|mjs|css)$#', $delen['path'] ) ) {
		return $src;
	}

	wp_parse_str( $delen['query'], $args );
	if ( ! isset( $args['ver'] ) || ! is_scalar( $args['ver'] ) ) {
		return $src;
	}
	$versie = preg_replace( '/[^A-Za-z0-9._-]/', '', (string) $args['ver'] );
	if ( '' === $versie ) {
		return $src;
	}
	unset( $args['ver'] );

	$nieuw = '';
	if ( ! empty( $delen['host'] ) ) {
		$nieuw = ( isset( $delen['scheme'] ) ? $delen['scheme'] . ':' : '' ) . '//' . $delen['host'] . ( isset( $delen['port'] ) ? ':' . $delen['port'] : '' );
	}
	$nieuw .= '/_v/' . $versie . $delen['path'];
	if ( $args ) {
		$nieuw = add_query_arg( array_map( 'rawurlencode', $args ), $nieuw );
	}
	if ( isset( $delen['fragment'] ) ) {
		$nieuw .= '#' . $delen['fragment'];
	}
	return $nieuw;
}
// Laat: na plugins die zelf nog aan de URL sleutelen.
add_filter( 'script_loader_src', 'threeducation_versiepaden_src', 9999 );
add_filter( 'style_loader_src', 'threeducation_versiepaden_src', 9999 );
add_filter( 'script_module_loader_src', 'threeducation_versiepaden_src', 9999 );

/**
 * Ook de prefetch-hints (<link rel="prefetch">) die WooCommerce voor de
 * winkelwagen- en checkoutscripts afdrukt: die moeten naar dezelfde URL wijzen
 * als het script zelf, anders haalt de browser een bestand op dat hij daarna
 * niet gebruikt.
 *
 * @param array $urls Hints: een URL of een array met 'href'.
 * @return array
 */
function threeducation_versiepaden_hints( $urls ) {
	if ( ! is_array( $urls ) ) {
		return $urls;
	}
	foreach ( $urls as $i => $url ) {
		if ( is_string( $url ) ) {
			$urls[ $i ] = threeducation_versiepaden_src( html_entity_decode( $url, ENT_QUOTES ) );
		} elseif ( is_array( $url ) && isset( $url['href'] ) && is_string( $url['href'] ) ) {
			$urls[ $i ]['href'] = threeducation_versiepaden_src( html_entity_decode( $url['href'], ENT_QUOTES ) );
		}
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'threeducation_versiepaden_hints', 9999 );

/**
 * Melding voor beheerders zolang de regel in de .htaccess ontbreekt.
 */
function threeducation_versiepaden_melding() {
	if ( ! current_user_can( 'manage_options' ) || threeducation_versiepaden_regel_aanwezig() ) {
		return;
	}
	if ( defined( 'THREEDUCATION_VERSIEPADEN_UIT' ) && THREEDUCATION_VERSIEPADEN_UIT ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>3DUCATION versiepaden staat uit:</strong> het blok <code># BEGIN 3ducation-versiepaden</code> ontbreekt in de .htaccess of staat onder de eerste <code>RewriteRule . /index.php</code>. Na een plugin-update kan de checkout daardoor blijven hangen tot de Varnish-cache bij EasyHost is leeggemaakt. Het blok staat bovenaan in <code>mu-plugins/3ducation-versiepaden.php</code>.</p></div>';
}
add_action( 'admin_notices', 'threeducation_versiepaden_melding' );
