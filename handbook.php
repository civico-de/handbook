<?php

declare(strict_types = 1);

require_once 'handbook.civix.php';
// A missing vendor/ disables the screens and shows in system status.
\Civi\Handbook\Dependencies::load(__DIR__ . '/vendor/autoload.php');

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
