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
    $sources = new self();
    $mapper = \CRM_Extension_System::singleton()->getMapper();
    foreach ($mapper->getActiveModuleFiles() as $module) {
      $key = is_array($module) ? $module['fullName'] ?? NULL : NULL;
      if (is_string($key)) {
        $sources->addExtension($mapper, $key);
      }
    }
    ksort($sources->dirs);
    $extra = \Civi::settings()->get('handbook_extra_dir');
    $sources->addExtra(is_string($extra) ? trim($extra) : '');
    return $sources;
  }

  private function addExtension(\CRM_Extension_Mapper $mapper, string $key): void {
    try {
      $dir = realpath($mapper->keyToBasePath($key) . '/handbook');
      $label = $mapper->keyToInfo($key)->label;
    }
    catch (\CRM_Extension_Exception $e) {
      return;
    }
    if ($dir !== FALSE && is_dir($dir)) {
      $this->dirs[$key] = ['dir' => $dir, 'label' => is_string($label) ? $label : $key];
    }
  }

  private function addExtra(string $extra): void {
    if ($extra === '') {
      return;
    }
    $dir = realpath($extra);
    if ($dir === FALSE || !is_dir($dir) || !is_readable($dir)) {
      $this->errors[] = E::ts('Das zusätzliche Handbuch-Verzeichnis „%1“ ist kein lesbares Verzeichnis.', [
        1 => $extra,
      ]);
      return;
    }
    $this->dirs[self::EXTRA] = ['dir' => $dir, 'label' => E::ts('Weitere Anleitungen')];
  }

}
