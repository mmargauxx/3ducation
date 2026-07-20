<?php
/**
 * Title: Service intake
 * Slug: 3ducation/service-intake
 * Categories: 3ducation, call-to-action
 * Description: Repair-request block — what to have ready, plus a semantic support intake form.
 *
 * NOTE: The <form> below is a layout placeholder. Wire it to a form handler
 * (e.g. Contact Form 7 / WPForms shortcode, or a POST endpoint) before going
 * live — it does not submit anywhere on its own.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"55%"} -->
<div class="wp-block-column" style="flex-basis:55%"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Wat hebben we nodig?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|ink-soft"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--ink-soft)">Voor een snelle en efficiënte service omschrijf je je probleem zo gedetailleerd mogelijk. Hou de volgende gegevens bij de hand voor je aanvraag:</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|ink-soft"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--ink-soft)">Ons team neemt na ontvangst zo snel mogelijk contact met je op voor een passende oplossing.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"45%"} -->
<div class="wp-block-column" style="flex-basis:45%"><!-- wp:group {"className":"info-panel info-panel--amber","layout":{"type":"constrained"}} -->
<div class="wp-block-group info-panel info-panel--amber"><!-- wp:paragraph {"className":"info-panel__title","fontSize":"small","fontFamily":"display"} -->
<p class="info-panel__title has-display-font-family has-small-font-size">Hou bij de hand</p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"wsaud-list"} -->
<ul class="wp-block-list wsaud-list"><!-- wp:list-item --><li>Naam, e-mail en telefoonnummer</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Uitgebreide probleembeschrijving</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Type printer en serienummer (op de QR-code-sticker van het toestel)</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Factuurnummer en aankoopdatum (enkel indien bij ons aangekocht)</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Duidelijke foto's of video's van het probleem</li><!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"ink","textColor":"base","className":"workshops-intake","layout":{"type":"constrained","contentSize":"680px"}} -->
<div class="wp-block-group alignfull workshops-intake has-base-color has-ink-background-color has-text-color has-background" id="service-form" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontWeight":"500"},"color":{"text":"var:preset|color|amber"}},"fontSize":"small","fontFamily":"display"} -->
<p class="has-text-color has-display-font-family has-small-font-size" style="color:var(--wp--preset--color--amber);font-weight:500;letter-spacing:2px;text-transform:uppercase">Aanvraagformulier</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"textColor":"surface","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-surface-color has-text-color has-x-large-font-size">Herstelling aanvragen</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|mist"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--mist)">Vul onderstaande gegevens zo volledig mogelijk in. Hoe gedetailleerder je aanvraag, hoe sneller we je kunnen helpen.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|40"} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:html -->
<form class="intake-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" novalidate>
	<?php echo threeducation_intake_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- returns escaped HTML ?>
	<?php threeducation_intake_hidden_fields( 'service' ); ?>
	<div class="intake-form__row">
		<div class="intake-field">
			<label for="svc-name"><?php echo esc_html__( 'Naam', '3ducation' ); ?></label>
			<input type="text" id="svc-name" name="name" autocomplete="name" placeholder="<?php echo esc_attr__( 'Voor- en achternaam', '3ducation' ); ?>" />
		</div>
		<div class="intake-field">
			<label for="svc-email"><?php echo esc_html__( 'E-mailadres', '3ducation' ); ?></label>
			<input type="email" id="svc-email" name="email" autocomplete="email" placeholder="jij@voorbeeld.be" />
		</div>
	</div>
	<div class="intake-form__row">
		<div class="intake-field">
			<label for="svc-phone"><?php echo esc_html__( 'Telefoonnummer', '3ducation' ); ?></label>
			<input type="tel" id="svc-phone" name="phone" autocomplete="tel" placeholder="+32 …" />
		</div>
		<div class="intake-field">
			<label for="svc-printer"><?php echo esc_html__( 'Type printer', '3ducation' ); ?></label>
			<input type="text" id="svc-printer" name="printer" placeholder="<?php echo esc_attr__( 'Merk en model', '3ducation' ); ?>" />
		</div>
	</div>
	<div class="intake-field">
		<label for="svc-serial"><?php echo esc_html__( 'Serienummer', '3ducation' ); ?></label>
		<input type="text" id="svc-serial" name="serial" placeholder="<?php echo esc_attr__( 'Bv. SN-000000', '3ducation' ); ?>" />
		<span class="field-hint"><?php echo esc_html__( 'Te vinden op de QR-code-sticker op je toestel.', '3ducation' ); ?></span>
	</div>
	<div class="intake-form__row">
		<div class="intake-field">
			<label for="svc-invoice"><?php echo esc_html__( 'Factuurnummer (optioneel)', '3ducation' ); ?></label>
			<input type="text" id="svc-invoice" name="invoice" placeholder="<?php echo esc_attr__( 'Enkel indien bij ons gekocht', '3ducation' ); ?>" />
		</div>
		<div class="intake-field">
			<label for="svc-date"><?php echo esc_html__( 'Aankoopdatum (optioneel)', '3ducation' ); ?></label>
			<input type="date" id="svc-date" name="purchase_date" />
		</div>
	</div>
	<div class="intake-field">
		<label for="svc-message"><?php echo esc_html__( 'Probleembeschrijving', '3ducation' ); ?></label>
		<textarea id="svc-message" name="message" rows="5" placeholder="<?php echo esc_attr__( 'Beschrijf het probleem zo gedetailleerd mogelijk…', '3ducation' ); ?>"></textarea>
	</div>
	<div class="intake-field">
		<label for="svc-files"><?php echo esc_html__( "Foto's of video's", '3ducation' ); ?></label>
		<input type="file" id="svc-files" name="attachments[]" accept="image/*,video/*" multiple />
		<span class="field-hint"><?php echo esc_html__( 'Duidelijke beelden van het probleem helpen ons sneller op weg.', '3ducation' ); ?></span>
	</div>
	<button type="submit" class="intake-form__submit"><?php echo esc_html__( 'Verstuur aanvraag', '3ducation' ); ?></button>
</form>
<!-- /wp:html --></div>
<!-- /wp:group -->
