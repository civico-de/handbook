<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Breadcrumb and previous/next links in table-of-contents order.
 *
 * @group headless
 * @covers \Civi\Handbook\PageNav
 */
final class PageNavHeadlessTest extends HandbookTestCase {

  private Library $library;

  private PageNav $nav;

  protected function setUp(): void {
    parent::setUp();
    $dir = (string) realpath(self::FIXTURES . '/sources');
    $this->library = new Library([
      'alpha' => ['dir' => $dir . '/alpha', 'label' => 'Zweiter Name'],
      'beta' => ['dir' => $dir . '/beta', 'label' => 'Erster Name'],
      'zulu' => ['dir' => $dir . '/zulu', 'label' => 'Zulu'],
    ]);
    $this->nav = new PageNav(
      $this->library,
      static fn (string $path, array $query): string => '/' . $path . ($query === [] ? '' : '?p=' . $query['p']),
    );
  }

  /**
   * @return array{prev: string|null, next: string|null}
   */
  private function neighbours(string $id): array {
    $pager = $this->nav->pager($this->library->find($id));
    return ['prev' => $pager['prev']['url'] ?? NULL, 'next' => $pager['next']['url'] ?? NULL];
  }

  public function testPagerFollowsTheTocAcrossSourcesLandingPagesIncluded(): void {
    $this->assertSame(['prev' => NULL, 'next' => '/civicrm/handbook?p=zulu/seite'], $this->neighbours('zulu/index'));
    $this->assertSame(
      ['prev' => '/civicrm/handbook?p=zulu/seite', 'next' => '/civicrm/handbook?p=beta/seite'],
      $this->neighbours('beta/index'),
    );
    $this->assertSame(['prev' => '/civicrm/handbook?p=beta/seite', 'next' => NULL], $this->neighbours('alpha/seite'));
    $this->assertSame(
      'Beta ohne Order',
      $this->nav->pager($this->library->find('zulu/seite'))['next']['title'] ?? NULL,
    );
  }

  public function testPageUnknownToTheListHasNoNeighbours(): void {
    $stray = new Page('zulu/weg', '/weg.md', ['title' => 'Weg']);
    $this->assertSame(['prev' => NULL, 'next' => NULL], $this->nav->pager($stray));
  }

  public function testBreadcrumbLinksTheLandingPageAndNamesTheSection(): void {
    $this->assertSame(
      [
        ['label' => 'Handbuch', 'url' => '/civicrm/handbook'],
        ['label' => 'Zulu', 'url' => '/civicrm/handbook?p=zulu/index'],
        ['label' => 'Erste Schritte', 'url' => NULL],
      ],
      $this->nav->breadcrumb($this->library->find('zulu/seite')),
    );
  }

  public function testBreadcrumbOfALandingPageOrASourceWithoutOne(): void {
    $this->assertSame(
      ['label' => 'Zulu', 'url' => NULL],
      $this->nav->breadcrumb($this->library->find('zulu/index'))[1],
    );
    $crumbs = $this->nav->breadcrumb($this->library->find('alpha/seite'));
    $this->assertCount(2, $crumbs);
    $this->assertNull($crumbs[1]['url']);
  }

}
