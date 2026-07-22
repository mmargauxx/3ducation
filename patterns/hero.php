<?php
/**
 * Title: Hero
 * Slug: 3ducation/hero
 * Categories: 3ducation, banner
 * Description: Full-bleed photo hero (Cover block) with the 3DUCATION motto.
 *
 * Built as a wp:cover so the header photo is editable from the Site Editor:
 * Vormgeving → Editor → Voorpagina → klik de hero → Vervang. The overlay is a
 * left→right dark gradient scrim that keeps the light text legible over any
 * photo. Content sits in a 1240 band (.home-hero__content) so its left edge
 * lines up with the rest of the page; text caps at 640. The starting photo is
 * the bundled asset; replacing it in the editor saves a template override in
 * the database (see the header-image note before deploying theme updates).
 */
?>
<!-- wp:cover {"url":"<?php echo esc_url( get_theme_file_uri( 'assets/images/homepage-header.jpg' ) ); ?>","dimRatio":100,"customGradient":"linear-gradient(90deg,rgba(15,16,22,0.82) 0%,rgba(15,16,22,0.6) 45%,rgba(15,16,22,0.2) 100%)","isDark":true,"align":"full","className":"home-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}}} -->
<div class="wp-block-cover alignfull home-hero" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient" style="background:linear-gradient(90deg,rgba(15,16,22,0.82) 0%,rgba(15,16,22,0.6) 45%,rgba(15,16,22,0.2) 100%)"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/homepage-header.jpg' ) ); ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"className":"home-hero__content","layout":{"type":"default"}} -->
<div class="wp-block-group home-hero__content"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"3px","fontWeight":"500"},"color":{"text":"var:preset|color|magenta"}},"fontSize":"small","fontFamily":"display"} -->
<p class="has-text-color has-display-font-family has-small-font-size" style="color:var(--wp--preset--color--magenta);font-weight:500;letter-spacing:3px;text-transform:uppercase">3D-printshop · België</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2.75rem, 6vw, 4.5rem)"},"color":{"text":"var:preset|color|surface"}}} -->
<h1 class="wp-block-heading has-text-color" style="color:var(--wp--preset--color--surface);font-size:clamp(2.75rem, 6vw, 4.5rem)">Learn to Print<br>Print to Learn.</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|mist"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--mist)">Vooraf gemonteerde 3D-printers, betrouwbare filamenten en resin, plus hands-on workshops. Je koopt geen doos, je koopt vertrouwen.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop">Bekijk de webshop</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline is-style-outline--light"} -->
<div class="wp-block-button is-style-outline is-style-outline--light"><a class="wp-block-button__link wp-element-button" href="/product-category/workshops">Workshops</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
