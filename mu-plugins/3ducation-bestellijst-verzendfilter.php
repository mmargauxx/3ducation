<?php
/**
 * Plugin Name: 3DUCATION bestellijst: filter op verzendmethode
 * Description: Keuzelijst "Alle verzendmethodes" boven de WooCommerce-bestellijst, om bijvoorbeeld alleen de bpost @ home-bestellingen te tonen.
 * Version: 1.2.0
 * Author: 3DUCATION
 *
 * Vraag van Margaux (2026-11-24): in de bestellijst filteren op alle
 * bpost @ home-bestellingen.
 *
 * Wat dit doet: naast de bestaande filters (datum, verkoopkanaal, klant)
 * staat een keuzelijst met elke verzendmethode zoals ze op de bestellingen
 * staat ("bpost @ home", "Zelf Afhalen (Nieuwkerken)" …), met het aantal
 * bestellingen erachter. Kies je er een en klik je op Filter, dan toont de
 * lijst alleen bestellingen met die verzendmethode. Werkt samen
 * met de statustabs, de zoekfunctie en de andere filters.
 *
 * Groepering: per verzendmethode zoals WooCommerce ze bewaart (meta
 * `method_id` + `instance_id` op de verzendregel, bv. `local_pickup:1` of
 * `shipping_Bpost_71_weight:5`), met als label de naam op de meest recente
 * bestelling. Een hernoemde methode ("Zelf Ophalen" → "Zelf Afhalen
 * (Nieuwkerken)" op 2026-11-24) blijft zo één keuze. Verzendregels zonder
 * method_id (oude geïmporteerde bestellingen) staan bewust niet in de lijst.
 *
 * Afhalen (1.1.0, 2026-11-24): live heeft uit de oude shop meerdere
 * afhaalmethodes met elk een eigen code ("Zelf ophalen Nieuwkerken" twee
 * keer, "Zelf Afhalen (Nieuwkerken)"). Elke verzendregel met "halen" in de
 * naam (ophalen, afhalen) is daarom één keuze, ook zonder method_id.
 *
 * Lijst (1.2.0): alfabetisch (hoofdletters tellen niet, dus bpost bovenaan),
 * en "Standaard verzendingskosten" (oude shop), "bpost pakketpunt" (staat
 * uit, wacht op bpost) en "Gratis verzending" staan er niet in. Meer namen
 * verbergen: filter `threeducation_verzendfilter_verborgen` (lijst van
 * tekstdelen, hoofdletterongevoelig).
 * Geen vaste bpost-code in de code: het transporteursnummer verschilt per
 * bpost-account. Bestellingen zonder verzendregel (kassa, cadeaubon) vallen
 * onder geen enkele keuze.
 *
 * Aan/uit: WooCommerce → Bestelinstellingen (`3ducation-bestelinstellingen.php`),
 * "Filter op verzendmethode tonen", standaard aan. Uit = geen keuzelijst en
 * geen enkele query-aanpassing. Ontbreekt het instellingenbestand, dan staat
 * de filter aan.
 *
 * Werkt met de HPOS-bestellijst (WooCommerce → Bestellingen,
 * `page=wc-orders`) en met de klassieke lijst (`edit.php?post_type=shop_order`).
 * Alleen lezen: verandert niets aan bestellingen.
 *
 * Afhankelijkheid (zie memory mu-plugins-update-checks): de WooCommerce-hooks
 * `woocommerce_order_list_table_restrict_manage_orders`,
 * `woocommerce_order_list_table_prepare_items_query_args` en
 * `woocommerce_orders_table_query_clauses`, en de tabel
 * `woocommerce_order_items`. Controle na een WooCommerce-update: de
 * keuzelijst staat er, en "bpost @ home" kiezen toont alleen zulke
 * bestellingen (het aantal klopt met het getal in de keuzelijst bij "Alle").
 */

defined( 'ABSPATH' ) || exit;

const THREEDUCATION_VERZENDFILTER_PARAM = '_3du_verzending';

/** Staat de filter aan in WooCommerce → Bestelinstellingen? */
function threeducation_verzendfilter_aan(): bool {
	return ! function_exists( 'threeducation_bestelinstelling' ) || threeducation_bestelinstelling( 'verzendfilter' );
}

/** De gekozen verzendmethode uit de URL, of '' (ook als de filter uit staat). */
function threeducation_verzendfilter_keuze(): string {
	if ( ! threeducation_verzendfilter_aan() ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- alleen lezen, een filter in de lijst.
	return isset( $_GET[ THREEDUCATION_VERZENDFILTER_PARAM ] ) ? sanitize_text_field( wp_unslash( $_GET[ THREEDUCATION_VERZENDFILTER_PARAM ] ) ) : '';
}

/**
 * FROM/JOIN-deel en sleutel-expressie die verzendregels per methode groeperen.
 *
 * Sleutel `afhalen` voor elke regel met "halen" in de naam, anders `<method_id>:<instance_id>`.
 * Andere regels zonder method_id (oude imports) vallen weg via de WHERE-voorwaarde.
 *
 * @return array{0:string,1:string,2:string} [ from_join, key_expression, where ]
 */
function threeducation_verzendfilter_bron(): array {
	global $wpdb;

	$from = "{$wpdb->prefix}woocommerce_order_items oi
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta mid ON mid.order_item_id = oi.order_item_id AND mid.meta_key = 'method_id'
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta iid ON iid.order_item_id = oi.order_item_id AND iid.meta_key = 'instance_id'";
	$key   = "CASE WHEN LOCATE( 'halen', oi.order_item_name ) > 0 THEN 'afhalen' ELSE CONCAT( mid.meta_value, ':', COALESCE( iid.meta_value, '' ) ) END";
	$where = "oi.order_item_type = 'shipping' AND ( LOCATE( 'halen', oi.order_item_name ) > 0 OR ( mid.meta_value IS NOT NULL AND mid.meta_value <> '' ) )";

	return array( $from, $key, $where );
}

/**
 * Alle verzendmethodes op bestellingen, alfabetisch: sleutel => [ naam van de recentste bestelling, aantal bestellingen ].
 *
 * @return array<string,array{0:string,1:int}>
 */
function threeducation_verzendfilter_methodes(): array {
	global $wpdb;

	list( $from, $key, $where ) = threeducation_verzendfilter_bron();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- vaste SQL zonder invoer.
	$rows = $wpdb->get_results(
		"SELECT {$key} AS sleutel,
			SUBSTRING_INDEX( GROUP_CONCAT( oi.order_item_name ORDER BY oi.order_id DESC SEPARATOR '\n' ), '\n', 1 ) AS naam,
			COUNT( DISTINCT oi.order_id ) AS aantal
		FROM {$from}
		WHERE {$where} AND oi.order_item_name <> ''
		GROUP BY sleutel
		ORDER BY aantal DESC, naam ASC
		LIMIT 100"
	);

	$verborgen = (array) apply_filters( 'threeducation_verzendfilter_verborgen', array( 'standaard verzendingskosten', 'bpost pakketpunt', 'gratis verzending' ) );

	$out = array();
	foreach ( (array) $rows as $row ) {
		$naam = (string) $row->naam;
		foreach ( $verborgen as $deel ) {
			if ( '' !== (string) $deel && false !== stripos( $naam, (string) $deel ) ) {
				continue 2;
			}
		}
		$out[ (string) $row->sleutel ] = array( $naam, (int) $row->aantal );
	}

	// Alfabetisch op de getoonde naam, hoofdletters tellen niet ("bpost" vóór "Gratis").
	uasort(
		$out,
		function ( $a, $b ) {
			return strnatcasecmp( $a[0], $b[0] );
		}
	);

	return $out;
}

/** Toont de keuzelijst. */
function threeducation_verzendfilter_dropdown(): void {
	if ( ! threeducation_verzendfilter_aan() ) {
		return;
	}

	$methodes = threeducation_verzendfilter_methodes();
	if ( ! $methodes ) {
		return;
	}

	$keuze = threeducation_verzendfilter_keuze();
	?>
	<label for="filter-by-3du-verzending" class="screen-reader-text"><?php esc_html_e( 'Filter op verzendmethode', '3ducation' ); ?></label>
	<select name="<?php echo esc_attr( THREEDUCATION_VERZENDFILTER_PARAM ); ?>" id="filter-by-3du-verzending">
		<option value=""><?php esc_html_e( 'Alle verzendmethodes', '3ducation' ); ?></option>
		<?php foreach ( $methodes as $sleutel => list( $naam, $aantal ) ) : ?>
			<option value="<?php echo esc_attr( $sleutel ); ?>" <?php selected( $sleutel, $keuze ); ?>>
				<?php echo esc_html( sprintf( '%s (%d)', $naam, $aantal ) ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/** SQL-voorwaarde "deze bestelling heeft een verzendregel met deze methode-sleutel" voor de gegeven id-kolom. */
function threeducation_verzendfilter_sql( string $id_column, string $sleutel ): string {
	global $wpdb;

	list( $from, $key, $where ) = threeducation_verzendfilter_bron();

	return $wpdb->prepare(
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- kolomnaam en vaste SQL uit WooCommerce/WordPress zelf.
		" AND {$id_column} IN ( SELECT oi.order_id FROM {$from} WHERE {$where} AND {$key} = %s )",
		$sleutel
	);
}

/* ---------------------------------------------------------------------
 * HPOS-bestellijst (WooCommerce → Bestellingen)
 * --------------------------------------------------------------------- */

add_action(
	'woocommerce_order_list_table_restrict_manage_orders',
	function ( $order_type = 'shop_order', $which = 'top' ) {
		if ( 'shop_order' === $order_type && 'top' === $which ) {
			threeducation_verzendfilter_dropdown();
		}
	},
	20,
	2
);

// Markeer de query van de lijst, zodat de WHERE-aanvulling alleen daar komt (niet op tellers of andere orderqueries).
add_filter(
	'woocommerce_shop_order_list_table_prepare_items_query_args',
	function ( $args ) {
		$keuze = threeducation_verzendfilter_keuze();
		if ( '' !== $keuze ) {
			$args[ THREEDUCATION_VERZENDFILTER_PARAM ] = $keuze;
		}

		return $args;
	}
);

add_filter(
	'woocommerce_orders_table_query_clauses',
	function ( $clauses, $query, $args ) {
		$sleutel = isset( $args[ THREEDUCATION_VERZENDFILTER_PARAM ] ) ? (string) $args[ THREEDUCATION_VERZENDFILTER_PARAM ] : '';
		if ( '' === $sleutel || ! is_object( $query ) || ! method_exists( $query, 'get_table_name' ) ) {
			return $clauses;
		}

		$clauses['where'] = ( $clauses['where'] ?? '' ) . threeducation_verzendfilter_sql( $query->get_table_name( 'orders' ) . '.id', $sleutel );

		return $clauses;
	},
	10,
	3
);

// Het totaal boven de lijst ("84 items") en het aantal pagina's: WooCommerce neemt een gecachte telling per
// status zolang zijn eigen filters leeg zijn en kijkt daarbij niet naar de onze. Tel dan opnieuw met de filter.
add_filter(
	'woocommerce_shop_order_list_table_order_count',
	function ( $count, $status ) {
		$sleutel = threeducation_verzendfilter_keuze();
		if ( '' === $sleutel ) {
			return $count;
		}

		$result = wc_get_orders(
			array(
				'type'                             => 'shop_order',
				'status'                           => (array) $status,
				'limit'                            => 1,
				'paginate'                         => true,
				'return'                           => 'ids',
				THREEDUCATION_VERZENDFILTER_PARAM => $sleutel,
			)
		);

		return is_object( $result ) && isset( $result->total ) ? (int) $result->total : $count;
	},
	10,
	2
);

/* ---------------------------------------------------------------------
 * Klassieke bestellijst (bestellingen als berichten, zonder HPOS)
 * --------------------------------------------------------------------- */

add_action(
	'restrict_manage_posts',
	function ( $post_type = '', $which = 'top' ) {
		if ( 'shop_order' === $post_type && 'top' === $which ) {
			threeducation_verzendfilter_dropdown();
		}
	},
	20,
	2
);

add_filter(
	'posts_where',
	function ( $where, $query ) {
		global $pagenow, $wpdb;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query instanceof WP_Query || ! $query->is_main_query() || 'shop_order' !== $query->get( 'post_type' ) ) {
			return $where;
		}

		$sleutel = threeducation_verzendfilter_keuze();

		return '' === $sleutel ? $where : $where . threeducation_verzendfilter_sql( "{$wpdb->posts}.ID", $sleutel );
	},
	10,
	2
);
