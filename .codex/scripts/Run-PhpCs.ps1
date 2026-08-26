. "$PSScriptRoot\Load-Env.ps1"
Set-Location $env:PLUGIN_ROOT
$runner = ".\vendor\bin\phpcs.bat"
if (-not (Test-Path $runner)) {
    Write-Warning "PHPCS runner not found: $runner"
    exit 2
}
& $runner
exit $LASTEXITCODE
