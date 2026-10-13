<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Handbook\Vendor\Symfony\Component\Yaml\Yaml;

/**
 * Missing Composer libraries must not break the site: screens explain, the
 * status check flags it, and the release archive carries vendor/.
 *
 * @group headless
 * @covers \Civi\Handbook\Dependencies
 * @covers \Civi\Handbook\StatusCheck
 */
final class DependenciesHeadlessTest extends HandbookTestCase {

  protected function tearDown(): void {
    unset(\Civi::$statics[Dependencies::class]);
    parent::tearDown();
  }

  private function simulateMissing(): void {
    \Civi::$statics[Dependencies::class]['simulateMissing'] = TRUE;
  }

  public function testLoadingAMissingAutoloaderIsHarmless(): void {
    Dependencies::load('/nonexistent/vendor/autoload.php');
    $this->assertTrue(Dependencies::available());
  }

  public function testStatusCheckFlagsMissingLibraries(): void {
    $this->assertSame([], $this->checkNames());
    $this->simulateMissing();
    $this->assertSame(['handbook_dependencies'], $this->checkNames());
  }

  public function testScreensExplainInsteadOfFailing(): void {
    $this->simulateMissing();
    $_GET['p'] = 'extra/alpha';
    ob_start();
    try {
      (new \CRM_Handbook_Page_Handbook())->run();
    }
    finally {
      $html = (string) ob_get_clean();
      unset($_GET['p']);
    }
    $this->assertStringContainsString('composer install --no-dev', $html);
    $this->assertStringNotContainsString('handbook-toc', $html);
  }

  public function testAssetRouteAndContextLinksStandDown(): void {
    $this->simulateMissing();
    $request = (new \GuzzleHttp\Psr7\ServerRequest('GET', '/'))->withQueryParams(['p' => 'extra/bild.png']);
    $this->assertSame(503, AssetRoute::handle($request)->getStatusCode());
    $_GET['q'] = 'civicrm/dashboard';
    ContextHelp::onPageRun(new \Civi\Core\Event\GenericHookEvent());
    unset($_GET['q']);
    $this->assertFalse(isset(\Civi::$statics[ContextHelp::class]));
  }

  public function testReleaseArchiveBundlesVendor(): void {
    $workflow = Yaml::parseFile(dirname(__DIR__, 4) . '/.github/workflows/release.yml');
    $this->assertIsArray($workflow);
    $this->assertTrue($workflow['jobs']['release']['with']['composer_install'] ?? NULL);
  }

  /**
   * @return list<string>
   */
  private function checkNames(): array {
    $event = \Civi\Core\Event\GenericHookEvent::create(['messages' => []]);
    StatusCheck::onCheck($event);
    return array_map(static fn (\CRM_Utils_Check_Message $m): string => $m->getName(), $event->messages);
  }

}
