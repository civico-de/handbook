<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * The section of a manual's subdirectory.
 */
final class DirectorySection {

  /**
   * Pages of one subdirectory without their own `section` take the directory's:
   * its index page's section or title, else the directory name.
   *
   * @param list<\Civi\Handbook\Page> $pages
   *
   * @return list<\Civi\Handbook\Page>
   */
  public static function apply(array $pages): array {
    if ($pages === []) {
      return [];
    }
    $section = basename($pages[0]->dir());
    foreach ($pages as $page) {
      if (!$page->isSectionIndex()) {
        continue;
      }
      $section = $page->section ?? $page->title;
    }
    return array_map(static fn (Page $p): Page => $p->section === NULL ? $p->withSection($section) : $p, $pages);
  }

}
