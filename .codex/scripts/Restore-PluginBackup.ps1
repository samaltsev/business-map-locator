[CmdletBinding(SupportsShouldProcess = $true)]
param([Parameter(Mandatory = $true)][string]$BackupPath, [ValidateSet('Source','Stand','Both')][string]$Target = 'Both', [switch]$ConfirmRestore, [string]$ProjectRoot)

. (Join-Path $PSScriptRoot 'Codex-WorkflowCommon.ps1')
if (-not $ConfirmRestore) { throw 'Restore requires -ConfirmRestore after reviewing the overwritten targets.' }
$project = (Resolve-Path $(if ($ProjectRoot) { $ProjectRoot } else { Join-Path $PSScriptRoot '..\..' })).Path
$backup = (Resolve-Path -LiteralPath $BackupPath).Path
Assert-PathWithin -Path $backup -Parent (Join-Path $project '.codex\backups') -Label 'Backup path'
Test-PluginBackupIntegrity -BackupPath $backup | Out-Null
$targets = @()
if ($Target -in @('Source','Both')) { $targets += @{ Snapshot = 'source-snapshot'; Destination = Join-Path $project 'business-map-locator' } }
if ($Target -in @('Stand','Both')) { $targets += @{ Snapshot = 'stand-snapshot'; Destination = 'D:\OSPanel\home\business-map.local\public\wp-content\plugins\business-map-locator' } }
foreach ($item in $targets) {
    Write-Host "Will overwrite: $($item.Destination) from $(Join-Path $backup $item.Snapshot)"
    if ($PSCmdlet.ShouldProcess($item.Destination, 'Restore plugin snapshot')) { Invoke-RobocopyChecked -Source (Join-Path $backup $item.Snapshot) -Destination $item.Destination -Arguments @('/MIR','/COPY:DAT','/DCOPY:DAT','/R:2','/W:1') }
}
