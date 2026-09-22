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
   * Explicit orders before none, so any integer, PHP_INT_MAX included, stays ordered.
   *
   * @return array{bool, int|null, string, string}
   */
  private static function key(?int $order, string $name, string $id): array {
    return [$order === NULL, $order, mb_strtolower($name), $id];
  }

  /**
   * Orders page groups by their landing page's `order` (none last), then label
   * ignoring case, then source key, and flattens them.
   *
   * @param array<string, list<\Civi\Handbook\Page>> $groups
   * @param array<string, array{dir: string, label: string}> $sources
   *
   * @return list<\Civi\Handbook\Page>
   */
  public static function sources(array $groups, array $sources): array {
    $keys = [];
    foreach ($groups as $source => $group) {
      $keys[$source] = self::key(self::landingOrder($group), $sources[$source]['label'] ?? $source, $source);
    }
    uksort($groups, static fn (string $a, string $b): int => $keys[$a] <=> $keys[$b]);
    return array_merge(...array_values($groups));
  }

  /**
   * @param list<\Civi\Handbook\Page> $group
   */
  private static function landingOrder(array $group): ?int {
    foreach ($group as $page) {
      if ($page->isLanding()) {
        return $page->order;
      }
    }
    return NULL;
  }

}
