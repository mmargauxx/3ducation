# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`3ducation` is a custom **WooCommerce block theme (Full Site Editing)** for [3ducation.be](https://3ducation.be), a Belgian 3D-printing shop (printers, filament, resin, accessories, workshops). It is **theme source only** — no products, pages, or content live here; those are entered on the live site. There is **no build step**: `theme.json`, HTML block templates, and PHP block patterns are shipped as-is. UI copy is Dutch (nl_NL / EUR / BE).

## Local development

Requires Docker (or OrbStack) and **Node 22 LTS** — newer Node breaks `wp-env`'s native install step, and the WordPress container silently fails to recreate (site goes down) on Node 26.

```bash
export PATH="$HOME/.nvm/versions/node/v22.23.1/bin:$PATH"   # nvm use 22 does NOT stick in non-interactive shells
npm install
./node_modules/.bin/wp-env start      # or: npm start
```

- Site: http://localhost:8888 · Admin: http://localhost:8888/wp-admin (`admin` / `password`)
- Stop: `npm stop` · Reset DB: `npm run clean` · Tear down: `npm run destroy`
- **Avoid restarting wp-env** unless `.wp-env.json` changed. Options and theme-file edits need no restart; the MySQL volume (products, options) survives restarts.

`.wp-env.json` provisions WooCommerce + WordPress Importer, PHP 8.2, and `WP_DEBUG`/`SCRIPT_DEBUG` on.

### Gotchas (verified, but re-check against current code)

- **Pattern cache:** after adding OR editing any `patterns/*.php`, `wp cache flush` alone is NOT enough — the block-pattern list is cached separately. Run `./node_modules/.bin/wp-env run cli wp eval 'wp_clean_themes_cache(); wp_cache_flush();'` or the pattern renders empty/stale. Template HTML edits (`templates/*.html`) are read from disk — no cache clear needed.
- **Coming-soon mode:** WooCommerce ships with `woocommerce_coming_soon=yes`, so `/shop` shows a placeholder. `wp option update woocommerce_coming_soon no` to see the real storefront (a DB setting, not in the theme).
- **CSS cache:** `custom.css` is enqueued with a fixed `?ver` (`THREEDUCATION_VERSION`), so edits don't bust returning browsers' cache — hard-refresh. Headless-Chrome screenshots are always fresh and can disagree with the browser.
- **Screenshots:** headless Chrome works — `"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless --disable-gpu --hide-scrollbars --window-size=W,H --virtual-time-budget=4000 --screenshot=out.png URL` (the virtual-time budget lets CSS load animations settle).
- **Site Editor blank canvas in Chrome:** Chrome 149 nulls the editor's `srcdoc` canvas iframe on load, so core's `block-editor.js` `onLoad` throws `Cannot destructure property 'documentElement' of 'contentDocument' as it is null` and the canvas stays blank. Not a theme bug — reproduces on core/WooCommerce blocks with no plugins/policies; Safari (different engine) is unaffected. Fixes: edit in Safari, edit theme files directly, or try a fresh Chrome profile / `chrome://flags` Reset all / a different Chrome channel. The `Block validation failed for woocommerce/product-filter-*` / `product-image` console spam that accompanies it is unrelated WooCommerce noise (prints in Safari too) — ignore it; the only line that matters is the single `Uncaught … contentDocument … null`.

## Architecture

Styling is **data-driven, layered by intent** — put a change at the lowest layer that can express it:

1. **`theme.json` (v3)** — the design system and single source of truth for tokens: the tricolor brand palette (magenta/cyan/amber + inks), the multi-stop `cube` gradient, self-hosted Space Grotesk `display` font, fluid type/spacing scales, and block/element style defaults. Most visual changes belong here, referenced as `var:preset|color|magenta`, `var:preset|spacing|60`, etc. `defaultPalette`/`defaultGradients`/`defaultFontSizes`/`defaultSpacingSizes` are all disabled — only the presets defined here exist.
2. **`assets/custom.css`** — ONLY the supplementary rules `theme.json` can't express: the category bento grid, the kinetic partner marquee, hover micro-physics, the site-notice bar. Enqueued in `functions.php` and also loaded as an editor stylesheet. Don't add here what a `theme.json` token or block attribute can do.
3. **`templates/*.html` + `parts/*.html`** — block-markup templates (`front-page`, `archive-product`, `single-product`, `page-cart`, `page-checkout`, `index`, `search`, `404`, …) and the `header`/`footer` parts. Templates compose patterns and WooCommerce blocks; they carry inline block attributes, not custom CSS.
4. **`patterns/*.php`** — reusable block-markup sections registered to the `3ducation` pattern category. Each file's PHP docblock (`Title:`, `Slug: 3ducation/<name>`, `Categories:`) is the registration; the body is block HTML. Patterns are referenced from templates via `<!-- wp:pattern {"slug":"3ducation/hero"} /-->`. Some (e.g. `product-banner`, `product-spotlights`) use PHP to build `get_template_directory_uri()` image paths.

**`functions.php`** is the only substantive PHP. It is deliberately thin: theme supports, WooCommerce declaration + product-gallery features, asset enqueue, pattern-category registration, and the **site-notice feature** — a dismissible announcement bar (`Settings → Site melding` in wp-admin) with an on/off toggle, optional CTA, and optional start/end date window. Notice settings carry a content-hash `id` so editing the message re-shows the bar to visitors who dismissed the prior version; `assets/notice.js` wires the close button and `localStorage` dismissal.

### Content flow

The homepage (`front-page.html`) is a brochure — hero → offering → partners → webshop-CTA — that funnels to a single Webshop (`/shop`, rendered by `archive-product.html` with a category bento and promo banners). Navigation and CTAs point at `/shop` and `/product-category/workshops`. Everything downstream (products, cart, checkout) is standard WooCommerce blocks styled through `theme.json`.

## Conventions

- **Naming:** PHP prefix is `threeducation_` (note: the enqueue callback is misspelled `threducation_enqueue_assets` — keep it consistent if referenced). Text domain and pattern category are both `3ducation`. Version constant `THREEDUCATION_VERSION` (currently `0.9.0`) also lives in `style.css` — bump both together, and it doubles as the asset cache-buster.
- **Brand:** magenta is the primary/action color; the `cube` gradient and per-section color-coding (magenta/cyan/amber) are the visual signature. Keep new UI within the existing palette rather than introducing raw hex values.
- **Localization:** all user-facing strings are Dutch and wrapped in `__()`/`esc_html__()` with the `3ducation` text domain.
- `.agents/`, `.claude/`, `skills-lock.json`, and `node_modules/` are tooling/deps, gitignored — not theme source.

## Deployment

Live site runs on **EasyHost Managed WordPress**. Deploy by SFTP into `wp-content/themes/3ducation/` (or zip → Appearance → Themes → Add New), then activate. WooCommerce + a payment gateway (e.g. Mollie) are configured on the live site; content does not travel with the theme.
