# Handbook

A CiviCRM extension that shows Markdown user manuals inside the CiviCRM UI.
Any enabled extension can ship its own manual pages. The handbook lists them
in one table of contents, renders them with linkable sections, searches them,
and links a page from the CiviCRM screens it describes.

Proprietary software of civico GmbH, see [LICENSE.txt](LICENSE.txt).

## Requirements

- The CiviCRM and PHP versions pinned in `info.xml`.
- Composer dependencies (`league/commonmark`, `symfony/yaml`). `vendor/` is not
  committed. Run `composer install --no-dev` in the extension directory before
  you enable it. Without `vendor/`, System Status shows an error and the
  handbook screens only show a notice to install the libraries; the rest of
  the site keeps working.

## Adding a manual to an extension

Put Markdown files into a `handbook/` directory at the extension root:

```
myextension/
  handbook/
    getting-started.md
    events.md
    events.de.md
    images/
      event-form.png
```

The handbook reads only the `*.md` files directly in `handbook/`, not in its
subdirectories. Each file is one page. Its id is `<extension key>/<filename
without .md>`, for example `myextension/events`. A filename must match
`[A-Za-z0-9][A-Za-z0-9._-]*.md`. A name with a locale suffix, such as
`events.de.md`, is a translation of a page, not a page of its own (see
Languages).

### Frontmatter

Frontmatter is optional YAML between two `---` lines at the top of the file:

```yaml
---
title: Managing events
order: 10
section: Events
routes:
  - civicrm/event/manage
---
```

| Key      | Type            | Effect |
|----------|-----------------|--------|
| `title`  | string          | Page title. Without it, the handbook uses the first `# ` heading, then the filename. |
| `order`  | integer         | Sort key within the extension, ascending. Pages without an order come after the ordered ones. Ties sort by title, ignoring case. |
| `section` | string         | Groups pages within the extension under this name in the table of contents. Surrounding spaces are ignored; a blank value counts as no section. |
| `routes` | list of strings | CiviCRM paths where a "Handbook: <title>" link appears above the page content. |

A file with invalid YAML, a wrong type, or an invalid filename is left out.
The problem is listed at the top of the handbook index.

### Sections

Pages with the same `section` are listed together under that name. A section
sits where its first page sorts (by `order`, then title); its pages keep that
order among themselves. Pages without a section form an unnamed group, placed
where its first page sorts. Sections do not change page ids and need no
subdirectories. For example, orders 10–19 with `section: Setup` and 20–29
with `section: Events` give two groups in that order.

### Landing page and source order

An optional `handbook/index.md` is the source's landing page. It is the first
entry of its source in the table of contents, labelled "Overview", and the
breadcrumb of the other pages links to it. Its `section` is ignored. Its title
names the source in the table of contents, breadcrumb and search results;
without a landing page, the name is the extension's label, or "More
manuals" for the extra directory. Its frontmatter `order` orders the source
itself; sources without one follow the ordered sources, alphabetically by name.

### Links and images

- `other-page.md` and `other-page.md#section` point to that page of the same
  source, anchor included.
- Relative image paths, such as `images/event-form.png`, are served through
  `civicrm/handbook/asset`.
- Absolute URLs, `/civicrm/...` paths and `#anchor` links stay as they are.
  External images, including protocol-relative `//host/...` ones, load from
  their origin in the reader's browser.
- Every heading gets a slug id, so `#section` links work from anywhere.
- Raw HTML in the Markdown is removed, and `javascript:` style links are
  dropped. Tables, strikethrough and autolinks (GitHub Flavored Markdown) are
  supported.

### Languages

`events.md` is the default page. `events.<locale>.md` is its translation,
where the locale is a language code (`de`) or a language and region
(`de_DE`): the last dot segment before `.md` must match
`^[a-z]{2}(_[A-Z]{2})?$`. Any other dotted name is an ordinary page, so
`notes.v1.2.md` and `events.DE.md` keep their full names, and
`notes.v1.2.de.md` translates `notes.v1.2.md`. This is the suffix layout that
static site generators use for translations, so the same directory can feed a
website, where the default page is the page without a suffix.

A reader sees the file for their CiviCRM locale: the full locale first
(`events.de_DE.md` for `de_DE`), then the language (`events.de.md`), then the
default page. A reader with `de_AT` gets `events.de.md`, never
`events.de_DE.md`.

- A translation keeps the page id, `myextension/events`, and links to it name
  the default file: write `events.md`, not `events.de.md`.
- Frontmatter keys of the translation override the default page's, key by
  key. `order`, `section` and `routes` it leaves out come from the default
  page. Its title comes from its own frontmatter, then its first `# `
  heading, then the default page's title.
- Translations share the images of their source.
- Search reads the files the reader sees, so a German reader searches the
  German text.
- A translation without its default page, a broken translation and a broken
  default page are listed as errors on the index in every locale. A broken
  translation is left out and the reader gets the next file in line; a broken
  default page leaves out the whole page.

## Interface language

The interface strings are English. `l10n/de_DE/LC_MESSAGES/handbook.po` holds
the German translation; CiviCRM reads the compiled `handbook.mo` next to it.
After editing the `.po`, recompile it:

```bash
msgfmt --check --statistics -o handbook.mo handbook.po
```

Route titles (page title, breadcrumb, settings screen) and the menu label are
stored when the menu is rebuilt, so they follow the site's locale at the last
`cv flush`, not each reader's.

## Screens

| Path | Content |
|------|---------|
| `civicrm/handbook` | Index: source errors on top, then one card per source listing its sections and pages. |
| `civicrm/handbook?p=<id>` | One page. See below. |
| `civicrm/handbook/search?term=<text>` | Every section containing the text, case-insensitive, grouped by page: the page title with "source › section", then up to five sections, each linking there with its first matching line, and "and N more sections" for the rest. The status line counts sections and pages. |
| `civicrm/handbook/asset?p=<source>/<path>` | An image of a manual (png, jpg, gif, webp). |

A page has three parts:

- the table of contents: one collapsible group per source, open for the
  current page's source, section names as small headings. It stays in view
  and scrolls on its own.
- the page: a breadcrumb (Handbook › source › section), the content, and
  links to the previous and next page in table-of-contents order, landing
  pages included.
- "On this page", the page's `##` and `###` headings.

The layout follows the screen width:

- below 64em, one column: table of contents, then "On this page", then
  the content.
- from 64em, the table of contents on the left and the page beside it, with
  "On this page" above the content.
- from 90em, three columns: table of contents, content, and "On this page"
  on the right, kept in view.

The search screen shows the table of contents with every group closed.

The menu entry "Handbook" sits under **Support**.

## Setting

`handbook_extra_dir` (Administer: `civicrm/admin/setting/handbook`) is an
absolute server path to one more directory of Markdown pages, for manuals that
do not belong to an extension. Its pages appear as source `extra`. It is empty
by default. Saving a relative path is rejected. If the path is set but is not a
readable directory, the index shows an error. Every Markdown file and image in
the directory becomes readable to holders of `access handbook`, so point it at
a directory meant for manuals.

```bash
cv vset handbook_extra_dir=/srv/manuals
```

## Permission

Every handbook route requires `access handbook`. The permission is registered
through `hook_civicrm_permission`, so it can be granted to roles on every CMS,
Standalone included. Context links are shown only to users who hold it.

## Limits

- The handbook caches the page list per locale until the next system flush (`cv flush`).
  New or renamed files, and changes to `title`, `order`, `section` or
  `routes`, show up after a flush. The same holds for a title taken from the
  first `# ` heading.
- Rendered pages are cached in CiviCRM's `long` cache, keyed by file path,
  content hash, locale, the site's handbook URL and the extension version. Changes to
  page text and a new release of the extension show up immediately, and search
  reads the section headings from the same cache. Older entries stay until the
  next flush.
- Search scans the Markdown source line by line, without an index, and shows
  one hit per section. The matching line is shown as plain text: emphasis,
  code, links, list and quote markers are removed, a table row shows its cells
  joined by " · ", and table separator rows are never shown. A phrase that
  spans a line break is not found.
- Context links match the exact path from `CRM_Utils_System::currentPath()` on
  pages (`hook_civicrm_pageRun`) and QuickForm screens (`hook_civicrm_buildForm`),
  for example `civicrm/event/add`. Angular-only screens get no link.
- The asset route serves only images whose real path, after following symlinks,
  lies inside the source directory. A request that points outside it gets 400,
  a missing file 404. SVG is not served.

## Development

Dev stack, gates and CI follow the civikitchen template:

```bash
/path/to/civikitchen/scaffold/ckup -d        # stack in .docker/, free ports
docker compose -f .docker/docker-compose.yml exec -u www-data -w /var/www/html/ext/handbook app composer install
docker compose -f .docker/docker-compose.yml exec -u www-data -w /var/www/html/ext/handbook app ck ci
```

`tests/fixtures/extra/` is a sample extra directory. The extension's own
`handbook/` directory is both its user manual, in English with German
translations, and the discovery fixture.

`tests/e2e/missing-vendor.sh` checks against the running stack that the site
keeps working without `vendor/`. `tests/browser/` runs axe-core colour-contrast
checks of the handbook screens in the classic theme and in Riverlea light and
dark; it switches the backend theme while it runs and restores it afterwards:

```bash
npm ci && npx playwright install chromium && npm run test:e2e
```
