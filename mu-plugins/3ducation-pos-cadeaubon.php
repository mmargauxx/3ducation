<?php
/**
 * Plugin Name: 3DUCATION cadeaubon in de kassa
 * Description: Laat WooCommerce POS de bedrag-varianten van de PW-cadeaubon zien (verkoop) en zet een boncode die de kassier als coupon ingeeft om in een echte PW-boninwisseling (inwisselen).
 * Version: 1.1.0
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
 * DEEL 2 — INWISSELEN (v1.1.0, bestelling van 2026-09-03: bon van € 20 op een
 * kassabestelling van € 40 gaf "cadeaubon gebruikt € 0"). De kassa stuurt een
 * ingegeven boncode als coupon mee. PW Gift Cards antwoordt daarop met een
 * lege "virtuele coupon" (bedrag 0) en zou de bon daarna via de
 * winkelwagen-sessie verwerken — maar een kassabestelling heeft geen
 * winkelwagen, dus die stap komt nooit. Resultaat: couponregel op € 0, geen
 * bonregel, totaal onveranderd, saldo onaangeroerd.
 *
 * De oplossing hier: in kassa-context (REST én de betaalpagina van de kassa)
 *  1. accepteren we een boncode altijd als virtuele coupon, ook zonder cart;
 *  2. keuren we hem af met PW's eigen regels (inactief, vervallen, saldo 0,
 *     bon-met-bon) zodat de kassier een duidelijke melding krijgt;
 *  3. zetten we bij elke herberekening van de bestelling de couponregel om in
 *     een PW-bonregel (WC_Order_Item_PW_Gift_Card) met bedrag = min(saldo,
 *     resterend totaal). Vanaf dan doet PW de rest: totaal verlagen, saldo
 *     afboeken bij betaling/voltooiing, terugboeken bij annulering of
 *     terugbetaling. De couponregel blijft staan als zichtbare marker en
 *     krijgt het ingewisselde bedrag als "korting", zodat kassa en kassabon
 *     "-€ 20" tonen (de kassabon van de POS kent alleen coupons en fees).
 *
 * Het bedrag wordt bij elke herberekening opnieuw begrensd zolang er niets
 * afgeboekt is; na afboeking staat het vast. Een bon die de kassier weer
 * verwijdert, verdwijnt mee — tenzij hij al afgeboekt is.
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

/* =====================================================================
 * Deel 2 — bon inwisselen via de kassa
 * ===================================================================== */

/** Zoek de PW-bon bij een (coupon)code; null als er geen bon met dat nummer is. */
function threeducation_pos_find_gift_card( $code ) {
	if ( ! class_exists( 'PW_Gift_Card' ) || ! is_string( $code ) || '' === trim( $code ) ) {
		return null;
	}
	$card = new PW_Gift_Card( trim( $code ) );

	return $card->get_id() ? $card : null;
}

/** Bevat de bestelling het bonproduct zelf? (PW-regel "geen bon met een bon kopen".) */
function threeducation_pos_order_contains_gift_card_product( $order ): bool {
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( threeducation_pos_is_gift_card( $product ) || ( $product && $product->get_parent_id() && threeducation_pos_is_gift_card( wc_get_product( $product->get_parent_id() ) ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * PW's inwisselregels, als foutmelding voor de kassier. Lege string = in orde.
 * Spiegelt PW_Gift_Cards_Redeeming::add_gift_card_to_session(), dat alleen
 * met een winkelwagen werkt.
 */
function threeducation_pos_gift_card_error( PW_Gift_Card $card, $order = null ): string {
	if ( ! $card->get_active() ) {
		return __( 'Deze cadeaubon is niet actief.', '3ducation' );
	}
	if ( method_exists( $card, 'has_expired' ) && $card->has_expired() ) {
		return __( 'Deze cadeaubon is vervallen.', '3ducation' );
	}
	if ( (float) $card->get_balance() <= 0 ) {
		return __( 'Deze cadeaubon heeft geen saldo meer.', '3ducation' );
	}
	if ( 'yes' !== get_option( 'pwgc_allow_gift_card_purchasing', 'yes' )
		&& $order instanceof WC_Abstract_Order
		&& threeducation_pos_order_contains_gift_card_product( $order ) ) {
		return __( 'Een cadeaubon kan niet gebruikt worden om een andere cadeaubon te kopen.', '3ducation' );
	}
	if ( apply_filters( 'pwgc_gift_card_blocked', false, $card->get_number() ) ) {
		return __( 'Deze cadeaubon kan niet gebruikt worden.', '3ducation' );
	}
	$message = apply_filters( 'pwgc_gift_card_can_be_redeemed', '', $card->get_number() );

	return is_string( $message ) ? $message : '';
}

/** Onthoud per code de afkeurreden, zodat woocommerce_coupon_error hem kan tonen. */
function threeducation_pos_coupon_error( string $code, ?string $set = null ): string {
	static $errors = array();
	$key = wc_strtolower( $code );
	if ( null !== $set ) {
		$errors[ $key ] = $set;
	}

	return $errors[ $key ] ?? '';
}

/**
 * 1. Boncode als virtuele coupon accepteren, ook zonder winkelwagen.
 * Zelfde vorm als PW's eigen virtuele coupon (pw-gift-cards-redeeming.php),
 * alleen zonder de eis dat WC()->cart bestaat — in REST is die er niet.
 */
function threeducation_pos_gift_card_coupon_data( $data, $code ) {
	if ( false !== $data || ! threeducation_pos_is_pos_request() || ! threeducation_pos_find_gift_card( $code ) ) {
		return $data;
	}

	return array(
		'id'            => -1,
		'code'          => $code,
		'description'   => 'pw_gift_card',
		'amount'        => 0,
		'coupon_amount' => 0,
	);
}
add_filter( 'woocommerce_get_shop_coupon_data', 'threeducation_pos_gift_card_coupon_data', 5, 2 );

/** 2. Geldigheid volgens PW's regels; de reden gaat naar de kassier. */
function threeducation_pos_validate_gift_card_coupon( $valid, $coupon, $discounts = null ) {
	if ( ! $valid || ! $coupon instanceof WC_Coupon || ! threeducation_pos_is_pos_request() ) {
		return $valid;
	}
	$card = threeducation_pos_find_gift_card( $coupon->get_code() );
	if ( ! $card ) {
		return $valid;
	}
	$order = ( $discounts instanceof WC_Discounts ) ? $discounts->get_object() : null;
	$error = threeducation_pos_gift_card_error( $card, $order );
	if ( '' === $error ) {
		return true;
	}
	threeducation_pos_coupon_error( $coupon->get_code(), $error );

	return false;
}
add_filter( 'woocommerce_coupon_is_valid', 'threeducation_pos_validate_gift_card_coupon', 10, 3 );

function threeducation_pos_gift_card_coupon_message( $err, $err_code, $coupon ) {
	if ( WC_Coupon::E_WC_COUPON_INVALID_FILTERED !== (int) $err_code || ! $coupon instanceof WC_Coupon ) {
		return $err;
	}
	$stored = threeducation_pos_coupon_error( $coupon->get_code() );

	return '' !== $stored ? $stored : $err;
}
add_filter( 'woocommerce_coupon_error', 'threeducation_pos_gift_card_coupon_message', 10, 3 );

/**
 * 3. Couponregels met een boncode ↔ PW-bonregels gelijktrekken.
 *
 * Draait binnen calculate_totals() vóór PW's eigen hook (prioriteit 10), die
 * het totaal met de bonregels verlaagt. WooCommerce roept calculate_totals()
 * aan na elke apply_coupon()/remove_coupon() (via recalculate_coupons) en bij
 * elke kassa-update met regels, dus dit dekt de REST-lanen én de betaalpagina.
 */
function threeducation_pos_sync_gift_card_lines( WC_Order $order ): void {
	global $pw_gift_cards_redeeming;
	if ( ! class_exists( 'WC_Order_Item_PW_Gift_Card' ) || ! is_object( $pw_gift_cards_redeeming ) ) {
		return;
	}

	$existing = array(); // bonnummer => bestaande bonregel.
	foreach ( $order->get_items( 'pw_gift_card' ) as $item ) {
		$existing[ $item->get_card_number() ] = $item;
	}

	$coupons = array(); // bonnummer => array( bon, couponregel ).
	foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
		$card = threeducation_pos_find_gift_card( $coupon_item->get_code() );
		if ( $card && ! isset( $coupons[ $card->get_number() ] ) ) {
			$coupons[ $card->get_number() ] = array( $card, $coupon_item );
		}
	}
	if ( ! $existing && ! $coupons ) {
		return;
	}

	// Bon door de kassier verwijderd → bonregel mee weg, tenzij al afgeboekt.
	foreach ( $existing as $number => $item ) {
		if ( ! isset( $coupons[ $number ] ) && ! $item->meta_exists( '_pw_gift_card_debited' ) ) {
			$order->remove_item( $item->get_id() );
			unset( $existing[ $number ] );
		}
	}

	$available = (float) $pw_gift_cards_redeeming->calculate_order_total( $order ); // totaal vóór bonnen.
	$decimals  = wc_get_price_decimals();
	foreach ( $coupons as $number => list( $card, $coupon_item ) ) {
		$item = $existing[ $number ] ?? null;
		if ( $item && $item->meta_exists( '_pw_gift_card_debited' ) ) {
			$amount = (float) $item->get_amount(); // Al afgeboekt: bedrag staat vast.
		} else {
			$amount = round( max( 0, min( $available, (float) $card->get_balance() ) ), $decimals );
			if ( ! $item ) {
				$item = new WC_Order_Item_PW_Gift_Card();
				$item->set_props( array( 'card_number' => $number, 'amount' => $amount ) );
				$order->add_item( $item );
			} else {
				$item->set_amount( $amount );
			}
		}
		$available = max( 0, $available - $amount );

		// De couponregel is wat kassa en kassabon tonen: geef hem het ingewisselde bedrag.
		// Dit is louter weergave; zie threeducation_pos_keep_gift_card_coupon_empty().
		$coupon_item->set_discount( $amount );
		$coupon_item->set_discount_tax( 0 );
	}
}

/**
 * Bewaking: een bon-coupon mag nooit een échte korting worden.
 *
 * Als WooCommerce de coupons van een bestelling opnieuw toepast
 * (recalculate_coupons: tweede coupon, kassa-update met andere codes, coupon
 * toevoegen in wp-admin), bouwt het een virtuele coupon zonder bedrag opnieuw
 * op uit de korting die op de couponregel staat — en dat is het weergavebedrag
 * van hierboven. Zonder deze filter zou de bon dan dubbel tellen: als korting
 * op de regels én als bonregel. Geldt in elke context, want de bestelling
 * blijft bestaan na de kassa.
 */
function threeducation_pos_keep_gift_card_coupon_empty( $coupon, $code ) {
	if ( $coupon instanceof WC_Coupon && threeducation_pos_find_gift_card( $code ) ) {
		$coupon->set_amount( 0 );
		$coupon->set_free_shipping( false );
	}

	return $coupon;
}
add_filter( 'woocommerce_order_recalculate_coupons_coupon_object', 'threeducation_pos_keep_gift_card_coupon_empty', 10, 2 );

function threeducation_pos_sync_gift_card_lines_on_totals( $and_taxes, $order ) {
	if ( $order instanceof WC_Order && threeducation_pos_is_pos_request() ) {
		threeducation_pos_sync_gift_card_lines( $order );
	}
}
add_action( 'woocommerce_order_after_calculate_totals', 'threeducation_pos_sync_gift_card_lines_on_totals', 5, 2 );
