<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Handbook\Renderer
 * @covers \Civi\Handbook\PagePath
 * @covers \Civi\Handbook\Markdown\Video
 * @covers \Civi\Handbook\VideoEmbedder
 * @covers \Civi\Handbook\Markdown\VideoRenderer
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

  public function testLinksFromASubdirectoryResolveAgainstIt(): void {
    $html = $this->renderer()->render('ext/setup', implode(' ', [
      '[a](rechte.md)',
      '[b](../top.md#x)',
      '[c](./tief/mehr.de.md)',
      '[d](../../raus.md)',
      '![e](../images/bild.png)',
    ]))['html'];
    $this->assertStringContainsString('href="/civicrm/handbook?p=ext%2Fsetup%2Frechte"', $html);
    $this->assertStringContainsString('href="/civicrm/handbook?p=ext%2Ftop#x"', $html);
    $this->assertStringContainsString('href="/civicrm/handbook?p=ext%2Fsetup%2Ftief%2Fmehr"', $html);
    // Above the source the path stays, so the page is not found.
    $this->assertStringContainsString('href="/civicrm/handbook?p=ext%2F..%2Fraus"', $html);
    $this->assertStringContainsString('src="/civicrm/handbook/asset?p=ext%2Fimages%2Fbild.png"', $html);
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

  public function testALinkedPosterOfAnHttpsVideoPlaysInPlace(): void {
    $html = $this->renderer()->render(
      'extra',
      "[![Tour & more](video/tour.png)](https://media.example.org/x/tour.mp4)\n",
    )['html'];
    $this->assertSame(
      '<p><video controls preload="none" playsinline poster="/civicrm/handbook/asset?p=extra%2Fvideo%2Ftour.png"'
      . ' src="https://media.example.org/x/tour.mp4" aria-label="Tour &amp; more">'
      . '<a href="https://media.example.org/x/tour.mp4">Tour &amp; more</a></video></p>',
      trim($html),
    );
  }

  public function testAVideoInAHeadingKeepsItsLabelInTheOutline(): void {
    $headings = $this->renderer()->render(
      'extra',
      "## Watch [![Demo](p.png)](https://media.example.org/demo.mp4)\n",
    )['headings'];
    $this->assertSame('Watch Demo', $headings[0]['text']);
  }

  /**
   * @return array<string, array{string}>
   */
  public static function notVideos(): array {
    return [
      'plain http' => ["[![T](p.png)](http://example.org/t.mp4)\n"],
      'not a video file' => ["[![T](p.png)](https://example.org/t.mp4.html)\n"],
      'text beside the poster' => ["[![T](p.png) watch](https://example.org/t.mp4)\n"],
      'a text link' => ["[watch](https://example.org/t.mp4)\n"],
      'a host ending in .mp4' => ["[![T](p.png)](https://example.mp4)\n"],
      'a poster without text' => ["[![](p.png)](https://example.org/t.mp4)\n"],
    ];
  }

  /**
   * @dataProvider notVideos
   */
  public function testOtherLinksStayLinks(string $markdown): void {
    $html = $this->renderer()->render('extra', $markdown)['html'];
    $this->assertStringNotContainsString('<video', $html);
    $this->assertStringContainsString('<a href=', $html);
  }

  public function testTablesSitInAFocusableScrollBox(): void {
    $html = $this->renderer()->render('extra', "| a | b |\n|---|---|\n| 1 | 2 |\n")['html'];
    $this->assertStringContainsString('<div class="handbook-table" tabindex="0"><table>', $html);
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
