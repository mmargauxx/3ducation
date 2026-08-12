<?php
/**
 * Plugin Name: 3DUCATION legacy redirects
 * Description: 301-redirects voor de oude Duda-webshop-URLs (/webshop/...-p<ID> en -c<ID>).
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Waarom een mu-plugin en niet het thema?
 * Redirects zijn site-infrastructuur, geen vormgeving: ze moeten blijven werken
 * als het thema wordt gewisseld, vervangen of tijdelijk uitvalt. Daarom staat
 * deze code in wp-content/mu-plugins/ en niet in functions.php. Mu-plugins laden
 * altijd en kunnen niet per ongeluk gedeactiveerd worden.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Bestaat die map
 * nog niet, maak hem dan aan. Er is geen activatiestap.
 *
 * LET OP: verwijder deze code eerst uit het thema (functions.php) of upload eerst
 * de themaversie waarin ze weg is. Staan beide er tegelijk, dan geeft PHP een
 * fatal error "cannot redeclare threeducation_legacy_key()" — mu-plugins laden
 * vóór het thema.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;


/** Normaliseert een slug tot enkel letters en cijfers. */
function threeducation_legacy_key( $slug ) {
	return preg_replace( '/[^a-z0-9]/', '', strtolower( remove_accents( (string) $slug ) ) );
}

/**
 * Bouwt (en cachet) de map van genormaliseerde slug naar product-ID.
 *
 * @return array<string,int>
 */
function threeducation_legacy_product_map() {
	$map = get_transient( 'threeducation_legacy_product_map' );
	if ( is_array( $map ) ) {
		return $map;
	}

	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT ID, post_name FROM {$wpdb->posts}
		 WHERE post_type = 'product' AND post_status = 'publish'"
	);

	$map = array();
	foreach ( $rows as $row ) {
		$key = threeducation_legacy_key( $row->post_name );
		// Eerste treffer wint: bij een dubbele sleutel is de oudste post het origineel.
		if ( '' !== $key && ! isset( $map[ $key ] ) ) {
			$map[ $key ] = (int) $row->ID;
		}
	}

	set_transient( 'threeducation_legacy_product_map', $map, 12 * HOUR_IN_SECONDS );
	return $map;
}

/** Gooit de cache weg zodra er een product bijkomt of verandert. */
function threeducation_legacy_flush_map( $post_id ) {
	if ( 'product' === get_post_type( $post_id ) ) {
		delete_transient( 'threeducation_legacy_product_map' );
	}
}
add_action( 'save_post', 'threeducation_legacy_flush_map' );
add_action( 'trashed_post', 'threeducation_legacy_flush_map' );

/** Vangt de oude /webshop/-URLs op en stuurt ze door met een 301. */
function threeducation_legacy_redirect() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path    = trim( (string) wp_parse_url( $request, PHP_URL_PATH ), '/' );

	if ( 0 !== strpos( $path, 'webshop/' ) ) {
		return;
	}

	$rest = substr( $path, strlen( 'webshop/' ) );

	// Oude productpagina: <naam>-p<ID>.
	if ( preg_match( '#^(.+)-p\d+$#', $rest, $matches ) ) {
		$map = threeducation_legacy_product_map();
		$key = threeducation_legacy_key( $matches[1] );

		if ( isset( $map[ $key ] ) ) {
			wp_safe_redirect( get_permalink( $map[ $key ] ), 301 );
			exit;
		}

		// Product bestaat niet meer: toon wat er wél is.
		wp_safe_redirect(
			add_query_arg(
				array(
					's'         => rawurlencode( str_replace( '-', ' ', $matches[1] ) ),
					'post_type' => 'product',
				),
				home_url( '/' )
			),
			301
		);
		exit;
	}

	// Oude categoriepagina: <naam>-c<ID>. We sturen door naar het echte
	// categoriearchief, want dat draagt de markdown-beschrijving, de thumbnail
	// en de uitgelichte producten van die categorie — een gefilterde shop-URL
	// heeft dat allemaal niet. `get_term_link()` bouwt de URL met de basis die
	// op de site is ingesteld (op deze site het Nederlandse `product-categorie`),
	// dus die basis mag hier nooit hardcoded staan.
	if ( preg_match( '#^(.+)-c\d+$#', $rest, $matches ) ) {
		$term = get_term_by( 'slug', sanitize_title( $matches[1] ), 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				wp_safe_redirect( $link, 301 );
				exit;
			}
		}
	}

	// Alles wat overblijft onder /webshop/ gaat naar de webshop zelf.
	if ( function_exists( 'wc_get_page_id' ) ) {
		$shop_id = wc_get_page_id( 'shop' );
		if ( $shop_id > 0 ) {
			wp_safe_redirect( get_permalink( $shop_id ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'threeducation_legacy_redirect', 1 );
