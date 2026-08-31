<?php
/**
 * Title: Over ons — team
 * Slug: 3ducation/about-team
 * Categories: 3ducation
 * Description: "Het team achter 3DUCATION" — de teamleden met portret, naam en functie.
 *
 * Reuses the about-gallery tile styling (.about-gallery__item) so the cards match
 * the social-proof gallery language; adds only the .about-team row and the
 * name/role caption. The row wraps and centres, so five members render as
 * three + two centred underneath — add or remove an entry in $members below and
 * the layout re-balances by itself (no CSS change needed).
 *
 * Portraits are theme files in assets/images/ so they travel with the theme
 * and survive every deploy. The filenames carry the shoot year: images are not
 * version-busted the way custom.css is, so reusing a filename leaves returning
 * visitors looking at the browser-cached old photo. New photo = new filename. The open slot points at the striped "Foto volgt"
 * placeholder; drop a real portrait in assets/images/ and change the filename
 * here. Do NOT swap these through the Site Editor: saving there freezes a copy
 * of this whole template in the database, which masks later theme updates.
 */

$members = array(
	array(
		'image' => 'team-natalie-2026.jpg',
		'name'  => __( 'Natalie Verbeke', '3ducation' ),
		'role'  => __( 'Winkelmedewerker', '3ducation' ),
	),
	array(
		'image' => 'team-patrick-2026.jpg',
		'name'  => __( 'Patrick Smet', '3ducation' ),
		'role'  => __( 'Zaakvoerder', '3ducation' ),
	),
	array(
		'image' => 'team-cato-2026.jpg',
		'name'  => __( 'Cato Smet', '3ducation' ),
		'role'  => __( '3D-print expert onderwijs', '3ducation' ),
	),
	array(
		'image' => 'team-tibo-2026.jpg',
		'name'  => __( 'Tibo Smet', '3ducation' ),
		'role'  => __( 'Technisch medewerker', '3ducation' ),
	),
	array(
		'image' => 'team-placeholder.png',
		'name'  => __( 'Joren Van der Meiren', '3ducation' ),
		'role'  => __( 'Technisch medewerker', '3ducation' ),
	),
);
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:paragraph {"className":"print-eyebrow print-eyebrow--magenta","fontSize":"small","fontFamily":"display"} -->
<p class="print-eyebrow print-eyebrow--magenta has-display-font-family has-small-font-size">Het team</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Het team achter 3DUCATION</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|ink-soft"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--ink-soft)">Maak kennis met de mensen die jou met veel enthousiasme verder helpen op school, thuis en aan de werkbank.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|40"} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"className":"about-team","layout":{"type":"default"}} -->
<div class="wp-block-group about-team">
<?php foreach ( $members as $member ) : ?>
<!-- wp:group {"className":"about-gallery__item about-team__item","layout":{"type":"default"}} -->
<div class="wp-block-group about-gallery__item about-team__item"><!-- wp:image {"className":"about-team__photo","sizeSlug":"large"} -->
<figure class="wp-block-image size-large about-team__photo"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/' . $member['image'] ) ); ?>" alt="<?php echo esc_attr( $member['name'] ); ?>"/></figure>
<!-- /wp:image -->
<!-- wp:group {"className":"about-team__cap","layout":{"type":"default"}} -->
<div class="wp-block-group about-team__cap"><!-- wp:paragraph {"className":"about-team__name"} -->
<p class="about-team__name"><?php echo esc_html( $member['name'] ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"about-team__role"} -->
<p class="about-team__role"><?php echo esc_html( $member['role'] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
