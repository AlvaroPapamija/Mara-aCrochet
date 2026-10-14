# ==============================================================================
# Script de Despliegue Automatizado: Maraña Crochet -> XAMPP (Localhost)
# ==============================================================================

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "   DESPLIEGUE MARAÑA CROCHET ATELIER EN XAMPP (LOCALHOST) " -ForegroundColor Magenta
Write-Host "==========================================================" -ForegroundColor Cyan

$xamppPath = "C:\xampp"
$htdocsTarget = "$xamppPath\htdocs\marana-crochet"
$sqlScript = "$PSScriptRoot\database\marana_crochet.sql"

# 1. Verificar XAMPP
if (-not (Test-Path $xamppPath)) {
    Write-Error "No se encontró XAMPP en $xamppPath. Ajusta la ruta si está instalado en otra unidad."
    exit 1
}

# 2. Verificar/Iniciar MySQL si no está corriendo
$mysqlProcess = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not $mysqlProcess) {
    Write-Host "[1/5] Iniciando MySQL en XAMPP..." -ForegroundColor Yellow
    Start-Process -FilePath "$xamppPath\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=$xamppPath\mysql\bin\my.ini","--standalone" -WindowStyle Hidden
    Start-Sleep -Seconds 3
} else {
    Write-Host "[1/5] MySQL ya está en ejecución." -ForegroundColor Green
}

# 3. Verificar/Iniciar Apache si no está corriendo
$apacheProcess = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if (-not $apacheProcess) {
    Write-Host "[2/5] Iniciando Apache en XAMPP..." -ForegroundColor Yellow
    Start-Process -FilePath "$xamppPath\apache\bin\httpd.exe" -WorkingDirectory $xamppPath -WindowStyle Hidden
    Start-Sleep -Seconds 2
} else {
    Write-Host "[2/5] Apache ya está en ejecución." -ForegroundColor Green
}

# 4. Asegurar Base de Datos marana_crochet
Write-Host "[3/5] Verificando base de datos MySQL 'marana_crochet'..." -ForegroundColor Yellow
& "$xamppPath\mysql\bin\mysql.exe" -u root -e "source $sqlScript;"
if ($LASTEXITCODE -eq 0) {
    Write-Host "      Base de datos 'marana_crochet' sincronizada correctamente." -ForegroundColor Green
} else {
    Write-Warning "      No se pudo importar automáticamente el SQL. Revisa que MySQL esté activo."
}

# 5. Compilar Frontend React
Write-Host "[4/5] Compilando frontend React (Vite)..." -ForegroundColor Yellow
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Error "Error compilando el frontend. Revisa los mensajes de error arriba."
    exit 1
}

# 6. Desplegar en htdocs/marana-crochet
Write-Host "[5/5] Copiando archivos a $htdocsTarget..." -ForegroundColor Yellow
if (-not (Test-Path $htdocsTarget)) {
    New-Item -ItemType Directory -Force -Path $htdocsTarget | Out-Null
}

Copy-Item -Path "$PSScriptRoot\dist\*" -Destination $htdocsTarget -Recurse -Force
New-Item -ItemType Directory -Force -Path "$htdocsTarget\api" | Out-Null
Copy-Item -Path "$PSScriptRoot\api\*" -Destination "$htdocsTarget\api\" -Recurse -Force

Write-Host "==========================================================" -ForegroundColor Green
Write-Host "   ¡DESPLIEGUE COMPLETADO CON ÉXITO!" -ForegroundColor Green
Write-Host "   Frontend: http://localhost/marana-crochet/" -ForegroundColor Cyan
Write-Host "   API:      http://localhost/marana-crochet/api/status.php" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Green

# Abrir en el navegador por defecto
Start-Process "http://localhost/marana-crochet/"
