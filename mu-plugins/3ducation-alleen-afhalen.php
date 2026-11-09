<?php
/**
 * Plugin Name: 3DUCATION alleen afhalen bij montage-opties
 * Description: Kiest een klant bij een printer de PPOM-optie "vooraf gemonteerd … afgehaald" of "… workshop bij afhaling", dan blijft bij het afrekenen alleen afhalen over.
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * De opties staan als PPOM-velden op de printers (+ de Falcon2-laser). PPOM
 * bewaart de gekozen tekst in de winkelwagen in `$cart_item['ppom']['fields']`.
 * Herkenning gebeurt op de tekst van de keuze: die bevat "gemonteerd" én
 * "afgehaald" of "afhaling". Optie 1 (zelfbouwpakket) valt daar buiten, en
 * bedragen als "(+€50,00)" in de tekst doen er niet toe. Wie de optietekst in
 * PPOM herschrijft, houdt die twee woorden erin of past de filter
 * `threeducation_alleen_afhalen_optie` aan.
 *
 * Eén zo'n product in de winkelwagen maakt de hele bestelling afhalen: er is
 * één verzendpakket per bestelling.
 *
 * Staat afhalen om een of andere reden niet tussen de verzendopties, dan blijft
 * alles ongemoeid: de klant moet kunnen afrekenen.
 *
 * Geldt alleen voor de webshop-winkelwagen. De kassa (WCPOS) en handmatige
 * bestellingen gebruiken geen verzendopties uit de winkelwagen.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Geen
 * activatiestap. Zonder WooCommerce of PPOM doet het bestand niets.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;

/**
 * Of deze winkelwagenregel een optie heeft die alleen afgehaald kan worden.
 * Velden die PPOM conditioneel verborg tellen niet mee, net als in PPOM zelf.
 *
 * @param array $cart_item Winkelwagenregel.
 * @return bool
 */
function threeducation_alleen_afhalen_regel( $cart_item ) {
	if ( empty( $cart_item['ppom']['fields'] ) || ! is_array( $cart_item['ppom']['fields'] ) ) {
		return false;
	}

	$velden    = $cart_item['ppom']['fields'];
	$verborgen = isset( $cart_item['ppom']['conditionally_hidden'] ) ? $cart_item['ppom']['conditionally_hidden'] : '';

	foreach ( is_string( $verborgen ) ? explode( ',', $verborgen ) : (array) $verborgen as $naam ) {
		unset( $velden[ $naam ] );
	}

	$gevonden = false;

	array_walk_recursive(
		$velden,
		static function ( $waarde ) use ( &$gevonden, $cart_item ) {
			if ( $gevonden || ! is_string( $waarde ) ) {
				return;
			}
			$match    = (bool) preg_match( '/gemonteerd/iu', $waarde ) && (bool) preg_match( '/afgehaald|afhaling/iu', $waarde );
			$gevonden = (bool) apply_filters( 'threeducation_alleen_afhalen_optie', $match, $waarde, $cart_item );
		}
	);

	return $gevonden;
}

/**
 * Of een van deze winkelwagenregels alleen afgehaald kan worden.
 *
 * @param array $regels Winkelwagenregels.
 * @return bool
 */
function threeducation_alleen_afhalen_regels( $regels ) {
	foreach ( (array) $regels as $regel ) {
		if ( threeducation_alleen_afhalen_regel( $regel ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Of deze verzendoptie een afhaalmethode is.
 *
 * @param mixed $rate Verzendoptie.
 * @return bool
 */
function threeducation_alleen_afhalen_is_afhaalrate( $rate ) {
	$method_ids = (array) apply_filters( 'threeducation_afhaal_method_ids', array( 'pickup_location', 'local_pickup' ) );

	return $rate instanceof WC_Shipping_Rate && in_array( $rate->get_method_id(), $method_ids, true );
}

/**
 * Merkt het verzendpakket. WooCommerce bewaart de verzendopties in de sessie
 * zolang de hash van het pakket gelijk blijft; door dit merkteken krijgt een
 * winkelwagen van vóór deze plugin toch nieuwe opties.
 *
 * @param array $packages Verzendpakketten.
 * @return array
 */
function threeducation_alleen_afhalen_pakketten( $packages ) {
	foreach ( $packages as $sleutel => $package ) {
		if ( ! empty( $package['contents'] ) && threeducation_alleen_afhalen_regels( $package['contents'] ) ) {
			$packages[ $sleutel ]['threeducation_alleen_afhalen'] = true;
		}
	}
	return $packages;
}
add_filter( 'woocommerce_cart_shipping_packages', 'threeducation_alleen_afhalen_pakketten', 100 );

/**
 * Houdt alleen de afhaalmethodes over.
 *
 * @param WC_Shipping_Rate[] $rates   Verzendopties.
 * @param array              $package Verzendpakket.
 * @return WC_Shipping_Rate[]
 */
function threeducation_alleen_afhalen_rates( $rates, $package ) {
	if ( empty( $package['contents'] ) || ! threeducation_alleen_afhalen_regels( $package['contents'] ) ) {
		return $rates;
	}

	$afhalen = array_filter( $rates, 'threeducation_alleen_afhalen_is_afhaalrate' );

	return $afhalen ? $afhalen : $rates;
}
add_filter( 'woocommerce_package_rates', 'threeducation_alleen_afhalen_rates', 100, 2 );

/**
 * Of de beperking nu echt geldt: een pakket met zo'n optie waarin alleen nog
 * afhaalmethodes staan. Leest de pakketten zonder de winkelwagentotalen aan te
 * raken, wat WC()->cart->calculate_shipping() wel zou doen.
 *
 * @return bool
 */
function threeducation_alleen_afhalen_actief() {
	if ( ! WC()->cart->needs_shipping() ) {
		return false;
	}

	foreach ( (array) WC()->shipping()->calculate_shipping( WC()->cart->get_shipping_packages() ) as $package ) {
		if ( empty( $package['contents'] ) || empty( $package['rates'] ) || ! threeducation_alleen_afhalen_regels( $package['contents'] ) ) {
			continue;
		}
		if ( count( array_filter( $package['rates'], 'threeducation_alleen_afhalen_is_afhaalrate' ) ) === count( $package['rates'] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Zet boven het afrekenblok waarom alleen afhalen kan, en verbergt de keuze
 * Verzenden/Afhalen: die blijft anders staan zolang afhalen bestaat, terwijl
 * "Verzenden" dan nergens heen leidt. De afhaallocatie zelf blijft zichtbaar.
 *
 * @param string $html Gerenderd afrekenblok.
 * @return string
 */
function threeducation_alleen_afhalen_melding( $html ) {
	if ( is_admin() || ! function_exists( 'WC' ) || ! WC()->cart || ! threeducation_alleen_afhalen_actief() ) {
		return $html;
	}

	$tekst = __( 'Je koos een montage-optie die je in de winkel afhaalt. Deze bestelling kan daarom niet verzonden worden.', '3ducation' );

	return '<style>.wc-block-checkout__shipping-method{display:none!important}</style>'
		. '<div class="wc-block-components-notice-banner is-info" role="status" style="margin-bottom:1.5rem">'
		. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.2a8.8 8.8 0 1 0 0 17.6 8.8 8.8 0 0 0 0-17.6zm0 16.1a7.3 7.3 0 1 1 0-14.6 7.3 7.3 0 0 1 0 14.6zM11.25 11h1.5v5h-1.5v-5zm0-3h1.5v1.5h-1.5V8z"></path></svg>'
		. '<div class="wc-block-components-notice-banner__content">' . esc_html( $tekst ) . '</div>'
		. '</div>'
		. $html;
}
add_filter( 'render_block_woocommerce/checkout', 'threeducation_alleen_afhalen_melding' );
