<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Case-insensitive substring search over the Markdown of all pages. One hit per
 * section: the first matching line below a heading (from the render cache), linked to it.
 */
final class Search {

  private const CONTEXT = 200;

  public function __construct(
    private readonly Library $library,
    private readonly RenderCache $rendered,
  ) {}

  /**
   * @return list<array{page: \Civi\Handbook\Page, heading: string|null, anchor: string|null, context: string}>
   */
  public function run(string $term): array {
    $term = trim($term);
    if ($term === '') {
      return [];
    }
    $hits = [];
    foreach ($this->library->pages() as $page) {
      $seen = [];
      try {
        $body = $this->library->body($page);
        $headings = $this->rendered->page($page, $this->library)['headings'];
      }
      catch (HandbookException $e) {
        // Removed since the page list was cached.
        continue;
      }
      foreach (preg_split('/\R/', $body) ?: [] as $index => $line) {
        if (mb_stripos($line, $term) === FALSE) {
          continue;
        }
        $section = self::sectionAt($headings, $index + 1);
        $key = $section['slug'] ?? '';
        if (isset($seen[$key])) {
          continue;
        }
        $seen[$key] = TRUE;
        $hits[] = [
          'page' => $page,
          'heading' => $section['text'] ?? NULL,
          'anchor' => $section['slug'] ?? NULL,
          'context' => mb_strimwidth(PlainText::fromLine($line), 0, self::CONTEXT, '…'),
        ];
      }
    }
    return $hits;
  }

  /**
   * The last heading at or above a body line, if any.
   *
   * @param list<array{level: int, text: string, slug: string, line: int}> $headings
   *
   * @return array{level: int, text: string, slug: string, line: int}|null
   */
  private static function sectionAt(array $headings, int $line): ?array {
    $section = NULL;
    foreach ($headings as $heading) {
      if ($heading['line'] > $line) {
        break;
      }
      $section = $heading;
    }
    return $section;
  }

}
