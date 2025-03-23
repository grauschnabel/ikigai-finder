# Coding Standards - Ikigai Finder

## Einleitung

Dieses Dokument beschreibt die Coding Standards für das Ikigai Finder-Plugin und wie man sicherstellt, dass der Code diesen Standards entspricht.

## WordPress Coding Standards

Das Ikigai Finder-Plugin folgt den [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/), mit einigen projektspezifischen Anpassungen.

Wichtige Punkte:
- Tabs für die Einrückung, keine Spaces
- Klammern immer auf neuen Zeilen
- Kommentare enden mit einem Satzzeichen (Punkt, Fragezeichen, Ausrufezeichen)
- Yoda-Bedingungen für Vergleiche

## Tools für die Code-Formatierung

### .editorconfig

Das Projekt enthält eine `.editorconfig`-Datei, die moderne Editoren automatisch verwenden, um die grundlegende Formatierung (Tabs vs. Spaces, Zeilenenden) einzuhalten.

### PHPCS & PHPCBF

Für die PHP-Codeformatierung verwenden wir PHP_CodeSniffer und die WordPress-Coding-Standards:

```bash
# Überprüfen der Codierungsstandards
composer run phpcs

# Automatische Korrektur von Formatierungsproblemen
composer run phpcbf
```

### Automatisches Fix-Skript

Ein automatisches Fix-Skript `bin/fix-coding-style.sh` hilft, häufige Formatierungsprobleme zu beheben:

```bash
# Ausführen des Skripts zur Korrektur von Formatierungsproblemen
composer run fix-style
```

Dieses Skript:
1. Konvertiert Spaces zu Tabs in allen relevanten Dateien
2. Führt PHPCBF aus, um weitere Formatierungsprobleme zu beheben

## Manuelle Korrekturen

Nicht alle Probleme können automatisch behoben werden. Häufige manuelle Korrekturen:

### 1. Kommentare mit Satzzeichen beenden

```php
// Falsch: Hier ist ein Kommentar ohne Satzzeichen
// Richtig: Hier ist ein Kommentar mit einem Satzzeichen.
```

### 2. Yoda-Bedingungen verwenden

```php
// Falsch
if ($variable === true) {
    // ...
}

// Richtig
if (true === $variable) {
    // ...
}
```

### 3. Template-Strings in JavaScript korrekt formatieren

```js
// Falsch
const html = `<div class = "test">Text</div>`;

// Richtig
const html = `<div class="test">Text</div>`;
```

## GitHub Actions CI

Der GitHub Actions Workflow prüft den Code automatisch auf Einhaltung der Coding Standards. Dies hilft, die Codequalität im Projekt konsistent zu halten.
