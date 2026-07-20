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

### After every upload — flush caches
Patterns are cached separately from the object cache, so a plain `wp cache flush`
is **not enough** after editing/adding any `patterns/*.php`:

```bash
wp eval 'wp_clean_themes_cache(); wp_cache_flush();'
```
No WP-CLI on the host? Deactivate + reactivate the theme instead.
`THREEDUCATION_VERSION` (in `style.css` + `functions.php`, currently `0.15.1`)
busts the browser CSS cache but **not** the pattern cache — bump both in lockstep on
every release and still flush.

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

---

## 4. Before launch — code TODOs still in the theme

**Forms**
- [ ] **Wire up the three intake forms** — `contact-form.php`, `service-intake.php`,
      `workshops-intake.php` use `action="#"` and submit nowhere. Connect to a form
      plugin (Contact Form 7 / WPForms) or a POST-to-email handler. Each carries a
      visible `[Placeholder]` note reminding you to do so — drop the note once wired.

**Placeholder copy** (search the codebase for `[Placeholder]`)
- [x] **`workshops-audiences.php`** — audience-card body + bullets finalised (v0.15.0).
- [x] Intro copy in `workshops-intake.php`, `about.php`, and `about-team.php` finalised;
      `edu-packages.php` "x leerkrachten" filler resolved.
- [ ] **Team names** — `about-team.php` tile 1 is "Patrick Smet"; tiles 2 & 3 still hold
      `[Naam 2]` / `[Naam 3]` placeholders — fill the two remaining names (+ real photos).
- [ ] The only `[Placeholder]` markers left are the three form dev-notes
      ("Koppel dit formulier…"); they disappear once the forms below are wired.

**Photos** — some pillar heroes now ship real photos; others still show a
"Foto volgt" tile (`.social-proof-placeholder`). Swap the remaining tiles for real images:
- [ ] Heroes **with** real photos already: `/oplossingen`, `/workshops`,
      `/educatieve-pakketten`.
- [ ] Still "Foto volgt": `about-hero.php` (`/over-ons`), `service-hero.php`
      (`/service`), and the homepage `audience-split.php` tile.
- [ ] **About page team tiles** (`about-team.php`) — replace the placeholder tiles
      with real team photos + names/roles.
- [ ] **About page gallery tiles** (`about.php` social-proof) — real workshop/classroom photos.

---

## 5. Env parity — do NOT

- Hard-code product-attribute IDs (they differ per environment; the theme resolves
  them at render time on purpose).
- Copy the local wp-env database to the server — enter content on the target site.
- Upload `.wp-env.json` — it's local-only and will confuse a managed host.
