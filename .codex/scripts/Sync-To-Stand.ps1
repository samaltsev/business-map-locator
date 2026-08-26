[CmdletBinding()]
param([string]$VerifiedBackupPath, [string]$ProjectRoot, [switch]$DryRun, [string[]]$ApprovedPath)

. (Join-Path $PSScriptRoot 'Codex-WorkflowCommon.ps1')
$project = (Resolve-Path $(if ($ProjectRoot) { $ProjectRoot } else { Join-Path $PSScriptRoot '..\..' })).Path
$source = Join-Path $project 'business-map-locator'
$stand = 'D:\OSPanel\home\business-map.local\public\wp-content\plugins\business-map-locator'
if (-not (Test-Path -LiteralPath $source) -or -not (Test-Path -LiteralPath $stand)) { throw 'Source or stand plugin directory is missing.' }
if ((Resolve-Path $source).Path -eq (Resolve-Path $stand).Path) { throw 'Source and stand paths must be different.' }
if (-not $VerifiedBackupPath) { $VerifiedBackupPath = & (Join-Path $PSScriptRoot 'New-PluginBackup.ps1') -TaskName 'pre-deploy' -ProjectRoot $project }
$backup = (Resolve-Path -LiteralPath $VerifiedBackupPath).Path
# This deliberately recomputes every snapshot SHA-256 before a deployment. verified:true alone is never a gate.
Test-PluginBackupIntegrity -BackupPath $backup -ExpectedSourcePath $source -ExpectedStandPath $stand | Out-Null

if ($PSBoundParameters.ContainsKey('ApprovedPath')) {
    $approved = @($ApprovedPath | Where-Object { -not [string]::IsNullOrWhiteSpace($_) })
    if ($approved.Count -eq 0) { throw 'ApprovedPath mode requires at least one explicit relative file path.' }
    $approved = @($approved | ForEach-Object {
        if ([IO.Path]::IsPathRooted($_) -or $_ -match '[*?]' -or $_ -match '(^|[\\/])\.\.([\\/]|$)' -or $_.EndsWith('/') -or $_.EndsWith('\\')) { throw "Approved path is invalid: $_" }
        $relative = $_.Replace('\','/')
        $sourceFile = Join-Path $source $relative
        $standFile = Join-Path $stand $relative
        Assert-PathWithin -Path $sourceFile -Parent $source -Label 'Approved source path'
        Assert-PathWithin -Path $standFile -Parent $stand -Label 'Approved stand path'
        if (-not (Test-Path -LiteralPath $sourceFile -PathType Leaf)) { throw "Approved source file is missing: $relative" }
        $relative
    } | Sort-Object -Unique)
    $changedPaths = @($approved | Where-Object {
        $sourceHash = (Get-FileHash -Algorithm SHA256 -LiteralPath (Join-Path $source $_)).Hash
        $standFile = Join-Path $stand $_
        -not (Test-Path -LiteralPath $standFile -PathType Leaf) -or $sourceHash -ne (Get-FileHash -Algorithm SHA256 -LiteralPath $standFile).Hash
    })
    if ($DryRun) { return [pscustomobject]@{ BackupPath = $backup; Mode = 'APPROVED'; Added = @(); Changed = $changedPaths; Removed = @(); Planned = $changedPaths; Deployment = 'NOT_RUN' } }
    try {
        foreach ($relative in $changedPaths) { $destination = Join-Path $stand $relative; New-Item -ItemType Directory -Force -Path (Split-Path -Parent $destination) | Out-Null; Copy-Item -LiteralPath (Join-Path $source $relative) -Destination $destination -Force }
        foreach ($relative in $changedPaths) { if ((Get-FileHash -Algorithm SHA256 -LiteralPath (Join-Path $source $relative)).Hash -ne (Get-FileHash -Algorithm SHA256 -LiteralPath (Join-Path $stand $relative)).Hash) { throw "Approved deployment hash mismatch: $relative" } }
        return [pscustomobject]@{ BackupPath = $backup; Mode = 'APPROVED'; Changed = $changedPaths; Removed = @(); Planned = $changedPaths; Parity = 'PASS' }
    } catch {
        foreach ($relative in $changedPaths) { $snapshot = Join-Path (Join-Path $backup 'stand-snapshot') $relative; if (Test-Path -LiteralPath $snapshot -PathType Leaf) { Copy-Item -LiteralPath $snapshot -Destination (Join-Path $stand $relative) -Force } }
        throw "Approved deployment failed and touched files rollback was attempted: $($_.Exception.Message)"
    }
}

$excludeDirs = @('.git','.codex','tests','build','node_modules','vendor','tmp','temp','.phpunit.cache')
$excludeFiles = @('.env','.env.codex','.env.codex.example')
$sourceManifest = Get-TreeManifest -Root $source -ExcludeDirectories $excludeDirs -ExcludeFiles $excludeFiles
$standBefore = Get-TreeManifest -Root $stand -ExcludeDirectories $excludeDirs -ExcludeFiles $excludeFiles
$changed = @($sourceManifest | Where-Object { $entry = $_; -not @($standBefore | Where-Object { $_.Path -eq $entry.Path -and $_.SHA256 -eq $entry.SHA256 -and $_.Length -eq $entry.Length }) })
$removed = @($standBefore | Where-Object { $entry = $_; -not @($sourceManifest | Where-Object { $_.Path -eq $entry.Path }) })
$changedPaths = @($changed | ForEach-Object { $_.Path })
$removedPaths = @($removed | ForEach-Object { $_.Path })
$plannedPaths = @($changedPaths + $removedPaths)
if ($DryRun) { return [pscustomobject]@{ BackupPath = $backup; Added = @(); Changed = $changedPaths; Removed = $removedPaths; Planned = $plannedPaths; Deployment = 'NOT_RUN' } }
try {
    $copyArguments = @('/MIR','/COPY:DAT','/DCOPY:DAT','/R:2','/W:1','/XD') + $excludeDirs + @('/XF') + $excludeFiles
    Invoke-RobocopyChecked -Source $source -Destination $stand -Arguments $copyArguments
    foreach ($directory in @('.codex','tests','build','node_modules','vendor','tmp','temp','.phpunit.cache')) {
        $stalePath = Join-Path $stand $directory
        if (Test-Path -LiteralPath $stalePath) { Assert-PathWithin -Path $stalePath -Parent $stand -Label 'Stale stand development directory'; Remove-Item -LiteralPath $stalePath -Recurse -Force }
    }
    $standManifest = Get-TreeManifest -Root $stand -ExcludeDirectories $excludeDirs -ExcludeFiles $excludeFiles
    if (-not (Test-ManifestMatch $sourceManifest $standManifest)) { throw 'Source/stand manifest mismatch after deployment.' }
    $reportPath = Join-Path $project ('.codex\reports\' + (Get-Date -Format 'yyyy-MM-dd_HH-mm-ss') + '-source-stand-manifest.json')
    [pscustomobject]@{ source = $sourceManifest; stand = $standManifest; parity = 'PASS'; backup = $backup; changed = $changedPaths; removed = $removedPaths } | ConvertTo-Json -Depth 6 | Set-Content -Encoding UTF8 -LiteralPath $reportPath
    [pscustomobject]@{ BackupPath = $backup; ManifestPath = $reportPath; Parity = 'PASS'; Changed = $changedPaths; Removed = $removedPaths }
} catch {
    & (Join-Path $PSScriptRoot 'Restore-PluginBackup.ps1') -BackupPath $backup -Target Stand -ConfirmRestore
    throw "Deployment failed and stand rollback was attempted: $($_.Exception.Message)"
}
