<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Rules for the handbook_extra_dir setting: empty or an absolute path.
 */
final class ExtraDir {

  /**
   * Setting validate_callback.
   *
   * @throws \CRM_Core_Exception
   */
  public static function validate(mixed &$value): bool {
    $path = is_string($value) ? trim($value) : '';
    if ($path !== '' && !self::isAbsolute($path)) {
      throw new \CRM_Core_Exception(self::relativeMessage($path));
    }
    return TRUE;
  }

  public static function isAbsolute(string $path): bool {
    return str_starts_with($path, '/');
  }

  public static function relativeMessage(string $path): string {
    return E::ts('The extra handbook directory must be an absolute path (starting with /), not “%1”.', [
      1 => $path,
    ]);
  }

}
