<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Headless base: the extension installed, the extra directory pointing at
 * tests/fixtures/extra, and the per-request library and locale reset.
 */
abstract class HandbookTestCase extends TestCase implements HeadlessInterface, TransactionalInterface {

  public const FIXTURES = __DIR__ . '/../../../fixtures';

  private ?\Civi\Core\Locale $savedLocale = NULL;

  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    return ck_headless()->apply();
  }

  protected function setUp(): void {
    parent::setUp();
    $this->useExtraDir((string) realpath(self::FIXTURES . '/extra'));
  }

  protected function tearDown(): void {
    $this->savedLocale?->apply();
    $this->savedLocale = NULL;
    unset(\Civi::$statics[Library::class], \Civi::$statics[ContextHelp::class]);
    parent::tearDown();
  }

  /**
   * Switches ts() and the reader's locale. Applied as is: negotiate() would
   * fall back to en_US on a site without core's catalogue for $locale.
   */
  protected function useLocale(string $locale): void {
    $this->savedLocale ??= \Civi\Core\Locale::detect();
    (new \Civi\Core\Locale([
      'nominal' => $locale,
      'ts' => $locale,
      'moneyFormat' => $locale,
      'uf' => $locale,
    ]))->apply();
  }

  protected function useExtraDir(string $dir): Library {
    \Civi::settings()->set('handbook_extra_dir', $dir);
    unset(\Civi::$statics[Library::class]);
    return Library::fromSystem();
  }

}
