# Hours editor repair (GitHub-only preparation)

Scope: hydrate saved weekly plain-hours text, isolate copy selection/cancel, fix layout.
Owner: assets/js/admin/location-editor.js initHoursEditor; existing canonical save endpoint unchanged.

User approved deferring verified local backup ONLY for GitHub patch preparation.
No deployment, database write, migration, index rebuild, version bump, or PowerShell policy change performed.

Changes:
- Parse complete seven-day schedules using current localized day labels, English day names or three-letter day codes. Supports CRLF and en dash/hyphen.
- Preserve unrecognised free text unchanged until the user completes all seven days. No inferred schedule.
- Selecting destinations/cancel stops input/change propagation; no schedule change or dirty/autosave.
- Reset selection when changing source. Empty selection/invalid source does not apply.
- Block manual, automatic and legacy AJAX submissions while edited hours are incomplete.
- Only send aliases for fields present in the form, preserving hidden contacts.
- Explicit grid slots; checkbox sizing and hidden overrides.

Checks performed:
- JavaScript syntax: PASS in V8.
- Actual initHoursEditor executed in a lightweight form/event test double: PASS.
- Hydration, CRLF, cancel, event isolation, Mon-to-Tue/Sat copy, Sunday preservation, closed copy, invalid source, empty selection, source reset, rehydrate submitted text, legacy text preservation: PASS.
- Alias omission and explicit empty values: PASS.
- Test runner was exercised in V8 with injected assert/fs/path shims. Native Node and PHPUnit were NOT available/run.
- WordPress persistence, browser layout/accessibility and reload from real DB: NOT VERIFIED.

Run locally:
```
node tests/js/location-hours-editor.test.cjs
php vendor/bin/phpunit --bootstrap tests/bootstrap.php
git diff --check
```

Before deployment: verified backup and source/stand preflight required.
Browser acceptance on a designated test Location:
1. Open saved schedule: verify every day/time against previous data.
2. Copy -> select Tue-Sat -> Cancel: no changed hours, no new save request.
3. Copy -> select Tue-Sat -> Apply: only selected days change.
4. Save -> verify successful response -> reload -> compare exact hours.
5. Incomplete source and empty selection: no copy.
6. Check narrow/wide viewport and keyboard controls.
7. Verify hidden contacts and Area assignments remain unchanged in DB.

Known limitations: plain-text weekly hours only; arbitrary legacy prose is not auto-converted.
No live Open-until calculation or timezone-status feature implemented in this repair.
