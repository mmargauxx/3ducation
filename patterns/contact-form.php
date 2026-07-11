<?php
/**
 * Title: Contact form
 * Slug: 3ducation/contact-form
 * Categories: 3ducation, call-to-action
 * Description: General contact message form on a dark panel.
 *
 * NOTE: The <form> below is a layout placeholder. Wire it to a form handler
 * (e.g. Contact Form 7 / WPForms shortcode, or a POST endpoint) before going
 * live — it does not submit anywhere on its own.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"ink","textColor":"base","className":"workshops-intake","layout":{"type":"constrained","contentSize":"680px"}} -->
<div class="wp-block-group alignfull workshops-intake has-base-color has-ink-background-color has-text-color has-background" id="contact-form" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontWeight":"500"},"color":{"text":"var:preset|color|magenta"}},"fontSize":"small","fontFamily":"display"} -->
<p class="has-text-color has-display-font-family has-small-font-size" style="color:var(--wp--preset--color--magenta);font-weight:500;letter-spacing:2px;text-transform:uppercase">Stuur een bericht</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"textColor":"surface","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-surface-color has-text-color has-x-large-font-size">Laat iets weten</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|mist"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--mist)">Vul onderstaande gegevens in, dan nemen we zo snel mogelijk contact met je op. Voor een herstelling gebruik je best het <a href="/service#service-form">aanvraagformulier voor service</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|40"} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:html -->
<form class="intake-form" action="#" method="post" novalidate>
	<div class="intake-form__row">
		<div class="intake-field">
			<label for="contact-name"><?php echo esc_html__( 'Naam', '3ducation' ); ?></label>
			<input type="text" id="contact-name" name="name" autocomplete="name" placeholder="<?php echo esc_attr__( 'Voor- en achternaam', '3ducation' ); ?>" />
		</div>
		<div class="intake-field">
			<label for="contact-email"><?php echo esc_html__( 'E-mailadres', '3ducation' ); ?></label>
			<input type="email" id="contact-email" name="email" autocomplete="email" placeholder="jij@voorbeeld.be" />
		</div>
	</div>
	<div class="intake-form__row">
		<div class="intake-field">
			<label for="contact-phone"><?php echo esc_html__( 'Telefoonnummer (optioneel)', '3ducation' ); ?></label>
			<input type="tel" id="contact-phone" name="phone" autocomplete="tel" placeholder="+32 …" />
		</div>
		<div class="intake-field">
			<label for="contact-subject"><?php echo esc_html__( 'Onderwerp', '3ducation' ); ?></label>
			<select id="contact-subject" name="subject">
				<option value=""><?php echo esc_html__( 'Kies een onderwerp…', '3ducation' ); ?></option>
				<option value="webshop"><?php echo esc_html__( 'Webshop & producten', '3ducation' ); ?></option>
				<option value="workshops"><?php echo esc_html__( 'Workshops & opleidingen', '3ducation' ); ?></option>
				<option value="scholen"><?php echo esc_html__( 'Scholen & educatieve pakketten', '3ducation' ); ?></option>
				<option value="service"><?php echo esc_html__( 'Service & herstelling', '3ducation' ); ?></option>
				<option value="anders"><?php echo esc_html__( 'Iets anders', '3ducation' ); ?></option>
			</select>
		</div>
	</div>
	<div class="intake-field">
		<label for="contact-message"><?php echo esc_html__( 'Bericht', '3ducation' ); ?></label>
		<textarea id="contact-message" name="message" rows="5" placeholder="<?php echo esc_attr__( 'Waarmee kunnen we je helpen?', '3ducation' ); ?>"></textarea>
	</div>
	<button type="submit" class="intake-form__submit"><?php echo esc_html__( 'Verstuur bericht', '3ducation' ); ?></button>
	<p class="intake-form__note">[Placeholder] <?php echo esc_html__( 'Koppel dit formulier aan je formulier-plugin of e-mailhandler voordat je live gaat.', '3ducation' ); ?></p>
</form>
<!-- /wp:html --></div>
<!-- /wp:group -->
