$dbPath = "c:\xamp\htdocs\noir\database\noir.sqlite"
$backupDir = "c:\xamp\htdocs\noir\database\backups"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFile = "$backupDir\noir_backup_$timestamp.sqlite"
$zipFile = "$backupDir\noir_backup_$timestamp.zip"

if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
}

if (Test-Path $dbPath) {
    Copy-Item $dbPath $backupFile
    Compress-Archive -Path $backupFile -DestinationPath $zipFile
    Remove-Item $backupFile
    Write-Host "Backup created successfully at $zipFile"
} else {
    Write-Host "Database file not found!"
}
