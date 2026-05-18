# WP Odds Comparison — Technical Documentation

## 1. Purpose

This plugin compares live betting odds from multiple bookmakers on WordPress posts and pages. Data is sourced from **The Odds API** through a pluggable provider layer, cached with transients, and presented via a dynamic Gutenberg block and admin settings.

## 2. Code structure

| Path | Responsibility |
|------|----------------|
| `wp-odds-comparison.php` | Constants, autoloader, hooks, `wpoc()` helper |
| `includes/Core/Plugin.php` | Singleton bootstrap; wires admin, REST, block, assets |
| `includes/Core/Settings.php` | `wp_options` storage (`wpoc_settings`) |
| `includes/Core/Cache.php` | Transient cache with `remember()` helper |
| `includes/API/OddsProviderInterface.php` | Strategy contract for data sources |
| `includes/API/TheOddsApiProvider.php` | The Odds API v4 client |
| `includes/API/OddsFetcherFactory.php` | Factory for provider instances |
| `includes/API/OddsService.php` | Cached fetch + normalization |
| `includes/Odds/OddsConverter.php` | Decimal ↔ fractional ↔ American |
| `includes/Bookmakers/BookmakerRegistry.php` | Labels and outbound links |
| `includes/Admin/` | Settings page under **Settings → Odds Comparison** |
| `includes/Blocks/OddsComparisonBlock.php` | Block registration + SSR callback |
| `includes/REST/OddsController.php` | Public REST routes |
| `includes/Frontend/Renderer.php` | HTML table output |
| `block.json` | Block metadata (WP 6+) |
| `assets/` | CSS/JS for admin, block editor, frontend |

## 3. Design patterns

### Singleton

- `WPOddsComparison\Core\Plugin` — single plugin instance via `Plugin::instance()`.
- `WPOddsComparison\Core\Cache` — optional shared cache via `Cache::instance()`.

### Factory

- `OddsFetcherFactory::create( $provider_id )` instantiates providers from a filterable map.

### Strategy

- `OddsProviderInterface` allows swapping The Odds API for mocks, scrapers, or other feeds without changing `OddsService`.

### Observer (WordPress hooks)

- `wpoc_loaded` — after boot
- `wpoc_api_response` — after each API response
- `wpoc_normalized_events` — before output
- `wpoc_odds_providers` — register providers
- `wpoc_bookmaker_catalog` — extend bookmaker list
- `wpoc_default_bookmaker_link` — default URLs

Settings save triggers `Cache::flush_all()` (reactive invalidation).

## 4. Data flow

```
Block / REST request
    → OddsService::get_comparison()
        → Cache::remember()
            → OddsFetcherFactory → TheOddsApiProvider::get_odds()
        → normalize_events() + OddsConverter::format()
    → Renderer (SSR) or JSON (REST)
```

## 5. Odds conversion

`OddsConverter` stores internal values as **decimal** (European). Display formats:

| Format | Example | Conversion |
|--------|---------|------------|
| Decimal | 2.50 | Base |
| Fractional | 3/2 | profit = decimal − 1, reduced fraction |
| American | +150 | +((d−1)×100) if d≥2; −(100/(d−1)) if d<2 |

REST: `GET /wp-json/wpoc/v1/convert?value=3/2&from=fractional&to=american`

## 6. Caching and rate limits

- Responses cached with `set_transient()`; key = MD5 of sport + markets + bookmakers.
- Default TTL: 300s (configurable, minimum 60s).
- HTTP 429 from API throws a clear error; increase TTL to reduce calls.
- Sports list cached for at least 3600s.

## 7. Admin usage

1. **Settings → Odds Comparison**
2. Add API key.
3. Check bookmakers to enable site-wide.
4. Set affiliate URLs per bookmaker.
5. Choose default markets and odds format.

## 8. Gutenberg block attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `sport` | string | The Odds API sport key |
| `markets` | array | e.g. `["h2h"]` |
| `bookmakers` | array | Keys to compare in this block |
| `oddsFormat` | string | `decimal`, `fractional`, `american` |
| `title` | string | Optional heading |
| `maxEvents` | number | Limit events shown (1–20) |

Dynamic block: `save` returns `null`; PHP `render_callback` outputs HTML.

## 9. Extending the plugin

### Add a new odds provider

```php
add_filter( 'wpoc_odds_providers', function ( $map, $settings ) {
    $map['my_source'] = \MyVendor\MyOddsProvider::class;
    return $map;
}, 10, 2 );
```

Implement `OddsProviderInterface` with `get_sports()` and `get_odds()`.

### Add bookmakers

```php
add_filter( 'wpoc_bookmaker_catalog', function ( $catalog ) {
    $catalog['my_book'] = 'My Bookmaker';
    return $catalog;
} );
```

### Customize normalized output

```php
add_filter( 'wpoc_normalized_events', function ( $events ) {
    // Modify or filter events
    return $events;
} );
```

## 10. Security

- Admin settings require `manage_options`.
- Inputs sanitized on save (`sanitize_key`, `esc_url_raw`, etc.).
- Outbound links use `rel="nofollow sponsored noopener"`.
- API key stored in options; use environment-specific keys in production.

## 11. Performance checklist

- Transient caching on all odds fetches.
- `has_block()` guard before enqueuing frontend JS/CSS.
- Single API request per cache key per TTL.
- Server-side rendering avoids empty client shells for SEO.

## 12. Testing locally

1. Use a staging WordPress with the plugin activated.
2. Configure a valid API key.
3. Insert block with `sport=soccer_epl` and 2–3 bookmakers.
4. Verify **Settings** save clears cache and updates links.
5. Call `GET /wp-json/wpoc/v1/odds?sport=soccer_epl&bookmakers=bet365,pinnacle`.

## 13. Coding standards

- PHP: PSR-4 namespaces, `declare(strict_types=1)`, WordPress Coding Standards–friendly escaping.
- JavaScript: WordPress `@wordpress/*` packages in block editor.
- Run `composer lint` if PHPCS is installed with PSR-12 ruleset.
