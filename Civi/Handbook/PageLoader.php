<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Reads one Markdown file: optional YAML frontmatter (title, order, routes),
 * title fallback to the first H1, then the filename.
 */
final class PageLoader {

  private const SLUG = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/';

  /**
   * @throws \Civi\Handbook\HandbookException
   *   With a German message naming the file when it cannot be a page.
   */
  public static function load(string $source, string $file): Page {
    $slug = basename($file, '.md');
    $name = $source . '/' . basename($file);
    if (preg_match(self::SLUG, $slug) !== 1) {
      throw HandbookException::badRequest(E::ts('%1: ungültiger Dateiname.', [1 => $name]));
    }
    [$yaml, $body] = Frontmatter::split((string) file_get_contents($file));
    $meta = Frontmatter::parse($name, $yaml);
    $title = $meta['title'] ?? self::firstH1($body) ?? $slug;
    $order = $meta['order'] ?? NULL;
    $routes = $meta['routes'] ?? [];
    if (!is_string($title) || $order !== NULL && !is_int($order) || !self::isPathList($routes)) {
      throw HandbookException::badRequest(E::ts('%1: title muss Text, order eine ganze Zahl und routes eine Liste von Pfaden sein.', [
        1 => $name,
      ]));
    }
    /** @var list<string> $routes */
    return new Page(
      $source . '/' . $slug,
      $title,
      $order,
      array_map(static fn (string $r): string => trim($r, '/'), $routes),
      $file,
    );
  }

  private static function isPathList(mixed $routes): bool {
    return (
      is_array($routes)
      && array_is_list($routes)
      && array_filter($routes, static fn (mixed $r): bool => !is_string($r)) === []
    );
  }

  private static function firstH1(string $body): ?string {
    return preg_match('/^# +(.+?)(?: +#+)? *$/m', $body, $m) === 1 ? $m[1] : NULL;
  }

}
