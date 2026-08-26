Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Get-CodexProjectRoot {
    return (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
}

function Assert-PathWithin {
    param([string]$Path, [string]$Parent, [string]$Label)
    $resolvedPath = [IO.Path]::GetFullPath($Path)
    $resolvedParent = [IO.Path]::GetFullPath($Parent).TrimEnd('\') + '\'
    if (-not $resolvedPath.StartsWith($resolvedParent, [StringComparison]::OrdinalIgnoreCase)) {
        throw "$Label must stay within ${Parent}: $Path"
    }
}

function Invoke-RobocopyChecked {
    param([string]$Source, [string]$Destination, [string[]]$Arguments)
    & robocopy $Source $Destination @Arguments | Out-Null
    if ($LASTEXITCODE -ge 8) {
        throw "robocopy failed with exit code $LASTEXITCODE while copying $Source to $Destination."
    }
}

function Get-PluginVersion {
    param([string]$PluginRoot)
    $mainFile = Join-Path $PluginRoot 'business-map-locator.php'
    $match = Select-String -LiteralPath $mainFile -Pattern '^\s*\* Version:\s*(.+)$' | Select-Object -First 1
    if (-not $match) { throw "Plugin version not found in $mainFile." }
    return $match.Matches[0].Groups[1].Value.Trim()
}

function Get-ProductionScanExcludeGlobs {
    return @(
        '!**/vendor/**',
        '!**/tests/**',
        '!**/fixtures/**',
        '!**/.git/**',
        '!**/.codex/**',
        '!**/logs/**',
        '!**/reports/**',
        '!**/backups/**',
        '!**/tmp/**',
        '!**/temp/**',
        '!**/node_modules/**',
        '!**/build/**'
    )
}

function Find-ProductionScanMatches {
    param(
        [Parameter(Mandatory = $true)][string]$Root,
        [Parameter(Mandatory = $true)][string]$Pattern
    )

    $arguments = @('-n', '--glob', '*.php', '--glob', '*.js', '--glob', '*.css')
    foreach ($glob in Get-ProductionScanExcludeGlobs) {
        $arguments += @('--glob', $glob)
    }
    $arguments += @($Pattern, $Root)
    return @(& rg @arguments 2>$null)
}

function Get-ProductionPhpFiles {
    param([Parameter(Mandatory = $true)][string]$Root)

    $excludedDirectories = @('vendor', 'tests', 'fixtures', '.git', '.codex', 'logs', 'reports', 'backups', 'tmp', 'temp', 'node_modules', 'build')
    $rootPath = (Resolve-Path -LiteralPath $Root).Path.TrimEnd([char]'\', [char]'/' )
    return @(Get-ChildItem -LiteralPath $rootPath -Recurse -File -Filter '*.php' | Where-Object {
        $relative = $_.FullName.Substring($rootPath.Length).TrimStart([char]'\', [char]'/')
        @($relative -split '[\\/]' | Where-Object { $excludedDirectories -contains $_ }).Count -eq 0
    })
}

function Get-TreeManifest {
    param([string]$Root, [string[]]$ExcludeDirectories = @(), [string[]]$ExcludeFiles = @())
    $rootPath = (Resolve-Path $Root).Path.TrimEnd('\','/')
    $items = Get-ChildItem -LiteralPath $rootPath -Recurse -File | Where-Object {
        $relative = $_.FullName.Substring($rootPath.Length).TrimStart('\','/')
        $parts = $relative -split '[\\/]'
        $directoryExcluded = @($parts | Where-Object { $ExcludeDirectories -contains $_ }).Count -gt 0
        $fileName = $_.Name
        $fileExcluded = @($ExcludeFiles | Where-Object { $fileName -like $_ }).Count -gt 0
        -not $directoryExcluded -and -not $fileExcluded
    }
    return @($items | ForEach-Object {
        $relative = $_.FullName.Substring($rootPath.Length).TrimStart('\','/').Replace('\','/')
        [pscustomobject]@{ Path = $relative; SHA256 = (Get-FileHash -Algorithm SHA256 -LiteralPath $_.FullName).Hash; Length = $_.Length }
    } | Sort-Object Path)
}

function Test-ManifestMatch {
    param([object[]]$Expected, [object[]]$Actual)
    $expectedJson = @($Expected | Sort-Object Path) | ConvertTo-Json -Depth 4 -Compress
    $actualJson = @($Actual | Sort-Object Path) | ConvertTo-Json -Depth 4 -Compress
    return $expectedJson -eq $actualJson
}

function Get-ManifestSummary {
    param([object[]]$Entries)
    return [ordered]@{ file_count = @($Entries).Count; total_bytes = [int64](@($Entries | Measure-Object -Property Length -Sum).Sum) }
}

function Test-PluginBackupIntegrity {
    param(
        [Parameter(Mandatory = $true)][string]$BackupPath,
        [string]$ExpectedSourcePath,
        [string]$ExpectedStandPath
    )
    $backup = (Resolve-Path -LiteralPath $BackupPath).Path
    foreach ($required in @('backup-info.json','MANIFEST-SHA256.json','MANIFEST-SHA256.txt','source-snapshot','stand-snapshot')) {
        if (-not (Test-Path -LiteralPath (Join-Path $backup $required))) { throw "Backup is incomplete: missing $required." }
    }
    try { $info = Get-Content -Raw -LiteralPath (Join-Path $backup 'backup-info.json') | ConvertFrom-Json } catch { throw "Backup metadata is invalid JSON: $($_.Exception.Message)" }
    try { $manifest = Get-Content -Raw -LiteralPath (Join-Path $backup 'MANIFEST-SHA256.json') | ConvertFrom-Json } catch { throw "Backup manifest is invalid JSON: $($_.Exception.Message)" }
    foreach ($property in @('schema_version','task_name','created_at','plugin_slug','plugin_version','source_path','stand_path','snapshots','verified')) {
        if ($null -eq $info.PSObject.Properties[$property]) { throw "Backup metadata is missing $property." }
    }
    if ($info.schema_version -ne 1 -or $info.plugin_slug -ne 'business-map-locator' -or $info.verified -ne $true) { throw 'Backup metadata does not describe a verified Business Map Locator backup.' }
    if ($manifest.schema_version -ne 1 -or $manifest.algorithm -ne 'SHA-256') { throw 'Backup manifest schema or algorithm is invalid.' }
    foreach ($name in @('source','stand')) {
        if ($null -eq $manifest.$name -or $null -eq $manifest.$name.files -or $null -eq $info.snapshots.$name) { throw "Backup manifest is missing $name snapshot data." }
        $actual = Get-TreeManifest -Root (Join-Path $backup "$name-snapshot")
        $expected = @($manifest.$name.files | ForEach-Object { [pscustomobject]@{ Path = $_.Path; SHA256 = $_.SHA256; Length = [int64]$_.Length } })
        if (-not (Test-ManifestMatch -Expected $expected -Actual $actual)) { throw "Backup hash validation failed for $name snapshot." }
        $summary = Get-ManifestSummary -Entries $actual
        if ($summary.file_count -ne [int]$info.snapshots.$name.file_count -or $summary.total_bytes -ne [int64]$info.snapshots.$name.total_bytes) { throw "Backup metadata summary mismatch for $name snapshot." }
    }
    if ($ExpectedSourcePath -and -not ((Resolve-Path -LiteralPath $ExpectedSourcePath).Path -eq $info.source_path)) { throw 'Backup source identity does not match the current source path.' }
    if ($ExpectedStandPath -and -not ((Resolve-Path -LiteralPath $ExpectedStandPath).Path -eq $info.stand_path)) { throw 'Backup stand identity does not match the current stand path.' }
    if ((Get-PluginVersion -PluginRoot (Join-Path $backup 'source-snapshot')) -ne $info.plugin_version -or (Get-PluginVersion -PluginRoot (Join-Path $backup 'stand-snapshot')) -ne $info.plugin_version) { throw 'Backup plugin version identity does not match metadata.' }
    return [pscustomobject]@{ BackupPath = $backup; Verified = $true; SourceFiles = @($manifest.source.files).Count; StandFiles = @($manifest.stand.files).Count }
}
