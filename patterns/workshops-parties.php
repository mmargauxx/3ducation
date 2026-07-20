<?php
/**
 * Title: Workshops verjaardagsfeestje
 * Slug: 3ducation/workshops-parties
 * Categories: 3ducation
 * Description: The 3D birthday-party offer for 10–14 year olds — practical details and price.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"surface","className":"solutions-section","layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignfull solutions-section has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%"><!-- wp:group {"className":"info-panel info-panel--cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group info-panel info-panel--cyan"><!-- wp:paragraph {"className":"info-panel__title","fontSize":"small","fontFamily":"display"} -->
<p class="info-panel__title has-display-font-family has-small-font-size">Praktisch</p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"wsaud-list"} -->
<ul class="wp-block-list wsaud-list"><!-- wp:list-item --><li>Maximaal 8 personen per namiddag</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Woensdagnamiddag, weekend of schoolvakanties (± 13u30–16u30)</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>€ 25 per kind, inclusief geprint gadget om mee te nemen</li><!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%"><!-- wp:paragraph {"className":"print-eyebrow print-eyebrow--cyan","fontSize":"small","fontFamily":"display"} -->
<p class="print-eyebrow print-eyebrow--cyan has-display-font-family has-small-font-size">Verjaardagsfeestjes</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Verjaardagsfeestje in 3D!</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|ink-soft"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--ink-soft)">Ben je tussen de 10 en 14 jaar en zoek je een origineel verjaardagsfeestje? Tijdens een interactieve namiddag ontdekken jij en je vrienden stap voor stap hoe een 3D-printer werkt. We starten met Tinkercad, een virtuele blokkendoos waarmee je je eigen 3D-tekening maakt. Elk kind krijgt als aandenken een gepersonaliseerd, geprint gadget mee naar huis.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"price-tag"} -->
<p class="price-tag">€ 25 <span>per kind</span></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><?php
// Booking via LatePoint: any element with the class "latepoint-book-button"
// opens the LatePoint booking popup when the plugin is active. The mailto href
// is the graceful fallback for environments where LatePoint isn't installed yet
// (dev, or pre-launch). Preselect the "Verjaardagsfeestje in 3D" service by
// returning its LatePoint service id from this filter once the service exists on
// the server — kept out of the theme so the id stays per-environment, like the
// shop attributes (see GO-LIVE.md). Empty (default) opens the general booking form.
$lp_service = apply_filters( 'threeducation_latepoint_party_service', '' );
$lp_data    = '' !== $lp_service ? ' data-selected_service_id="' . esc_attr( $lp_service ) . '"' : '';
?><a class="wp-block-button__link wp-element-button latepoint-book-button" href="mailto:info@3ducation.be?subject=Verjaardagsfeestje%20in%203D"<?php echo $lp_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- value escaped above ?>><?php echo esc_html__( 'Reserveer je feestje', '3ducation' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
