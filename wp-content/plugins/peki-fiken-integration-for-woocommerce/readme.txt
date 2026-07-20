=== Peki – Fiken Integration for WooCommerce ===
Contributors: peki
Tags: woocommerce, fiken, accounting, bookkeeping, invoices
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.22
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automate your bookkeeping by connecting WooCommerce to Fiken. Export orders automatically and save time on manual accounting tasks.

== Description ==

Peki – Fiken Integration for WooCommerce automatically exports your WooCommerce orders to the Norwegian accounting platform Fiken. When orders are completed, the plugin creates vouchers in Fiken, reducing manual work and potential errors.

Visit [peki.no/integration/fiken](https://peki.no/integration/fiken) for more information.

= Key Features =

* Automatic order export to Fiken when orders are completed
* Advanced bank account mapping – developed with senior accountant
* Automatic customer creation in Fiken
* Separate shipping line items with proper VAT handling
* VAT export for goods and services outside Norway
* Dynamic VAT rates based on store location
* Multi-currency support
* Refund handling with credit notes
* Multi-site support with shared quota per Fiken company
* GDPR compliant – only necessary data is transmitted
* Norwegian translation included

= Requirements =

Before using this plugin, you need:

1. An active Fiken account with API access enabled (NOK 99/month add-on)
2. At least one invoice issued in Fiken to initialize counters
3. WooCommerce installed and activated

= Free Plan =

The plugin includes 15 free transfers per month. Multiple WordPress sites connected to the same Fiken company share the monthly quota.

= Pricing =

Most affordable Fiken integration on the market. Upgrade plans available for higher transfer limits:
* Starter: 100 transfers/month (NOK 119 ex. VAT)
* Growth: 1,000 transfers/month (NOK 319 ex. VAT)
* Pro: 5,000 transfers/month (NOK 639 ex. VAT)

== Plan Benefits ==

The plugin includes a free tier and three paid plans. Here’s what you get with each:

* Free:
  * 15 transfers/month
  * Core Woo → Fiken export
  * Community-level support
* Starter:
  * Everything in Free
  * 100 transfers/month
  * Email support
* Growth:
  * Everything in Starter
  * Automatically save invoice PDFs to the Media Library
  * Per-payment document type overrides (Invoice vs CashSale)
  * 1,000 transfers/month
* Pro:
  * Everything in Growth
  * Highest monthly transfer limit (5,000 transfers/month)

Note: This is an independent third-party plugin. We are not affiliated with Fiken AS or WooCommerce/Automattic.

== Installation ==

1. Install the plugin through WordPress or upload to `/wp-content/plugins/`
2. Activate the plugin
3. Enable Fiken API in your Fiken account (Foretak → Tillegstjenester → API)
4. Create your first invoice in Fiken if you haven't already
5. Navigate to Peki → Fiken in WordPress admin and connect your account
6. Configure bank account mapping for your payment methods

== Frequently Asked Questions ==

= Do I need a Fiken account? =
Yes, an active Fiken account with API access is required (NOK 99/month add-on).

= Can I connect multiple stores? =
Yes, unlimited WordPress sites can connect to the same Fiken company and share the monthly transfer quota.

= Is the plugin compatible with HPOS? =
Yes, the plugin is fully compatible with WooCommerce High-Performance Order Storage.

= What data is sent to external services? =
The plugin sends order data to peki.no (export service) and fiken.no (accounting platform) to create vouchers. Only necessary order and customer information is transmitted. See Terms and Privacy policies on peki.no and fiken.no.

= Is my data secure? =
Yes, all data is transmitted over secure HTTPS connections.

== External Services ==

This plugin connects to external services to function:

* Peki export service (peki.no) - Processes and forwards order data to Fiken API
* Fiken API (fiken.no) - Creates accounting vouchers from order data

By using this plugin, you agree to data processing on these external servers. Review their respective privacy policies for details.

== Screenshots ==

1. Connection – Demonstrates the simple and streamlined process of establishing a secure connection to Fiken.
2. Bank Account Mapping – Displays the mapping between WooCommerce payment methods and registered bank accounts in Fiken.
3. Order Export Status – Indicates whether each WooCommerce order has been successfully exported to Fiken.

== Changelog ==

= 1.0.22 =
* Fix: VAT mapping for Stripe Tax and other third-party tax providers now correctly detects 25% Norwegian VAT. Previously, orders with correct VAT calculated by Stripe Tax were incorrectly exported to Fiken as "Sale without VAT" (VAT code 52). The plugin now calculates the effective VAT rate from actual amounts when tax rate IDs are not available, ensuring correct mapping to Fiken's HIGH VAT type (25%) for Norwegian domestic sales.

= 1.0.21 =
* Fix: Increase export service connect timeout to reduce cURL error 28 failures on slow networks.

= 1.0.20 =
* Logging: Export errors now automatically add an order note so merchants see failures without opening logs.
* UI: Added a Logs tab under WooCommerce → Fiken that shows the latest error entries from the WooCommerce logger (peki-fiken log).
* Compatibility: Confirmed with WordPress 6.9.

= 1.0.19 =
* Maintenance: Version bump and documentation sync.

= 1.0.18 =
* Maintenance: Version bump and documentation sync.

= 1.0.17 =
* UI: Advanced page grouped into “Export rules” and “Growth features”, with larger, easier-to-click checkboxes and clear descriptions.
* UI: GROWTH badge with hover tooltip on gated settings; portal link shown when not eligible.
* Enforcement: Per-payment document type overrides are Growth-gated in both UI and saving logic (server continues to enforce PDF feature).
* Multi-site: Added `company_slug` to portal/checkout links so upgrades apply across all sites tied to the same Fiken company.
* Upgrade flow: If a subscription exists, “Choose plan” routes to the portal to upgrade instead of creating a second subscription.
* CSS: Added styles for growth badge and larger checkboxes.
* Change: All plugin endpoints now use Fiken-specific paths (`/stripe-connect/fiken/...`) for status and portal.
* Added: New Fiken status endpoint (`/stripe-connect/fiken/status.php`) mirroring root behavior.
* Fix: Fiken customer portal include path corrected to load local `env.php`.
* Fix: Growth gating now reads plan via the Fiken GET status endpoint using the current shop URL (immediate unlock after upgrade).
* UI/Docs: Plan Benefits section and plan card bullets added; removed “Priority support” mention from Pro.

= 1.0.16 =
* Enforcement: Automatic invoice PDF saving is Growth-only and gated server-side; local code edits cannot bypass the gate.
* UI: Advanced toggle for “Automatically save invoice PDFs to Media Library” is disabled unless current plan is Growth (based on cached server status).
* UI: Order notes now show a “Download PDF” button for saved attachments.
* Dev: Added binary download helpers and 401 refresh to legacy client; PDF endpoint added with plan check.
* Fix: Customer portal link reliability improved (includes `shop`, `shop_url`, `v=3`, `connection_id`).
* Fix: Partial credit notes refunded amounts match requested gross by scaling line quantities; fallback derives net from gross using VAT type.

= 1.0.15 =
* Fix: Prevent duplicate customers in Fiken by reusing existing contact by email when available (server-side).
* Fix: Amount scaling guard — clamp minor unit factor to 100 (NOK) to avoid 1/100 totals if decimals are misreported.
* Fix: Advanced “Document Type per Payment Method” no longer resets when saving Bank Account Mapping; bacs override persists.
* Change: Default bank account fallback aligned with UI to 1920:10001 (Driftskonto). Previously defaulted to 1960.
* Dev/Compat: Advanced saves keep existing per-gateway overrides when the cash map isn’t posted.
* Added: Optional automatic invoice PDF download to Media Library with order attachment and “Download PDF” button in order notes. Requires Growth subscription; enforced server-side.
* Added: Advanced setting toggle for invoice PDF saving (disabled unless on Growth based on server status cache).
* Fix: Customer portal button reliability — includes `shop`, `shop_url`, `v=3` and `connection_id` to ensure correct account routing.
* Fix: Partial credit notes now match requested refund gross by proportionally scaling line quantity; fallback derives net from gross by VAT type (prevents over-crediting full line).
* Dev: Backend endpoint to fetch invoice PDF and binary client helpers; token refresh on 401 for binary requests too.

= 1.0.14 =
* Fix: Per-payment “Document Type” override is now always honored (bypasses master toggle). bacs set to Invoice now forces `cash=false`.  
* Dev: Added filter `pekifiken_cash_flag` to allow programmatic override of the cash flag.  
* UI: Support tab simplified to direct email link (petter@peki.no), web form removed.  
* Docs: Added Screenshots section; updated links to `peki.no/fiken`.  
* Compliance: Additional translators comments/escaping for PHPCS.

= 1.0.13 =
* Added: Per-payment “Document Type” control (Invoice vs CashSale) with Advanced-tab master toggle.
* Added: Live enable/disable of per-gateway dropdowns when master toggle changes.
* Added: Orders list column shows Fiken export status with Woo dashicons (HPOS + legacy).
* Added: Admin “Order actions” entry to force export to Fiken (overrides duplicate guard).
* Improved: Prevent auto-export on status change if already exported (idempotency).
* Improved: Amount scaling respects WooCommerce price decimals; server normalizes to øre.
* Fix: Server accepts bankAccountCode also for non-cash invoices to satisfy Fiken API.
* Compliance: PHPCS fixes (translators comments, placeholder ordering, escaping, prefixed globals).

= 1.0.12 =
* Added: Order notes now show export status (success/error) with invoice IDs
* Added: Norwegian (Bokmål) translation
* Improved: Notifications now fetch quota from Peki server automatically
* Improved: Dismiss notifications reset when usage values change
* Fix: Removed unsupported API parameters causing 400 errors

= 1.0.11 =
* Fix: Bank account mapping functionality improved
* Fix: Admin notifications dismiss handling

= 1.0.10 =
* Fix: Free-plan “limit reached (15)” notice now triggers only when remaining == 0 (or used >= limit if remaining is unknown). Prevents premature hard-stop banners.
* Tweak: Soft warning shows at ~10/15 only when usage can be derived (used or remaining known).
* Hardening: Safer parsing of quota options (treat empty/false as unset) to avoid misreads.
* UX: Dismiss keys are period-scoped; notices respect monthly reset.
* Internals: Prefer fresh server quota; fall back to cached status only when necessary.

= 1.0.9 =
* Added: Support for VAT code 52.
* Improved: Stability of admin notices (dismiss handling and consistent display).

= 1.0.9 =
* Added: Support for VAT code 52.
* Improved: Stability of admin notices (dismiss handling and consistent display).

= 1.0.8 =
* Fix: Fixing Refund bugs.
* Fix: Account mapping.

= 1.0.7 =
* Improvement: Refund handling refined for better accuracy and compatibility.
* Feature: Added setting to disable VAT (MVA) when needed.

= 1.0.6 =
* Fix: PHPCS warnings resolved for OAuth callback handling and webhook signature validation.
* Improvement: Enhanced installation instructions with mandatory Fiken API activation and first invoice creation.
* Docs: Clarified that Fiken invoice counter and customer counter must be initialized before API integration works.
* Security: Proper sanitization and unslashing of server variables in webhook endpoint.

= 1.0.5 =
* Fix: Duplicate “Connect to Fiken” notice; render guarded to run once per request.
* Improvement: Quota notices now read from cached server status (shared per `company_slug`), with webhook/cron refresh.
* Docs: Added Fiken API add-on requirement and first-invoice note; clarified multi-site behavior and pricing plans.
* Meta: Bumped “Tested up to” to 6.8; updated Stable tag.

= 1.0.4 =
* Docs: English-only readme per WordPress.org requirements; improved SEO wording for WooCommerce/Fiken integration in Norway.  
* Product: Clarified free tier (**15 transfers/month**) and option to upgrade to unlimited.  
* Docs: Removed references to manual “Send to Fiken” actions and subscription support (not available).  
* Link: Updated to https://peki.no/fiken in the description.

= 1.0.3 =
* Compliance: Neutralized upgrade/plan wording; “Manage Subscription” link only.  
* Security: Reviewed nonce and capability checks in connect/disconnect flows.  
* Prefix: Migrated internal keys to `pekifiken_` with legacy fallbacks.  
* API: Plan/limits decided by remote service; plugin surfaces neutral errors.  
* Docs: Expanded “External services.”

= 1.0.2 =
* Documentation updates and refund handling improvements.

= 1.0.1 =
* Fix: Dismissible admin notices; clarified docs.

= 1.0.0 =
* First public release.

== Upgrade Notice ==

= 1.0.12 =
Enhanced order tracking with export status in order notes. Norwegian translation included. Improved notification system with automatic server sync.

