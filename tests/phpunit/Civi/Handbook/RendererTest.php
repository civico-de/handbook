<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Handbook\Renderer
 */
final class RendererTest extends TestCase {

  private function renderer(): Renderer {
    return new Renderer(
      static fn (string $path, array $query, ?string $fragment): string => (
        '/' . $path . '?' . http_build_query($query) . ($fragment === NULL ? '' : '#' . $fragment)
      ),
    );
  }

  /**
   * @return array{html: string, headings: list<array{level: int, text: string, slug: string, line: int}>}
   */
  private function alpha(): array {
    $body = (string) file_get_contents(HandbookTestCase::FIXTURES . '/extra/alpha.md');
    return $this->renderer()->render('extra', (string) preg_replace('/\A---.*?---\n/s', '', $body));
  }

  public function testHeadingsCarrySlugIds(): void {
    $result = $this->alpha();
    $this->assertStringContainsString('<h2 id="erster-abschnitt">Erster Abschnitt</h2>', $result['html']);
    $this->assertSame(
      ['alpha-aus-der-überschrift', 'erster-abschnitt', 'zweiter-abschnitt'],
      array_column($result['headings'], 'slug'),
    );
  }

  public function testRelativePageLinkBecomesHandbookUrlWithAnchor(): void {
    $this->assertStringContainsString(
      'href="/civicrm/handbook?p=extra%2Fzeta#zeta-ohne-kopfdaten"',
      $this->alpha()['html'],
    );
  }

  public function testAbsoluteCiviAndAnchorLinksStayUntouched(): void {
    $html = $this->alpha()['html'];
    $this->assertStringContainsString('href="https://example.org/x"', $html);
    $this->assertStringContainsString('href="/civicrm/contact/search"', $html);
    $this->assertStringContainsString('href="#erster-abschnitt"', $html);
    $this->assertStringContainsString('src="https://example.org/a.png"', $html);
  }

  public function testRelativeImageIsServedThroughAssetRoute(): void {
    $this->assertStringContainsString('src="/civicrm/handbook/asset?p=extra%2Fbild.png"', $this->alpha()['html']);
  }

  public function testRawHtmlAndUnsafeLinksAreRemoved(): void {
    $html = $this->alpha()['html'];
    $this->assertStringNotContainsString('<script', $html);
    $this->assertStringNotContainsString('javascript:', $html);
    $this->assertStringContainsString('<a>böse</a>', $html, 'the unsafe link keeps its text but loses its href');
  }

  public function testHeadingsGiveBodyLineNumbers(): void {
    $outline = $this->renderer()->render('extra', "Intro\n\n## A\n\ntext\n\n## A\n")['headings'];
    $this->assertSame([3, 7], array_column($outline, 'line'));
    $this->assertSame(['a', 'a-1'], array_column($outline, 'slug'));
  }

  public function testPageLinkWithQueryStringStaysAPageLink(): void {
    $html = $this->renderer()->render('extra', '[x](other.md?x=1#s)')['html'];
    $this->assertStringContainsString('href="/civicrm/handbook?p=extra%2Fother#s"', $html);
  }

  public function testProtocolRelativeImagesLoadFromTheirOrigin(): void {
    $html = $this->renderer()->render('extra', '![x](//example.org/x.png)')['html'];
    $this->assertStringContainsString('src="//example.org/x.png"', $html);
  }

}
