---
title: Handbook
---

# Handbook

The handbook shows the user manuals of your extensions inside CiviCRM. Every enabled extension can ship its manual as a set of Markdown files. The handbook collects them in one table of contents, renders them, searches them, and links each page from the CiviCRM screens it describes. Readers see each page in their own language when the manual ships a translation.

The people who work in CiviCRM read the manuals there, next to the screens they describe. Extension developers write the manuals in their own repository, and the same files can also feed a documentation website.

## Install

The supported CiviCRM, PHP and Smarty versions are listed in the extension's `info.xml`. Download the release archive from the [releases page](https://github.com/civico-de/handbook/releases), unpack it into your CiviCRM extensions directory and enable it:

```bash
cv ext:enable handbook
```

A git checkout has no `vendor/` directory. Run `composer install --no-dev` in it before you enable the extension. Without `vendor/`, System Status reports "Handbook: libraries missing" and the handbook screens only show a notice to install the libraries. The rest of the site keeps working.

Then grant the permission `access handbook` to every role that should read the manuals.

## First steps

Open the menu Support › Handbook. The start page lists every manual on your site, this one included.

To add a manual to your own extension, create a directory named `handbook` at its root with one Markdown file per page:

```markdown
---
title: Getting started
order: 10
---

# Getting started

The greeter sends a greeting to every new contact.
```

Run `cv flush`, and the page appears in the handbook.

## Where to go next

- [Using the handbook](using.md) explains the start page, the table of contents, search, links on other screens, the permission and the extra directory.
- [Writing manuals](writing-manuals.md) covers file names, frontmatter, sections, the landing page, links, images, translations and caching.
