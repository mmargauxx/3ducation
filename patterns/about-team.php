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

<!-- wp:html -->
<div class="about-team">
	<figure class="about-gallery__item about-team__item"><span class="about-gallery__ph" aria-hidden="true">Foto</span><figcaption class="about-team__cap"><span class="about-team__name"><?php echo esc_html__( 'Patrick Smet', '3ducation' ); ?></span><span class="about-team__role"><?php echo esc_html__( 'Oprichter &amp; 3D-expert', '3ducation' ); ?></span></figcaption></figure>
	<figure class="about-gallery__item about-team__item"><span class="about-gallery__ph" aria-hidden="true">Foto</span><figcaption class="about-team__cap"><span class="about-team__name"><?php echo esc_html__( '[Naam 2]', '3ducation' ); ?></span><span class="about-team__role"><?php echo esc_html__( 'Workshops &amp; Educatie', '3ducation' ); ?></span></figcaption></figure>
	<figure class="about-gallery__item about-team__item"><span class="about-gallery__ph" aria-hidden="true">Foto</span><figcaption class="about-team__cap"><span class="about-team__name"><?php echo esc_html__( '[Naam 3]', '3ducation' ); ?></span><span class="about-team__role"><?php echo esc_html__( 'Service &amp; Montage', '3ducation' ); ?></span></figcaption></figure>
</div>
<!-- /wp:html --></div>
<!-- /wp:group -->
