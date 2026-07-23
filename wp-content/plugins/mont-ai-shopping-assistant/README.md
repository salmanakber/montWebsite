# Mont AI Shopping Assistant

Production-ready AI shopping concierge for the existing Montenapoleone WordPress + WooCommerce site.

**Plugin slug:** `mont-ai-shopping-assistant`  
**Does not redesign the storefront** — adds a floating widget and server-side AI tools that reuse your WooCommerce cart and Mont custom options (Passform, size, collar, cuff, measurements).

---

## Quick start

1. Activate **Mont AI Shopping Assistant** in WP Admin → Plugins.
2. Open **WooCommerce → AI Assistant**.
3. Add **Groq API Key** (primary) and **Gemini API Key** (fallback).
4. Click **Rebuild index now**.
5. Visit the storefront — a chat bubble appears bottom-right.

---

## Architecture

```
mont-ai-shopping-assistant/
├── mont-ai-shopping-assistant.php   # Bootstrap
├── includes/
│   ├── class-autoloader.php
│   ├── class-activator.php
│   ├── class-plugin.php
│   ├── Admin/                       # Settings UI
│   ├── API/                         # REST routes
│   ├── Providers/                   # Groq + Gemini + Manager
│   ├── Cart/                        # Add/update/remove cart
│   ├── Product/                     # Knowledge, custom options, index
│   ├── Language/                    # EN / IT / NB / VI
│   ├── Services/                    # Chat loop, tools, logger, cache
│   └── Assets/                      # Lazy front-end enqueue
├── templates/chat-widget.php
└── assets/{css,js}/chat-widget.*
```

Namespace: `Mont_AI_Assistant\`

Classes are modular and auto-loaded from `includes/{Subdir}/class-{name}.php`.

---

## AI providers & fallback

### How it works

`Providers\Provider_Manager` owns all provider switching:

1. Every request tries the **primary** provider (default: Groq).
2. On timeout, HTTP 429/5xx, or any thrown error → retry **fallback** (default: Gemini).
3. The customer never sees which model answered.
4. When logging is enabled, each answer is logged with `provider` and whether fallback was used.

### Classes

| Class | Role |
|-------|------|
| `Provider_Interface` | Contract: `chat()`, `is_configured()`, `get_id()` |
| `Groq_Provider` | OpenAI-compatible Groq Chat Completions + tools |
| `Gemini_Provider` | Gemini `generateContent` + function calling, normalized to OpenAI-style `tool_calls` |
| `Provider_Manager` | Ordered try/fallback + `mont_ai_register_providers` hook |

### Adding another provider later

```php
add_action( 'mont_ai_register_providers', function( $manager ) {
    $manager->register( new \MyPlugin\OpenAI_Provider() );
});
```

Implement `Provider_Interface`, return the same response shape:

```php
[
  'content'    => string,
  'tool_calls' => array, // OpenAI format
  'raw'        => mixed,
  'provider'   => 'openai',
]
```

Then set primary/fallback in admin (or filter settings).

---

## Language switching

- Selector in the chat header: English, Italian, Norwegian (`nb`), Vietnamese.
- Choice stored in `localStorage` (`mont_ai_lang`); history in `sessionStorage`.
- Changing language does **not** reload the page.
- `Language_Manager::prompt_instruction()` injects a hard instruction so replies are generated **in the selected language**.
- Product titles stay original unless WPML/ACF translations exist on the product itself.

---

## WooCommerce integration

### Product knowledge

`Product\Product_Knowledge` builds a rich payload:

- Type, name, SKU, prices (regular/sale), stock, categories, tags  
- Attributes & variations  
- Images / gallery  
- Relevant meta (`_fabric_color`, `_dc_multicurrency_prices`, `_b2b_product`, `_product_video`, …)  
- ACF fields when ACF is present  
- Mont **custom options schema** via `Product\Custom_Options`

### Custom options detection

`Custom_Options` mirrors the live product page:

| Option | Source | Required |
|--------|--------|----------|
| `body_fit` (Passform) | `pa_body-fit` | Yes |
| `size` (Størrelse) | `pa_size` | Yes |
| `collar_type` | ACF `choose_collar_update` (options page) | Yes (unless category hides collar/cuff) |
| `cuff_type` | ACF `choose_cuff_update` | Yes (same) |
| `custom_measurements` | Theme measurement form | Optional |
| `quantity` | — | Yes |

Category flags `cup_and_collar` / `customer_tailoring_` hide collar/cuff or measurements, same as the theme.

Filter: `mont_ai_custom_options`.

### Cart

`Cart\Cart_Service` adds items with the same `custom_data` keys the theme uses:

- `Passform`, `Størrelse`, `Snipp (Collar)`, `Mansjetter (Cuff)`  
- Norwegian measurement labels (`Skjortelengde`, …)  
- `unique_key` for line uniqueness  
- Resolves WC variation from body fit + size when possible  
- Applies `custom_price` surcharge when provided  

Filter: `mont_ai_cart_item_data`.

Mini-cart refresh: front-end triggers `wc_fragment_refresh` / `added_to_cart` and the theme’s `update_cart_count` AJAX when available.

---

## Tools (function calling)

Defined & executed by `Services\Tool_Executor`:

| Tool | Purpose |
|------|---------|
| `search_products` | Index search → results + product cards |
| `get_product` | Full knowledge payload |
| `get_variations` | Variation list |
| `get_custom_options` | Required/optional schema |
| `validate_selection` | Block incomplete orders |
| `add_to_cart` | Only after validation |
| `get_cart` | Cart summary |
| `update_cart_item` | Change qty |
| `remove_cart_item` | Remove line |

`Chat_Service` runs up to 6 tool rounds, then returns the final assistant message + cards.

---

## Product index

- Table: `{prefix}mont_ai_product_index`  
- Columns: `product_id`, `sku`, `title`, `search_blob`, `payload` (JSON), `updated_at`  
- Updated on product save; daily cron `mont_ai_rebuild_index`  
- Admin button rebuilds all  
- Optional **Allowed categories** limit  

Search uses sanitized LIKE across title / SKU / blob (portable across MySQL versions).

---

## REST API

Base: `/wp-json/mont-ai/v1/`

| Route | Method | Auth |
|-------|--------|------|
| `/config` | GET | Public |
| `/chat` | POST | `X-WP-Nonce` (`wp_rest`) |
| `/cart` | GET | Nonce |

**Chat body**

```json
{
  "message": "I need a blue linen shirt",
  "language": "en",
  "history": [{ "role": "user", "content": "…" }],
  "context": { "product_id": 123 }
}
```

API keys never leave the server.

---

## Security

- Nonce on REST mutating routes  
- Capability checks on admin (`manage_woocommerce`)  
- Sanitize / escape on save & output  
- Server-side provider calls only (`wp_remote_post`)  
- Keys stored in `mont_ai_settings` option (not exposed to JS)

---

## Admin settings

**WooCommerce → AI Assistant**

- Groq / Gemini keys & models  
- Primary / fallback provider  
- Temperature, max tokens  
- System prompt add-on  
- Welcome message, theme color (`#1b3359` default matches site navy)  
- Languages + default  
- Allowed categories  
- Logging & debug mode  
- Rebuild index + recent logs  

---

## Front-end widget

- Floating bubble → expandable panel  
- Typing indicator, timestamps, session history  
- Product cards with image / price / link  
- Mobile full-screen panel  
- Accent via `--mont-ai-accent`  
- Hide with: `add_filter( 'mont_ai_show_widget', '__return_false' );`

---

## Order-building behaviour (prompt)

The system prompt instructs the model to:

1. Discover need → search  
2. Recommend  
3. `get_custom_options`  
4. Ask **one required option at a time**  
5. Offer optional measurements  
6. `validate_selection` → `add_to_cart`  
7. Confirm + offer checkout  

It must not invent stock/prices and must not mention providers or tools to the customer.

---

## Requirements

- WordPress 5.8+  
- PHP 7.4+  
- WooCommerce 5+  
- Outbound HTTPS to Groq & Google Generative Language APIs  

Optional: ACF (collar/cuff options), Mont theme custom cart display hooks.
