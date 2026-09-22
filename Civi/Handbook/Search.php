<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Case-insensitive substring search over the Markdown of all pages. Each hit
 * names the last heading above it, so results link to that section.
 */
final class Search {

  private const CONTEXT = 200;

  public function __construct(
    private readonly Library $library,
    private readonly Renderer $renderer,
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
      $body = $this->library->body($page);
      $headings = $this->renderer->outline($body);
      foreach (preg_split('/\R/', $body) ?: [] as $index => $line) {
        if (mb_stripos($line, $term) === FALSE) {
          continue;
        }
        $section = NULL;
        foreach ($headings as $heading) {
          if ($heading['line'] > ($index + 1)) {
            break;
          }
          $section = $heading;
        }
        $hits[] = [
          'page' => $page,
          'heading' => $section['text'] ?? NULL,
          'anchor' => $section['slug'] ?? NULL,
          'context' => mb_strimwidth(trim($line), 0, self::CONTEXT, '…'),
        ];
      }
    }
    return $hits;
  }

}
