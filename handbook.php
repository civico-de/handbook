<?php

declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'handbook.civix.php';
$handbookAutoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($handbookAutoload)) {
  throw new RuntimeException('Handbook dependencies are missing. Run composer install in the extension directory.');
}
$handbookLoader = require_once $handbookAutoload;
if ($handbookLoader instanceof \Composer\Autoload\ClassLoader) {
  // Appended, not prepended: a shared package must not shadow core's own version.
  $handbookLoader->unregister();
  $handbookLoader->register(FALSE);
}
// phpcs:enable

/**
 * Lifecycle glue only; behaviour lives in Civi\Handbook listeners.
 */
function handbook_civicrm_config(\CRM_Core_Config $config): void {
  _handbook_civix_civicrm_config($config);
}

function handbook_civicrm_install(): void {
  _handbook_civix_civicrm_install();
}

function handbook_civicrm_enable(): void {
  _handbook_civix_civicrm_enable();
}
