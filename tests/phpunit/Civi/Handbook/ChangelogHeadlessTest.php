<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * @group headless
 * @covers \Civi\Handbook\Library
 * @covers \Civi\Handbook\Sources
 * @covers \Civi\Handbook\Page
 * @covers \Civi\Handbook\PageSections
 */
final class ChangelogHeadlessTest extends HandbookTestCase {

  public function testExtensionChangelogIsTheLastPageOfItsManual(): void {
    $pages = array_values(array_filter(
      Library::fromSystem()->pages(),
      static fn (Page $p): bool => $p->source() === 'handbook',
    ));
    $last = end($pages);
    $this->assertSame('handbook/changes', $last->id);
    $this->assertSame(realpath(Catalogue::ROOT . '/CHANGELOG.md'), $last->file);
    $this->assertSame('Changes', $last->title);
    $this->assertStringStartsWith('# Changelog', Library::fromSystem()->body($last));
  }

  public function testChangelogPageIsTitledInTheReadersLanguage(): void {
    $this->useLocale('de_DE');
    $this->assertSame('Änderungen', Library::fromSystem()->find('handbook/changes')->title);
  }

  public function testManualPageOfTheSameSlugWins(): void {
    $dir = $this->tempDir();
    file_put_contents($dir . '/changes.md', "# Release notes\n");
    $changelog = $this->tempDir() . '/CHANGELOG.md';
    file_put_contents($changelog, "# Changelog\n");
    $library = new Library(['x' => ['dir' => $dir, 'label' => 'X', 'changelog' => $changelog]]);
    $this->assertSame(['x/changes'], array_map(static fn (Page $p): string => $p->id, $library->pages()));
    $this->assertSame($dir . '/changes.md', $library->find('x/changes')->file);
  }

  public function testSourceWithoutChangelogHasNoChangesPage(): void {
    $dir = $this->tempDir();
    file_put_contents($dir . '/index.md', "# X\n");
    $library = new Library(['x' => ['dir' => $dir, 'label' => 'X']]);
    $this->assertSame(['x/index'], array_map(static fn (Page $p): string => $p->id, $library->pages()));
  }

  public function testSidebarListsOnlyTheNewestVersions(): void {
    $versions = '';
    for ($minor = 12; $minor >= 1; $minor--) {
      $versions .= "## [1.{$minor}.0] - 2026-01-01\n\n### Fixed\n\n- A fix.\n\n";
    }
    $library = $this->libraryWithChangelog("# Changelog\n\n## [Unreleased]\n\n" . $versions);
    $page = $library->find('x/changes');
    $headings = (new Renderer(static fn (): string => ''))->render($page->dir(), $library->body($page))['headings'];
    $sections = PageSections::of($page, $headings);
    $this->assertSame(
      ['[1.12.0] - 2026-01-01', '[1.11.0] - 2026-01-01', '[1.10.0] - 2026-01-01'],
      array_slice(array_column($sections, 'text'), 0, 3),
    );
    $this->assertCount(PageSections::CHANGELOG_VERSIONS, $sections);
    $this->assertSame([2], array_values(array_unique(array_column($sections, 'level'))));
  }

  public function testEmptyUnreleasedSectionIsLeftOut(): void {
    $library = $this->libraryWithChangelog(
      "# Changelog\n\n## [Unreleased]\n\n## [1.0.0] - 2026-01-01\n\n### Added\n\n- First.\n",
    );
    $body = $library->body($library->find('x/changes'));
    $this->assertStringNotContainsString('Unreleased', $body);
    $this->assertStringContainsString("# Changelog\n\n## [1.0.0]", $body);
    $this->assertSame([], (new Search($library, new RenderCache(NULL)))->run('Unreleased'));
  }

  public function testUnreleasedSectionWithEntriesStays(): void {
    $library = $this->libraryWithChangelog(
      "# Changelog\n\n## [Unreleased]\n\n### Fixed\n\n- Pending.\n\n## [1.0.0] - 2026-01-01\n\n### Added\n\n- First.\n",
    );
    $this->assertStringContainsString(
      "## [Unreleased]\n\n### Fixed\n\n- Pending.",
      $library->body($library->find('x/changes')),
    );
  }

  public function testOwnChangesPageKeepsTheUsualSidebar(): void {
    $dir = $this->tempDir();
    file_put_contents($dir . '/changes.md', "# Changes\n\n## [Unreleased]\n\n## Two\n\n### Three\n");
    $library = new Library(['x' => ['dir' => $dir, 'label' => 'X']]);
    $page = $library->find('x/changes');
    $this->assertFalse($page->isChangelog());
    $body = $library->body($page);
    $this->assertStringContainsString('## [Unreleased]', $body);
    $headings = (new Renderer(static fn (): string => ''))->render($page->dir(), $body)['headings'];
    $this->assertSame(['[Unreleased]', 'Two', 'Three'], array_column(PageSections::of($page, $headings), 'text'));
  }

  private function libraryWithChangelog(string $markdown): Library {
    $dir = $this->tempDir();
    file_put_contents($dir . '/index.md', "# X\n");
    $changelog = $this->tempDir() . '/CHANGELOG.md';
    file_put_contents($changelog, $markdown);
    return new Library(['x' => ['dir' => $dir, 'label' => 'X', 'changelog' => $changelog]]);
  }

  private function tempDir(): string {
    $dir = sys_get_temp_dir() . '/handbook-test-' . bin2hex(random_bytes(4));
    mkdir($dir);
    return $dir;
  }

}
