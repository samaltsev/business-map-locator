# Areas Slice 1 — Relation Index Schema + Rebuild Foundation

## Baseline

- Branch: `codex/production-hardening`
- HEAD: `3ea16be7d5259d95f6a97f5217259473c62a715a`
- Plugin version: `1.3.2-beta40.7`
- Initial worktree: clean.

## Backup

- Status: verified.
- Path: `D:\WP plugins\business-map-locator\.codex\backups\2026-09-07_13-45-41_areas-relation-index-foundation`
- Evidence: `backup-info.json` reports `verified: true`, 196 source files and 188 stand files.
- The workflow default stand path was stale (`business-map.local`); the existing backup script was run with its supported explicit `-StandPath` option for the active stand.

## Ownership and implementation

- Schema: `BML_Schema`, `BML_Database`, `SchemaMigrator`.
- Scalar and relation synchronization: `BML_Location_Index`, triggered by `BML_Location_Indexer`.
- Rebuild: existing `BML_Migrator` → `BML_Location_Index::rebuild()` batching (100).
- Source of truth: WordPress direct term relationships. `bml_location_terms` is derived and rebuildable.
- No City-to-Area migration, Area assignment, REST/repository, CSV, shortcode, frontend, or editor behavior was changed.

## Schema

- Table: `wpbml_bml_location_terms`.
- Columns: `location_id BIGINT UNSIGNED`, `taxonomy VARCHAR(32)`, `term_id BIGINT UNSIGNED`, `is_primary TINYINT(1) DEFAULT 0`.
- Keys: unique `(location_id, taxonomy, term_id)`; `(taxonomy, term_id, location_id)`; `(location_id, taxonomy, is_primary)`.
- Engine/collation: InnoDB / `utf8mb4_unicode_520_ci`.
- Internal database version: `1.3.3` → `1.3.4`; schema version: `4` → `5`.
- Creation is idempotent through existing `dbDelta`; the first upgrade invoked the same table definition through both install and schema-step paths without error.

## Relation behavior

- Allowlist: `bml_category`, `bml_city`, `bml_area` only.
- Rows contain direct term IDs only; no ancestors, names, or slugs are stored.
- `is_primary` is always `0`.
- Per-Location sync replaces rows only for its current `location_id`, in a short relation-table transaction; a source-read or transaction-start failure leaves current rows untouched; no global truncate is used.
- Delete removes scalar and relation rows. Trash invokes the existing delete path; untrash and taxonomy changes reindex through the existing indexer.
- Term rename is safe because rows contain only `term_id`.

## Rebuild and database evidence

Canonical local run used `BML_Database::install()` followed by batched `BML_Location_Index::rebuild()`:

- 810 eligible Locations processed and indexed.
- 9 batches; 0 failures.
- Rebuild completion flag cleared through `BML_Database::mark_rebuild_complete()`.

| Taxonomy | WordPress eligible direct relationships | Relation-index rows | Result |
|---|---:|---:|---|
| `bml_category` | 811 | 811 | MATCH |
| `bml_city` | 810 | 810 | MATCH |
| `bml_area` | 0 | 0 | MATCH / area-less |

Integrity queries:

- Duplicate relation-key groups: 0.
- Taxonomies outside allowlist: 0.
- Orphan/ineligible Location IDs: 0.
- Stale term IDs: 0.
- Non-zero `is_primary`: 0.

## Checks

- Changed-source and deployed-file PHP lint: PASS.
- PHPUnit: PASS — 102 tests, 396 assertions, 0 failures, 0 errors.
- PHPStan affected paths: no Slice 1 findings. One pre-existing finding remains: `SchemaMigrator.php` references runtime WordPress constant `ABSPATH` outside PHPStan bootstrap.
- PHPCS: not actionable. The repository WPCS configuration reports pervasive legacy formatting and direct `$wpdb` findings across touched pre-existing owners; no broad formatting cleanup was performed.
- Source/stand SHA-256 parity: PASS for all seven deployed production files.
- Local HTTP smoke: `https://business-map-locator/wp-json/business-map/v1/health` 200; public Locations endpoint 200.

## Remaining risks

- Relation sync is derived after scalar upsert; an unexpected relation-table write failure leaves scalar data current and is recovered by the existing rebuild lifecycle.
- The project sync/health scripts still contain an obsolete `business-map.local` stand path. This round used explicit verified backup plus per-file hash-checked deployment to the active local stand; workflow-path repair remains separate tooling work.
