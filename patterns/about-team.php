<?php
/**
 * Title: Over ons — team
 * Slug: 3ducation/about-team
 * Categories: 3ducation
 * Description: "Het team achter 3DUCATION" — a grid of team members with photo placeholders (drop in real portraits before go-live).
 *
 * Reuses the about-gallery tile styling (.about-gallery__item / __ph striped
 * fill) so the placeholders match the social-proof gallery language; adds only
 * the .about-team grid and the name/role caption.
 */
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
<div class="wp-block-group about-team"><!-- wp:group {"className":"about-gallery__item about-team__item","layout":{"type":"default"}} -->
<div class="wp-block-group about-gallery__item about-team__item"><!-- wp:image {"className":"about-team__photo","sizeSlug":"large"} -->
<figure class="wp-block-image size-large about-team__photo"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/team-natalie.jpg' ) ); ?>" alt="<?php echo esc_attr__( 'Natalie Verbeke', '3ducation' ); ?>"/></figure>
<!-- /wp:image -->
<!-- wp:group {"className":"about-team__cap","layout":{"type":"default"}} -->
<div class="wp-block-group about-team__cap"><!-- wp:paragraph {"className":"about-team__name"} -->
<p class="about-team__name"><?php echo esc_html__( 'Natalie Verbeke', '3ducation' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"about-team__role"} -->
<p class="about-team__role"><?php echo esc_html__( 'Winkelmedewerker', '3ducation' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"about-gallery__item about-team__item","layout":{"type":"default"}} -->
<div class="wp-block-group about-gallery__item about-team__item"><!-- wp:image {"className":"about-team__photo","sizeSlug":"large"} -->
<figure class="wp-block-image size-large about-team__photo"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/team-patrick.jpg' ) ); ?>" alt="<?php echo esc_attr__( 'Patrick Smet', '3ducation' ); ?>"/></figure>
<!-- /wp:image -->
<!-- wp:group {"className":"about-team__cap","layout":{"type":"default"}} -->
<div class="wp-block-group about-team__cap"><!-- wp:paragraph {"className":"about-team__name"} -->
<p class="about-team__name"><?php echo esc_html__( 'Patrick Smet', '3ducation' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"about-team__role"} -->
<p class="about-team__role"><?php echo esc_html__( 'Zaakvoerder', '3ducation' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"about-gallery__item about-team__item","layout":{"type":"default"}} -->
<div class="wp-block-group about-gallery__item about-team__item"><!-- wp:image {"className":"about-team__photo","sizeSlug":"large"} -->
<figure class="wp-block-image size-large about-team__photo"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/team-cato.jpg' ) ); ?>" alt="<?php echo esc_attr__( 'Cato Smet', '3ducation' ); ?>"/></figure>
<!-- /wp:image -->
<!-- wp:group {"className":"about-team__cap","layout":{"type":"default"}} -->
<div class="wp-block-group about-team__cap"><!-- wp:paragraph {"className":"about-team__name"} -->
<p class="about-team__name"><?php echo esc_html__( 'Cato Smet', '3ducation' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"about-team__role"} -->
<p class="about-team__role"><?php echo esc_html__( '3D-print expert onderwijs', '3ducation' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
