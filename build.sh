#!/usr/bin/env bash
# ============================================================
#  build.sh — Génère techrappy-seo.zip pour WordPress
#  Usage : bash build.sh
# ============================================================

set -e

ZIP_WP="techrappy-seo.zip"
ZIP_CPANEL="techrappy-seo-cpanel.zip"
FOLDER="techrappy-seo"
SAVED_CD="$(pwd)"
BUILD_DIR="$(mktemp -d)/techrappy-seo-build"

echo ""
echo "[1/3] Nettoyage du dossier temporaire..."
mkdir -p "$BUILD_DIR/$FOLDER"

echo "[2/3] Copie des fichiers du plugin..."
cp techrappy-seo.php index.php uninstall.php "$BUILD_DIR/$FOLDER/"
cp -r includes views assets database languages "$BUILD_DIR/$FOLDER/"

echo "[3/3] Création des archives..."
rm -f "$SAVED_CD/$ZIP_WP" "$SAVED_CD/$ZIP_CPANEL"

# IMPORTANT : cd dans BUILD_DIR + chemin relatif pour éviter tout dossier parent dans l'archive
# ZIP 1 : avec dossier techrappy-seo/ à la racine (pour WordPress Admin)
(cd "$BUILD_DIR" && zip -r9 "$SAVED_CD/$ZIP_WP" "$FOLDER/")

# ZIP 2 : fichiers directement à la racine (pour cPanel)
(cd "$BUILD_DIR/$FOLDER" && zip -r9 "$SAVED_CD/$ZIP_CPANEL" .)

echo ""
echo " OK ! Archives créées dans : $SAVED_CD"
echo ""
echo " [1] $ZIP_WP — WordPress Admin :"
echo "     Extensions > Ajouter > Télécharger une extension"
echo "     Choisir ce fichier > Installer > Activer"
echo ""
echo " [2] $ZIP_WP — Site avec ancienne installation (via Terminal cPanel) :"
echo "     1. Uploader $ZIP_WP dans /wp-content/plugins/ via cPanel"
echo "     2. Dans Terminal cPanel (remplacer SITE par le domaine) :"
echo "        SITE=chapeau-media.fr"
echo "        rm -rf ~/\$SITE/wp-content/plugins/techrappy-seo"
echo "        cd ~/\$SITE/wp-content/plugins && umask 022 && unzip techrappy-seo.zip && rm techrappy-seo.zip"
echo "     3. Activer depuis wp-admin/plugins.php"
echo ""

# Nettoyage
rm -rf "$(dirname "$BUILD_DIR")"
