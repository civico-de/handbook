<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Api4\Navigation;

/**
 * Permission, routes, screens and context links through the CiviCRM layers.
 *
 * @group headless
 * @covers \Civi\Handbook\ContextHelp
 * @covers \Civi\Handbook\View
 * @covers \CRM_Handbook_Page_Handbook
 * @covers \CRM_Handbook_Page_Search
 * @covers \Civi\Handbook\AssetRoute
 */
final class IntegrationHeadlessTest extends HandbookTestCase {

  protected function tearDown(): void {
    unset($_GET['p'], $_GET['term'], $_GET['q']);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    parent::tearDown();
  }

  /**
   * @param list<string> $permissions
   */
  private function grant(array $permissions): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
  }

  public function testPermissionIsDeclared(): void {
    $permissions = \CRM_Core_Permission::basicPermissions(TRUE, TRUE);
    $this->assertArrayHasKey(ContextHelp::PERMISSION, $permissions);
  }

  public function testEveryRouteRequiresThePermission(): void {
    foreach (['civicrm/handbook', 'civicrm/handbook/search', 'civicrm/handbook/asset'] as $path) {
      $item = \CRM_Core_Menu::get($path);
      $this->assertIsArray($item, $path);
      $this->grant(['access CiviCRM']);
      $this->assertFalse(\CRM_Core_Permission::checkMenuItem($item), $path);
      $this->grant(['access CiviCRM', ContextHelp::PERMISSION]);
      $this->assertTrue(\CRM_Core_Permission::checkMenuItem($item), $path);
    }
  }

  public function testSettingsRouteRequiresAdministerCivicrm(): void {
    $item = \CRM_Core_Menu::get('civicrm/admin/setting/handbook');
    $this->assertIsArray($item);
    $this->grant(['access CiviCRM', ContextHelp::PERMISSION]);
    $this->assertFalse(\CRM_Core_Permission::checkMenuItem($item));
    $this->grant(['access CiviCRM', 'administer CiviCRM']);
    $this->assertTrue(\CRM_Core_Permission::checkMenuItem($item));
  }

  public function testNavigationEntryUnderSupport(): void {
    $item = Navigation::get(FALSE)
      ->addSelect('parent_id.name', 'url', 'permission')
      ->addWhere('name', '=', 'handbook_manual')
      ->execute()
      ->single();
    $this->assertSame('Support', $item['parent_id.name']);
    $this->assertSame('civicrm/handbook', $item['url']);
    $this->assertSame([ContextHelp::PERMISSION], $item['permission']);
  }

  public function testContextLinkIsAddedOnceOnMatchingRoute(): void {
    $this->grant([ContextHelp::PERMISSION]);
    $_GET['q'] = 'civicrm/dashboard';
    $event = new \Civi\Core\Event\GenericHookEvent();
    ContextHelp::onPageRun($event);
    ContextHelp::onPageRun($event);
    $markup = $this->regionMarkup();
    $this->assertSame(1, substr_count($markup, 'class="handbook-context"'));
    $this->assertStringContainsString('Handbuch: Alpha aus der Überschrift', $markup);
    $this->assertStringContainsString('p=extra%2Falpha', $markup);
  }

  public function testContextLinkOnQuickFormScreensOncePerRequest(): void {
    $this->grant([ContextHelp::PERMISSION]);
    $_GET['q'] = 'civicrm/event/add';
    $event = \Civi\Core\Event\GenericHookEvent::create(['formName' => 'CRM_Event_Form_ManageEvent_EventInfo']);
    ContextHelp::onBuildForm($event);
    ContextHelp::onBuildForm($event);
    ContextHelp::onPageRun($event);
    $markup = $this->regionMarkup();
    $this->assertSame(1, substr_count($markup, 'class="handbook-context"'));
    $this->assertStringContainsString('Handbuch: Alpha aus der Überschrift', $markup);
  }

  public function testNoContextLinkWithoutPermissionOrMatch(): void {
    $this->grant(['access CiviCRM']);
    $_GET['q'] = 'civicrm/dashboard';
    ContextHelp::onPageRun(new \Civi\Core\Event\GenericHookEvent());
    $this->grant([ContextHelp::PERMISSION]);
    $_GET['q'] = 'civicrm/event/manage';
    ContextHelp::onPageRun(new \Civi\Core\Event\GenericHookEvent());
    $this->assertStringNotContainsString('handbook-context', $this->regionMarkup());
  }

  public function testPageRendersContentTocAndSections(): void {
    $html = $this->runPage(new \CRM_Handbook_Page_Handbook(), ['p' => 'extra/alpha']);
    $this->assertStringContainsString('<h2 id="erster-abschnitt">', $html);
    $this->assertStringContainsString('aria-current="page"', $html);
    $this->assertStringContainsString('href="#zweiter-abschnitt"', $html);
    $this->assertStringContainsString('role="search"', $html);
  }

  public function testUnknownPageShowsIndexWithError(): void {
    $html = $this->runPage(new \CRM_Handbook_Page_Handbook(), ['p' => 'extra/missing']);
    $this->assertStringContainsString('Das Handbuch benutzen', $html);
    $this->assertStringNotContainsString('aria-current', $html);
    $this->assertStringContainsString('Diese Handbuchseite gibt es nicht.', $html);
  }

  public function testIndexListsSourcesAndErrors(): void {
    $html = $this->runPage(new \CRM_Handbook_Page_Handbook(), []);
    $this->assertStringContainsString('Weitere Anleitungen', $html);
    $this->assertStringContainsString('Das Handbuch benutzen', $html);
    $this->assertStringContainsString('extra/kaputt.md', $html);
  }

  public function testSearchScreenGroupsHitsByPageAndLinksAnchors(): void {
    $html = $this->runPage(new \CRM_Handbook_Page_Search(), ['term' => 'Fixturewort']);
    $this->assertStringContainsString('<p role="status">2 Treffer auf 2 Seiten für „Fixturewort“</p>', $html);
    $this->assertMatchesRegularExpression(
      '#<h3><a href="[^"]*extra%2Falpha">Alpha aus der Überschrift</a></h3>#',
      $html,
    );
    $this->assertStringContainsString('<p class="handbook-result-place">Weitere Anleitungen</p>', $html);
    $this->assertMatchesRegularExpression(
      '#<a href="[^"]*extra%2Falpha\#erster-abschnitt">Erster Abschnitt</a>#',
      $html,
    );
  }

  public function testPageHasBreadcrumbSectionsPagerAndCollapsibleToc(): void {
    $html = $this->runPage(new \CRM_Handbook_Page_Handbook(), ['p' => 'handbook/anleitungen-schreiben']);
    $this->assertMatchesRegularExpression(
      '#<nav class="handbook-breadcrumb" aria-label="Pfad im Handbuch">\s*<ol>\s*<li><a href="[^"]*civicrm/handbook">Handbuch</a></li>#',
      $html,
    );
    $this->assertStringContainsString('<li>Schreiben</li>', $html);
    $this->assertStringContainsString('<h2 id="handbook-sections-heading">Auf dieser Seite</h2>', $html);
    $this->assertMatchesRegularExpression(
      '#rel="prev" href="[^"]*handbook%2Fbenutzen"><span class="handbook-pager-dir">Vorherige Seite</span> Das Handbuch benutzen</a>#',
      $html,
    );
    $this->assertMatchesRegularExpression('#rel="next" href="[^"]*extra%2Falpha">#', $html);
    $this->assertSame(
      1,
      substr_count($html, '<details class="handbook-group" open>'),
      'only the current source is open',
    );
    $this->assertStringContainsString('<h3 class="handbook-section-name">Schreiben</h3>', $html);
  }

  public function testIndexShowsOneCardPerSourceWithSections(): void {
    $html = $this->runPage(new \CRM_Handbook_Page_Handbook(), []);
    $this->assertSame(2, substr_count($html, '<section class="handbook-card"'));
    $this->assertStringContainsString('<h2 id="handbook-card-extra">Weitere Anleitungen</h2>', $html);
    $this->assertStringContainsString('<h3 class="handbook-section-name">Lesen</h3>', $html);
    $this->assertStringNotContainsString('<details', $html);
  }

  /**
   * @return array<string, array{string, int}>
   */
  public static function assets(): array {
    return [
      'image' => ['extra/bild.png', 200],
      'traversal' => ['extra/../outside.png', 400],
      'missing' => ['extra/missing.png', 404],
    ];
  }

  /**
   * @dataProvider assets
   */
  public function testAssetRouteAnswersWithStatus(string $p, int $status): void {
    $request = (new \GuzzleHttp\Psr7\ServerRequest('GET', '/civicrm/handbook/asset'))->withQueryParams(['p' => $p]);
    $response = \Civi\Handbook\AssetRoute::handle($request);
    $this->assertSame($status, $response->getStatusCode());
    if ($status === 200) {
      $this->assertSame('image/png', $response->getHeaderLine('Content-Type'));
      $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }
  }

  /**
   * @param array<string, string> $params
   */
  private function runPage(\CRM_Core_Page $page, array $params): string {
    foreach ($params as $key => $value) {
      $_GET[$key] = $value;
    }
    ob_start();
    try {
      $page->run();
    }
    finally {
      $html = (string) ob_get_clean();
    }
    return $html;
  }

  private function regionMarkup(): string {
    $markup = '';
    foreach (\CRM_Core_Region::instance('page-body')->getAll() as $snippet) {
      $markup .= is_string($snippet['markup'] ?? NULL) ? $snippet['markup'] : '';
    }
    return $markup;
  }

}
