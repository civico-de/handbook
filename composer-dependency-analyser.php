<?php

declare(strict_types = 1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

/**
 * ckdeps config. vendor/ is excluded because the psr-0 rule `"CRM_": "."`
 * would otherwise scan it. Ignores beyond the bundled set: bare `Civi`,
 * `CiviMix\` (civix mixin loader), `ezc*` (core's zetacomponents) and the
 * PSR-7/PSR-3 interfaces plus Guzzle PSR-7, which core's vendor/ provides.
 *
 * ext-ctype stands in for the replaced symfony/polyfill-ctype: symfony/yaml in
 * vendor/ calls ctype_*, handbook's own code does not.
 */
return (new Configuration())
  ->addPathToExclude(__DIR__ . '/vendor')
  ->ignoreUnknownClassesRegex('~^(CRM_|Civi\\\\|Civi$|CiviCRM|CiviMix\\\\|ezc|GuzzleHttp\\\\|Psr\\\\)~')
  ->ignoreUnknownFunctionsRegex('~^(civicrm_|civi|CiviMix\\\\)~')
  ->ignoreErrorsOnExtension('ext-ctype', [ErrorType::UNUSED_DEPENDENCY])
  ->disableReportingUnmatchedIgnores();
