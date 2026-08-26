# Business Map Locator — Release Plan

**Baseline:** `1.3.2-beta29`  
**Last update:** 24 July 2026

This document defines release sequencing and gates. It does not own implementation status; see [ROADMAP](ROADMAP.md). Detailed requirements are maintained in the [Master Specification](../specification/NBH-Business-Locator-Master-Spec-v3.0.md).

## Release sequence

| Release track | Required result |
|---|---|
| Foundation | Runtime, safety, Location and REST contracts, server-side loading |
| Areas Migration | Hierarchical Areas with reversible City migration |
| Free product completion | Directory UX and publication tools |
| Free RC | Quality, localization, security, compatibility and evidence |
| Free 1.0 GA | Production WordPress.org release |
| Pro 1.0 | Availability, Services, Saved Locators, presets and Elementor |
| Joint Free/Pro GA | Compatible production Free + Pro packages |
| 1.0.x stabilization | Four to eight weeks of compatibility fixes |
| WooCommerce Pickup 1.0 | Separate checkout and order-snapshot add-on |

## Gate A — Scope lock

- Free/Pro matrix approved.
- Technical identifiers approved before public GA.
- Four Free layouts and six total Pro presets approved.
- City is legacy; Area is canonical.
- Map provider and Locator configuration are distinct concepts.

## Gate B — Architecture

Current implementation status is maintained only in [ROADMAP](ROADMAP.md). This gate closes only when the requirements below are satisfied.

Gate closes only when:

- clean installation and beta upgrade preserve data;
- Areas migration is reversible;
- one canonical save, search and REST path exists;
- index rebuild works;
- legacy compatibility is isolated and documented.

## Gate C — Free feature complete

Required:

- Directory + Map reference experience;
- compact and detailed Location cards;
- hierarchical Areas;
- true server radius and distance sorting;
- Shortcode Builder;
- full Gutenberg controls;
- CSV compatibility and Area paths;
- EN/DE/UK/RU localization;
- OSM and Google provider parity, including clustering.

## Gate D — Free RC quality

Required evidence:

- automated tests green;
- Plugin Check with no unresolved errors;
- PHPCS and agreed PHPStan level;
- accessibility checklist;
- performance test at 10, 500, 2,000 and 5,000 Locations;
- security and privacy review;
- documentation and translation artifacts;
- source-to-ZIP parity.

## Free 1.0 GA

Release only after:

- clean install and beta upgrade rehearsal;
- current and supported WordPress/PHP environment checks;
- OSM and Google smoke tests;
- multiple Locator instance test;
- mobile and keyboard acceptance;
- signed package hash;
- final owner acceptance.

## Pro 1.0 track

### Services and Availability

- Services taxonomy and filters;
- structured weekly schedules and exceptions;
- timezone-correct `Open now`;
- Smart Ranking.

### Saved Locators and Presets

- Saved Locator entity;
- per-Locator configuration;
- system and custom presets;
- canonical `ResolveLocator` and `RenderLocator` contracts;
- downgrade snapshot.

### Elementor

- native widget selects a Saved Locator;
- renderer parity with shortcode and Gutenberg;
- lazy dependencies;
- responsive height only;
- editor/public lifecycle and multi-instance tests.

## Gate G — Integration readiness

Before joint GA:

- versioned public read contracts;
- `LocationPublicDto` and immutable snapshot allowlist;
- eligible-location and availability validation services;
- documented hooks;
- add-ons do not read private tables directly;
- semantic contract-version detection.

## WooCommerce Pickup track

Starts after at least four weeks of stable Free/Pro `1.0.x` operation.

1. Contract lock.
2. Shipping method and admin settings.
3. Eligibility and Classic Checkout.
4. Checkout Blocks and Store API.
5. HPOS-compatible immutable order snapshot.
6. RC/GA matrix and owner acceptance.

WooCommerce Pickup is a separate ZIP and does not block the main Free/Pro GA.
