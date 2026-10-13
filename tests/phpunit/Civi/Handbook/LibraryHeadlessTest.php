<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * @group headless
 * @covers \Civi\Handbook\Library
 * @covers \Civi\Handbook\Variants
 * @covers \Civi\Handbook\PageName
 * @covers \Civi\Handbook\Ordering
 * @covers \Civi\Handbook\Page
 * @covers \Civi\Handbook\PageLoader
 * @covers \Civi\Handbook\PageMeta
 * @covers \Civi\Handbook\Frontmatter
 * @covers \Civi\Handbook\Sources
 */
final class LibraryHeadlessTest extends HandbookTestCase {

  public function testDiscoversEnabledExtensionsAndTheExtraDirectory(): void {
    $library = Library::fromSystem();
    $this->assertSame(['handbook', 'extra'], array_keys($library->sources()));
    $ids = array_map(static fn (Page $p): string => $p->id, $library->pages());
    $this->assertSame(
      [
        'handbook/using',
        'handbook/writing-manuals',
        'extra/alpha',
        'extra/ohne-titel',
        'extra/zeta',
      ],
      $ids,
    );
  }

  public function testTitleFallsBackToFirstHeadingThenFilename(): void {
    $library = Library::fromSystem();
    $this->assertSame('Using the handbook', $library->find('handbook/using')->title);
    $this->assertSame('Alpha aus der Überschrift', $library->find('extra/alpha')->title);
    $this->assertSame('Zeta ohne Kopfdaten', $library->find('extra/zeta')->title);
    $this->assertSame('ohne-titel', $library->find('extra/ohne-titel')->title);
    $this->assertSame(5, $library->find('extra/alpha')->order);
    $this->assertNull($library->find('extra/zeta')->order);
  }

  public function testBrokenFrontmatterIsReportedNotSkippedSilently(): void {
    $errors = Library::fromSystem()->errors();
    $this->assertCount(1, $errors);
    $this->assertStringContainsString('extra/kaputt.md', $errors[0]);
  }

  public function testWrongFrontmatterTypesAreReported(): void {
    $dir = $this->tempDir();
    file_put_contents($dir . '/a.md', "---\norder: erst\n---\n# A\n");
    file_put_contents($dir . '/b.md', "---\nroutes: civicrm/x\n---\n# B\n");
    $library = $this->useExtraDir($dir);
    $this->assertSame(
      [],
      array_filter($library->pages(), static fn (Page $p): bool => $p->source() === Sources::EXTRA),
    );
    $this->assertCount(2, $library->errors());
  }

  public function testUnreadableExtraDirectoryIsAnError(): void {
    $library = $this->useExtraDir('/nonexistent/handbook');
    $this->assertArrayNotHasKey(Sources::EXTRA, $library->sources());
    $this->assertStringContainsString('/nonexistent/handbook', $library->errors()[0]);
  }

  public function testEmptyExtraDirectoryMeansExtensionsOnly(): void {
    $library = $this->useExtraDir('');
    $this->assertSame(['handbook'], array_keys($library->sources()));
    $this->assertSame([], $library->errors());
  }

  public function testRoutesMatchWithSlashesTrimmed(): void {
    $library = Library::fromSystem();
    $this->assertSame(
      ['extra/alpha'],
      array_map(static fn (Page $p): string => $p->id, $library->forRoute('/civicrm/dashboard/')),
    );
    $this->assertCount(1, $library->forRoute('civicrm/contact/search'));
    $this->assertSame([], $library->forRoute('civicrm/event/manage'));
  }

  public function testBodyDropsFrontmatter(): void {
    $library = Library::fromSystem();
    $body = $library->body($library->find('extra/alpha'));
    $this->assertStringStartsWith("\n# Alpha", $body);
  }

  public function testUnknownPageIsNotFound(): void {
    $this->expectExceptionCode(404);
    Library::fromSystem()->find('extra/missing');
  }

  public function testFileListIsCachedUntilSystemFlush(): void {
    $dir = $this->tempDir();
    file_put_contents($dir . '/one.md', "# One\n");
    $this->assertCount(1, $this->extraPages($this->useExtraDir($dir)));

    file_put_contents($dir . '/two.md', "# Two\n");
    $this->assertCount(1, $this->extraPages($this->useExtraDir($dir)), 'served from cache');

    \Civi::rebuild(['system' => TRUE])->execute();
    $this->assertCount(2, $this->extraPages($this->useExtraDir($dir)));
  }

  public function testListCachedBeforeThePageClassChangedIsNotRead(): void {
    $dir = $this->tempDir();
    file_put_contents($dir . '/one.md', "# One\n");
    $sources = ['s' => ['dir' => $dir, 'label' => 'S']];
    $cache = new \CRM_Utils_Cache_ArrayCache([]);
    $cache->set('handbook_pages_' . md5(serialize($sources)), ['pages' => [], 'errors' => []]);
    $this->assertCount(1, (new Library($sources, [], $cache))->pages());
  }

  public function testPageListKeyDependsOnThePageProperties(): void {
    $sources = ['s' => ['dir' => '/d', 'label' => 'S']];
    $this->assertNotSame(
      Library::pagesKey($sources, 'en_US', ['id', 'file', 'title', 'order', 'routes']),
      Library::pagesKey($sources, 'en_US', ['id', 'file', 'title', 'order', 'section', 'routes']),
    );
    $this->assertSame(
      Library::pagesKey($sources, 'en_US', ['id', 'title']),
      Library::pagesKey($sources, 'en_US', ['id', 'title']),
    );
  }

  public function testSameOrderAndTitleSortByIdWhateverTheInputOrder(): void {
    $a = new Page('extra/a', '/a.md', ['title' => 'Gleich', 'order' => 1]);
    $b = new Page('extra/b', '/b.md', ['title' => 'Gleich', 'order' => 1]);
    $this->assertLessThan(0, Ordering::pages($a, $b));
    $this->assertGreaterThan(0, Ordering::pages($b, $a));
  }

  public function testExplicitMaximumOrderStillSortsBeforeUnordered(): void {
    $ordered = new Page('extra/z', '/z.md', ['title' => 'Zuletzt', 'order' => PHP_INT_MAX]);
    $unordered = new Page('extra/a', '/a.md', ['title' => 'Anfang', 'order' => NULL]);
    $this->assertLessThan(0, Ordering::pages($ordered, $unordered));
    $this->assertGreaterThan(0, Ordering::pages($unordered, $ordered));
  }

  public function testSourceWithMaximumLandingOrderSortsBeforeSourceWithout(): void {
    $groups = [
      'a' => [new Page('a/seite', '/a.md', ['title' => 'Seite', 'order' => NULL])],
      'z' => [new Page('z/index', '/z.md', ['title' => 'Start', 'order' => PHP_INT_MAX])],
    ];
    $sources = ['a' => ['dir' => '/a', 'label' => 'A'], 'z' => ['dir' => '/z', 'label' => 'Z']];
    $ids = array_map(static fn (Page $p): string => $p->id, Ordering::sources($groups, $sources));
    $this->assertSame(['z/index', 'a/seite'], $ids);
  }

  /**
   * @return list<\Civi\Handbook\Page>
   */
  private function extraPages(Library $library): array {
    return array_values(array_filter($library->pages(), static fn (Page $p): bool => $p->source() === Sources::EXTRA));
  }

  private function tempDir(): string {
    $dir = sys_get_temp_dir() . '/handbook-test-' . bin2hex(random_bytes(4));
    mkdir($dir);
    return $dir;
  }

}
