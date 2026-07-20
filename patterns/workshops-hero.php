<?php
/**
 * Title: Workshops hero
 * Slug: 3ducation/workshops-hero
 * Categories: 3ducation, banner
 * Description: Lead-in hero for the Workshops & education page, with a wayfinding link to the bookable workshop dates.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"54%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:54%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","contentSize":"640px"}} -->
<div class="wp-block-group"><!-- wp:group {"className":"ws-hero-top","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group ws-hero-top"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"3px","fontWeight":"500"},"color":{"text":"var:preset|color|cyan"}},"fontSize":"small","fontFamily":"display"} -->
<p class="has-text-color has-display-font-family has-small-font-size" style="color:var(--wp--preset--color--cyan);font-weight:500;letter-spacing:3px;text-transform:uppercase">Workshops &amp; educatie</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2.5rem, 5.5vw, 4rem)"}}} -->
<h1 class="wp-block-heading" style="font-size:clamp(2.5rem, 5.5vw, 4rem)">Leer 3D-printen, van ontwerp tot print</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|ink-soft"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--ink-soft)">Van je allereerste print tot je eigen ontwerp: in kleine groep leer je alles om zelfstandig aan de slag te gaan met je 3D-printer. Ook een origineel idee voor een verjaardagsfeestje voor 10- tot 14-jarigen.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#intake">Vraag een offerte</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"46%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:46%"><!-- wp:image {"className":"hero-photo","sizeSlug":"large"} -->
<figure class="wp-block-image size-large hero-photo"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/workshops.jpg' ) ); ?>" alt="<?php echo esc_attr__( 'Deelnemers volgen samen een 3D-print workshop rond een laptop, met 3D-geprinte bloemen op tafel.', '3ducation' ); ?>"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
