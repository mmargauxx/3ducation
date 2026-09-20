<?php
/**
 * Plugin Name: 3DUCATION btw verlegd aan de kassa
 * Description: Rekent een kassaverkoop zonder btw af voor een klant die als "btw verlegd" is aangeduid en een niet-Belgisch EU-btw-nummer heeft (intracommunautaire levering).
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Vraag van Patrick (2026-09-18): Nederlandse zakelijke klanten in de winkel
 * kunnen bedienen met btw verlegd. De webshop doet dat via de EU VAT-plugin;
 * aan de kassa gebeurde er niets en werd gewoon 21 % gerekend.
 *
 * FISCALE VOORWAARDE, en de reden dat dit per klant gaat en niet automatisch:
 * verlegging berust op een intracommunautaire levering, en dan moet het vervoer
 * naar de andere lidstaat aantoonbaar zijn. Een Nederlandse zaakvoerder die aan
 * de toonbank koopt en meeneemt, is in beginsel een binnenlandse Belgische
 * levering met 21 %, ook met een geldig NL-nummer. Daarom hangt de vrijstelling
 * aan een vinkje dat iemand met kennis van zaken per klant zet
 * (Gebruikers -> klant -> "Btw-verlegging (kassa)"), niet aan de loutere
 * aanwezigheid van een btw-nummer. Een gastverkoop zonder klant op de
 * bestelling krijgt dus nooit verlegging.
 *
 * MECHANISME. WooCommerce zet bij elke herberekening alle regel- en
 * verzendbtw op nul als `woocommerce_order_is_vat_exempt` true teruggeeft
 * (WC_Abstract_Order::calculate_taxes). WooCommerce POS rekent zelf geen btw:
 * het stuurt de bestelling naar de gewone wc/v3-controller, en die roept
 * calculate_totals() bij het aanmaken en bij elke wijziging die regels of
 * adressen aanraakt. Deze filter grijpt dus precies op het juiste moment in,
 * en blijft staan bij elke herberekening van de kassa.
 *
 * Waarom niet via de EU VAT-plugin: die hangt aan de winkelwagen en de
 * checkout (`woocommerce_before_calculate_totals`, `set_is_vat_exempt()` op
 * WC()->customer) en heeft geen enkele hook op via de REST API aangemaakte
 * bestellingen. Bij een REST-verzoek bestaat er bovendien geen
 * WC()->customer-object. De vrijstelling moet dus op de *bestelling* staan,
 * niet op de klant.
 *
 * VOORWAARDEN (alle vier moeten kloppen):
 *  1. het is een kassabestelling (`created_via = woocommerce-pos`);
 *  2. er staat een klant op de bestelling;
 *  3. die klant heeft het vinkje aan;
 *  4. zijn btw-nummer begint met een EU-landcode die niet BE is.
 *
 * UITZONDERING PER BESTELLING. De EU VAT-plugin zet onderaan elk bestelscherm
 * een knop "Exempt VAT" / "Impose VAT". Die schrijft `exempt_vat_from_admin`
 * op de bestelling; de waarde `never` wint hier, zodat één verkoop alsnog met
 * btw afgerekend kan worden zonder het vinkje van de klant te wijzigen.
 *
 * WAT DIT BESTAND OP DE BESTELLING SCHRIJFT (voor de boekhouding, niet voor de
 * berekening):
 *  - `_billing_vat` als dat leeg was, zodat het nummer op de kassabon en de
 *    factuur staat (WCPOS leest die sleutel zelf al uit);
 *  - `is_vat_exempt = yes`, zodat de vrijstelling ook zichtbaar is buiten deze
 *    filter en bij een latere herberekening overeind blijft;
 *  - één bestelnota met het gebruikte nummer.
 *
 * AFHANKELIJKHEDEN (zie memory mu-plugins-update-checks): het thema-veld
 * `billing_vat` / `_billing_vat` en de WooCommerce-filter
 * `woocommerce_order_is_vat_exempt`. Voor "is dit een kassabestelling?"
 * gebruiken we `wcpos_is_pos_order()`, met de verouderde alias
 * `woocommerce_pos_is_pos_order()` en daarna een eigen controle op
 * `created_via` / de oude meta `_pos` als terugval -- die twee waarden staan op
 * de bestelling zelf en overleven een naamswijziging in de kassa. Is er
 * helemaal geen kassa, dan bestaat `created_via = woocommerce-pos` niet en doet
 * dit bestand niets.
 *
 * NOG BUITEN DIT BESTAND: de vermelding "Btw verlegd" op de kassabon (het
 * WCPOS-bonsjabloon staat op live, niet in de repository) en op de
 * OnFact-factuur. Zonder die vermelding is het bedrag juist maar de factuur
 * formeel onvolledig.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Geen
 * activatiestap. Zonder WooCommerce POS doet het bestand niets.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'THREEDUCATION_VERLEGD_META' ) ) {
	define( 'THREEDUCATION_VERLEGD_META', 'threeducation_btw_verlegd' );
}

/**
 * EU-landcodes waarvoor verlegging kan gelden -- België bewust niet.
 *
 * EL en GR staan er beide in (Griekse btw-nummers beginnen met EL), en XI voor
 * Noord-Ierland, dat voor goederen nog onder de EU-btw-regels valt.
 *
 * @return string[]
 */
function threeducation_verlegd_eu_prefixes() {
	$prefixes = array(
		'AT', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'EL', 'ES', 'FI', 'FR', 'GR',
		'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO',
		'SE', 'SI', 'SK', 'XI',
	);

	return apply_filters( 'threeducation_verlegd_eu_prefixes', $prefixes );
}

/**
 * Is dit een kassabestelling?
 *
 * `wcpos_is_pos_order()` is de huidige functie van WooCommerce POS;
 * `woocommerce_pos_is_pos_order()` is de verouderde alias ervan. Bestaat geen
 * van beide, dan kijken we zelf naar wat de kassa op de bestelling schrijft.
 */
function threeducation_verlegd_is_pos_order( $order ) {
	if ( function_exists( 'wcpos_is_pos_order' ) ) {
		return (bool) wcpos_is_pos_order( $order );
	}

	if ( function_exists( 'woocommerce_pos_is_pos_order' ) ) {
		return (bool) woocommerce_pos_is_pos_order( $order );
	}

	return 'woocommerce-pos' === $order->get_created_via() || '1' === $order->get_meta( '_pos' );
}

/** Normaliseer een btw-nummer zoals het thema dat doet: BE 0772.923.417 -> BE0772923417. */
function threeducation_verlegd_normalize_vat( $vat ) {
	return strtoupper( preg_replace( '/[^0-9A-Za-z]/', '', (string) $vat ) );
}

/**
 * Het btw-nummer van de bestelling, met de klant als terugval.
 *
 * De kassa schrijft zelf geen btw-nummer op de bestelling, dus bij een nieuwe
 * kassaverkoop staat het alleen nog bij de klant.
 */
function threeducation_verlegd_vat_number( $order ) {
	$vat = threeducation_verlegd_normalize_vat( $order->get_meta( '_billing_vat' ) );

	if ( '' === $vat ) {
		$customer_id = $order->get_customer_id();

		if ( $customer_id ) {
			$vat = threeducation_verlegd_normalize_vat( get_user_meta( $customer_id, 'billing_vat', true ) );
		}
	}

	return $vat;
}

/** Geldt verlegging voor deze bestelling? */
function threeducation_verlegd_applies( $order ) {
	$applies = false;

	if (
		$order instanceof WC_Order
		&& threeducation_verlegd_is_pos_order( $order )
		&& 'never' !== $order->get_meta( 'exempt_vat_from_admin' )
	) {
		$customer_id = $order->get_customer_id();

		if ( $customer_id && 'yes' === get_user_meta( $customer_id, THREEDUCATION_VERLEGD_META, true ) ) {
			$vat    = threeducation_verlegd_vat_number( $order );
			$prefix = substr( $vat, 0, 2 );

			$applies = (
				strlen( $vat ) > 4
				&& 'BE' !== $prefix
				&& in_array( $prefix, threeducation_verlegd_eu_prefixes(), true )
			);
		}
	}

	return (bool) apply_filters( 'threeducation_verlegd_applies', $applies, $order );
}

/**
 * De eigenlijke vrijstelling.
 *
 * Een bestaande vrijstelling (bijvoorbeeld de knop "Exempt VAT" van de EU
 * VAT-plugin, die op PHP_INT_MAX loopt) blijft staan; we zetten er alleen
 * onze eigen voorwaarde naast.
 */
function threeducation_verlegd_order_is_vat_exempt( $is_exempt, $order ) {
	return $is_exempt || threeducation_verlegd_applies( $order );
}
add_filter( 'woocommerce_order_is_vat_exempt', 'threeducation_verlegd_order_is_vat_exempt', 10, 2 );

/**
 * Leg de verlegging vast op de bestelling, nadat de kassa ze heeft weggeschreven.
 *
 * Dit raakt de berekening niet -- die is op dit punt al gebeurd via de filter
 * hierboven. Het zorgt er alleen voor dat het btw-nummer, de vrijstelling en
 * een spoor voor de boekhouding op de bestelling staan.
 *
 * @param WC_Order        $order    De weggeschreven bestelling.
 * @param WP_REST_Request $request  Het REST-verzoek.
 * @param bool            $creating Nieuwe bestelling of wijziging.
 */
function threeducation_verlegd_record( $order, $request, $creating ) {
	if ( ! threeducation_verlegd_applies( $order ) ) {
		return;
	}

	$vat     = threeducation_verlegd_vat_number( $order );
	$changed = false;

	if ( '' !== $vat && threeducation_verlegd_normalize_vat( $order->get_meta( '_billing_vat' ) ) !== $vat ) {
		$order->update_meta_data( '_billing_vat', $vat );
		$changed = true;
	}

	if ( 'yes' !== $order->get_meta( 'is_vat_exempt' ) ) {
		$order->update_meta_data( 'is_vat_exempt', 'yes' );
		$changed = true;
	}

	if ( 'yes' !== $order->get_meta( '_threeducation_verlegd' ) ) {
		$order->update_meta_data( '_threeducation_verlegd', 'yes' );
		$order->add_order_note(
			sprintf(
				/* translators: %s: btw-nummer van de klant */
				esc_html__( 'Btw verlegd toegepast (kassaverkoop, vrijstelling ingesteld op de klant). Btw-nummer: %s', '3ducation' ),
				$vat
			)
		);
		$changed = true;
	}

	if ( $changed ) {
		$order->save();
	}
}
add_action( 'woocommerce_rest_insert_shop_order_object', 'threeducation_verlegd_record', 20, 3 );

/**
 * Het vinkje op het profiel van de klant.
 */
function threeducation_verlegd_user_field( $user ) {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$checked = 'yes' === get_user_meta( $user->ID, THREEDUCATION_VERLEGD_META, true );
	$vat     = threeducation_verlegd_normalize_vat( get_user_meta( $user->ID, 'billing_vat', true ) );
	?>
	<h2><?php esc_html_e( 'Btw-verlegging (kassa)', '3ducation' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="threeducation_btw_verlegd"><?php esc_html_e( 'Btw verlegd', '3ducation' ); ?></label>
			</th>
			<td>
				<label>
					<input type="checkbox" name="threeducation_btw_verlegd" id="threeducation_btw_verlegd" value="yes" <?php checked( $checked ); ?> />
					<?php esc_html_e( 'Kassaverkopen aan deze klant zonder btw afrekenen', '3ducation' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Alleen aanvinken bij een intracommunautaire levering: de goederen gaan aantoonbaar naar een andere EU-lidstaat en het btw-nummer is gecontroleerd. Zonder niet-Belgisch EU-btw-nummer doet dit vinkje niets.', '3ducation' ); ?>
					<br />
					<?php
					printf(
						/* translators: %s: btw-nummer van de klant, of een streepje */
						esc_html__( 'Btw-nummer van deze klant: %s', '3ducation' ),
						'<code>' . esc_html( '' !== $vat ? $vat : '—' ) . '</code>'
					);
					?>
				</p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'threeducation_verlegd_user_field' );
add_action( 'edit_user_profile', 'threeducation_verlegd_user_field' );

/**
 * Bewaar het vinkje.
 *
 * WordPress heeft de nonce van het gebruikersformulier (`update-user_<id>`) al
 * gecontroleerd voordat deze hooks aan de beurt komen; we kijken hier naar de
 * rechten.
 */
function threeducation_verlegd_save_user_field( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	if ( isset( $_POST['threeducation_btw_verlegd'] ) && 'yes' === $_POST['threeducation_btw_verlegd'] ) {
		update_user_meta( $user_id, THREEDUCATION_VERLEGD_META, 'yes' );
	} else {
		delete_user_meta( $user_id, THREEDUCATION_VERLEGD_META );
	}
}
add_action( 'personal_options_update', 'threeducation_verlegd_save_user_field' );
add_action( 'edit_user_profile_update', 'threeducation_verlegd_save_user_field' );
