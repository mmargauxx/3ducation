<?php
/**
 * Title: Service hero
 * Slug: 3ducation/service-hero
 * Categories: 3ducation, banner
 * Description: Lead-in for the Service & montage pillar page — repair, maintenance and spare parts for every 3D-printer.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"54%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:54%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","contentSize":"640px"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"3px","fontWeight":"500"},"color":{"text":"var:preset|color|amber"}},"fontSize":"small","fontFamily":"display"} -->
<p class="has-text-color has-display-font-family has-small-font-size" style="color:var(--wp--preset--color--amber);font-weight:500;letter-spacing:3px;text-transform:uppercase">Service &amp; montage</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2.5rem, 5.5vw, 4rem)"}}} -->
<h1 class="wp-block-heading" style="font-size:clamp(2.5rem, 5.5vw, 4rem)">Herstelling en onderhoud</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|ink-soft"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--ink-soft)">Loop je vast met een technische storing, is je toestel toe aan onderhoud of zoek je specifieke reserveonderdelen? Het supportteam van 3DUCATION helpt je snel weer op weg.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|ink-soft"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--ink-soft)">We voeren reparaties en onderhoud uit op alle toestellen, ook als je printer oorspronkelijk niet bij ons werd aangekocht.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#service-form">Vul het aanvraagformulier in</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="mailto:info@3ducation.be?subject=Herstelling">Mail ons rechtstreeks</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"46%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:46%"><!-- wp:image {"className":"hero-photo","sizeSlug":"large"} -->
<figure class="wp-block-image size-large hero-photo"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/service-montage.jpg' ) ); ?>" alt="<?php echo esc_attr__( 'Blauwe 3D-geprinte bootjes als voorbeeld van 3D-printwerk.', '3ducation' ); ?>"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
