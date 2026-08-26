[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][ValidatePattern('^[a-z0-9-]+$')][string]$TaskName,
    [string]$ProjectRoot, [string]$SourcePath, [string]$StandPath, [string]$BackupRoot
)

. (Join-Path $PSScriptRoot 'Codex-WorkflowCommon.ps1')
$project = (Resolve-Path $(if ($ProjectRoot) { $ProjectRoot } else { Join-Path $PSScriptRoot '..\..' })).Path
$source = (Resolve-Path -LiteralPath $(if ($SourcePath) { $SourcePath } else { Join-Path $project 'business-map-locator' })).Path
$stand = (Resolve-Path -LiteralPath $(if ($StandPath) { $StandPath } else { 'D:\OSPanel\home\business-map.local\public\wp-content\plugins\business-map-locator' })).Path
$backupBase = if ($BackupRoot) { $BackupRoot } else { Join-Path $project '.codex\backups' }
Assert-PathWithin -Path $backupBase -Parent $project -Label 'Backup root'
New-Item -ItemType Directory -Force -Path $backupBase | Out-Null
$backupPath = Join-Path $backupBase ((Get-Date -Format 'yyyy-MM-dd_HH-mm-ss') + '_' + $TaskName)
if (Test-Path -LiteralPath $backupPath) { throw "Backup destination already exists: $backupPath" }

$excludeDirs = @('.git','.codex','node_modules','build','backups','tmp','temp')
$excludeFiles = @('.env','.env.codex','.env.codex.example','*.log','*.zip')
# Staging must live on the same volume as backups so Directory.Move is an atomic rename.
$staging = Join-Path (Split-Path -Parent $backupBase) ('.bml-backup-staging-' + [guid]::NewGuid().ToString('N'))
try {
    New-Item -ItemType Directory -Path $staging | Out-Null
    $sourceBefore = Get-TreeManifest -Root $source -ExcludeDirectories $excludeDirs -ExcludeFiles $excludeFiles
    $standBefore = Get-TreeManifest -Root $stand -ExcludeDirectories $excludeDirs -ExcludeFiles $excludeFiles
    $copyArguments = @('/E','/COPY:DAT','/DCOPY:DAT','/R:2','/W:1','/XD') + $excludeDirs + @('/XF') + $excludeFiles
    Invoke-RobocopyChecked -Source $source -Destination (Join-Path $staging 'source-snapshot') -Arguments $copyArguments
    Invoke-RobocopyChecked -Source $stand -Destination (Join-Path $staging 'stand-snapshot') -Arguments $copyArguments
    $sourceManifest = Get-TreeManifest -Root (Join-Path $staging 'source-snapshot')
    $standManifest = Get-TreeManifest -Root (Join-Path $staging 'stand-snapshot')
    if (-not (Test-ManifestMatch $sourceBefore $sourceManifest) -or -not (Test-ManifestMatch $standBefore $standManifest)) { throw 'Backup verification failed: snapshot manifest does not match its source.' }
    $manifest = [ordered]@{ schema_version = 1; algorithm = 'SHA-256'; source = [ordered]@{ files = $sourceManifest }; stand = [ordered]@{ files = $standManifest } }
    $manifest | ConvertTo-Json -Depth 6 | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $staging 'MANIFEST-SHA256.json')
    $manifestLines = @($sourceManifest | ForEach-Object { "SOURCE  $($_.SHA256)  $($_.Path)" })
    $manifestLines += @($standManifest | ForEach-Object { "STAND   $($_.SHA256)  $($_.Path)" })
    $manifestLines | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $staging 'MANIFEST-SHA256.txt')
    $info = [ordered]@{ schema_version = 1; task_name = $TaskName; created_at = (Get-Date).ToString('o'); plugin_slug = 'business-map-locator'; plugin_version = Get-PluginVersion $source; source_path = $source; stand_path = $stand; exclusions = [ordered]@{ directories = $excludeDirs; files = $excludeFiles }; manifest_path = 'MANIFEST-SHA256.json'; snapshots = [ordered]@{ source = (Get-ManifestSummary $sourceManifest); stand = (Get-ManifestSummary $standManifest) }; verified = $true }
    $info | ConvertTo-Json -Depth 6 | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $staging 'backup-info.json')
    Test-PluginBackupIntegrity -BackupPath $staging -ExpectedSourcePath $source -ExpectedStandPath $stand | Out-Null
    [IO.Directory]::Move($staging, $backupPath)
    Test-PluginBackupIntegrity -BackupPath $backupPath -ExpectedSourcePath $source -ExpectedStandPath $stand | Out-Null
    Write-Output $backupPath
} catch {
    if (Test-Path -LiteralPath $staging) { Remove-Item -LiteralPath $staging -Recurse -Force }
    throw "Backup was not verified: $($_.Exception.Message)"
}
