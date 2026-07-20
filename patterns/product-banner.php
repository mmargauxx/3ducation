<?php
/**
 * Title: Product spotlight (drie producten)
 * Slug: 3ducation/product-banner
 * Categories: 3ducation, banner, woocommerce
 * Description: A three-up spotlight of hand-picked products at the top of the shop. On a category archive it shows that category's own spotlights (Producten -> Categorieen), otherwise the global picks (Instellingen -> Uitgelichte producten); falls back to the three newest products.
 */

if ( ! function_exists( 'wc_get_product' ) ) {
	return;
}
// Op een categorie-archief tonen we de spotlights van díe categorie (met
// terugval op de globale selectie); overal elders de globale selectie.
$spotlights = threeducation_spotlight_products();
// De sectie-eyebrow: op een categorie-archief prefixen we de categorienaam
// ("Filamenten Uitgelicht"), overal elders gewoon "Uitgelicht".
$eyebrow_label = esc_html__( 'Uitgelicht', '3ducation' );
if ( function_exists( 'is_product_category' ) && is_product_category() ) {
	$queried = get_queried_object();
	if ( $queried instanceof WP_Term ) {
		$spotlights    = threeducation_get_category_spotlight_products( $queried->term_id );
		$eyebrow_label = esc_html( $queried->name ) . ' ' . esc_html__( 'Uitgelicht', '3ducation' );
	}
}
if ( empty( $spotlights ) ) {
	return;
}
$accents = array( 'cyan', 'cyan', 'cyan' );
?>
<!-- wp:group {"align":"wide","className":"spotlights-wrap","style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignwide spotlights-wrap" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)"><!-- wp:paragraph {"className":"print-eyebrow print-eyebrow--cyan","fontSize":"small","fontFamily":"display"} -->
<p class="print-eyebrow print-eyebrow--cyan has-display-font-family has-small-font-size"><?php echo $eyebrow_label; // reeds ge-escaped ?></p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<div class="spotlights">
<?php
$i = 0;
foreach ( $spotlights as $product ) :
	$accent    = $accents[ $i % 3 ];
	++$i;
	$permalink = get_permalink( $product->get_id() );
	$title     = $product->get_name();
	$price     = $product->get_price_html();
	$image     = $product->get_image( 'woocommerce_thumbnail' );
	$eyebrow   = '';
	$terms     = get_the_terms( $product->get_id(), 'product_cat' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$eyebrow = $terms[0]->name;
	}
	?>
	<a class="spotlight-card spotlight-card--<?php echo esc_attr( $accent ); ?>" href="<?php echo esc_url( $permalink ); ?>">
		<span class="spotlight-card__media"><?php echo wp_kses_post( $image ); ?></span>
		<span class="spotlight-card__body">
			<?php if ( '' !== $eyebrow ) : ?>
				<span class="spotlight-card__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
			<?php endif; ?>
			<span class="spotlight-card__title"><?php echo esc_html( $title ); ?></span>
			<?php if ( '' !== $price ) : ?>
				<span class="spotlight-card__price"><?php echo wp_kses_post( $price ); ?></span>
			<?php endif; ?>
			<span class="spotlight-card__cta"><?php echo esc_html__( 'Bekijk product', '3ducation' ); ?> &rarr;</span>
		</span>
	</a>
<?php endforeach; ?>
</div>
<!-- /wp:html --></div>
<!-- /wp:group -->
