$checks = @(
    "Test-PHP-Syntax.ps1",
    "Run-PhpUnit.ps1",
    "Run-PhpCs.ps1",
    "Run-PhpStan.ps1"
)

$failed = $false
foreach ($check in $checks) {
    Write-Host "`n=== $check ==="
    & (Join-Path $PSScriptRoot $check)
    if ($LASTEXITCODE -eq 1) { $failed = $true }
}
if ($failed) { exit 1 }
