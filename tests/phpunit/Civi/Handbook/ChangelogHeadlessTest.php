<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * @group headless
 * @covers \Civi\Handbook\Library
 * @covers \Civi\Handbook\Sources
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

  private function tempDir(): string {
    $dir = sys_get_temp_dir() . '/handbook-test-' . bin2hex(random_bytes(4));
    mkdir($dir);
    return $dir;
  }

}
