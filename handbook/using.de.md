---
title: Das Handbuch benutzen
section: Lesen
---

# Das Handbuch benutzen

Das Handbuch sammelt die Bedienungsanleitungen der Erweiterungen auf Ihrer Site. Sie öffnen es über das Menü Support › Handbuch.

## Die Startseite

Die Startseite zeigt für jede Anleitung eine Karte. Eine Karte listet die Übersichtsseite der Anleitung, ihre Themen und ihre Seiten.

Hat eine Anleitung ein Problem, steht es oben auf der Startseite unter „Fehler im Handbuch:“. Liefert keine Erweiterung eine Anleitung, zeigt die Startseite „Es sind noch keine Anleitungen vorhanden.“

Ein Link auf eine Seite, die es nicht gibt, führt zur Startseite mit der Meldung „Diese Handbuchseite gibt es nicht.“

## Inhaltsverzeichnis

Das Inhaltsverzeichnis steht neben jeder Seite. Jede Anleitung ist eine Gruppe, die Sie mit einem Klick auf ihren Namen oder mit der Tastatur auf- und zuklappen. Offen ist die Gruppe der Seite, die Sie gerade lesen.

Der erste Eintrag einer Gruppe, „Übersicht“, führt zur Übersichtsseite der Anleitung, falls sie eine hat. Kleine Überschriften teilen längere Anleitungen in Themen.

Auf einem breiten Bildschirm bleibt das Inhaltsverzeichnis beim Scrollen sichtbar. Auf einem schmalen Bildschirm steht es zuerst, über der Seite.

## Eine Seite lesen

Ein Pfad über der Seite zeigt, wo Sie sind: Handbuch › Anleitung › Thema. Der Name der Anleitung führt zu ihrer Übersichtsseite.

„Auf dieser Seite“ listet die Überschriften der Seite. Auf einem sehr breiten Bildschirm steht die Liste rechts neben dem Text und bleibt beim Scrollen sichtbar. Sonst steht sie über dem Text.

Unten führen „Vorherige Seite“ und „Nächste Seite“ in der Reihenfolge des Inhaltsverzeichnisses durch alle Anleitungen.

## Suchen

Das Suchfeld steht auf jeder Handbuchseite. Die Suche findet jeden Abschnitt, der Ihren Begriff enthält, unabhängig von Groß- und Kleinschreibung.

Die Treffer sind nach Seiten gruppiert. Zu jeder Seite stehen ihre Anleitung und ihr Thema, dann bis zu fünf passende Abschnitte. Jeder Abschnitt zeigt die erste Zeile, die den Begriff enthält, und führt direkt zu diesem Abschnitt. Eine Zeile wie „und 3 weitere Abschnitte“ zählt den Rest. Die Zeile über den Treffern zählt die gefundenen Abschnitte und Seiten.

Die Suche liest den Quelltext einer Seite Zeile für Zeile. Einen Ausdruck, den die Autor:innen auf zwei Zeilen umbrochen haben, findet sie deshalb nicht. Die passende Zeile erscheint als reiner Text, ohne Formatierung.

![Suchfeld](images/search.png)

## Links auf anderen Seiten

Gehört eine Anleitung zu einer CiviCRM-Seite, erscheint oben auf dieser Seite ein Link wie „Handbuch: Grüße versenden“. Wie Sie das einrichten, steht in [Anleitungen schreiben](writing-manuals.md#links-auf-civicrm-seiten).

## Sprachen

Das Handbuch zeigt jede Seite in Ihrer CiviCRM-Sprache, wenn die Anleitung eine Übersetzung mitliefert, sonst in ihrer Standardsprache. Die Suche durchsucht dieselben Seiten, die Sie sehen.

## Berechtigung

Wer das Handbuch lesen möchte, braucht die Berechtigung `access handbook`. In der Liste der Berechtigungen heißt sie „Handbuch: Anleitungen lesen“. Sie gilt für die Handbuchseiten, die Suche, die Bilder und die Links auf anderen Seiten. Die Berechtigung steht in der eigenen Liste von CiviCRM, Sie können sie also auf jedem CMS Rollen zuweisen, auch unter Standalone.

## Zusätzliches Verzeichnis

Anleitungen, die zu keiner Erweiterung gehören, können in einem eigenen Verzeichnis auf dem Server liegen. Den absoluten Pfad tragen Sie in die Einstellung `handbook_extra_dir` ein, die „Zusätzliches Handbuch-Verzeichnis“ heißt. Sie steht auf der Seite `civicrm/admin/setting/handbook`. Für diese Seite brauchen Sie die Berechtigung, CiviCRM zu verwalten. Sie können sie auch auf der Kommandozeile setzen:

```bash
cv setting:set handbook_extra_dir=/srv/manuals
```

Die Seiten im Verzeichnis folgen den Regeln aus [Anleitungen schreiben](writing-manuals.md). Sie bilden eine Anleitung namens „Weitere Anleitungen“, sofern ihre Startseite ihr keinen Titel gibt. Ihre Seiten-IDs beginnen mit extra. Die Einstellung ist anfangs leer.

Ein relativer Pfad lässt sich nicht speichern. Kann das Handbuch die Einstellung nicht verwenden, zeigt die Startseite einen dieser Fehler:

- Das zusätzliche Handbuch-Verzeichnis „…“ ist kein lesbares Verzeichnis.
- Das zusätzliche Handbuch-Verzeichnis wird nicht gelesen: eine Erweiterung heißt bereits „extra“.
- Das zusätzliche Handbuch-Verzeichnis muss ein absoluter Pfad sein (beginnt mit /), nicht „…“.

Die letzte Meldung erscheint, wenn die Einstellung trotzdem einen relativen Pfad enthält.

Alle, die die Berechtigung `access handbook` haben, können jede Markdown-Datei und jedes Bild in diesem Verzeichnis lesen. Richten Sie die Einstellung deshalb auf ein Verzeichnis, das nur für Anleitungen gedacht ist.
