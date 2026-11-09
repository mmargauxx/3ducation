# Overzicht mu-plugins 3DUCATION

Stand: 9 november 2026.

Must-use plugins staan in `wp-content/mu-plugins/`, laden altijd en hebben geen activatieknop. Ze zitten niet in de thema-zip en worden per bestand via FTP geüpload naar `/www/wp-content/mu-plugins/`.

Een update van een andere plugin verwijdert ze nooit. Hernoemt die plugin een hook, dan stopt onze mu-plugin wel stil met werken, zonder foutmelding. Daarom staat bij elke plugin een controle na een update.

## In één oogopslag

| Bestand | Versie | Wat het doet | Op live |
|---|---|---|---|
| **Webshop** | | | |
| `3ducation-alleen-afhalen.php` | 1.0.0 | Montage-optie 2 of 3 gekozen: alleen afhalen mogelijk | Ja |
| `3ducation-bestelstatussen.php` | 1.1.0 | Statussen "Klaar voor afhaling" en "Verzonden" | Ja |
| `3ducation-pakket-voorraad.php` | 1.0.0 | Pakketvoorraad = laagste voorraad van alle producten | Ja |
| `3ducation-product-schema.php` | 1.0.0 | Prijs, voorraad en SKU terug in Google | Ja |
| `3ducation-ga-paginaweergave.php` | 1.1.0 | Voorkomt dubbele telling in GA4 | Ja |
| **Kassa (WCPOS)** | | | |
| `3ducation-pakket-kassavoorraad.php` | 1.0.0 | Verlaagt de voorraad van losse producten bij een pakket aan de kassa | Ja |
| `3ducation-pos-cadeaubon.php` | 1.1.0 | Cadeaubon verkopen en inwisselen aan de kassa | Ja |
| `3ducation-pos-kassabon-punten.php` | 1.1.0 | Spaarpunten op de kassabon | Ja |
| `3ducation-pos-btw-op-bestelling.php` | 1.0.0 | Zet het BTW-Nummer van de klant op de kassabestelling | Ja |
| `3ducation-pos-btw-verlegd.php` | 1.1.1 | Btw verlegd per klant aan de kassa | Ja, slapend |
| **Cadeaubonnen** | | | |
| `3ducation-cadeaubon-vervaldatum.php` | 1.1.0 | Nieuwe cadeaubon is 2 jaar geldig | Ja |
| **Site en beheer** | | | |
| `3ducation-legacy-redirects.php` | 1.3.0 | 301-redirects van de oude Duda-site | Ja |
| `3ducation-onderhoudsmodus.php` | 1.1.1 | Site of webshop tijdelijk in onderhoud | Ja, uit tot je hem aanzet |
| `3ducation-verberg-cron-melding.php` | 1.0.0 | Verbergt een valse WP-Cron-melding in wp-admin | Ja |
| **Alleen testomgeving** | | | |
| `3ducation-staging-veilig.php` | 1.1.0 | Blokkeert mail, webhooks en externe verzoeken op een kopie | Nee, bewust |

De versies komen uit de repo. "Op live" komt uit de bestandslijst van de server op 9 november; de inhoud van de live bestanden is die dag niet vergeleken, behalve bij `3ducation-alleen-afhalen.php`.

Op de server staan ook `ensure-admin-not-deleted.php` en `wp-cli-login-server.php`. Die zijn van EasyHost, staan niet in deze repo en mogen blijven staan.

## Webshop

### `3ducation-alleen-afhalen.php`
- **Doet:** kiest een klant een PPOM-optie met "gemonteerd" en "afgehaald" of "afhaling", dan blijft voor de hele bestelling alleen afhalen over. Bij afrekenen staat een melding.
- **Hangt af van:** PPOM, WooCommerce (`woocommerce_package_rates`, afrekenblok).
- **Controle na update:** printer met optie 2 in de winkelwagen geeft alleen afhalen en de melding. Optie 1 kan nog verzonden worden.
- **Let op:** herschrijf je de optietekst, hou dan die woorden in optie 2 en 3 en gebruik ze niet samen in optie 1. Live gebruikt de klassieke methode `local_pickup`, dus de klant vult nog een verzendadres in.
- **Open:** eerste echte bestelling nakijken.

### `3ducation-bestelstatussen.php`
- **Doet:** "Klaar voor afhaling" alleen voor afhaalbestellingen, met klantmail. "Verzonden" alleen voor het beheer, zonder mail.
- **Hangt af van:** WooCommerce (bestelstatussen, e-mails, bulkacties).
- **Controle na update:** beide statussen staan in de lijst, de snelknop verschijnt alleen op afhaalbestellingen en de klantmail staat onder WooCommerce → E-mails.

### `3ducation-pakket-voorraad.php`
- **Doet:** een pakket is uitverkocht zodra één product op 0 staat, ook als dat product nabesteld kan worden. Het getal wordt bij elke load herberekend, dus met de hand invullen heeft geen zin.
- **Hangt af van:** WPC Product Bundles (`woosb_stock_quantity_mode`).
- **Controle na update:** een pakket met 8 kleuren toont de laagste voorraad van alle kleuren.

### `3ducation-product-schema.php`
- **Doet:** zet WooCommerce's productgegevens voor Google terug, want SureRank haalt ze weg zonder eigen vervanging.
- **Hangt af van:** SureRank, WooCommerce.
- **Controle na update:** productpagina in de Rich Results Test van Google toont één Product-schema met prijs en voorraad.

### `3ducation-ga-paginaweergave.php`
- **Doet:** geen eigen `page_view` (MonsterInsights stuurt die al) en geen tweede `add_to_cart` na een klassieke toevoeging.
- **Hangt af van:** Google Analytics for WooCommerce, MonsterInsights.
- **Controle na update:** in GA4 DebugView telt één pagina en één toevoeging maar één keer.

## Kassa (WCPOS)

Na elke WCPOS-update: één cadeaubon verkopen, één inwisselen en de puntenregel op de bon nakijken.

### `3ducation-pakket-kassavoorraad.php`
- **Doet:** de kassa en handmatige bestellingen bevatten alleen de pakketregel. Deze plugin verlaagt de voorraad van elk product in het pakket en zet die terug bij annuleren of terugbetalen met "terug op voorraad". Via de bestelactie "Pakketvoorraad verlagen" kan een oudere bestelling alsnog verwerkt worden. Webshopbestellingen blijven ongemoeid.
- **Hangt af van:** WPC Product Bundles, voorraadhooks van WooCommerce.
- **Controle na update:** pakket aan de kassa verkopen: elke kleur gaat 1 omlaag en de bestelnotitie staat erbij.
- **Open:** eerste automatische kassaverkoop bevestigen.

### `3ducation-pos-cadeaubon.php`
- **Doet:** toont de bedragen van de PW-cadeaubon in de kassa en zet een ingegeven boncode om in een echte inwisseling.
- **Hangt af van:** WCPOS, PW Gift Cards, coupon-hooks van WooCommerce.
- **Controle na update:** in de kassa één bon verkopen en één inwisselen.

### `3ducation-pos-kassabon-punten.php`
- **Doet:** zet het WPLoyalty-saldo en de gespaarde punten van deze aankoop op de kassabon. De bonlay-out zelf staat in WCPOS op live.
- **Hangt af van:** WCPOS (`woocommerce_pos_receipt_data`), tabellen van WPLoyalty.
- **Controle na update:** bon van een klant met punten toont het saldo en de gespaarde punten. Een gast krijgt geen puntenregel.

### `3ducation-pos-btw-op-bestelling.php`
- **Doet:** omweg voor een WCPOS-bug: een klant die tijdens de verkoop wordt aangemaakt, krijgt zijn BTW-Nummer niet op de bestelling en OnFact mist het dan. Een bestaand nummer wordt nooit overschreven.
- **Hangt af van:** WCPOS, EU VAT-plugin (`billing_eu_vat_number`).
- **Controle na update:** nieuwe klant met BTW-Nummer aanmaken tijdens een verkoop: het nummer staat op de bestelling. Doet niets meer zodra WCPOS de bug oplost.
- **Open:** kassatest met een nieuwe testklant.

### `3ducation-pos-btw-verlegd.php`
- **Doet:** rekent aan de kassa zonder btw als alle vier kloppen: kassabestelling, klant op de bestelling, vinkje "Btw-verlegging (kassa)" bij de klant, niet-Belgisch EU-btw-nummer.
- **Hangt af van:** WCPOS, WooCommerce (`woocommerce_order_is_vat_exempt`).
- **Controle na update:** alleen nodig zodra de functie gebruikt wordt: verkoop aan een klant met vinkje toont geen btw-regel.
- **Let op:** doet niets zolang geen enkele klant het vinkje heeft.
- **Open:** vraag aan de boekhouder over toonbankverkoop en vak 46 in OnFact nakijken, voordat iemand het vinkje zet.

## Cadeaubonnen

### `3ducation-cadeaubon-vervaldatum.php`
- **Doet:** geeft elke nieuwe PW-cadeaubon een vervaldatum van 2 jaar na aankoop en zet "Geldig tot …" in de bonmail.
- **Hangt af van:** PW Gift Cards (`pwgc_activity_create`, `pwgc_customer_email_item_data`).
- **Controle na update:** nieuwe bon heeft de juiste vervaldatum en de mail vermeldt die.

## Site en beheer

### `3ducation-legacy-redirects.php`
- **Doet:** stuurt oude Duda-URL's met een 301 door naar de nieuwe pagina's.
- **Hangt af van:** alleen WordPress.
- **Controle na update:** een oude Duda-URL geeft een 301 naar de juiste pagina.
- **Let op:** zet deze redirects niet terug in `functions.php`, dat geeft een fatale fout door dubbele functies.

### `3ducation-onderhoudsmodus.php`
- **Doet:** toont bezoekers een onderhoudspagina (503) voor de hele site of alleen de webshop, meteen of gepland. Beheerders en de kassa werken gewoon verder. Instellen via Instellingen → Onderhoudsmodus.
- **Hangt af van:** alleen WordPress, plus thema-opmaak en footergegevens.
- **Controle na update:** het instellingenscherm opent en "Voorbeeld bekijken" werkt.

### `3ducation-verberg-cron-melding.php`
- **Doet:** verbergt de valse melding "WP Cron is disabled". `DISABLE_WP_CRON` staat bewust aan omdat EasyHost een echte cronjob draait.
- **Hangt af van:** alleen WordPress.
- **Controle na update:** de melding staat niet in wp-admin.

## Alleen testomgeving

### `3ducation-staging-veilig.php`
- **Doet:** blokkeert op `test.3ducation.be` alle mail, webhooks (OnFact) en externe verzoeken (Mollie, bpost, MailPoet). Op live doet het bestand niets, maar het hoort daar niet thuis.
- **Hangt af van:** alleen WordPress en WooCommerce-webhooks.
- **Controle:** rode balk in wp-admin op de testomgeving.
- **Let op:** een kopie van live overschrijft de mu-plugins op de testomgeving. Zet dit bestand er na elke kopie weer op, vóór het eerste inloggen.
