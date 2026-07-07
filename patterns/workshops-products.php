<?php
/**
 * Title: Workshops te boeken (sessies)
 * Slug: 3ducation/workshops-products
 * Categories: 3ducation, woocommerce
 * Description: Toont de boekbare workshop-sessies (producten uit de categorie 'workshops') op de workshops-pagina. Inschrijven = aankoop via de webshop. Verlopen sessies (voorbij hun zichtbaarheidsvenster _visible_until) worden standaard getoond met een 'Verlopen'-label; via de knop kun je ze verbergen.
 */

if ( ! function_exists( 'wc_get_products' ) ) {
	return;
}

// Toggle: standaard verbergen we verlopen sessies. ?toon_verlopen=1 toont ze alsnog (voor wie de historiek wil zien).
$threeducation_show_expired = isset( $_GET['toon_verlopen'] ) && '1' === $_GET['toon_verlopen']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- leesactie, geen state-wijziging.

// Haal ALLE gepubliceerde workshops op, óók buiten hun venster. Het globale
// pre_get_posts-filter sluit verlopen producten normaal uit de webshop; hier
// ontkoppelen we het bewust zodat deze pagina afgelopen sessies kan tonen.
if ( function_exists( 'threeducation_filter_product_queries' ) ) {
	remove_action( 'pre_get_posts', 'threeducation_filter_product_queries' );
}
$threeducation_workshop_ids = wc_get_products(
	array(
		'status'   => 'publish',
		'limit'    => -1,
		'category' => array( 'workshops' ),
		'return'   => 'ids',
	)
);
if ( function_exists( 'threeducation_filter_product_queries' ) ) {
	add_action( 'pre_get_posts', 'threeducation_filter_product_queries' );
}

if ( empty( $threeducation_workshop_ids ) ) {
	return;
}

// Bepaal per sessie of ze verlopen is (voorbij _visible_until) en sorteer:
// eerst de eerstvolgende (op datum), verlopen sessies achteraan.
$threeducation_today = current_time( 'Y-m-d' );
$threeducation_items = array();
foreach ( $threeducation_workshop_ids as $threeducation_id ) {
	$threeducation_until = get_post_meta( $threeducation_id, '_visible_until', true );
	$threeducation_items[] = array(
		'id'      => $threeducation_id,
		'until'   => $threeducation_until,
		'expired' => ( ! empty( $threeducation_until ) && $threeducation_today > $threeducation_until ),
	);
}
usort(
	$threeducation_items,
	static function ( $a, $b ) {
		if ( $a['expired'] !== $b['expired'] ) {
			return $a['expired'] ? 1 : -1;
		}
		$au = '' !== $a['until'] ? $a['until'] : '9999-12-31';
		$bu = '' !== $b['until'] ? $b['until'] : '9999-12-31';
		return strcmp( $au, $bu );
	}
);

$threeducation_has_expired = (bool) array_filter( wp_list_pluck( $threeducation_items, 'expired' ) );
$threeducation_accents     = array( 'magenta', 'cyan', 'amber' );
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:paragraph {"className":"print-eyebrow print-eyebrow--cyan","fontSize":"small","fontFamily":"display"} -->
<p class="print-eyebrow print-eyebrow--cyan has-display-font-family has-small-font-size">Boek je plek</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Kies een workshopsessie</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|ink-soft"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--ink-soft)">Inschrijven gebeurt eenvoudig via de webshop: kies hieronder een datum en reserveer je plek. Op is op &mdash; het aantal plaatsen per sessie is beperkt.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<?php if ( $threeducation_has_expired ) : ?>
<div class="workshop-toolbar">
	<?php if ( $threeducation_show_expired ) : ?>
		<a href="<?php echo esc_url( remove_query_arg( 'toon_verlopen' ) ); ?>#sessies"><?php echo esc_html__( 'Verberg verlopen sessies', '3ducation' ); ?></a>
	<?php else : ?>
		<a href="<?php echo esc_url( add_query_arg( 'toon_verlopen', '1' ) ); ?>#sessies"><?php echo esc_html__( 'Toon ook verlopen sessies', '3ducation' ); ?></a>
	<?php endif; ?>
</div>
<?php endif; ?>
<div class="workshop-list" id="sessies">
<?php
$threeducation_i = 0;
foreach ( $threeducation_items as $threeducation_item ) :
	if ( ! $threeducation_show_expired && $threeducation_item['expired'] ) {
		continue;
	}
	$threeducation_product = wc_get_product( $threeducation_item['id'] );
	if ( ! $threeducation_product ) {
		continue;
	}
	$threeducation_accent  = $threeducation_accents[ $threeducation_i % 3 ];
	++$threeducation_i;
	$threeducation_title   = $threeducation_product->get_name();
	$threeducation_price   = $threeducation_product->get_price_html();
	$threeducation_image   = $threeducation_product->get_image( 'woocommerce_thumbnail' );
	$threeducation_expired = $threeducation_item['expired'];

	if ( $threeducation_expired ) :
		?>
		<div class="spotlight-card spotlight-card--<?php echo esc_attr( $threeducation_accent ); ?> spotlight-card--expired">
			<span class="spotlight-card__media"><?php echo wp_kses_post( $threeducation_image ); ?></span>
			<span class="spotlight-card__body">
				<span class="spotlight-card__title"><?php echo esc_html( $threeducation_title ); ?></span>
				<?php if ( '' !== $threeducation_price ) : ?>
					<span class="spotlight-card__price"><?php echo wp_kses_post( $threeducation_price ); ?></span>
				<?php endif; ?>
				<span class="workshop-badge"><?php echo esc_html__( 'Verlopen', '3ducation' ); ?></span>
			</span>
		</div>
		<?php
	else :
		?>
		<a class="spotlight-card spotlight-card--<?php echo esc_attr( $threeducation_accent ); ?>" href="<?php echo esc_url( get_permalink( $threeducation_item['id'] ) ); ?>">
			<span class="spotlight-card__media"><?php echo wp_kses_post( $threeducation_image ); ?></span>
			<span class="spotlight-card__body">
				<span class="spotlight-card__title"><?php echo esc_html( $threeducation_title ); ?></span>
				<?php if ( '' !== $threeducation_price ) : ?>
					<span class="spotlight-card__price"><?php echo wp_kses_post( $threeducation_price ); ?></span>
				<?php endif; ?>
				<span class="spotlight-card__cta"><?php echo esc_html__( 'Bekijk &amp; boek', '3ducation' ); ?> &rarr;</span>
			</span>
		</a>
		<?php
	endif;
endforeach;
?>
</div>
<!-- /wp:html --></div>
<!-- /wp:group -->
