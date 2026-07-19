@echo off
REM Redeploy skinlookserver to shared hosting (see docs/DEPLOYMENT.md).
REM Run this from your LOCAL Windows machine — it builds assets locally (Node
REM never runs on the server) and then SSHes in to pull, install, migrate and
REM re-cache. Uses Windows' built-in ssh.exe/scp.exe/tar.exe — nothing extra
REM to install.
REM
REM Usage:
REM   deploy.bat              full deploy: build assets, upload, migrate, cache
REM   deploy.bat --no-assets  skip the local npm build + asset upload
REM   deploy.bat --seed       also run `php artisan db:seed` after migrating

setlocal enabledelayedexpansion
cd /d "%~dp0"

REM ---- fill these in once ----------------------------------------------------
set "SSH_USER=skinrkip"
set "SSH_HOST=business97.web-hosting.com"
set "SSH_PORT=21098"
set "REMOTE_PATH=/home/skinrkip/admapi.skinlookbd.com/skinlookbd-server"
set "PHP_BIN=php"
REM -----------------------------------------------------------------------------

set "WITH_ASSETS=1"
set "WITH_SEED=0"

:parse_args
if "%~1"=="" goto args_done
if /I "%~1"=="--no-assets" set "WITH_ASSETS=0"
if /I "%~1"=="--seed" set "WITH_SEED=1"
shift
goto parse_args
:args_done

if "%WITH_ASSETS%"=="1" (
    echo.
    echo ==^> Building frontend assets locally ^(Node never runs on the server^)
    call npm ci
    if errorlevel 1 goto :error

    call npm run build
    if errorlevel 1 goto :error

    echo.
    echo ==^> Packaging public\build for upload
    if exist build.tar.gz del /q build.tar.gz
    tar -czf build.tar.gz -C public build
    if errorlevel 1 goto :error

    echo.
    echo ==^> Uploading build.tar.gz to the server
    scp -P %SSH_PORT% build.tar.gz "%SSH_USER%@%SSH_HOST%:%REMOTE_PATH%/build.tar.gz"
    if errorlevel 1 goto :error
    del /q build.tar.gz
) else (
    echo.
    echo ==^> Skipping asset build ^(--no-assets^)
)

echo.
echo ==^> Running remote deploy steps over SSH
ssh -p %SSH_PORT% "%SSH_USER%@%SSH_HOST%" bash -s -- "%REMOTE_PATH%" "%PHP_BIN%" "%WITH_SEED%" "%WITH_ASSETS%" < deploy-remote.sh
if errorlevel 1 goto :error

echo.
echo ==^> Deploy complete
exit /b 0

:error
echo.
echo *** Deploy failed — see the error above. ***
exit /b 1
