# WP Odds Comparison

Advanced WordPress plugin that fetches live odds from multiple bookmakers via [The Odds API](https://the-odds-api.com/), displays them in a customizable Gutenberg block, and provides a full admin settings panel.

## Features

- **Live odds** from ~15 bookmakers (extensible catalog)
- **Admin dashboard** — API key, cache TTL, enabled bookmakers, markets, affiliate links
- **Gutenberg block** — per-block sport, markets, bookmaker selection, odds format
- **Odds conversion** — decimal, fractional, and American formats
- **Performance** — WordPress transients caching, conditional asset loading
- **OOP architecture** — PSR-4 autoloading, interfaces, Factory and Singleton patterns

## Requirements

- WordPress 6.0+
- PHP 7.4+
- [The Odds API](https://the-odds-api.com/) account (free tier available)

## Installation

1. Clone or copy this folder into `wp-content/plugins/wp-odds-comparison/`.
2. Activate **WP Odds Comparison** in **Plugins**.
3. Go to **Settings → Odds Comparison**.
4. Enter your API key, select bookmakers and markets, and save.
5. Add the **Odds Comparison** block to any post or page.

### Development build (optional)

```bash
cd wp-odds-comparison
npm install
npm run build
```

The plugin works without a build step using `assets/js/block/index.js` directly.

## Configuration

| Setting | Description |
|--------|-------------|
| API Key | Your key from The Odds API |
| Cache TTL | Seconds to cache responses (default 300) |
| Default Sport | Sport key, e.g. `soccer_epl`, `basketball_nba` |
| Enabled Bookmakers | Site-wide defaults for the block |
| Markets | `h2h`, `spreads`, `totals` |
| Bookmaker URLs | Outbound/affiliate links per bookmaker |

## Gutenberg block

1. Insert **Odds Comparison** from the block inserter.
2. In the sidebar, choose sport key, markets, odds format, and bookmakers.
3. Bookmakers can be added or removed per block without changing global settings.
4. The block uses server-side rendering for SEO; the editor shows a live REST preview.

## REST API

Namespace: `wp-json/wpoc/v1`

| Endpoint | Parameters |
|----------|------------|
| `GET /odds` | `sport`, `markets`, `bookmakers`, `odds_format` |
| `GET /bookmakers` | — |
| `GET /convert` | `value`, `from`, `to` |

## Architecture overview

```
wp-odds-comparison.php          # Bootstrap
includes/
  Core/                         # Plugin, Settings, Cache (Singleton)
  API/                          # Provider interface, The Odds API, Factory, Service
  Bookmakers/                   # Registry
  Odds/                         # OddsConverter
  Admin/                        # Settings UI
  Blocks/                       # Gutenberg registration
  REST/                         # REST controller
  Frontend/                     # Renderer, assets
```

See [DOCUMENTATION.md](DOCUMENTATION.md) for extension hooks and design patterns.

## Why The Odds API instead of scraping?

The assignment references odds comparison sources. This implementation uses The Odds API’s official REST service because it is:

- **Reliable** — structured JSON, documented rate limits
- **Compliant** — avoids ToS issues with scraping third-party sites
- **Extensible** — new providers implement `OddsProviderInterface`

A scraper adapter can be added via the `wpoc_odds_providers` filter.

## License

GPL-2.0-or-later
