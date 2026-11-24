<?php
/**
 * Plugin Name: 3DUCATION bestelinstellingen
 * Description: Scherm WooCommerce → Bestelinstellingen: kies of bpost-labels automatisch aangemaakt worden bij een betaalde bestelling, en of de filter op verzendmethode in de bestellijst staat.
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Vraag van Margaux (2026-11-24): de klant moet zelf kunnen kiezen
 *  - of bpost een label automatisch aanmaakt zodra een bestelling betaald
 *    is, of alleen nog met de knop (wat `3ducation-bpost-handmatig.php`
 *    sinds 2026-11-19 afdwingt);
 *  - of de keuzelijst "Alle verzendmethodes" van
 *    `3ducation-bestellijst-verzendfilter.php` in de bestellijst staat.
 *
 * Eén optie `threeducation_bestelinstellingen` (array):
 *  - `bpost_automatisch` 0|1, standaard 0 (labels alleen met de knop).
 *  - `verzendfilter`     0|1, standaard 1 (keuzelijst zichtbaar).
 *
 * De twee andere mu-plugins lezen dit via `threeducation_bestelinstelling()`
 * en vallen terug op dezelfde standaard als dit bestand ontbreekt; zonder
 * dit bestand gedragen ze zich dus zoals vóór 2026-11-24.
 *
 * Wie mag het zien: iedereen met `manage_woocommerce` (beheerder en
 * winkelbeheerder), net als de bestellijst zelf.
 */

defined( 'ABSPATH' ) || exit;

const THREEDUCATION_BESTELINSTELLINGEN_OPTIE = 'threeducation_bestelinstellingen';

/** Standaardwaarden: het gedrag van vóór dit scherm. */
function threeducation_bestelinstellingen_defaults(): array {
	return array(
		'bpost_automatisch' => 0,
		'verzendfilter'     => 1,
	);
}

/**
 * Lees één instelling.
 *
 * @param string $sleutel `bpost_automatisch` of `verzendfilter`.
 */
function threeducation_bestelinstelling( string $sleutel ): bool {
	$o = wp_parse_args( (array) get_option( THREEDUCATION_BESTELINSTELLINGEN_OPTIE, array() ), threeducation_bestelinstellingen_defaults() );

	return ! empty( $o[ $sleutel ] );
}

/** Registreer de optie bij de Settings API. */
add_action(
	'admin_init',
	function () {
		register_setting(
			'threeducation_bestelinstellingen',
			THREEDUCATION_BESTELINSTELLINGEN_OPTIE,
			array(
				'type'              => 'array',
				'default'           => threeducation_bestelinstellingen_defaults(),
				'sanitize_callback' => function ( $input ) {
					$input = (array) $input;

					return array(
						'bpost_automatisch' => empty( $input['bpost_automatisch'] ) ? 0 : 1,
						'verzendfilter'     => empty( $input['verzendfilter'] ) ? 0 : 1,
					);
				},
			)
		);
	}
);

// options.php vraagt standaard manage_options; winkelbeheerders mogen dit scherm ook opslaan.
add_filter(
	'option_page_capability_threeducation_bestelinstellingen',
	function () {
		return 'manage_woocommerce';
	}
);

/** Menu-item onder WooCommerce. */
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'woocommerce',
			__( 'Bestelinstellingen', '3ducation' ),
			__( 'Bestelinstellingen', '3ducation' ),
			'manage_woocommerce',
			'threeducation-bestelinstellingen',
			'threeducation_bestelinstellingen_render'
		);
	},
	60
);

/** Het scherm. */
function threeducation_bestelinstellingen_render(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$o            = wp_parse_args( (array) get_option( THREEDUCATION_BESTELINSTELLINGEN_OPTIE, array() ), threeducation_bestelinstellingen_defaults() );
	$naam         = THREEDUCATION_BESTELINSTELLINGEN_OPTIE;
	$bpost_actief = class_exists( 'BpostWoo' );
	$bestellijst  = admin_url( 'admin.php?page=wc-orders' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bestelinstellingen', '3ducation' ); ?></h1>
		<?php settings_errors(); ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'threeducation_bestelinstellingen' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'bpost-labels', '3ducation' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="<?php echo esc_attr( $naam ); ?>[bpost_automatisch]" value="0" <?php checked( (int) $o['bpost_automatisch'], 0 ); ?> />
								<strong><?php esc_html_e( 'Alleen met de knop', '3ducation' ); ?></strong>
							</label>
							<p class="description" style="margin:0 0 0.8em 1.6em;"><?php esc_html_e( 'Een betaalde bestelling maakt geen label aan. Je maakt het zelf in de bestellijst met de bpost-knop (blauw printericoon) of de bulkactie "bpost: Print label". Kiest een klant achteraf toch voor afhalen, dan is er nog geen label betaald.', '3ducation' ); ?></p>
							<label>
								<input type="radio" name="<?php echo esc_attr( $naam ); ?>[bpost_automatisch]" value="1" <?php checked( (int) $o['bpost_automatisch'], 1 ); ?> />
								<strong><?php esc_html_e( 'Automatisch bij betaling', '3ducation' ); ?></strong>
							</label>
							<p class="description" style="margin:0 0 0 1.6em;"><?php esc_html_e( 'Zodra een bestelling met bpost-verzending betaald is (In behandeling), maakt bpost meteen het label aan en rekent de credit aan. Een label dat al gemaakt is, kan niet geannuleerd worden. Afhaalbestellingen krijgen nooit een label.', '3ducation' ); ?></p>
						</fieldset>
						<?php if ( ! $bpost_actief ) : ?>
							<p class="description"><em><?php esc_html_e( 'De bpost-plugin lijkt niet actief; deze keuze heeft dan geen effect.', '3ducation' ); ?></em></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Bestellijst', '3ducation' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $naam ); ?>[verzendfilter]" value="1" <?php checked( (int) $o['verzendfilter'], 1 ); ?> />
							<?php esc_html_e( 'Filter op verzendmethode tonen', '3ducation' ); ?>
						</label>
						<p class="description">
							<?php
							printf(
								/* translators: %s: link naar de bestellijst */
								esc_html__( 'Zet boven de %s een keuzelijst "Alle verzendmethodes", om bijvoorbeeld alleen de bpost @ home-bestellingen of alleen de afhaalbestellingen te tonen.', '3ducation' ),
								'<a href="' . esc_url( $bestellijst ) . '">' . esc_html__( 'bestellijst', '3ducation' ) . '</a>'
							);
							?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
