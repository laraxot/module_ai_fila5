---
title: Website signal contract
module: AI
issue: 32
updated: 2026-11-27
tags: [bmad, ecc, prospecting]
---

# Website signals contract

## Entry points

| Method | Network | Use |
|--------|---------|-----|
| `DetectWebsiteSignalsAction::execute($url)` | **Disabled** (fail-closed) | Until SSRF-safe adapter |
| `analyzeHtml($html, $seconds, $type, $context)` | None | Unit tests, host-provided HTML |
| `analyzeHtmlResult(...)` | None | Full `WebsiteAuditResultData` |

Host apps must fetch only public URLs with their own allow-list adapter, then call `analyzeHtml`.

## Polarity

| polarity | Rank score | Meaning |
|----------|------------|---------|
| `gap` | weight > 0 | Opportunity hint on **examined** page |
| `strength` | 0 | Capability marker observed |
| measured gaps (`slow`, `no-https-hint`) | weight | From timing or page URL scheme |

**Never** treat a missing regex as proof the business lacks the capability elsewhere.

## Categories

mobile, seo, performance, security, trust, conversion, commerce, hospitality, stack, presence, contact, maintenance, a11y, reachability, strength

## Default gap keys

See `WebsiteSignalCatalog::defaults()`. Override weights:

```php
// config/ai.php or host publish
'prospect' => [
  'signals' => [
    'weights' => [
      'no-order-online' => 5,
      'not-mobile' => 4,
      'no-chat' => 0, // disable
    ],
  ],
],
```

## Hospitality ICP (`businessType` contains restaurant/cafe/...)

Extra gaps: `no-menu`, `no-hours`, `no-order-online`.

## Commerce ICP

Extra gap: `no-commerce` when checkout markers absent.

## Evidence rules

- Prefer short evidence strings (`year=2019`, `count=3`, JSON-LD types).
- confidence `hint` for HTML regex; `measured` for seconds/scheme; `error` for fail-closed fetch.

## Ranking

Sum **gap** scores only (strengths are score 0). Higher = work the lead sooner.
