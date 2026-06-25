<?php
/**
 * Title: Product spotlights (banner row)
 * Slug: 3ducation/product-spotlights
 * Categories: 3ducation, banner, woocommerce
 * Description: A row of three image-led promo banners, color-coded per shop section.
 */
$base    = esc_url( get_template_directory_uri() . '/assets/images/' );
$resin   = $base . 'cat-resin.jpg';
$fil     = $base . 'cat-filamenten.jpg';
$work    = $base . 'cat-workshops.jpg';
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50"},"blockGap":"0"}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50)"><!-- wp:group {"className":"spotlights","layout":{"type":"default"}} -->
<div class="wp-block-group spotlights"><!-- wp:group {"className":"spotlight spotlight--resin","layout":{"type":"constrained"}} -->
<div class="wp-block-group spotlight spotlight--resin"><!-- wp:group {"className":"spotlight__media","layout":{"type":"constrained"}} -->
<div class="wp-block-group spotlight__media"><!-- wp:image {"className":"print-banner__img"} -->
<figure class="wp-block-image print-banner__img"><img src="<?php echo $resin; ?>" alt="Resin voor 3D-printen"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"spotlight__body","layout":{"type":"constrained"}} -->
<div class="wp-block-group spotlight__body"><!-- wp:paragraph {"className":"print-eyebrow print-eyebrow--magenta","fontSize":"small","fontFamily":"display"} -->
<p class="print-eyebrow print-eyebrow--magenta has-display-font-family has-small-font-size">Materiaal</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">Resin</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"spotlight__price","fontFamily":"display"} -->
<p class="spotlight__price has-display-font-family"><a class="stretched" href="/product-category/resin">vanaf €34,99 →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"spotlight spotlight--filament","layout":{"type":"constrained"}} -->
<div class="wp-block-group spotlight spotlight--filament"><!-- wp:group {"className":"spotlight__media","layout":{"type":"constrained"}} -->
<div class="wp-block-group spotlight__media"><!-- wp:image {"className":"print-banner__img"} -->
<figure class="wp-block-image print-banner__img"><img src="<?php echo $fil; ?>" alt="Filamenten voor 3D-printen"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"spotlight__body","layout":{"type":"constrained"}} -->
<div class="wp-block-group spotlight__body"><!-- wp:paragraph {"className":"print-eyebrow print-eyebrow--cyan","fontSize":"small","fontFamily":"display"} -->
<p class="print-eyebrow print-eyebrow--cyan has-display-font-family has-small-font-size">Materiaal</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">Filamenten</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"spotlight__price","fontFamily":"display"} -->
<p class="spotlight__price has-display-font-family"><a class="stretched" href="/product-category/filamenten">vanaf €19,99 →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"spotlight spotlight--workshops","layout":{"type":"constrained"}} -->
<div class="wp-block-group spotlight spotlight--workshops"><!-- wp:group {"className":"spotlight__media","layout":{"type":"constrained"}} -->
<div class="wp-block-group spotlight__media"><!-- wp:image {"className":"print-banner__img"} -->
<figure class="wp-block-image print-banner__img"><img src="<?php echo $work; ?>" alt="3D-print workshop"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"spotlight__body","layout":{"type":"constrained"}} -->
<div class="wp-block-group spotlight__body"><!-- wp:paragraph {"className":"print-eyebrow print-eyebrow--amber","fontSize":"small","fontFamily":"display"} -->
<p class="print-eyebrow print-eyebrow--amber has-display-font-family has-small-font-size">Leren</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">Workshops</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"spotlight__price","fontFamily":"display"} -->
<p class="spotlight__price has-display-font-family"><a class="stretched" href="/product-category/workshops">vanaf €41 →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
