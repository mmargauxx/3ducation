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

// Highlight the chip for the category currently being viewed (if any), so the
// row doubles as a "you are here" wayfinder on category archives.
$current_cat_id = 0;
if ( function_exists( 'is_product_category' ) && is_product_category() ) {
	$queried = get_queried_object();
	if ( $queried instanceof WP_Term ) {
		$current_cat_id = (int) $queried->term_id;
	}
}
?>
<!-- wp:group {"align":"wide","className":"cat-chips-wrap","style":{"spacing":{"padding":{"top":"var:preset|spacing|30"}}},"layout":{"type":"constrained","wideSize":"1200px"}} -->
<div class="wp-block-group alignwide cat-chips-wrap" style="padding-top:var(--wp--preset--spacing--30)"><!-- wp:html -->
<nav class="cat-chips category-chips-container" aria-label="<?php echo esc_attr__( 'Productcategorieën', '3ducation' ); ?>">
<?php
$i = 0;
foreach ( $terms as $term ) :
	$accent    = $accents[ $i % 3 ];
	$link      = esc_url( get_term_link( $term ) );
	$count     = number_format_i18n( (int) $term->count );
	$is_active = ( (int) $term->term_id === $current_cat_id );
	$classes   = 'cat-chip cat-chip--' . $accent . ( $is_active ? ' is-active' : '' );
	++$i;
	?>
	<a class="<?php echo esc_attr( $classes ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?> href="<?php echo $link; ?>"><span class="cat-chip__name"><?php echo esc_html( $term->name ); ?></span> <span class="cat-chip__count"><?php echo esc_html( $count ); ?></span></a>
<?php endforeach; ?>
</nav>
<!-- /wp:html --></div>
<!-- /wp:group -->
