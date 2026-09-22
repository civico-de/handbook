<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Api4\Navigation;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Uninstall removes the managed menu entry; reinstall
 * brings the entry back.
 *
 * @group headless
 */
final class LifecycleHeadlessTest extends TestCase implements HeadlessInterface {

  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    return ck_headless()->apply();
  }

  protected function tearDown(): void {
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('handbook') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(['handbook']);
    }
    parent::tearDown();
  }

  public function testUninstallAndReinstall(): void {
    $manager = \CRM_Extension_System::singleton()->getManager();
    $this->assertSame(1, $this->navigationCount());

    $manager->disable(['handbook']);
    $manager->uninstall(['handbook']);
    $this->assertSame(\CRM_Extension_Manager::STATUS_UNINSTALLED, $manager->getStatus('handbook'));
    $this->assertSame(0, $this->navigationCount());

    $manager->install(['handbook']);
    $this->assertSame(1, $this->navigationCount());
  }

  private function navigationCount(): int {
    return Navigation::get(FALSE)->addWhere('name', '=', 'handbook')->execute()->count();
  }

}
