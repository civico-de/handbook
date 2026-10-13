---
title: Anleitungen schreiben
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
section: Veranstaltungen
routes:
  - civicrm/event/manage
```

Ohne `title` gilt die erste Überschrift, sonst der Dateiname. `order` legt die
Reihenfolge fest.

## Themen

Seiten mit demselben `section` stehen im Inhaltsverzeichnis zusammen unter
diesem Namen. Ein Thema steht dort, wo seine erste Seite nach `order`
einsortiert ist. Seiten ohne `section` bilden eine eigene Gruppe ohne Namen.

Praktisch ist, Themen in Zehnerschritten zu nummerieren: `order` 10 bis 19
für das erste Thema, 20 bis 29 für das zweite.

Eine Datei `index.md` ist die Startseite der Erweiterung. Sie steht als
„Übersicht“ ganz oben in ihrer Gruppe, und ihr Titel ist der Name der Gruppe.

## Seiten verknüpfen

Unter `routes` stehen die CiviCRM-Pfade, auf denen ein Link zu dieser Anleitung
erscheinen soll. Links auf andere Seiten schreiben Sie als relativen Pfad, etwa
[zurück zum Lesen](using.md).

## Bilder

Bilder liegen neben den Markdown-Dateien und werden relativ eingebunden.

## Sprachen

Die Datei `events.md` ist die Seite in der Standardsprache. Übersetzungen
liegen daneben als `events.de.md` für Deutsch oder `events.de_DE.md` für
Deutsch in Deutschland. Das Handbuch zeigt die Fassung, die zur
CiviCRM-Sprache der Lesenden passt: erst die mit dem vollen Gebietsschema,
dann die mit der Sprache, sonst `events.md`. Alle Fassungen haben dieselbe Seiten-ID, Links
wie `events.md` gelten also für jede Sprache.

Kopfdaten einer Übersetzung ersetzen die der Standardseite; was fehlt, wird
übernommen. Meist genügen `title` und `section`. Bilder teilen sich alle
Fassungen.

## Wann Änderungen sichtbar werden

Geänderter Text erscheint sofort. Neue oder umbenannte Dateien und geänderte
Kopfdaten erscheinen erst, nachdem der Cache von CiviCRM geleert wurde, etwa
mit `cv flush`. Das gilt auch für einen Titel, der aus der ersten Überschrift
`# ` stammt.
