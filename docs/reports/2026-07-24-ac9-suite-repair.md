# AC-9 PHPUnit Suite Repair Evidence

**Project:** Business Map Locator  
**Baseline:** `1.3.2-beta29`  
**Scope:** Areas Migration — Phase A code-freeze, AC-9 only  
**Date:** 24 July 2026  
**Outcome:** Repair verified by static, deployment, and REST smoke checks. AC-9 remains pending a native PHPUnit 10.5 run.

## Scope and boundary

This round was limited to restoring test-suite prerequisites and correcting failures identified for AC-9. No Areas Phase B work was performed. No Area terms or relationships were created or migrated, and no Search, repository, CSV, marker metadata, frontend, cache, index, or migration semantics changed.

The supplied archive `business-map-locator-ac9-fixed.zip` was checked before import. Its SHA-256 was:

```text
30a6cb3cb5ce67a4bd0e9a80fa28f5eee0323fba84a488a166ca2d790c46574a
```

The current baseline already contained `tests/`; it was preserved rather than replaced wholesale, so the existing Phase A and capability-upgrade coverage remains intact.

## Changes applied

| File | Change | Reason |
|---|---|---|
| `business-map-locator/src/Rest/LocationsController.php` | Detail route ID pattern changed from an arbitrary non-slash segment to `[1-9][0-9]*`. | Reject non-numeric detail identifiers. |
| `business-map-locator/tests/ImportWorkspaceUiTest.php` | Replaced a user-facing text assertion with the stable `data-transfer-panel="history"` hook. | Avoid failure caused by obsolete interface wording. |
| `business-map-locator/tests/LocationContractTest.php` | Escaped `$id` in a test string. | Preserve the intended literal code fragment in the fixture. |

## Verification

- Verified backup created before implementation: `.codex/backups/2026-07-24_13-13-16_ac9-suite-repair`.
- Source and local WordPress stand were synchronized successfully.
- Source/stand parity: **PASS** — `.codex/reports/2026-07-24_13-15-24-source-stand-manifest.json`.
- PHP syntax: **PASS** for all 151 PHP files.
- The earlier 150-file figure referred to the supplied AC-9 archive. The current baseline has one additional pre-existing Phase A regression file, `tests/AreaCapabilitiesUpgradeTest.php`, which was added for AC-13 capability-upgrade coverage and was not imported or changed in this AC-9 round.
- Postflight checks: plugin bootstrap, version parity, lint, conflict-marker, debug-statement, absolute-path, secret, and CDN checks: **PASS**.
- HTTP smoke:
  - stand home page: **HTTP 200**;
  - REST index: **HTTP 200**;
  - Locations collection: **HTTP 200**;
  - `/wp-json/business-map/v1/locations/foo`: **HTTP 404**;
  - `/wp-json/business-map/v1/locations/20155`: **HTTP 200**, response ID `20155`.
- The automated postflight root-page probe timed out once, but an immediate direct repeat completed with **HTTP 200** in 1.5 seconds. This is recorded as an environmental transient, not a product failure.

## PHPUnit status

The supplied evidence reports 92 tests, zero failures, zero errors via a temporary compatible runner. That runner is not part of the plugin archive and was not used as a substitute for PHPUnit in this verification.

`vendor/bin/phpunit` cannot be run in the present environment because Composer/PHPUnit dependencies are unavailable. Therefore the mandatory native PHPUnit 10.5 result has **not** been independently confirmed.

### Native-run attempt

On 24 July 2026, the required `composer install` command was invoked from the plugin root. PowerShell returned `CommandNotFoundException` for `composer` with exit code `1`. The `php` command and `vendor/bin/phpunit` are also unavailable in this environment. No PHPUnit command was run because its dependency installation did not start.

## Acceptance decision

| Criterion | Status | Evidence |
|---|---|---|
| AC-9 full PHPUnit suite | **PENDING** | Repairs and non-PHPUnit checks pass; native `vendor/bin/phpunit --configuration phpunit.xml.dist` is still required. |
| Code-freeze boundary | **PASS** | Only AC-9 route and test-maintenance changes were applied. |

Phase A remains **Conditionally Accepted**, Code Freeze remains active, and Phase B remains blocked. AC-10 browser acceptance is unaffected.

## Required final confirmation

Run in a Composer-enabled environment:

```bash
composer install
vendor/bin/phpunit --configuration phpunit.xml.dist
```

Record the native command output and exit code in this report before changing the AC-9 or Phase A status.
