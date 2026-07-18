<?php
/**
 * Title: Educatie pakketten
 * Slug: 3ducation/edu-packages
 * Categories: 3ducation
 * Description: The three educational packages — Single, Double and Multi — as color-coded cards.
 *
 * Reuses the workshops audience-card system (.wsaud-card / .wsaud-list) so the
 * package cards match the rest of the site; adds only .edu-grid + .edu-foot.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Onze educatieve pakketten</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|ink-soft"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:var(--wp--preset--color--ink-soft)">Elk pakket is een voorbeeld en vrij combineerbaar. De keuze van de printers is volledig aan jou. Wij adviseren volgens de noden van je school.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|40"} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"className":"edu-grid","layout":{"type":"default"}} -->
<div class="wp-block-group edu-grid"><!-- wp:group {"className":"wsaud-card wsaud-card--cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group wsaud-card wsaud-card--cyan"><!-- wp:paragraph {"className":"wsaud-tag","fontSize":"small","fontFamily":"display"} -->
<p class="wsaud-tag has-display-font-family has-small-font-size">Educatief pakket · Voorbeeld 1</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">&#8220;Single&#8221;</h3>
<!-- /wp:heading -->

<!-- wp:image {"className":"edu-thumb"} -->
<figure class="wp-block-image edu-thumb"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/edu-pakket-single.png' ) ); ?>" alt="<?php echo esc_attr__( 'Voorbeeld van het Single-pakket: 1 Creality 3D-printer, starterspack, workshop voor 2 leerkrachten en support.', '3ducation' ); ?>"/></figure>
<!-- /wp:image -->

<!-- wp:list {"className":"wsaud-list"} -->
<ul class="wp-block-list wsaud-list"><!-- wp:list-item --><li>1 × 3D-printer naar eigen keuze</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Starterspack: 3D-LAc + 2 rollen Winkle PLA HD, keuze uit 30+ kleuren</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Installatie van printer &amp; software, klaar voor gebruik</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Workshop 3D-printen voor 2 leerkrachten (± 2 uur)</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Support bij problemen</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:group {"className":"edu-foot","layout":{"type":"default"}} -->
<div class="wp-block-group edu-foot"><!-- wp:paragraph {"className":"edu-price","fontSize":"small"} -->
<p class="edu-price has-small-font-size"><strong>Prijs</strong>Afhankelijk van de keuze van de printer</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"edu-cta","fontFamily":"display"} -->
<p class="edu-cta has-display-font-family"><a class="stretched" href="mailto:info@3ducation.be?subject=Offerte%20educatief%20pakket%20Single">Offerte aanvragen →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"wsaud-card wsaud-card--cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group wsaud-card wsaud-card--cyan"><!-- wp:paragraph {"className":"wsaud-tag","fontSize":"small","fontFamily":"display"} -->
<p class="wsaud-tag has-display-font-family has-small-font-size">Educatief pakket · Voorbeeld 2</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">&#8220;Double&#8221;</h3>
<!-- /wp:heading -->

<!-- wp:image {"className":"edu-thumb"} -->
<figure class="wp-block-image edu-thumb"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/edu-pakket-double.png' ) ); ?>" alt="<?php echo esc_attr__( 'Voorbeeld van het Double-pakket: 2 Creality 3D-printers, starterspack, workshop voor 4 leerkrachten en support.', '3ducation' ); ?>"/></figure>
<!-- /wp:image -->

<!-- wp:list {"className":"wsaud-list"} -->
<ul class="wp-block-list wsaud-list"><!-- wp:list-item --><li>2 × 3D-printer naar eigen keuze</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Starterspack: 3D-LAc + 2 rollen Winkle PLA HD, keuze uit 30+ kleuren</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Installatie van printers &amp; software, klaar voor gebruik</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Workshop 3D-printen voor 4 leerkrachten (± 2 uur)</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Support bij problemen</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:group {"className":"edu-foot","layout":{"type":"default"}} -->
<div class="wp-block-group edu-foot"><!-- wp:paragraph {"className":"edu-price","fontSize":"small"} -->
<p class="edu-price has-small-font-size"><strong>Prijs</strong>Afhankelijk van de keuze van de printers</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"edu-cta","fontFamily":"display"} -->
<p class="edu-cta has-display-font-family"><a class="stretched" href="mailto:info@3ducation.be?subject=Offerte%20educatief%20pakket%20Double">Offerte aanvragen →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"wsaud-card wsaud-card--cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group wsaud-card wsaud-card--cyan"><!-- wp:paragraph {"className":"wsaud-tag","fontSize":"small","fontFamily":"display"} -->
<p class="wsaud-tag has-display-font-family has-small-font-size">Educatief pakket · Voorbeeld 3</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">&#8220;Multi&#8221;</h3>
<!-- /wp:heading -->

<!-- wp:image {"className":"edu-thumb"} -->
<figure class="wp-block-image edu-thumb"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/edu-pakket-multi.png' ) ); ?>" alt="<?php echo esc_attr__( 'Voorbeeld van het Multi-pakket: meerdere Creality 3D-printers, starterspack, workshop voor meerdere leerkrachten en support.', '3ducation' ); ?>"/></figure>
<!-- /wp:image -->

<!-- wp:list {"className":"wsaud-list"} -->
<ul class="wp-block-list wsaud-list"><!-- wp:list-item --><li>3 of meer 3D-printers naar eigen keuze</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Starterspack: 3D-LAc + 2 rollen Winkle PLA HD, keuze uit 30+ kleuren</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Installatie van printers &amp; software, klaar voor gebruik</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Workshop op maat voor het hele leerkrachtenteam (duur afhankelijk van aantal printers, ± 2 uur)</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Support bij problemen</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:group {"className":"edu-foot","layout":{"type":"default"}} -->
<div class="wp-block-group edu-foot"><!-- wp:paragraph {"className":"edu-price","fontSize":"small"} -->
<p class="edu-price has-small-font-size"><strong>Prijs</strong>Afhankelijk van de hoeveelheid en de keuze van de printers</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"edu-cta","fontFamily":"display"} -->
<p class="edu-cta has-display-font-family"><a class="stretched" href="mailto:info@3ducation.be?subject=Offerte%20educatief%20pakket%20Multi">Offerte aanvragen →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
