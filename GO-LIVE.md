# Go-live checklist — 3DUCATION theme

The theme is **source only**: no products, pages, or content travel with it. Everything
under **"On the server"** must be set up on each WordPress install (dev / test / live).
There is **no build step** — the files you upload are what runs.

---

## 1. Deploy the theme files

Ship theme source only — never upload `node_modules/`, `.git/`, `.claude/`,
`.wp-env.json`, or `package*.json`.

```bash
# clean zip of committed files (run from repo root)
git archive --format=zip -o 3ducation.zip HEAD
```

Then either:
- **Appearance → Themes → Add New → Upload** the zip (atomic, simplest), or
- **SFTP** the files into `wp-content/themes/3ducation/` (faster for single-file tweaks).

### Ook uploaden: de mu-plugin (buiten het thema)
`mu-plugins/3ducation-legacy-redirects.php` hoort in **`wp-content/mu-plugins/`**, niet in
het thema — het zit dan ook bewust niet in de zip. Redirects zijn site-infrastructuur en
moeten een themawissel overleven; mu-plugins laden altijd en kunnen niet gedeactiveerd
worden. Bestaat de map nog niet, maak hem aan. Er is geen activatiestap.

**Volgorde bij de eerste keer:** upload eerst de themaversie waarin de redirect-code weg is
(v0.18.5 of nieuwer), pas daarna de mu-plugin. Staan beide er tegelijk, dan geeft PHP een
fatal error over een dubbele `threeducation_legacy_key()` — mu-plugins laden vóór het thema.

### After every upload — the version bump is the cache flush
Bump `THREEDUCATION_VERSION` in `style.css` **and** `functions.php` in lockstep on
every release (zie `style.css`). That one bump covers both caches:

- **Browser CSS/JS** — the version is the `?ver=` query arg on every enqueued asset.
- **Block patterns** — since WP 6.4 the pattern cache is keyed on the theme's
  `Version:` header, so a new version makes WordPress re-scan `patterns/` by itself
  (`WP_Theme::get_pattern_cache()`, `wp-includes/class-wp-theme.php`).

So a normal release needs **no** theme switching and no WP-CLI. Only if you ever edit
`patterns/*.php` on the server *without* bumping the version does the cache go stale;
then run `wp eval 'wp_clean_themes_cache(); wp_cache_flush();'`, or — with no WP-CLI on
the host — switch to another theme and back (there is no "deactivate" for themes; Site
Editor customisations survive the switch, they are stored per theme).

---

## 2. On the server — Pages (or nav links 404)

Create a **Page** for each of these slugs and assign the matching template
(Page → **Page Attributes → Template**). WooCommerce creates `/shop`, `/cart`,
`/checkout`, `/my-account` itself.

| Page slug                | Template to assign        | Linked from |
|--------------------------|---------------------------|-------------|
| `oplossingen`            | Webshop & oplossingen     | nav "3D-printen" |
| `workshops`              | Workshops & educatie      | nav |
| `service`                | Service & montage         | nav |
| `over-ons`               | Over ons                  | nav |
| `contact`                | Contact                   | nav + footer |
| `educatieve-pakketten`   | Educatieve pakketten      | CTAs |

---

## 3. On the server — WooCommerce & settings

- [ ] `wp option update woocommerce_coming_soon no` (else `/shop` shows a placeholder)
- [ ] Import / enter **products** and **product categories**
- [ ] Create the **`kleur`** and **`type`** product attributes **and assign them to
      products** — otherwise the shop's Kleur/Type filters render empty. (The pattern
      resolves the attribute ID by label per environment, so no code change is needed —
      the attributes just have to exist.)
- [ ] **Settings → Uitgelichte producten** — pick up to 3 spotlight products
      (falls back to the 3 newest published products)
- [ ] Per **product category**: markdown description + thumbnail image
- [ ] Create **workshop session** products (they appear on `/workshops`)
- [ ] **Settings → Site melding** — configure or disable the announcement bar
      (the "Aangepaste openingsuren…" banner is test content)
- [ ] Configure a **payment gateway** (e.g. Mollie)
- [ ] **Cal.com — workshops & verjaardagsfeestjes.** Boekingen lopen via Cal.com
      (gratis plan, extern) — **geen** LatePoint, geen booking-plugin. Twee event types
      bestaan al:
      - **Workshop 3D Printen** → `https://cal.com/3ducation/workshop-3d-printen`
        (€ 100, ± 2 uur)
      - **Verjaardagsfeestje 3D Printen** → `https://cal.com/3ducation/verjaardagsfeestje-3d-printen`
        (€ 25 per kind, max 8 kinderen, woensdagnamiddag/weekend/schoolvakanties ± 13u30–16u30)
      De knoppen in `workshops-detail.php` en `workshops-parties.php` openen de agenda
      in een **Cal.com-popup** (`assets/cal-embed.js` + `threeducation_calcom_button_attrs()`);
      de `href` naar cal.com blijft als fallback staan wanneer het script niet laadt.
      Wijzigt een URL, override hem dan met het filter
      `threeducation_calcom_url( $url, $event )` (`$event` = `workshop` of `feestje`) —
      de standaard-URL's staan in `functions.php`.
      **Cookiebanner:** de popup laadt `app.cal.com/embed/embed.js`, een derde partij —
      neem Cal.com op in de cookie-/privacyverklaring.
      **Instellen in Cal.com (feestje):** één ouder boekt de héle namiddag voor de groep,
      dus **"Offer seats" moet UIT** — dat is bedoeld voor losse deelnemers die elk apart
      hetzelfde tijdslot boeken, wat hier niet het geval is. In plaats daarvan: duur = 3 uur
      (13u30–16u30), availability = woensdagnamiddag/weekend/schoolvakanties, en een
      verplichte **booking question "Aantal kinderen"** (getal, max 8) plus "Leeftijd van de
      jarige". De prijs (€ 25 per kind) volgt dan uit dat antwoord.
      **Betaling:** Cal.com's Stripe-app zit niet op het gratis plan, dus er wordt achteraf /
      op factuur betaald. Wil je vooraf laten afrekenen, houd de workshop dan daarnaast als
      WooCommerce-product zodat Mollie het kan innen.
- [ ] **Cadeaubonnen — PW WooCommerce Gift Cards.** Er staan nog bonnen uit het oude
      systeem open die **deels afgewaardeerd** kunnen zijn, dus ze moeten mét resterend
      saldo mee; losse WooCommerce-coupons kunnen dat niet.
      **1. Data verzamelen (klant, eerst doen).** Per open bon: code, *resterend* saldo,
      vervaldatum, en e-mail van de begunstigde als die bekend is. Zonder die lijst heeft
      de rest geen zin — dit is het enige echte blokkerende stuk.
      **2. Gratis versie op test installeren** en het bon-product aanmaken. Zet **Tax
      Status = None** op dat product: de plugin behandelt bonnen als *multi-purpose*, dus
      de BTW valt bij het **inwisselen**, niet bij de aankoop. Op "Taxable" zetten geeft
      dubbele BTW (de plugin haalt de BTW bij inwisseling er niet vanzelf af).
      **3. Testen zonder licentie.** De gratis versie heeft geen "maak bon aan"-knop; de
      officiële omweg is een **100 %-kortingscoupon** voor jezelf en dan een bon
      front-end bestellen. Test daarmee: deel-inwisseling + restsaldo, en één bestelling
      waarin bon **+** WPLoyalty-punten **+** aanbiedingsprijs samenvallen, met de BTW
      erop (die velden wijzigden in v0.17.4).
      **4. Pas dan Pro kopen** — $99 voor 1 site, jaarlijkse verlenging, **geen
      geld-terug-garantie**. Alleen Pro kan importeren (en handmatig bonnen aanmaken).
      **5. Importformaat** (`pimwick.com/gift-cards-sample.csv`): **geen kopregel**, vier
      kolommen — code, saldo, vervaldatum, e-mail; de laatste twee optioneel. Twee vallen:
      de datum is **Amerikaans MM/DD/YYYY** en het saldo gebruikt een **punt** als
      decimaalteken, terwijl een Belgische Excel-export DD/MM/YYYY en een komma geeft.
      Zie `docs/cadeaubonnen-template.csv`.
      **6. Importeer eerst 2-3 rijen**, controleer saldo + vervaldatum in de admin, en pas
      daarna de rest — en importeer elke code exact één keer.
- [ ] Install **WooCommerce Product Add-Ons** (`woocommerce-product-addons`) — it powers
      the per-product option selectors (e.g. the printer workshop's "Kies hier uw
      optie" zelfbouw / gemonteerd / +workshop radio group with price deltas). The
      option copy itself is product data entered per product, not theme source.
- [ ] **Translate Product Add-Ons UI strings to Dutch** — the plugin ships no nl_NL for a
      few labels, so the option block shows English **"Product Price"** and **"Total"**.
      These live in the `woocommerce-product-addons` text domain (a *different* domain
      from the theme's `gettext_woocommerce` fallback filter, which only covers core
      WooCommerce). Fix with **Loco Translate** → Plugins → WooCommerce Product Add-Ons
      → Dutch: "Product Price" → "Productprijs", "Total" → "Totaal" (check also
      "Grand total" / "Options total"). Loco writes an update-safe `.mo` into
      `wp-content/languages/plugins/`.
- [ ] **WPLoyalty naar het Nederlands.** Doe eerst de *instellingen*: campagnenamen,
      beloningslabels, de tekst van de puntenwidget en de e-mailsjablonen zijn invoervelden
      in WPLoyalty zelf — vul die meteen Nederlands in, dat dekt het meeste van wat een
      bezoeker leest. Wat dan nog Engels blijft, vertaal je met **Loco Translate** →
      Plugins → WPLoyalty → Nederlands, met opslaglocatie **"Custom"**
      (`wp-content/languages/loco/`) zodat een plugin-update de vertaling niet wist.
      Alleen de front-end strings zijn de moeite (puntensaldo, inwisselknop, kortingsregel
      in de winkelwagen); de admin-strings mogen Engels blijven. Sitetaal moet op Nederlands
      staan, anders laadt WordPress het `.mo`-bestand niet.

---

## 4. Before launch — code TODOs still in the theme

**Forms**
- [x] **The three intake forms are wired** — `contact-form.php`, `service-intake.php`,
      `workshops-intake.php` POST to an `admin-post.php` handler in `functions.php`
      (nonce + honeypot + `wp_mail` to `info@3ducation.be`; the service form also attaches
      photos/videos). Success/error banners show on the page after submit.
- [ ] **On the live host, install an SMTP plugin** (e.g. WP Mail SMTP) so `wp_mail` actually
      delivers, then send a test through each form. Override the recipient with the
      `threeducation_intake_recipient` filter if it isn't `info@3ducation.be`.

**Copy**
- [x] Audience-card, intake, about and package copy finalised.
- [x] **Team names filled** — Natalie Verbeke / Patrick Smet / Cato Smet (`about-team.php`).
- [x] **No `[Placeholder]` markers remain** — the three form dev-notes are gone now the
      forms are wired.

**Photos** — every "Foto"/"Foto volgt" placeholder tile is now gone; all heroes,
gallery and team tiles ship a real image. Several are **stock stand-ins**, so
before (or shortly after) launch swap these for genuine 3DUCATION photos:
- [ ] **Team portraits** (`about-team.php` — `team-natalie/-patrick/-cato.jpg`)
      are stock people — replace with real photos of the team.
- [ ] **"Uit de praktijk" gallery** (`about.php`) + some section photos reuse
      generic 3D-print stock — swap for real workshop/classroom/party shots.
- Real photos already in place across: homepage hero, `/oplossingen`, `/workshops`,
  `/educatieve-pakketten`, `/service`, `/over-ons`. (To revert the homepage hero to a
  different image, point `.home-hero` in `hero.php` at another file in `assets/images/`.)

---

## 5. Env parity — do NOT

- Hard-code product-attribute IDs (they differ per environment; the theme resolves
  them at render time on purpose).
- Copy the local wp-env database to the server — enter content on the target site.
- Upload `.wp-env.json` — it's local-only and will confuse a managed host.
