<?php
/**
 * Title: Product trust badges
 * Slug: 3ducation/product-trust
 * Categories: 3ducation, woocommerce
 * Description: Trust-signal badges shown near the Add-to-Cart button — local support, optional pre-assembled delivery, official partner. Edit the labels under Instellingen → Vertrouwensbadges.
 */

$threeducation_trust_badges = function_exists( 'threeducation_trust_badges' ) ? threeducation_trust_badges() : array();
if ( empty( $threeducation_trust_badges ) ) {
	return;
}
?>
<!-- wp:group {"className":"product-trust","layout":{"type":"constrained"}} -->
<div class="wp-block-group product-trust"><!-- wp:html -->
<ul class="trust-badges" aria-label="<?php echo esc_attr__( 'Waarom bij 3ducation kopen', '3ducation' ); ?>">
<?php foreach ( $threeducation_trust_badges as $threeducation_badge ) : ?>
	<li class="trust-badge trust-badge--<?php echo esc_attr( $threeducation_badge['accent'] ); ?>">
		<span class="trust-badge__icon" aria-hidden="true"><?php echo $threeducation_badge['icon']; // phpcs:ignore WordPress.Security.EscapeOutput -- static theme-controlled SVG ?></span>
		<span class="trust-badge__text">
			<strong><?php echo esc_html( $threeducation_badge['title'] ); ?></strong>
			<?php if ( '' !== $threeducation_badge['text'] ) : ?>
				<?php echo esc_html( $threeducation_badge['text'] ); ?>
			<?php endif; ?>
		</span>
	</li>
<?php endforeach; ?>
</ul>
<!-- /wp:html --></div>
<!-- /wp:group -->
