<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Handbook\RenderCache
 */
final class RenderCacheTest extends TestCase {

  private string $dir;

  /**
   * Counts writes, so a test can tell a cache hit from a fresh render.
   */
  private \CRM_Utils_Cache_ArrayCache $cache;

  protected function setUp(): void {
    parent::setUp();
    $this->dir = sys_get_temp_dir() . '/handbook-render-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
    $this->cache = new class([]) extends \CRM_Utils_Cache_ArrayCache {

      public int $writes = 0;

      /**
       * @param string $key
       * @param mixed $value
       * @param int|null $ttl
       */
      public function set($key, $value, $ttl = NULL) {
        $this->writes++;
        return parent::set($key, $value, $ttl);
      }

    };
  }

  protected function tearDown(): void {
    array_map('unlink', glob($this->dir . '/*') ?: []);
    rmdir($this->dir);
    parent::tearDown();
  }

  /**
   * Writes the page file with a fixed mtime.
   */
  private function write(string $markdown, int $mtime = 1_000_000): void {
    file_put_contents($this->dir . '/seite.md', $markdown);
    touch($this->dir . '/seite.md', $mtime);
  }

  /**
   * @return array{html: string, headings: list<array{level: int, text: string, slug: string, line: int}>}
   */
  private function render(string $base = '/civicrm/handbook', string $version = '1.0'): array {
    $library = new Library(['s' => ['dir' => $this->dir, 'label' => 'S']]);
    $url = static fn (string $path, array $query, ?string $fragment): string => $base . '?' . http_build_query($query);
    return (new RenderCache($this->cache, $url, $version))->page($library->find('s/seite'), $library);
  }

  public function testUnchangedContentIsServedFromTheCacheWhateverTheMtime(): void {
    $this->write("## Alt\n");
    $this->render();
    $this->write("## Alt\n", 2_000_000);
    $this->assertSame('alt', $this->render()['headings'][0]['slug']);
    $this->assertSame(1, $this->cache->writes);
  }

  public function testEditWithinTheSameSecondAndSizeRendersAgain(): void {
    $this->write("## Alt\n");
    $this->render();
    $this->write("## Neu\n");
    $this->assertSame('neu', $this->render()['headings'][0]['slug']);
  }

  public function testExtensionVersionIsPartOfTheKey(): void {
    $this->write("## Alt\n");
    $this->render(version: '1.0');
    $this->render(version: '1.1');
    $this->render(version: '1.0');
    $this->assertSame(2, $this->cache->writes);
  }

  public function testBaseUrlIsPartOfTheKey(): void {
    $this->write("[b](other.md)\n");
    $this->assertStringContainsString('href="/a?p=s%2Fother"', $this->render('/a')['html']);
    $this->assertStringContainsString('href="/b?p=s%2Fother"', $this->render('/b')['html']);
  }

  public function testVanishedFileIsNotFound(): void {
    $this->write("# Da\n");
    $library = new Library(['s' => ['dir' => $this->dir, 'label' => 'S']]);
    $page = $library->find('s/seite');
    unlink($this->dir . '/seite.md');
    $this->expectExceptionCode(404);
    (new RenderCache($this->cache))->page($page, $library);
  }

}
