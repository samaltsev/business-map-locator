. "$PSScriptRoot\Load-Env.ps1"
Set-Location $env:PLUGIN_ROOT

$candidates = @(
    ".\vendor\bin\phpunit.bat",
    ".\vendor\bin\phpunit",
    ".\phpunit.phar"
)

$runner = $candidates | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $runner) {
    Write-Warning "PHPUnit runner not found."
    exit 2
}

if ($runner.EndsWith(".phar")) {
    & $env:PHP_CLI $runner
} else {
    & $runner
}
exit $LASTEXITCODE
