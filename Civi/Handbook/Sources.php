<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Finds the source directories: every enabled extension with a handbook/
 * directory, plus the configured extra directory as source "extra".
 */
final class Sources {

  public const EXTRA = 'extra';

  /**
   * @var array<string, array{dir: string, label: string}>
   */
  public array $dirs = [];

  /**
   * @var list<string>
   */
  public array $errors = [];

  public static function fromSystem(): self {
    $extensions = [];
    $mapper = \CRM_Extension_System::singleton()->getMapper();
    foreach ($mapper->getActiveModuleFiles() as $module) {
      $key = is_array($module) ? $module['fullName'] ?? NULL : NULL;
      if (is_string($key)) {
        $extensions += self::extension($mapper, $key);
      }
    }
    $extra = \Civi::settings()->get('handbook_extra_dir');
    return self::build($extensions, is_string($extra) ? trim($extra) : '');
  }

  /**
   * @param array<string, array{dir: string, label: string}> $extensions
   *   Extension handbook directories keyed by extension key.
   */
  public static function build(array $extensions, string $extra): self {
    $sources = new self();
    $sources->dirs = $extensions;
    ksort($sources->dirs);
    $sources->addExtra($extra);
    return $sources;
  }

  /**
   * @return array<string, array{dir: string, label: string}>
   */
  private static function extension(\CRM_Extension_Mapper $mapper, string $key): array {
    try {
      $dir = realpath($mapper->keyToBasePath($key) . '/handbook');
      if ($dir === FALSE || !is_dir($dir)) {
        return [];
      }
      // keyToInfo() parses info.xml, and this runs on every CiviCRM page.
      $label = $mapper->keyToInfo($key)->label;
    }
    catch (\CRM_Extension_Exception $e) {
      return [];
    }
    return [$key => ['dir' => $dir, 'label' => is_string($label) ? $label : $key]];
  }

  private function addExtra(string $extra): void {
    if ($extra === '') {
      return;
    }
    if (!ExtraDir::isAbsolute($extra)) {
      $this->errors[] = ExtraDir::relativeMessage($extra);
      return;
    }
    // Core does not restrict extension keys, so an extension may be keyed "extra".
    if (isset($this->dirs[self::EXTRA])) {
      $this->errors[] = E::ts('The extra handbook directory is not read: an extension is already named “%1”.', [
        1 => self::EXTRA,
      ]);
      return;
    }
    $dir = realpath($extra);
    if ($dir === FALSE || !is_dir($dir) || !is_readable($dir)) {
      $this->errors[] = E::ts('The extra handbook directory “%1” is not a readable directory.', [
        1 => $extra,
      ]);
      return;
    }
    $this->dirs[self::EXTRA] = ['dir' => $dir, 'label' => E::ts('More manuals')];
  }

}
