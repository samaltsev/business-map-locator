. "$PSScriptRoot\Load-Env.ps1"
$root = $env:PLUGIN_ROOT
$php = $env:PHP_CLI

if (-not (Test-Path $php)) { throw "PHP CLI not found: $php" }
if (-not (Test-Path $root)) { throw "Plugin root not found: $root" }

$errors = @()
Get-ChildItem $root -Recurse -Filter *.php | ForEach-Object {
    $result = & $php -l $_.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        $errors += "$($_.FullName)`n$result"
    }
}

if ($errors.Count -gt 0) {
    $errors | ForEach-Object { Write-Error $_ }
    exit 1
}
Write-Host "PHP syntax: PASS"
