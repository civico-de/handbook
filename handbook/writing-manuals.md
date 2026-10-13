---
title: Writing manuals
order: 2
section: Writing
---

# Writing manuals

This page is for developers who want to ship a manual with their extension. It covers file names, frontmatter, the order of pages, links, images and translations.

## Add a manual

Create a directory named `handbook` at the root of your extension. Put one Markdown file into it for every page of the manual:

```
org.example.greeter/
  handbook/
    index.md
    getting-started.md
    getting-started.de.md
    greetings.md
    images/
      greeting-form.png
```

The handbook reads only the Markdown files directly in this directory. Files in subdirectories are not pages, so a subdirectory is the place for images. Manuals of disabled extensions do not show.

A file name may contain only unaccented letters, digits, dots, hyphens and underscores, and it must start with a letter or a digit. A name like `getting-started.de.md` is not a page of its own but a translation, described under [Languages](#languages).

Every page has an id made of the extension key and the file name without the extension, such as org.example.greeter/greetings. Its address in CiviCRM is `civicrm/handbook?p=org.example.greeter/greetings`.

Run `cv flush` after you add or rename a file. The new pages then appear under Support › Handbook.

## Frontmatter

A page can start with frontmatter, a block of YAML between two lines of three hyphens. It must be the first thing in the file, and every key in it is optional.

```yaml
---
title: Sending greetings
order: 20
section: Greetings
routes:
  - civicrm/greeter/send
---
```

| Key | Value | Effect |
|---|---|---|
| `title` | text | The title of the page in the table of contents, the breadcrumb, search results and links. Without it, the first heading of level one is the title. Without that heading, the file name is. |
| `order` | whole number | The position of the page within its manual, lowest first. Pages without an order come after the ordered ones. Pages with the same order sort by title, ignoring case. |
| `section` | text | The group the page belongs to in the table of contents. Spaces around the name are ignored, and an empty value means no section. |
| `routes` | list of paths | The CiviCRM screens that show a link to this page, described under [Links from CiviCRM screens](#links-from-civicrm-screens). |

A file the handbook cannot read as a page is left out. The handbook index then lists the problem at the top, under "Errors in the handbook:". Each message starts with the extension key and the file name, followed by one of these:

| Message | Cause |
|---|---|
| "frontmatter is not valid YAML" | The YAML does not parse. The parser's explanation follows in brackets. |
| "frontmatter is not a map of keys to values." | The block holds a single value or a list instead of keys. |
| "title and section must be text, order a whole number and routes a list of paths." | A key has the wrong type, for example a single path under routes instead of a list. |
| "invalid file name." | The file name breaks the rule under [Add a manual](#add-a-manual). |
| "translation without the default page" | A translation has no default page, see [Languages](#languages). |

## Sections

Long manuals read better in sections. Give the pages of one topic the same `section`, and the table of contents lists them together under that name. The breadcrumb above each page shows its section too.

A section sits where its first page sorts, by order and then by title. Its pages keep that order among themselves. Pages without a section form a group without a name, placed the same way. Sections need no subdirectories and do not change page ids.

Numbering pages in steps of ten keeps sections apart. Orders 10 to 19 for a section named Setup and 20 to 29 for a section named Greetings give two sections in that order.

## Landing page and order

A file named `index.md` is the landing page of your manual. It is the first entry of the manual in the table of contents, labelled "Overview", and the breadcrumb of every other page links to it. Its section is ignored.

The title of the landing page names the whole manual in the table of contents, the breadcrumb and search results. Without a landing page, the manual carries the label of the extension, or "More manuals" for the [extra directory](using.md#extra-directory).

Manuals are listed alphabetically by name. To move a manual ahead of the others, give its landing page an `order`. Manuals with an order come first, lowest first, and the others follow alphabetically.

## Linking pages

To link another page of the same manual, use its file name as the link target, such as `greetings.md`. Add an anchor to jump to a heading on that page, such as `greetings.md#the-greeting-form`. Always name the default file, also from a translation. The handbook opens the page in the reader's language.

Every heading gets an anchor made from its text in lower case, with hyphens for spaces and without punctuation. A heading "The greeting form" gets the anchor `#the-greeting-form`.

Full addresses such as https://example.org, paths that start with a slash and links to an anchor on the same page stay as you wrote them.

The handbook removes raw HTML from the Markdown and drops links with unsafe schemes such as javascript. Tables, strikethrough, task lists and autolinks from GitHub Flavored Markdown work.

## Images

Put images into a subdirectory of the manual and link them with a relative path:

```markdown
![The greeting form](images/greeting-form.png)
```

The handbook serves PNG, JPEG, GIF and WebP images. It does not serve SVG. A relative link to any other kind of file goes the same way, so it does not work either.

An image must lie inside the manual's directory, also after following symbolic links. A path that leads outside gets the answer 400, and a missing file gets 404. Images with a full address, including ones that start with two slashes, load from their own server in the reader's browser.

Translations use the same images as the default page.

## Links from CiviCRM screens

A page can be linked from the CiviCRM screens it describes. List their paths under `routes`:

```yaml
routes:
  - civicrm/greeter/send
  - civicrm/greeter/settings
```

Each of these screens then shows the link "Handbook: Sending greetings" above its content, named after the page title. When several pages list the same path, the screen shows a link to each of them. Only readers with the permission `access handbook` see the links.

The handbook adds the link when CiviCRM builds a page or a form. It compares the path of the screen exactly, without the query string, and slashes at either end do not matter. Everything after a # in the address never reaches the server, so the views inside a screen like SearchKit all share one path.

## Languages

A page in the default language has a plain file name, such as `greetings.md`. A translation adds a language code before the extension:

- `greetings.de.md` is the German page.
- `greetings.de_DE.md` is the German page for Germany.

The language code has two lower-case letters. A region of two capital letters can follow after an underscore. Any other dotted name is an ordinary page. So `notes.v1.2.md` keeps its full name, and its German translation is `notes.v1.2.de.md`. Static site generators use the same suffixes for translations, so the directory can also feed a documentation website.

A reader sees the file for their CiviCRM language. For a reader with de_DE, the handbook takes the first file that exists:

1. `greetings.de_DE.md`
2. `greetings.de.md`
3. `greetings.md`

A reader with de_AT gets the German page and never the one for Germany.

A translation is the same page in another language. It keeps the page id, so links to it name the default file.

The frontmatter of a translation overrides the default page key by key. Keys it leaves out come from the default page, except the title. The title comes from the translation's own frontmatter, then from its first heading of level one, then from the default page.

Search reads the files the reader sees, so a German reader searches the German text.

Broken files are listed on the index in every language:

- A translation without its default page shows "translation without the default page" and is left out.
- A broken translation is left out, and the reader gets the next file in line.
- A broken default page leaves out the whole page with all its translations.

## Interface language

The interface of the handbook is in English. The German translation is in `l10n/de_DE/LC_MESSAGES/handbook.po`, and CiviCRM reads the compiled file next to it. After editing the translation, compile it in that directory:

```bash
msgfmt --check --statistics -o handbook.mo handbook.po
```

The titles of the handbook routes and the menu label are stored when CiviCRM rebuilds its menu. They follow the language of the site at the last `cv flush`, not the language of each reader.

## When changes show

The handbook keeps its list of pages for each language until the next system flush. New and renamed files, changed frontmatter and a title taken from the first heading show after `cv flush`. Changes to the text of a page show at once.
