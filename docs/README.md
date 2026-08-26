# Business Map Locator Documentation

## Source of truth

- Product requirements: `specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- Current implementation status: `roadmap/ROADMAP.md`
- Release gates and sequencing: `roadmap/RELEASE-PLAN.md`
- Development process: `process/NBH-Business-Locator-Development-Orchestration.md`
- Architecture decisions: `architecture/`
- Internal milestone history: `changelog/CHANGELOG-DEV.md`
- Public release changes: `changelog/CHANGELOG.md`
- Historical specifications: `archive/specification/` (reference only; never a current source of truth)

## Update rule

After an implementation round is accepted:

1. Update `roadmap/ROADMAP.md`.
2. Update the affected architecture document.
3. Add a dated entry to `changelog/CHANGELOG-DEV.md`.
4. Update the public changelog only for user-visible behavior.
5. Preserve evidence under `.codex/reports/` and link durable reports where appropriate.
6. Do not rewrite the Master Specification for ordinary progress updates.
