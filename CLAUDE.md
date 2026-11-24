# CLAUDE.md

## What this is

`3ducation` is a custom **WooCommerce block theme (FSE)** for 3ducation.be, a Belgian 3D-printing shop. **Theme source only**: products, pages and content live on the live site. **No build step**: `theme.json`, HTML templates and PHP patterns ship as-is. UI copy is Dutch (nl_NL / EUR / BE).

Also in the repo but **not in the theme zip** (`.gitattributes` `export-ignore`):
- `mu-plugins/*.php`: must-use plugins, uploaded per file to `wp-content/mu-plugins/`. Overview + per-plugin update check in `mu-plugins/README.md` (keep it current on every mu-plugin change). **Each file's header docblock is the spec** (why, hooks, edge cases, requirements such as the `.htaccess` block for `3ducation-versiepaden.php`). Read the header before touching one.
- Live runs **WCPOS Pro** (`plugins/woocommerce-pos-pro`, core bundled in `vendor/wcpos/woocommerce-pos`); the free `plugins/woocommerce-pos` 1.10.7 folder on live is stale, not the active code.
- `GO-LIVE.md` (server-side checklist).
- Gitignored local tooling: `_import/`, `.claude/`, `.agents/`, `*.zip`, `*.pdf`, `overdracht.html`.

## Local development

Docker/OrbStack + **Node 22 LTS** (newer Node breaks `wp-env`; on Node 26 the WP container silently fails to recreate).

```bash
export PATH="$HOME/.nvm/versions/node/v22.23.1/bin:$PATH"   # nvm use 22 does NOT stick in non-interactive shells
npm install
./node_modules/.bin/wp-env start      # or: npm start
```

- http://localhost:8888, admin `admin` / `password`. WP-CLI: `./node_modules/.bin/wp-env run cli wp <command>`.
- Avoid restarting wp-env unless `.wp-env.json` changed; the DB volume survives restarts.
- Gitignored `.wp-env.override.json` pins WP core (currently 7.0 branch).
- No tests, no linter: verify with screenshots + `php -l`.
- Testing a mu-plugin locally: copy the file into the container, never commit test fixtures.

### Gotchas

- **Pattern cache:** after editing `patterns/*.php` run `wp eval 'wp_clean_themes_cache(); wp_cache_flush();'` (plain `wp cache flush` is not enough). Templates are read from disk. On deploy the `Version:` bump invalidates it.
- **Coming soon:** `wp option update woocommerce_coming_soon no` to see the real shop locally.
- **CSS cache:** assets carry a fixed version; hard-refresh. Headless screenshots are always fresh.
- **Screenshots:** `"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless --disable-gpu --hide-scrollbars --window-size=W,H --virtual-time-budget=4000 --screenshot=out.png URL`
- **Site Editor overrides:** saving a template/part in the Site Editor writes a DB copy that shadows the theme file forever. If a template change doesn't show, check for a DB override first ("Clear customizations").
- `woocommerce/product-filter-*` block-validation console spam in the editor is noise.

## Architecture

Put a change at the **lowest layer** that can express it:

1. **`theme.json` (v3)**: single source of design tokens. Tricolor brand (magenta/cyan/amber), light ramp (`base`/`surface`/`ink`/`ink-soft`/`muted`/`border`/`base-soft`), dark sub-palette (`surface-dark`, `ink-line`, `mist`, `mist-soft`), `cube` gradient, self-hosted Space Grotesk, fluid type/spacing (`20`→`80`, `45` = 2rem). Core default presets are disabled. **No raw design hex** anywhere else. `customTemplates` registers the pillar/contact templates + `page-no-title`.
2. **`assets/custom.css`**: only what `theme.json` can't express (bento grid, marquee, hover physics, notice bar, forms, read-more). `!important` against WooCommerce/core CSS is expected.
3. **`templates/*.html` + `parts/*.html`**: block markup with inline block attributes, no custom CSS. `parts/footer.html` just references the `3ducation/footer` pattern.
4. **`patterns/*.php`**: docblock (`Title:`, `Slug: 3ducation/<name>`, `Categories:`) is the registration. Pattern PHP runs per request, so several render live data (`product-spotlights` = category chips, `product-banner`, `category-intro`, `shop-filters` (attribute IDs looked up at render, never hardcode), `product-trust`, `footer`). Inserter-only, used by no template: `featured-products`, `product-categories`, `workshops-cta`, `cookiebeleid`.

**Never hardcode shop URLs in patterns** (category base is `product-categorie` under nl_NL; WC pages can move). Use `threeducation_product_cat_url( $slug, $fallback )` and `threeducation_wc_page_url( 'cart'|'checkout'|'myaccount'|'shop' )`. Theme-owned slugs (`/shop`, `/workshops`, …) are fine.

### `functions.php`

All substantive PHP in one file on purpose, in commented sections. Things that aren't obvious from reading it:

- The enqueue callback is misspelled `threducation_enqueue_assets`: keep it.
- **Settings screens** (wp-admin → Settings: Site melding, Site pop-up, Uitgelichte producten, Vertrouwensbadges, Boekingslinks, Footer, Cookies): one option each, with `_defaults()` + merge so empty fields fall back. Notice/pop-up use a content-hash id so an edited message re-shows after dismissal.
- **Cookies:** tracking comes from plugins (MonsterInsights, Jetpack, WC order attribution), so the theme output-buffers front-end HTML and rewrites recognised scripts to `type="text/plain" data-3du-consent=…` (`threeducation_cookies_blocklist()`); `assets/cookies.js` re-creates them after consent, sets Consent Mode v2 and shims the WP Consent API (GA for WooCommerce dies without it). Banner bottom-right; the pop-up pill is bottom-left and waits for the banner.
- **Product visibility window:** `_visible_from` / `_visible_until` meta hide products everywhere (query, 404, non-purchasable, related). Used for dated workshop sessions.
- **BTW-nummer:** one field, the EU VAT plugin's `billing_eu_vat_number` / `_billing_eu_vat_number` (`THREEDUCATION_VAT_META`), shared by checkout, WCPOS and OnFact. `billing_vat` is legacy (read fallback only). **Never bulk-migrate orders**: every order save fires the OnFact webhook.
- **Booking links:** `threeducation_booking_link( $event )` resolves url / email / product_cat → Cal.com default; buttons via `threeducation_booking_button_attrs()`. Only `cal.com/<user>/<slug>` gets the popup embed.
- **Intake forms** (`contact-form`, `service-intake`, `workshops-intake`) POST to `admin-post.php` → `threeducation_handle_intake`, no form plugin; recipient via `threeducation_intake_recipient`.
- Pre-order plugin filter really is spelled `preorder_avaiable_date_text`.
- Legacy redirects live in the mu-plugin. **Don't put them back** here: duplicate function declarations fatal.

### Content and brand

- Three pillars, each a slug-mapped FSE template (`page-<slug>.html`): `/oplossingen` (nav "3D-printen"), `/workshops` (incl. Educatie/scholen), `/service`. Plus `/educatieve-pakketten`, `/over-ons`, `/contact`. **Pillar pages stay templates + patterns**; the wp-admin Page is an empty shell so the slug resolves.
- Pillar colours: magenta = oplossingen/webshop (and primary buttons everywhere), cyan = workshops/educatie, amber = service. Scholen = cyan, thuis = magenta. Enumerations (tiers, steps, 404) use all three; exceptions: category chips + Uitgelicht strip amber-only, educatie cards/steps cyan-only.
- In block markup reference presets as `var:preset|color|<slug>` so blocks still validate.
- Brand is written `3DUCATION` in copy; identifiers stay lowercase `3ducation`.

## Conventions

- PHP prefix `threeducation_`; text domain and pattern category `3ducation` (don't rename).
- **`THREEDUCATION_VERSION` in `functions.php` and `Version:` in `style.css`: bump both on every shipped change.** It busts asset caches and the pattern cache.
- Commit messages Dutch, with version: `Maak de footerteksten bewerkbaar voor de klant (v0.18.22)`.
- All user strings Dutch, wrapped in `__()`/`esc_html__()` with `3ducation`. Comments Dutch or English.
- Check the 782–1023px tablet band on layout changes. `:has()` can't nest. Cart/checkout strings come from WooCommerce JS i18n.

## Deployment

EasyHost Managed WordPress; full procedure in `GO-LIVE.md`.

```bash
git archive --format=zip --prefix=3ducation/ -o 3ducation-<versie>.zip HEAD
```

- `--prefix=3ducation/` is mandatory (otherwise a second theme folder, Site Editor customisations detach).
- Asset URLs carry the version in the path (`/assets/v0.18.33/custom.css`, rewritten by `assets/.htaccess`) because EasyHost's cache ignores query strings (30 days, per `Accept-Encoding`). No version bump = stale CSS/JS. Verify live with `curl -H 'Accept-Encoding: gzip, br' --compressed`.
- Mu-plugins are uploaded separately. Never copy the local DB to the server.
