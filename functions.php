<?php
/**
 * 3ducation theme functions.
 *
 * Block theme: most setup is data-driven (theme.json, templates, parts).
 * This file wires up theme supports, asset enqueuing, pattern categories,
 * and WooCommerce integration.
 *
 * @package 3ducation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'THREEDUCATION_VERSION' ) ) {
	define( 'THREEDUCATION_VERSION', '0.11.2' );
}

/**
 * Theme setup.
 */
function threeducation_setup() {
	// Core block-theme / general supports.
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/custom.css' );

	// Make theme available for translation.
	load_theme_textdomain( '3ducation', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'threeducation_setup' );

/**
 * Declare WooCommerce support and product gallery features.
 */
function threeducation_woocommerce_setup() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'threeducation_woocommerce_setup' );

/**
 * Enqueue front-end styles.
 *
 * theme.json drives the design system; custom.css holds only the
 * supplementary rules that theme.json can't express.
 */
function threducation_enqueue_assets() {
	wp_enqueue_style(
		'threeducation-custom',
		get_template_directory_uri() . '/assets/custom.css',
		array(),
		THREEDUCATION_VERSION
	);

	// Only ship the notice script when a notice is actually live.
	$notice = threeducation_notice_settings();
	if ( ! empty( $notice['active'] ) ) {
		wp_enqueue_script(
			'threeducation-notice',
			get_template_directory_uri() . '/assets/notice.js',
			array(),
			THREEDUCATION_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'threducation_enqueue_assets' );

/**
 * Site notice: settings, banner output, and admin screen.
 *
 * A dismissible announcement bar at the very top of every page, for special
 * opening or closing days. Fully editable from the WordPress admin under
 * Settings -> Site melding (no code required).
 */

/** Default option values. */
function threeducation_notice_defaults() {
	return array(
		'enabled'    => 0,
		'message'    => '',
		'cta_label'  => '',
		'cta_url'    => '',
		'start_date' => '',
		'end_date'   => '',
	);
}

/**
 * Resolved notice settings: stored options merged with defaults, plus a
 * computed 'active' flag (respects the on/off toggle and the optional date
 * window) and an 'id' that changes whenever the content changes, so edits
 * re-show the bar to visitors who dismissed a previous version.
 */
function threeducation_notice_settings() {
	$o     = wp_parse_args( (array) get_option( 'threeducation_notice', array() ), threeducation_notice_defaults() );
	$today = current_time( 'Y-m-d' );

	$active = ! empty( $o['enabled'] ) && '' !== trim( (string) $o['message'] );
	if ( $active && $o['start_date'] && $today < $o['start_date'] ) {
		$active = false;
	}
	if ( $active && $o['end_date'] && $today > $o['end_date'] ) {
		$active = false;
	}

	$o['active'] = $active;
	$o['id']     = substr( md5( $o['message'] . '|' . $o['cta_label'] . '|' . $o['cta_url'] . '|' . $o['start_date'] . '|' . $o['end_date'] ), 0, 10 );

	return $o;
}

/**
 * Output the announcement bar at the very top of the document body.
 *
 * The inline script hides the bar before paint for visitors who already
 * dismissed this exact notice; assets/notice.js wires the close button.
 */
function threeducation_render_notice_bar() {
	$n = threeducation_notice_settings();
	if ( empty( $n['active'] ) ) {
		return;
	}

	$has_cta = '' !== $n['cta_label'] && '' !== $n['cta_url'];
	?>
	<div id="site-notice" class="site-notice-bar" data-notice-id="<?php echo esc_attr( $n['id'] ); ?>" role="region" aria-label="<?php echo esc_attr__( 'Aankondiging', '3ducation' ); ?>">
		<div class="site-notice-bar__inner">
			<p class="site-notice-bar__text"><?php echo esc_html( $n['message'] ); ?></p>
			<?php if ( $has_cta ) : ?>
				<a class="site-notice-bar__cta" href="<?php echo esc_url( $n['cta_url'] ); ?>"><?php echo esc_html( $n['cta_label'] ); ?> &rarr;</a>
			<?php endif; ?>
			<button type="button" class="site-notice-bar__close" data-notice-close aria-label="<?php echo esc_attr__( 'Melding sluiten', '3ducation' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
			</button>
		</div>
	</div>
	<script>
	( function () {
		var b = document.getElementById( 'site-notice' );
		if ( ! b ) {
			return;
		}
		var show = true;
		try {
			show = window.localStorage.getItem( '3ducation:notice-dismissed' ) !== b.getAttribute( 'data-notice-id' );
		} catch ( e ) {}
		if ( show ) {
			b.classList.add( 'is-visible' );
		}
	}() );
	</script>
	<?php
}
add_action( 'wp_body_open', 'threeducation_render_notice_bar' );

/**
 * Register the notice option with the Settings API.
 */
function threeducation_notice_register_settings() {
	register_setting(
		'threeducation_notice',
		'threeducation_notice',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'threeducation_notice_sanitize',
			'default'           => threeducation_notice_defaults(),
		)
	);
}
add_action( 'admin_init', 'threeducation_notice_register_settings' );

/** Sanitize submitted notice settings. */
function threeducation_notice_sanitize( $input ) {
	$input = (array) $input;

	$clean_date = static function ( $value ) {
		$value = trim( (string) $value );
		$d     = DateTime::createFromFormat( 'Y-m-d', $value );
		return ( $d && $d->format( 'Y-m-d' ) === $value ) ? $value : '';
	};

	return array(
		'enabled'    => empty( $input['enabled'] ) ? 0 : 1,
		'message'    => sanitize_text_field( $input['message'] ?? '' ),
		'cta_label'  => sanitize_text_field( $input['cta_label'] ?? '' ),
		'cta_url'    => empty( $input['cta_url'] ) ? '' : esc_url_raw( trim( (string) $input['cta_url'] ) ),
		'start_date' => $clean_date( $input['start_date'] ?? '' ),
		'end_date'   => $clean_date( $input['end_date'] ?? '' ),
	);
}

/** Add the settings screen under the Settings menu. */
function threeducation_notice_admin_menu() {
	add_options_page(
		__( 'Site melding', '3ducation' ),
		__( 'Site melding', '3ducation' ),
		'manage_options',
		'threeducation-notice',
		'threeducation_notice_render_admin_page'
	);
}
add_action( 'admin_menu', 'threeducation_notice_admin_menu' );

/** Render the settings screen. */
function threeducation_notice_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$n = threeducation_notice_settings();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Site melding', '3ducation' ); ?></h1>
		<p><?php echo esc_html__( 'Toon een aankondigingsbalk bovenaan de website, bijvoorbeeld voor aangepaste openingsuren of sluitingsdagen.', '3ducation' ); ?></p>

		<p>
			<strong><?php echo esc_html__( 'Status:', '3ducation' ); ?></strong>
			<?php if ( $n['active'] ) : ?>
				<span style="color:#0a7d2c;"><?php echo esc_html__( 'Zichtbaar op de website.', '3ducation' ); ?></span>
			<?php else : ?>
				<span style="color:#8a8d99;"><?php echo esc_html__( 'Niet zichtbaar (uitgeschakeld, leeg, of buiten de ingestelde periode).', '3ducation' ); ?></span>
			<?php endif; ?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'threeducation_notice' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php echo esc_html__( 'Inschakelen', '3ducation' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="threeducation_notice[enabled]" value="1" <?php checked( $n['enabled'], 1 ); ?> />
							<?php echo esc_html__( 'Toon de meldingsbalk', '3ducation' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tdn-message"><?php echo esc_html__( 'Bericht', '3ducation' ); ?></label></th>
					<td><textarea id="tdn-message" name="threeducation_notice[message]" rows="2" class="large-text"><?php echo esc_textarea( $n['message'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="tdn-cta-label"><?php echo esc_html__( 'Knoptekst (optioneel)', '3ducation' ); ?></label></th>
					<td><input type="text" id="tdn-cta-label" name="threeducation_notice[cta_label]" value="<?php echo esc_attr( $n['cta_label'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="tdn-cta-url"><?php echo esc_html__( 'Knoplink (optioneel)', '3ducation' ); ?></label></th>
					<td>
						<input type="text" id="tdn-cta-url" name="threeducation_notice[cta_url]" value="<?php echo esc_attr( $n['cta_url'] ); ?>" class="regular-text" placeholder="/shop" />
						<p class="description"><?php echo esc_html__( 'Bijvoorbeeld /shop of een volledige URL.', '3ducation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tdn-start"><?php echo esc_html__( 'Tonen vanaf (optioneel)', '3ducation' ); ?></label></th>
					<td><input type="date" id="tdn-start" name="threeducation_notice[start_date]" value="<?php echo esc_attr( $n['start_date'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="tdn-end"><?php echo esc_html__( 'Tonen tot en met (optioneel)', '3ducation' ); ?></label></th>
					<td>
						<input type="date" id="tdn-end" name="threeducation_notice[end_date]" value="<?php echo esc_attr( $n['end_date'] ); ?>" />
						<p class="description"><?php echo esc_html__( 'Laat de datums leeg om de melding meteen en onbeperkt te tonen.', '3ducation' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Normalise the shop/loop add-to-cart button labels:
 *   – "In winkelwagen"  — products that add straight to the cart (ajax);
 *   – "Selecteer opties" — products sent to their page to pick a variation
 *     or fill options (variable, or simple products with PPOM options);
 *   – default ("Lees meer") — unavailable / out-of-stock products.
 *
 * Runs at a late priority so it wins over plugins (e.g. PPOM) that also
 * filter this label. Mirrors the button-colour states in custom.css, which
 * key off the same add_to_cart_button / ajax_add_to_cart classes.
 */
function threeducation_add_to_cart_text( $text, $product ) {
	if ( ! $product || ! ( $product->is_purchasable() && $product->is_in_stock() ) ) {
		return $text;
	}
	return $product->supports( 'ajax_add_to_cart' )
		? __( 'In winkelwagen', '3ducation' )
		: __( 'Selecteer opties', '3ducation' );
}
add_filter( 'woocommerce_product_add_to_cart_text', 'threeducation_add_to_cart_text', 9999, 2 );

/**
 * Register a pattern category so the theme's patterns are grouped
 * together in the inserter.
 */
function threeducation_register_pattern_categories() {
	register_block_pattern_category(
		'3ducation',
		array( 'label' => __( '3DUCATION', '3ducation' ) )
	);
}
add_action( 'init', 'threeducation_register_pattern_categories' );

/**
 * SEO metadata for the three pillar pages.
 *
 * The theme has no SEO plugin, so we set a hand-written <title>, meta
 * description and meta keywords per pillar, keyed on the page slug. If an SEO
 * plugin (Yoast, Rank Math, …) is later activated it will manage these tags
 * itself; this is a lightweight fallback so the pillar pages ship with proper
 * metadata out of the box.
 */
function threeducation_pillar_seo() {
	return array(
		'oplossingen'          => array(
			'title'       => __( 'Webshop & oplossingen — 3D-printers voor scholen en thuis', '3ducation' ),
			'description' => __( 'Educatieve 3D-printpakketten voor basis- en secundaire scholen, plus voorgemonteerde 3D-printers voor thuis. Advies op maat, opleiding voor leerkrachten inbegrepen.', '3ducation' ),
			'keywords'    => __( '3D-printer kopen school, educatief pakket 3D-printen, 3D-printer basisschool, voorgemonteerde 3D-printer thuis', '3ducation' ),
		),
		'workshops'            => array(
			'title'       => __( 'Workshops & opleidingen — leer 3D-printen', '3ducation' ),
			'description' => __( 'Praktische 3D-printworkshops van ± 2 uur en originele verjaardagsfeestjes in 3D voor 10- tot 14-jarigen. Leer printen, ontwerpen in Tinkercad en slicen.', '3ducation' ),
			'keywords'    => __( '3D-print workshop, 3D-printer leren gebruiken, Tinkercad cursus, origineel verjaardagsfeestje 10 14 jaar, workshop 3D-tekenen', '3ducation' ),
		),
		'service'              => array(
			'title'       => __( 'Service & montage — 3D-printer herstelling en onderhoud', '3ducation' ),
			'description' => __( 'Herstelling, onderhoud en reserveonderdelen voor alle 3D-printers, ook als je toestel niet bij ons is gekocht. Vraag support aan via ons online formulier.', '3ducation' ),
			'keywords'    => __( '3D-printer herstelling, 3D-printer onderhoud, 3D-printer reparatie, reserveonderdelen 3D-printer, technische support 3D-printen', '3ducation' ),
		),
		'educatieve-pakketten' => array(
			'title'       => __( 'Educatieve pakketten — 3D-printen op school', '3ducation' ),
			'description' => __( 'Klaar-voor-de-klas 3D-printpakketten met installatie, lerarenopleiding en support. Op maat van basis- en secundaire scholen.', '3ducation' ),
			'keywords'    => __( 'educatief pakket 3D-printen, 3D-printer school, 3D-printen op school, lerarenopleiding 3D-printen', '3ducation' ),
		),
		'over-ons'             => array(
			'title'       => __( 'Over 3DUCATION — 3D-printen zonder zorgen, op school en thuis', '3ducation' ),
			'description' => __( '3DUCATION maakt 3D-printen praktisch en direct inzetbaar: voorgemonteerde printers, opleiding en support voor scholen en gezinnen. Je koopt geen doos, je koopt vertrouwen.', '3ducation' ),
			'keywords'    => __( 'over 3ducation, 3D-printen op school, 3D-printer thuis, STEM onderwijs 3D-printen, 3D-printen zonder zorgen', '3ducation' ),
		),
	);
}

/** Return the SEO slug for the current page, or '' if it isn't a pillar page. */
function threeducation_current_pillar_slug() {
	if ( ! is_page() ) {
		return '';
	}
	$object = get_queried_object();
	$slug   = ( $object && isset( $object->post_name ) ) ? $object->post_name : '';
	return array_key_exists( $slug, threeducation_pillar_seo() ) ? $slug : '';
}

/** Override the document <title> for pillar pages. */
function threeducation_pillar_title_parts( $parts ) {
	$slug = threeducation_current_pillar_slug();
	if ( $slug ) {
		$seo            = threeducation_pillar_seo();
		$parts['title'] = $seo[ $slug ]['title'];
	}
	return $parts;
}
add_filter( 'document_title_parts', 'threeducation_pillar_title_parts' );

/** Output <meta name="description"> and <meta name="keywords"> for pillar pages. */
function threeducation_pillar_meta_tags() {
	$slug = threeducation_current_pillar_slug();
	if ( ! $slug ) {
		return;
	}
	$seo = threeducation_pillar_seo();
	printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $seo[ $slug ]['description'] ) );
	printf( '<meta name="keywords" content="%s" />' . "\n", esc_attr( $seo[ $slug ]['keywords'] ) );
}
add_action( 'wp_head', 'threeducation_pillar_meta_tags', 1 );

/**
 * Webshop product sorting.
 *
 * Replace WooCommerce's catalog "sorteer op" options with the set the shop
 * needs — A→Z, Z→A, gemiddelde beoordeling, prijs (beide richtingen) en
 * releasedatum — and implement the A→Z / Z→A title ordering that WooCommerce
 * doesn't ship out of the box.
 *
 * The archive-product template's Product Collection block inherits the main
 * query, so the catalog-sorting dropdown drives it via the ?orderby= param and
 * WC_Query's catalog ordering.
 */
function threeducation_catalog_orderby_options( $options ) {
	return array(
		'title'      => __( 'Naam: A → Z', '3ducation' ),
		'title-desc' => __( 'Naam: Z → A', '3ducation' ),
		'rating'     => __( 'Gemiddelde beoordeling', '3ducation' ),
		'price'      => __( 'Prijs: laag → hoog', '3ducation' ),
		'price-desc' => __( 'Prijs: hoog → laag', '3ducation' ),
		'date'       => __( 'Releasedatum: nieuwste eerst', '3ducation' ),
	);
}
add_filter( 'woocommerce_catalog_orderby', 'threeducation_catalog_orderby_options' );
add_filter( 'woocommerce_default_catalog_orderby_options', 'threeducation_catalog_orderby_options' );

/**
 * Default the shop to A → Z (WooCommerce's stored default is 'menu_order',
 * which we've removed from the options list above).
 */
function threeducation_default_catalog_orderby() {
	return 'title';
}
add_filter( 'woocommerce_default_catalog_orderby', 'threeducation_default_catalog_orderby' );

/**
 * Implement the two title-based orderings. WooCommerce splits 'title-desc' on
 * the hyphen before this filter runs, so we read the raw request value to tell
 * A→Z from Z→A. Runs late (priority 20) to win over WC's own args.
 */
function threeducation_catalog_ordering_args( $args ) {
	$orderby = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( '' === $orderby ) {
		$orderby = 'title';
	}
	if ( 'title' === $orderby || 'title-desc' === $orderby ) {
		$args['orderby']  = 'title';
		$args['order']    = ( 'title-desc' === $orderby ) ? 'DESC' : 'ASC';
		$args['meta_key'] = ''; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	}
	return $args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'threeducation_catalog_ordering_args', 20 );

/**
 * Spotlight products (shop).
 *
 * Up to three products featured in the strip at the top of the shop, chosen in
 * wp-admin under Settings -> Uitgelichte producten. Replaces the old single
 * flagship banner so the storefront leads with three products, not one.
 */

/** The selected spotlight product IDs, in order (max three, valid ints only). */
function threeducation_spotlight_ids() {
	$ids = (array) get_option( 'threeducation_spotlights', array() );
	$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
	return array_slice( $ids, 0, 3 );
}

/**
 * Resolve the selected spotlight products. Falls back to the three newest
 * published products so the strip is never empty out of the box.
 *
 * @return WC_Product[]
 */
function threeducation_spotlight_products() {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$out = array();
	foreach ( threeducation_spotlight_ids() as $id ) {
		$product = wc_get_product( $id );
		if ( $product && 'publish' === $product->get_status() ) {
			$out[] = $product;
		}
	}
	if ( empty( $out ) && function_exists( 'wc_get_products' ) ) {
		$fallback = wc_get_products(
			array(
				'status'     => 'publish',
				'visibility' => 'visible',
				'limit'      => 3,
				'orderby'    => 'date',
				'order'      => 'DESC',
			)
		);
		$out = is_array( $fallback ) ? $fallback : array();
	}
	return array_slice( $out, 0, 3 );
}

/** Register the spotlights option with the Settings API. */
function threeducation_spotlights_register_settings() {
	register_setting(
		'threeducation_spotlights',
		'threeducation_spotlights',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'threeducation_spotlights_sanitize',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'threeducation_spotlights_register_settings' );

/** Sanitize the submitted spotlight IDs: up to three unique positive ints. */
function threeducation_spotlights_sanitize( $input ) {
	$ids = array();
	foreach ( (array) $input as $value ) {
		$value = absint( $value );
		if ( $value ) {
			$ids[] = $value;
		}
	}
	return array_slice( array_values( array_unique( $ids ) ), 0, 3 );
}

/** Add the settings screen under the Settings menu. */
function threeducation_spotlights_admin_menu() {
	add_options_page(
		__( 'Uitgelichte producten', '3ducation' ),
		__( 'Uitgelichte producten', '3ducation' ),
		'manage_options',
		'threeducation-spotlights',
		'threeducation_spotlights_render_admin_page'
	);
}
add_action( 'admin_menu', 'threeducation_spotlights_admin_menu' );

/** Load WooCommerce's searchable product <select> on our settings screen only. */
function threeducation_spotlights_admin_assets( $hook ) {
	if ( 'settings_page_threeducation-spotlights' !== $hook || ! function_exists( 'WC' ) ) {
		return;
	}
	wp_enqueue_script( 'wc-enhanced-select' );
	wp_enqueue_style( 'woocommerce_admin_styles' );
	wp_enqueue_style( 'select2' );
}
add_action( 'admin_enqueue_scripts', 'threeducation_spotlights_admin_assets' );

/** Render the settings screen: three searchable product pickers. */
function threeducation_spotlights_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$ids     = array_pad( threeducation_spotlight_ids(), 3, 0 );
	$has_wc  = function_exists( 'wc_get_product' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Uitgelichte producten', '3ducation' ); ?></h1>
		<p><?php echo esc_html__( 'Kies tot drie producten die bovenaan de webshop worden uitgelicht. Laat een veld leeg om minder producten te tonen; laat alles leeg om automatisch de drie nieuwste producten te tonen.', '3ducation' ); ?></p>

		<?php if ( ! $has_wc ) : ?>
			<div class="notice notice-warning"><p><?php echo esc_html__( 'WooCommerce is niet actief, dus er zijn geen producten om te kiezen.', '3ducation' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'threeducation_spotlights' ); ?>
			<table class="form-table" role="presentation">
				<?php
				for ( $i = 0; $i < 3; $i++ ) :
					$id    = (int) $ids[ $i ];
					$label = '';
					if ( $id && $has_wc ) {
						$product = wc_get_product( $id );
						if ( $product ) {
							$label = wp_strip_all_tags( $product->get_formatted_name() );
						}
					}
					?>
					<tr>
						<th scope="row"><label for="tds-<?php echo esc_attr( $i ); ?>"><?php printf( esc_html__( 'Product %d', '3ducation' ), (int) $i + 1 ); ?></label></th>
						<td>
							<select
								id="tds-<?php echo esc_attr( $i ); ?>"
								class="wc-product-search"
								style="width:400px;"
								name="threeducation_spotlights[<?php echo esc_attr( $i ); ?>]"
								data-placeholder="<?php echo esc_attr__( 'Zoek een product…', '3ducation' ); ?>"
								data-action="woocommerce_json_search_products_and_variations"
								data-allow_clear="true">
								<?php if ( $id && '' !== $label ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>" selected="selected"><?php echo esc_html( $label ); ?></option>
								<?php endif; ?>
							</select>
						</td>
					</tr>
				<?php endfor; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
