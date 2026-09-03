<?php
/**
 * Plugin Name: 3DUCATION cadeaubon in de kassa
 * Description: Laat WooCommerce POS de bedrag-varianten van de PW-cadeaubon zien, zodat een kassaverkoop een variation_id meestuurt en PW Gift Cards de bon aanmaakt én mailt.
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Het probleem (bestelling #14129, 2026-09-02): PW Gift Cards maakt een bon
 * alleen aan als de orderregel een variation_id heeft — het bedrag zit in de
 * variatie (€ 25, € 50, …). WooCommerce POS toont die variaties niet, want de
 * kassa kijkt naar de letterlijke tekst `type: "variable"` in de REST-payload
 * en het bonproduct meldt zich als `pw-gift-card`. De kassier zag dus enkel
 * het hoofdproduct op € 0, tikte zelf een prijs in, en de bon werd nooit
 * aangemaakt (en dus ook nooit gemaild) terwijl de bestelling wél op
 * "Voltooid" kwam.
 *
 * De oplossing: voor kassaverzoeken melden we het bonproduct als `variable`.
 * De kassa haalt dan de variaties op, de kassier kiest het bedrag, en de
 * bestelling bevat een variation_id — precies wat PW nodig heeft. Webshop en
 * beheer zien nog steeds `pw-gift-card`; alleen de POS krijgt de andere waarde.
 *
 * Bijkomende bewaking: de kassa stuurt bij een productwijziging zijn volledige
 * opgeslagen payload terug, dus óók `type: "variable"`. Zonder ingrijpen zou
 * WooCommerce het bonproduct dan omzetten naar een gewoon variabel product en
 * zouden alle cadeaubonnen breken. Daarom zetten we het type op zo'n verzoek
 * terug naar `pw-gift-card` vóór WooCommerce het verwerkt.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Geen
 * activatiestap. Zonder WooCommerce POS of PW Gift Cards doet het bestand niets.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;

/** Is dit verzoek afkomstig van de kassa (WooCommerce POS)? */
function threeducation_pos_is_pos_request(): bool {
	if ( function_exists( 'wcpos_request' ) && wcpos_request() ) {
		return true;
	}

	// De "changes"/"resolve"-lanes van de POS-sync serialiseren buiten een
	// gewone dispatch om; daar is deze marker het enige signaal.
	if ( class_exists( '\WCPOS\WooCommercePOS\Sync\Store_Scope' )
		&& method_exists( '\WCPOS\WooCommercePOS\Sync\Store_Scope', 'is_v2_lane' )
		&& \WCPOS\WooCommercePOS\Sync\Store_Scope::is_v2_lane() ) {
		return true;
	}

	return false;
}

/** Is dit product de PW-cadeaubon? */
function threeducation_pos_is_gift_card( $product ): bool {
	return $product instanceof WC_Product && 'pw-gift-card' === $product->get_type();
}

/**
 * Meld het bonproduct als `variable` aan de kassa.
 *
 * Beide leeslanen van WooCommerce POS (de wc/v3-proxy én de per-product
 * serializer) lopen door WooCommerce's eigen REST-controller, dus deze ene
 * filter bereikt de kassa overal. De `variations`-lijst staat al goed:
 * WooCommerce vult die op basis van is_type('variable'), en PW's productklasse
 * antwoordt daar ja op. Alleen de tekst `type` week af.
 */
function threeducation_pos_mask_gift_card_type( $response, $product, $request ) {
	if ( ! threeducation_pos_is_gift_card( $product ) || ! threeducation_pos_is_pos_request() ) {
		return $response;
	}

	$data = $response->get_data();
	if ( is_array( $data ) && isset( $data['type'] ) ) {
		$data['type'] = 'variable';
		$response->set_data( $data );
	}

	return $response;
}
add_filter( 'woocommerce_rest_prepare_product_object', 'threeducation_pos_mask_gift_card_type', 20, 3 );

/**
 * Bewaking: een kassa-schrijfactie mag het producttype niet omzetten.
 *
 * Draait vóór de WooCommerce-callback. Komt er voor de bon een
 * `type: "variable"` binnen, dan zetten we dat terug naar `pw-gift-card`, wat
 * WooCommerce gewoon als bestaand producttype kent (product_type_selector).
 */
function threeducation_pos_guard_gift_card_type( $response, $handler, $request ) {
	if ( ! $request instanceof WP_REST_Request ) {
		return $response;
	}
	if ( ! in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
		return $response;
	}
	if ( 'variable' !== $request->get_param( 'type' ) ) {
		return $response;
	}
	if ( ! preg_match( '#/products/(\d+)/?$#', $request->get_route(), $m ) ) {
		return $response;
	}
	if ( ! threeducation_pos_is_pos_request() || ! class_exists( 'WC_Product_Factory' ) ) {
		return $response;
	}
	if ( 'pw-gift-card' === WC_Product_Factory::get_product_type( (int) $m[1] ) ) {
		$request->set_param( 'type', 'pw-gift-card' );
	}

	return $response;
}
add_filter( 'rest_request_before_callbacks', 'threeducation_pos_guard_gift_card_type', 10, 3 );
