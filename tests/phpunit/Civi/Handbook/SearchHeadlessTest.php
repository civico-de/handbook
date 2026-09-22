<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * @group headless
 * @covers \Civi\Handbook\Search
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

}
