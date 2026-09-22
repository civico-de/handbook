<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;
use League\CommonMark\Parser\MarkdownParser;
use Symfony\Component\Yaml\Yaml;

/**
 * The Composer libraries in vendor/. Missing ones disable the handbook
 * screens and raise a status check instead of breaking the site.
 */
final class Dependencies {

  /**
   * Registers vendor/autoload.php after core's loader, if it exists.
   */
  public static function load(string $autoload): void {
    if (!is_file($autoload)) {
      return;
    }
    $loader = require_once $autoload;
    if ($loader instanceof \Composer\Autoload\ClassLoader) {
      // Appended, not prepended: a shared package must not shadow core's own version.
      $loader->unregister();
      $loader->register(FALSE);
    }
  }

  public static function available(): bool {
    $statics = \Civi::$statics[self::class] ?? NULL;
    if (is_array($statics) && !empty($statics['simulateMissing'])) {
      return FALSE;
    }
    return class_exists(MarkdownParser::class) && class_exists(Yaml::class);
  }

  public static function message(): string {
    return E::ts(
      'Die Programmbibliotheken des Handbuchs fehlen (vendor/). Bitte im Verzeichnis der Erweiterung „composer install --no-dev“ ausführen.',
    );
  }

}
