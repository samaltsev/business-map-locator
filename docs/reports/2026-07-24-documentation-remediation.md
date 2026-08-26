# Documentation Remediation Evidence — 2026-07-24

**Project:** Business Map Locator  
**Baseline:** `1.3.2-beta29`  
**Scope:** Documentation only. No PHP, JavaScript, CSS, runtime, database, or REST implementation files were changed.

## Summary

The Documentation Sync Audit findings have been remediated. The live v3 Master Specification is now requirements-only; `ROADMAP.md` is the single owner of implementation status; architecture documents distinguish current runtime from target and planned work; v2 is archived; and changelog scope is separated between public releases and engineering history.

**Documentation maturity score: 88/100.**

## Changes completed

### Master Specification

- Removed the beta29 runtime audit, completed-work history, dated iteration plan, and post-launch delivery roadmap from the live v3 Master Specification.
- Removed the beta29/current-runtime column from the Free/Pro product matrix.
- Renumbered the remaining specification chapters after the removals.
- Added a normative boundary: the live specification contains requirements, architecture, contracts, acceptance criteria, and release requirements only.
- Preserved prior material in `docs/archive/specification/NBH-Business-Locator-Master-Spec-v3.0-pre-remediation.md`.

### Roadmap and release plan

- Declared `ROADMAP.md` the single source of implementation status.
- Corrected Master Spec and changelog references to valid relative Markdown links.
- Added the verified current Areas/marker runtime boundary: `bml_city` only; bounds-only marker endpoint; `items` plus `truncated` only.
- Removed duplicated progress details from `RELEASE-PLAN.md`; it now owns sequencing and gates only.

### Architecture

- Added explicit **Current Runtime**, **Target Architecture**, and **Planned** boundaries to Runtime, REST, Areas, and Contracts documents.
- Documented that `bml_city` is current and that hierarchical `bml_area` migration is in progress.
- Documented current marker metadata as `items` plus `truncated`; documented `returned`, `totalInBounds`, and filter parity as target work.

### Version and changelog cleanup

- Archived the retired v2 specification in `docs/archive/specification/` and documented archive usage.
- Updated `AGENTS.md` and all active `.codex/prompts/` references from v2 to the live v3 specification.
- Restricted `CHANGELOG.md` to user-visible unreleased changes.
- Kept engineering milestones and Areas Migration progress in `CHANGELOG-DEV.md`.

## Validation

| Check | Result | Evidence |
|---|---|---|
| Documentation folders and required live files | PASS | `docs/specification`, `roadmap`, `architecture`, `changelog`, and `reports` are present. |
| Markdown relative links | PASS | 13 links checked; 0 broken links. |
| Markdown table structure | PASS | Header and separator column counts match across all live Markdown files. |
| Operational v2 references | PASS | No `docs/spec/...v2.0.md` reference remains in `AGENTS.md` or `.codex/prompts/`. |
| Master Spec history boundary | PASS | The live v3 file no longer contains the beta29 audit, blocker history, or implementation iteration/roadmap chapters. |
| Architecture wording | PASS | Current/target/planned status headings are present and no document calls `bml_area`, `returned`, or `totalInBounds` current runtime behaviour. |
| Roadmap status ownership | PASS | `ROADMAP.md` owns implementation status; `RELEASE-PLAN.md` no longer carries a status column. |
| Changelog scope | PASS | Public changelog excludes internal deployment/process work and in-progress items. |
| Production code integrity | PASS | Source files match the verified backup when its documented Cache-directory exclusions are applied. |

## Remaining documentation debt

1. A route-by-route REST API reference should be published from verified responses after Area and marker enhancements are implemented.
2. A practical City-to-Area migration runbook should be created only with the migration service, dry run, execution, resume, and rollback procedures.
3. A focused frontend loading protocol document may later centralize the repeated implementation contract now intentionally summarized in Runtime, REST, and Contracts.
4. Browser-rendered Markdown review was not run; link and structural validation was command-line based.

## Archive and backup

- Historical v2 and pre-remediation v3 materials are preserved under `docs/archive/specification/`; they are not current sources of truth.
- Verified backup created before remediation: `.codex/backups/2026-07-24_10-42-22_documentation-remediation`.

## Files in scope

- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/roadmap/ROADMAP.md`
- `docs/roadmap/RELEASE-PLAN.md`
- `docs/architecture/Runtime.md`
- `docs/architecture/REST.md`
- `docs/architecture/Areas.md`
- `docs/architecture/Contracts.md`
- `docs/changelog/CHANGELOG.md`
- `docs/changelog/CHANGELOG-DEV.md`
- `docs/README.md`
- `docs/archive/README.md`
- `AGENTS.md` and active `.codex/prompts/` specification references
