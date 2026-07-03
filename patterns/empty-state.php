<?php
/**
 * Title: Lege staat (funky 404)
 * Slug: 3ducation/empty-state
 * Categories: 3ducation
 * Description: Herbruikbare "niets gevonden" lege staat — het funky 404-beeld met titel en uitleg. Gebruikt door de zoek- en shop-pagina's zonder resultaten; elke template voegt zelf de passende knoppen toe.
 */
?>
<!-- wp:group {"className":"error-404 empty-state","layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group error-404 empty-state"><!-- wp:html -->
<div class="error-404__stage" aria-hidden="true">
	<div class="error-404__code"><span class="error-404__digit error-404__digit--magenta">4</span><span class="error-404__digit error-404__digit--amber error-404__shift">0</span><span class="error-404__digit error-404__digit--cyan">4</span></div>
</div>
<!-- /wp:html -->

<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-text-align-center has-xx-large-font-size"><?php echo esc_html__( 'Niets gevonden', '3ducation' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"var:preset|color|ink-soft"}},"fontSize":"large"} -->
<p class="has-text-align-center has-text-color has-large-font-size" style="color:var(--wp--preset--color--ink-soft)"><?php echo esc_html__( 'We konden niets vinden dat past bij je zoekopdracht. Probeer iets anders of blader verder via het menu hierboven.', '3ducation' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
