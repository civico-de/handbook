<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Handbook\WhatsNew
 */
final class WhatsNewTest extends TestCase {

  /**
   * @return array{version: string, date: string|null, body: string}|null
   */
  private static function latest(string $markdown): ?array {
    $headings = (new Renderer(static fn (): string => ''))->render('/tmp', $markdown)['headings'];
    return WhatsNew::latest($markdown, $headings);
  }

  public function testNewestReleasedSectionSkippingUnreleased(): void {
    $latest = self::latest(
      "# Changelog\n\n## [Unreleased]\n\n### Fixed\n\n- Pending.\n\n## [1.2.0] - 2026-11-19\n\n### Added\n\n- New.\n\n"
      . "## [1.1.0] - 2026-10-01\n\n### Fixed\n\n- Old.\n\n[1.2.0]: https://example.org/compare\n",
    );
    $this->assertSame('1.2.0', $latest['version'] ?? NULL);
    $this->assertSame('2026-11-19', $latest['date'] ?? NULL);
    $this->assertSame("### Added\n\n- New.\n\n[1.2.0]: https://example.org/compare", $latest['body'] ?? NULL);
  }

  public function testLastSectionRunsToTheEnd(): void {
    $latest = self::latest("# Changelog\n\n## 0.1.0\n\n- First.\n");
    $this->assertSame(['version' => '0.1.0', 'date' => NULL, 'body' => "- First.\n\n"], $latest);
  }

  public function testOnlyUnreleasedMeansNothingNew(): void {
    $this->assertNull(self::latest("# Changelog\n\n## [Unreleased]\n\n- Pending.\n"));
    $this->assertNull(self::latest("# Changelog\n\nNothing yet.\n"));
  }

}
