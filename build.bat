@echo off
REM ============================================================
REM  build.bat -- Genere techrappy-seo.zip pour WordPress
REM  Double-cliquez sur ce fichier ou executez-le dans CMD.
REM ============================================================

setlocal

set ZIP_WP=techrappy-seo.zip
set ZIP_CPANEL=techrappy-seo-cpanel.zip
set FOLDER=techrappy-seo
set BUILD_DIR=%TEMP%\techrappy-seo-build
set SAVED_CD=%CD%

echo.
echo [1/3] Nettoyage du dossier temporaire...
if exist "%BUILD_DIR%" rd /s /q "%BUILD_DIR%"
mkdir "%BUILD_DIR%\%FOLDER%"

echo [2/3] Copie des fichiers du plugin...

copy /Y "techrappy-seo.php"  "%BUILD_DIR%\%FOLDER%\" >nul
copy /Y "index.php"          "%BUILD_DIR%\%FOLDER%\" >nul
copy /Y "uninstall.php"      "%BUILD_DIR%\%FOLDER%\" >nul

xcopy /E /I /Y "includes"  "%BUILD_DIR%\%FOLDER%\includes"  >nul
xcopy /E /I /Y "views"     "%BUILD_DIR%\%FOLDER%\views"     >nul
xcopy /E /I /Y "assets"    "%BUILD_DIR%\%FOLDER%\assets"    >nul
xcopy /E /I /Y "database"  "%BUILD_DIR%\%FOLDER%\database"  >nul
xcopy /E /I /Y "languages" "%BUILD_DIR%\%FOLDER%\languages" >nul

echo [3/3] Creation des archives...

if exist "%SAVED_CD%\%ZIP_WP%"     del /f /q "%SAVED_CD%\%ZIP_WP%"
if exist "%SAVED_CD%\%ZIP_CPANEL%" del /f /q "%SAVED_CD%\%ZIP_CPANEL%"

REM Compress-Archive avec chemin absolu : seul le nom du dossier (techrappy-seo\)
REM est inclus dans le zip, pas les dossiers parents. Structure correcte pour WP.
powershell -NoProfile -Command "Compress-Archive -Path '%BUILD_DIR%\%FOLDER%' -DestinationPath '%SAVED_CD%\%ZIP_WP%' -Force"
powershell -NoProfile -Command "Compress-Archive -Path '%BUILD_DIR%\%FOLDER%\*' -DestinationPath '%SAVED_CD%\%ZIP_CPANEL%' -Force"

if %ERRORLEVEL% NEQ 0 goto :error

echo.
echo  OK ! Archives creees dans : %SAVED_CD%
echo.
echo  [1] %ZIP_WP% -- Installation via WordPress Admin :
echo      IMPORTANT : si le plugin est deja installe, le desactiver et
echo      le supprimer d'abord (Extensions -- Supprimer), sinon double
echo      dossier et erreur "fichier introuvable".
echo      Ensuite : Extensions -- Ajouter -- Telecharger -- Installer -- Activer
echo.
echo  [2] %ZIP_CPANEL% -- Mise a jour via Terminal cPanel (recommande) :
echo      1. Uploader %ZIP_CPANEL% dans /wp-content/plugins/ via cPanel
echo      2. Dans Terminal cPanel :
echo         cd ~/NOM-DU-SITE/wp-content/plugins
echo         rm -rf techrappy-seo
echo         unzip %ZIP_CPANEL% -d techrappy-seo
echo         rm %ZIP_CPANEL%
echo.
goto :end

:error
echo.
echo  ERREUR : la creation du zip a echoue.
echo  Verifiez que PowerShell est disponible sur votre systeme.
echo.

:end
rd /s /q "%BUILD_DIR%" 2>nul
pause
endlocal
