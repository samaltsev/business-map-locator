. "$PSScriptRoot\Load-Env.ps1"

$source = $env:PLUGIN_ROOT
$build = $env:BUILD_DIR
$slug = $env:PLUGIN_SLUG
$stage = Join-Path $build "stage\$slug"
$zip = Join-Path $build "$slug.zip"

if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
New-Item -ItemType Directory -Force -Path $stage | Out-Null

$excludeDirs = @(".git", ".github", ".idea", ".vscode", ".codex", "node_modules", "tests", "build")
$excludeFiles = @(".env", ".env.codex", "*.log", "*.zip", "phpunit.xml", "phpstan.neon", "composer.lock")

$xd = $excludeDirs | ForEach-Object { Join-Path $source $_ }
$args = @($source, $stage, "/E", "/XD") + $xd + @("/XF") + $excludeFiles
& robocopy @args
if ($LASTEXITCODE -ge 8) { exit $LASTEXITCODE }

if (Test-Path $zip) { Remove-Item $zip -Force }
Compress-Archive -Path $stage -DestinationPath $zip -CompressionLevel Optimal

$hash = Get-FileHash $zip -Algorithm SHA256
Write-Host "ZIP: $zip"
Write-Host "SHA-256: $($hash.Hash)"
