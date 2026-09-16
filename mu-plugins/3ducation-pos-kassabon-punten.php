<?php
/**
 * Plugin Name: 3DUCATION spaarpunten op de kassabon
 * Description: Zet het huidige WPLoyalty-puntensaldo van de klant en de punten die met de aankoop gespaard werden in de bondata van WooCommerce POS, zodat de thermische kassabon ze kan tonen.
 * Version: 1.1.0
 * Author: 3DUCATION
 *
 * Vraag van Patrick (2026-09-16): na een kassaverkoop moet de klant op de bon
 * zien hoeveel spaarpunten hij nu heeft. v1.1.0 (zelfde dag): ook hoeveel
 * punten hij met deze aankoop spaarde, en "punt"/"punten" vast in het
 * Nederlands (het WPLoyalty-label hangt af van de taal van de kassagebruiker).
 *
 * WooCommerce POS (vanaf 1.10.8) bouwt de bondata server-side in
 * Receipt_Data_Builder en laat ze door de filter `woocommerce_pos_receipt_data`
 * gaan — voor de live bon, de PDF én de fiscale snapshot. Het bonsjabloon is
 * logicless (Mustache), dus wat we hier toevoegen is er meteen bruikbaar:
 *
 *   {{#customer.loyalty_points_text}}…{{customer.loyalty_points_text}}…{{/customer.loyalty_points_text}}
 *   {{#customer.loyalty_points_earned_text}}…{{/customer.loyalty_points_earned_text}}
 *
 * Toegevoegde sleutels onder `customer` (alleen als de klant een WPLoyalty-
 * account heeft; anders ontbreken ze en valt de sectie op de bon gewoon weg):
 *  - loyalty_points       int     huidig saldo (kolom `points` in wlr_users)
 *  - loyalty_points_text  string  "125 punten" / "1 punt"
 *  - loyalty_points_earned       int     punten gespaard met deze bestelling
 *  - loyalty_points_earned_text  string  "+25 punten"; lege string bij 0, zodat
 *                                        de regel op de bon dan wegvalt
 *
 * Gespaarde punten = som van de `credit`-rijen min de `debit`-rijen (retour)
 * in `{prefix}wlr_earn_campaign_transaction` voor dit order_id en dit
 * e-mailadres, alleen campaign_type `point` (beloningen zijn geen punten).
 * Het e-mailfilter houdt verwijzingspunten van een ándere klant op dezelfde
 * bestelling buiten de telling.
 *
 * WPLoyalty koppelt punten aan het factuur-e-mailadres van de bestelling
 * (Woocommerce::getOrderEmail + filter `wlr_order_email`); we doen hetzelfde.
 * Een kassaverkoop zonder klant (gast, geen e-mail) krijgt dus niets.
 *
 * Timing: WPLoyalty kent punten toe op `woocommerce_order_status_changed`
 * (prioriteit 1000, synchroon). De kassa vraagt de bon pas op nadat de
 * bestelling betaald/voltooid is, dus het saldo bevat de punten van deze
 * aankoop al — op voorwaarde dat "Voltooid" in WPLoyalty bij de
 * verdienstatussen staat (standaard: processing + completed).
 *
 * Afhankelijkheden (zie memory mu-plugins-update-checks): WPLoyalty-tabel
 * `{prefix}wlr_users` (user_email/points/is_banned_user) en
 * `{prefix}wlr_earn_campaign_transaction` (order_id/user_email/points/
 * transaction_type/campaign_type), en de
 * WCPOS-filter `woocommerce_pos_receipt_data`. Breekt één van beide bij een
 * update, dan verdwijnt enkel de puntenregel van de bon — nooit de bon zelf.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_pos_receipt_data', 'threeducation_pos_receipt_loyalty_points', 10, 2 );

/**
 * Voeg het WPLoyalty-puntensaldo toe aan de bondata.
 *
 * @param array    $data  Bondata van WooCommerce POS.
 * @param WC_Order $order Bestelling.
 * @return array
 */
function threeducation_pos_receipt_loyalty_points( $data, $order ) {
	if ( ! defined( 'WLR_PLUGIN_VERSION' ) || ! is_array( $data ) || ! is_object( $order ) || ! method_exists( $order, 'get_billing_email' ) ) {
		return $data;
	}

	$email = sanitize_email( (string) $order->get_billing_email() );
	$email = (string) apply_filters( 'wlr_order_email', $email, $order );
	if ( '' === $email ) {
		return $data;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'wlr_users';

	// Een ontbrekende tabel of kolom mag de bon nooit breken.
	$suppress = $wpdb->suppress_errors( true );
	$row      = $wpdb->get_row(
		$wpdb->prepare( "SELECT points, is_banned_user FROM {$table} WHERE user_email = %s LIMIT 1", $email ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	);
	$wpdb->suppress_errors( $suppress );

	if ( ! $row || ! empty( $row->is_banned_user ) ) {
		return $data;
	}

	$points = max( 0, (int) $row->points );

	$suppress = $wpdb->suppress_errors( true );
	$earned   = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE( SUM( CASE WHEN transaction_type = 'debit' THEN -points ELSE points END ), 0 )
			FROM {$wpdb->prefix}wlr_earn_campaign_transaction
			WHERE order_id = %s AND user_email = %s AND campaign_type = 'point'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			(string) $order->get_id(),
			$email
		)
	);
	$wpdb->suppress_errors( $suppress );
	$earned = max( 0, $earned );

	if ( ! isset( $data['customer'] ) || ! is_array( $data['customer'] ) ) {
		$data['customer'] = array();
	}
	$data['customer']['loyalty_points']             = $points;
	$data['customer']['loyalty_points_text']        = threeducation_pos_receipt_points_text( $points );
	$data['customer']['loyalty_points_earned']      = $earned;
	$data['customer']['loyalty_points_earned_text'] = $earned > 0 ? '+' . threeducation_pos_receipt_points_text( $earned ) : '';

	return $data;
}

/**
 * "1 punt" / "1.250 punten" — vast Nederlands, los van de taal van de kassagebruiker.
 *
 * @param int $points Aantal punten.
 * @return string
 */
function threeducation_pos_receipt_points_text( $points ) {
	$points = (int) $points;

	return number_format( $points, 0, ',', '.' ) . ' ' . ( 1 === $points ? 'punt' : 'punten' );
}
