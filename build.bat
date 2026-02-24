@echo off
REM ============================================================
REM  build.bat — Génère techrappy-seo.zip pour WordPress
REM  Double-cliquez sur ce fichier ou exécutez-le dans CMD.
REM ============================================================

setlocal

set ZIP_NAME=techrappy-seo.zip
set FOLDER=techrappy-seo
set BUILD_DIR=%TEMP%\techrappy-seo-build

echo.
echo [1/3] Nettoyage du dossier temporaire...
if exist "%BUILD_DIR%" rd /s /q "%BUILD_DIR%"
mkdir "%BUILD_DIR%\%FOLDER%"

echo [2/3] Copie des fichiers du plugin...

REM Fichiers racine
copy /Y "techrappy-seo.php"  "%BUILD_DIR%\%FOLDER%\" >nul
copy /Y "index.php"          "%BUILD_DIR%\%FOLDER%\" >nul
copy /Y "uninstall.php"      "%BUILD_DIR%\%FOLDER%\" >nul

REM Dossiers sources
xcopy /E /I /Y "includes"  "%BUILD_DIR%\%FOLDER%\includes"  >nul
xcopy /E /I /Y "views"     "%BUILD_DIR%\%FOLDER%\views"     >nul
xcopy /E /I /Y "assets"    "%BUILD_DIR%\%FOLDER%\assets"    >nul
xcopy /E /I /Y "database"  "%BUILD_DIR%\%FOLDER%\database"  >nul
xcopy /E /I /Y "languages" "%BUILD_DIR%\%FOLDER%\languages" >nul

echo [3/3] Création de l'archive %ZIP_NAME%...

REM Supprimer le zip précédent si existant
if exist "%ZIP_NAME%" del /f /q "%ZIP_NAME%"

REM Utilise PowerShell (disponible sur tout Windows 7+)
powershell -NoProfile -Command ^
  "Compress-Archive -Path '%BUILD_DIR%\%FOLDER%' -DestinationPath '%CD%\%ZIP_NAME%' -Force"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo  OK ! Archive creee : %CD%\%ZIP_NAME%
    echo.
    echo  Installation WordPress :
    echo    Extensions ^> Ajouter ^> Telecharger une extension
    echo    Choisir %ZIP_NAME% ^> Installer ^> Activer
    echo.
) else (
    echo.
    echo  ERREUR : la creation du zip a echoue.
    echo  Verifiez que PowerShell est disponible sur votre systeme.
    echo.
)

REM Nettoyage
rd /s /q "%BUILD_DIR%"

pause
endlocal
