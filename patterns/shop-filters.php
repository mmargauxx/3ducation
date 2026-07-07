<?php
/**
 * Title: Webshop filters (zijbalk)
 * Slug: 3ducation/shop-filters
 * Categories: 3ducation, woocommerce
 * Description: De volledige filter-zijbalk van de webshop: categorie, merk, kleur, type, prijs en beschikbaarheid. Categorie/merk/prijs/status zijn statisch; kleur en type zijn product-attributen waarvan het numerieke `attributeId` per omgeving verschilt (lokaal vs. live) en hier tijdens het renderen wordt opgezocht.
 *
 * Waarom een pattern i.p.v. losse blokken in de template?
 * - `woocommerce/product-filter-attribute` heeft een numeriek `attributeId` nodig
 *   (het `-taxonomy`-blok ondersteunt geen product-attributen). Dat ID mag niet
 *   hardcoded, want het verschilt per site. PHP lost het hier op via slug/label.
 * - Het hele `product-filters`-blok staat BEWUST samen in één pattern. Zou je
 *   alleen de attribuut-filters via een los `wp:pattern` binnen de template-
 *   `product-filters` plaatsen, dan verliezen ze de `filterParams`-context
 *   (het core/pattern-blok rendert in een verse context) en tonen ze niets.
 */

// Zoek het attribuut-ID op naam/label; ID's verschillen per omgeving.
$threeducation_resolve_attribute_id = static function ( array $candidates, $label_match ) {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return 0;
	}
	foreach ( wc_get_attribute_taxonomies() as $threeducation_tax ) {
		$threeducation_name  = strtolower( $threeducation_tax->attribute_name );
		$threeducation_label = strtolower( trim( $threeducation_tax->attribute_label ) );
		if ( in_array( $threeducation_name, $candidates, true ) || $threeducation_label === $label_match ) {
			return (int) $threeducation_tax->attribute_id;
		}
	}
	return 0;
};

$threeducation_kleur_id = $threeducation_resolve_attribute_id( array( 'kleur', 'color', 'colour' ), 'kleur' );
$threeducation_type_id  = $threeducation_resolve_attribute_id( array( 'type', 'soort', 'producttype', 'product-type' ), 'type' );
?>
<!-- wp:woocommerce/product-filters -->
<div class="wp-block-woocommerce-product-filters"><!-- wp:woocommerce/product-filter-active -->
<div class="wp-block-woocommerce-product-filter-active"><!-- wp:woocommerce/product-filter-clear-button /--></div>
<!-- /wp:woocommerce/product-filter-active -->

<!-- wp:woocommerce/product-filter-taxonomy {"taxonomy":"product_cat"} -->
<div class="wp-block-woocommerce-product-filter-taxonomy"><!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Categorie</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list /--></div>
<!-- /wp:woocommerce/product-filter-taxonomy -->

<!-- wp:woocommerce/product-filter-taxonomy {"taxonomy":"product_brand"} -->
<div class="wp-block-woocommerce-product-filter-taxonomy"><!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Merk</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list /--></div>
<!-- /wp:woocommerce/product-filter-taxonomy -->
<?php if ( $threeducation_kleur_id ) : ?>

<!-- wp:woocommerce/product-filter-attribute {"attributeId":<?php echo (int) $threeducation_kleur_id; ?>} -->
<div class="wp-block-woocommerce-product-filter-attribute"><!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Kleur</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list /--></div>
<!-- /wp:woocommerce/product-filter-attribute -->
<?php endif; ?>
<?php if ( $threeducation_type_id ) : ?>

<!-- wp:woocommerce/product-filter-attribute {"attributeId":<?php echo (int) $threeducation_type_id; ?>} -->
<div class="wp-block-woocommerce-product-filter-attribute"><!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Type</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list /--></div>
<!-- /wp:woocommerce/product-filter-attribute -->
<?php endif; ?>

<!-- wp:woocommerce/product-filter-price -->
<div class="wp-block-woocommerce-product-filter-price"><!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Prijs</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-price-slider /--></div>
<!-- /wp:woocommerce/product-filter-price -->

<!-- wp:woocommerce/product-filter-status -->
<div class="wp-block-woocommerce-product-filter-status"><!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Beschikbaarheid</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list /--></div>
<!-- /wp:woocommerce/product-filter-status --></div>
<!-- /wp:woocommerce/product-filters -->
