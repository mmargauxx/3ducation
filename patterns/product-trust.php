<?php
/**
 * Title: Product trust badges
 * Slug: 3ducation/product-trust
 * Categories: 3ducation, woocommerce
 * Description: Trust-signal badges shown near the Add-to-Cart button — local support, optional pre-assembled delivery, official partner. Static, site-wide reassurance; edit the labels to taste.
 */
?>
<!-- wp:group {"className":"product-trust","layout":{"type":"constrained"}} -->
<div class="wp-block-group product-trust"><!-- wp:html -->
<ul class="trust-badges" aria-label="<?php echo esc_attr__( 'Waarom bij 3ducation kopen', '3ducation' ); ?>">
	<li class="trust-badge trust-badge--magenta">
		<span class="trust-badge__icon" aria-hidden="true">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
		</span>
		<span class="trust-badge__text">
			<strong><?php echo esc_html__( 'Lokale support', '3ducation' ); ?></strong>
			<?php echo esc_html__( 'Advies en herstelling vanuit Vlaanderen', '3ducation' ); ?>
		</span>
	</li>
	<li class="trust-badge trust-badge--cyan">
		<span class="trust-badge__icon" aria-hidden="true">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
		</span>
		<span class="trust-badge__text">
			<strong><?php echo esc_html__( 'Vooraf gemonteerd', '3ducation' ); ?></strong>
			<?php echo esc_html__( 'Optioneel klaar-om-te-printen geleverd', '3ducation' ); ?>
		</span>
	</li>
	<li class="trust-badge trust-badge--amber">
		<span class="trust-badge__icon" aria-hidden="true">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a5 5 0 1 0-5-5"/><circle cx="12" cy="10" r="1"/><path d="M8.5 20.5 12 15l3.5 5.5L12 19Z"/></svg>
		</span>
		<span class="trust-badge__text">
			<strong><?php echo esc_html__( 'Officieel verkooppunt', '3ducation' ); ?></strong>
			<?php echo esc_html__( '[Merk] partner, echte garantie', '3ducation' ); ?>
		</span>
	</li>
</ul>
<!-- /wp:html --></div>
<!-- /wp:group -->
