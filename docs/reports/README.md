# Evidence Reports

This directory is reserved for durable, reviewable implementation evidence that belongs in project documentation.

Environment-specific raw snapshots, temporary backups and Codex execution artifacts should remain under the project-level `.codex/` directories and must not enter release packages.

A final round report should include:

- scope and acceptance result;
- backup and baseline references;
- files and schema changed;
- automated checks with PASS/FAIL/SKIPPED accuracy;
- runtime and HTTP evidence;
- migration or rollback evidence when relevant;
- source/stand parity;
- remaining risks and technical debt.
