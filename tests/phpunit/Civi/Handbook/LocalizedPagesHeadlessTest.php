<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Language variants `<slug>.<locale>.md`: which file a reader sees, what it
 * inherits from the default page, and how lists, caches and search follow.
 *
 * @group headless
 * @covers \Civi\Handbook\Library
 * @covers \Civi\Handbook\Variants
 * @covers \Civi\Handbook\PageName
 * @covers \Civi\Handbook\PageLoader
 * @covers \Civi\Handbook\PageMeta
 * @covers \Civi\Handbook\Search
 * @covers \Civi\Handbook\RenderCache
 */
final class LocalizedPagesHeadlessTest extends HandbookTestCase {

  private string $dir;

  protected function setUp(): void {
    parent::setUp();
    $this->dir = sys_get_temp_dir() . '/handbook-l10n-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
  }

  protected function tearDown(): void {
    array_map('unlink', glob($this->dir . '/*.md') ?: []);
    rmdir($this->dir);
    parent::tearDown();
  }

  /**
   * @param array<string, string> $files
   */
  private function write(array $files): void {
    foreach ($files as $name => $content) {
      file_put_contents($this->dir . '/' . $name, $content);
    }
  }

  private function library(string $locale, ?\CRM_Utils_Cache_Interface $cache = NULL): Library {
    return new Library(['p' => ['dir' => $this->dir, 'label' => 'P']], [], $cache, $locale);
  }

  /**
   * @return array<string, string>
   *   Title per page id.
   */
  private function titles(Library $library): array {
    $titles = [];
    foreach ($library->pages() as $page) {
      $titles[$page->id] = $page->title;
    }
    ksort($titles);
    return $titles;
  }

  public function testReaderSeesFullLocaleThenLanguageThenDefault(): void {
    $this->write([
      'x.md' => "# X default\n",
      'x.de.md' => "# X de\n",
      'x.de_DE.md' => "# X de_DE\n",
      'y.md' => "# Y default\n",
      'y.de.md' => "# Y de\n",
      'z.md' => "# Z default\n",
    ]);
    $this->assertSame(
      ['p/x' => 'X de_DE', 'p/y' => 'Y de', 'p/z' => 'Z default'],
      $this->titles($this->library('de_DE')),
    );
    $this->assertSame(['p/x' => 'X de', 'p/y' => 'Y de', 'p/z' => 'Z default'], $this->titles($this->library('de_AT')));
    $this->assertSame(
      ['p/x' => 'X default', 'p/y' => 'Y default', 'p/z' => 'Z default'],
      $this->titles($this->library('en_US')),
    );
    $this->assertSame(
      ['p/x' => 'X default', 'p/y' => 'Y default', 'p/z' => 'Z default'],
      $this->titles($this->library('fr_FR')),
    );
    $this->assertSame($this->dir . '/x.de_DE.md', $this->library('de_DE')->find('p/x')->file);
  }

  public function testDottedNamesThatAreNoLocaleArePagesOfTheirOwn(): void {
    $this->write([
      'notes.v1.2.md' => "# Notes 1.2\n",
      'notes.v1.2.de.md' => "# Notizen 1.2\n",
      'events.DE.md' => "# Upper case\n",
    ]);
    $this->assertSame(
      ['p/events.DE' => 'Upper case', 'p/notes.v1.2' => 'Notizen 1.2'],
      $this->titles($this->library('de_DE')),
    );
    $this->assertSame([], $this->library('de_DE')->errors());
  }

  public function testFileNameThatCannotBeAPageIsAnError(): void {
    $this->write(['two words.md' => "# Two\n", '_draft.de.md' => "# Draft\n", 'ok.md' => "# Ok\n"]);
    $library = $this->library('de_DE');
    $this->assertSame(['p/ok' => 'Ok'], $this->titles($library));
    $errors = $library->errors();
    sort($errors);
    $this->assertSame(['p/_draft.de.md: invalid file name.', 'p/two words.md: invalid file name.'], $errors);

    $this->expectException(HandbookException::class);
    $this->expectExceptionMessage('p/two words.md: invalid file name.');
    PageLoader::load('p', $this->dir . '/two words.md');
  }

  public function testVariantFrontmatterOverridesTheDefaultKeyByKey(): void {
    $this->write([
      'events.md' => "---\ntitle: Events\norder: 3\nsection: Setup\nroutes:\n  - civicrm/event/manage\n---\n# Managing events\n",
      'events.de.md' => "---\nsection: Einrichtung\n---\n# Veranstaltungen verwalten\n",
      'events.fr.md' => "Pas de titre.\n",
      'events.nl.md' => "---\ntitle: Evenementen\norder: ~\nroutes: []\n---\n",
    ]);
    $de = $this->library('de_DE');
    $page = $de->find('p/events');
    $this->assertSame('Veranstaltungen verwalten', $page->title, 'the variant heading beats the default title');
    $this->assertSame(3, $page->order);
    $this->assertSame('Einrichtung', $page->section);
    $this->assertSame(['civicrm/event/manage'], $page->routes);
    $this->assertSame(
      ['p/events'],
      array_map(static fn (Page $p): string => $p->id, $de->forRoute('civicrm/event/manage')),
    );

    $this->assertSame(
      'Events',
      $this->library('fr_FR')->find('p/events')->title,
      'no title of its own: the default title',
    );
    $this->assertSame('Setup', $this->library('fr_FR')->find('p/events')->section);

    $nl = $this->library('nl_NL');
    $this->assertSame('Evenementen', $nl->find('p/events')->title);
    $this->assertNull($nl->find('p/events')->order);
    $this->assertSame([], $nl->forRoute('civicrm/event/manage'));
  }

  public function testTranslationWithoutDefaultPageIsAnError(): void {
    $this->write(['only.de.md' => "# Nur Deutsch\n", 'other.md' => "# Other\n"]);
    foreach (['de_DE', 'en_US'] as $locale) {
      $library = $this->library($locale);
      $this->assertSame(['p/other'], array_keys($this->titles($library)), $locale);
      $this->assertSame(['p/only.de.md: translation without the default page only.md.'], $library->errors(), $locale);
    }
  }

  public function testBrokenVariantIsReportedInEveryLocaleAndLeftOut(): void {
    $this->write([
      'x.md' => "# X\n",
      'x.de_DE.md' => "---\norder: [kaputt\n---\n",
      'x.de.md' => "# X de\n",
    ]);
    foreach (['en_US', 'de_DE'] as $locale) {
      $errors = $this->library($locale)->errors();
      $this->assertCount(1, $errors, $locale);
      $this->assertStringContainsString('p/x.de_DE.md', $errors[0]);
    }
    $this->assertSame('X de', $this->library('de_DE')->find('p/x')->title, 'the next match in line');
  }

  public function testVariantOfABrokenDefaultIsLeftOut(): void {
    $this->write(['x.md' => "---\norder: erst\n---\n", 'x.de.md' => "# X de\n"]);
    $library = $this->library('de_DE');
    $this->assertSame([], $library->pages());
    $this->assertCount(1, $library->errors());
    $this->assertStringContainsString('p/x.md', $library->errors()[0]);
  }

  public function testPageListIsCachedPerLocale(): void {
    $this->write(['x.md' => "# X\n", 'x.de.md' => "# X de\n"]);
    $cache = new \CRM_Utils_Cache_ArrayCache([]);
    $this->assertSame(['p/x' => 'X'], $this->titles($this->library('en_US', $cache)));
    $this->assertSame(['p/x' => 'X de'], $this->titles($this->library('de_DE', $cache)));
    $this->assertSame(['p/x' => 'X'], $this->titles($this->library('en_US', $cache)));
    $sources = ['p' => ['dir' => $this->dir, 'label' => 'P']];
    $this->assertNotSame(Library::pagesKey($sources, 'en_US', ['id']), Library::pagesKey($sources, 'de_DE', ['id']));
  }

  public function testSearchReadsTheVariantsTheReaderSees(): void {
    $this->write(['x.md' => "# Fruit\n\nApples here.\n", 'x.de.md' => "# Obst\n\nÄpfel hier.\n"]);
    $ids = fn (string $locale, string $term): array => array_map(
      static fn (array $hit): string => $hit['page']->id,
      (new Search($this->library($locale), new RenderCache(NULL)))->run($term),
    );
    $this->assertSame(['p/x'], $ids('de_DE', 'äpfel'));
    $this->assertSame([], $ids('de_DE', 'apples'));
    $this->assertSame(['p/x'], $ids('en_US', 'apples'));
    $this->assertSame([], $ids('en_US', 'äpfel'));
  }

  public function testCurrentLocalePicksTheVariantOfTheOwnManual(): void {
    $this->useLocale('de_DE');
    $page = Library::fromSystem()->find('handbook/using');
    $this->assertSame('Das Handbuch benutzen', $page->title);
    $this->assertSame('Lesen', $page->section);
    $this->assertSame(1, $page->order, 'inherited from using.md');
    $germanIds = array_map(static fn (Page $p): string => $p->id, Library::fromSystem()->pages());

    $this->useLocale('en_US');
    $page = Library::fromSystem()->find('handbook/using');
    $this->assertSame('Using the handbook', $page->title);
    $this->assertSame('Reading', $page->section);
    $this->assertSame($germanIds, array_map(static fn (Page $p): string => $p->id, Library::fromSystem()->pages()));
  }

  public function testVariantsShareTheImagesOfTheSource(): void {
    $this->useLocale('de_DE');
    $library = Library::fromSystem();
    $html = RenderCache::fromSystem()->page($library->find('handbook/using'), $library)['html'];
    $this->assertStringContainsString('p=handbook%2Fimages%2Fsearch.png', $html);
    $this->assertSame('image/png', (new AssetResolver($library))->resolve('handbook/images/search.png')['type']);
  }

}
