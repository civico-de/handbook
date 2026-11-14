<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Finds the source directories: every enabled extension with a handbook/
 * directory, or the one its info.xml tag `handbook:dir=<name>` names, plus the
 * configured extra directory as source "extra".
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
    $errors = [];
    $mapper = \CRM_Extension_System::singleton()->getMapper();
    // Core caches the tags of all extensions, so this reads no info.xml.
    $declared = ExtensionDir::declared($mapper->getAllTags());
    // Core documents each entry as ['prefix' => …, 'fullName' => …, 'filePath' => …].
    /** @var list<array{fullName: string}> $modules */
    $modules = $mapper->getActiveModuleFiles();
    foreach (array_column($modules, 'fullName') as $key) {
      try {
        $extensions += self::extension($mapper, $key, $declared[$key] ?? []);
      }
      catch (\InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
      }
    }
    $extra = \Civi::settings()->get('handbook_extra_dir');
    return self::build($extensions, is_string($extra) ? trim($extra) : '', $errors);
  }

  /**
   * @param array<string, array{dir: string, label: string}> $extensions
   *   Extension handbook directories keyed by extension key.
   * @param list<string> $errors
   */
  public static function build(array $extensions, string $extra, array $errors = []): self {
    $sources = new self();
    $sources->dirs = $extensions;
    $sources->errors = $errors;
    ksort($sources->dirs);
    $sources->addExtra($extra);
    return $sources;
  }

  /**
   * @param list<string> $declared
   *
   * @return array<string, array{dir: string, label: string}>
   */
  private static function extension(\CRM_Extension_Mapper $mapper, string $key, array $declared): array {
    try {
      $dir = ExtensionDir::resolve($mapper->keyToBasePath($key), $key, $declared);
      if ($dir === NULL) {
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
