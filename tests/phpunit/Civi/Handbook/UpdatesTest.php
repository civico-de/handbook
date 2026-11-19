<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Handbook\Updates
 */
final class UpdatesTest extends TestCase {

  public function testFirstSightingIsABaselineWithoutNotice(): void {
    $tracked = Updates::track(['herald' => '1.0.0'], [], '2026-11-19');
    $this->assertSame(['herald' => ['version' => '1.0.0', 'since' => NULL]], $tracked);
    $this->assertSame([], Updates::recent($tracked, '2026-11-19'));
  }

  public function testUnchangedVersionsStayQuiet(): void {
    $seen = ['a' => ['version' => '1.0.0', 'since' => NULL], 'b' => ['version' => '2.0.0', 'since' => NULL]];
    $tracked = Updates::track(['a' => '1.0.0', 'b' => '2.0.0'], $seen, '2026-11-19');
    $this->assertSame($seen, $tracked);
    $this->assertSame([], Updates::recent($tracked, '2026-11-19'));
  }

  public function testChangedVersionIsDatedAndListedForTwoWeeks(): void {
    $tracked = Updates::track(['a' => '1.1.0'], ['a' => ['version' => '1.0.0', 'since' => NULL]], '2026-11-19');
    $this->assertSame(['a' => ['version' => '1.1.0', 'since' => '2026-11-19']], $tracked);
    $this->assertSame(['a'], Updates::recent($tracked, '2026-11-19'));
    $later = Updates::track(['a' => '1.1.0'], $tracked, '2026-12-02');
    $this->assertSame('2026-11-19', $later['a']['since']);
    $this->assertSame(['a'], Updates::recent($later, '2026-12-02'));
    $this->assertSame([], Updates::recent($later, '2026-12-03'));
  }

  public function testDowngradeCountsAsAChange(): void {
    $tracked = Updates::track(['a' => '1.0.0'], ['a' => ['version' => '1.1.0', 'since' => NULL]], '2026-11-19');
    $this->assertSame(['a'], Updates::recent($tracked, '2026-11-19'));
  }

  public function testUninstalledExtensionsAreForgotten(): void {
    $seen = ['gone' => ['version' => '1.0.0', 'since' => '2026-11-18']];
    $this->assertSame([], Updates::track([], $seen, '2026-11-19'));
  }

  public function testMalformedStoredEntryIsTreatedAsUnseen(): void {
    $tracked = Updates::track(['a' => '1.0.0'], ['a' => 'garbage'], '2026-11-19');
    $this->assertSame(['a' => ['version' => '1.0.0', 'since' => NULL]], $tracked);
  }

}
