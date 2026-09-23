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
„Übersicht“ ganz oben in ihrer Gruppe.

## Seiten verknüpfen

Unter `routes` stehen die CiviCRM-Pfade, auf denen ein Link zu dieser Anleitung
erscheinen soll. Links auf andere Seiten schreiben Sie als relativen Pfad, etwa
[zurück zur Übersicht](benutzen.md).

## Bilder

Bilder liegen neben den Markdown-Dateien und werden relativ eingebunden.

## Wann Änderungen sichtbar werden

Geänderter Text erscheint sofort. Neue oder umbenannte Dateien und geänderte
Kopfdaten erscheinen erst, nachdem der Cache von CiviCRM geleert wurde, etwa
mit `cv flush`.
