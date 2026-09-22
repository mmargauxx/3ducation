<?php
/**
 * Plugin Name: 3DUCATION pakketvoorraad bij kassaverkoop
 * Description: Haalt bij een pakket dat zonder de losse producten in een bestelling staat (WCPOS-kassa, handmatige bestelling) elk product van het pakket uit de voorraad, en zet het terug bij annuleren of terugbetalen met "terug op voorraad".
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Melding van Patrick (2026-09-22): bestelling #14442 (kassa, "Panchroma
 * Matte PLA pakket 8 basiskleuren 8x1KG") haalde de acht kleuren niet uit de
 * voorraad; #14437 (webshop, "Pakket 6 basiskleuren") wel.
 *
 * Oorzaak: WPC Product Bundles (8.6.5) verlaagt zelf geen voorraad. In de
 * webshop-winkelwagen voegt hij elk product van het pakket als eigen regel
 * toe (hook `woocommerce_add_to_cart` → add_to_cart_items()), en die regels
 * verlaagt WooCommerce gewoon. Een bestelling die niet door de winkelwagen
 * gaat (WCPOS, "Bestelling toevoegen" in wp-admin, REST) heeft alleen de
 * pakketregel, dus bleven de kleuren staan.
 *
 * Werking:
 * - Na WooCommerce's eigen voorraadverlaging (`woocommerce_reduce_order_stock`)
 *   zoekt dit elke pakketregel (producttype `woosb`) zonder WPC's eigen
 *   `_woosb_ids`-meta en zonder kindregels (`_woosb_parent_id`). Voor zo'n
 *   regel gaat elk product van het pakket omlaag met (aantal in pakket ×
 *   aantal pakketten). Wat er verlaagd is, staat op de regel in
 *   `_3du_pakket_voorraad` (samenstelling per stuk + aantal pakketten), zodat
 *   terugzetten dezelfde producten raakt, ook als het pakket later wijzigt,
 *   en zodat niets twee keer gebeurt.
 * - Annuleren / mislukt / terug naar in afwachting
 *   (`woocommerce_restore_order_stock`) zet de producten terug.
 * - Terugbetaling met "terug op voorraad" (filter
 *   `woocommerce_can_restock_refunded_items`, die WooCommerce vlak voor het
 *   terugzetten aanroept) zet per terugbetaald pakket de producten terug.
 * - Bestelacties in wp-admin ("Pakketvoorraad verlagen"): om een oudere
 *   kassabestelling (zoals #14442) alsnog te verwerken. Verschijnt alleen als
 *   de bestelling een onverwerkte pakketregel heeft.
 *
 * Webshopbestellingen worden niet aangeraakt: die hebben kindregels.
 *
 * Afhankelijkheid (zie memory mu-plugins-update-checks): WPC Product Bundles
 * (`WC_Product_Woosb::get_items()`, order-item-meta `_woosb_ids` /
 * `_woosb_parent_id`) en WooCommerce's voorraadhooks.
 */

defined( 'ABSPATH' ) || exit;

const THREEDUCATION_PAKKET_META = '_3du_pakket_voorraad';

/**
 * Pakketregels van een bestelling die nog niet verwerkt zijn: producttype
 * woosb, geen WPC-kindregels, nog geen eigen meta.
 *
 * @return WC_Order_Item_Product[]
 */
function threeducation_pakket_onverwerkte_regels( WC_Order $order ): array {
	$items   = $order->get_items();
	$ouders  = [];
	$regels  = [];

	foreach ( $items as $item ) {
		$ouder = $item->get_meta( '_woosb_parent_id' );
		if ( $ouder ) {
			$ouders[ (int) $ouder ] = true;
		}
	}

	foreach ( $items as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}
		$product = $item->get_product();
		if ( ! $product || 'woosb' !== $product->get_type() ) {
			continue;
		}
		if ( $item->get_meta( '_woosb_ids' ) || isset( $ouders[ $item->get_product_id() ] ) || $item->meta_exists( THREEDUCATION_PAKKET_META ) ) {
			continue;
		}
		$regels[] = $item;
	}

	return $regels;
}

/**
 * Samenstelling van een pakket: product-ID → aantal per pakket, alleen
 * producten die voorraad beheren (geen geneste pakketten).
 *
 * @return array<int,float>
 */
function threeducation_pakket_samenstelling( WC_Product $pakket ): array {
	if ( ! method_exists( $pakket, 'get_items' ) ) {
		return [];
	}

	$per = [];
	foreach ( (array) $pakket->get_items() as $onderdeel ) {
		$id  = (int) ( $onderdeel['id'] ?? 0 );
		$qty = (float) ( $onderdeel['qty'] ?? 0 );
		if ( ! $id || $qty <= 0 ) {
			continue;
		}
		$product = wc_get_product( $id );
		if ( ! $product || 'woosb' === $product->get_type() || ! $product->managing_stock() ) {
			continue;
		}
		$per[ $id ] = ( $per[ $id ] ?? 0 ) + $qty;
	}

	return $per;
}

/**
 * Verhoogt of verlaagt de producten van een pakketregel en geeft de
 * notitieregels terug ("Naam (SKU) 12→11").
 *
 * @param array<int,float> $per
 */
function threeducation_pakket_pas_voorraad_aan( array $per, float $aantal, string $richting ): array {
	$notities = [];
	foreach ( $per as $id => $qty ) {
		$product = wc_get_product( $id );
		if ( ! $product || ! $product->managing_stock() ) {
			continue;
		}
		$oud  = $product->get_stock_quantity();
		$nieuw = wc_update_product_stock( $product, wc_stock_amount( $qty * $aantal ), $richting );
		if ( is_wp_error( $nieuw ) || false === $nieuw ) {
			continue;
		}
		$notities[] = sprintf( '%s %s→%s', $product->get_formatted_name(), $oud, $nieuw );
		if ( 'decrease' === $richting && function_exists( 'wc_trigger_stock_change_actions' ) ) {
			wc_trigger_stock_change_actions( wc_get_product( $id ) );
		}
	}
	return $notities;
}

/**
 * Verlaagt de producten van alle onverwerkte pakketregels.
 */
function threeducation_pakket_verlaag( WC_Order $order ): int {
	if ( 'yes' !== get_option( 'woocommerce_manage_stock' ) ) {
		return 0;
	}

	$verwerkt = 0;
	foreach ( threeducation_pakket_onverwerkte_regels( $order ) as $item ) {
		$per = threeducation_pakket_samenstelling( $item->get_product() );
		if ( ! $per ) {
			continue;
		}
		$aantal = (float) $item->get_quantity();

		// Eerst de meta, dan de voorraad: een tweede aanroep halverwege slaat
		// deze regel dan over in plaats van dubbel te verlagen.
		$item->update_meta_data( THREEDUCATION_PAKKET_META, [ 'per' => $per, 'aantal' => $aantal ] );
		$item->save();

		$notities = threeducation_pakket_pas_voorraad_aan( $per, $aantal, 'decrease' );
		if ( $notities ) {
			$order->add_order_note(
				sprintf(
					/* translators: 1: pakketnaam, 2: lijst "product oud→nieuw" */
					__( 'Voorraad van de producten in pakket "%1$s" verlaagd: %2$s', '3ducation' ),
					$item->get_name(),
					implode( ', ', $notities )
				)
			);
		}
		++$verwerkt;
	}

	return $verwerkt;
}

add_action(
	'woocommerce_reduce_order_stock',
	function ( $order ) {
		if ( $order instanceof WC_Order ) {
			threeducation_pakket_verlaag( $order );
		}
	}
);

// Annuleren, mislukt of terug naar in afwachting: alles terugzetten wat nog verlaagd staat.
add_action(
	'woocommerce_restore_order_stock',
	function ( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			$data = $item->get_meta( THREEDUCATION_PAKKET_META );
			if ( ! is_array( $data ) || empty( $data['per'] ) || empty( $data['aantal'] ) ) {
				continue;
			}
			$notities = threeducation_pakket_pas_voorraad_aan( $data['per'], (float) $data['aantal'], 'increase' );
			$item->delete_meta_data( THREEDUCATION_PAKKET_META );
			$item->save();
			if ( $notities ) {
				$order->add_order_note(
					sprintf(
						/* translators: 1: pakketnaam, 2: lijst "product oud→nieuw" */
						__( 'Voorraad van de producten in pakket "%1$s" teruggezet: %2$s', '3ducation' ),
						$item->get_name(),
						implode( ', ', $notities )
					)
				);
			}
		}
	}
);

// Terugbetaling met "terug op voorraad": per terugbetaald pakket de producten terugzetten.
add_filter(
	'woocommerce_can_restock_refunded_items',
	function ( $kan, $order, $terugbetaald ) {
		if ( ! $kan || ! $order instanceof WC_Order || ! is_array( $terugbetaald ) ) {
			return $kan;
		}
		foreach ( $order->get_items() as $item_id => $item ) {
			$data = $item->get_meta( THREEDUCATION_PAKKET_META );
			$qty  = (float) ( $terugbetaald[ $item_id ]['qty'] ?? 0 );
			if ( ! is_array( $data ) || empty( $data['per'] ) || empty( $data['aantal'] ) || $qty <= 0 ) {
				continue;
			}
			$qty      = min( $qty, (float) $data['aantal'] );
			$notities = threeducation_pakket_pas_voorraad_aan( $data['per'], $qty, 'increase' );

			$data['aantal'] = (float) $data['aantal'] - $qty;
			$item->update_meta_data( THREEDUCATION_PAKKET_META, $data );
			$item->save();

			if ( $notities ) {
				$order->add_order_note(
					sprintf(
						/* translators: 1: pakketnaam, 2: lijst "product oud→nieuw" */
						__( 'Voorraad van de producten in pakket "%1$s" teruggezet na terugbetaling: %2$s', '3ducation' ),
						$item->get_name(),
						implode( ', ', $notities )
					)
				);
			}
		}
		return $kan;
	},
	10,
	3
);

// Bestelactie in wp-admin om een oudere kassabestelling alsnog te verwerken.
add_filter(
	'woocommerce_order_actions',
	function ( $acties, $order = null ) {
		if ( $order instanceof WC_Order && threeducation_pakket_onverwerkte_regels( $order ) ) {
			$acties['3du_pakket_voorraad'] = __( 'Pakketvoorraad verlagen (producten van het pakket uit de voorraad halen)', '3ducation' );
		}
		return $acties;
	},
	10,
	2
);

add_action(
	'woocommerce_order_action_3du_pakket_voorraad',
	function ( $order ) {
		if ( $order instanceof WC_Order && current_user_can( 'edit_shop_orders' ) ) {
			threeducation_pakket_verlaag( $order );
		}
	}
);
