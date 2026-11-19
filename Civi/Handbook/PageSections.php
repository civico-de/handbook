<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * The headings a page lists under "On this page": levels 2 and 3, on a
 * changelog page only its newest versions.
 */
final class PageSections {

  public const CHANGELOG_VERSIONS = 10;

  /**
   * @param list<array{level: int, text: string, slug: string, line: int}> $headings
   *
   * @return list<array{level: int, text: string, slug: string, line: int}>
   */
  public static function of(Page $page, array $headings): array {
    if ($page->isChangelog()) {
      $versions = array_filter($headings, static fn (array $h): bool => $h['level'] === 2);
      return array_slice(array_values($versions), 0, self::CHANGELOG_VERSIONS);
    }
    return array_values(array_filter($headings, static fn (array $h): bool => $h['level'] >= 2 && $h['level'] <= 3));
  }

}
