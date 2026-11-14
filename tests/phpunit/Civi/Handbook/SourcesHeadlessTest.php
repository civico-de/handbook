<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * @group headless
 * @covers \Civi\Handbook\Sources
 * @covers \Civi\Handbook\ExtraDir
 * @covers \Civi\Handbook\ExtensionDir
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

  public function testDeclaredDirTagsAreReadPerExtension(): void {
    $tags = [
      'mgmt:hidden' => ['org.example.hidden'],
      'handbook:dir=docs' => ['org.example.a', 'org.example.b'],
      'handbook:dir=manual' => ['org.example.b'],
    ];
    $this->assertSame(
      [
        'org.example.a' => ['docs'],
        'org.example.b' => ['docs', 'manual'],
      ],
      ExtensionDir::declared($tags),
    );
  }

  public function testDeclaredDirReplacesHandbookDir(): void {
    $base = (string) realpath(self::FIXTURES . '/declared');
    $this->assertSame($base . '/docs', ExtensionDir::resolve($base, 'org.example.a', ['docs']));
    $this->assertSame($base . '/handbook', ExtensionDir::resolve($base, 'org.example.a', []));
  }

  public function testNoDeclarationAndNoHandbookDirIsNoSource(): void {
    $this->assertNull(ExtensionDir::resolve((string) realpath(self::FIXTURES . '/sources'), 'org.example.a', []));
  }

  /**
   * @return array<string, array{list<string>, string}>
   */
  public static function unusableDeclarations(): array {
    return [
      'missing' => [['nowhere'], 'not a directory inside the extension'],
      'a file' => [['notes.md'], 'not a directory inside the extension'],
      'the root' => [['.'], 'not a directory inside the extension'],
      'outside' => [['../extra'], 'not a directory inside the extension'],
      'absolute' => [['/tmp'], 'not a directory inside the extension'],
      'two' => [['docs', 'handbook'], 'more than one handbook directory'],
    ];
  }

  /**
   * @dataProvider unusableDeclarations
   * @param list<string> $declared
   */
  public function testUnusableDeclarationIsAnError(array $declared, string $message): void {
    try {
      ExtensionDir::resolve((string) realpath(self::FIXTURES . '/declared'), 'org.example.a', $declared);
      $this->fail('An unusable declaration must not resolve');
    }
    catch (\InvalidArgumentException $e) {
      $this->assertStringContainsString($message, $e->getMessage());
      $this->assertStringContainsString('org.example.a', $e->getMessage());
    }
  }

  public function testExtensionErrorsReachTheSources(): void {
    $sources = Sources::build([], '', ['The extension org.example.a declares …']);
    $this->assertSame(['The extension org.example.a declares …'], $sources->errors);
  }

}
