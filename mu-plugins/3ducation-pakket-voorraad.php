<?php
/**
 * Plugin Name: 3DUCATION pakketvoorraad = laagste kleur
 * Description: Laat WPC Product Bundles de voorraad van een pakket baseren op de laagste voorraad van álle producten in het pakket, ook die nabesteld kunnen worden. Het pakket is uitverkocht zodra één product op 0 staat.
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Vraag van Patrick (2026-09-16), bv. "Panchroma Matte PLA pakket 8
 * basiskleuren 8x1KG": het pakket stond op 33 (Sapphire Blue) terwijl Sunrise
 * Orange er nog maar 21 had.
 *
 * Oorzaak: WPC Product Bundles (8.6.5) rekent de pakketvoorraad in
 * WC_Product_Woosb::compute_stock_data() (includes/class-product.php) uit met de filter
 * `woosb_stock_quantity_mode`. De standaard `hard_limit` neemt het minimum van
 * de producten die NIET nabesteld kunnen worden; een kleur met "kan nabesteld
 * worden" telt niet mee, dus het pakket bleef verkopen terwijl die kleur in
 * nabestelling ging. De modus `physical` neemt het minimum over álle producten
 * die voorraad beheren. Zolang er minstens één product zonder nabestelling in
 * het pakket zit, staat het pakket zelf op "geen nabestelling" en wordt het
 * dus uitverkocht zodra dat minimum 0 is.
 *
 * Het getal is geen vaste waarde: de plugin berekent het bij elke load en
 * schrijft het terug in `_stock` van het pakket. Met de hand invullen heeft
 * daarom geen zin (behalve als het pakket zelf "voorraad beheren" aan heeft
 * en dat getal lager is — dan wint dat).
 *
 * Geldt voor álle pakketten (producttype `woosb`). Composite Products
 * (`wpc-composite-products`) gebruikt deze filter niet.
 *
 * Afhankelijkheid (zie memory mu-plugins-update-checks): de filter
 * `woosb_stock_quantity_mode` van WPC Product Bundles. Verdwijnt of hernoemt
 * die bij een update, dan valt het pakket stil terug op `hard_limit`.
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'woosb_stock_quantity_mode',
	function () {
		return 'physical';
	}
);
