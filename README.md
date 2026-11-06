# Handbook

Handbook is a CiviCRM extension that shows the user manuals of your extensions
inside CiviCRM. Every enabled extension can ship its manual as Markdown files.
Handbook collects them in one table of contents, renders them, searches them,
and links each page from the CiviCRM screens it describes.

The user manual is at <https://civico-de.github.io/handbook/>. Licensed under
AGPL-3.0-or-later, see [LICENSE.txt](LICENSE.txt). Report bugs in the
[issue tracker](https://github.com/civico-de/handbook/issues). Report security issues by email
to security@civico.de, not in a public issue; see [SECURITY.md](SECURITY.md).

## Requirements

The supported CiviCRM, PHP and Smarty versions are listed in `info.xml`.
Handbook needs no other extension.

## Install

Download the archive from the
[releases page](https://github.com/civico-de/handbook/releases), unpack it into
your extensions directory and enable it:

```bash
cv ext:enable handbook
```

A git checkout has no `vendor/` directory. Run `composer install --no-dev` in
it first. Without it, System Status reports an error and the handbook screens
show a notice instead of the manuals. The rest of the site keeps working.

Grant the permission `access handbook` to every role that should read the
manuals. Readers open the handbook from the menu Support › Handbook.

## Add a manual to your extension

Create a directory named `handbook` at the root of your extension, with one
Markdown file per page:

```
org.example.greeter/
  handbook/
    index.md
    getting-started.md
    getting-started.de.md
    images/
      greeting-form.png
```

A page can start with frontmatter. Every key is optional:

```yaml
---
title: Getting started
order: 10
section: Setup
routes:
  - civicrm/greeter/settings
---
```

The title names the page, the order sorts it, and the section groups it with
other pages in the table of contents. Each screen listed under routes shows a
link to the page. `index.md` is the landing page of the manual. A file with a
language code, such as `getting-started.de.md`, translates the page of the same
name.

Run `cv flush` after adding or renaming a file. The pages then appear in the
handbook.

## Documentation

- [Overview](https://civico-de.github.io/handbook/): what the handbook does,
  install and first steps
- [Using the handbook](https://civico-de.github.io/handbook/using/): the start
  page, contents, search, links on other screens, the permission and the extra
  directory for manuals outside extensions
- [Writing manuals](https://civico-de.github.io/handbook/writing-manuals/):
  file names, frontmatter, sections, the landing page, links, images,
  translations, the interface language and caching

The German manual is at <https://civico-de.github.io/handbook/de/>.

## Development

The dev stack and every CI gate run in the
[civikitchen](https://github.com/civico-de/civikitchen) Docker image. Run
`composer install` (PHP 8.2 or later) for the Composer libraries, then:

```bash
cd .docker
docker compose up -d     # CiviCRM on http://localhost:8080, admin/admin
docker compose exec -w /var/www/html/ext/handbook app ck ci
```

With a civikitchen checkout next to this repository,
`../civikitchen/scaffold/ckup` does the `composer install` in the image and
starts the stack on free host ports, and `../civikitchen/scaffold/ckx ck ci`
runs a command in it. The extension's own
`handbook/` directory is both its user manual and a test fixture, so changing a
page title, section or order can break a test. `tests/fixtures/extra/` is a
sample extra directory.

### Prefixed libraries

PHP has one class table per process: if two extensions bundle different
versions of a library, the first copy loaded serves both. Every
`composer install` therefore runs [Strauss](https://github.com/BrianHenryIE/strauss)
over `vendor/` (`.civikitchen/strauss.php`, managed by civikitchen's
`ckinit`). It moves league/commonmark, symfony/yaml and everything they pull
in under `Civi\Handbook\Vendor\`, for example
`Civi\Handbook\Vendor\League\CommonMark\Environment\Environment`, and renames
the global function `trigger_deprecation()` to
`civi_handbook_vendor_trigger_deprecation()`. Code in this extension imports
the prefixed names. The unprefixed names do not load.

- `psr/event-dispatcher` is prefixed as well. Handbook never passes
  CommonMark's dispatcher to core or gets one from it, so a private copy of the
  interfaces costs nothing.
- `composer.json` replaces `symfony/polyfill-ctype` and
  `symfony/polyfill-php80`. Handbook requires PHP 8.2 and ext-ctype, so both
  would be no-ops.
- The PSR-7 and PSR-3 names in `AssetRoute` and `StatusCheck` stay
  unprefixed and are not bundled. They come from core: core passes in the
  request, takes the response and reads the check severity.

Two checks run against the running stack. `tests/e2e/missing-vendor.sh` checks
that the site keeps working without the Composer libraries. `tests/browser/`
runs axe-core colour-contrast checks of the handbook screens in the classic
theme and in Riverlea light and dark. It switches the backend theme while it
runs and restores it afterwards:

```bash
npm ci && npx playwright install chromium && npm run test:e2e
```
