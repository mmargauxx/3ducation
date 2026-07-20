<?php
/**
 * Title: Workshops intake
 * Slug: 3ducation/workshops-intake
 * Categories: 3ducation, call-to-action
 * Description: Lead-generation intake block for the Workshops page — a clean, semantic contact-form layout placeholder.
 *
 * NOTE: The markup below is a layout placeholder only. Wire the <form> up to a
 * form handler (e.g. Contact Form 7 / WPForms shortcode, or a POST endpoint)
 * before going live — it does not submit anywhere on its own.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"ink","textColor":"base","className":"workshops-intake","layout":{"type":"constrained","contentSize":"680px"}} -->
<div class="wp-block-group alignfull workshops-intake has-base-color has-ink-background-color has-text-color has-background" id="intake" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontWeight":"500"},"color":{"text":"var:preset|color|cyan"}},"fontSize":"small","fontFamily":"display"} -->
<p class="has-text-color has-display-font-family has-small-font-size" style="color:var(--wp--preset--color--cyan);font-weight:500;letter-spacing:2px;text-transform:uppercase">Intake</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"textColor":"surface","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-surface-color has-text-color has-x-large-font-size">Vertel ons over je groep</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|mist"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--mist)">Vertel ons meer over jouw groep en wensen. Laat je gegevens achter en we stellen een voorstel op maat voor je op. We reageren altijd binnen de twee werkdagen!</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|40"} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:html -->
<form class="intake-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate>
	<?php echo threeducation_intake_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- returns escaped HTML ?>
	<?php threeducation_intake_hidden_fields( 'workshops' ); ?>
	<div class="intake-form__row">
		<div class="intake-field">
			<label for="intake-name"><?php echo esc_html__( 'Naam', '3ducation' ); ?></label>
			<input type="text" id="intake-name" name="name" autocomplete="name" placeholder="<?php echo esc_attr__( 'Voor- en achternaam', '3ducation' ); ?>" />
		</div>
		<div class="intake-field">
			<label for="intake-email"><?php echo esc_html__( 'E-mailadres', '3ducation' ); ?></label>
			<input type="email" id="intake-email" name="email" autocomplete="email" placeholder="jij@voorbeeld.be" />
		</div>
	</div>
	<div class="intake-form__row">
		<div class="intake-field">
			<label for="intake-org"><?php echo esc_html__( 'Organisatie (optioneel)', '3ducation' ); ?></label>
			<input type="text" id="intake-org" name="organisation" autocomplete="organization" placeholder="<?php echo esc_attr__( 'School, bedrijf of vereniging', '3ducation' ); ?>" />
		</div>
		<div class="intake-field">
			<label for="intake-audience"><?php echo esc_html__( 'Doelgroep', '3ducation' ); ?></label>
			<select id="intake-audience" name="audience">
				<option value=""><?php echo esc_html__( 'Maak een keuze…', '3ducation' ); ?></option>
				<option value="school"><?php echo esc_html__( 'School', '3ducation' ); ?></option>
				<option value="business"><?php echo esc_html__( 'Bedrijf', '3ducation' ); ?></option>
				<option value="hobby"><?php echo esc_html__( 'Hobbyist', '3ducation' ); ?></option>
			</select>
		</div>
	</div>
	<div class="intake-field">
		<label for="intake-message"><?php echo esc_html__( 'Je vraag', '3ducation' ); ?></label>
		<textarea id="intake-message" name="message" rows="4" placeholder="<?php echo esc_attr__( 'Groepsgrootte, niveau, gewenste datum…', '3ducation' ); ?>"></textarea>
	</div>
	<button type="submit" class="intake-form__submit"><?php echo esc_html__( 'Verstuur aanvraag', '3ducation' ); ?></button>
</form>
<!-- /wp:html --></div>
<!-- /wp:group -->
