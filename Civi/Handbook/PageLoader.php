<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Reads one Markdown file: optional YAML frontmatter (title, order, section, routes),
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
    $meta['title'] ??= self::firstH1($body) ?? $slug;
    $meta['routes'] ??= [];
    if (
      !is_string($meta['title'])
      || !is_int($meta['order'] ?? 0)
      || !is_string($meta['section'] ?? '')
      || !self::isPathList($meta['routes'])
    ) {
      throw HandbookException::badRequest(E::ts('%1: title und section müssen Text, order eine ganze Zahl und routes eine Liste von Pfaden sein.', [
        1 => $name,
      ]));
    }
    /** @var array{title: string, order?: int|null, section?: string|null, routes: list<string>} $meta */
    $meta['routes'] = array_map(static fn (string $r): string => trim($r, '/'), $meta['routes']);
    return new Page($source . '/' . $slug, $file, $meta);
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
