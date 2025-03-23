#!/bin/bash

# Deployment-Skript für WordPress.org

# Dieses Skript benötigt folgende Umgebungsvariablen:
# - SVN_USERNAME: Der WordPress.org-Benutzername
# - SVN_PASSWORD: Das WordPress.org-Passwort
# - PLUGIN_SLUG: Der Plugin-Slug auf WordPress.org

# Prüfen, ob die erforderlichen Umgebungsvariablen gesetzt sind
if [[ -z "$SVN_USERNAME" || -z "$SVN_PASSWORD" || -z "$PLUGIN_SLUG" ]]; then
  echo "FEHLER: Bitte stellen Sie sicher, dass SVN_USERNAME, SVN_PASSWORD und PLUGIN_SLUG als Umgebungsvariablen gesetzt sind."
  exit 1
fi

# Verzeichnisse festlegen
SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}/"
SVN_DIR="./svn"
PLUGIN_BUILD_DIR="./build"

# Aktuelles Verzeichnis speichern
CURRENT_DIR=$(pwd)

# Version aus Plugin-Hauptdatei auslesen
VERSION=$(grep -i "Version:" ${PLUGIN_BUILD_DIR}/wp_ikigai.php | awk -F' ' '{print $3}' | tr -d '\r\n')

if [ -z "$VERSION" ]; then
  echo "FEHLER: Konnte Version nicht aus wp_ikigai.php ermitteln."
  exit 1
fi

echo "Bereite Deployment für Version ${VERSION} vor..."

# Prüfen, ob diese Version bereits existiert
svn ls "${SVN_URL}tags/${VERSION}/" > /dev/null 2>&1
if [ $? -eq 0 ]; then
  echo "FEHLER: Version ${VERSION} existiert bereits im WordPress.org-Repository."
  exit 1
fi

# SVN-Repository auschecken
echo "Checke SVN-Repository aus..."
svn co --quiet "$SVN_URL" "$SVN_DIR"

# Bestehende Trunk-Dateien löschen (außer .svn)
echo "Lösche alte Trunk-Dateien..."
cd "$SVN_DIR/trunk"
find . -type f -not -path "*.svn*" -delete
find . -type d -empty -not -path "*.svn*" -delete

# Neue Dateien in den Trunk kopieren
echo "Kopiere neue Dateien in den Trunk..."
cd "$CURRENT_DIR"
rsync -a --exclude=".git" --exclude=".github" --exclude=".svn" "$PLUGIN_BUILD_DIR/" "$SVN_DIR/trunk/"

# Dateien für SVN hinzufügen oder entfernen
cd "$SVN_DIR"
echo "Aktualisiere SVN..."
svn add --force trunk/* --quiet
svn stat | grep '^!' | awk '{print $2}' | xargs -I% svn delete % --quiet

# Commit zum Trunk
echo "Committe zum Trunk..."
svn ci --no-auth-cache --username "$SVN_USERNAME" --password "$SVN_PASSWORD" -m "Update auf Version $VERSION" --quiet

# Erstelle neue Tag-Version
echo "Erstelle Tag für Version $VERSION..."
svn cp "$SVN_URL/trunk" "$SVN_URL/tags/$VERSION" --message "Tagging Version $VERSION" --username "$SVN_USERNAME" --password "$SVN_PASSWORD" --quiet

# Aufräumen
echo "Räume auf..."
cd "$CURRENT_DIR"
rm -rf "$SVN_DIR"

echo "Deployment abgeschlossen!"
echo "Plugin wurde auf WordPress.org deployt: https://wordpress.org/plugins/${PLUGIN_SLUG}/"
