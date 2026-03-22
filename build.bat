@echo off
REM ============================================================
REM  build.bat -- Genere techrappy-seo.zip pour WordPress
REM  Double-cliquez sur ce fichier ou executez-le dans CMD.
REM ============================================================
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build.ps1" -SourceDir "%~dp0" -OutDir "%~dp0"
pause
