<?php
/**
 * Title: Shop subkop
 * Slug: 3ducation/shop-subhead
 * Categories: 3ducation, woocommerce
 * Description: A one-line descriptive subheading under the shop H1. Shown only on the main webshop archive; category archives already carry their own term description under the title.
 */

// Alleen op de hoofd-webshoppagina. Op een categorie-archief staat onder de H1
// al de eigen term-omschrijving, dus daar tonen we geen generieke subkop.
if ( ! function_exists( 'is_shop' ) || ! is_shop() ) {
	return;
}
?>
<!-- wp:paragraph {"className":"shop-subhead","style":{"color":{"text":"var:preset|color|ink-soft"}},"fontSize":"large"} -->
<p class="shop-subhead has-text-color has-large-font-size" style="color:var(--wp--preset--color--ink-soft)">Printers, filament, onderdelen &amp; workshops</p>
<!-- /wp:paragraph -->
