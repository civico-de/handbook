<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Sort order of pages within a source and of the sources themselves.
 */
final class Ordering {

  /**
   * `order` ascending (none last), then title ignoring case, then id.
   */
  public static function pages(Page $a, Page $b): int {
    return self::key($a->order, $a->title, $a->id) <=> self::key($b->order, $b->title, $b->id);
  }

  /**
   * Sorts a source's pages: the landing page first, then the pages grouped by
   * `section`, each group (unnamed included) placed where its first page sorts.
   *
   * @param list<\Civi\Handbook\Page> $pages
   *
   * @return list<\Civi\Handbook\Page>
   */
  public static function source(array $pages): array {
    usort($pages, self::pages(...));
    $landing = [];
    $groups = [];
    foreach ($pages as $page) {
      if ($page->isLanding()) {
        $landing[] = $page;
        continue;
      }
      // Prefixed so a numeric section name stays a string key.
      $groups['s' . ($page->section ?? '')][] = $page;
    }
    return array_merge($landing, ...array_values($groups));
  }

  /**
   * Explicit orders before none, so any integer, PHP_INT_MAX included, stays ordered.
   *
   * @return array{bool, int|null, string, string}
   */
  private static function key(?int $order, string $name, string $id): array {
    return [$order === NULL, $order, mb_strtolower($name), $id];
  }

  /**
   * Orders page groups by their landing page's `order` (none last), then the
   * landing title or source label ignoring case, then source key, and flattens them.
   *
   * @param array<string, list<\Civi\Handbook\Page>> $groups
   * @param array<string, array{dir: string, label: string}> $sources
   *
   * @return list<\Civi\Handbook\Page>
   */
  public static function sources(array $groups, array $sources): array {
    $keys = [];
    foreach ($groups as $source => $group) {
      $keys[$source] = self::key(self::landing($group)?->order, self::sourceName($group, $sources, $source), $source);
    }
    uksort($groups, static fn (string $a, string $b): int => $keys[$a] <=> $keys[$b]);
    return array_merge(...array_values($groups));
  }

  /**
   * Display name of a source: its landing page's title, else its label.
   *
   * @param list<\Civi\Handbook\Page> $pages
   *   Pages of any sources.
   * @param array<string, array{dir: string, label: string}> $sources
   */
  public static function sourceName(array $pages, array $sources, string $source): string {
    $own = array_values(array_filter($pages, static fn (Page $p): bool => $p->source() === $source));
    return self::landing($own)->title ?? $sources[$source]['label'] ?? $source;
  }

  /**
   * @param list<\Civi\Handbook\Page> $group
   */
  private static function landing(array $group): ?Page {
    foreach ($group as $page) {
      if ($page->isLanding()) {
        return $page;
      }
    }
    return NULL;
  }

}
