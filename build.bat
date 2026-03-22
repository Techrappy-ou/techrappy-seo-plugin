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

REM Sauvegarder le répertoire courant AVANT toute opération
set SAVED_CD=%CD%

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
if exist "%SAVED_CD%\%ZIP_WP%"     del /f /q "%SAVED_CD%\%ZIP_WP%"
if exist "%SAVED_CD%\%ZIP_CPANEL%" del /f /q "%SAVED_CD%\%ZIP_CPANEL%"

REM IMPORTANT : on se place dans BUILD_DIR et on utilise un chemin RELATIF
REM pour éviter que Compress-Archive inclue des dossiers parents dans l'archive.

REM --- ZIP 1 : pour upload via WordPress Admin ---
REM Structure : techrappy-seo/ à la racine (standard WordPress)
powershell -NoProfile -Command ^
  "Set-Location '%BUILD_DIR%'; Compress-Archive -Path '.\%FOLDER%' -DestinationPath '%SAVED_CD%\%ZIP_WP%' -Force"

REM --- ZIP 2 : pour installation manuelle via cPanel ---
REM Structure : fichiers directement à la racine (sans dossier wrapper)
powershell -NoProfile -Command ^
  "Set-Location '%BUILD_DIR%'; Compress-Archive -Path '.\%FOLDER%\*' -DestinationPath '%SAVED_CD%\%ZIP_CPANEL%' -Force"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo  OK ! Archives creees dans : %SAVED_CD%
    echo.
    echo  [1] %ZIP_WP%
    echo      Methode recommandee - WordPress Admin :
    echo        Extensions ^> Ajouter ^> Telecharger une extension
    echo        Choisir ce fichier ^> Installer ^> Activer
    echo.
    echo  [2] %ZIP_CPANEL%
    echo      Methode manuelle - cPanel Gestionnaire de fichiers :
    echo        1. Supprimer /wp-content/plugins/techrappy-seo/ entierement
    echo        2. Naviguer dans /wp-content/plugins/
    echo        3. Creer un dossier "techrappy-seo"
    echo        4. Naviguer DANS ce dossier
    echo        5. Uploader %ZIP_CPANEL% ici puis Extraire
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
