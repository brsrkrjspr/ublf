# PowerShell script to start PHP server
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$publicPath = Join-Path $scriptPath "htdocs\public"

# Try to find PHP
$phpPath = $null

# Check if PHP is in PATH
try {
    $null = Get-Command php -ErrorAction Stop
    $phpPath = "php"
} catch {
    # Check common XAMPP locations
    $xamppPaths = @(
        "C:\xampp\php\php.exe",
        "D:\xampp\php\php.exe",
        "C:\Program Files\xampp\php\php.exe"
    )
    
    foreach ($path in $xamppPaths) {
        if (Test-Path $path) {
            $phpPath = $path
            break
        }
    }
    
    # Check Program Files
    if (-not $phpPath) {
        $programFilesPath = "C:\Program Files\PHP\php.exe"
        if (Test-Path $programFilesPath) {
            $phpPath = $programFilesPath
        }
    }
}

if (-not $phpPath) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "ERROR: PHP not found!" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install PHP or XAMPP:" -ForegroundColor Yellow
    Write-Host "- XAMPP: https://www.apachefriends.org/" -ForegroundColor Cyan
    Write-Host "- PHP: https://www.php.net/downloads.php" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Or add PHP to your system PATH." -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

# If PHP not found, try XAMPP directly
if (-not $phpPath) {
    $xamppPhp = "C:\xampp\php\php.exe"
    if (Test-Path $xamppPhp) {
        $phpPath = $xamppPhp
    }
}

if (-not $phpPath) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "ERROR: PHP not found!" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install PHP or XAMPP:" -ForegroundColor Yellow
    Write-Host "- XAMPP: https://www.apachefriends.org/" -ForegroundColor Cyan
    Write-Host "- PHP: https://www.php.net/downloads.php" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Or add PHP to your system PATH." -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "Starting PHP Server on http://localhost:8000" -ForegroundColor Green
Write-Host "PHP Path: $phpPath" -ForegroundColor Gray
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host ""

Set-Location $publicPath
& $phpPath -S localhost:8000

