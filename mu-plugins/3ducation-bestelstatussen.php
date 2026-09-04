<?php
/**
 * Plugin Name: 3DUCATION bestelstatussen
 * Description: Twee extra bestelstatussen: "Klaar voor afhaling" (alleen voor afhaalbestellingen, met klantmail zodra de bestelling klaarligt) en "Verzonden" (alleen voor het beheer, zonder klantmail).
 * Version: 1.1.0
 * Author: 3DUCATION
 *
 * Waarom: een webshopbestelling met "Afhalen in de winkel" staat na betaling op
 * "In behandeling" en blijft daar tot ze afgehaald is. De klant weet dus niet
 * wanneer de bestelling klaarligt, en de winkel ziet in de lijst niet welke
 * bestellingen nog verzameld moeten worden en welke op de klant wachten.
 *
 * Wat dit doet:
 *  1. Registreert de status `wc-afhaalklaar` ("Klaar voor afhaling"), tussen
 *     "In behandeling" en "In de wacht". Telt als betaald (`is_paid`) en zit in
 *     de rapporten. De kassa (WooCommerce POS) neemt de status automatisch
 *     over uit `wc_get_order_statuses()`.
 *  2. Beperkt de status tot afhaalbestellingen: in de bestellijst staat de
 *     snelknop "Klaar voor afhaling" alleen bij afhaalbestellingen die in
 *     behandeling of in de wacht staan; op het bewerkscherm verdwijnt de optie
 *     uit de statuskeuze voor niet-afhaalbestellingen; en als de status toch
 *     op een niet-afhaalbestelling gezet wordt (bulkactie, kassa, REST), zet
 *     een bewaking hem terug met een notitie bij de bestelling.
 *  3. Stuurt de klant de mail "Klaar voor afhaling" (WooCommerce → Instellingen
 *     → E-mails, daar zijn onderwerp, kop en tekst aanpasbaar). De mail bevat
 *     het winkeladres en de openingsuren uit Instellingen → Footer van het
 *     thema, en het besteloverzicht. Opnieuw sturen kan via "Bestelling
 *     acties" op het bewerkscherm.
 *  4. Registreert de status `wc-verzonden` ("Verzonden"), na "Klaar voor
 *     afhaling". Puur administratief: geen klantmail, geen bewaking. In de
 *     bestellijst staat de snelknop "Verzonden" bij verzendbestellingen (niet
 *     afhalen) die in behandeling staan; bulkactie "Wijzig status naar
 *     verzonden". Telt als betaald en zit in de rapporten.
 *
 * Een afhaalbestelling herkennen we aan de verzendregel: methode
 * `pickup_location` (blok-afrekenen, op live "Zelf Ophalen (Nieuwkerken)") of
 * `local_pickup` (klassiek), of een verzendmethode waarvan de naam "afhal",
 * "ophal" of "pickup" bevat. Aanpasbaar via de filter
 * `threeducation_afhaal_method_ids`.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Geen
 * activatiestap. Zonder WooCommerce doet het bestand niets.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;

/** Statusslugs zonder `wc-`-voorvoegsel. */
const THREEDUCATION_AFHAAL_STATUS    = 'afhaalklaar';
const THREEDUCATION_VERZONDEN_STATUS = 'verzonden';

/* =====================================================================
 * Afhaalbestelling herkennen
 * ===================================================================== */

/**
 * Is dit een bestelling die in de winkel afgehaald wordt?
 *
 * @param WC_Order|int|null $order Bestelling of id.
 */
function threeducation_afhaal_is_pickup_order( $order ): bool {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return false;
	}
	if ( ! $order instanceof WC_Order ) {
		$order = wc_get_order( $order );
	}
	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	$method_ids = (array) apply_filters( 'threeducation_afhaal_method_ids', array( 'pickup_location', 'local_pickup' ) );

	foreach ( $order->get_shipping_methods() as $item ) {
		if ( in_array( $item->get_method_id(), $method_ids, true ) ) {
			return true;
		}
		$title = strtolower( $item->get_method_title() . ' ' . $item->get_name() );
		if ( false !== strpos( $title, 'afhal' ) || false !== strpos( $title, 'ophal' ) || false !== strpos( $title, 'pickup' ) ) {
			return true;
		}
	}

	return false;
}

/* =====================================================================
 * 1. Status registreren
 * ===================================================================== */

function threeducation_afhaal_register_status() {
	register_post_status(
		'wc-' . THREEDUCATION_AFHAAL_STATUS,
		array(
			'label'                     => _x( 'Klaar voor afhaling', 'Bestelstatus', '3ducation' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: aantal bestellingen */
			'label_count'               => _n_noop( 'Klaar voor afhaling <span class="count">(%s)</span>', 'Klaar voor afhaling <span class="count">(%s)</span>', '3ducation' ),
		)
	);
	register_post_status(
		'wc-' . THREEDUCATION_VERZONDEN_STATUS,
		array(
			'label'                     => _x( 'Verzonden', 'Bestelstatus', '3ducation' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: aantal bestellingen */
			'label_count'               => _n_noop( 'Verzonden <span class="count">(%s)</span>', 'Verzonden <span class="count">(%s)</span>', '3ducation' ),
		)
	);
}
add_action( 'init', 'threeducation_afhaal_register_status' );

/** Voeg de statussen toe aan de WooCommerce-lijst, direct na "In behandeling": eerst afhaling, dan verzonden. */
function threeducation_afhaal_order_statuses( $statuses ) {
	$new = array(
		'wc-' . THREEDUCATION_AFHAAL_STATUS    => _x( 'Klaar voor afhaling', 'Bestelstatus', '3ducation' ),
		'wc-' . THREEDUCATION_VERZONDEN_STATUS => _x( 'Verzonden', 'Bestelstatus', '3ducation' ),
	);
	$new = array_diff_key( $new, $statuses );
	if ( ! $new ) {
		return $statuses;
	}
	$out = array();
	foreach ( $statuses as $slug => $name ) {
		$out[ $slug ] = $name;
		if ( 'wc-processing' === $slug ) {
			$out = array_merge( $out, $new );
		}
	}

	return array_merge( $out, $new );
}
add_filter( 'wc_order_statuses', 'threeducation_afhaal_order_statuses' );

/** Een bestelling die klaarligt of verzonden is, is betaald en telt mee in de rapporten. */
function threeducation_afhaal_add_status_to_list( $statuses ) {
	if ( ! is_array( $statuses ) ) {
		return $statuses;
	}
	foreach ( array( THREEDUCATION_AFHAAL_STATUS, THREEDUCATION_VERZONDEN_STATUS ) as $status ) {
		if ( ! in_array( $status, $statuses, true ) ) {
			$statuses[] = $status;
		}
	}

	return $statuses;
}
add_filter( 'woocommerce_order_is_paid_statuses', 'threeducation_afhaal_add_status_to_list' );
add_filter( 'woocommerce_reports_order_statuses', 'threeducation_afhaal_add_status_to_list' );

/* =====================================================================
 * 2. Alleen voor afhaalbestellingen
 * ===================================================================== */

/**
 * Bewaking: wordt de status op een niet-afhaalbestelling gezet (bulkactie,
 * kassa, REST), zet hem dan terug op de vorige status en leg dat vast in een
 * bestelnotitie. Hangt op `_changed` (prioriteit 1) omdat alleen die haak de
 * vorige status meegeeft; de mailtrigger controleert zelf ook nog op afhaling.
 */
function threeducation_afhaal_guard_on_changed( $order_id, $from, $to, $order ) {
	if ( THREEDUCATION_AFHAAL_STATUS !== $to || ! $order instanceof WC_Order || threeducation_afhaal_is_pickup_order( $order ) ) {
		return;
	}
	$previous = ( '' === $from || THREEDUCATION_AFHAAL_STATUS === $from ) ? 'processing' : $from;
	$order->set_status( $previous );
	$order->save();
	$order->add_order_note( __( 'Status "Klaar voor afhaling" is alleen voor bestellingen met afhaling in de winkel; de vorige status is hersteld.', '3ducation' ) );
}
add_action( 'woocommerce_order_status_changed', 'threeducation_afhaal_guard_on_changed', 1, 4 );

/** Bulkacties in de bestellijst (klassiek en HPOS). WooCommerce handelt `mark_<status>` zelf af. */
function threeducation_afhaal_bulk_action( $actions ) {
	$new = array(
		'mark_' . THREEDUCATION_AFHAAL_STATUS    => __( 'Wijzig status naar klaar voor afhaling', '3ducation' ),
		'mark_' . THREEDUCATION_VERZONDEN_STATUS => __( 'Wijzig status naar verzonden', '3ducation' ),
	);
	$out = array();
	foreach ( $actions as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'mark_processing' === $key ) {
			$out = array_merge( $out, $new );
		}
	}

	return array_merge( $out, $new );
}
add_filter( 'bulk_actions-edit-shop_order', 'threeducation_afhaal_bulk_action', 20 );
add_filter( 'bulk_actions-woocommerce_page_wc-orders', 'threeducation_afhaal_bulk_action', 20 );

/**
 * Snelknoppen in de bestellijst: "Klaar voor afhaling" bij afhaalbestellingen
 * in behandeling of in de wacht, "Verzonden" bij verzendbestellingen in
 * behandeling.
 */
function threeducation_afhaal_list_action( $actions, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return $actions;
	}
	$pickup = threeducation_afhaal_is_pickup_order( $order );
	if ( $pickup && $order->has_status( array( 'processing', 'on-hold' ) ) ) {
		$status = THREEDUCATION_AFHAAL_STATUS;
		$name   = __( 'Klaar voor afhaling', '3ducation' );
	} elseif ( ! $pickup && $order->has_status( 'processing' ) ) {
		$status = THREEDUCATION_VERZONDEN_STATUS;
		$name   = __( 'Verzonden', '3ducation' );
	} else {
		return $actions;
	}
	$actions[ $status ] = array(
		'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=' . $status . '&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
		'name'   => $name,
		'action' => $status,
	);

	return $actions;
}
add_filter( 'woocommerce_admin_order_actions', 'threeducation_afhaal_list_action', 10, 2 );

/** "Bestelling acties" op het bewerkscherm: de afhaalmail opnieuw sturen. WooCommerce handelt `send_email_<id>` zelf af. */
function threeducation_afhaal_order_actions( $actions, $order = null ) {
	if ( $order instanceof WC_Order && threeducation_afhaal_is_pickup_order( $order ) ) {
		$actions['send_email_customer_afhaalklaar'] = __( 'E-mail "Klaar voor afhaling" naar klant sturen', '3ducation' );
	}

	return $actions;
}
add_filter( 'woocommerce_order_actions', 'threeducation_afhaal_order_actions', 10, 2 );

/** De bestelling die op het huidige beheerscherm bewerkt wordt, of null. */
function threeducation_afhaal_admin_order() {
	if ( ! function_exists( 'get_current_screen' ) || ! function_exists( 'wc_get_order' ) ) {
		return null;
	}
	$screen = get_current_screen();
	if ( ! $screen ) {
		return null;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- alleen lezen van het scherm-id.
	if ( 'woocommerce_page_wc-orders' === $screen->id && ! empty( $_GET['id'] ) ) {
		$order = wc_get_order( absint( $_GET['id'] ) );
	} elseif ( 'shop_order' === $screen->id && ! empty( $_GET['post'] ) ) {
		$order = wc_get_order( absint( $_GET['post'] ) );
	} else {
		return null;
	}
	// phpcs:enable

	return $order instanceof WC_Order ? $order : null;
}

/** Bewerkscherm: verwijder de status uit de keuzelijst voor niet-afhaalbestellingen. */
function threeducation_afhaal_admin_footer_script() {
	$order = threeducation_afhaal_admin_order();
	if ( ! $order || threeducation_afhaal_is_pickup_order( $order ) || $order->has_status( THREEDUCATION_AFHAAL_STATUS ) ) {
		return;
	}
	?>
	<script>
	document.querySelectorAll('#order_status option[value="wc-<?php echo esc_js( THREEDUCATION_AFHAAL_STATUS ); ?>"]').forEach(function (o) { o.remove(); });
	</script>
	<?php
}
add_action( 'admin_footer', 'threeducation_afhaal_admin_footer_script' );

/** Beheerstijl: statuslabels in de lijst en de iconen van de snelknoppen. */
function threeducation_afhaal_admin_styles() {
	if ( ! wp_style_is( 'woocommerce_admin_styles', 'enqueued' ) ) {
		return;
	}
	$a   = THREEDUCATION_AFHAAL_STATUS;
	$v   = THREEDUCATION_VERZONDEN_STATUS;
	$css = "
	.order-status.status-{$a} { background: #d7ecf8; color: #0f4c6e; }
	.order-status.status-{$v} { background: #e6dff5; color: #46307a; }
	.widefat .column-wc_actions a.{$a}::after { font-family: Dashicons; content: '\\f513'; }
	.widefat .column-wc_actions a.{$v}::after { font-family: Dashicons; content: '\\f139'; }
	";
	wp_add_inline_style( 'woocommerce_admin_styles', $css );
}
add_action( 'admin_enqueue_scripts', 'threeducation_afhaal_admin_styles', 20 );

/* =====================================================================
 * 3. Klantmail "Klaar voor afhaling"
 * ===================================================================== */

/** Laat WooCommerce de `_notification`-variant van de statushaak aanmaken (zo hoeft de mailer niet vooraf geladen te zijn). */
function threeducation_afhaal_email_actions( $actions ) {
	$actions[] = 'woocommerce_order_status_' . THREEDUCATION_AFHAAL_STATUS;

	return $actions;
}
add_filter( 'woocommerce_email_actions', 'threeducation_afhaal_email_actions' );

/**
 * Winkelgegevens voor in de mail: adres en openingsuren uit Instellingen →
 * Footer van het thema, met dezelfde standaardwaarden als het thema.
 *
 * @return array{adres:string,openingsuren:string,telefoon:string}
 */
function threeducation_afhaal_shop_info(): array {
	$defaults = array(
		'adres'        => "Vrasenestraat 40\n9100 Nieuwkerken-Waas",
		'openingsuren' => "Maandag & dinsdag: gesloten\nWoensdag: 14:00–18:00\nDonderdag: 10:00–13:00 & 14:00–18:00\nVrijdag: 10:00–13:00 & 14:00–18:00\nZaterdag: 10:00–17:00\nZondag: gesloten",
		'telefoon'     => '+32 468 11 82 42',
	);
	$values   = function_exists( 'threeducation_footer_values' ) ? threeducation_footer_values() : array();

	foreach ( $defaults as $key => $default ) {
		$value = isset( $values[ $key ] ) ? trim( (string) $values[ $key ] ) : '';
		$defaults[ $key ] = '' === $value ? $default : $value;
	}

	return $defaults;
}

function threeducation_afhaal_register_email( $emails ) {
	if ( ! class_exists( 'WC_Email' ) ) {
		return $emails;
	}

	if ( ! class_exists( 'Threeducation_Email_Customer_Afhaalklaar' ) ) {
		/**
		 * Klantmail: de bestelling ligt klaar in de winkel.
		 */
		class Threeducation_Email_Customer_Afhaalklaar extends WC_Email {

			public function __construct() {
				$this->id             = 'customer_afhaalklaar';
				$this->customer_email = true;
				$this->title          = __( 'Klaar voor afhaling', '3ducation' );
				$this->description    = __( 'Naar de klant zodra een afhaalbestelling de status "Klaar voor afhaling" krijgt. Bevat het winkeladres, de openingsuren en het besteloverzicht.', '3ducation' );
				$this->placeholders   = array(
					'{order_date}'   => '',
					'{order_number}' => '',
				);

				add_action( 'woocommerce_order_status_' . THREEDUCATION_AFHAAL_STATUS . '_notification', array( $this, 'trigger' ), 10, 2 );

				parent::__construct();
			}

			public function get_default_subject() {
				return __( 'Je bestelling #{order_number} ligt klaar bij 3DUCATION', '3ducation' );
			}

			public function get_default_heading() {
				return __( 'Je bestelling ligt klaar', '3ducation' );
			}

			/** Standaardtekst boven het besteloverzicht. */
			public function get_default_intro() {
				return __( "Goed nieuws: je bestelling ligt klaar in onze winkel. Je kan ze afhalen tijdens de openingsuren. Neem dit bericht of je bestelnummer mee.", '3ducation' );
			}

			public function get_default_additional_content() {
				return __( 'Tot binnenkort in de winkel! Vragen? Antwoord gerust op deze mail.', '3ducation' );
			}

			public function get_intro() {
				$intro = $this->get_option( 'intro', $this->get_default_intro() );

				return (string) apply_filters( 'threeducation_afhaal_email_intro', $this->format_string( $intro ), $this->object, $this );
			}

			/**
			 * Verstuur de mail.
			 *
			 * @param int           $order_id Bestelling-id.
			 * @param WC_Order|bool $order    Bestelling.
			 */
			public function trigger( $order_id, $order = false ) {
				$this->setup_locale();

				if ( $order_id && ! $order instanceof WC_Order ) {
					$order = wc_get_order( $order_id );
				}

				if ( $order instanceof WC_Order && threeducation_afhaal_is_pickup_order( $order ) ) {
					$this->object                         = $order;
					$this->recipient                      = $order->get_billing_email();
					$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
					$this->placeholders['{order_number}'] = $order->get_order_number();

					if ( $this->is_enabled() && $this->get_recipient() ) {
						$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
					}
				}

				$this->restore_locale();
			}

			/** Het blok met adres en openingsuren, als HTML. */
			private function shop_info_html(): string {
				$info  = threeducation_afhaal_shop_info();
				$lines = function ( $text ) {
					$parts = array_filter( array_map( 'trim', explode( "\n", (string) $text ) ), 'strlen' );

					return implode( '<br>', array_map( 'esc_html', $parts ) );
				};

				return '<table cellspacing="0" cellpadding="0" style="width:100%;margin-bottom:24px;border:1px solid #e5e5e5;border-radius:6px;" border="0"><tr><td style="padding:16px 20px;vertical-align:top;width:50%;">'
					. '<strong>' . esc_html__( 'Afhaaladres', '3ducation' ) . '</strong><br>' . $lines( $info['adres'] )
					. '<br><br><strong>' . esc_html__( 'Telefoon', '3ducation' ) . '</strong><br>' . esc_html( $info['telefoon'] )
					. '</td><td style="padding:16px 20px;vertical-align:top;width:50%;">'
					. '<strong>' . esc_html__( 'Openingsuren', '3ducation' ) . '</strong><br>' . $lines( $info['openingsuren'] )
					. '</td></tr></table>';
			}

			public function get_content_html() {
				ob_start();
				do_action( 'woocommerce_email_header', $this->get_heading(), $this );
				echo wp_kses_post( wpautop( wptexturize( $this->get_intro() ) ) );
				echo $this->shop_info_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hierboven ge-escaped.
				do_action( 'woocommerce_email_order_details', $this->object, false, false, $this );
				do_action( 'woocommerce_email_order_meta', $this->object, false, false, $this );
				do_action( 'woocommerce_email_customer_details', $this->object, false, false, $this );
				$additional = $this->get_additional_content();
				if ( $additional ) {
					echo wp_kses_post( wpautop( wptexturize( $additional ) ) );
				}
				do_action( 'woocommerce_email_footer', $this );

				return ob_get_clean();
			}

			public function get_content_plain() {
				$info = threeducation_afhaal_shop_info();
				ob_start();
				echo wp_strip_all_tags( $this->get_heading() ) . "\n\n";
				echo wp_strip_all_tags( wptexturize( $this->get_intro() ) ) . "\n\n";
				echo wp_strip_all_tags( __( 'Afhaaladres', '3ducation' ) ) . ":\n" . wp_strip_all_tags( $info['adres'] ) . "\n" . wp_strip_all_tags( $info['telefoon'] ) . "\n\n";
				echo wp_strip_all_tags( __( 'Openingsuren', '3ducation' ) ) . ":\n" . wp_strip_all_tags( $info['openingsuren'] ) . "\n\n";
				do_action( 'woocommerce_email_order_details', $this->object, false, true, $this );
				echo "\n----------------------------------------\n\n";
				do_action( 'woocommerce_email_order_meta', $this->object, false, true, $this );
				do_action( 'woocommerce_email_customer_details', $this->object, false, true, $this );
				$additional = $this->get_additional_content();
				if ( $additional ) {
					echo "\n" . wp_strip_all_tags( wptexturize( $additional ) ) . "\n\n";
				}
				echo wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

				return ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- platte tekst, geen HTML.
			}

			/** Instellingenvelden: standaard WooCommerce plus een bewerkbare inleiding. */
			public function init_form_fields() {
				parent::init_form_fields();
				$fields = array();
				foreach ( $this->form_fields as $key => $field ) {
					$fields[ $key ] = $field;
					if ( 'heading' === $key ) {
						$fields['intro'] = array(
							'title'       => __( 'Bericht', '3ducation' ),
							'description' => __( 'Tekst boven het adres en het besteloverzicht. Beschikbare plaatshouders: {order_number}, {order_date}, {site_title}.', '3ducation' ),
							'css'         => 'width:400px; height: 75px;',
							'placeholder' => $this->get_default_intro(),
							'type'        => 'textarea',
							'default'     => '',
							'desc_tip'    => true,
						);
					}
				}
				$this->form_fields = $fields;
			}
		}
	}

	$emails['Threeducation_Email_Customer_Afhaalklaar'] = new Threeducation_Email_Customer_Afhaalklaar();

	return $emails;
}
add_filter( 'woocommerce_email_classes', 'threeducation_afhaal_register_email' );
