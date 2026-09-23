---
title: Anleitungen schreiben
order: 2
section: Schreiben
---

# Anleitungen schreiben

Eine Erweiterung liefert Anleitungen, indem sie ein Verzeichnis `handbook/` mit
Markdown-Dateien enthält. Jede Datei ist eine Seite.

## Kopfdaten

Am Anfang einer Datei stehen optional Kopfdaten zwischen zwei Zeilen `---`:

```yaml
title: Veranstaltungen anlegen
order: 10
routes:
  - civicrm/event/manage
```

Ohne `title` gilt die erste Überschrift, sonst der Dateiname.

## Seiten verknüpfen

Unter `routes` stehen die CiviCRM-Pfade, auf denen ein Link zu dieser Anleitung
erscheinen soll. Links auf andere Seiten schreiben Sie als relativen Pfad, etwa
[zurück zur Übersicht](benutzen.md).

## Bilder

Bilder liegen neben den Markdown-Dateien und werden relativ eingebunden.
