<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * @group headless
 * @covers \Civi\Handbook\Frontmatter
 * @covers \Civi\Handbook\PageLoader
 * @covers \Civi\Handbook\PageMeta
 */
final class FrontmatterHeadlessTest extends HandbookTestCase {

  private function file(string $content): string {
    $file = sys_get_temp_dir() . '/handbook-fm-' . bin2hex(random_bytes(4)) . '.md';
    file_put_contents($file, $content);
    return $file;
  }

  public function testEmptyBlockIsFrontmatter(): void {
    $page = PageLoader::load('s', $this->file("---\n---\n# Titel\n"));
    $this->assertSame('Titel', $page->title);
    $this->assertSame([NULL, "# Titel\n"], Frontmatter::split("---\n---\n# Titel\n"));
  }

  public function testByteOrderMarkBeforeTheBlock(): void {
    $page = PageLoader::load('s', $this->file("\u{FEFF}---\ntitle: Mit BOM\norder: 3\n---\nText\n"));
    $this->assertSame('Mit BOM', $page->title);
    $this->assertSame(3, $page->order);
  }

  public function testByteOrderMarkWithoutBlockIsNotPartOfTheBody(): void {
    $this->assertSame([NULL, "# Titel\n"], Frontmatter::split("\u{FEFF}# Titel\n"));
  }

  public function testScalarYamlIsAPageError(): void {
    $this->expectException(HandbookException::class);
    $this->expectExceptionMessage('not a map');
    PageLoader::load('s', $this->file("---\nnur ein Satz\n---\nText\n"));
  }

  public function testSequenceYamlIsAPageError(): void {
    $this->expectException(HandbookException::class);
    $this->expectExceptionMessage('not a map');
    PageLoader::load('s', $this->file("---\n- title\n- order\n---\nText\n"));
  }

  public function testNumericKeysAreAPageError(): void {
    $this->expectException(HandbookException::class);
    $this->expectExceptionMessage('not a map');
    PageLoader::load('s', $this->file("---\n1: eins\n---\nText\n"));
  }

  public function testSectionIsReadAsText(): void {
    $page = PageLoader::load('s', $this->file("---\nsection: Geld\n---\n# Rechnung\n"));
    $this->assertSame('Geld', $page->section);
    $this->assertNull(PageLoader::load('s', $this->file("# Ohne\n"))->section);
    $this->assertSame('Geld', PageLoader::load('s', $this->file("---\nsection: '  Geld '\n---\n"))->section);
    $this->assertNull(PageLoader::load('s', $this->file("---\nsection: ''\n---\n"))->section);
    $this->assertNull(PageLoader::load('s', $this->file("---\nsection: '   '\n---\n"))->section);
  }

  /**
   * @return array<string, array{string}>
   */
  public static function badSections(): array {
    return [
      'number' => ["section: 40\n"],
      'list' => ["section: [Geld, Buchungen]\n"],
      'map' => ["section:\n  name: Geld\n"],
    ];
  }

  /**
   * @dataProvider badSections
   */
  public function testSectionOfAnotherTypeIsAPageError(string $yaml): void {
    $this->expectException(HandbookException::class);
    $this->expectExceptionMessage('section must be text');
    PageLoader::load('s', $this->file("---\n" . $yaml . "---\nText\n"));
  }

}
