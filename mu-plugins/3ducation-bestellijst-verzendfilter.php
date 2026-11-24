<?php
/**
 * Plugin Name: 3DUCATION bestellijst: filter op verzendmethode
 * Description: Keuzelijst "Alle verzendmethodes" boven de WooCommerce-bestellijst, om bijvoorbeeld alleen de bpost @ home-bestellingen te tonen.
 * Version: 1.0.0
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
 * method_id (oude geïmporteerde bestellingen) worden op hun naam gegroepeerd.
 * Geen vaste bpost-code in de code: het transporteursnummer verschilt per
 * bpost-account. Bestellingen zonder verzendregel (kassa, cadeaubon) vallen
 * onder geen enkele keuze.
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

/** De gekozen verzendmethode uit de URL, of ''. */
function threeducation_verzendfilter_keuze(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- alleen lezen, een filter in de lijst.
	return isset( $_GET[ THREEDUCATION_VERZENDFILTER_PARAM ] ) ? sanitize_text_field( wp_unslash( $_GET[ THREEDUCATION_VERZENDFILTER_PARAM ] ) ) : '';
}

/**
 * FROM/JOIN-deel en sleutel-expressie die verzendregels per methode groeperen.
 *
 * Sleutel `m:<method_id>:<instance_id>` als de regel een method_id heeft, anders `n:<naam>`.
 *
 * @return array{0:string,1:string} [ from_join, key_expression ]
 */
function threeducation_verzendfilter_bron(): array {
	global $wpdb;

	$from = "{$wpdb->prefix}woocommerce_order_items oi
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta mid ON mid.order_item_id = oi.order_item_id AND mid.meta_key = 'method_id'
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta iid ON iid.order_item_id = oi.order_item_id AND iid.meta_key = 'instance_id'";
	$key  = "COALESCE( CONCAT( 'm:', NULLIF( mid.meta_value, '' ), ':', COALESCE( iid.meta_value, '0' ) ), CONCAT( 'n:', oi.order_item_name ) )";

	return array( $from, $key );
}

/**
 * Alle verzendmethodes op bestellingen: sleutel => [ naam van de recentste bestelling, aantal bestellingen ].
 *
 * @return array<string,array{0:string,1:int}>
 */
function threeducation_verzendfilter_methodes(): array {
	global $wpdb;

	list( $from, $key ) = threeducation_verzendfilter_bron();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- vaste SQL zonder invoer.
	$rows = $wpdb->get_results(
		"SELECT {$key} AS sleutel,
			SUBSTRING_INDEX( GROUP_CONCAT( oi.order_item_name ORDER BY oi.order_id DESC SEPARATOR '\n' ), '\n', 1 ) AS naam,
			COUNT( DISTINCT oi.order_id ) AS aantal
		FROM {$from}
		WHERE oi.order_item_type = 'shipping' AND oi.order_item_name <> ''
		GROUP BY sleutel
		ORDER BY aantal DESC, naam ASC
		LIMIT 100"
	);

	$out = array();
	foreach ( (array) $rows as $row ) {
		$out[ (string) $row->sleutel ] = array( (string) $row->naam, (int) $row->aantal );
	}

	return $out;
}

/** Toont de keuzelijst. */
function threeducation_verzendfilter_dropdown(): void {
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

	list( $from, $key ) = threeducation_verzendfilter_bron();

	return $wpdb->prepare(
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- kolomnaam en vaste SQL uit WooCommerce/WordPress zelf.
		" AND {$id_column} IN ( SELECT oi.order_id FROM {$from} WHERE oi.order_item_type = 'shipping' AND {$key} = %s )",
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
