<?php
/**
 * Plugin Name: 3DUCATION betalen bij ophalen: niet met een cadeaubon
 * Description: Verbergt de betaalmethode "Betalen bij ophalen" (WooCommerce "Contant betalen bij ontvangst", id cod) zodra er een cadeaubon in de winkelwagen zit, zodat een bon nooit onbetaald uitgegeven wordt.
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Vraag van Patrick (2026-11-23): klanten moeten bij afhalen in de winkel
 * kunnen betalen. Dat is de ingebouwde methode "Contant betalen bij
 * ontvangst", beperkt tot de afhaalmethode (WooCommerce → Instellingen →
 * Betalingen → Offline betaalmethoden). Zo'n bestelling komt meteen op
 * "In behandeling" zonder betaling.
 *
 * Probleem: PW Gift Cards geeft een bon uit (en mailt de code) bij
 * "In behandeling" als "Send when processing" aan staat, anders bij
 * "Afgerond". Een cadeaubon in een bestelling met betalen bij ophalen kan dus
 * een geldige bon opleveren voor er betaald is. "Accepteer voor virtuele
 * bestellingen" uitvinken dekt alleen een winkelwagen met enkel bonnen; een
 * bon samen met filament en afhalen zou nog door kunnen.
 *
 * Wat dit doet: haalt `cod` uit `woocommerce_available_payment_gateways`
 * zolang de winkelwagen (of, op de pagina "Bestelling betalen", de
 * bestelling) een PW-cadeaubon bevat. Het afrekenblok krijgt zijn lijst
 * beschikbare methodes via de Store API uit diezelfde filter, en de Store API
 * weigert bij het plaatsen een methode die er niet in staat. De klassieke
 * afrekenpagina gebruikt dezelfde filter.
 *
 * Niet geraakt: de kassa (WCPOS heeft eigen betaalmethodes en geen
 * winkelwagen), wp-admin, en elke andere betaalmethode.
 *
 * Afhankelijkheid (zie memory mu-plugins-update-checks): het producttype
 * `pw-gift-card` van PW Gift Cards en de gateway-id `cod`. Controle na een
 * update van PW Gift Cards of WooCommerce: winkelwagen met een cadeaubon en
 * een fysiek product, afhalen kiezen: "Betalen bij ophalen" staat er niet.
 * Zonder de bon staat hij er wel.
 *
 * Extra producttypes toevoegen: filter `threeducation_cadeaubon_product_types`.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is dit product (of de ouder van deze variatie) een cadeaubon?
 *
 * @param WC_Product|false|null $product Product uit de winkelwagen of bestelling.
 */
function threeducation_afhaalbetaling_is_cadeaubon( $product ): bool {
	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	$types = (array) apply_filters(
		'threeducation_cadeaubon_product_types',
		array( defined( 'PWGC_PRODUCT_TYPE_SLUG' ) ? PWGC_PRODUCT_TYPE_SLUG : 'pw-gift-card' )
	);

	if ( $product->is_type( $types ) ) {
		return true;
	}

	if ( $product->get_parent_id() ) {
		$parent = wc_get_product( $product->get_parent_id() );

		return $parent instanceof WC_Product && $parent->is_type( $types );
	}

	return false;
}

/** Bevat de huidige winkelwagen, of de bestelling op "Bestelling betalen", een cadeaubon? */
function threeducation_afhaalbetaling_heeft_cadeaubon(): bool {
	// Pagina "Bestelling betalen" (link uit een mail of uit wp-admin): de bestelling telt, niet de winkelwagen.
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
		$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
		if ( $order ) {
			foreach ( $order->get_items() as $item ) {
				if ( $item instanceof WC_Order_Item_Product && threeducation_afhaalbetaling_is_cadeaubon( $item->get_product() ) ) {
					return true;
				}
			}
		}

		return false;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		if ( threeducation_afhaalbetaling_is_cadeaubon( $cart_item['data'] ?? null ) ) {
			return true;
		}
	}

	return false;
}

add_filter(
	'woocommerce_available_payment_gateways',
	function ( $gateways ) {
		if ( ! is_array( $gateways ) || ! isset( $gateways['cod'] ) ) {
			return $gateways;
		}

		// In wp-admin (instellingen, bestelscherm) altijd de volledige lijst tonen.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $gateways;
		}

		if ( threeducation_afhaalbetaling_heeft_cadeaubon() ) {
			unset( $gateways['cod'] );
		}

		return $gateways;
	},
	20
);
