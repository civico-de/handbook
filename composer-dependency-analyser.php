<?php

declare(strict_types = 1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

/**
 * ckdeps config. vendor/ is excluded because the psr-0 rule `"CRM_": "."`
 * would otherwise scan it. Ignores beyond the bundled set: bare `Civi`,
 * `CiviMix\` (civix mixin loader), `ezc*` (core's zetacomponents) and the
 * PSR-7/PSR-3 interfaces plus Guzzle PSR-7, which core's vendor/ provides.
 */
return (new Configuration())
  ->addPathToExclude(__DIR__ . '/vendor')
  ->ignoreUnknownClassesRegex('~^(CRM_|Civi\\\\|Civi$|CiviCRM|CiviMix\\\\|ezc|GuzzleHttp\\\\|Psr\\\\)~')
  ->ignoreUnknownFunctionsRegex('~^(civicrm_|civi|CiviMix\\\\)~')
  ->disableReportingUnmatchedIgnores();
