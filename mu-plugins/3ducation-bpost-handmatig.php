<?php
/**
 * Plugin Name: 3DUCATION bpost: label alleen met de knop
 * Description: Zet de automatische export van de bpost-plugin uit. Een betaalde bestelling maakt niet langer vanzelf een bpost-label (en verbruikt dus geen credits); het label komt er pas als je in de bestellijst op de bpost-knop klikt.
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Vraag van Patrick (2026-11-19): de bpost-plugin maakt een label zodra een
 * bestelling betaald is. Kiest de klant daarna toch voor afhalen, dan betaalt
 * Patrick de verzendkost terug maar kan hij het al betaalde label niet
 * annuleren. Hij wil zelf beslissen wanneer het label gemaakt wordt.
 *
 * Oorzaak: bpost-shipping-platform (nagekeken in 3.2.5 en 3.2.6) leest in
 * includes/class-woo-Bpost.php de optie `Bpost_autoexport` en hangt
 * `auto_export()` aan `woocommerce_order_status_<status>`. Die optie wordt bij
 * elke token-vernieuwing hard op `wc-processing` gezet
 * (includes/core/class-Bpost-api-v3.php, get_token()) en heeft geen veld in
 * wp-admin, dus in de database aanpassen houdt geen stand.
 *
 * Oplossing: de optie bij het lezen leeg teruggeven. De plugin slaat de haak
 * dan over (`strlen( $autoexport_status ) > 0`). Een mu-plugin laadt vóór de
 * gewone plugins, dus de filter staat er als bpost de optie leest. De waarde
 * in de database blijft ongemoeid: haal dit bestand weg en de automatische
 * export werkt weer zoals voorheen.
 *
 * Wat blijft werken: de bpost-knop per bestelling en de bulkactie in de
 * bestellijst, en alles aan de kant van het afrekenen (tarieven, pakketpunt).
 *
 * Afhankelijkheid (zie memory mu-plugins-update-checks): de optienaam
 * `Bpost_autoexport`. Hernoemt bpost die bij een update, dan komt de
 * automatische export stil terug. Controle na een update van de bpost-plugin:
 * een betaalde testbestelling met verzending blijft op "niet geëxporteerd"
 * staan tot je op de knop klikt.
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pre_option_Bpost_autoexport',
	function () {
		return '';
	}
);
