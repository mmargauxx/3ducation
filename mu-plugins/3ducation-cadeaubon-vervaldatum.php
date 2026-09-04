<?php
/**
 * Plugin Name: 3DUCATION cadeaubon vervaldatum
 * Description: Geeft elke nieuw aangemaakte PW-cadeaubon (webshop, kassa én beheer) een vervaldatum van 2 jaar na aankoop, zet "Geldig tot …" in de bonmail, en kan eenmalig de bestaande bonnen van de nieuwe shop op aanmaakdatum + 2 jaar zetten (?3du_verval_bonnen=test, dan =doen).
 * Version: 1.0.0
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
 *  3. Eenmalig bijwerken van de bestaande bonnen van de nieuwe shop: surf als
 *     beheerder naar ?3du_verval_bonnen=test (toont wat er zou gebeuren) en
 *     daarna ?3du_verval_bonnen=doen. Regel (afgesproken 2026-09-04): elke
 *     actieve, nog niet vervallen bon krijgt aanmaakdatum + 2 jaar. Bonnen die
 *     uit de oude webshop gemigreerd zijn, worden hier overgeslagen: hun echte
 *     aankoopdatum zit alleen in de oude export, en die verwerkt een apart
 *     eenmalig script (3ducation-vervaldatum-migratie.php, buiten de repo).
 *     Veilig om te herhalen: er verandert alleen iets als de datum afwijkt.
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

/*
 * 3. Eenmalig bijwerken van de bestaande bonnen van de nieuwe shop.
 */
add_action( 'admin_init', function () {
	if ( empty( $_GET['3du_verval_bonnen'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$echt = ( 'doen' === $_GET['3du_verval_bonnen'] );

	if ( ! class_exists( 'PW_Gift_Card' ) ) {
		wp_die( 'PW WooCommerce Gift Cards is niet actief.' );
	}

	global $wpdb;
	$vandaag = current_time( 'Y-m-d' );

	$rijen = $wpdb->get_results( $wpdb->prepare(
		"SELECT c.pimwick_gift_card_id AS id, c.number, c.expiration_date,
		        CONVERT_TZ( c.create_date, @@session.time_zone, '+00:00' ) AS create_date_gmt,
		        ( SELECT a.note FROM {$wpdb->pimwick_gift_card_activity} a
		          WHERE a.pimwick_gift_card_id = c.pimwick_gift_card_id AND a.action = %s
		          ORDER BY a.pimwick_gift_card_activity_id ASC LIMIT 1 ) AS aanmaak_notitie
		 FROM {$wpdb->pimwick_gift_card} c
		 WHERE c.active = 1
		 ORDER BY c.create_date ASC",
		'create'
	) );

	$gedaan = $ongewijzigd = $overgeslagen = $mislukt = 0;
	echo '<pre style="padding:20px;font:13px/1.6 monospace">';
	echo $echt ? "VERVALDATUM ZETTEN\n\n" : "TESTLOOP - er wordt niets gewijzigd\n\n";
	echo sprintf( "Regel: actieve, niet-vervallen bon -> aanmaakdatum %s. Gemigreerde bonnen: apart script.\nActieve bonnen: %d\n\n", THREEDUCATION_BON_GELDIGHEID, count( $rijen ) );

	foreach ( $rijen as $rij ) {
		$huidig     = $rij->expiration_date ?: null;
		// Aanmaakdatum in de tijdzone van de site, zoals de haak hierboven ook rekent.
		$aangemaakt = get_date_from_gmt( $rij->create_date_gmt, 'Y-m-d' );
		$migratie   = is_string( $rij->aanmaak_notitie ) && 0 === strpos( $rij->aanmaak_notitie, THREEDUCATION_BON_MIGRATIE_PREFIX );

		if ( $migratie ) {
			$overgeslagen++;
			continue;
		}
		if ( null !== $huidig && $huidig < $vandaag ) {
			echo sprintf( "overgeslagen  %s  al vervallen op %s\n", $rij->number, $huidig );
			$overgeslagen++;
			continue;
		}

		$nieuw = threeducation_bon_vervaldatum( strtotime( $aangemaakt ) );
		if ( $nieuw === $huidig ) {
			$ongewijzigd++;
			continue;
		}

		if ( ! $echt ) {
			echo sprintf( "zou zetten    %s  %s  ->  geldig tot %s  (aangemaakt %s)\n", $rij->number, $huidig ?: '(geen)    ', $nieuw, $aangemaakt );
			$gedaan++;
			continue;
		}
		if ( threeducation_bon_zet_vervaldatum( $rij->id, $nieuw ) ) {
			echo sprintf( "gezet         %s  %s  ->  geldig tot %s  (aangemaakt %s)\n", $rij->number, $huidig ?: '(geen)    ', $nieuw, $aangemaakt );
			$gedaan++;
		} else {
			echo sprintf( "MISLUKT       %s: %s\n", $rij->number, $wpdb->last_error );
			$mislukt++;
		}
	}

	echo sprintf( "\n%d %s, %d al in orde, %d overgeslagen (gemigreerd of al vervallen), %d mislukt\n", $gedaan, $echt ? 'gezet' : 'te zetten', $ongewijzigd, $overgeslagen, $mislukt );
	echo '</pre>';
	exit;
} );
