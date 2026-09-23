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
    return (new Search(Library::fromSystem(), new RenderCache(NULL)))->run($term);
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

  /**
   * @return array<string, array{string, string}>
   */
  public static function blockLines(): array {
    return [
      'bullet' => ['- Punkt mit **Wort**', 'Punkt mit Wort'],
      'star bullet' => ['* Punkt', 'Punkt'],
      'numbered' => ['3. Schritt drei', 'Schritt drei'],
      'numbered with paren' => ['12) Schritt', 'Schritt'],
      'indented nested' => ['    - tiefer', 'tiefer'],
      'quote' => ['> Hinweis: `cv flush`', 'Hinweis: cv flush'],
      'nested quote with list' => ['> > - zitiert', 'zitiert'],
      'table row' => ['| CRM | Workshops *und* Kurse | [Link](x.md) |', 'CRM · Workshops und Kurse · Link'],
      'table row with empty and escaped cells' => ['| a \\| b | | c |', 'a | b · c'],
      'table separator' => ['|---|:---:|--:|', ''],
      'table separator with spaces' => ['| --- | --- |', ''],
      'rule' => ['* * *', ''],
      'emphasis at line start stays text' => ['*kursiv* am Anfang', 'kursiv am Anfang'],
      'year is no list marker without space' => ['2024.Jahr', '2024.Jahr'],
    ];
  }

  /**
   * @dataProvider blockLines
   */
  public function testBlockMarkdownIsStripped(string $line, string $expected): void {
    $this->assertSame($expected, PlainText::fromLine($line));
  }

  public function testSeparatorRowIsNeverAHit(): void {
    $hits = $this->searchIn("| Spalte | Wert |\n|---|---|\n| a - b | c |\n", '-');
    $this->assertSame(['a - b · c'], array_column($hits, 'context'));
  }

  public function testHitOnAHeadingShowsTheHeadingText(): void {
    $hits = $this->searchIn("# Das Handbuch benutzen\n\nText.\n\n## Mehr zum Handbuch ##\n", 'Handbuch');
    $this->assertSame(['Das Handbuch benutzen', 'Mehr zum Handbuch'], array_column($hits, 'context'));
  }

  public function testOneHitPerSectionWithItsFirstMatchingLine(): void {
    $hits = $this->searchIn(
      "Wort vorn\n\nWort zwei\n\n## A\n\nerstes Wort\n\nzweites Wort\n\n## B\n\nWort in B\n",
      'Wort',
    );
    $this->assertSame([NULL, 'a', 'b'], array_column($hits, 'anchor'));
    $this->assertSame(['Wort vorn', 'erstes Wort', 'Wort in B'], array_column($hits, 'context'));
  }

  public function testSectionWithAnEmptySlugKeepsItsOwnHit(): void {
    // "## 🎉" gets the slug '', the same key text above any heading had.
    $hits = $this->searchIn("Wort vorn\n\n## 🎉\n\nWort eins\n", 'Wort');
    $this->assertSame(['Wort vorn', 'Wort eins'], array_column($hits, 'context'));
  }

  public function testResultsAreGroupedByPageWithTheirFirstSections(): void {
    $markdown = "---\nsection: Geld\n---\n# Start\n\n" . str_repeat("## Teil\n\nWort hier\n\nnoch ein Wort\n\n", 7);
    $view = new View(
      new Library(['s' => ['dir' => '/nonexistent', 'label' => 'Quelle']]),
      static fn (string $path, array $query, ?string $fragment): string => $query['p'] . '#' . $fragment,
    );
    $results = $view->results([
      ...$this->searchIn($markdown, 'Wort'),
      ...$this->searchIn("# Zwei\n\nWort\n", 'Wort', 'zwei'),
    ]);
    $this->assertCount(2, $results);
    $this->assertSame('Start', $results[0]['title']);
    $this->assertSame('Quelle › Geld', $results[0]['place']);
    $this->assertCount(View::HITS_PER_PAGE, $results[0]['hits']);
    $this->assertSame(2, $results[0]['more']);
    $this->assertSame(['s/seite#teil', 's/seite#teil-1'], array_slice(array_column($results[0]['hits'], 'url'), 0, 2));
    $this->assertSame('Quelle', $results[1]['place']);
    $this->assertSame(0, $results[1]['more']);
  }

  /**
   * @return list<array{page: \Civi\Handbook\Page, heading: string|null, anchor: string|null, context: string}>
   */
  private function searchIn(string $markdown, string $term, string $slug = 'seite'): array {
    $dir = sys_get_temp_dir() . '/handbook-search-' . bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir . '/' . $slug . '.md', $markdown);
    $library = new Library(['s' => ['dir' => $dir, 'label' => 'S']]);
    try {
      return (new Search($library, new RenderCache(NULL)))->run($term);
    }
    finally {
      unlink($dir . '/' . $slug . '.md');
      rmdir($dir);
    }
  }

}
