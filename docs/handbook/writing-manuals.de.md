---
title: Anleitungen schreiben
section: Schreiben
---

# Anleitungen schreiben

Diese Seite richtet sich an Entwickler:innen, die ihrer Erweiterung eine Anleitung mitgeben möchten. Sie beschreibt Dateinamen, Frontmatter, die Reihenfolge der Seiten, Links, Bilder und Übersetzungen.

## Eine Anleitung anlegen

Legen Sie in Ihrer Erweiterung das Verzeichnis `docs/handbook` an. Darin liegt für jede Seite der Anleitung eine Markdown-Datei:

```
org.example.greeter/
  docs/
    handbook/
      index.md
      getting-started.md
      getting-started.de.md
      greetings.md
      setup/
        index.md
        permissions.md
      images/
        greeting-form.png
    architecture.md
```

Das Handbuch liest die Markdown-Dateien in diesem Verzeichnis und in seinen Unterverzeichnissen. Ein Unterverzeichnis mit Seiten ist ein [Thema](#themen), eines ohne, wie `images`, ist der Platz für Bilder. Der Rest von `docs`, etwa `architecture.md`, gehört nicht zur Anleitung: Dort steht die Dokumentation für Entwickler:innen. Dateien und Verzeichnisse, deren Name mit einem Punkt beginnt, werden übersprungen. Anleitungen deaktivierter Erweiterungen erscheinen nicht.

Liegt die Anleitung Ihrer Erweiterung in einem anderen Verzeichnis, etwa `manual`, nennen Sie es mit einem Tag in der `info.xml`. Das Handbuch liest dann dieses Verzeichnis statt `docs/handbook`, nach denselben Regeln:

```xml
<tags>
  <tag>handbook:dir=manual</tag>
</tags>
```

Das Verzeichnis muss innerhalb der Erweiterung liegen und darf nicht ihr Wurzelverzeichnis sein. Ein Verzeichnis, das es nicht gibt, oder ein zweiter solcher Tag erscheint als Fehler auf der Startseite des Handbuchs. Führen Sie nach einer Änderung des Tags `cv flush` aus.

Ein Dateiname darf nur Buchstaben ohne Akzente oder Umlaute, Ziffern, Punkte, Bindestriche und Unterstriche enthalten und muss mit einem Buchstaben oder einer Ziffer beginnen. Ein Name wie `getting-started.de.md` ist keine eigene Seite, sondern eine Übersetzung. Mehr dazu unter [Sprachen](#sprachen).

Jede Seite hat eine ID aus dem Schlüssel der Erweiterung und dem Dateinamen ohne Endung, etwa org.example.greeter/greetings. Ihre Adresse in CiviCRM ist `civicrm/handbook?p=org.example.greeter/greetings`.

Führen Sie `cv flush` aus, nachdem Sie eine Datei hinzugefügt oder umbenannt haben. Danach erscheinen die neuen Seiten unter Support › Handbuch.

## Frontmatter

Eine Seite kann mit Frontmatter beginnen, einem YAML-Block zwischen zwei Zeilen aus drei Bindestrichen. Er muss ganz am Anfang der Datei stehen, und jeder Schlüssel darin ist optional.

```yaml
---
title: Sending greetings
order: 20
section: Greetings
routes:
  - civicrm/greeter/send
---
```

| Schlüssel | Wert | Wirkung |
|---|---|---|
| `title` | Text | Der Titel der Seite im Inhaltsverzeichnis, im Pfad über der Seite, in den Suchergebnissen und in Links. Fehlt er, gilt die erste Überschrift der ersten Ebene. Fehlt auch sie, gilt der Dateiname. |
| `order` | ganze Zahl | Die Position der Seite in ihrer Anleitung, die kleinste zuerst. Seiten ohne Angabe folgen nach den nummerierten. Seiten mit derselben Zahl sind nach Titel sortiert, ohne Rücksicht auf Groß- und Kleinschreibung. |
| `section` | Text | Das Thema, unter dem die Seite im Inhaltsverzeichnis steht. Leerzeichen um den Namen zählen nicht, und ein leerer Wert bedeutet kein Thema. |
| `routes` | Liste von Pfaden | Die CiviCRM-Seiten, die einen Link auf diese Seite zeigen. Mehr dazu unter [Links auf CiviCRM-Seiten](#links-auf-civicrm-seiten). |

Eine Datei, die das Handbuch nicht als Seite lesen kann, bleibt weg. Die Startseite des Handbuchs nennt das Problem dann oben unter „Fehler im Handbuch:“. Jede Meldung beginnt mit dem Schlüssel der Erweiterung und dem Dateinamen, gefolgt von einer dieser Angaben:

| Meldung | Ursache |
|---|---|
| „Frontmatter ist kein gültiges YAML“ | Das YAML lässt sich nicht lesen. Die Erklärung des Parsers folgt in Klammern. |
| „Frontmatter ist keine Zuordnung von Schlüsseln zu Werten.“ | Der Block enthält einen einzelnen Wert oder eine Liste statt Schlüsseln. |
| „title und section müssen Text, order eine ganze Zahl und routes eine Liste von Pfaden sein.“ | Ein Schlüssel hat den falschen Typ, etwa ein einzelner Pfad unter routes statt einer Liste. |
| „ungültiger Dateiname.“ | Der Dateiname verletzt die Regel unter [Eine Anleitung anlegen](#eine-anleitung-anlegen). |
| „Übersetzung ohne die Standardseite“ | Zu einer Übersetzung fehlt die Standardseite, siehe [Sprachen](#sprachen). |

## Themen

Lange Anleitungen lesen sich besser in Themen. Legen Sie die Seiten eines Themas in ein Unterverzeichnis, dann stehen sie im Inhaltsverzeichnis zusammen. Auch der Pfad über jeder Seite zeigt ihr Thema.

Das Thema trägt den Titel der `index.md` im Unterverzeichnis, ohne sie den Namen des Unterverzeichnisses. Diese `index.md` ist die erste Seite des Themas, und ihr `order` bestimmt, wo das Thema steht. Eine Seite in einem Unterverzeichnis hat das Verzeichnis in ihrer ID, etwa `org.example.greeter/setup/permissions`.

Der Frontmatter-Schlüssel `section` benennt ein Thema ohne Unterverzeichnis, und auf einer Seite in einem Unterverzeichnis ersetzt er dessen Thema. Seiten mit demselben Themennamen stehen zusammen, wo auch immer ihre Dateien liegen.

Ein Thema ohne `index.md` steht dort, wo seine erste Seite einsortiert ist, nach Zahl und dann nach Titel. Seine Seiten behalten diese Reihenfolge untereinander. Seiten ohne Thema bilden eine Gruppe ohne Namen, die genauso eingeordnet wird.

Wenn Sie die Seiten in Zehnerschritten nummerieren, bleiben die Themen getrennt. Die Zahlen 10 bis 19 für ein Thema namens Setup und 20 bis 29 für ein Thema namens Greetings ergeben zwei Themen in dieser Reihenfolge.

## Startseite und Reihenfolge

Die Datei `index.md` oben in der Anleitung ist ihre Startseite. Sie ist der erste Eintrag der Anleitung im Inhaltsverzeichnis, mit der Beschriftung „Übersicht“, und der Pfad über jeder anderen Seite führt zu ihr. Ihr Thema zählt nicht.

Der Titel der Startseite benennt die ganze Anleitung im Inhaltsverzeichnis, im Pfad über der Seite und in den Suchergebnissen. Ohne Startseite trägt die Anleitung den Namen der Erweiterung, beim [zusätzlichen Verzeichnis](using.md#zusätzliches-verzeichnis) den Namen „Weitere Anleitungen“.

Die Anleitungen stehen alphabetisch nach Namen. Um eine Anleitung vor die anderen zu setzen, geben Sie ihrer Startseite einen Wert für `order`. Anleitungen mit einer Zahl stehen zuerst, die kleinste vorn, und die übrigen folgen alphabetisch.

## Seite mit den Änderungen

Eine Erweiterung mit einer `CHANGELOG.md` in ihrem Hauptverzeichnis bekommt sie als letzte Seite ihrer Anleitung, mit dem Titel „Änderungen“. Die Datei erscheint unverändert, in jeder Sprache, und relative Links darin gehen von der obersten Ebene der Anleitung aus. Eine eigene Seite `changes.md` der Anleitung tritt an ihre Stelle. Das zusätzliche Verzeichnis hat keine solche Seite.

## Seiten verknüpfen

Um auf eine andere Seite derselben Anleitung zu verlinken, nehmen Sie ihren Pfad relativ zur verlinkenden Seite als Linkziel, etwa `greetings.md`, `setup/permissions.md` oder, von einer Seite in `setup` aus, `../greetings.md`. Mit einem Anker springen Sie zu einer Überschrift auf dieser Seite, etwa `greetings.md#the-greeting-form`. Nennen Sie immer die Standarddatei, auch aus einer Übersetzung heraus. Das Handbuch öffnet die Seite in der Sprache der Leser:innen.

Jede Überschrift bekommt einen Anker aus ihrem Text in Kleinbuchstaben, mit Bindestrichen statt Leerzeichen und ohne Satzzeichen. Die Überschrift „The greeting form“ bekommt den Anker `#the-greeting-form`.

Vollständige Adressen wie https://example.org, Pfade, die mit einem Schrägstrich beginnen, und Links auf einen Anker derselben Seite bleiben, wie Sie sie geschrieben haben.

Das Handbuch entfernt rohes HTML aus dem Markdown und verwirft Links mit unsicheren Schemata wie javascript. Tabellen, Durchstreichungen, Aufgabenlisten und automatische Links aus GitHub Flavored Markdown funktionieren.

## Bilder

Legen Sie Bilder in ein Unterverzeichnis der Anleitung und binden Sie sie mit einem relativen Pfad ein:

```markdown
![The greeting form](images/greeting-form.png)
```

Das Handbuch liefert Bilder im Format PNG, JPEG, GIF, WebP und SVG aus. Es sendet jedes Bild mit einer Sandbox-Richtlinie, sodass Skripte in einer SVG-Datei nie laufen. Ein relativer Link auf eine andere Art von Datei nimmt denselben Weg und bekommt die Antwort 400.

Ein Bild muss im Verzeichnis der Anleitung liegen, auch nachdem symbolische Links aufgelöst sind. Ein Pfad, der hinausführt, bekommt die Antwort 400, eine fehlende Datei 404. Bilder mit vollständiger Adresse, auch solche, die mit zwei Schrägstrichen beginnen, lädt der Browser der Leser:innen von ihrem eigenen Server.

Übersetzungen verwenden dieselben Bilder wie die Standardseite.

## Videos

Ein Vorschaubild, das auf eine Videodatei unter einer https-Adresse verlinkt, wird an Ort und Stelle abgespielt:

```markdown
[![Video: das Begrüßungsformular](images/greeting-video.png)](https://media.example.org/greeting.mp4)
```

Der Link darf nur das Bild enthalten, und seine Adresse muss auf `.mp4` oder `.webm` enden. Das Handbuch liefert selbst keine Videodateien aus, sie liegen also auf einem eigenen Server. Der Player lädt nichts, bevor die Leser:innen ihn starten, und der Alternativtext des Bildes benennt das Video; ohne ihn bleibt das Vorschaubild ein Link. Wo Markdown ohne das Handbuch angezeigt wird, etwa auf einer Code-Plattform, bleibt das Vorschaubild ein Link auf das Video.

## Links auf CiviCRM-Seiten

Eine Seite kann von den CiviCRM-Seiten aus verlinkt werden, die sie beschreibt. Tragen Sie deren Pfade unter `routes` ein:

```yaml
routes:
  - civicrm/greeter/send
  - civicrm/greeter/settings
```

Jede dieser Seiten zeigt dann über ihrem Inhalt den Link „Handbuch: Sending greetings“, benannt nach dem Seitentitel. Nennen mehrere Seiten denselben Pfad, zeigt die CiviCRM-Seite einen Link auf jede von ihnen. Nur Leser:innen mit der Berechtigung `access handbook` sehen die Links.

Das Handbuch fügt den Link ein, wenn CiviCRM eine Seite oder ein Formular aufbaut. Es vergleicht den Pfad der Seite genau, ohne die Abfrage nach dem Fragezeichen, und Schrägstriche am Anfang oder Ende zählen nicht. Alles nach einem # in der Adresse erreicht den Server nie, daher teilen sich die Ansichten innerhalb einer Seite wie SearchKit einen Pfad.

## Sprachen

Eine Seite in der Standardsprache hat einen einfachen Dateinamen, etwa `greetings.md`. Eine Übersetzung fügt vor der Endung einen Sprachcode ein:

- `greetings.de.md` ist die deutsche Seite.
- `greetings.de_DE.md` ist die deutsche Seite für Deutschland.

Der Sprachcode besteht aus zwei Kleinbuchstaben. Nach einem Unterstrich kann eine Region aus zwei Großbuchstaben folgen. Jeder andere Name mit Punkten ist eine gewöhnliche Seite. So behält `notes.v1.2.md` seinen vollen Namen, und seine deutsche Übersetzung heißt `notes.v1.2.de.md`. Statische Website-Generatoren verwenden dieselben Endungen für Übersetzungen, daher kann das Verzeichnis auch eine Dokumentations-Website speisen.

Leser:innen sehen die Datei für ihre CiviCRM-Sprache. Bei de_DE nimmt das Handbuch die erste Datei, die es gibt:

1. `greetings.de_DE.md`
2. `greetings.de.md`
3. `greetings.md`

Leser:innen mit de_AT bekommen die deutsche Seite und nie die für Deutschland.

Eine Übersetzung ist dieselbe Seite in einer anderen Sprache. Sie behält die Seiten-ID, Links auf sie nennen also die Standarddatei.

Das Frontmatter einer Übersetzung ersetzt das der Standardseite Schlüssel für Schlüssel. Schlüssel, die es weglässt, kommen von der Standardseite, mit Ausnahme des Titels. Der Titel kommt aus dem eigenen Frontmatter der Übersetzung, dann aus ihrer ersten Überschrift der ersten Ebene, dann von der Standardseite.

Die Suche liest die Dateien, die die Leser:innen sehen. Wer Deutsch eingestellt hat, durchsucht also den deutschen Text.

Fehlerhafte Dateien stehen in jeder Sprache auf der Startseite des Handbuchs:

- Eine Übersetzung ohne ihre Standardseite meldet „Übersetzung ohne die Standardseite“ und bleibt weg.
- Eine fehlerhafte Übersetzung bleibt weg, und die Leser:innen bekommen die nächste Datei in der Reihe.
- Eine fehlerhafte Standardseite lässt die ganze Seite mit allen Übersetzungen wegfallen.

## Sprache der Oberfläche

Die Oberfläche des Handbuchs ist auf Englisch. Die deutsche Übersetzung steht in `l10n/de_DE/LC_MESSAGES/handbook.po`, und CiviCRM liest die kompilierte Datei daneben. Kompilieren Sie die Übersetzung nach jeder Änderung in diesem Verzeichnis:

```bash
msgfmt --check --statistics -o handbook.mo handbook.po
```

Die Titel der Handbuch-Routen und der Menüeintrag werden gespeichert, wenn CiviCRM sein Menü neu aufbaut. Sie folgen der Sprache der Site beim letzten `cv flush`, nicht der Sprache der einzelnen Leser:innen.

## Wann Änderungen sichtbar werden

Das Handbuch behält seine Liste der Seiten für jede Sprache bis zum nächsten Leeren des Caches. Neue und umbenannte Dateien, geändertes Frontmatter und ein Titel aus der ersten Überschrift erscheinen nach `cv flush`. Änderungen am Text einer Seite erscheinen sofort.
