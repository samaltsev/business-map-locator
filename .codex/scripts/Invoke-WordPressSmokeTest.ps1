[CmdletBinding()]
param()
$php = 'D:\OSPanel\modules\PHP-8.2\PHP\php.exe'
$runner = Join-Path $PSScriptRoot 'runtime\location-contract-smoke.php'
if (-not (Test-Path $php) -or -not (Test-Path $runner)) { throw 'WordPress smoke runner prerequisites are unavailable.' }
& $php $runner
if ($LASTEXITCODE -ne 0) { throw "WordPress smoke test failed with exit code $LASTEXITCODE." }
