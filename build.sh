#!/usr/bin/env bash
# ============================================================
#  build.sh — Génère techrappy-seo.zip pour WordPress
#  Usage : bash build.sh
# ============================================================

set -e

ZIP_NAME="techrappy-seo.zip"
FOLDER="techrappy-seo"
BUILD_DIR="$(mktemp -d)/techrappy-seo-build"

echo ""
echo "[1/3] Nettoyage du dossier temporaire..."
mkdir -p "$BUILD_DIR/$FOLDER"

echo "[2/3] Copie des fichiers du plugin..."
cp techrappy-seo.php index.php uninstall.php "$BUILD_DIR/$FOLDER/"
cp -r includes views assets database languages "$BUILD_DIR/$FOLDER/"

echo "[3/3] Création de l'archive $ZIP_NAME..."
rm -f "$ZIP_NAME"
(cd "$BUILD_DIR" && zip -r9 "$(pwd)/$ZIP_NAME" "$FOLDER/") \
  || (cd "$BUILD_DIR" && zip -r9 - "$FOLDER/" > "$(pwd)/$ZIP_NAME")

# On recopie dans le dossier du projet
cp "$BUILD_DIR/$ZIP_NAME" "$(pwd)/$ZIP_NAME" 2>/dev/null || true

echo ""
echo " OK ! Archive créée : $(pwd)/$ZIP_NAME"
echo ""
echo " Installation WordPress :"
echo "   Extensions > Ajouter > Télécharger une extension"
echo "   Choisir $ZIP_NAME > Installer > Activer"
echo ""

# Nettoyage
rm -rf "$BUILD_DIR"
