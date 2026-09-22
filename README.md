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
    images/
      event-form.png
```

The handbook reads only the `*.md` files directly in `handbook/`, not in its
subdirectories. Each file is one page. Its id is `<extension key>/<filename
without .md>`, for example `myextension/events`. A filename must match
`[A-Za-z0-9][A-Za-z0-9._-]*.md`.

### Frontmatter

Frontmatter is optional YAML between two `---` lines at the top of the file:

```yaml
---
title: Managing events
order: 10
routes:
  - civicrm/event/manage
---
```

| Key      | Type            | Effect |
|----------|-----------------|--------|
| `title`  | string          | Page title. Without it, the handbook uses the first `# ` heading, then the filename. |
| `order`  | integer         | Sort key within the extension, ascending. Pages without an order come after the ordered ones. Ties sort by title, ignoring case. |
| `routes` | list of strings | CiviCRM paths where a "Handbuch: <title>" link appears above the page content. |

A file with invalid YAML, a wrong type, or an invalid filename is left out.
The problem is listed at the top of the handbook index.

### Landing page and source order

An optional `handbook/index.md` is the source's landing page: the group
heading in the table of contents links to it, and it is not listed as a
separate page. Its frontmatter `order` orders the source itself; sources
without one follow the ordered sources, alphabetically by label.

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

## Screens

| Path | Content |
|------|---------|
| `civicrm/handbook` | Index: table of contents grouped by extension, plus source errors. |
| `civicrm/handbook?p=<id>` | One page, with the table of contents and a list of its sections. |
| `civicrm/handbook/search?term=<text>` | Every line containing the text, case-insensitive, shown as "Page › section" with the line and a link to the section. |
| `civicrm/handbook/asset?p=<source>/<path>` | An image of a manual (png, jpg, gif, webp). |

The menu entry "Handbuch" sits under **Support**.

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

- The handbook caches the page list until the next system flush (`cv flush`).
  New or renamed files, and changes to `title`, `order` or `routes`, show up
  after a flush. Changes to page text show up immediately.
- Search scans the Markdown source line by line, without an index. Hits show
  raw Markdown, and a phrase that spans a line break is not found.
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
docker compose -f .docker/docker-compose.yml exec -u www-data app bash -c \
  'cd /var/www/html/ext/handbook && composer install && CIVICRM_UF=UnitTests ckphpunit \
   && cklint --all && ckconform && phpstan analyse && ckfmt --check'
```

`tests/fixtures/extra/` is a sample extra directory. The extension's own
`handbook/` directory is both its user manual and the discovery fixture.

`tests/e2e/missing-vendor.sh` checks against the running stack that the site
keeps working without `vendor/`. `tests/browser/` runs axe-core colour-contrast
checks of the handbook screens in the classic theme and in Riverlea light and
dark; it switches the backend theme while it runs and restores it afterwards:

```bash
npm ci && npx playwright install chromium && npm run test:e2e
```
