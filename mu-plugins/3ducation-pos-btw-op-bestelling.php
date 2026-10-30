<?php
/**
 * Plugin Name: 3DUCATION BTW-Nummer op kassabestelling
 * Description: Zet het BTW-Nummer van het klantprofiel op een kassabestelling die er zelf geen heeft, zodat OnFact het nummer krijgt (omweg voor een WCPOS-bug bij een klant die tijdens de verkoop wordt aangemaakt).
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Melding van Patrick (2026-10-30): een nieuwe klant met BTW-Nummer die aan de
 * kassa wordt aangemaakt, krijgt zijn factuur in OnFact zonder BTW-Nummer en
 * dus niet via Peppol. Bestaande klanten gaan wel goed (#15055). Voorbeelden:
 * #15020 en #15022 (28-10).
 *
 * Oorzaak, in de WCPOS-app (monorepo,
 * `packages/core/src/screens/main/pos/cart/add-customer.tsx`): na het
 * aanmaken van de klant zet de kassa alleen `customer_id`, `billing` en
 * `shipping` op de bon, niet `tax_ids`. Een bestaande klant kiezen
 * (`pos/hooks/utils.ts`) kopieert `tax_ids` wel. De bestelling gaat dus met
 * `tax_ids: []` naar de server, en `Order_Write_Payload::persist_tax_ids()`
 * schrijft die lege lijst weg. Het klantprofiel krijgt het nummer wel
 * (`billing_eu_vat_number`). Gemeld bij de WCPOS-ontwikkelaar op 30-10.
 *
 * Werking: bij elke opslag van een kassabestelling tijdens een REST-verzoek
 * (dus door de kassa, niet in wp-admin) zonder `_billing_eu_vat_number`, met een
 * klant die er op zijn profiel wel een heeft, wordt dat nummer op de bestelling
 * gezet. Het gebeurt in dezelfde opslag, dus de OnFact-webhook ziet het nummer
 * meteen. Er komt één bestelnota bij.
 *
 * Wat dit bewust NIET doet:
 * - een bestaand nummer op de bestelling overschrijven;
 * - iets doen bij een opslag in wp-admin: wie daar het nummer van een bestelling
 *   wist, ziet het niet terugkomen;
 * - btw, bedragen of status wijzigen.
 * Wisselt de kassa daarna van klant, dan wordt het overgenomen nummer
 * weggehaald (of vervangen door dat van de nieuwe klant), maar alleen als het
 * nog het nummer is dat dit bestand er zelf op zette.
 *
 * Als WCPOS de bug oplost, staat het nummer al op de bestelling en doet dit
 * bestand niets meer. Het mag dan weg.
 *
 * Afhankelijkheden (zie memory mu-plugins-update-checks): het EU VAT-pluginveld
 * (`billing_eu_vat_number` / `_billing_eu_vat_number`), `wcpos_is_pos_order()`
 * met `created_via = woocommerce-pos` als terugval.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Geen
 * activatiestap.
 */

defined( 'ABSPATH' ) || exit;

/** Welke klant leverde het overgenomen nummer (order meta). */
const THREEDUCATION_BTW_VAN_KLANT_META = '_3du_btw_van_klant';

/** Is dit een kassabestelling? Zelfde volgorde als in 3ducation-pos-btw-verlegd.php. */
function threeducation_btw_bestelling_is_pos_order( $order ) {
	if ( function_exists( 'wcpos_is_pos_order' ) ) {
		return (bool) wcpos_is_pos_order( $order );
	}

	if ( function_exists( 'woocommerce_pos_is_pos_order' ) ) {
		return (bool) woocommerce_pos_is_pos_order( $order );
	}

	return 'woocommerce-pos' === $order->get_created_via() || '1' === $order->get_meta( '_pos' );
}

/** BE 0772.923.417 -> BE0772923417, zoals het thema. */
function threeducation_btw_bestelling_normalize( $vat ) {
	return strtoupper( preg_replace( '/[^0-9A-Za-z]/', '', (string) $vat ) );
}

/** Bestellingen die in dit verzoek een nummer kregen, voor de bestelnota na de opslag. */
function threeducation_btw_bestelling_noted( $order_id = null, $vat = null ) {
	static $pending = array();

	if ( null !== $order_id ) {
		$pending[ $order_id ] = $vat;
	}

	return $pending;
}

/**
 * Vul het BTW-Nummer aan vlak voor de kassa de bestelling opslaat.
 *
 * Loopt na WCPOS' eigen `woocommerce_before_order_object_save` (prioriteit 10),
 * dus `created_via` staat er al bij het aanmaken. De opslag van
 * Tax_Id_Writer::write_for_order() komt hier ook langs.
 *
 * @param WC_Abstract_Order $order De bestelling.
 */
function threeducation_btw_bestelling_fill( $order ) {
	if ( ! $order instanceof WC_Order || ! wp_is_serving_rest_request() ) {
		return;
	}
	if ( ! threeducation_btw_bestelling_is_pos_order( $order ) ) {
		return;
	}

	$customer_id = (int) $order->get_customer_id();
	$current     = threeducation_btw_bestelling_normalize( $order->get_meta( '_billing_eu_vat_number' ) );
	$copied_from = (int) $order->get_meta( THREEDUCATION_BTW_VAN_KLANT_META );

	// Eerder door ons overgenomen, en de kassa wisselde van klant: haal ons nummer weg.
	if ( $copied_from && $copied_from !== $customer_id ) {
		$ours = threeducation_btw_bestelling_normalize( get_user_meta( $copied_from, 'billing_eu_vat_number', true ) );
		if ( '' === $current || $current === $ours ) {
			$order->delete_meta_data( '_billing_eu_vat_number' );
			$current = '';
		}
		$order->delete_meta_data( THREEDUCATION_BTW_VAN_KLANT_META );
	}

	if ( '' !== $current || $customer_id <= 0 ) {
		return;
	}

	$vat = threeducation_btw_bestelling_normalize( get_user_meta( $customer_id, 'billing_eu_vat_number', true ) );
	if ( '' === $vat ) {
		return;
	}

	$order->update_meta_data( '_billing_eu_vat_number', $vat );
	$order->update_meta_data( THREEDUCATION_BTW_VAN_KLANT_META, $customer_id );
	threeducation_btw_bestelling_noted( spl_object_id( $order ), $vat );
}
add_action( 'woocommerce_before_order_object_save', 'threeducation_btw_bestelling_fill', 100 );

/** Eén bestelnota zodra de bestelling een ID heeft. */
function threeducation_btw_bestelling_note( $order ) {
	$pending = threeducation_btw_bestelling_noted();
	$key     = spl_object_id( $order );

	if ( ! isset( $pending[ $key ] ) || ! $order->get_id() ) {
		return;
	}

	$vat = $pending[ $key ];
	threeducation_btw_bestelling_noted( $key, null );
	if ( null === $vat ) {
		return;
	}

	$order->add_order_note(
		sprintf(
			/* translators: %s: btw-nummer van de klant */
			esc_html__( 'BTW-Nummer %s overgenomen van het klantprofiel (de kassa zette het niet op de bestelling).', '3ducation' ),
			$vat
		)
	);
}
add_action( 'woocommerce_after_order_object_save', 'threeducation_btw_bestelling_note' );
