param(
    [string]$BackupDir = "./backups"
)
$Stamp = Get-Date -Format "yyyyMMdd-HHmmss"
New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null
& mysqldump -h $env:DB_HOST -u $env:DB_USER -p$env:DB_PASS $env:DB_NAME | Out-File -Encoding utf8 "$BackupDir/db-$Stamp.sql"
if (Test-Path "./uploads") {
    Compress-Archive -Path "./uploads" -DestinationPath "$BackupDir/uploads-$Stamp.zip" -Force
}
Write-Output "Backup written to $BackupDir"