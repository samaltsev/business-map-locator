$common = Join-Path $PSScriptRoot '..\Codex-WorkflowCommon.ps1'
. $common

$script:scanContractRoot = Join-Path $env:TEMP ('bml-production-scan-' + [guid]::NewGuid().ToString('N'))

Describe 'Production scan contract' {
    BeforeAll {
        foreach ($relative in @('src\valid.js', 'vendor\fixture.js', 'tests\fixture.js', 'fixtures\fixture.js', '.codex\fixture.js', 'logs\fixture.js', 'reports\fixture.js', 'backups\fixture.js', 'tmp\fixture.js', 'temp\fixture.js', 'node_modules\fixture.js', 'build\fixture.js')) {
            $path = Join-Path $script:scanContractRoot $relative
            New-Item -ItemType Directory -Force -Path (Split-Path -Parent $path) | Out-Null
            Set-Content -LiteralPath $path -Value 'console.log("scan contract");'
        }
    }

    AfterAll {
        if (Test-Path -LiteralPath $script:scanContractRoot) { Remove-Item -LiteralPath $script:scanContractRoot -Recurse -Force }
    }

    It 'allowlists every non-production directory required by the workflow' {
        $globs = Get-ProductionScanExcludeGlobs
        foreach ($expected in @('!**/vendor/**', '!**/tests/**', '!**/fixtures/**', '!**/.git/**', '!**/.codex/**', '!**/logs/**', '!**/reports/**', '!**/backups/**', '!**/tmp/**', '!**/temp/**')) {
            ($globs -contains $expected) | Should Be $true
        }
    }

    It 'finds a production violation but excludes fixtures and development artifacts' {
        $matches = @(Find-ProductionScanMatches -Root $script:scanContractRoot -Pattern 'console\.log')
        $matches.Count | Should Be 1
        $matches[0] | Should Match 'src[\\/]valid\.js'
    }

    It 'keeps PHPUnit cache out of stand deployment' {
        $syncScript = Get-Content -LiteralPath (Join-Path $PSScriptRoot '..\Sync-To-Stand.ps1') -Raw
        $syncScript | Should Match "'\.phpunit\.cache'"
    }
}
