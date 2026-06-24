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
	define( 'THREEDUCATION_VERSION', '0.9.0' );
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
 * Register a pattern category so the theme's patterns are grouped
 * together in the inserter.
 */
function threeducation_register_pattern_categories() {
	register_block_pattern_category(
		'3ducation',
		array( 'label' => __( '3ducation', '3ducation' ) )
	);
}
add_action( 'init', 'threeducation_register_pattern_categories' );
