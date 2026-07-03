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
	define( 'THREEDUCATION_VERSION', '0.11.3' );
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
 * Dutch fallbacks for WooCommerce strings the nl_NL language pack does not yet
 * translate — mostly newer block / Interactivity-API strings (e.g. the shop
 * grid's "%d in cart" button state and a few screen-reader labels). WordPress
 * otherwise renders the English source next to already-translated copy.
 *
 * Gap-fill only: the override applies solely when the language pack returned
 * the untranslated source, so a future pack translation always wins. Trim
 * entries as upstream (translate.wordpress.org) coverage catches up.
 */
function threeducation_wc_string_fallbacks( $translation, $text, $domain ) {
	if ( $translation !== $text ) {
		return $translation; // already translated by the language pack
	}

	static $map = array(
		'%d in cart'                                     => '%d in winkelwagen',
		'Add to cart: &ldquo;%s&rdquo;'                  => 'In winkelwagen: &ldquo;%s&rdquo;',
		'View products in the &ldquo;%s&rdquo; category' => 'Bekijk producten in de categorie &ldquo;%s&rdquo;',
		'Original price was: %s.'                        => 'Oorspronkelijke prijs was: %s.',
		'Current price is: %s.'                          => 'Huidige prijs is: %s.',
	);

	return isset( $map[ $text ] ) ? $map[ $text ] : $translation;
}
add_filter( 'gettext_woocommerce', 'threeducation_wc_string_fallbacks', 10, 3 );

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
			'title'       => __( 'Webshop & oplossingen · 3D-printers voor scholen en thuis', '3ducation' ),
			'description' => __( 'Educatieve 3D-printpakketten voor basis- en secundaire scholen, plus voorgemonteerde 3D-printers voor thuis. Advies op maat, opleiding voor leerkrachten inbegrepen.', '3ducation' ),
			'keywords'    => __( '3D-printer kopen school, educatief pakket 3D-printen, 3D-printer basisschool, voorgemonteerde 3D-printer thuis', '3ducation' ),
		),
		'workshops'            => array(
			'title'       => __( 'Workshops & opleidingen · leer 3D-printen', '3ducation' ),
			'description' => __( 'Praktische 3D-printworkshops van ± 2 uur en originele verjaardagsfeestjes in 3D voor 10- tot 14-jarigen. Leer printen, ontwerpen in Tinkercad en slicen.', '3ducation' ),
			'keywords'    => __( '3D-print workshop, 3D-printer leren gebruiken, Tinkercad cursus, origineel verjaardagsfeestje 10 14 jaar, workshop 3D-tekenen', '3ducation' ),
		),
		'service'              => array(
			'title'       => __( 'Service & montage · 3D-printer herstelling en onderhoud', '3ducation' ),
			'description' => __( 'Herstelling, onderhoud en reserveonderdelen voor alle 3D-printers, ook als je toestel niet bij ons is gekocht. Vraag support aan via ons online formulier.', '3ducation' ),
			'keywords'    => __( '3D-printer herstelling, 3D-printer onderhoud, 3D-printer reparatie, reserveonderdelen 3D-printer, technische support 3D-printen', '3ducation' ),
		),
		'educatieve-pakketten' => array(
			'title'       => __( 'Educatieve pakketten · 3D-printen op school', '3ducation' ),
			'description' => __( 'Klaar-voor-de-klas 3D-printpakketten met installatie, lerarenopleiding en support. Op maat van basis- en secundaire scholen.', '3ducation' ),
			'keywords'    => __( 'educatief pakket 3D-printen, 3D-printer school, 3D-printen op school, lerarenopleiding 3D-printen', '3ducation' ),
		),
		'over-ons'             => array(
			'title'       => __( 'Over 3DUCATION · 3D-printen zonder zorgen, op school en thuis', '3ducation' ),
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
		// Spotlights load products by ID (not a WP_Query), so the visibility
		// window's pre_get_posts filter never runs — honour it here. Managers
		// still see out-of-window products, matching the rest of the site.
		if ( ! current_user_can( 'edit_products' ) && ! threeducation_product_in_window( $id ) ) {
			continue;
		}
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

/* ------------------------------------------------------------------ *
 * Product trust badges (Settings → Vertrouwensbadges)
 *
 * The three reassurance badges shown next to the Add-to-Cart button (rendered
 * by patterns/product-trust.php). Each slot's icon and brand accent are fixed
 * by design; only the title + description are editable here. An empty field
 * falls back to that slot's default text, so the strip always shows three badges.
 * Stored in option `threeducation_trust_badges`.
 * ------------------------------------------------------------------ */

/**
 * The three badge slots with their fixed icon + accent and default copy.
 * The SVG markup is theme-controlled (not user input), so it is echoed as-is.
 *
 * @return array[]
 */
function threeducation_trust_badge_defaults() {
	return array(
		array(
			'accent' => 'magenta',
			'icon'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
			'title'  => __( 'Lokale support', '3ducation' ),
			'text'   => __( 'Advies en herstelling vanuit Vlaanderen', '3ducation' ),
		),
		array(
			'accent' => 'cyan',
			'icon'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
			'title'  => __( 'Vooraf gemonteerd', '3ducation' ),
			'text'   => __( 'Optioneel klaar-om-te-printen geleverd', '3ducation' ),
		),
		array(
			'accent' => 'amber',
			'icon'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a5 5 0 1 0-5-5"/><circle cx="12" cy="10" r="1"/><path d="M8.5 20.5 12 15l3.5 5.5L12 19Z"/></svg>',
			'title'  => __( 'Officieel verkooppunt', '3ducation' ),
			'text'   => __( '[Merk] partner, echte garantie', '3ducation' ),
		),
	);
}

/**
 * Resolve the three badges: saved title/text where present, defaults otherwise.
 *
 * @return array[]
 */
function threeducation_trust_badges() {
	$defaults = threeducation_trust_badge_defaults();
	$saved    = get_option( 'threeducation_trust_badges', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$badges = array();
	foreach ( $defaults as $i => $badge ) {
		if ( isset( $saved[ $i ]['title'] ) && '' !== trim( (string) $saved[ $i ]['title'] ) ) {
			$badge['title'] = trim( (string) $saved[ $i ]['title'] );
		}
		if ( isset( $saved[ $i ]['text'] ) && '' !== trim( (string) $saved[ $i ]['text'] ) ) {
			$badge['text'] = trim( (string) $saved[ $i ]['text'] );
		}
		$badges[] = $badge;
	}
	return $badges;
}

/** Register the trust-badges option with the Settings API. */
function threeducation_trust_badges_register_settings() {
	register_setting(
		'threeducation_trust_badges',
		'threeducation_trust_badges',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'threeducation_trust_badges_sanitize',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'threeducation_trust_badges_register_settings' );

/** Sanitize the submitted badge titles/descriptions (three slots, plain text). */
function threeducation_trust_badges_sanitize( $input ) {
	$out = array();
	for ( $i = 0; $i < 3; $i++ ) {
		$out[ $i ] = array(
			'title' => isset( $input[ $i ]['title'] ) ? sanitize_text_field( wp_unslash( $input[ $i ]['title'] ) ) : '',
			'text'  => isset( $input[ $i ]['text'] ) ? sanitize_text_field( wp_unslash( $input[ $i ]['text'] ) ) : '',
		);
	}
	return $out;
}

/** Add the settings screen under the Settings menu. */
function threeducation_trust_badges_admin_menu() {
	add_options_page(
		__( 'Vertrouwensbadges', '3ducation' ),
		__( 'Vertrouwensbadges', '3ducation' ),
		'manage_options',
		'threeducation-trust-badges',
		'threeducation_trust_badges_render_admin_page'
	);
}
add_action( 'admin_menu', 'threeducation_trust_badges_admin_menu' );

/** Render the settings screen: title + description for each of the three badges. */
function threeducation_trust_badges_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$badges   = threeducation_trust_badges();
	$swatches = array(
		'magenta' => '#e6186c',
		'cyan'    => '#0fb1bf',
		'amber'   => '#f7941e',
	);
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Vertrouwensbadges', '3ducation' ); ?></h1>
		<p><?php echo esc_html__( 'Bewerk de drie vertrouwensbadges die naast de “In winkelwagen”-knop op productpagina’s verschijnen. Het pictogram en de kleur per badge liggen vast; laat een veld leeg om de standaardtekst te gebruiken.', '3ducation' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'threeducation_trust_badges' ); ?>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $badges as $i => $badge ) :
					$swatch = isset( $swatches[ $badge['accent'] ] ) ? $swatches[ $badge['accent'] ] : '#8c8f94';
					?>
					<tr>
						<th scope="row">
							<span style="display:inline-block;width:11px;height:11px;border-radius:50%;vertical-align:baseline;margin-right:6px;background:<?php echo esc_attr( $swatch ); ?>;"></span>
							<?php printf( esc_html__( 'Badge %d', '3ducation' ), (int) $i + 1 ); ?>
						</th>
						<td>
							<p style="margin:0 0 .5rem;">
								<label for="tdt-<?php echo esc_attr( $i ); ?>-title" style="display:block;font-weight:600;"><?php echo esc_html__( 'Titel', '3ducation' ); ?></label>
								<input type="text" id="tdt-<?php echo esc_attr( $i ); ?>-title" class="regular-text"
									name="threeducation_trust_badges[<?php echo esc_attr( $i ); ?>][title]"
									value="<?php echo esc_attr( $badge['title'] ); ?>">
							</p>
							<p style="margin:0;">
								<label for="tdt-<?php echo esc_attr( $i ); ?>-text" style="display:block;font-weight:600;"><?php echo esc_html__( 'Omschrijving', '3ducation' ); ?></label>
								<input type="text" id="tdt-<?php echo esc_attr( $i ); ?>-text" class="regular-text"
									name="threeducation_trust_badges[<?php echo esc_attr( $i ); ?>][text]"
									value="<?php echo esc_attr( $badge['text'] ); ?>">
							</p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Pre-order integration — "Pre-Orders for WooCommerce" (XootiX).
 *
 * On the single product page the plugin echoes its "Available on {date}" line
 * as bare text on woocommerce_before_add_to_cart_form. Wrap it in our branded
 * .preorder-availability-notice panel (styled in custom.css) so it reads as a
 * deliberate reassurance about dispatch rather than a stray sentence.
 *
 * Uses the plugin's own filter (note the plugin's spelling, "avaiable"). If the
 * plugin is inactive the filter never fires — harmless. Enable the line itself
 * under Pre-Orders → available-date position = single product.
 */
function threeducation_wrap_preorder_available_date( $text ) {
	if ( '' === trim( wp_strip_all_tags( (string) $text ) ) ) {
		return $text;
	}
	return '<p class="preorder-availability-notice">' . $text . '</p>';
}
add_filter( 'preorder_avaiable_date_text', 'threeducation_wrap_preorder_available_date' );

/**
 * Place the pre-order flag ON the product image (single product), matching the
 * Aanbieding (sale) badge. By default the plugin prints it on
 * woocommerce_before_single_product_summary — at the top of the product group,
 * away from the image. We suppress that instance and inject an identical
 * <span class="onsale on-preorder"> inside the product-image-gallery block,
 * where WooCommerce's sale flash sits, so both flags share the same corner.
 */
function threeducation_is_single_preorder_product( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return false;
	}
	$id = $product->get_id();
	if ( 'yes' !== get_post_meta( $id, '_is_pre_order', true ) ) {
		return false;
	}
	$date = get_post_meta( $id, '_pre_order_date', true );
	return $date && strtotime( $date ) > time();
}

/**
 * Suppress the plugin's single-product badge (the one printed at the top of the
 * product summary); it is re-rendered inside the gallery instead. The loop/shop
 * badge — same filter, different context — is left untouched.
 */
function threeducation_suppress_single_preorder_badge( $html ) {
	if ( is_product() && doing_action( 'woocommerce_before_single_product_summary' ) ) {
		return '';
	}
	return $html;
}
add_filter( 'woocommerce_preorder_badge', 'threeducation_suppress_single_preorder_badge', 20 );

/**
 * Inject the pre-order flag just inside the product-image-gallery wrapper so it
 * overlays the image exactly like the sale badge.
 */
function threeducation_inject_preorder_badge_in_gallery( $content, $block ) {
	if ( empty( $block['blockName'] ) || 'woocommerce/product-image-gallery' !== $block['blockName'] ) {
		return $content;
	}
	global $product;
	$p = ( $product instanceof WC_Product ) ? $product : wc_get_product( get_queried_object_id() );
	if ( ! threeducation_is_single_preorder_product( $p ) ) {
		return $content;
	}
	$badge = '<span class="onsale on-preorder">' . esc_html( get_option( 'wc_preorders_badge_text', 'Preorder' ) ) . '</span>';
	return preg_replace(
		'/(<div\b[^>]*\bwp-block-woocommerce-product-image-gallery\b[^>]*>)/',
		'$1' . $badge,
		$content,
		1
	);
}
add_filter( 'render_block', 'threeducation_inject_preorder_badge_in_gallery', 10, 2 );

/* ------------------------------------------------------------------ *
 * Product visibility window — show a product only between two dates
 *
 * Adds "Zichtbaar vanaf" / "Zichtbaar tot" date fields to each product
 * (Product data → Algemeen). Outside that window the product is FULLY hidden:
 * dropped from the shop grid, search and Store API (AJAX filter) results, its
 * single page 404s, and it can't be added to the cart. Shop managers
 * (edit_products capability) always see everything so they can preview/manage.
 *
 * Dates are inclusive and compared as site-local Y-m-d strings; an empty field
 * means "no bound on that side". Empty values are deleted (never stored as ''),
 * so the out-of-window query can rely on key existence + a non-empty value.
 * ------------------------------------------------------------------ */

/**
 * Render the two date inputs in the General product-data tab.
 */
function threeducation_visibility_fields() {
	echo '<div class="options_group">';
	woocommerce_wp_text_input( array(
		'id'          => '_visible_from',
		'label'       => __( 'Zichtbaar vanaf', '3ducation' ),
		'description' => __( 'Laat leeg voor geen startdatum. Vóór deze datum is het product volledig verborgen.', '3ducation' ),
		'desc_tip'    => true,
		'type'        => 'date',
	) );
	woocommerce_wp_text_input( array(
		'id'          => '_visible_until',
		'label'       => __( 'Zichtbaar tot', '3ducation' ),
		'description' => __( 'Laat leeg voor geen einddatum. Ná deze datum is het product volledig verborgen.', '3ducation' ),
		'desc_tip'    => true,
		'type'        => 'date',
	) );
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'threeducation_visibility_fields' );

/**
 * Persist the fields (CRUD hook). Keep only valid Y-m-d values; delete the meta
 * when empty so an empty string is never mistaken for an ancient date.
 */
function threeducation_visibility_save( $product ) {
	foreach ( array( '_visible_from', '_visible_until' ) as $key ) {
		$val = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		if ( $val && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $val ) ) {
			$product->update_meta_data( $key, $val );
		} else {
			$product->delete_meta_data( $key );
		}
	}
}
add_action( 'woocommerce_admin_process_product_object', 'threeducation_visibility_save' );

/**
 * True when today falls inside a product's visibility window (or it has none).
 */
function threeducation_product_in_window( $product_id ) {
	$from  = get_post_meta( $product_id, '_visible_from', true );
	$until = get_post_meta( $product_id, '_visible_until', true );
	if ( empty( $from ) && empty( $until ) ) {
		return true;
	}
	$today = current_time( 'Y-m-d' );
	if ( ! empty( $from ) && $today < $from ) {
		return false;
	}
	if ( ! empty( $until ) && $today > $until ) {
		return false;
	}
	return true;
}

/**
 * IDs of products currently OUTSIDE their window. Direct SQL (no WP_Query, so it
 * can't recurse through pre_get_posts); memoised per request and cached per day.
 */
function threeducation_hidden_product_ids() {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$today = current_time( 'Y-m-d' );
	$cache = get_transient( 'threeducation_hidden_products' );
	if ( is_array( $cache ) && isset( $cache['day'] ) && $cache['day'] === $today ) {
		$ids = $cache['ids'];
		return $ids;
	}
	global $wpdb;
	$rows = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT p.ID FROM {$wpdb->posts} p
		 INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
		 WHERE p.post_type = 'product'
		   AND ( ( m.meta_key = '_visible_from'  AND m.meta_value <> '' AND m.meta_value > %s )
		      OR ( m.meta_key = '_visible_until' AND m.meta_value <> '' AND m.meta_value < %s ) )",
		$today,
		$today
	) );
	$ids = array_map( 'intval', $rows );
	set_transient( 'threeducation_hidden_products', array( 'day' => $today, 'ids' => $ids ), DAY_IN_SECONDS );
	return $ids;
}

/**
 * Flush the cache whenever a product is saved so window edits take effect at once.
 */
function threeducation_clear_hidden_cache() {
	delete_transient( 'threeducation_hidden_products' );
}
add_action( 'woocommerce_update_product', 'threeducation_clear_hidden_cache' );
add_action( 'woocommerce_new_product', 'threeducation_clear_hidden_cache' );

/**
 * Exclude out-of-window products from front-end product queries — the shop grid,
 * category/tag archives, search, and the Store API (its internal WP_Query fires
 * pre_get_posts too). Managers are exempt so they can still browse/manage.
 */
function threeducation_filter_product_queries( $query ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	if ( current_user_can( 'edit_products' ) ) {
		return;
	}
	$pt         = $query->get( 'post_type' );
	$is_product = ( 'product' === $pt )
		|| ( is_array( $pt ) && in_array( 'product', $pt, true ) )
		|| ( empty( $pt ) && ( $query->is_post_type_archive( 'product' ) || $query->is_tax( get_object_taxonomies( 'product' ) ) ) );
	if ( ! $is_product ) {
		return;
	}
	$hidden = threeducation_hidden_product_ids();
	if ( empty( $hidden ) ) {
		return;
	}
	$existing = (array) $query->get( 'post__not_in' );
	$query->set( 'post__not_in', array_values( array_unique( array_merge( $existing, $hidden ) ) ) );
}
add_action( 'pre_get_posts', 'threeducation_filter_product_queries' );

/**
 * 404 the single product page when it is out of window (non-managers only).
 */
function threeducation_404_hidden_product() {
	if ( ! is_product() || current_user_can( 'edit_products' ) ) {
		return;
	}
	if ( ! threeducation_product_in_window( get_queried_object_id() ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'threeducation_404_hidden_product' );

/**
 * Belt-and-braces: make an out-of-window product non-purchasable and reject a
 * direct add-to-cart, in case one is reached outside the normal catalog path.
 */
function threeducation_purchasable_window( $purchasable, $product ) {
	if ( current_user_can( 'edit_products' ) ) {
		return $purchasable;
	}
	return $purchasable && threeducation_product_in_window( $product->get_id() );
}
add_filter( 'woocommerce_is_purchasable', 'threeducation_purchasable_window', 10, 2 );

function threeducation_add_to_cart_window( $passed, $product_id ) {
	if ( current_user_can( 'edit_products' ) ) {
		return $passed;
	}
	if ( ! threeducation_product_in_window( $product_id ) ) {
		wc_add_notice( __( 'Dit product is momenteel niet beschikbaar.', '3ducation' ), 'error' );
		return false;
	}
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'threeducation_add_to_cart_window', 10, 2 );

/**
 * Keep hidden products out of the "related products" strip as well.
 */
function threeducation_filter_related_products( $related ) {
	if ( current_user_can( 'edit_products' ) ) {
		return $related;
	}
	return array_values( array_diff( (array) $related, threeducation_hidden_product_ids() ) );
}
add_filter( 'woocommerce_related_products', 'threeducation_filter_related_products', 10, 1 );

/**
 * Admin Products list: a "Zichtbaarheid" column that summarises each product's
 * visibility window at a glance — an "Actief" / "Ingepland" / "Verlopen" badge
 * plus the date range — so scheduled products are scannable without opening each.
 */
function threeducation_visibility_admin_column( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'price' === $key ) {
			$new['visibility_window'] = __( 'Zichtbaarheid', '3ducation' );
		}
	}
	if ( ! isset( $new['visibility_window'] ) ) {
		$new['visibility_window'] = __( 'Zichtbaarheid', '3ducation' );
	}
	return $new;
}
add_filter( 'manage_edit-product_columns', 'threeducation_visibility_admin_column' );

function threeducation_visibility_admin_column_content( $column, $post_id ) {
	if ( 'visibility_window' !== $column ) {
		return;
	}
	$from  = get_post_meta( $post_id, '_visible_from', true );
	$until = get_post_meta( $post_id, '_visible_until', true );

	if ( empty( $from ) && empty( $until ) ) {
		echo '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Altijd zichtbaar', '3ducation' ) . '</span>';
		return;
	}

	$fmt   = get_option( 'date_format' );
	$today = current_time( 'Y-m-d' );

	// Status badge — muted admin tints (wp-admin chrome, not front-end theme).
	if ( ! empty( $from ) && $today < $from ) {
		$label = __( 'Ingepland', '3ducation' );
		$bg    = '#fcf0e0';
		$fg    = '#8a5300';
	} elseif ( ! empty( $until ) && $today > $until ) {
		$label = __( 'Verlopen', '3ducation' );
		$bg    = '#e9e9ec';
		$fg    = '#646970';
	} else {
		$label = __( 'Actief', '3ducation' );
		$bg    = '#e3f4ef';
		$fg    = '#0a7a66';
	}

	if ( ! empty( $from ) && ! empty( $until ) ) {
		$range = date_i18n( $fmt, strtotime( $from ) ) . ' &ndash; ' . date_i18n( $fmt, strtotime( $until ) );
	} elseif ( ! empty( $from ) ) {
		/* translators: %s: date */
		$range = sprintf( __( 'vanaf %s', '3ducation' ), date_i18n( $fmt, strtotime( $from ) ) );
	} else {
		/* translators: %s: date */
		$range = sprintf( __( 'tot %s', '3ducation' ), date_i18n( $fmt, strtotime( $until ) ) );
	}

	printf(
		'<span style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;line-height:1.6;background:%1$s;color:%2$s;">%3$s</span><br><span style="color:#50575e;font-size:12px;">%4$s</span>',
		esc_attr( $bg ),
		esc_attr( $fg ),
		esc_html( $label ),
		wp_kses( $range, array() )
	);
}
add_action( 'manage_product_posts_custom_column', 'threeducation_visibility_admin_column_content', 10, 2 );

/**
 * Give the column an explicit width. WP admin list tables are table-layout:fixed,
 * so without a width the "Zichtbaarheid" header collapses and wraps one letter
 * per line on column-heavy screens. Scoped to the Products list.
 */
function threeducation_visibility_admin_column_css() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-product' !== $screen->id ) {
		return;
	}
	echo '<style>
		.wp-list-table th.column-visibility_window { width: 11em; white-space: nowrap; }
		.wp-list-table td.column-visibility_window { width: 11em; }
	</style>' . "\n";
}
add_action( 'admin_head', 'threeducation_visibility_admin_column_css' );

/**
 * Resolve a product's visibility-window status for admin display.
 * Returns null when the product has no window (always visible), else an array
 * of { key: active|scheduled|expired, label, range }.
 */
function threeducation_visibility_status( $product_id ) {
	$from  = get_post_meta( $product_id, '_visible_from', true );
	$until = get_post_meta( $product_id, '_visible_until', true );
	if ( empty( $from ) && empty( $until ) ) {
		return null;
	}
	$fmt   = get_option( 'date_format' );
	$today = current_time( 'Y-m-d' );

	if ( ! empty( $from ) && $today < $from ) {
		$key = 'scheduled';
		$label = __( 'Ingepland', '3ducation' );
	} elseif ( ! empty( $until ) && $today > $until ) {
		$key = 'expired';
		$label = __( 'Verlopen', '3ducation' );
	} else {
		$key = 'active';
		$label = __( 'Actief', '3ducation' );
	}

	if ( ! empty( $from ) && ! empty( $until ) ) {
		$range = date_i18n( $fmt, strtotime( $from ) ) . ' &ndash; ' . date_i18n( $fmt, strtotime( $until ) );
	} elseif ( ! empty( $from ) ) {
		/* translators: %s: date */
		$range = sprintf( __( 'vanaf %s', '3ducation' ), date_i18n( $fmt, strtotime( $from ) ) );
	} else {
		/* translators: %s: date */
		$range = sprintf( __( 'tot %s', '3ducation' ), date_i18n( $fmt, strtotime( $until ) ) );
	}

	return array( 'key' => $key, 'label' => $label, 'range' => $range );
}

/**
 * Show the visibility-window status inside the Publiceren (submit) box on the
 * product edit screen — right next to WordPress' own Status/Zichtbaarheid lines
 * — so it's obvious when a product is currently hidden from visitors. (Core's
 * Status/Zichtbaarheid stay Gepubliceerd/Openbaar: this window is a runtime
 * overlay, not a post-status change.)
 */
function threeducation_visibility_submitbox() {
	global $post;
	if ( ! $post || 'product' !== $post->post_type ) {
		return;
	}
	$status = threeducation_visibility_status( $post->ID );

	echo '<div class="misc-pub-section" style="border-top:1px solid #dcdcde;">';
	echo '<span class="dashicons dashicons-visibility" style="color:#8c8f94;vertical-align:middle;"></span> ';

	if ( null === $status ) {
		echo esc_html__( 'Zichtbaarheidsvenster: altijd zichtbaar', '3ducation' );
	} else {
		$colors = array(
			'active'    => '#0a7a66',
			'scheduled' => '#8a5300',
			'expired'   => '#b32d2e',
		);
		printf(
			'%1$s: <strong style="color:%2$s">%3$s</strong><br><span style="margin-left:24px;color:#50575e;">%4$s</span>',
			esc_html__( 'Zichtbaarheidsvenster', '3ducation' ),
			esc_attr( $colors[ $status['key'] ] ),
			esc_html( $status['label'] ),
			wp_kses( $status['range'], array() )
		);
		if ( 'active' !== $status['key'] ) {
			echo '<br><span style="margin-left:24px;color:#b32d2e;font-style:italic;">'
				. esc_html__( 'Nu verborgen voor bezoekers.', '3ducation' ) . '</span>';
		}
	}
	echo '</div>';
}
add_action( 'post_submitbox_misc_actions', 'threeducation_visibility_submitbox' );
