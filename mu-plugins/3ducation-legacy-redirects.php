<?php
/**
 * Plugin Name: 3DUCATION legacy redirects
 * Description: 301-redirects voor de oude Duda-webshop-URLs (/webshop/...-p<ID> en -c<ID>).
 * Version: 1.1.0
 * Author: 3DUCATION
 *
 * Waarom een mu-plugin en niet het thema?
 * Redirects zijn site-infrastructuur, geen vormgeving: ze moeten blijven werken
 * als het thema wordt gewisseld, vervangen of tijdelijk uitvalt. Daarom staat
 * deze code in wp-content/mu-plugins/ en niet in functions.php. Mu-plugins laden
 * altijd en kunnen niet per ongeluk gedeactiveerd worden.
 *
 * Installeren: dit bestand naar wp-content/mu-plugins/ uploaden. Bestaat die map
 * nog niet, maak hem dan aan. Er is geen activatiestap.
 *
 * Sinds 1.1.0 zit er ook een vaste tabel oud-product-ID → nieuwe slug in
 * (threeducation_legacy_product_id_map). De oude Duda/Ecwid-ID's stonden in
 * geen enkele export, maar de Google Merchant-export van 2026-09-03 had ze wél,
 * naast de oude productnaam. Zo landen ook de filamenten die bij de
 * categorie-herindeling hernoemd zijn op hun productpagina in plaats van op
 * de zoekresultaten. Nieuwe producten hoeven hier niet in: die hebben geen
 * oude URL. Wordt een product uit de tabel verwijderd, dan valt de URL
 * gewoon terug op de naam-lookup en daarna op de zoekpagina.
 *
 * LET OP: verwijder deze code eerst uit het thema (functions.php) of upload eerst
 * de themaversie waarin ze weg is. Staan beide er tegelijk, dan geeft PHP een
 * fatal error "cannot redeclare threeducation_legacy_key()" — mu-plugins laden
 * vóór het thema.
 *
 * @package 3ducation
 */

defined( 'ABSPATH' ) || exit;


/** Normaliseert een slug tot enkel letters en cijfers. */
function threeducation_legacy_key( $slug ) {
	return preg_replace( '/[^a-z0-9]/', '', strtolower( remove_accents( (string) $slug ) ) );
}

/**
 * Bouwt (en cachet) de map van genormaliseerde slug naar product-ID.
 *
 * @return array<string,int>
 */
function threeducation_legacy_product_map() {
	$map = get_transient( 'threeducation_legacy_product_map' );
	if ( is_array( $map ) ) {
		return $map;
	}

	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT ID, post_name FROM {$wpdb->posts}
		 WHERE post_type = 'product' AND post_status = 'publish'"
	);

	$map = array();
	foreach ( $rows as $row ) {
		$key = threeducation_legacy_key( $row->post_name );
		// Eerste treffer wint: bij een dubbele sleutel is de oudste post het origineel.
		if ( '' !== $key && ! isset( $map[ $key ] ) ) {
			$map[ $key ] = (int) $row->ID;
		}
	}

	set_transient( 'threeducation_legacy_product_map', $map, 12 * HOUR_IN_SECONDS );
	return $map;
}

/** Gooit de cache weg zodra er een product bijkomt of verandert. */
function threeducation_legacy_flush_map( $post_id ) {
	if ( 'product' === get_post_type( $post_id ) ) {
		delete_transient( 'threeducation_legacy_product_map' );
	}
}
add_action( 'save_post', 'threeducation_legacy_flush_map' );
add_action( 'trashed_post', 'threeducation_legacy_flush_map' );

/**
 * Vaste tabel: oud Duda/Ecwid-product-ID → slug van het product op de nieuwe
 * site. Gegenereerd uit de Google Merchant-export van 2026-09-03 (oud ID +
 * oude naam) gematcht op de live catalogus; handmatig nagekeken. De slug wordt
 * via threeducation_legacy_key() opgezocht in de gewone slug-map, dus een
 * verdwenen product geeft hier geen fout maar valt terug op de zoekpagina.
 *
 * @return array<int,string>
 */
function threeducation_legacy_product_id_map() {
	return array(
		416881004 => 'panchroma-matte-arctic-teal-pla-1kg',
		416891606 => 'panchroma-matte-cotton-white-pla-1kg',
		416892618 => 'panchroma-matte-lavender-purple-pla-1kg',
		416896651 => 'panchroma-matte-pastel-peach-pla-1kg',
		416897554 => 'panchroma-matte-charcoal-black-pla-1kg',
		436550939 => '3dlac-spray',
		450832917 => 'panchroma-matte-pastel-ice-pla-1kg',
		450855692 => 'panchroma-matte-earth-brown-pla-1kg',
		450871339 => 'panchroma-marble-slate-grey-pla-1kg',
		450904027 => 'panchroma-matte-sakura-pink-pla-1kg',
		457111009 => 'panchroma-matte-army-light-green-pla-1kg',
		457129011 => 'panchroma-matte-wood-brown-pla-1kg',
		457135604 => 'panchroma-matte-army-brown-pla-1kg',
		458798029 => 'creality-ender-blower-fan-4010-24v',
		458798049 => '1m-capricorn-tube-xs-ultra-low-friction-ptfe-bowden-1-75mm',
		458798051 => '1m-originele-witte-ptfe-bowden-tube-van-hoge-kwaliteit-1-75mm',
		458813760 => 'creality-extruder-push-fit',
		458815509 => 'creality-eindeloop-switch',
		458821752 => 'creality-ender-fan-4010-24v',
		458828936 => 'originele-creality-cr-6-se-cr-10-smart-extruder',
		475877879 => 'panchroma-dual-yin-yang-pla-1kg',
		496472085 => 'pei-flexplate-235x235mm-ender-3-series-k1-cr10-se',
		507645479 => 'pla-hd-1-75-mm-ebony-brown-1kg',
		513207895 => 'pla-hd-1-75-mm-pastel-cotton-candy-pink-1kg',
		513229333 => 'pla-hd-1-75-mm-mahogany-brown-1kg',
		513296503 => 'pla-hd-1-75-mm-jet-black-1kg',
		520833890 => 'pla-hd-1-75-mm-ecotisa-green-1kg',
		520871314 => 'cadeaubon',
		522974697 => 'pla-hd-1-75-mm-glacier-white-1kg',
		522975448 => 'pla-hd-1-75-mm-canary-yellow-1kg',
		522977172 => 'pla-hd-1-75-mm-pacific-blue-1kg',
		522979960 => 'pla-hd-1-75-mm-transparent-1kg',
		531810863 => 'carborundum-glasplaat-310x320-mm',
		534597898 => 'panchroma-matte-army-beige-pla-1kg',
		534619492 => 'panchroma-matte-army-blue-pla-1kg',
		540342899 => 'pla-hd-1-75-mm-winkle-purple-1kg',
		540342901 => 'pla-hd-1-75-mm-ash-grey-1kg',
		541947564 => 'pla-hd-1-75-mm-pakket-8-basiskleuren-rood-geel-blauw-groen-wit-zwart-paars-oranje-8x1kg',
		541954289 => 'pla-hd-1-75-mm-pakket-4-basiskleuren-rood-geel-blauw-groen-4x1kg',
		541959502 => 'pla-hd-1-75-mm-pakket-6-basiskleuren-rood-geel-blauw-groen-wit-zwart-6x1kg',
		543203215 => 'panchroma-matte-muted-green-pla-1kg',
		543206961 => 'panchroma-matte-muted-purple-pla-1kg',
		543228964 => 'panchroma-matte-muted-blue-pla-1kg',
		543261257 => 'panchroma-matte-muted-white-pla-1kg',
		553582143 => 'starterpack-2x-pla-hd-kleur-naar-keuze-3d-lac',
		576229398 => 'creality-ptfe-tube-knipper',
		576257367 => 'originele-high-speed-nozzle-04mm-voor-de-ender-3v3-se-ender-7',
		576390254 => 'creality-spacepi-x4-filament-dryer',
		576557297 => 'creality-3d-cr-5-pro-extruder-fan-30-mm',
		576569757 => 'creality-3d-heating-block-kit-high-temperature-300%e2%84%83-ender-3s1-pro',
		581237550 => 'pla-hd-1-75-mm-silk-steel-blue-1kg',
		581298771 => 'pla-hd-1-75-mm-silver-1kg',
		583301698 => 'pla-hd-1-75-mm-silk-kings-gold-1kg',
		588047470 => 'originele-creality-k1-k1max-ender3-v3-ke-cr10-se-nozzle-messing',
		592349291 => 'petg-1-75-mm-ash-grey-1kg',
		615245354 => 'pla-tough-1-75-mm-zwart-jet-black-1kg',
		623025330 => 'panchroma-dual-matte-chameleon-teal-yellow-pla-1kg',
		625092210 => 'creality-3d-k1-k1-max-hotend-kit',
		631146592 => 'panchroma-dual-matte-sunrise-red-yellow-pla-1kg',
		637547057 => 'panchroma-matte-electric-indigo-pla-1kg',
		637547802 => 'panchroma-matte-lime-green-pla-1kg',
		637547808 => 'panchroma-matte-pastel-periwinkle-pla-1kg',
		637548571 => 'panchroma-matte-ash-grey-pla-1kg',
		637593595 => 'panchroma-gradient-matte-spring-pla-1kg',
		637947572 => 'panchroma-marble-sandstone-pla-1kg',
		637951607 => 'panchroma-marble-limestone-pla-1kg',
		637954338 => 'panchroma-marble-brick-pla-1kg',
		638552729 => 'creality-silicone-heater-block-cover-ender-3s1-sermoonv1-serie',
		639393096 => 'creality-k1-k1-max-ai-camera',
		647926901 => 'creality-falcon-desktop-smoke-purifier',
		647926922 => 'falcon-honeycomb-panel-460x346x15-mm-for-a1-a1-pro',
		658350590 => 'ender-3-v3-plus',
		685959067 => 'creality-k1-k1-max-quick-swap-nozzle-upgrade-kit-k1c',
		688276603 => 'creality-k2-plus-combo',
		691329345 => 'polymaker-petg-black-1kg',
		691359003 => 'polyflex-tpu95-hf-black-1kg',
		692798392 => 'bambu-hotend-a1-series-04mm-hardened-steel',
		692798393 => 'bambu-hotend-x1-p1-series-04mm-hardened-steel',
		692810107 => 'bambu-hotend-a1-series-04mm-stainless-steel',
		692830367 => 'creality-ender-3v3-se-ke-quick-swap-nozzle-upgrade-kit',
		694665165 => 'creality-3d-thermistor-ender3-s1-pro',
		694665166 => 'creality-3d-thermistor-ender3-s1-pro',
		694665167 => 'creality-3d-cr-6-se-max-auto-level-sensor',
		694667169 => 'creality-3d-ender-3-s1-heating-tube',
		694667170 => 'creality-3d-cr-6-se-thermistor',
		694682640 => 'creality-3d-heating-tube_24v_50w-ender3-s1-pro',
		694682644 => 'creality-3d-cr-6-se-heater',
		696533339 => 'creality-3d-cr-10-se-extruder-kit',
		708014032 => 'polymaker-polylite-abs-black-1kg',
		708361461 => 'creality-rotary-kit-pro',
		709097716 => 'pla-hd-1-75-mm-royal-blue-1kg',
		709795588 => 'polymaker-polydryer-box',
		710288615 => 'panchroma-dual-matte-mixed-berries-red-dark-blue-pla-1kg',
		711315553 => 'polymaker-polylite-pla-cf-black-1kg',
		711315568 => 'fiberon-petg-rcf08-black-05kg',
		714035694 => 'panchroma-matte-sky-blue-pla-1kg',
		716029111 => 'bambu-hotend-x1-p1-series-02mm-stainless-steel',
		716029112 => 'bambu-hotend-a1-series-02mm-stainless-steel',
		717743461 => '230v-stroomkabel',
		719003994 => 'panchroma-pla-celestial-purple-pla-1kg',
		719022488 => 'panchroma-pla-celestial-blue-pla-1kg',
		719152515 => 'panchroma-pla-celestial-green-pla-1kg',
		719152523 => 'panchroma-pla-neon-green-1kg',
		719153521 => 'panchroma-pla-neon-magenta-1kg',
		724749295 => 'bambu-hotend-a1-series-08mm-hardened-steel',
		724761256 => 'bambu-hotend-x1-p1-series-06mm-hardened-steel',
		729254281 => 'creality-sparkx-i7',
		729481265 => 'fiberon-pa6-cf20-05kg',
		732847049 => 'panchroma-galaxy-dark-blue-pla-1kg',
		732847051 => 'panchroma-galaxy-dark-grey-pla-1kg',
		732847054 => 'panchroma-luminous-rainbow-pla-1kg',
		732847061 => 'panchroma-translucent-grey-pla-1kg',
		732847064 => 'panchroma-dual-yin-yang-pla-1kg',
		732847070 => 'panchroma-dual-matte-camouflage-dark-green-brown-pla-1kg',
		732847073 => 'panchroma-dual-matte-shadow-orange-orange-black-pla-1kg',
		732847116 => 'petg-1-75-mm-royal-blue-1kg',
		732848543 => 'panchroma-dual-matte-shadow-black-white-black-pla-1kg',
		732848564 => 'panchroma-galaxy-dark-green-pla-1kg',
		732848567 => 'panchroma-galaxy-dark-red-pla-1kg',
		732848570 => 'panchroma-luminous-blue-pla-1kg',
		732848572 => 'panchroma-luminous-green-pla-1kg',
		732848576 => 'panchroma-luminous-yellow-pla-1kg',
		732848579 => 'panchroma-metallic-blue-pla-1kg',
		732848621 => 'petg-1-75-mm-transparent-1kg',
		732854274 => 'panchroma-luminous-orange-pla-1kg',
		732854275 => 'panchroma-luminous-pink-pla-1kg',
		732854278 => 'panchroma-metallic-gold-pla-1kg',
		732854280 => 'panchroma-metallic-bronze-pla-1kg',
		732854283 => 'panchroma-metallic-green-pla-1kg',
		732854289 => 'panchroma-translucent-cyaan-pla-1kg',
		732854291 => 'panchroma-translucent-yellow-pla-1kg',
		732854301 => 'panchroma-dual-matte-foggy-purple-grey-purple-pla-1kg',
		732854307 => 'panchroma-gradient-matte-cappuccino-pla-1kg',
		733074787 => 'saturn-4-ultra-16k',
		736663712 => 'pei-peo-flexplate-300x300mm-ender-3v3-plus-k1-max',
		741559606 => 'polyflex-tpu95-blue-750g',
		741562313 => 'polyflex-tpu95-hf-white-1kg',
		743110795 => 'matte-pla-1-75-mm-pakket-8-basiskleuren-rood-geel-blauw-groen-wit-zwart-paars-oranje-8x1kg',
		743111792 => 'matte-pla-1-75-mm-pakket-4-basiskleuren-rood-geel-blauw-groen-4x1kg',
		752887248 => 'k1-series-upgradekit-1x-creality-cfs-creality-filament-system',
		754348770 => 'creality-k2-plus-ceramic-heating-block-kit',
		754348792 => 'creality-cutter-rod-for-k2-plus',
		754349264 => 'creality-unicorn-k2-plus-creality-hi-quick-swap-nozzle-0-2mm',
		754349288 => 'creality-top-glass-kit-for-k2-plus',
		762322491 => 'creality-unicorn-k2-plus-creality-hi-quick-swap-nozzle-0-6mm',
		762854727 => 'creality-filament-detector-sensor',
		762933358 => 'bambu-lab-ams-2-pro-automatic-material-system',
		764567868 => 'winkle-resin-grijs-10k-waterafwasbare-3d-hars-1kg',
		764569635 => 'winkle-resin-transparant-10k-waterafwasbare-3d-hars-1kg',
		764713155 => 'pei-peo-flexplate-250x250mm-bambu-lab-p1-x1-a1',
		764713677 => 'pei-starry-pey-flexplate-235x235mm-ender-3-series-k1-cr10-se',
		768190168 => 'sunlu-ptfe-teflon-tubes-for-filament-connector-200pcs',
		768209027 => 'winkle-3d-primer-spray-grijs-400ml',
		768209087 => 'winkle-3d-primer-spray-wit-400ml',
		768906740 => 'panchroma-silk-lime-pla-175mm-1kg',
		768906742 => 'panchroma-silk-magenta-pla-175mm-1kg',
		768906744 => 'panchroma-silk-peridot-green-pla-175mm-1kg',
		770520159 => 'panchroma-dual-silk-banquet-gold-magenta-pla-175mm-1kg',
		770521146 => 'panchroma-dual-silk-jadeite-green-chrome-pla-175mm-1kg',
		770524150 => 'panchroma-dual-silk-beluga-silver-blue-pla-175mm-1kg',
		770946285 => 'cfs-front-tray-shaft-per-stuk',
		772041363 => 'pei-flexplate-250x250mm-bambu-lab-p1-x1-a1',
		772866067 => 'elegoo-centauri-carbon',
		775668283 => 'pfa-film-for-saturn-2-8k-3-4-4-ultra-4-ultra-16k-5-pcs',
		775735042 => 'creality-otter-lite-3d-scanner',
		778041832 => 'creality-k2-pro-combo',
		778043792 => 'creality-k2-combo',
		779541234 => 'petg-1-75-mm-krystal-aquamarine-1kg',
		779569539 => 'petg-1-75-mm-krystal-lime-1kg',
		779604004 => 'petg-1-75-mm-krystal-pinkish-1kg',
		779855055 => 'aurapol-pla-175-mm-sandy-blush-1-kg',
		779855057 => 'aurapol-pla-175-mm-olive-powder-1-kg',
		779855058 => 'aurapol-pla-175-mm-wild-sage-1-kg',
		779855318 => 'aurapol-pla-175-mm-royal-red-1-kg',
		779873228 => 'aurapol-pla-175-mm-caramel-champagne-1-kg',
		780673034 => 'creality-falcon-series-opaque-glossy-colorful-acrylic-sheets-300-x-300-x-3mm-10pcs',
		780923256 => 'creality-cfs-485-cable-45cm',
		780924588 => 'sd-geheugenkaart-microdrive-hc-sd-8-gb-10-klasse',
		780928501 => 'creality-cfs-485-cable-100cm',
		780960520 => 'microsd-geheugenkaart-8-gb-10-klasse',
		784768997 => 'k2-plus-biqu-cryogrip-pro-buildplate-350x350',
		786373525 => 'panchroma-pla-celestial-white-pla-1kg',
		786373573 => 'panchroma-pla-celestial-light-yellow-pla-1kg',
		786449272 => 'panchroma-gradient-silk-pla-fire',
		787660910 => 'kroon-oil-naaimachine-olie-smo-1830-100ml',
		788531247 => 'bambu-lab-h2s-ams-combo',
		789765145 => 'bambu-lab-p2s-combo-met-ams2',
		790515198 => 'aurapol-pla-175-mm-metallic-purple-1-kg',
		792547035 => 'polymaker-petg-dark-blue-1kg',
		792547057 => 'polymaker-petg-dark-grey-1kg',
		794419791 => 'creality-hi-pei-diamond-peo-printoppervlak',
		795108276 => 'bambu-hotend-p2s-h2s-series-04mm-hardened-steel',
		797998844 => 'aurapol-pla-175-mm-mango-tango-1-kg',
		797998846 => 'aurapol-pla-175-mm-l-ego-baby-blue-1-kg',
		798000868 => 'aurapol-pla-175-mm-park-side-1-kg',
		798018082 => 'panchroma-silk-silver-pla-175mm-1kg',
		799792107 => 'winkle-resin-beige-10k-waterafwasbare-3d-hars-1kg',
		802287767 => 'nieuw-creality-falcon-a1-pro-20w-laser-engraver-cutter',
		803244575 => 'panchroma-galaxy-dark-grey-pla-1kg',
		803562039 => 'creality-3d-ring-synchronous-belt',
		808492124 => 'led-lamp-kit-collection',
		808492607 => 'bambu-hotend-p2s-h2s-series-02mm-stainless-steel',
		814903366 => 'hotend-heating-assembly-a1-series',
		816175232 => 'ruthex-m2-m3-m4-m5-threaded-insert-assortment-box-270pcs',
		816185781 => 'ruthex-soldering-tips-for-thread-inserts-m2-m2-5-m3-m4-m5-m6-m8',
		819098511 => 'nieuw-elegoo-centauri-carbon-2',
		824120449 => 'anti-vibration-feet-x1-p1-p2-series',
		828223138 => 'panchroma-silk-light-blue-pla-175mm-1kg',
		828223154 => 'panchroma-silk-dark-bluepla-175mm-1kg',
		828223963 => 'panchroma-silk-chrome-pla-175mm-1kg',
		828243266 => 'panchroma-pla-refill-basic-orange-1kg',
		828243503 => 'panchroma-pla-refill-matte-savannah-yellow-1kg',
		828243512 => 'panchroma-pla-refill-matte-pastel-peanut-1kg',
		828244001 => 'panchroma-pla-refill-matte-lava-red-1kg',
		828244005 => 'panchroma-pla-refill-matte-sunrise-orange-1kg',
		828244017 => 'panchroma-pla-refill-matte-army-beige-1kg',
		828244019 => 'panchroma-pla-refill-matte-earth-brown-1kg',
		828244020 => 'panchroma-pla-refill-matte-wood-brown-1kg',
		828244043 => 'panchroma-pla-refill-basic-black-1kg',
		828988181 => 'creality-hi-hotend-kit',
		833971951 => 'panchroma-matte-muted-moss-pla-1kg',
		833994522 => 'panchroma-matte-pastel-coral-pla-1kg',
		833994534 => 'panchroma-matte-muted-mauve-pla-1kg',
		833995504 => 'panchroma-matte-muted-terracotta-pla-1kg',
		833995506 => 'panchroma-matte-grass-green-pla-1kg',
		835446823 => 'creality-unicorn-k2-plus-creality-hi-quick-swap-nozzle-0-8mm',
		838653238 => 'pla-silk-multicolor-blue-yellow-1kg',
		838653242 => 'pla-silk-multicolor-white-red-1kg',
		838659156 => 'pla-silk-multicolor-yellow-red-1kg',
		838659169 => 'pla-silk-multicolor-blue-white-red-1kg',
		838659207 => 'polymaker-petg-red-1kg',
		838659209 => 'polymaker-petg-blue-1kg',
		838659215 => 'polymaker-petg-teal-1kg',
		838659217 => 'polymaker-petg-silver-1kg',
		838659218 => 'polymaker-petg-purple-1kg',
		838659245 => 'polymaker-petg-dark-purple-1kg',
		838659249 => 'fiberon-asa-cf08-navy-blue-05kg',
		838661591 => 'pla-silk-multicolor-blue-white-1kg',
		838661595 => 'pla-silk-multicolor-green-red-1kg',
		838661608 => 'pla-silk-multicolor-yellow-green-1kg',
		838661613 => 'pla-silk-multicolor-green-white-red-1kg',
		838661648 => 'polymaker-petg-electric-blue-1kg',
		838661650 => 'polymaker-petg-lime-1kg',
		838661654 => 'polymaker-petg-pink-1kg',
		838665754 => 'pla-silk-multicolor-yellow-green-1kg',
		838665759 => 'pla-silk-multicolor-yellow-black-red-1kg',
		838665762 => 'pla-hd-texture-concrete-1kg',
		838665763 => 'pla-hd-texture-quartz-1kg',
		838665766 => 'pla-hd-texture-blue-granite-1kg',
		838665767 => 'pla-hd-texture-brick-1kg',
		838665769 => 'pla-hd-texture-yellow-marble-1kg',
		838665787 => 'polymaker-petg-grey-1kg',
		838665790 => 'polymaker-petg-orange-1kg',
		838665791 => 'polymaker-petg-yellow-1kg',
		838665800 => 'polymaker-petg-green-1kg',
		838665802 => 'polymaker-petg-magenta-1kg',
		839494579 => 'originele-elegoo-centauri-carbon-nozzle-messing',
		842110616 => 'bambu-lab-a2l-combo-met-ams',
		842645079 => 'panchroma-pla-refill-matte-army-dark-green-1kg',
		842649283 => 'panchroma-pla-refill-basic-brown-1kg',
		845709303 => 'bambu-lab-pla-translucent-purple-1kg-met-spoel',
		845715325 => 'bambu-lab-petg-translucent-purple-1kg-navulling',
		845757027 => 'panchroma-basic-pla-pakket-8-basiskleuren-rood-geel-blauw-groen-wit-zwart-paars-oranje-8x1kg-1-75-mm',
		845949539 => 'galaxy-surface-plate-bambu-lab-p1-x1-a1-p2-x2',
		845961766 => 'creality-k2-pro-pei-printoppervlak',
		845961853 => 'pei-flexplate-snapmaker-u1',
		845968096 => 'peo-pet-flexplate-snapmaker-u1',
		845968341 => 'creality-k2-pro-starry-pey-chameleon-diamond-peo-printoppervlak',
		846183111 => 'creality-falcon-series-frosted-mat-orange-acrylic-sheets-300-x-300-x-3mm-1pcs',
		846183113 => 'creality-falcon-series-frosted-mat-purple-acrylic-sheets-300-x-300-x-3mm-1pcs',
		846183147 => 'creality-falcon-series-frosted-mat-blackacrylic-sheets-300-x-300-x-3mm-1pcs',
		846185788 => 'creality-falcon-series-transparent-acrylic-sheets-200x-200x-3mm-10pcs',
		846185866 => 'creality-falcon-series-frosted-mat-turquoise-acrylic-sheets-300-x-300-x-3mm-1pcs',
		846313678 => 'creality-rotary-kit-pro-upgrade-package-for-a1',
		846536098 => 'creality-falcon-series-opaque-glossy-red-acrylic-sheets-300-x-300-x-3mm-1pcs',
		846536099 => 'creality-falcon-series-opaque-glossy-orange-acrylic-sheets-300-x-300-x-3mm-1pcs',
		846536109 => 'creality-falcon-series-opaque-glossy-black-acrylic-sheets-300-x-300-x-3mm-1pcs',
		846536110 => 'creality-falcon-series-transparent-red-acrylic-sheets-200x-200x-3mm-1pcs',
		846537839 => 'creality-falcon-series-opaque-glossy-green-acrylic-sheets-300-x-300-x-3mm-1pcs',
		846537840 => 'creality-falcon-series-opaque-glossy-purple-acrylic-sheets-300-x-300-x-3mm-1pcs',
		846537841 => 'creality-falcon-series-transparent-green-acrylic-sheets-200x-200x-3mm-1pcs',
		846537842 => 'creality-falcon-series-transparent-yellow-acrylic-sheets-200x-200x-3mm-1pcs',
		847359363 => 'aurapol-pla-175-mm-raspberry-partially-transparent-1-kg',
		847359364 => 'aurapol-pla-175-mm-lipstick-red-1-kg',
		847359365 => 'aurapol-pla-175-mm-lavender-field-partially-transparent-1-kg',
		847360322 => 'aurapol-pla-175-mm-machine-blue-1-kg',
		847360337 => 'aurapol-pla-175-mm-evergreen-moss-1-kg',
		847364593 => 'aurapol-pla-175-mm-bondi-beach-1-kg',
		847364595 => 'aurapol-pla-175-mm-aqua-dream-partially-transparent-1-kg',
		847380520 => 'aurapol-pla-175-mm-galaxy-black-1-kg',
	);
}

/** Vangt de oude /webshop/-URLs op en stuurt ze door met een 301. */
function threeducation_legacy_redirect() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path    = trim( (string) wp_parse_url( $request, PHP_URL_PATH ), '/' );

	if ( 0 !== strpos( $path, 'webshop/' ) ) {
		return;
	}

	$rest = substr( $path, strlen( 'webshop/' ) );

	// Oude productpagina: <naam>-p<ID>.
	if ( preg_match( '#^(.+)-p(\d+)$#', $rest, $matches ) ) {
		$map = threeducation_legacy_product_map();

		// Eerst op het oude ID: dat overleeft een hernoemd product.
		$ids = threeducation_legacy_product_id_map();
		$old = (int) $matches[2];
		if ( isset( $ids[ $old ] ) ) {
			$key = threeducation_legacy_key( $ids[ $old ] );
			if ( isset( $map[ $key ] ) ) {
				wp_safe_redirect( get_permalink( $map[ $key ] ), 301 );
				exit;
			}
		}

		// Dan op de oude naam.
		$key = threeducation_legacy_key( $matches[1] );
		if ( isset( $map[ $key ] ) ) {
			wp_safe_redirect( get_permalink( $map[ $key ] ), 301 );
			exit;
		}

		// Product bestaat niet meer: toon wat er wél is.
		wp_safe_redirect(
			add_query_arg(
				array(
					's'         => rawurlencode( str_replace( '-', ' ', $matches[1] ) ),
					'post_type' => 'product',
				),
				home_url( '/' )
			),
			301
		);
		exit;
	}

	// Oude categoriepagina: <naam>-c<ID>. We sturen door naar het echte
	// categoriearchief, want dat draagt de markdown-beschrijving, de thumbnail
	// en de uitgelichte producten van die categorie — een gefilterde shop-URL
	// heeft dat allemaal niet. `get_term_link()` bouwt de URL met de basis die
	// op de site is ingesteld (op deze site het Nederlandse `product-categorie`),
	// dus die basis mag hier nooit hardcoded staan.
	if ( preg_match( '#^(.+)-c\d+$#', $rest, $matches ) ) {
		$term = get_term_by( 'slug', sanitize_title( $matches[1] ), 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				wp_safe_redirect( $link, 301 );
				exit;
			}
		}
	}

	// Alles wat overblijft onder /webshop/ gaat naar de webshop zelf.
	if ( function_exists( 'wc_get_page_id' ) ) {
		$shop_id = wc_get_page_id( 'shop' );
		if ( $shop_id > 0 ) {
			wp_safe_redirect( get_permalink( $shop_id ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'threeducation_legacy_redirect', 1 );
