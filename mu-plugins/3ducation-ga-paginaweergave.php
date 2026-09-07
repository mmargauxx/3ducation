<?php
/**
 * Plugin Name: 3DUCATION GA4 zonder dubbele paginaweergaven
 * Description: Laat "Google Analytics for WooCommerce" alleen de e-commerce-events (view_item, add_to_cart, purchase, …) sturen en géén eigen page_view. De paginaweergaven komen van MonsterInsights, die dezelfde GA4-property gebruikt; zonder dit bestand telt elke pagina dubbel.
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Achtergrond: Google Analytics for WooCommerce 2.x heeft geen vinkje
 * "standard tracking" meer en roept altijd gtag("config", G-…) aan, wat een
 * page_view stuurt. MonsterInsights doet dat ook, voor dezelfde ID. Deze filter
 * zet send_page_view uit op de WooCommerce-config; de events blijven werken.
 *
 * Wordt MonsterInsights ooit gedeactiveerd, verwijder dan dit bestand, anders
 * verdwijnen de paginaweergaven helemaal.
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
