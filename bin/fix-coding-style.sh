#!/bin/bash

# Skript zum Korrigieren von Coding-Style-Problemen
# =================================================
#
# Dieses Skript führt folgende Korrekturen durch:
# 1. Konvertiert Spaces zu Tabs in PHP-, JS- und CSS-Dateien
# 2. Führt den PHP Code Beautifier (PHPCBF) aus, um weitere Formatierungsprobleme zu beheben
#
# Hinweis: Manche Probleme können nicht automatisch behoben werden und müssen manuell korrigiert werden.
# Dazu gehören:
# - Inline-Kommentare, die nicht mit einem Satzzeichen enden
# - Fehlerhafte HTML-Formatierung in Template-Strings
# - Komplexe Einrückungsprobleme in JS-Dateien

echo "Korrigiere Coding-Style-Probleme..."
echo "==================================="

echo -e "\n1. Konvertiere Spaces zu Tabs in relevanten Dateien..."
# Finde alle PHP-, JS- und CSS-Dateien, die nicht in ignorierten Verzeichnissen liegen
find . -type f \( -name "*.php" -o -name "*.js" -o -name "*.css" \) \
    ! -path "./vendor/*" \
    ! -path "./node_modules/*" \
    ! -path "./assets/*" \
    ! -path "./tests/*" \
    ! -path "./build/*" \
    ! -path "./languages/*" \
    -exec bash -c 'echo "  - $1"; expand -t 4 "$1" | unexpand -t 4 > "$1.tmp" && mv "$1.tmp" "$1"' -- {} \;

echo -e "\n2. Führe PHPCBF aus, um weitere Formatierungsprobleme zu beheben..."
composer run phpcbf

echo -e "\nHinweis: Nicht alle Probleme konnten automatisch behoben werden."
echo "Bitte führe 'composer run phpcs' aus, um verbleibende Probleme zu prüfen."
echo "Fertig! Die Code-Formatierung wurde verbessert."
