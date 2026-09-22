<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * @group headless
 * @covers \Civi\Handbook\Sources
 */
final class SourcesHeadlessTest extends HandbookTestCase {

  public function testExtensionKeyedLikeTheExtraSourceIsAnErrorNotOverwritten(): void {
    $ownDir = (string) realpath(self::FIXTURES . '/extra');
    $sources = Sources::build([Sources::EXTRA => ['dir' => $ownDir, 'label' => 'Extension']], (string) realpath(self::FIXTURES));
    $this->assertSame(['dir' => $ownDir, 'label' => 'Extension'], $sources->dirs[Sources::EXTRA]);
    $this->assertCount(1, $sources->errors);
    $this->assertStringContainsString(Sources::EXTRA, $sources->errors[0]);
  }

}
