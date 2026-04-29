@echo off
REM Aroma ERP Deployment Script for Windows
REM Usage: deploy.bat [environment] [version]

setlocal enabledelayedexpansion

REM Configuration
set ENVIRONMENT=%1
if "%ENVIRONMENT%"=="" set ENVIRONMENT=production
set VERSION=%2
if "%VERSION%"=="" set VERSION=latest
set PROJECT_NAME=aroma-erp
set DOCKER_COMPOSE_FILE=docker-compose.yml
set BACKUP_DIR=C:\backup\aroma-erp
set LOG_FILE=C:\logs\aroma-erp-deploy.log

REM Create directories
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
if not exist "C:\logs" mkdir "C:\logs"

echo [%date% %time%] Starting deployment for %PROJECT_NAME% > "%LOG_FILE%"
echo [%date% %time%] Environment: %ENVIRONMENT% >> "%LOG_FILE%"
echo [%date% %time%] Version: %VERSION% >> "%LOG_FILE%"

REM Check if Docker is installed
docker --version >nul 2>&1
if errorlevel 1 (
    echo [%date% %time%] ERROR: Docker is not installed >> "%LOG_FILE%"
    echo ERROR: Docker is not installed
    exit /b 1
)

REM Check if Docker Compose is installed
docker-compose --version >nul 2>&1
if errorlevel 1 (
    echo [%date% %time%] ERROR: Docker Compose is not installed >> "%LOG_FILE%"
    echo ERROR: Docker Compose is not installed
    exit /b 1
)

echo [%date% %time%] Creating database backup... >> "%LOG_FILE%"
docker-compose exec -T db mysqldump -u root -p%DB_PASSWORD% aroma_erp > "%BACKUP_DIR%\db_backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%.sql"

echo [%date% %time%] Creating application backup... >> "%LOG_FILE%"
tar -czf "%BACKUP_DIR%\app_backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%.tar.gz" --exclude=node_modules --exclude=vendor --exclude=.git .

echo [%date% %time%] Pulling latest changes... >> "%LOG_FILE%"
git pull origin main

echo [%date% %time%] Building Docker images... >> "%LOG_FILE%"
docker-compose build --no-cache

echo [%date% %time%] Starting services... >> "%LOG_FILE%"
docker-compose up -d

echo [%date% %time%] Waiting for services to be ready... >> "%LOG_FILE%"
timeout /t 30 /nobreak >nul

echo [%date% %time%] Running Laravel setup... >> "%LOG_FILE%"

REM Install dependencies
docker-compose exec app composer install --no-dev --optimize-autoloader

REM Generate application key
docker-compose exec app php artisan key:generate --force

REM Run migrations
docker-compose exec app php artisan migrate --force

REM Clear caches
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

REM Optimize for production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

REM Set permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 755 /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/bootstrap/cache

echo [%date% %time%] Performing health check... >> "%LOG_FILE%"
curl -f http://localhost/health >nul 2>&1
if errorlevel 1 (
    echo [%date% %time%] ERROR: Health check failed >> "%LOG_FILE%"
    echo ERROR: Health check failed
    exit /b 1
) else (
    echo [%date% %time%] Health check passed >> "%LOG_FILE%"
)

echo [%date% %time%] Deployment completed successfully! >> "%LOG_FILE%"
echo [%date% %time%] Application is running at: http://localhost >> "%LOG_FILE%"
echo [%date% %time%] PhpMyAdmin is available at: http://localhost:8080 >> "%LOG_FILE%"
echo [%date% %time%] MailHog is available at: http://localhost:8025 >> "%LOG_FILE%"

echo [%date% %time%] Running containers: >> "%LOG_FILE%"
docker-compose ps >> "%LOG_FILE%"

echo Deployment completed successfully!
echo Application is running at: http://localhost
echo PhpMyAdmin is available at: http://localhost:8080
echo MailHog is available at: http://localhost:8025
