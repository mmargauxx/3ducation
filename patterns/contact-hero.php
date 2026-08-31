<?php
/**
 * Title: Contact hero
 * Slug: 3ducation/contact-hero
 * Categories: 3ducation, banner
 * Description: Lead-in for the contact page — how to reach 3DUCATION and where to find the showroom.
 *
 * Two-column hero like the pillar pages, but the showroom shot is landscape,
 * so the photo takes .hero-photo--wide (3/2) instead of the usual 4/5 panel.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"54%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:54%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","contentSize":"640px"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"3px","fontWeight":"500"},"color":{"text":"var:preset|color|magenta"}},"fontSize":"small","fontFamily":"display"} -->
<p class="has-text-color has-display-font-family has-small-font-size" style="color:var(--wp--preset--color--magenta);font-weight:500;letter-spacing:3px;text-transform:uppercase">Contact &amp; bezoek</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2.5rem, 5.5vw, 4rem)"}}} -->
<h1 class="wp-block-heading" style="font-size:clamp(2.5rem, 5.5vw, 4rem)">Neem contact op</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|ink-soft"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--ink-soft)">Een vraag over een product, een workshop of een herstelling? Loop gerust binnen in onze showroom in Nieuwkerken-Waas, bel of mail ons, of laat hieronder een bericht na. We helpen je graag verder.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#contact-form">Stuur een bericht</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="tel:+32468118242">Bel +32 468 11 82 42</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"46%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:46%"><!-- wp:image {"className":"hero-photo hero-photo--wide","sizeSlug":"large"} -->
<figure class="wp-block-image size-large hero-photo hero-photo--wide"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/contact-hero-2026.jpg' ) ); ?>" alt="<?php echo esc_attr__( 'De toonbank in de showroom met verschillende 3D-printers naast elkaar.', '3ducation' ); ?>"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
