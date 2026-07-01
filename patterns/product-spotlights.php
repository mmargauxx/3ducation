<?php
/**
 * Title: Category chips (shop)
 * Slug: 3ducation/product-spotlights
 * Categories: 3ducation, woocommerce
 * Description: A compact, wrapping row of category chips (name + product count) for the nine most-populated product categories, color-coded in the brand tricolor. Kept small on purpose so the product grid leads.
 */

// The nine most-populated top-level product categories, Uncategorized aside.
// Rendered dynamically so the row keeps pace with the catalog.
$default_cat = (int) get_option( 'default_product_cat' );
$terms       = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'parent'     => 0,
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'exclude'    => array_filter( array( $default_cat ) ),
		'number'     => 9,
	)
);
if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}

$accents = array( 'magenta', 'cyan', 'amber' );
?>
<!-- wp:group {"align":"wide","className":"cat-chips-wrap","style":{"spacing":{"padding":{"top":"var:preset|spacing|30"}}},"layout":{"type":"constrained","wideSize":"1200px"}} -->
<div class="wp-block-group alignwide cat-chips-wrap" style="padding-top:var(--wp--preset--spacing--30)"><!-- wp:html -->
<nav class="cat-chips" aria-label="<?php echo esc_attr__( 'Productcategorieën', '3ducation' ); ?>">
<?php
$i = 0;
foreach ( $terms as $term ) :
	$accent = $accents[ $i % 3 ];
	$link   = esc_url( get_term_link( $term ) );
	$count  = number_format_i18n( (int) $term->count );
	++$i;
	?>
	<a class="cat-chip cat-chip--<?php echo esc_attr( $accent ); ?>" href="<?php echo $link; ?>"><span class="cat-chip__name"><?php echo esc_html( $term->name ); ?></span> <span class="cat-chip__count"><?php echo esc_html( $count ); ?></span></a>
<?php endforeach; ?>
</nav>
<!-- /wp:html --></div>
<!-- /wp:group -->
