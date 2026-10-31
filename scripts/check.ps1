# Runs every automated check in order. Stops at the first failure.
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot

function Invoke-Step {
    param([string]$Name, [string]$Directory, [string]$Command)
    Write-Host "`n==> $Name" -ForegroundColor Cyan
    Push-Location $Directory
    try {
        cmd /c $Command
        if ($LASTEXITCODE -ne 0) {
            throw "FAILED: $Name (exit code $LASTEXITCODE)"
        }
    }
    finally {
        Pop-Location
    }
}

Invoke-Step "Backend: code style (Pint)"          "$root\backend"  "vendor\bin\pint --test"
Invoke-Step "Backend: static analysis (Larastan)" "$root\backend"  "vendor\bin\phpstan analyse --memory-limit=1G"
Invoke-Step "Backend: tests (PHPUnit, smartthink_test)" "$root\backend" "php artisan test"
Invoke-Step "Frontend: all checks"                "$root\frontend" "npm run check"

Write-Host "`nALL CHECKS PASSED" -ForegroundColor Green