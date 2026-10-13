---
title: Writing manuals
order: 2
section: Writing
---

# Writing manuals

An extension ships manuals in a `handbook/` directory of Markdown files.
Each file is one page.

## Frontmatter

A file may start with frontmatter between two `---` lines:

```yaml
title: Managing events
order: 10
section: Events
routes:
  - civicrm/event/manage
```

Without `title`, the first heading is the title, otherwise the filename.
`order` sets the order.

## Sections

Pages with the same `section` are listed together under that name in the
table of contents. A section sits where its first page sorts by `order`.
Pages without a `section` form a group without a name.

Numbering sections in steps of ten works well: `order` 10 to 19 for the
first section, 20 to 29 for the second.

A file `index.md` is the extension's start page. It is listed as "Overview"
at the top of its group, and its title names the group.

## Linking pages

`routes` lists the CiviCRM paths on which a link to this manual appears.
Write links to other pages as relative paths, for example
[back to reading](using.md).

## Images

Images sit next to the Markdown files and are linked relatively.

## Languages

The file `events.md` is the page in the default language. Translations sit
beside it as `events.de.md` for German or `events.de_DE.md` for German as
used in Germany. The handbook shows the version that matches the reader's
language: first the one with the full locale, then the one with the
language, otherwise `events.md`. All versions share one page id, so links
such as `events.md` work in every language.

The frontmatter of a translation replaces the default page's; what it leaves
out is taken over. Usually `title` and `section` are enough. All versions
share the images.

## When changes show

Changed text shows at once. New or renamed files and changed frontmatter
show after CiviCRM's cache has been cleared, for example with `cv flush`.
The same holds for a title taken from the first `# ` heading.
