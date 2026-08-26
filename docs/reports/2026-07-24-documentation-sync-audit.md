# Documentation Sync Audit — 2026-07-24

**Project:** Business Map Locator  
**Baseline:** `1.3.2-beta29`  
**Scope:** documentation-only audit; no PHP, JavaScript, runtime configuration, or roadmap status was changed.

## Summary

**Documentation Health Score: 62/100 (needs remediation).** The new top-level documentation layout is present and the roadmap accurately records the accepted foundation and the current Areas Migration round. However, the current Master Specification contains beta29 audit/history and a dated implementation roadmap; the Area and marker architecture documents describe target behaviour as if it were available in the current runtime; and the public changelog contains internal engineering progress.

**Final documentation maturity score: 58/100.** The information exists, but ownership boundaries between product requirements, current status, architecture, and changelogs are not yet consistently enforced.

## Evidence basis

- Documentation inventory: `docs/README.md`, `architecture/`, `roadmap/`, `changelog/`, `reports/`, `specification/`, plus retained `spec/` and `process/` directories.
- Runtime read-only inspection: `src/WordPress/ContentTypes.php`, `src/Rest/LocationsController.php`, `src/Infrastructure/Database/LocationRepository.php`, `assets/js/map-controller.js`, and provider adapters.
- Reference search: Markdown and project-control files under `docs/`, `AGENTS.md`, and `.codex/prompts/`.

## Findings

### Critical

1. **The v3 Master Specification mixes requirements with implementation history.**
   `specification/NBH-Business-Locator-Master-Spec-v3.0.md` contains an audit of `1.3.2-beta29`, implemented/current-build statements, P0 blocker history, and a dated iteration roadmap (sections 2 and 22). This conflicts with the documentation rule that the Master Spec represents product requirements, while history belongs in roadmap, architecture records, and `CHANGELOG-DEV`.

2. **Area architecture is ahead of the runtime.**
   `architecture/Areas.md` calls `bml_area` the “current beta canonical slug”; `architecture/Contracts.md` presents its Area model as a transition contract; and `architecture/REST.md` documents active `area*` parameters and City-to-Area resolution. The runtime only registers and uses `bml_city` (`src/WordPress/ContentTypes.php` and the REST/index/admin/import paths). No `bml_area` registration, migration service, `area` REST argument, or Area response field exists. These statements must be labelled *target / planned for the active Areas Migration*, not current runtime behaviour.

3. **The documented marker contract exceeds the implementation.**
   The marker route accepts only `north`, `south`, `east`, and `west` in `src/Rest/LocationsController.php`. It delegates to `LocationRepository::markers()`, which returns `{ items, truncated }`; it neither applies category/city/area/search filters nor returns `returned` or `totalInBounds`. `architecture/REST.md` therefore incorrectly describes active canonical filters and presents the three-field metadata schema as an accepted current contract. `ROADMAP.md` correctly lists that metadata as Areas Migration debt.

### Major

4. **Relative paths in `roadmap/ROADMAP.md` are broken.**
   From `docs/roadmap/ROADMAP.md`, `docs/specification/...` and `docs/changelog/...` resolve under `docs/roadmap/docs/`, which does not exist. They should be `../specification/...` and `../changelog/...`, or root-relative links if the renderer supports them.

5. **The obsolete v2 specification remains an effective source of truth.**
   `AGENTS.md` and all `.codex/prompts/**` still point to `docs/spec/NBH-Business-Locator-Master-Spec-v2.0.md`, while `docs/README.md`, `ROADMAP.md`, and the orchestration document name v3.0 as current. Keep v2 only as an explicitly labelled archive, or update all operational references to v3.0. Do not leave two competing specifications.

6. **`CHANGELOG.md` is not limited to user-visible release changes.**
   Its “Improved” entry for runtime deployment/verification safety is internal engineering process. The “In progress” section contains unshipped implementation work, and the `1.3.2-beta29` baseline note is internal status rather than a public release entry. These belong in `CHANGELOG-DEV.md` and `ROADMAP.md`.

### Moderate

7. **The new structure is incomplete as an operational documentation set.**
   `docs/reports/` contains only a README; before this audit it had no durable evidence. There is also no dedicated migration runbook, no explicit API schema/reference for implemented routes, and no architecture record focused on the actual frontend loading protocol. The information is partly distributed across the Master Spec and architecture files.

8. **Status and requirement content is duplicated.**
   Foundation/Areas statuses appear in both `ROADMAP.md` and `RELEASE-PLAN.md`; loading and concurrency rules are repeated in `Runtime.md`, `REST.md`, and `Contracts.md`; Area migration rules are repeated in `Areas.md`, `REST.md`, and `Contracts.md`; and the Master Spec repeats the roadmap. Repetition currently drifts because it lacks an ownership rule for each fact.

9. **Completed-milestone status is otherwise synchronized.**
   The seven accepted milestones requested for this audit are marked completed in `ROADMAP.md`, recorded as accepted in `CHANGELOG-DEV.md`, and represented in the architecture set. Areas Migration is consistently shown as in progress. No completed stage is still treated as future work in the live roadmap/release-plan/changelog set.

## Architecture consistency matrix

| Subject | Master Spec requirement | Current runtime evidence | Documentation assessment |
|---|---|---|---|
| Runtime ownership | One canonical owner / legacy isolation before GA | Namespaced and legacy layers coexist | `Runtime.md` accurately records this as technical debt. |
| REST card/detail | Paginated cards and lazy detail | Collection, detail, and pagination routes are present | Consistent at the documented baseline level. |
| Server-side loading | No eager all-page loading | Per-instance controllers, cancellation, bounds marker requests | Consistent. |
| AbortController | Separate request cancellation | Separate card/marker/detail controllers in `map-controller.js` | Consistent. |
| Instance isolation | Unique independent Locator state | Per-controller state and teardown are present | Consistent. |
| Marker endpoint | Bounds query, minimal data, response-limit metadata | Bounds-only endpoint; `{items, truncated}` | Metadata/filter documentation overclaims. |
| Areas / Location contract | Hierarchical Area with City compatibility after migration | Only non-hierarchical `bml_city` exists | Target design is incorrectly stated as current. |

## Changelog validation

`CHANGELOG-DEV.md` contains all accepted architecture milestones:

- Runtime Architecture Audit
- Settings Runtime Stabilization
- Development Safety Baseline
- Location Contract P0
- REST Card / Detail Contract
- Server-side Frontend Loading

It also correctly records Areas Migration as in progress. Its remaining issue is wording that says marker metadata will close in the “same REST-contract round”; the active roadmap assigns it to Areas Migration.

## Missing documents

1. **Migration runbook** — operator-facing dry-run, execute, resume, verify, and selective-rollback procedures, created when the Area service exists.
2. **Implemented REST API reference** — route, query-parameter, response-schema, error-schema, and compatibility table for the actual beta runtime.
3. **Frontend loading protocol** — a focused architecture record for request channels, marker filter parity, cancellation, sequence guards, provider events, and teardown.
4. **Documentation governance / archive policy** — designate canonical documents, archive rules for v2, link style, and update ownership.

## Recommended improvements

1. Refactor v3 into a requirement-only Master Spec. Move beta29 audit and iteration/status material to `ROADMAP.md`, relevant architecture documents, and `CHANGELOG-DEV.md`.
2. Add a clear `Implemented`, `In progress / target`, and `Planned` label to every architecture section. Do not call `bml_area`, Area REST fields, or the three-field marker metadata current until runtime evidence exists.
3. Correct `ROADMAP.md` relative links and replace all operational v2 references with v3; retain v2 in an `archive/` location only if history must be preserved.
4. Restrict the public changelog to released, user-observable changes. Keep internal safety, evidence, and in-progress work out of it.
5. Make `ROADMAP.md` the sole owner of implementation status; make `RELEASE-PLAN.md` own gates/sequencing only.
6. After an Areas implementation acceptance, publish the migration runbook and API reference from verified runtime responses rather than target designs.

## Suggested document merges

- Merge the duplicated *current status* narrative from `RELEASE-PLAN.md` into concise links to `ROADMAP.md`; retain release gates in `RELEASE-PLAN.md`.
- Consolidate the repeated frontend-loading statements in `Runtime.md`, `REST.md`, and `Contracts.md` into a dedicated **Frontend Loading Protocol** document. Leave only short normative links in the three source documents.
- Move the Master Spec’s roadmap/history chapters into `ROADMAP.md` and `RELEASE-PLAN.md` rather than maintaining a fourth roadmap narrative.

## Suggested document splits

- Split `NBH-Business-Locator-Master-Spec-v3.0.md` into **Product Requirements** and an archived **beta29 audit/background** document.
- When Areas is implemented, split `Areas.md` into a stable **Area Contract** and an operational **City-to-Area Migration Runbook**.
- Split `REST.md` into an implemented **Public API Reference** and a shorter **REST Architecture / evolution policy** if complete schemas make it unwieldy.

## Documentation debt

| Priority | Debt | Consequence |
|---|---|---|
| P0 | Master Spec contains implementation history | Product requirements and status can contradict each other. |
| P0 | Area and marker documents overstate runtime support | Consumers may build against absent APIs or migration features. |
| P1 | v2 remains operationally referenced | Agents can follow stale requirements. |
| P1 | Broken roadmap-relative paths | Reviewers cannot reliably navigate sources of truth. |
| P1 | Public changelog includes internal/in-progress work | Release communication becomes misleading. |
| P2 | Duplicated status/protocol text | Future status drift is likely. |

## Roadmap decision

No `ROADMAP.md` status change was made. Its completed foundation stages and Areas Migration status match the accepted implementation status supplied for this audit and the inspected runtime boundary. The required remediation is document-content correction, not a change of implementation status.

## Checks performed

- Folder and filename inventory: PASS — requested live folders/files exist; legacy `docs/spec/` and `docs/process/` also remain.
- Cross-reference scan: FAIL — relative paths in `ROADMAP.md` are invalid; v2/v3 source-of-truth references conflict.
- Completed-milestone status scan: PASS — accepted stages are not represented as future roadmap work.
- Runtime-to-architecture inspection: FAIL — Area and marker contract overclaims documented above.
- Changelog scope inspection: FAIL — public changelog includes internal and in-progress material.
- Browser/runtime test: not run; this was a read-only documentation audit.
