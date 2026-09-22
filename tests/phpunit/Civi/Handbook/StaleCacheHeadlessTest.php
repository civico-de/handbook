<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * A file removed after the page list was cached must not break the screens.
 *
 * @group headless
 * @covers \Civi\Handbook\Library
 * @covers \Civi\Handbook\Search
 * @covers \CRM_Handbook_Page_Handbook
 */
final class StaleCacheHeadlessTest extends HandbookTestCase {

  private string $dir;

  protected function setUp(): void {
    parent::setUp();
    $this->dir = sys_get_temp_dir() . '/handbook-stale-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
    file_put_contents($this->dir . '/one.md', "# One\n\nStichwort eins\n");
    file_put_contents($this->dir . '/two.md', "# Two\n\nStichwort zwei\n");
    $this->assertCount(2, $this->extraIds($this->useExtraDir($this->dir)));
    unlink($this->dir . '/two.md');
    unset(\Civi::$statics[Library::class]);
  }

  public function testVanishedPageShowsNotFoundAndLeavesTheList(): void {
    $this->assertContains('extra/two', $this->extraIds(Library::fromSystem()), 'still cached');
    $_GET['p'] = 'extra/two';
    ob_start();
    try {
      (new \CRM_Handbook_Page_Handbook())->run();
    }
    finally {
      $html = (string) ob_get_clean();
      unset($_GET['p']);
    }
    $this->assertStringContainsString('Diese Handbuchseite gibt es nicht.', $html);
    unset(\Civi::$statics[Library::class]);
    $this->assertSame(['extra/one'], $this->extraIds(Library::fromSystem()));
  }

  public function testSearchSkipsVanishedFiles(): void {
    $hits = (new Search(Library::fromSystem(), new Renderer()))->run('Stichwort');
    $this->assertSame(['extra/one'], array_map(static fn (array $h): string => $h['page']->id, $hits));
    unset(\Civi::$statics[Library::class]);
    $this->assertSame(['extra/one'], $this->extraIds(Library::fromSystem()));
  }

  /**
   * @return list<string>
   */
  private function extraIds(Library $library): array {
    $extra = array_filter($library->pages(), static fn (Page $page): bool => $page->source() === Sources::EXTRA);
    return array_values(array_map(static fn (Page $page): string => $page->id, $extra));
  }

}
