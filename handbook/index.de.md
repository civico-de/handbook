---
title: Handbuch
---

# Handbuch

Das Handbuch zeigt die Bedienungsanleitungen Ihrer Erweiterungen direkt in CiviCRM. Jede aktivierte Erweiterung kann ihre Anleitung als Markdown-Dateien mitliefern. Das Handbuch sammelt sie in einem Inhaltsverzeichnis, stellt sie dar, durchsucht sie und verlinkt jede Seite von den CiviCRM-Seiten aus, die sie beschreibt. Liefert eine Anleitung eine Übersetzung mit, sehen die Leser:innen jede Seite in ihrer eigenen Sprache.

Wer in CiviCRM arbeitet, liest die Anleitungen dort, gleich neben den Seiten, die sie beschreiben. Entwickler:innen von Erweiterungen schreiben die Anleitungen in ihrem eigenen Repository, und dieselben Dateien können auch eine Dokumentations-Website speisen.

## Installation

Die unterstützten Versionen von CiviCRM, PHP und Smarty stehen in der Datei `info.xml` der Erweiterung. Laden Sie das Release-Archiv von der [Release-Seite](https://github.com/civico-de/handbook/releases) herunter, entpacken Sie es in das Erweiterungsverzeichnis von CiviCRM und aktivieren Sie die Erweiterung:

```bash
cv ext:enable handbook
```

Ein Git-Checkout enthält kein Verzeichnis `vendor/`. Führen Sie darin `composer install --no-dev` aus, bevor Sie die Erweiterung aktivieren. Ohne `vendor/` meldet der Systemstatus „Handbuch: Programmbibliotheken fehlen“, und die Handbuchseiten zeigen nur einen Hinweis, die Bibliotheken zu installieren. Der Rest der Site funktioniert weiter.

Geben Sie danach jeder Rolle, die die Anleitungen lesen soll, die Berechtigung `access handbook`.

## Erste Schritte

Öffnen Sie das Menü Support › Handbuch. Die Startseite listet jede Anleitung auf Ihrer Site, auch diese.

Um Ihrer eigenen Erweiterung eine Anleitung mitzugeben, legen Sie in ihrem Wurzelverzeichnis ein Verzeichnis namens `handbook` an, mit einer Markdown-Datei pro Seite:

```markdown
---
title: Getting started
order: 10
---

# Getting started

The greeter sends a greeting to every new contact.
```

Führen Sie `cv flush` aus, dann erscheint die Seite im Handbuch.

## Wie es weitergeht

- [Das Handbuch benutzen](using.md) erklärt die Startseite, das Inhaltsverzeichnis, die Suche, die Links auf anderen Seiten, die Berechtigung und das zusätzliche Verzeichnis.
- [Anleitungen schreiben](writing-manuals.md) beschreibt Dateinamen, Frontmatter, Themen, die Startseite, Links, Bilder, Übersetzungen und den Cache.
