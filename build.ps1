##############################################################
#  build.ps1 -- Genere techrappy-seo.zip pour WordPress
#  Usage direct : powershell -ExecutionPolicy Bypass -File build.ps1
#  (aussi appele automatiquement par build.bat)
##############################################################
param(
    [string]$SourceDir = $PSScriptRoot,
    [string]$OutDir    = $PSScriptRoot
)

Add-Type -Assembly System.IO.Compression
Add-Type -Assembly System.IO.Compression.FileSystem

$FOLDER   = 'techrappy-seo'
$ZIP_WP   = Join-Path $OutDir 'techrappy-seo.zip'
$ZIP_CP   = Join-Path $OutDir 'techrappy-seo-cpanel.zip'

# Fichiers et dossiers a inclure
$FILES = @('techrappy-seo.php', 'index.php', 'uninstall.php')
$DIRS  = @('includes', 'views', 'assets', 'database', 'languages')

# --- Supprime les anciens zips ---
foreach ($z in @($ZIP_WP, $ZIP_CP)) {
    if (Test-Path $z) { Remove-Item -Force $z }
}

# --- Cree les deux zips ---
$zipWP = [System.IO.Compression.ZipFile]::Open($ZIP_WP, 'Create')
$zipCP = [System.IO.Compression.ZipFile]::Open($ZIP_CP, 'Create')

function Add-ToZip {
    param($zip, $filePath, $entryName)
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
        $zip, $filePath, $entryName,
        [System.IO.Compression.CompressionLevel]::Optimal
    ) | Out-Null
}

# Fichiers a la racine
foreach ($f in $FILES) {
    $full = Join-Path $SourceDir $f
    if (Test-Path $full) {
        Add-ToZip $zipWP $full "$FOLDER/$f"
        Add-ToZip $zipCP $full $f
    } else {
        Write-Warning "Fichier manquant : $f"
    }
}

# Dossiers (recursif)
foreach ($d in $DIRS) {
    $dirPath = Join-Path $SourceDir $d
    if (-not (Test-Path $dirPath)) {
        Write-Warning "Dossier manquant : $d"
        continue
    }
    Get-ChildItem -Recurse -File $dirPath | ForEach-Object {
        $rel = $_.FullName.Substring($dirPath.Length).TrimStart('\', '/')
        $rel = $rel.Replace('\', '/')
        Add-ToZip $zipWP $_.FullName "$FOLDER/$d/$rel"
        Add-ToZip $zipCP $_.FullName "$d/$rel"
    }
}

$zipWP.Dispose()
$zipCP.Dispose()

# --- Verification ---
$z = [System.IO.Compression.ZipFile]::OpenRead($ZIP_WP)
$entry = $z.Entries | Where-Object { $_.FullName -eq "$FOLDER/techrappy-seo.php" }
$count = $z.Entries.Count
$z.Dispose()

if (-not $entry) {
    Write-Error "ERREUR : structure ZIP incorrecte ! '$FOLDER/techrappy-seo.php' introuvable."
    exit 1
}

Write-Host ""
Write-Host " OK ! Archives creees ($count fichiers dans le zip WP) :"
Write-Host "   $ZIP_WP"
Write-Host "   $ZIP_CP"
Write-Host ""
Write-Host " [1] $FOLDER.zip -- Installation via WordPress Admin :"
Write-Host "     Extensions -- Supprimer l'ancienne version d'abord !"
Write-Host "     Puis : Extensions -- Ajouter -- Telecharger -- Installer -- Activer"
Write-Host ""
Write-Host " [2] $FOLDER-cpanel.zip -- Via Terminal cPanel :"
Write-Host "     cd ~/SITE/wp-content/plugins"
Write-Host "     rm -rf $FOLDER"
Write-Host "     unzip $FOLDER-cpanel.zip -d $FOLDER"
Write-Host ""
