<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Headless base: the extension installed, the extra directory pointing at
 * tests/fixtures/extra, and the per-request library reset.
 */
abstract class HandbookTestCase extends TestCase implements HeadlessInterface, TransactionalInterface {

  public const FIXTURES = __DIR__ . '/../../../fixtures';

  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    return ck_headless()->apply();
  }

  protected function setUp(): void {
    parent::setUp();
    $this->useExtraDir((string) realpath(self::FIXTURES . '/extra'));
  }

  protected function tearDown(): void {
    unset(\Civi::$statics[Library::class], \Civi::$statics[ContextHelp::class]);
    parent::tearDown();
  }

  protected function useExtraDir(string $dir): Library {
    \Civi::settings()->set('handbook_extra_dir', $dir);
    unset(\Civi::$statics[Library::class]);
    return Library::fromSystem();
  }

}
