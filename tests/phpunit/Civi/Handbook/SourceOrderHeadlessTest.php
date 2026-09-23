<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * handbook/index.md orders its source and is the source's landing page.
 *
 * @group headless
 * @covers \Civi\Handbook\Library
 * @covers \Civi\Handbook\Ordering
 * @covers \Civi\Handbook\View
 */
final class SourceOrderHeadlessTest extends HandbookTestCase {

  private function library(): Library {
    $dir = (string) realpath(self::FIXTURES . '/sources');
    // Labels deliberately sort against the keys.
    return new Library([
      'alpha' => ['dir' => $dir . '/alpha', 'label' => 'Zweiter Name'],
      'beta' => ['dir' => $dir . '/beta', 'label' => 'Erster Name'],
      'zulu' => ['dir' => $dir . '/zulu', 'label' => 'Zulu'],
    ]);
  }

  public function testIndexOrderComesFirstThenLabels(): void {
    $sources = array_values(array_unique(array_map(
      static fn (Page $p): string => $p->source(),
      $this->library()->pages(),
    )));
    $this->assertSame(['zulu', 'beta', 'alpha'], $sources);
  }

  public function testLandingPageIsTheOverviewEntryNotAListedPage(): void {
    $toc = (new View(
      $this->library(),
      static fn (string $path, array $query): string => '/' . $path . '?p=' . $query['p'],
    ))->toc('zulu/index');
    $this->assertSame('Zulu', $toc[0]['label']);
    $this->assertSame('/civicrm/handbook?p=zulu/index', $toc[0]['landing']['url'] ?? NULL);
    $this->assertTrue($toc[0]['landing']['current'] ?? NULL);
    $this->assertTrue($toc[0]['open']);
    $this->assertFalse($toc[1]['open'], 'only the current source is open');
    $this->assertSame(['zulu/seite'], array_column($toc[0]['sections'][0]['pages'], 'id'));
    $this->assertNull($toc[2]['landing'], 'alpha has no landing page');
  }

  public function testLandingPageRenders(): void {
    $library = $this->library();
    $this->assertStringContainsString('Startseite der Zulu-Quelle.', $library->body($library->find('zulu/index')));
  }

}
