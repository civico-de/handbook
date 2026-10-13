<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * @group headless
 * @covers \Civi\Handbook\Sources
 * @covers \Civi\Handbook\ExtraDir
 */
final class SourcesHeadlessTest extends HandbookTestCase {

  public function testExtensionKeyedLikeTheExtraSourceIsAnErrorNotOverwritten(): void {
    $ownDir = (string) realpath(self::FIXTURES . '/extra');
    $sources = Sources::build(
      [Sources::EXTRA => ['dir' => $ownDir, 'label' => 'Extension']],
      (string) realpath(self::FIXTURES),
    );
    $this->assertSame(['dir' => $ownDir, 'label' => 'Extension'], $sources->dirs[Sources::EXTRA]);
    $this->assertCount(1, $sources->errors);
    $this->assertStringContainsString(Sources::EXTRA, $sources->errors[0]);
  }

  public function testRelativeExtraDirIsRejectedOnSave(): void {
    try {
      \Civi\Api4\Setting::set(FALSE)->addValue('handbook_extra_dir', 'tests/fixtures/extra')->execute();
      $this->fail('A relative path must be rejected');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertStringContainsString('absolute path', $e->getMessage());
    }
    $dir = (string) realpath(self::FIXTURES . '/extra');
    \Civi\Api4\Setting::set(FALSE)->addValue('handbook_extra_dir', $dir)->execute();
    \Civi\Api4\Setting::set(FALSE)->addValue('handbook_extra_dir', '')->execute();
    $this->assertSame('', \Civi::settings()->get('handbook_extra_dir'));
  }

  public function testSettingsFormShowsTheMessage(): void {
    $meta = \Civi\Core\SettingsMetadata::getMetadata(['name' => ['handbook_extra_dir']]);
    $errors = \CRM_Admin_Form_Generic::genericSettingsFormRule(['handbook_extra_dir' => 'relative/dir'], [], $meta);
    $this->assertIsArray($errors);
    $this->assertStringContainsString('absolute path', (string) $errors['handbook_extra_dir']);
  }

  public function testRelativeExtraDirSetBehindTheApiIsAnErrorNotResolved(): void {
    $sources = Sources::build([], 'tests/fixtures/extra');
    $this->assertArrayNotHasKey(Sources::EXTRA, $sources->dirs);
    $this->assertStringContainsString('absolute path', $sources->errors[0]);
  }

}
