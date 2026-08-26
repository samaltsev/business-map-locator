[CmdletBinding()]
param([string]$ProjectRoot)

. (Join-Path $PSScriptRoot 'Codex-WorkflowCommon.ps1')

$project = (Resolve-Path $(if ($ProjectRoot) { $ProjectRoot } else { Join-Path $PSScriptRoot '..\..' })).Path
$source = Join-Path $project 'business-map-locator'
$stand = 'D:\OSPanel\home\business-map.local\public\wp-content\plugins\business-map-locator'
$php = 'D:\OSPanel\modules\PHP-8.2\PHP\php.exe'
$checks = [ordered]@{}
function Set-Check([string]$Name, [string]$Status, [string]$Detail) { $null = ($script:checks[$Name] = [ordered]@{ status = $Status; detail = $Detail }) }

try { $failures = @(); Get-ProductionPhpFiles -Root $source | ForEach-Object { & $php -l $_.FullName *> $null; if ($LASTEXITCODE -ne 0) { $failures += $_.FullName } }; Set-Check 'php_lint' ($(if ($failures.Count) {'FAIL'} else {'PASS'})) ($failures -join '; ') } catch { Set-Check 'php_lint' 'FAIL' $_.Exception.Message }
foreach ($item in @(@('conflict_markers','^(<<<<<<<|=======|>>>>>>>)'),@('debug_statements','\b(var_dump|print_r|console\.log)\s*\('),@('absolute_paths','[A-Za-z]:\\|file://'),@('secrets','AIza[0-9A-Za-z_-]{20,}|sk_live_|ghp_'),@('forbidden_cdn','unpkg\.com|cdnjs\.cloudflare\.com'))) {
    $matches = @(Find-ProductionScanMatches -Root $source -Pattern $item[1])
    Set-Check $item[0] ($(if ($matches.Count) {'FAIL'} else {'PASS'})) ($matches -join "`n")
}
$bootstrapCount = @(Find-ProductionScanMatches -Root $source -Pattern 'Plugin Name:' | ForEach-Object { ($_ -split ':', 2)[0] } | Sort-Object -Unique).Count
Set-Check 'main_bootstrap' ($(if ($bootstrapCount -eq 1) {'PASS'} else {'FAIL'})) "Plugin headers: $bootstrapCount"
$sourceVersion = (Select-String -LiteralPath (Join-Path $source 'business-map-locator.php') -Pattern '^\s*\* Version:\s*(.+)$').Matches.Groups[1].Value.Trim()
$standVersion = (Select-String -LiteralPath (Join-Path $stand 'business-map-locator.php') -Pattern '^\s*\* Version:\s*(.+)$').Matches.Groups[1].Value.Trim()
Set-Check 'plugin_version' ($(if ($sourceVersion -eq $standVersion) {'PASS'} else {'FAIL'})) "source=$sourceVersion stand=$standVersion"
Set-Check 'stand_dev_files' ($(if (@('.codex','tests','build','backups','.phpunit.cache') | Where-Object { Test-Path (Join-Path $stand $_) }) {'FAIL'} else {'PASS'})) 'Installed plugin does not contain excluded development directories.'
foreach ($url in @('http://business-map.local/','http://business-map.local/wp-json/','http://business-map.local/wp-json/business-map/v1/health','http://business-map.local/wp-json/business-map/v1/locations?per_page=1')) {
    try { $watch=[Diagnostics.Stopwatch]::StartNew(); $response=Invoke-WebRequest -UseBasicParsing -Uri $url -TimeoutSec 15; $watch.Stop(); Set-Check ("http_" + $url.Replace('http://business-map.local/','').Replace('/','_').Replace('?','_')) ($(if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 400){'PASS'}else{'FAIL'})) "HTTP $($response.StatusCode), $($watch.ElapsedMilliseconds) ms" } catch { Set-Check ("http_" + $url.Replace('http://business-map.local/','').Replace('/','_').Replace('?','_')) 'FAIL' $_.Exception.Message }
}
Push-Location $source
try {
    $phpunit = Join-Path $source 'vendor\bin\phpunit.bat'
    if (-not (Test-Path $phpunit)) {
        Set-Check 'phpunit' 'SKIPPED' 'Runner unavailable.'
    } else {
        try { & $phpunit *> $null; Set-Check 'phpunit' ($(if ($LASTEXITCODE -eq 0) {'PASS'} else {'FAIL'})) "exit=$LASTEXITCODE" } catch { Set-Check 'phpunit' 'FAIL' $_.Exception.Message }
    }
} finally {
    Pop-Location
}
Set-Check 'phpcs' 'SKIPPED' 'Not a runtime deployment gate; tracked separately as legacy style debt.'
Set-Check 'phpstan' 'SKIPPED' 'Not a runtime deployment gate; tracked separately as static-analysis debt.'
$node = Get-Command node -ErrorAction SilentlyContinue
if (-not $node) { Set-Check 'javascript_syntax' 'SKIPPED' 'Node unavailable.' }
else {
    try {
        $jsFailures = @(); Get-ChildItem $source -Recurse -File -Filter '*.js' | ForEach-Object { & $node.Source --check $_.FullName *> $null; if ($LASTEXITCODE -ne 0) { $jsFailures += $_.FullName } }
        Set-Check 'javascript_syntax' ($(if ($jsFailures.Count) {'FAIL'} else {'PASS'})) ($jsFailures -join '; ')
    } catch { Set-Check 'javascript_syntax' 'FAIL' $_.Exception.Message }
}
$report = Join-Path $project ('.codex\reports\' + (Get-Date -Format 'yyyy-MM-dd_HH-mm-ss') + '-stand-health.json')
[pscustomobject]$checks | ConvertTo-Json -Depth 5 | Set-Content -Encoding UTF8 -LiteralPath $report
[pscustomobject]@{ ReportPath = $report; Checks = $checks }
