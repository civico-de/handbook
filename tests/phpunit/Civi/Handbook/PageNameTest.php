<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use PHPUnit\Framework\TestCase;

/**
 * The filename rule: `<slug>.md`, or `<slug>.<locale>.md` for a language variant.
 *
 * @covers \Civi\Handbook\PageName
 * @covers \Civi\Handbook\Renderer
 */
final class PageNameTest extends TestCase {

  /**
   * @return array<string, array{string, array{0: string, 1: string|null}|null}>
   */
  public static function names(): array {
    return [
      'default page' => ['events.md', ['events', NULL]],
      'language variant' => ['events.de.md', ['events', 'de']],
      'full locale variant' => ['events.de_DE.md', ['events', 'de_DE']],
      'landing page variant' => ['index.fr_CA.md', ['index', 'fr_CA']],
      'dotted name' => ['foo.bar.md', ['foo.bar', NULL]],
      'version number' => ['notes.v1.2.md', ['notes.v1.2', NULL]],
      'dotted name with variant' => ['notes.v1.2.de.md', ['notes.v1.2', 'de']],
      'upper-case language is part of the name' => ['events.DE.md', ['events.DE', NULL]],
      'lower-case region is part of the name' => ['events.de_de.md', ['events.de_de', NULL]],
      'hyphenated tag is part of the name' => ['events.de-DE.md', ['events.de-DE', NULL]],
      'three letters are part of the name' => ['events.deu.md', ['events.deu', NULL]],
      'a bare language code is a page' => ['de.md', ['de', NULL]],
      'nothing before the suffix' => ['.de.md', NULL],
      'leading dot' => ['.hidden.md', NULL],
      'space' => ['two words.md', NULL],
      'not markdown' => ['events.txt', NULL],
    ];
  }

  /**
   * @param array{0: string, 1: string|null}|null $expected
   *
   * @dataProvider names
   */
  public function testParsesSlugAndLocale(string $basename, ?array $expected): void {
    $this->assertSame($expected, PageName::parse($basename));
  }

  public function testPreferenceIsFullLocaleThenLanguage(): void {
    $this->assertSame(['de_DE', 'de'], PageName::preference('de_DE'));
    $this->assertSame(['en_US', 'en'], PageName::preference('en_US'));
    $this->assertSame(['de'], PageName::preference('de'));
    $this->assertSame([], PageName::preference(''));
    $this->assertSame([], PageName::preference('sr_Latn'));
  }

  public function testLinkToAVariantNamesThePage(): void {
    $renderer = new Renderer(
      static fn (string $path, array $query, ?string $fragment): string => (
        '/' . $path . '?' . http_build_query($query) . ($fragment === NULL ? '' : '#' . $fragment)
      ),
    );
    $html = $renderer->render('s', '[a](events.de.md#x) [b](notes.v1.2.md) [c](events.de-DE.md)')['html'];
    $this->assertStringContainsString('href="/civicrm/handbook?p=s%2Fevents#x"', $html);
    $this->assertStringContainsString('href="/civicrm/handbook?p=s%2Fnotes.v1.2"', $html);
    $this->assertStringContainsString('href="/civicrm/handbook?p=s%2Fevents.de-DE"', $html);
  }

}
