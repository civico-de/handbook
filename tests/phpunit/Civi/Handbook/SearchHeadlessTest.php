<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * @group headless
 * @covers \Civi\Handbook\Search
 * @covers \Civi\Handbook\PlainText
 */
final class SearchHeadlessTest extends HandbookTestCase {

  /**
   * @return list<array{page: \Civi\Handbook\Page, heading: string|null, anchor: string|null, context: string}>
   */
  private function search(string $term): array {
    return (new Search(Library::fromSystem(), new Renderer()))->run($term);
  }

  public function testCaseInsensitiveHitsNameTheirSection(): void {
    $hits = $this->search('fixturewort');
    $this->assertCount(2, $hits);
    $this->assertSame('extra/alpha', $hits[0]['page']->id);
    $this->assertSame('Erster Abschnitt', $hits[0]['heading']);
    $this->assertSame('erster-abschnitt', $hits[0]['anchor']);
    $this->assertSame('Zeile mit FIXTUREWORT in Großbuchstaben.', $hits[0]['context']);
    $this->assertSame('extra/zeta', $hits[1]['page']->id);
    $this->assertSame('zeta-ohne-kopfdaten', $hits[1]['anchor']);
  }

  public function testHitBeforeAnyHeadingHasNoAnchor(): void {
    $hits = $this->search('keine Überschrift');
    $this->assertCount(1, $hits);
    $this->assertNull($hits[0]['anchor']);
  }

  public function testMultibyteTermMatchesCaseInsensitively(): void {
    $this->assertNotSame([], $this->search('ÜBERSCHRIFT'));
  }

  public function testBlankTermFindsNothing(): void {
    $this->assertSame([], $this->search('  '));
    $this->assertSame([], $this->search('kommt-nirgends-vor'));
  }

  public function testContextIsPlainTextWithoutInlineMarkdown(): void {
    $hits = $this->searchIn(
      "# Start\n\nMenü **Support › Handbuch** mit `cv flush`, [Link-Text](seite.md) und ![Bild-Text](a.png), *kursiv* und __fett__ in snake_case_name.\n",
      'Handbuch',
    );
    $this->assertSame(
      'Menü Support › Handbuch mit cv flush, Link-Text und Bild-Text, kursiv und fett in snake_case_name.',
      $hits[0]['context'],
    );
  }

  public function testHitOnAHeadingShowsTheHeadingText(): void {
    $hits = $this->searchIn("# Das Handbuch benutzen\n\nText.\n\n## Mehr zum Handbuch ##\n", 'Handbuch');
    $this->assertSame(['Das Handbuch benutzen', 'Mehr zum Handbuch'], array_column($hits, 'context'));
  }

  /**
   * @return list<array{page: \Civi\Handbook\Page, heading: string|null, anchor: string|null, context: string}>
   */
  private function searchIn(string $markdown, string $term): array {
    $dir = sys_get_temp_dir() . '/handbook-search-' . bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir . '/seite.md', $markdown);
    $library = new Library(['s' => ['dir' => $dir, 'label' => 'S']]);
    try {
      return (new Search($library, new Renderer()))->run($term);
    }
    finally {
      unlink($dir . '/seite.md');
      rmdir($dir);
    }
  }

}
