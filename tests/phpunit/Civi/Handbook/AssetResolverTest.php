<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Handbook\AssetResolver
 * @covers \Civi\Handbook\HandbookException
 */
final class AssetResolverTest extends TestCase {

  private function resolver(): AssetResolver {
    $dir = (string) realpath(HandbookTestCase::FIXTURES . '/extra');
    return new AssetResolver(new Library(['extra' => ['dir' => $dir, 'label' => 'Extra']]));
  }

  public function testServesImageInsideSource(): void {
    $asset = $this->resolver()->resolve('extra/bild.png');
    $this->assertSame('image/png', $asset['type']);
    $this->assertStringEndsWith('/fixtures/extra/bild.png', $asset['file']);
  }

  /**
   * @return array<string, array{string, int}>
   */
  public static function rejected(): array {
    return [
      'traversal to existing image' => ['extra/../outside.png', 400],
      'deep traversal' => ['extra/../../../../../../etc/passwd.png', 404],
      'not an image' => ['extra/alpha.md', 400],
      'no extension' => ['extra/../../../../../../etc/passwd', 400],
      'svg is not served' => ['extra/x.svg', 400],
      'unknown source' => ['nope/bild.png', 404],
      'missing file' => ['extra/missing.png', 404],
      'no source separator' => ['bild.png', 400],
      'nul byte' => ["extra/bild.png\0.png", 400],
    ];
  }

  /**
   * @dataProvider rejected
   */
  public function testRejects(string $p, int $status): void {
    $this->expectException(HandbookException::class);
    $this->expectExceptionCode($status);
    $this->resolver()->resolve($p);
  }

  public function testSymlinkOutOfTheSourceIsRejected(): void {
    $dir = sys_get_temp_dir() . '/handbook-link-' . bin2hex(random_bytes(4));
    mkdir($dir);
    symlink((string) realpath(HandbookTestCase::FIXTURES . '/outside.png'), $dir . '/link.png');
    $resolver = new AssetResolver(new Library(['s' => ['dir' => (string) realpath($dir), 'label' => 'S']]));
    $this->expectExceptionCode(400);
    $resolver->resolve('s/link.png');
  }

}
