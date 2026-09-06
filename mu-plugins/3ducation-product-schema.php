<?php
/**
 * Plugin Name: 3DUCATION productschema herstellen
 * Description: Zet de Product-structured-data van WooCommerce terug op productpagina's. SureRank haalt die weg om er eigen schema voor in de plaats te zetten, maar levert zelf geen Product-schema. Zonder dit bestand staan er geen prijs, voorraad, SKU of merk in Google.
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Achtergrond: SureRank draait in inc/schema/products.php op wp_footer (prioriteit 0)
 * een remove_action op WC()->structured_data->output_structured_data. De gegevens
 * worden nog wél opgebouwd (WC_Structured_Data::generate_product_data hangt aan
 * woocommerce_single_product_summary), alleen het uitschrijven verdwijnt.
 *
 * Wat dit doet: op enkelvoudige productpagina's schrijven we die data alsnog weg,
 * op wp_footer prioriteit 20 — dus ná SureRank's verwijdering en ná het opbouwen.
 * WooCommerce vult prijs, valuta, voorraadstatus, SKU, merk en beoordelingen zelf in,
 * voor alle producten tegelijk. Er is geen instelling per product nodig.
 *
 * Wordt SureRank ooit vervangen of gaat SureRank zelf wél Product-schema uitschrijven,
 * verwijder dit bestand dan: twee keer Product-schema op één pagina is rommelig.
 * Tijdelijk uitzetten kan ook met de filter hieronder:
 *
 *     add_filter( 'threeducation_herstel_productschema', '__return_false' );
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Geen activatiestap.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;

/**
 * Schrijf de Product-structured-data van WooCommerce alsnog weg in de voettekst.
 *
 * @return void
 */
function threeducation_herstel_productschema() {
	if ( ! function_exists( 'WC' ) || ! function_exists( 'is_product' ) ) {
		return;
	}
	if ( ! is_product() ) {
		return;
	}
	if ( ! apply_filters( 'threeducation_herstel_productschema', true ) ) {
		return;
	}

	$structured_data = WC()->structured_data;
	if ( ! is_object( $structured_data ) || ! method_exists( $structured_data, 'get_structured_data' ) ) {
		return;
	}

	// Doet WooCommerce het zelf nog? Dan is er niets weggehaald en schrijven wij niets:
	// twee keer Product-schema op één pagina is erger dan geen enkele keer. Zo blijft dit
	// bestand ook onschadelijk als SureRank ooit uitgezet of vervangen wordt.
	if ( false !== has_action( 'wp_footer', array( $structured_data, 'output_structured_data' ) ) ) {
		return;
	}

	// Alleen het Product-gedeelte opvragen: breadcrumbs, website en organisatie
	// levert SureRank al, die willen we hier niet dubbel hebben.
	$data = $structured_data->get_structured_data( array( 'product' ) );
	if ( empty( $data ) ) {
		return;
	}

	// Zelfde codering en escaping als WooCommerce zelf gebruikt in output_structured_data():
	// JSON_HEX_TAG tegen </script>-injectie, en $html = true zodat aanhalingstekens
	// niet als &quot; in de JSON belanden (dat maakt de JSON-LD onleesbaar voor Google).
	echo '<script type="application/ld+json">' . wc_esc_json( wp_json_encode( $data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ), true ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'threeducation_herstel_productschema', 20 );
