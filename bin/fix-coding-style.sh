#!/bin/bash

# Skript zum Konvertieren von Spaces zu Tabs in den relevanten Dateien
# Dieses Skript korrigiert die häufigsten Fehler in den Code-Style-Checks

echo "Konvertiere Spaces zu Tabs in PHP-, JS- und CSS-Dateien..."

# Finde alle PHP-, JS- und CSS-Dateien, die nicht in vendor, node_modules oder anderen ignorierten Verzeichnissen liegen
find . -type f \( -name "*.php" -o -name "*.js" -o -name "*.css" \) \
    ! -path "./vendor/*" \
    ! -path "./node_modules/*" \
    ! -path "./assets/*" \
    ! -path "./tests/*" \
    ! -path "./build/*" \
    ! -path "./languages/*" \
    -exec bash -c 'echo "Bearbeite $1"; expand -t 4 "$1" | unexpand -t 4 > "$1.tmp" && mv "$1.tmp" "$1"' -- {} \;

echo "Führe PHPCBF aus, um weitere Formatierungsprobleme zu beheben..."
composer run phpcbf

echo "Fertig! Alle Dateien wurden verarbeitet."
