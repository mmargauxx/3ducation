<?php
/**
 * Plugin Name: 3DUCATION GA4-correcties voor Google Analytics for WooCommerce
 * Description: Twee correcties op de plugin "Google Analytics for WooCommerce": (1) geen eigen page_view, want MonsterInsights stuurt die al voor dezelfde GA4-property (anders telt elke pagina dubbel); (2) geen tweede add_to_cart op de pagina ná een klassieke "Toevoegen aan winkelwagen" (anders telt elke toevoeging dubbel).
 * Version: 1.1.0
 * Author: 3DUCATION
 *
 * (1) Google Analytics for WooCommerce 2.x heeft geen vinkje "standard
 * tracking" meer en roept altijd gtag("config", G-…) aan, wat een page_view
 * stuurt. MonsterInsights doet dat ook, voor dezelfde ID. De filter hieronder
 * zet send_page_view uit op de WooCommerce-config; de e-commerce-events
 * blijven werken. Wordt MonsterInsights ooit gedeactiveerd, haal dan deze
 * filter weg, anders verdwijnen de paginaweergaven helemaal.
 *
 * (2) Bij een klassieke add-to-cart (formulier-POST op de productpagina, geen
 * redirect) stuurt de plugin add_to_cart op de POST-pagina zelf. Daarnaast
 * bewaart hij het event óók in de WooCommerce-sessie, bedoeld voor het geval
 * WooCommerce doorstuurt naar de winkelwagen. Hij doet dat zodra de filter
 * woocommerce_add_to_cart_redirect een URL oplevert — maar WooCommerce past
 * die filter ook toe bij het gewoon renderen van élke pagina (cart_url in de
 * JS-parameters wc_add_to_cart_params, class-wc-frontend-scripts.php), dus de
 * sessiesleutel wordt altijd gezet en de volgende pagina stuurt add_to_cart
 * nog een keer (gereproduceerd lokaal, plugin 2.4.1 + WooCommerce 11,
 * 2026-09-07). Hieronder: is in dit verzoek een product
 * toegevoegd én is de pagina gewoon gerenderd (wp_head is gelopen, dus geen
 * redirect), dan is het event al verstuurd en gooien we de sessiesleutel weg
 * vóór WooCommerce de sessie opslaat (shutdown, prioriteit 20).
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Geen activatiestap.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'woocommerce_ga_gtag_config',
	function ( $config ) {
		$config['send_page_view'] = false;
		return $config;
	}
);

add_action(
	'shutdown',
	function () {
		if ( ! did_action( 'woocommerce_add_to_cart' ) || ! did_action( 'wp_head' ) ) {
			return;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}
		if ( WC()->session->get( '_ga_pending_added_to_cart' ) ) {
			WC()->session->__unset( '_ga_pending_added_to_cart' );
		}
	},
	0
);
