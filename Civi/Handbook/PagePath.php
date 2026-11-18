<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Paths of pages and images relative to the directory of the linking page.
 */
final class PagePath {

  /**
   * `<source>/<path>` of a path relative to the directory id $dir, with `.`
   * and `..` folded. A `..` above the source stays, so the target is not found.
   */
  public static function resolve(string $dir, string $path): string {
    [$source, $base] = array_pad(explode('/', $dir, 2), 2, '');
    $segments = [];
    foreach (explode('/', $base . '/' . $path) as $segment) {
      if ($segment === '..' && $segments !== [] && end($segments) !== '..') {
        array_pop($segments);
        continue;
      }
      if ($segment !== '' && $segment !== '.') {
        $segments[] = $segment;
      }
    }
    return $source . '/' . implode('/', $segments);
  }

}
