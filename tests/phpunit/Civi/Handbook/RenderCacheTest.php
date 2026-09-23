<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Handbook\RenderCache
 */
final class RenderCacheTest extends TestCase {

  private string $dir;

  private \CRM_Utils_Cache_ArrayCache $cache;

  protected function setUp(): void {
    parent::setUp();
    $this->dir = sys_get_temp_dir() . '/handbook-render-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
    $this->cache = new \CRM_Utils_Cache_ArrayCache([]);
  }

  protected function tearDown(): void {
    array_map('unlink', glob($this->dir . '/*') ?: []);
    rmdir($this->dir);
    parent::tearDown();
  }

  /**
   * Writes the page file with a fixed mtime.
   */
  private function write(string $markdown, int $mtime): void {
    file_put_contents($this->dir . '/seite.md', $markdown);
    touch($this->dir . '/seite.md', $mtime);
  }

  /**
   * @return array{html: string, headings: list<array{level: int, text: string, slug: string, line: int}>}
   */
  private function render(string $base = '/civicrm/handbook'): array {
    $library = new Library(['s' => ['dir' => $this->dir, 'label' => 'S']]);
    $url = static fn (string $path, array $query, ?string $fragment): string => $base . '?' . http_build_query($query);
    return (new RenderCache($this->cache, $url))->page($library->find('s/seite'), $library);
  }

  public function testHitServesTheStoredResultWhileFileStatsMatch(): void {
    $this->write("## Alt\n", 1_000_000);
    $this->assertSame('alt', $this->render()['headings'][0]['slug']);
    // Same length and mtime: indistinguishable to the key, so the cached result shows.
    $this->write("## Neu\n", 1_000_000);
    $this->assertSame('alt', $this->render()['headings'][0]['slug']);
  }

  public function testChangedMtimeRendersAgain(): void {
    $this->write("## Alt\n", 1_000_000);
    $this->render();
    $this->write("## Neu\n", 1_000_001);
    $this->assertSame('neu', $this->render()['headings'][0]['slug']);
  }

  public function testChangedSizeRendersAgain(): void {
    $this->write("## Alt\n", 1_000_000);
    $this->render();
    $this->write("## Neuer\n", 1_000_000);
    $this->assertSame('neuer', $this->render()['headings'][0]['slug']);
  }

  public function testBaseUrlIsPartOfTheKey(): void {
    $this->write("[b](other.md)\n", 1_000_000);
    $this->assertStringContainsString('href="/a?p=s%2Fother"', $this->render('/a')['html']);
    $this->assertStringContainsString('href="/b?p=s%2Fother"', $this->render('/b')['html']);
  }

  public function testVanishedFileIsNotFound(): void {
    $this->write("# Da\n", 1_000_000);
    $library = new Library(['s' => ['dir' => $this->dir, 'label' => 'S']]);
    $page = $library->find('s/seite');
    unlink($this->dir . '/seite.md');
    $this->expectExceptionCode(404);
    (new RenderCache($this->cache))->page($page, $library);
  }

}
