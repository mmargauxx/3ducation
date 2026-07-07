<?php
/**
 * Title: Categorie-intro (afbeelding + SEO-omschrijving)
 * Slug: 3ducation/category-intro
 * Categories: 3ducation, woocommerce
 * Description: Toont op een productcategorie-archief de categorie-afbeelding (WooCommerce "Thumbnail") naast de SEO-omschrijving, die als markdown wordt geschreven en via Parsedown naar HTML wordt gerenderd. Lange omschrijvingen klappen in tot ~3 regels met een "Lees meer"-knop (CSS-only, geen JS). Rendert niets op de hoofd-webshoppagina.
 */

if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
	return;
}

$threeducation_term = get_queried_object();
if ( ! $threeducation_term instanceof WP_Term ) {
	return;
}

// Categorie-afbeelding uit de WooCommerce "Thumbnail" (term meta thumbnail_id).
$threeducation_thumb_id = (int) get_term_meta( $threeducation_term->term_id, 'thumbnail_id', true );
$threeducation_image    = $threeducation_thumb_id ? wp_get_attachment_image(
	$threeducation_thumb_id,
	'medium_large',
	false,
	array(
		'class'   => 'category-intro__img',
		'loading' => 'lazy',
		'alt'     => esc_attr( $threeducation_term->name ),
	)
) : '';

// SEO-omschrijving: markdown -> HTML.
$threeducation_desc_html = threeducation_render_markdown( $threeducation_term->description );

if ( '' === $threeducation_image && '' === $threeducation_desc_html ) {
	return;
}

// Alleen inklappen + "Lees meer" tonen als de tekst lang genoeg is (± 3 regels).
$threeducation_plain   = trim( wp_strip_all_tags( $threeducation_desc_html ) );
$threeducation_is_long = ( mb_strlen( $threeducation_plain ) > 180 );
$threeducation_cb_id   = 'cat-desc-toggle-' . (int) $threeducation_term->term_id;
?>
<!-- wp:group {"align":"wide","className":"category-intro","style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}},"layout":{"type":"constrained","wideSize":"1200px"}} -->
<div class="wp-block-group alignwide category-intro" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:html -->
<div class="category-intro__inner<?php echo $threeducation_image ? ' has-image' : ''; ?>">
<?php if ( $threeducation_image ) : ?>
	<div class="category-intro__media"><?php echo wp_kses_post( $threeducation_image ); ?></div>
<?php endif; ?>
<?php if ( '' !== $threeducation_desc_html ) : ?>
	<div class="cat-desc<?php echo $threeducation_is_long ? ' is-clamped' : ''; ?>">
	<?php if ( $threeducation_is_long ) : ?>
		<input type="checkbox" id="<?php echo esc_attr( $threeducation_cb_id ); ?>" class="cat-desc__cb" aria-label="<?php echo esc_attr__( 'Volledige omschrijving tonen', '3ducation' ); ?>" />
	<?php endif; ?>
		<div class="cat-desc__body"><?php echo wp_kses_post( $threeducation_desc_html ); ?></div>
	<?php if ( $threeducation_is_long ) : ?>
		<label for="<?php echo esc_attr( $threeducation_cb_id ); ?>" class="cat-desc__toggle">
			<span class="cat-desc__toggle-more"><?php echo esc_html__( 'Lees meer', '3ducation' ); ?></span>
			<span class="cat-desc__toggle-less"><?php echo esc_html__( 'Lees minder', '3ducation' ); ?></span>
			<svg class="cat-desc__chev" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</label>
	<?php endif; ?>
	</div>
<?php endif; ?>
</div>
<!-- /wp:html --></div>
<!-- /wp:group -->
