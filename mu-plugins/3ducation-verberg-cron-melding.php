<?php
/**
 * Plugin Name: 3DUCATION verberg de WP-Cron-melding
 * Description: Verbergt in wp-admin de pluginmelding "WP Cron is disabled. Any scheduled discount will not work." De melding klopt niet: DISABLE_WP_CRON staat bewust aan omdat EasyHost wp-cron.php elke 5 minuten via een echte cronjob aanroept.
 * Version: 1.0.0
 * Author: 3DUCATION
 *
 * Wat dit wél en niet doet: het verandert niets aan cron of aan de plugin die de
 * melding toont. Het haalt enkel, puur cosmetisch, het meldingsblok uit de
 * beheerpagina zodra de tekst erin voorkomt. Verdwijnt de tekst ooit (plugin
 * verwijderd, melding aangepast), dan doet dit bestand niets meer.
 *
 * Toont de melding ooit iets anders (bv. "WP Cron is disabled" met een andere
 * tweede zin), pas dan de zoektekst hieronder aan.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Geen activatiestap.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;

function threeducation_verberg_cron_melding_script() {
	if ( ! is_admin() ) {
		return;
	}
	// Zoektekst: kleine letters, zonder overbodige spaties. Beide zinnen moeten kloppen,
	// zodat een échte cron-waarschuwing van een andere plugin zichtbaar blijft.
	$fragments = array( 'wp cron is disabled', 'scheduled discount will not work' );
	?>
	<script>
	( function () {
		var fragments = <?php echo wp_json_encode( $fragments ); ?>;
		function matches( el ) {
			var text = ( el.textContent || '' ).toLowerCase().replace( /\s+/g, ' ' );
			return fragments.every( function ( f ) { return text.indexOf( f ) !== -1; } );
		}
		function sweep( root ) {
			var nodes = ( root || document ).querySelectorAll( '.notice, .updated, .error, .notice-error, .notice-warning' );
			for ( var i = 0; i < nodes.length; i++ ) {
				if ( matches( nodes[ i ] ) ) {
					nodes[ i ].remove();
				}
			}
		}
		function start() {
			sweep();
			// Sommige plugins voegen hun melding pas later via JavaScript toe.
			if ( window.MutationObserver ) {
				new MutationObserver( function () { sweep(); } ).observe( document.body, { childList: true, subtree: true } );
			}
		}
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', start );
		} else {
			start();
		}
	} )();
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'threeducation_verberg_cron_melding_script', 99 );
