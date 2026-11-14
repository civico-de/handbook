<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * The manual directory of an extension: handbook/, or the one its info.xml
 * tag `handbook:dir=<name>` names.
 */
final class ExtensionDir {

  public const TAG = 'handbook:dir=';

  /**
   * @param array<mixed> $tags
   *   Tag => extension keys, as CRM_Extension_Mapper::getAllTags() returns them.
   *
   * @return array<string, list<string>>
   *   Extension key => the directory names its `handbook:dir=` tags name.
   */
  public static function declared(array $tags): array {
    $dirs = [];
    foreach ($tags as $tag => $keys) {
      if (!is_array($keys) || !str_starts_with((string) $tag, self::TAG)) {
        continue;
      }
      foreach (array_filter($keys, 'is_string') as $key) {
        $dirs[$key][] = substr((string) $tag, strlen(self::TAG));
      }
    }
    return $dirs;
  }

  /**
   * The manual directory of an extension: the declared one, else handbook/.
   *
   * @param list<string> $declared
   *
   * @return string|null
   *   NULL when nothing is declared and there is no handbook/.
   *
   * @throws \InvalidArgumentException
   *   When the declaration does not name exactly one directory inside the extension.
   */
  public static function resolve(string $base, string $key, array $declared): ?string {
    if ($declared === []) {
      $dir = realpath($base . '/handbook');
      return $dir !== FALSE && is_dir($dir) ? $dir : NULL;
    }
    if (count($declared) > 1) {
      throw new \InvalidArgumentException(E::ts('The extension %1 declares more than one handbook directory: %2.', [
        1 => $key,
        2 => implode(', ', $declared),
      ]));
    }
    $root = realpath($base);
    $dir = realpath($base . '/' . $declared[0]);
    // The root itself would turn README.md and CHANGELOG.md into pages.
    if ($root === FALSE || $dir === FALSE || !is_dir($dir) || !str_starts_with($dir, $root . DIRECTORY_SEPARATOR)) {
      throw new \InvalidArgumentException(E::ts('The extension %1 declares the handbook directory “%2”, which is not a directory inside the extension.', [
        1 => $key,
        2 => $declared[0],
      ]));
    }
    return $dir;
  }

}
