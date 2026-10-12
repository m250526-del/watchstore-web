# WatchStore Web

Custom WordPress / WooCommerce code for **Eon Store**, an online watch store for Pakistan (COD plus advance payment, with WhatsApp order verification).

This repo holds only the code written for the store. WordPress core, WooCommerce, the Storefront parent theme, uploads and the database are not included.

> **This repo is public.** Never commit `wp-config.php`, API keys, passwords or bank details. All secrets are entered in the WordPress admin and stored in the database, not in these files.

## Related repo

The WhatsApp bot that this plugin talks to lives in a separate repo:
[`watchstore-whatsaap`](https://github.com/m250526-del/watchstore-whatsaap) (Node.js + Baileys, deployed on Render, session stored in Postgres).

## Repository layout

This repo is rooted at `wp-content`:

```
wp-content/
├── themes/
│   └── watchstore-theme/              Storefront child theme
│       ├── functions.php
│       ├── style.css
│       └── woocommerce/checkout/form-checkout.php
├── plugins/
│   └── watchstore-payments/           Main custom plugin
│       ├── watchstore-payments.php
│       ├── homepage-content-settings.php
│       ├── homepage-style-settings.php
│       ├── footer-links-settings.php
│       └── product-colors-meta.php
└── mu-plugins/
    └── dynamic-siteurl.php            Local development helper
```

A whitelist `.gitignore` ignores everything except the paths above.

## What each part does

### Child theme: `themes/watchstore-theme`
- `functions.php`: checkout field changes, country defaults, mobile top bar, page heading rendering.
- `style.css`: styling for checkout cards, the responsive mobile top bar, collapsible order totals and page headings.
- `woocommerce/checkout/form-checkout.php`: custom checkout page template override.

### Plugin: `plugins/watchstore-payments`
- `watchstore-payments.php`
  - Advance Payment gateway (id `raast_transfer`): bank transfer, EasyPaisa, JazzCash and Raast details.
  - Cash on Delivery orders start as `on-hold` so WhatsApp verification can finish first.
  - Checkout phone field with a country-code dropdown (PK, UK, US, UAE, KSA, Qatar, Oman, Australia). The selected code is stored in the order meta `_billing_phone_country_code`.
  - One-click copy buttons for account numbers, IBAN and Order ID.
  - Order-received page: verification popup, live status polling, resend button, and a keep-warm ping to the WhatsApp server.
- `homepage-content-settings.php`: admin settings and rendering for the homepage news ticker and responsive banner (images, direct video, YouTube embeds).
- `homepage-style-settings.php`: admin settings and CSS output for the homepage background gradient.
- `footer-links-settings.php`: admin settings and rendering for social and contact buttons in the footer.
- `product-colors-meta.php`: product meta box for color variants, with a color picker and swatch rendering.

### Must-use plugin: `mu-plugins/dynamic-siteurl.php`
Adjusts URL handling so local port addresses are not forced to HTTPS, while still working through external tunnel links. It is intended for local development.

## Requirements

- WordPress and WooCommerce
- The **Storefront** parent theme (the child theme depends on it)
- The WhatsApp server from the `watchstore-whatsaap` repo, deployed and reachable over HTTPS

## Installation

1. Install WordPress, WooCommerce and the Storefront theme.
2. Copy `themes/watchstore-theme` into `wp-content/themes/` and `plugins/watchstore-payments` into `wp-content/plugins/`. Copy `mu-plugins/dynamic-siteurl.php` only if you need it.
3. In wp-admin, activate the **WatchStore** theme and the **WatchStore Payments** plugin.
4. Go to **WooCommerce → Settings → Payments → Advance Payment** and fill in:
   - Bank name, account title, account number, IBAN
   - EasyPaisa and JazzCash numbers and account names
   - Primary WhatsApp number and Secondary / Fallback WhatsApp number
   - WhatsApp server URL and WhatsApp server API key (the key must match the `API_KEY` set on the server)
5. Set the pages and menus you want (Privacy Policy, Return & Exchange Policy, Terms & Conditions, Contact & Help) and configure the homepage and footer settings added by the plugin.

## Order flow

1. The customer places an order with Advance Payment (or COD). The order is set to `on-hold`.
2. On the order-received page, the plugin asks the WhatsApp server to send a bilingual (English and Urdu) verification message to the customer.
3. The customer confirms by replying `YES` (or the Urdu equivalent) or by sending their Order ID from any WhatsApp number.
4. For advance payment, the bot sends the payment details and asks for a screenshot. It acknowledges the first screenshot once, then stays quiet to avoid spam.
5. The store owner checks the payment manually and sets the order to **Processing** in wp-admin. The order-received page then switches to the verified message.

### Order-received page states
- **Popup:** message sent, number not on WhatsApp (shows the primary business number and a copyable Order ID), or backend unavailable (falls back to the secondary number). It is dismissed per order for the browser session.
- **Persistent section** (always visible on the page, polled every 7 seconds): WhatsApp button, copyable Order ID and a resend button limited to 4 attempts, then "verifying", then the manual-fallback note, then "verified".

## What is not in this repo

- Page content, menus, widgets, and the WordPress and WooCommerce settings
- The values saved by the homepage, footer and product-color settings (they live in the database)
- Uploaded media such as banner images and videos
- Third-party plugins, for example WP Mail SMTP, which are installed separately

To back all of that up, take a full site export from Local, or use a migration plugin when moving to production.

## Security notes

- Keep `wp-config.php`, API keys and real credentials out of this repo.
- If a secret is ever committed, rotate it immediately. Deleting it in a later commit does not remove it from Git history.
