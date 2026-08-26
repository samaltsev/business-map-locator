[CmdletBinding()]
param([Parameter(Mandatory = $true)][string]$BackupPath, [string]$ExpectedSourcePath, [string]$ExpectedStandPath)
. (Join-Path $PSScriptRoot 'Codex-WorkflowCommon.ps1')
Test-PluginBackupIntegrity -BackupPath $BackupPath -ExpectedSourcePath $ExpectedSourcePath -ExpectedStandPath $ExpectedStandPath
