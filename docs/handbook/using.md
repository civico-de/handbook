---
title: Using the handbook
order: 1
section: Reading
---

# Using the handbook

The handbook collects the user manuals of the extensions on your site. Open it from the menu Support › Handbook.

## The start page

The start page shows one card for each manual. A card lists the manual's overview page, its sections and its pages.

When a manual has a problem, the start page lists it at the top under "Errors in the handbook:". If no extension ships a manual, the start page says "There are no manuals yet."

A link to a page that does not exist leads to the start page with the message "This handbook page does not exist."

## Contents

The table of contents sits beside every page. Each manual is a group that you open and close by clicking its name or with the keyboard. The group of the page you are reading is open.

The first entry of a group, "Overview", leads to the manual's overview page if it has one. Small headings divide longer manuals into sections.

On a wide screen the table of contents stays in view while you scroll the page. On a narrow screen it comes first, above the page.

## Reading a page

A path above the page shows where you are: Handbook › manual › section. The name of the manual links to its overview page.

"On this page" lists the headings of the page. On a very wide screen the list sits to the right of the text and stays in view while you scroll. Otherwise it sits above the text.

At the bottom, "Previous page" and "Next page" lead through all manuals in the order of the table of contents.

## Searching

The search field is on every handbook screen. Search finds every section that contains your term, regardless of upper and lower case.

The results are grouped by page. Each page shows its manual and section, then up to five matching sections. Every section shows the first line that contains the term and leads straight to that section. A line such as "and 3 more sections" counts the rest. The line above the results counts the sections and pages found.

Search reads one line of the page's source at a time. A phrase that the author broke across two lines is therefore not found. The matching line is shown as plain text, without formatting.

![Search field](images/search.png)

## Links on other screens

When a manual belongs to a CiviCRM screen, a link such as "Handbook: Sending greetings" appears at the top of that screen. [Writing manuals](writing-manuals.md#links-from-civicrm-screens) explains how to set this up.

## Languages

The handbook shows each page in your CiviCRM language if the manual ships a translation, and in its default language otherwise. Search looks through the same pages you see.

## Permission

Reading the handbook requires the permission `access handbook`. In the list of permissions it is called "Handbook: read manuals". It covers the handbook screens, search, images and the links on other screens. The permission is part of CiviCRM's own list, so you can grant it to roles on every CMS, Standalone included.

## Extra directory

Manuals that do not belong to an extension can live in a directory of their own on the server. Set its absolute path in the setting `handbook_extra_dir`, called "Extra handbook directory". It is on the screen `civicrm/admin/setting/handbook`. That screen requires the permission to administer CiviCRM. You can also set it on the command line:

```bash
cv setting:set handbook_extra_dir=/srv/manuals
```

The pages in the directory follow the rules of [Writing manuals](writing-manuals.md). They form a manual named "More manuals", unless its landing page gives it a title. Their page ids start with extra. The setting is empty by default.

Saving a relative path is refused. When the setting cannot be used, the start page shows one of these errors:

- The extra handbook directory “…” is not a readable directory.
- The extra handbook directory is not read: an extension is already named “extra”.
- The extra handbook directory must be an absolute path (starting with /), not “…”.

The last one appears when the setting holds a relative path anyway.

Everyone with the permission `access handbook` can read every Markdown file and image in this directory. Point the setting at a directory meant for manuals.
