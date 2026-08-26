[CmdletBinding()]
param([Parameter(Mandatory = $true)][ValidatePattern('^[a-z0-9-]+$')][string]$TaskName, [string]$ProjectRoot)

$project = (Resolve-Path $(if ($ProjectRoot) { $ProjectRoot } else { Join-Path $PSScriptRoot '..\..' })).Path
$backup = & (Join-Path $PSScriptRoot 'New-PluginBackup.ps1') -TaskName $TaskName -ProjectRoot $project
$preflight = & (Join-Path $PSScriptRoot 'Test-StandHealth.ps1') -ProjectRoot $project
try {
    $deployment = & (Join-Path $PSScriptRoot 'Sync-To-Stand.ps1') -VerifiedBackupPath $backup -ProjectRoot $project
    $postflight = & (Join-Path $PSScriptRoot 'Test-StandHealth.ps1') -ProjectRoot $project
    $evidence = Join-Path $project ('.codex\reports\' + (Get-Date -Format 'yyyy-MM-dd_HH-mm-ss') + '-development-safety-baseline.md')
    @(
        '# Development safety baseline',
        '',
        "- Task: $TaskName",
        "- Created: $(Get-Date -Format o)",
        "- Backup: $backup",
        "- Deployment: $($deployment.Parity)",
        "- Source/stand manifest: $($deployment.ManifestPath)",
        "- Preflight: $($preflight.ReportPath)",
        "- Postflight: $($postflight.ReportPath)",
        "- Postflight result: $(($postflight.Checks.GetEnumerator() | ForEach-Object { \"$($_.Key)=$($_.Value.status)\" }) -join '; ')",
        '- Limitations: PHPUnit, PHPCS, PHPStan, and Node checks are marked SKIPPED only when their runners are unavailable.',
        "- Restoration: .\.codex\scripts\Restore-PluginBackup.ps1 -BackupPath '$backup' -Target Both -ConfirmRestore"
    ) -join "`n" | Set-Content -Encoding UTF8 -LiteralPath $evidence
    [pscustomobject]@{ BackupPath = $backup; EvidencePath = $evidence; Deployment = $deployment; Postflight = $postflight }
} catch { throw "Round stopped after failed deployment. Backup retained at $backup. $($_.Exception.Message)" }
