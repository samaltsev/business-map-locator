param(
    [string]$EnvFile = (Join-Path $PSScriptRoot "..\..\.env.codex")
)

if (-not (Test-Path $EnvFile)) {
    throw "Environment file not found: $EnvFile. Copy .env.codex.example to .env.codex."
}

Get-Content $EnvFile | ForEach-Object {
    $line = $_.Trim()
    if (-not $line -or $line.StartsWith("#")) { return }
    $parts = $line -split "=", 2
    if ($parts.Count -eq 2) {
        [Environment]::SetEnvironmentVariable($parts[0].Trim(), $parts[1].Trim(), "Process")
    }
}
