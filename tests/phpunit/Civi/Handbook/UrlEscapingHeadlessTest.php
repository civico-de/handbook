<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Non-clean URLs carry a query string; templates must escape `&` once.
 *
 * @group headless
 * @covers \Civi\Handbook\View
 * @covers \Civi\Handbook\ContextHelp
 */
final class UrlEscapingHeadlessTest extends HandbookTestCase {

  private function view(): View {
    return new View(
      Library::fromSystem(),
      static fn (string $path, array $query, ?string $fragment): string => (
        'index.php?q=' . $path . '&' . http_build_query($query) . ($fragment === NULL ? '' : '#' . $fragment)
      ),
    );
  }

  public function testUrlsAreBuiltRaw(): void {
    $url = $this->view()->toc()[0]['sections'][0]['pages'][0]['url'];
    $this->assertSame('index.php?q=civicrm/handbook&p=handbook%2Fbenutzen', $url);
  }

  public function testTocEscapesOnce(): void {
    $html = \CRM_Core_Smarty::singleton()->fetchWith('CRM/Handbook/toc.tpl', ['handbookToc' => $this->view()->toc()]);
    $this->assertStringContainsString('href="index.php?q=civicrm/handbook&amp;p=handbook%2Fbenutzen"', $html);
    $this->assertStringNotContainsString('&amp;amp;', $html);
  }

  public function testSearchResultsEscapeOnce(): void {
    $view = $this->view();
    $hits = (new Search(Library::fromSystem(), new RenderCache(NULL)))->run('fixturewort');
    $html = \CRM_Core_Smarty::singleton()->fetchWith('CRM/Handbook/Page/Search.tpl', [
      'handbookSearch' => View::searchForm(),
      'handbookTerm' => 'fixturewort',
      'handbookToc' => $view->toc(),
      'handbookResults' => $view->results($hits),
      'handbookSectionCount' => count($hits),
    ]);
    $this->assertStringContainsString(
      'href="index.php?q=civicrm/handbook&amp;p=extra%2Falpha#erster-abschnitt"',
      $html,
    );
    $this->assertStringNotContainsString('&amp;amp;', $html);
  }

  public function testContextMarkupEscapesOnce(): void {
    $markup = (string) ContextHelp::markup(
      Library::fromSystem()->forRoute('civicrm/dashboard'),
      static fn (string $path, array $query): string => 'index.php?q=' . $path . '&' . http_build_query($query),
    );
    $this->assertStringContainsString('href="index.php?q=civicrm/handbook&amp;p=extra%2Falpha"', $markup);
    $this->assertStringNotContainsString('&amp;amp;', $markup);
  }

}
