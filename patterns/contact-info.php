<?php
/**
 * Title: Contact info & map
 * Slug: 3ducation/contact-info
 * Categories: 3ducation
 * Description: Contact details (address, phone, e-mail, opening hours) alongside a map of the showroom.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"42%"} -->
<div class="wp-block-column" style="flex-basis:42%"><!-- wp:group {"className":"info-panel info-panel--magenta","layout":{"type":"constrained"}} -->
<div class="wp-block-group info-panel info-panel--magenta"><!-- wp:paragraph {"className":"info-panel__title","fontSize":"small","fontFamily":"display"} -->
<p class="info-panel__title has-display-font-family has-small-font-size">Showroom &amp; winkel</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0"}}}} -->
<p style="margin-top:0"><strong>3DUCATION</strong><br>Vrasenestraat 40<br>9100 Nieuwkerken-Waas</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"small","style":{"color":{"text":"var:preset|color|ink-soft"}}} -->
<p class="has-text-color has-small-font-size" style="color:var(--wp--preset--color--ink-soft)">Showroom · winkel · herstellingen · workshops. Vrij parkeren voor de deur.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"fontSize":"small","style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} -->
<div class="wp-block-buttons has-custom-font-size has-small-font-size" style="margin-top:var(--wp--preset--spacing--20)"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://www.google.com/maps/dir/?api=1&amp;destination=Vrasenestraat+40,+9100+Nieuwkerken-Waas" target="_blank" rel="noreferrer noopener">Routebeschrijving</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"info-panel info-panel--magenta","style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group info-panel info-panel--magenta" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"className":"info-panel__title","fontSize":"small","fontFamily":"display"} -->
<p class="info-panel__title has-display-font-family has-small-font-size">Telefoon &amp; e-mail</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0"}}}} -->
<p style="margin-top:0"><a href="tel:+32468118242">+32 468 11 82 42</a><br><a href="mailto:info@3ducation.be">info@3ducation.be</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"small","style":{"color":{"text":"var:preset|color|ink-soft"}}} -->
<p class="has-text-color has-small-font-size" style="color:var(--wp--preset--color--ink-soft)">Telefonisch bereikbaar 10:00–19:00, niet op zon- en feestdagen.<br>BTW BE 1023 306 448</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"info-panel info-panel--magenta","style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group info-panel info-panel--magenta" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"className":"info-panel__title","fontSize":"small","fontFamily":"display"} -->
<p class="info-panel__title has-display-font-family has-small-font-size">Openingsuren</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"small","style":{"spacing":{"margin":{"top":"0"}}}} -->
<p class="has-small-font-size" style="margin-top:0">Maandag &amp; dinsdag: gesloten<br>Woensdag: 14:00–18:00<br>Donderdag: 10:00–13:00 &amp; 14:00–18:00<br>Vrijdag: 10:00–13:00 &amp; 14:00–18:00<br>Zaterdag: 10:00–17:00<br>Zondag: gesloten</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"58%"} -->
<div class="wp-block-column" style="flex-basis:58%"><!-- wp:html -->
<div class="contact-map">
	<iframe
		title="<?php echo esc_attr__( 'Kaart met de locatie van 3DUCATION, Vrasenestraat 40, 9100 Nieuwkerken-Waas', '3ducation' ); ?>"
		src="https://www.google.com/maps?q=Vrasenestraat+40,+9100+Nieuwkerken-Waas&amp;z=15&amp;output=embed"
		width="600" height="450" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
		style="border:0"></iframe>
</div>
<!-- /wp:html --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
