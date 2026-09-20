<?php
/**
 * Plugin Name: 3DUCATION staging veilig
 * Description: Sluit op een kopie van de live site alles af wat naar buiten gaat: e-mail, webhooks (OnFact) en elk extern HTTP-verzoek (Mollie, bpost, MailPoet, statistieken).
 * Version: 1.1.0
 * Author: 3DUCATION
 *
 * Een kopie van de live database neemt alles mee: de FluentSMTP-instellingen,
 * de OnFact-webhook, de MailPoet-lijst met geplande taken, de sleutels van
 * Mollie en bpost. Zodra er op de kopie PHP draait -- een cronrun, een
 * statuswijziging, een openstaande Action Scheduler-taak -- vertrekt dat naar
 * de échte klanten en de échte koppelingen.
 *
 * Dit bestand knipt drie draden door:
 *  1. `pre_wp_mail` -> false: geen enkele mail vertrekt. Dit draait vóór elke
 *     SMTP-plugin; FluentSMTP deactiveren is níét genoeg, want dan valt
 *     WordPress terug op PHP mail() (zie memory mail-killswitch-bulk-import).
 *  2. `woocommerce_webhook_should_deliver` -> false: geen webhook vertrekt,
 *     dus OnFact maakt geen facturen aan voor testbestellingen.
 *  3. `pre_http_request` -> WP_Error voor elk verzoek naar buiten. Dat vangt
 *     alles wat niet via wp_mail of een webhook loopt: Mollie, bpost,
 *     MailPoet-API, MonsterInsights, Jetpack, licentiecontroles.
 *
 * VEILIGHEIDSGRENDEL: het bestand doet alleen iets als de site *niet* de live
 * site is. Belandt deze kopie ooit per ongeluk op de live server, dan blijft
 * live gewoon mailen.
 *
 * Er wordt eerst naar het *bestandspad* gekeken, niet naar de URL: een verse
 * kopie heeft de live-URL's nog in de database staan (EasyHost zet geen
 * WP_HOME in wp-config.php), dus een URL-controle zou zichzelf precies op het
 * gevaarlijkste moment uitschakelen -- vóór de search-replace, bij het eerste
 * inloggen. Staat de installatie onder /subsites/, dan is het per definitie
 * een kopie. Pas als dat pad er niet is valt hij terug op het domein.
 *
 * Boven in wp-admin staat een rode balk zolang het actief is, zodat niemand
 * per ongeluk denkt dat hij op live zit.
 *
 * Weghalen = het bestand verwijderen. Doe dat nooit op een kopie waar nog
 * echte klantgegevens in staan zonder eerst de webhooks, MailPoet en de
 * betaalkoppelingen uit te zetten.
 *
 * Installeren: naar wp-content/mu-plugins/ van de kopie uploaden. Geen
 * activatiestap.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Draait deze site op het live domein?
 *
 * Zo ja, dan doet dit bestand niets.
 */
function threeducation_staging_host_is_live( $host ) {
	$host = strtolower( (string) $host );
	$host = preg_replace( '/^www\./', '', $host );

	return '3ducation.be' === $host;
}

/**
 * Draait deze site op het live domein?
 *
 * Zo ja, dan doet dit bestand niets. Zie de grendel boven in dit bestand:
 * het pad wint van de URL, want een verse kopie draagt de live-URL's nog.
 */
function threeducation_staging_is_live() {
	// 1. Het bestandspad. De kopie staat onder /subsites/<host>/, live in /www/.
	$path = str_replace( '\\', '/', ABSPATH );
	if ( false !== strpos( $path, '/subsites/' ) ) {
		return false;
	}

	// 2. Geen kopiepad gevonden: terugval op het domein.
	$home = defined( 'WP_HOME' ) ? WP_HOME : get_option( 'home' );

	return threeducation_staging_host_is_live( wp_parse_url( (string) $home, PHP_URL_HOST ) );
}

if ( ! threeducation_staging_is_live() ) {

	/** 1. Geen enkele uitgaande mail. */
	add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );

	/** 2. Geen enkele WooCommerce-webhook (OnFact). */
	add_filter( 'woocommerce_webhook_should_deliver', '__return_false', PHP_INT_MAX );

	/**
	 * 3. Geen enkel verzoek naar buiten.
	 *
	 * Verzoeken naar de site zelf en naar localhost blijven werken, zodat
	 * wp-admin en de REST API van de kopie normaal doen.
	 */
	function threeducation_staging_block_http( $preempt, $args, $url ) {
		$host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );

		$allowed = array( 'localhost', '127.0.0.1' );

		// De hostnaam waarop dit verzoek binnenkwam -- dat is de kopie zelf.
		if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
			$allowed[] = strtolower( (string) $_SERVER['HTTP_HOST'] );
		}

		/*
		 * home_url() mag hier alleen bij als het níét het live domein is. Op een
		 * verse kopie staat de live-URL nog in de database; zonder deze
		 * controle zou de kopie de échte site mogen bevragen (loopback,
		 * Site Health, REST).
		 */
		$home_host = strtolower( (string) wp_parse_url( (string) get_option( 'home' ), PHP_URL_HOST ) );
		if ( '' !== $home_host && ! threeducation_staging_host_is_live( $home_host ) ) {
			$allowed[] = $home_host;
		}

		$allowed = apply_filters( 'threeducation_staging_allowed_hosts', $allowed );

		if ( '' === $host || in_array( $host, $allowed, true ) ) {
			return $preempt;
		}

		return new WP_Error(
			'threeducation_staging_geblokkeerd',
			sprintf( 'Geblokkeerd door "3DUCATION staging veilig": verzoek naar %s.', $host )
		);
	}
	add_filter( 'pre_http_request', 'threeducation_staging_block_http', PHP_INT_MAX, 3 );

	/** Zichtbare waarschuwing in wp-admin. */
	function threeducation_staging_notice() {
		echo '<div class="notice notice-error"><p><strong>Testomgeving.</strong> '
			. 'E-mail, webhooks (OnFact) en alle externe koppelingen zijn uitgeschakeld door de '
			. 'mu-plugin <code>3ducation-staging-veilig.php</code>. Er vertrekt niets naar klanten.</p></div>';
	}
	add_action( 'admin_notices', 'threeducation_staging_notice' );

	/** En in de adminbalk, ook op de voorkant. */
	function threeducation_staging_admin_bar( $bar ) {
		$bar->add_node(
			array(
				'id'    => 'threeducation-staging',
				'title' => 'TESTOMGEVING — mail & koppelingen uit',
				'meta'  => array( 'title' => 'Uitgaande mail, webhooks en externe verzoeken zijn geblokkeerd.' ),
			)
		);
	}
	add_action( 'admin_bar_menu', 'threeducation_staging_admin_bar', 100 );

	function threeducation_staging_admin_bar_style() {
		echo '<style>#wpadminbar #wp-admin-bar-threeducation-staging{background:#c20f59}'
			. '#wpadminbar #wp-admin-bar-threeducation-staging .ab-item{color:#fff;font-weight:700}</style>';
	}
	add_action( 'admin_head', 'threeducation_staging_admin_bar_style' );
	add_action( 'wp_head', 'threeducation_staging_admin_bar_style' );
}
