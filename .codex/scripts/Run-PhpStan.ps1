. "$PSScriptRoot\Load-Env.ps1"
Set-Location $env:PLUGIN_ROOT
$runner = ".\vendor\bin\phpstan.bat"
if (-not (Test-Path $runner)) {
    Write-Warning "PHPStan runner not found: $runner"
    exit 2
}
& $runner analyse
exit $LASTEXITCODE
