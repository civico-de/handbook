# Changelog

## [Unreleased]

### Added

- An extension can keep its manual in another directory than `handbook/`, named by the `info.xml`
  tag `handbook:dir=<directory>`.

## [0.3.0] - 2026-11-14

### Changed

- Requires CiviCRM 6.16 or newer.

## [0.2.0] - 2026-10-30

### Added

- Localized interface with English as source language and a German translation.
- Language variants of manual pages (for example `greetings.de.md` or `greetings.de_DE.md`), picked
  by the reader's CiviCRM locale.
- The handbook's own user manual is published as a documentation site.

### Changed

- Licensed under AGPL-3.0-or-later.
- The bundled Markdown and YAML libraries are prefixed into the extension's own namespace, so
  another extension shipping a different version of them can no longer conflict with Handbook.
- The PHP `ctype` extension is required.

## [0.1.1] - 2026-09-28

### Added

- A new `section` frontmatter key groups a source's pages into sections.
- Sources are shown as collapsible groups, with index cards, a breadcrumb, a pager between pages
  and search hits grouped by page.

### Changed

- Search shows one hit per section with its first matching line, as plain text without Markdown
  syntax.
- A source is named after the title of its landing page; a source without a landing page skips
  the overview list.
- Rendered pages are cached by content and extension version and invalidated when either changes.
- Wide tables scroll in a focusable box instead of breaking words.
- Links use the Riverlea theme's link colours.
- PHP 8.2 is supported; the minimum PHP version drops from 8.3 to 8.2.

### Fixed

- List markers in rendered pages are restored against the theme's list reset.
- Table headers in the classic theme and focus rings in the scrolling sidebars meet contrast and
  visibility requirements.

## [0.1.0] - 2026-09-22

### Added

- Renders Markdown manuals shipped in an extension's `handbook/` directory inside CiviCRM, with one
  table of contents, full-text search, an image route and context links from the CiviCRM screens a
  page describes, including QuickForm screens.
- Frontmatter for page metadata and `handbook/index.md` for landing pages and source ordering.
- Setting for an extra, absolute handbook directory outside the extensions.
- `access handbook` permission and a Support › Handbook menu entry.
- Without its Composer `vendor/` tree the extension reports a System Status error and shows a
  notice on the handbook screens instead of failing.

[Unreleased]: https://github.com/civico-de/handbook/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/civico-de/handbook/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/civico-de/handbook/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/civico-de/handbook/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/civico-de/handbook/releases/tag/v0.1.0
