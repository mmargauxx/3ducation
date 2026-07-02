<?php
/**
 * Title: Product banner (flagship)
 * Slug: 3ducation/product-banner
 * Categories: 3ducation, banner, woocommerce
 * Description: Big asymmetric banner spotlighting one flagship product, with a print-build image reveal.
 */
$img = esc_url( get_template_directory_uri() . '/assets/images/cat-printers.jpg' );
// The printers category image is the Elegoo Centauri Carbon, so the
// flagship banner features that exact product (image matches the copy).
?>
<!-- wp:group {"align":"wide","className":"print-banner print-banner--flagship","backgroundColor":"ink","style":{"border":{"radius":"24px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|60","right":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignwide print-banner print-banner--flagship has-ink-background-color has-background" style="border-radius:24px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"52%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:52%"><!-- wp:paragraph {"className":"print-eyebrow print-eyebrow--amber","fontSize":"small","fontFamily":"display"} -->
<p class="print-eyebrow print-eyebrow--amber has-display-font-family has-small-font-size">Flagship · 3D-printer</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"clamp(2.25rem, 4.5vw, 3.5rem)"},"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-color" style="color:#ffffff;font-size:clamp(2.25rem, 4.5vw, 3.5rem)">Elegoo Centauri Carbon</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#b9bac2"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:#b9bac2">Gesloten CoreXY, klaar voor techniek-filamenten. Vooraf gekalibreerd en getest, klaar om uit de doos te printen.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"print-specs","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group print-specs"><!-- wp:paragraph {"className":"print-spec","fontSize":"small","fontFamily":"display"} -->
<p class="print-spec has-display-font-family has-small-font-size">256 × 256 × 256 mm</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"print-spec","fontSize":"small","fontFamily":"display"} -->
<p class="print-spec has-display-font-family has-small-font-size">Gesloten CoreXY</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"print-spec","fontSize":"small","fontFamily":"display"} -->
<p class="print-spec has-display-font-family has-small-font-size">Tot 500 mm/s</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:group {"className":"print-price","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group print-price"><!-- wp:paragraph {"className":"print-price__amount","style":{"color":{"text":"#ffffff"}},"fontSize":"x-large","fontFamily":"display"} -->
<p class="print-price__amount has-text-color has-display-font-family has-x-large-font-size" style="color:#ffffff">€309</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"color":{"text":"#8a8d99"}},"fontSize":"small"} -->
<p class="has-text-color has-small-font-size" style="color:#8a8d99">incl. btw · op voorraad</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/product/elegoo-centauri-carbon">Bekijk de Centauri Carbon</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline is-style-outline--light"} -->
<div class="wp-block-button is-style-outline is-style-outline--light"><a class="wp-block-button__link wp-element-button" href="/product-category/3d-printers">Alle printers</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"48%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:48%"><!-- wp:group {"className":"print-banner__media","layout":{"type":"constrained"}} -->
<div class="wp-block-group print-banner__media"><!-- wp:image {"className":"print-banner__img","style":{"border":{"radius":"16px"}}} -->
<figure class="wp-block-image print-banner__img has-custom-border"><img src="<?php echo $img; ?>" alt="Elegoo Centauri Carbon 3D-printer" style="border-radius:16px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
