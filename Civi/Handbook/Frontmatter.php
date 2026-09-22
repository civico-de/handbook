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

  private const PATTERN = '/\A---\R(.*?)\R---(?:\R|\z)/s';

  /**
   * @return array{0: string|null, 1: string}
   *   Frontmatter YAML (or NULL) and the body after it.
   */
  public static function split(string $content): array {
    if (preg_match(self::PATTERN, $content, $m) === 1) {
      return [$m[1], substr($content, strlen($m[0]))];
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
    return is_array($meta) ? $meta : [];
  }

}
