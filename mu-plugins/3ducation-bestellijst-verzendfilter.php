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
 * lijst alleen bestellingen met een verzendregel met die naam. Werkt samen
 * met de statustabs, de zoekfunctie en de andere filters.
 *
 * Waarom op naam en niet op de bpost-code: de bpost-plugin bewaart als
 * method_id `shipping_Bpost_<transporteur>_weight`, met een nummer dat per
 * bpost-account verschilt; de naam is wat Patrick in de lijst en op de
 * bestelling ziet. Gevolg: hernoem je een verzendmethode (zoals "Zelf
 * Ophalen" → "Zelf Afhalen" op 2026-11-24), dan staan oude en nieuwe
 * bestellingen als twee aparte keuzes in de lijst. Bestellingen zonder
 * verzendregel (kassa, cadeaubon) vallen onder geen enkele keuze.
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
 * Alle verzendmethodenamen op bestellingen, met het aantal bestellingen, meest gebruikte eerst.
 *
 * @return array<string,int>
 */
function threeducation_verzendfilter_methodes(): array {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- eenmalig per lijstweergave, geen cache nodig.
	$rows = $wpdb->get_results(
		"SELECT order_item_name AS naam, COUNT( DISTINCT order_id ) AS aantal
		FROM {$wpdb->prefix}woocommerce_order_items
		WHERE order_item_type = 'shipping' AND order_item_name <> ''
		GROUP BY order_item_name
		ORDER BY aantal DESC, naam ASC
		LIMIT 100"
	);

	$out = array();
	foreach ( (array) $rows as $row ) {
		$out[ (string) $row->naam ] = (int) $row->aantal;
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
		<?php foreach ( $methodes as $naam => $aantal ) : ?>
			<option value="<?php echo esc_attr( $naam ); ?>" <?php selected( $naam, $keuze ); ?>>
				<?php echo esc_html( sprintf( '%s (%d)', $naam, $aantal ) ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/** SQL-voorwaarde "deze bestelling heeft een verzendregel met deze naam" voor de gegeven id-kolom. */
function threeducation_verzendfilter_sql( string $id_column, string $naam ): string {
	global $wpdb;

	return $wpdb->prepare(
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- kolomnaam komt uit WooCommerce/WordPress zelf.
		" AND {$id_column} IN ( SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type = 'shipping' AND order_item_name = %s )",
		$naam
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
		$naam = isset( $args[ THREEDUCATION_VERZENDFILTER_PARAM ] ) ? (string) $args[ THREEDUCATION_VERZENDFILTER_PARAM ] : '';
		if ( '' === $naam || ! is_object( $query ) || ! method_exists( $query, 'get_table_name' ) ) {
			return $clauses;
		}

		$clauses['where'] = ( $clauses['where'] ?? '' ) . threeducation_verzendfilter_sql( $query->get_table_name( 'orders' ) . '.id', $naam );

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
		$naam = threeducation_verzendfilter_keuze();
		if ( '' === $naam ) {
			return $count;
		}

		$result = wc_get_orders(
			array(
				'type'                             => 'shop_order',
				'status'                           => (array) $status,
				'limit'                            => 1,
				'paginate'                         => true,
				'return'                           => 'ids',
				THREEDUCATION_VERZENDFILTER_PARAM => $naam,
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

		$naam = threeducation_verzendfilter_keuze();

		return '' === $naam ? $where : $where . threeducation_verzendfilter_sql( "{$wpdb->posts}.ID", $naam );
	},
	10,
	2
);
