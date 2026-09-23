<?php
/**
 * Plugin Name: 3DUCATION cadeaubon vervaldatum
 * Description: Geeft elke nieuw aangemaakte PW-cadeaubon (webshop, kassa én beheer) een vervaldatum van 2 jaar na aankoop en zet "Geldig tot …" in de bonmail.
 * Version: 1.1.0
 * Author: 3DUCATION
 *
 * Waarom: de gratis versie van PW WooCommerce Gift Cards kan geen vervaldatum
 * zetten — niet automatisch bij aankoop en ook niet handmatig in het beheer
 * (PW_Gift_Card::set_expiration_date() is leeg; "Expiration Dates" zit in Pro).
 * Bonnen die via de webshop of de kassa verkocht worden, zouden dus nooit
 * verlopen. De winkel wil 2 jaar geldigheid vanaf de aankoopdatum.
 *
 * Wat dit doet:
 *  1. Bij het aanmaken van een bon (haak `pwgc_activity_create`, vuurt voor elke
 *     bon: webshopbestelling, kassaverkoop, beheer) zetten we de vervaldatum op
 *     vandaag + 2 jaar, tenzij er al een vervaldatum staat (bv. Pro) of de bon
 *     uit de migratie van de oude webshop komt (die scripts zetten zelf de
 *     oorspronkelijke datum).
 *  2. In de bonmail aan de klant voegen we "Geldig tot <datum>." toe onder het
 *     persoonlijke bericht — het standaardsjabloon toont de vervaldatum nergens,
 *     en de klant hoort te weten tot wanneer de bon geldig is.
 *
 * Het eenmalige bijwerken van de bestaande bonnen (?3du_verval_bonnen=test|doen)
 * is afgerond en in 1.1.0 verwijderd: het draaide zonder nonce en toonde alle
 * bonnummers. Gemigreerde bonnen liepen via een apart script buiten de repo.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Geen
 * activatiestap. Zonder PW Gift Cards doet het bestand niets.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;

/** Geldigheidsduur, als strtotime-verschuiving. */
const THREEDUCATION_BON_GELDIGHEID = '+2 years';

/** Notitie-prefix waarmee de migratiescripts hun bonnen aanmaken. */
const THREEDUCATION_BON_MIGRATIE_PREFIX = 'Migratie oude webshop';

/** Vervaldatum (Y-m-d) op basis van een tijdstip. */
function threeducation_bon_vervaldatum( $tijdstip ) {
	return date( 'Y-m-d', strtotime( THREEDUCATION_BON_GELDIGHEID, (int) $tijdstip ) );
}

/** Schrijft de vervaldatum rechtstreeks in de bontabel (de gratis plugin heeft geen setter). */
function threeducation_bon_zet_vervaldatum( $kaart_id, $datum ) {
	global $wpdb;
	return false !== $wpdb->update(
		$wpdb->pimwick_gift_card,
		array( 'expiration_date' => $datum ),
		array( 'pimwick_gift_card_id' => (int) $kaart_id )
	);
}

/*
 * 1. Nieuwe bonnen: vandaag + 2 jaar.
 */
add_action( 'pwgc_activity_create', function ( $kaart, $bedrag = null, $notitie = null ) {
	if ( ! is_object( $kaart ) || ! method_exists( $kaart, 'get_id' ) || ! $kaart->get_id() ) {
		return;
	}
	if ( $kaart->get_expiration_date() ) {
		return;
	}
	if ( is_string( $notitie ) && 0 === strpos( $notitie, THREEDUCATION_BON_MIGRATIE_PREFIX ) ) {
		return;
	}
	threeducation_bon_zet_vervaldatum( $kaart->get_id(), threeducation_bon_vervaldatum( current_time( 'timestamp' ) ) );
}, 10, 3 );

/*
 * 2. Bonmail: "Geldig tot <datum>." onder het bericht.
 */
add_filter( 'pwgc_customer_email_item_data', function ( $data ) {
	if ( ! is_object( $data ) || empty( $data->gift_card_number ) || ! class_exists( 'PW_Gift_Card' ) ) {
		return $data;
	}
	$kaart = new PW_Gift_Card( $data->gift_card_number );
	if ( ! $kaart->get_id() || ! $kaart->get_expiration_date() ) {
		return $data;
	}
	$regel = sprintf(
		/* translators: %s: datum */
		__( 'Geldig tot %s.', '3ducation' ),
		date_i18n( 'j F Y', strtotime( $kaart->get_expiration_date() ) )
	);
	$data->message = empty( $data->message ) ? $regel : rtrim( $data->message ) . "\n\n" . $regel;
	return $data;
} );
