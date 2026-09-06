<?php
/**
 * Plugin Name: 3DUCATION onderhoudsmodus
 * Description: Zet de website (of alleen de webshop) tijdelijk op "even geduld": bezoekers zien een pagina in de huisstijl met HTTP 503, beheerders en de kassa werken gewoon verder. Meteen of gepland, via Instellingen → Onderhoudsmodus.
 * Version: 1.1.0
 * Author: 3DUCATION
 *
 * Waarom een eigen bestand: de bestaande onderhoudsplugins slepen een eigen
 * paginabouwer, tracking en opmaak mee die niet bij het thema past. Dit doet
 * enkel wat nodig is:
 *
 *  1. Eén optie (`threeducation_onderhoud`) met een beheerscherm onder
 *     Instellingen → Onderhoudsmodus: aan/uit, bereik (hele website of alleen
 *     de webshop), een optioneel begin- en eindmoment, titel en tekst. Zelfde
 *     opzet als "Site melding" en "Site pop-up" in het thema.
 *  2. Planning: staat de modus aan, dan is hij actief vanaf het beginmoment
 *     (of meteen) tot het eindmoment (of tot hij weer uitgezet wordt). Het
 *     eindmoment staat ook op de pagina ("opnieuw online op …") en gaat als
 *     `Retry-After` mee naar zoekmachines.
 *  3. Bezoekers krijgen HTTP 503 (tijdelijk onbeschikbaar, Google houdt de
 *     pagina's in de index) met `Cache-Control: no-cache`, en een pagina die
 *     de kleuren, het lettertype en het kubuslogo uit het thema haalt
 *     (`theme.json` via `wp_get_global_settings()`), met het telefoonnummer
 *     en e-mailadres uit Instellingen → Footer. Valt het thema weg, dan
 *     blijven de ingebouwde reservewaarden over.
 *  4. Wie mag er door: ingelogde beheerders en winkelbeheerders
 *     (`manage_options` of `manage_woocommerce`, dus ook de kassa), en wie de
 *     voorbeeldlink `?onderhoud=<sleutel>` opent (zet een cookie van 24 uur).
 *     wp-admin, inloggen, REST (kassa-app, blokken), AJAX, cron en
 *     WooCommerce-webhooks (`wc-api`) worden nooit geblokkeerd, dus betalingen
 *     en bestelmails lopen gewoon door.
 *  5. In de beheerbalk staat "Onderhoudsmodus actief" zolang de modus loopt,
 *     zodat niemand hem vergeet uit te zetten.
 *  6. Voorbeeld zonder aanzetten: de knop "Voorbeeld bekijken" op het scherm
 *     opent `/?onderhoud_voorbeeld=1` (met nonce, alleen beheerders) en toont
 *     de pagina met de opgeslagen instellingen, met HTTP 200 en zonder dat
 *     er voor bezoekers iets verandert.
 *
 * Let op: staat er een paginacache voor de site (EasyHost), dan kan een
 * bezoeker de oude pagina nog even te zien krijgen tot die cache verloopt.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Geen activatiestap.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Instellingen
 * ---------------------------------------------------------------------- */

/** Standaardwaarden van de optie. */
function threeducation_onderhoud_defaults() {
	return array(
		'enabled' => 0,
		'bereik'  => 'site', // 'site' = hele website, 'shop' = alleen de webshop.
		'start'   => '',     // Y-m-d\TH:i in de tijdzone van de site, leeg = meteen.
		'einde'   => '',     // Y-m-d\TH:i, leeg = tot hij uitgezet wordt.
		'titel'   => __( 'Even geduld, we werken aan de website', '3ducation' ),
		'tekst'   => __( "We voeren een onderhoud uit en zijn zo dadelijk terug.\n\nDringende vraag of een bestelling afhalen? Bel of mail ons gerust.", '3ducation' ),
		'sleutel' => '',     // Geheime sleutel van de voorbeeldlink, eenmalig gegenereerd.
	);
}

/**
 * Zet een 'Y-m-d\TH:i'-waarde (uit het datetime-local veld) om naar een
 * timestamp in de tijdzone van de site. Leeg of ongeldig geeft 0.
 */
function threeducation_onderhoud_timestamp( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return 0;
	}
	$d = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $value, wp_timezone() );

	return ( $d && $d->format( 'Y-m-d\TH:i' ) === $value ) ? $d->getTimestamp() : 0;
}

/**
 * De actieve instellingen: opgeslagen optie + standaardwaarden, aangevuld
 * met 'status' (uit | gepland | actief | afgelopen), de timestamps van begin
 * en einde, en de sleutel van de voorbeeldlink (wordt eenmalig aangemaakt).
 */
function threeducation_onderhoud_settings() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$o = wp_parse_args( (array) get_option( 'threeducation_onderhoud', array() ), threeducation_onderhoud_defaults() );

	if ( '' === $o['sleutel'] ) {
		$o['sleutel'] = strtolower( wp_generate_password( 12, false ) );
		update_option( 'threeducation_onderhoud', array_intersect_key( $o, threeducation_onderhoud_defaults() ) );
	}

	$now            = time();
	$o['start_ts']  = threeducation_onderhoud_timestamp( $o['start'] );
	$o['einde_ts']  = threeducation_onderhoud_timestamp( $o['einde'] );

	if ( empty( $o['enabled'] ) ) {
		$o['status'] = 'uit';
	} elseif ( $o['einde_ts'] && $now >= $o['einde_ts'] ) {
		$o['status'] = 'afgelopen';
	} elseif ( $o['start_ts'] && $now < $o['start_ts'] ) {
		$o['status'] = 'gepland';
	} else {
		$o['status'] = 'actief';
	}

	$cache = $o;

	return $o;
}

/** Registreer de optie bij de Settings API. */
function threeducation_onderhoud_register_settings() {
	register_setting(
		'threeducation_onderhoud',
		'threeducation_onderhoud',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'threeducation_onderhoud_sanitize',
			'default'           => threeducation_onderhoud_defaults(),
		)
	);
}
add_action( 'admin_init', 'threeducation_onderhoud_register_settings' );

/** Maak de ingestuurde instellingen schoon. De sleutel komt nooit uit het formulier. */
function threeducation_onderhoud_sanitize( $input ) {
	$input    = (array) $input;
	$bestaand = wp_parse_args( (array) get_option( 'threeducation_onderhoud', array() ), threeducation_onderhoud_defaults() );

	$clean_moment = static function ( $value ) {
		$value = trim( (string) $value );
		return threeducation_onderhoud_timestamp( $value ) ? $value : '';
	};

	$start = $clean_moment( $input['start'] ?? '' );
	$einde = $clean_moment( $input['einde'] ?? '' );
	if ( $start && $einde && threeducation_onderhoud_timestamp( $einde ) <= threeducation_onderhoud_timestamp( $start ) ) {
		add_settings_error( 'threeducation_onderhoud', 'einde_voor_start', __( 'Het eindmoment lag vóór het beginmoment en is gewist.', '3ducation' ) );
		$einde = '';
	}

	return array(
		'enabled' => empty( $input['enabled'] ) ? 0 : 1,
		'bereik'  => ( isset( $input['bereik'] ) && 'shop' === $input['bereik'] ) ? 'shop' : 'site',
		'start'   => $start,
		'einde'   => $einde,
		'titel'   => sanitize_text_field( $input['titel'] ?? '' ),
		'tekst'   => sanitize_textarea_field( $input['tekst'] ?? '' ),
		'sleutel' => (string) $bestaand['sleutel'],
	);
}

/* -------------------------------------------------------------------------
 * Wie ziet de onderhoudspagina
 * ---------------------------------------------------------------------- */

/** Naam van het cookie dat de voorbeeldlink zet. */
function threeducation_onderhoud_cookie_naam() {
	return '3ducation_onderhoud';
}

/**
 * Mag deze bezoeker door? Beheerders, winkelbeheerders (kassa) en wie de
 * voorbeeldlink gebruikt (query-parameter of het cookie dat die zet).
 */
function threeducation_onderhoud_mag_door( array $o ) {
	if ( is_user_logged_in() && ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) ) ) {
		return true;
	}

	$sleutel = (string) $o['sleutel'];
	if ( '' === $sleutel ) {
		return false;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- geheime sleutel, geen formulier.
	if ( isset( $_GET['onderhoud'] ) && hash_equals( $sleutel, (string) wp_unslash( $_GET['onderhoud'] ) ) ) {
		setcookie( threeducation_onderhoud_cookie_naam(), $sleutel, time() + DAY_IN_SECONDS, '/', '', is_ssl(), true );
		return true;
	}
	// phpcs:enable

	$cookie = isset( $_COOKIE[ threeducation_onderhoud_cookie_naam() ] ) ? (string) $_COOKIE[ threeducation_onderhoud_cookie_naam() ] : '';

	return '' !== $cookie && hash_equals( $sleutel, $cookie );
}

/**
 * Valt deze aanvraag onder het onderhoud? Techniek (REST, AJAX, cron,
 * webhooks, inloggen) blijft altijd bereikbaar; bij bereik "shop" alleen de
 * WooCommerce-pagina's.
 */
function threeducation_onderhoud_geldt_hier( array $o ) {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return false;
	}
	if ( ! empty( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
		return false;
	}
	if ( get_query_var( 'wc-api' ) || isset( $_GET['wc-api'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}

	$geldt = true;
	if ( 'shop' === $o['bereik'] ) {
		$geldt = function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() );
	}

	/**
	 * Laat een pad buiten het onderhoud vallen, bv. een koppeling die
	 * bereikbaar moet blijven. Geef false terug om door te laten.
	 */
	return (bool) apply_filters( 'threeducation_onderhoud_geldt_hier', $geldt, $o );
}

/* -------------------------------------------------------------------------
 * De onderhoudspagina
 * ---------------------------------------------------------------------- */

/** Toon de onderhoudspagina en stop, als de modus actief is en de bezoeker niet door mag. */
function threeducation_onderhoud_template_redirect() {
	$o = threeducation_onderhoud_settings();

	if ( threeducation_onderhoud_is_voorbeeld() ) {
		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		threeducation_onderhoud_render_pagina( $o );
		exit;
	}

	if ( 'actief' !== $o['status'] || ! threeducation_onderhoud_geldt_hier( $o ) || threeducation_onderhoud_mag_door( $o ) ) {
		return;
	}

	$retry = $o['einde_ts'] ? max( MINUTE_IN_SECONDS, min( DAY_IN_SECONDS, $o['einde_ts'] - time() ) ) : HOUR_IN_SECONDS;

	nocache_headers();
	status_header( 503 );
	header( 'Retry-After: ' . (int) $retry );
	header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );

	threeducation_onderhoud_render_pagina( $o );
	exit;
}
add_action( 'template_redirect', 'threeducation_onderhoud_template_redirect', 0 );

/** URL van het voorbeeld voor beheerders: de homepage met een genonced'e parameter. */
function threeducation_onderhoud_voorbeeld_url() {
	return wp_nonce_url( add_query_arg( 'onderhoud_voorbeeld', '1', home_url( '/' ) ), 'threeducation_onderhoud_voorbeeld' );
}

/** Vraagt een ingelogde beheerder het voorbeeld op? Werkt ook als de modus uit staat. */
function threeducation_onderhoud_is_voorbeeld() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce wordt hieronder gecontroleerd.
	if ( empty( $_GET['onderhoud_voorbeeld'] ) || ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	return false !== wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'threeducation_onderhoud_voorbeeld' );
}

/**
 * De kleuren en het verloop uit theme.json, met reservewaarden voor als het
 * thema ooit wegvalt. Sleutels zijn de preset-slugs van het thema.
 */
function threeducation_onderhoud_stijl() {
	$stijl = array(
		'surface-dark' => '#1b1c22',
		'ink-line'     => '#2a2b33',
		'base'         => '#fafafa',
		'mist'         => '#b9bac2',
		'mist-soft'    => '#6c6e79',
		'magenta'      => '#e6186c',
		'amber'        => '#f7941e',
		'cube'         => 'linear-gradient(120deg, #e6186c 0%, #f2562f 26%, #f7941e 50%, #2fb58f 72%, #14b1bb 88%, #0fb1bf 100%)',
		'font-url-500' => '',
		'font-url-700' => '',
		'logo-url'     => '',
	);

	if ( function_exists( 'wp_get_global_settings' ) ) {
		$palette = wp_get_global_settings( array( 'color', 'palette' ) );
		foreach ( (array) ( $palette['theme'] ?? array() ) as $kleur ) {
			if ( isset( $kleur['slug'], $kleur['color'], $stijl[ $kleur['slug'] ] ) ) {
				$stijl[ $kleur['slug'] ] = $kleur['color'];
			}
		}
		$gradients = wp_get_global_settings( array( 'color', 'gradients' ) );
		foreach ( (array) ( $gradients['theme'] ?? array() ) as $verloop ) {
			if ( isset( $verloop['slug'], $verloop['gradient'] ) && 'cube' === $verloop['slug'] ) {
				$stijl['cube'] = $verloop['gradient'];
			}
		}
	}

	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	foreach ( array( '500', '700' ) as $gewicht ) {
		if ( file_exists( $dir . '/assets/fonts/space-grotesk-' . $gewicht . '.woff2' ) ) {
			$stijl[ 'font-url-' . $gewicht ] = $uri . '/assets/fonts/space-grotesk-' . $gewicht . '.woff2';
		}
	}
	if ( file_exists( $dir . '/assets/images/logo-cube.png' ) ) {
		$stijl['logo-url'] = $uri . '/assets/images/logo-cube.png';
	}

	return $stijl;
}

/** Tekstvak → alinea's: lege regel = nieuwe alinea, enkele regel = <br>. */
function threeducation_onderhoud_alineas( $tekst ) {
	$alineas = preg_split( '/\n\s*\n/', str_replace( "\r", '', (string) $tekst ) );
	$html    = '';
	foreach ( $alineas as $alinea ) {
		$alinea = trim( $alinea );
		if ( '' === $alinea ) {
			continue;
		}
		$html .= '<p>' . nl2br( esc_html( $alinea ) ) . '</p>';
	}

	return $html;
}

/** Het eindmoment in woorden, bv. "woensdag 10 september om 09:00". Leeg zonder eindmoment. */
function threeducation_onderhoud_einde_tekst( array $o ) {
	if ( ! $o['einde_ts'] ) {
		return '';
	}

	return wp_date( __( 'l j F \o\m H:i', '3ducation' ), $o['einde_ts'], wp_timezone() );
}

/** Druk de volledige onderhoudspagina af. */
function threeducation_onderhoud_render_pagina( array $o ) {
	$s        = threeducation_onderhoud_stijl();
	$titel    = '' !== trim( $o['titel'] ) ? $o['titel'] : threeducation_onderhoud_defaults()['titel'];
	$tekst    = '' !== trim( $o['tekst'] ) ? $o['tekst'] : threeducation_onderhoud_defaults()['tekst'];
	$einde    = threeducation_onderhoud_einde_tekst( $o );
	$contact  = function_exists( 'threeducation_footer_values' ) ? threeducation_footer_values() : array();
	$telefoon = trim( (string) ( $contact['telefoon'] ?? '' ) );
	$email    = trim( (string) ( $contact['email'] ?? '' ) );
	$tel_href = preg_replace( '/[^0-9+]/', '', $telefoon );
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo esc_html( $titel ); ?> – 3DUCATION</title>
<style>
<?php if ( $s['font-url-500'] ) : ?>
@font-face { font-family: "Space Grotesk"; font-weight: 500; font-style: normal; font-display: swap; src: url("<?php echo esc_url( $s['font-url-500'] ); ?>") format("woff2"); }
<?php endif; ?>
<?php if ( $s['font-url-700'] ) : ?>
@font-face { font-family: "Space Grotesk"; font-weight: 700; font-style: normal; font-display: swap; src: url("<?php echo esc_url( $s['font-url-700'] ); ?>") format("woff2"); }
<?php endif; ?>
:root {
	--surface-dark: <?php echo esc_html( $s['surface-dark'] ); ?>;
	--ink-line: <?php echo esc_html( $s['ink-line'] ); ?>;
	--base: <?php echo esc_html( $s['base'] ); ?>;
	--mist: <?php echo esc_html( $s['mist'] ); ?>;
	--mist-soft: <?php echo esc_html( $s['mist-soft'] ); ?>;
	--magenta: <?php echo esc_html( $s['magenta'] ); ?>;
	--amber: <?php echo esc_html( $s['amber'] ); ?>;
	--cube: <?php echo esc_html( $s['cube'] ); ?>;
	--display: "Space Grotesk", -apple-system, BlinkMacSystemFont, sans-serif;
	--sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
* { box-sizing: border-box; }
html { background: var(--surface-dark); }
body {
	margin: 0;
	min-height: 100vh;
	min-height: 100dvh;
	display: flex;
	flex-direction: column;
	color: var(--mist);
	font-family: var(--sans);
	font-size: 1.0625rem;
	line-height: 1.6;
	-webkit-font-smoothing: antialiased;
}
.balk { height: 4px; background: var(--cube); }
main {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: clamp(2.5rem, 8vw, 5rem) 1.5rem;
}
.kaart {
	width: 100%;
	max-width: 36rem;
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 1.25rem;
	animation: opkomen 0.6s ease-out both;
}
@keyframes opkomen { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
@media (prefers-reduced-motion: reduce) { .kaart { animation: none; } }
.logo { height: 4.5rem; width: auto; margin-bottom: 0.5rem; }
.woordmerk { font-family: var(--display); font-weight: 700; font-size: 1.5rem; letter-spacing: 0.02em; color: var(--base); margin: 0 0 0.5rem; }
h1 {
	margin: 0;
	font-family: var(--display);
	font-weight: 700;
	font-size: clamp(1.875rem, 5vw, 2.75rem);
	line-height: 1.1;
	letter-spacing: -0.02em;
	color: var(--base);
	text-wrap: balance;
}
.tekst p { margin: 0 0 0.75em; }
.tekst p:last-child { margin-bottom: 0; }
.terug {
	display: inline-flex;
	max-width: 100%;
	align-items: center;
	gap: 0.6rem;
	margin: 0;
	padding: 0.6rem 1rem 0.6rem 0.85rem;
	border: 1px solid var(--ink-line);
	border-radius: 999px;
	font-family: var(--display);
	font-weight: 500;
	font-size: 0.95rem;
	color: var(--base);
}
.terug::before { content: ""; width: 0.55rem; height: 0.55rem; border-radius: 50%; background: var(--amber); flex: none; }
.contact {
	margin: 0.5rem 0 0;
	padding: 1.25rem 0 0;
	border-top: 1px solid var(--ink-line);
	width: 100%;
	display: flex;
	flex-wrap: wrap;
	gap: 0.5rem 2rem;
	font-family: var(--display);
	font-size: 0.95rem;
}
.contact a { color: var(--base); text-decoration: none; border-bottom: 1px solid var(--mist-soft); transition: color 0.15s, border-color 0.15s; }
.contact a:hover, .contact a:focus-visible { color: var(--magenta); border-color: var(--magenta); outline: none; }
footer { padding: 1.5rem; text-align: center; font-size: 0.8rem; color: var(--mist-soft); }
</style>
</head>
<body>
<div class="balk" aria-hidden="true"></div>
<main>
	<div class="kaart">
		<?php if ( $s['logo-url'] ) : ?>
			<img class="logo" src="<?php echo esc_url( $s['logo-url'] ); ?>" alt="3DUCATION" width="160" height="200">
		<?php else : ?>
			<p class="woordmerk">3DUCATION</p>
		<?php endif; ?>
		<h1><?php echo esc_html( $titel ); ?></h1>
		<div class="tekst"><?php echo threeducation_onderhoud_alineas( $tekst ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ge-escaped in de functie. ?></div>
		<?php if ( $einde ) : ?>
			<p class="terug">
				<?php
				/* translators: %s: dag en tijdstip, bv. "woensdag 10 september om 09:00". */
				echo esc_html( sprintf( __( 'Opnieuw online op %s', '3ducation' ), $einde ) );
				?>
			</p>
		<?php endif; ?>
		<?php if ( $telefoon || $email ) : ?>
			<p class="contact">
				<?php if ( $telefoon ) : ?>
					<a href="tel:<?php echo esc_attr( $tel_href ); ?>"><?php echo esc_html( $telefoon ); ?></a>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>
</main>
<footer><?php echo esc_html__( 'Onderhoud: deze pagina verdwijnt vanzelf zodra we klaar zijn.', '3ducation' ); ?></footer>
</body>
</html>
	<?php
}

/* -------------------------------------------------------------------------
 * Beheer: beheerbalk + instellingenscherm
 * ---------------------------------------------------------------------- */

/** Herinnering in de beheerbalk zolang de modus loopt of gepland staat. */
function threeducation_onderhoud_admin_bar( $bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = threeducation_onderhoud_settings();
	if ( 'actief' !== $o['status'] && 'gepland' !== $o['status'] ) {
		return;
	}

	$label = 'actief' === $o['status']
		? ( 'shop' === $o['bereik'] ? __( 'Onderhoudsmodus actief (webshop)', '3ducation' ) : __( 'Onderhoudsmodus actief', '3ducation' ) )
		: sprintf(
			/* translators: %s: dag en tijdstip. */
			__( 'Onderhoud gepland: %s', '3ducation' ),
			wp_date( __( 'j F H:i', '3ducation' ), $o['start_ts'], wp_timezone() )
		);

	$bar->add_node(
		array(
			'id'    => 'threeducation-onderhoud',
			'title' => '<span class="ab-icon dashicons dashicons-hammer" style="margin-top:2px;"></span>' . esc_html( $label ),
			'href'  => admin_url( 'options-general.php?page=threeducation-onderhoud' ),
			'meta'  => array( 'class' => 'actief' === $o['status'] ? 'threeducation-onderhoud-actief' : '' ),
		)
	);
}
add_action( 'admin_bar_menu', 'threeducation_onderhoud_admin_bar', 90 );

/** Rode achtergrond voor het beheerbalk-item als de modus loopt. */
function threeducation_onderhoud_admin_bar_css() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}
	echo '<style>#wpadminbar .threeducation-onderhoud-actief > .ab-item{background:#b32d2e!important;color:#fff!important;}#wpadminbar .threeducation-onderhoud-actief:hover > .ab-item{background:#8a2223!important;}</style>';
}
add_action( 'wp_head', 'threeducation_onderhoud_admin_bar_css' );
add_action( 'admin_head', 'threeducation_onderhoud_admin_bar_css' );

/** Het scherm onder Instellingen. */
function threeducation_onderhoud_admin_menu() {
	add_options_page(
		__( 'Onderhoudsmodus', '3ducation' ),
		__( 'Onderhoudsmodus', '3ducation' ),
		'manage_options',
		'threeducation-onderhoud',
		'threeducation_onderhoud_render_admin_page'
	);
}
add_action( 'admin_menu', 'threeducation_onderhoud_admin_menu' );

/** Render het instellingenscherm. */
function threeducation_onderhoud_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o        = threeducation_onderhoud_settings();
	$defaults = threeducation_onderhoud_defaults();
	$formaat  = __( 'j F Y \o\m H:i', '3ducation' );
	$voorbeeld = add_query_arg( 'onderhoud', $o['sleutel'], home_url( '/' ) );

	switch ( $o['status'] ) {
		case 'actief':
			$status_kleur = '#b32d2e';
			$status_tekst = 'shop' === $o['bereik']
				? __( 'Actief: bezoekers zien de onderhoudspagina op de webshop.', '3ducation' )
				: __( 'Actief: bezoekers zien nu de onderhoudspagina.', '3ducation' );
			if ( $o['einde_ts'] ) {
				/* translators: %s: dag en tijdstip. */
				$status_tekst .= ' ' . sprintf( __( 'Eindigt vanzelf op %s.', '3ducation' ), wp_date( $formaat, $o['einde_ts'], wp_timezone() ) );
			}
			break;
		case 'gepland':
			$status_kleur = '#996800';
			/* translators: %s: dag en tijdstip. */
			$status_tekst = sprintf( __( 'Gepland: start vanzelf op %s.', '3ducation' ), wp_date( $formaat, $o['start_ts'], wp_timezone() ) );
			break;
		case 'afgelopen':
			$status_kleur = '#8a8d99';
			/* translators: %s: dag en tijdstip. */
			$status_tekst = sprintf( __( 'Afgelopen op %s. De website is gewoon bereikbaar; zet het vinkje uit of kies een nieuwe periode.', '3ducation' ), wp_date( $formaat, $o['einde_ts'], wp_timezone() ) );
			break;
		default:
			$status_kleur = '#0a7d2c';
			$status_tekst = __( 'Uit: de website is gewoon bereikbaar.', '3ducation' );
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Onderhoudsmodus', '3ducation' ); ?></h1>
		<p><?php echo esc_html__( 'Zet de website tijdelijk op "even geduld", bijvoorbeeld tijdens een update of een verhuis. Bezoekers zien een pagina in de huisstijl; jij en de kassa werken gewoon verder. Zoekmachines krijgen de melding "tijdelijk onbeschikbaar" en houden de pagina\'s bij.', '3ducation' ); ?></p>

		<p>
			<strong><?php echo esc_html__( 'Status:', '3ducation' ); ?></strong>
			<span style="color:<?php echo esc_attr( $status_kleur ); ?>;"><?php echo esc_html( $status_tekst ); ?></span>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'threeducation_onderhoud' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php echo esc_html__( 'Inschakelen', '3ducation' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="threeducation_onderhoud[enabled]" value="1" <?php checked( $o['enabled'], 1 ); ?> />
							<?php echo esc_html__( 'Onderhoudsmodus aan', '3ducation' ); ?>
						</label>
						<p class="description"><?php echo esc_html__( 'Zonder begin- en eindmoment gaat hij meteen aan en blijft hij aan tot je het vinkje weer uitzet.', '3ducation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Bereik', '3ducation' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="threeducation_onderhoud[bereik]" value="site" <?php checked( $o['bereik'], 'site' ); ?> /> <?php echo esc_html__( 'Hele website', '3ducation' ); ?></label><br>
							<label><input type="radio" name="threeducation_onderhoud[bereik]" value="shop" <?php checked( $o['bereik'], 'shop' ); ?> /> <?php echo esc_html__( 'Alleen de webshop (shop, producten, winkelmandje, afrekenen, account)', '3ducation' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tdo-start"><?php echo esc_html__( 'Begint op (optioneel)', '3ducation' ); ?></label></th>
					<td>
						<input type="datetime-local" id="tdo-start" name="threeducation_onderhoud[start]" value="<?php echo esc_attr( $o['start'] ); ?>" step="60" />
						<p class="description">
							<?php
							/* translators: %s: naam van de tijdzone. */
							echo esc_html( sprintf( __( 'Tijdzone van de site: %s.', '3ducation' ), wp_timezone_string() ) );
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tdo-einde"><?php echo esc_html__( 'Eindigt op (optioneel)', '3ducation' ); ?></label></th>
					<td>
						<input type="datetime-local" id="tdo-einde" name="threeducation_onderhoud[einde]" value="<?php echo esc_attr( $o['einde'] ); ?>" step="60" />
						<p class="description"><?php echo esc_html__( 'Staat ook op de onderhoudspagina als "Opnieuw online op …". Na dit moment is de website vanzelf weer bereikbaar.', '3ducation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tdo-titel"><?php echo esc_html__( 'Titel', '3ducation' ); ?></label></th>
					<td><input type="text" id="tdo-titel" name="threeducation_onderhoud[titel]" value="<?php echo esc_attr( $o['titel'] ); ?>" class="large-text" placeholder="<?php echo esc_attr( $defaults['titel'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="tdo-tekst"><?php echo esc_html__( 'Tekst', '3ducation' ); ?></label></th>
					<td>
						<textarea id="tdo-tekst" name="threeducation_onderhoud[tekst]" rows="5" class="large-text" placeholder="<?php echo esc_attr( $defaults['tekst'] ); ?>"><?php echo esc_textarea( $o['tekst'] ); ?></textarea>
						<p class="description"><?php echo esc_html__( 'Een lege regel begint een nieuwe alinea. Telefoonnummer en e-mailadres komen automatisch uit Instellingen → Footer.', '3ducation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tdo-voorbeeld"><?php echo esc_html__( 'Voorbeeldlink', '3ducation' ); ?></label></th>
					<td>
						<input type="text" id="tdo-voorbeeld" value="<?php echo esc_attr( $voorbeeld ); ?>" class="large-text code" readonly onfocus="this.select();" />
						<p class="description"><?php echo esc_html__( 'Wie deze link opent, krijgt 24 uur lang de gewone website te zien, ook zonder in te loggen. Handig om te testen op je telefoon of om iemand toch toegang te geven. Ingelogde beheerders en winkelbeheerders (kassa) hebben de link niet nodig.', '3ducation' ); ?></p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<?php submit_button( null, 'primary', 'submit', false ); ?>
				<a class="button" href="<?php echo esc_url( threeducation_onderhoud_voorbeeld_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Voorbeeld bekijken', '3ducation' ); ?></a>
				<span class="description" style="margin-left:0.5em;"><?php echo esc_html__( 'Opent de onderhoudspagina in een nieuw tabblad met de opgeslagen instellingen, zonder de modus aan te zetten. Sla eerst op als je iets wijzigde.', '3ducation' ); ?></span>
			</p>
		</form>
	</div>
	<?php
}
