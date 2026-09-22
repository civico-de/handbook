<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * YAML frontmatter between two `---` lines at the top of a Markdown file.
 */
final class Frontmatter {

  private const PATTERN = '/\A---\R(?:(.*?)\R)?---(?:\R|\z)/s';

  /**
   * @return array{0: string|null, 1: string}
   *   Frontmatter YAML (or NULL) and the body after it.
   */
  public static function split(string $content): array {
    if (str_starts_with($content, "\u{FEFF}")) {
      $content = substr($content, 3);
    }
    if (preg_match(self::PATTERN, $content, $m) === 1) {
      $yaml = $m[1] ?? '';
      return [$yaml === '' ? NULL : $yaml, substr($content, strlen($m[0]))];
    }
    return [NULL, $content];
  }

  /**
   * @return array<mixed>
   */
  public static function parse(string $name, ?string $yaml): array {
    try {
      $meta = $yaml === NULL ? [] : Yaml::parse($yaml);
    }
    catch (ParseException $e) {
      throw HandbookException::badRequest(E::ts('%1: Frontmatter ist kein gültiges YAML (%2).', [
        1 => $name,
        2 => $e->getMessage(),
      ]));
    }
    if ($meta !== NULL && !is_array($meta)) {
      throw HandbookException::badRequest(E::ts('%1: Frontmatter ist keine Zuordnung von Schlüsseln zu Werten.', [
        1 => $name,
      ]));
    }
    return $meta ?? [];
  }

}
