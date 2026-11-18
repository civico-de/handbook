<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Pages in subdirectories of a manual: ids with the path, sections named
 * after the directory, its index page first.
 *
 * @group headless
 * @covers \Civi\Handbook\Variants
 * @covers \Civi\Handbook\MarkdownTree
 * @covers \Civi\Handbook\DirectorySection
 * @covers \Civi\Handbook\Ordering
 * @covers \Civi\Handbook\Page
 */
final class NestedPagesHeadlessTest extends HandbookTestCase {

  private string $dir;

  protected function setUp(): void {
    parent::setUp();
    $this->dir = sys_get_temp_dir() . '/handbook-nested-' . bin2hex(random_bytes(4));
    $this->write([
      'index.md' => "# Start\n",
      'top.md' => "---\norder: 5\n---\n# Oben\n",
      'setup/index.md' => "---\norder: 20\n---\n# Einrichtung\n",
      'setup/index.de_DE.md' => "# Einrichten\n",
      'setup/alpha.md' => "# Alpha\n",
      'setup/rechte.md' => "---\norder: 1\n---\n# Rechte\n",
      'setup/eigen.md' => "---\nsection: Anderswo\n---\n# Eigen\n",
      'faq/frage.md' => "---\norder: 10\n---\n# Frage\n",
      'faq/tief/mehr.md' => "# Mehr\n",
      'images/bild.png' => 'png',
      '.hidden/geheim.md' => "# Geheim\n",
      'bad name/seite.md' => "# Kaputt\n",
    ]);
  }

  protected function tearDown(): void {
    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $file) {
      /** @var \SplFileInfo $file */
      $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($this->dir);
    parent::tearDown();
  }

  public function testSubdirectoriesAreSectionsInOrder(): void {
    $pages = $this->library('en_US')->pages();
    $this->assertSame(
      [
        'ext/index' => NULL,
        'ext/top' => NULL,
        'ext/faq/frage' => 'faq',
        'ext/setup/index' => 'Einrichtung',
        'ext/setup/rechte' => 'Einrichtung',
        'ext/setup/alpha' => 'Einrichtung',
        'ext/setup/eigen' => 'Anderswo',
        'ext/faq/tief/mehr' => 'tief',
      ],
      array_combine(
        array_map(static fn (Page $p): string => $p->id, $pages),
        array_map(static fn (Page $p): ?string => $p->section, $pages),
      ),
    );
  }

  public function testSectionTakesTheTitleOfTheIndexVariant(): void {
    $page = $this->library('de_DE')->find('ext/setup/alpha');
    $this->assertSame('Einrichten', $page->section);
  }

  public function testOnlyTheTopIndexIsTheLandingPage(): void {
    $library = $this->library('en_US');
    $this->assertTrue($library->find('ext/index')->isLanding());
    $this->assertFalse($library->find('ext/setup/index')->isLanding());
    $this->assertTrue($library->find('ext/setup/index')->isSectionIndex());
    $this->assertSame('ext/setup', $library->find('ext/setup/index')->dir());
  }

  public function testHiddenDirectoriesAreSkippedAndBadNamesReported(): void {
    $library = $this->library('en_US');
    $ids = array_map(static fn (Page $p): string => $p->id, $library->pages());
    $this->assertNotContains('ext/.hidden/geheim', $ids);
    $this->assertSame(['ext/bad name/seite.md: invalid file name.'], $library->errors());
  }

  public function testAnUnreadableSubdirectoryIsReportedAndTheRestRead(): void {
    if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
      $this->markTestSkipped('root reads every directory');
    }
    mkdir($this->dir . '/locked');
    file_put_contents($this->dir . '/locked/seite.md', "# Zu\n");
    chmod($this->dir . '/locked', 0o000);
    try {
      $library = $this->library('en_US');
      $this->assertContains('ext/setup/alpha', array_map(static fn (Page $p): string => $p->id, $library->pages()));
      $this->assertContains('ext/locked: directory not readable.', $library->errors());
    }
    finally {
      chmod($this->dir . '/locked', 0o755);
    }
  }

  public function testAnUnreadableSourceIsReported(): void {
    if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
      $this->markTestSkipped('root reads every directory');
    }
    chmod($this->dir, 0o000);
    try {
      $library = $this->library('en_US');
      $this->assertSame([], $library->pages());
      $this->assertSame(['ext: directory not readable.'], $library->errors());
    }
    finally {
      chmod($this->dir, 0o755);
    }
  }

  public function testImagesInSubdirectoriesResolve(): void {
    $asset = (new AssetResolver($this->library('en_US')))->resolve('ext/images/bild.png');
    $this->assertSame('image/png', $asset['type']);
  }

  private function library(string $locale): Library {
    return new Library(['ext' => ['dir' => (string) realpath($this->dir), 'label' => 'Ext']], [], NULL, $locale);
  }

  /**
   * @param array<string, string> $files
   */
  private function write(array $files): void {
    foreach ($files as $path => $content) {
      $file = $this->dir . '/' . $path;
      if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0o777, TRUE);
      }
      file_put_contents($file, $content);
    }
  }

}
