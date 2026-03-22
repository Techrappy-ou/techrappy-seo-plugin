@echo off
REM ============================================================
REM  build.bat — Génère techrappy-seo.zip pour WordPress
REM  Double-cliquez sur ce fichier ou exécutez-le dans CMD.
REM ============================================================

setlocal

set ZIP_WP=techrappy-seo.zip
set ZIP_CPANEL=techrappy-seo-cpanel.zip
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

echo [3/3] Creation des archives...

REM Supprimer les zips précédents si existants
if exist "%ZIP_WP%"     del /f /q "%ZIP_WP%"
if exist "%ZIP_CPANEL%" del /f /q "%ZIP_CPANEL%"

REM --- ZIP 1 : pour upload via WordPress Admin ---
REM Le dossier techrappy-seo/ est INCLUS dans l'archive (standard WP)
powershell -NoProfile -Command ^
  "Compress-Archive -Path '%BUILD_DIR%\%FOLDER%' -DestinationPath '%CD%\%ZIP_WP%' -Force"

REM --- ZIP 2 : pour installation manuelle via cPanel ---
REM Les fichiers sont directement a la racine de l'archive (sans dossier wrapper)
REM => Evite la double imbrication quand cPanel cree automatiquement un sous-dossier
powershell -NoProfile -Command ^
  "Compress-Archive -Path '%BUILD_DIR%\%FOLDER%\*' -DestinationPath '%CD%\%ZIP_CPANEL%' -Force"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo  OK ! Archives creees :
    echo.
    echo  [1] %ZIP_WP%
    echo      Via WordPress Admin (recommande) :
    echo        Extensions ^> Ajouter ^> Telecharger une extension
    echo        Choisir ce fichier ^> Installer ^> Activer
    echo.
    echo  [2] %ZIP_CPANEL%
    echo      Via cPanel Gestionnaire de fichiers :
    echo        1. Supprimer le dossier /wp-content/plugins/techrappy-seo/
    echo        2. Naviguer dans /wp-content/plugins/
    echo        3. Creer un dossier "techrappy-seo"
    echo        4. Naviguer DANS ce nouveau dossier
    echo        5. Uploader %ZIP_CPANEL% ici
    echo        6. Extraire ici (les fichiers s'installent sans double imbrication)
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
