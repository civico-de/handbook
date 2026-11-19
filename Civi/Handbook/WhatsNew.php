<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * The newest released section of every extension changelog, newest release
 * first, for the "What's new" screen.
 */
final class WhatsNew {

  /**
   * @var \Closure(string, array<string, string>, string|null): string
   */
  private \Closure $url;

  /**
   * @param (\Closure(string, array<string, string>, string|null): string)|null $url
   *   Builds an unescaped Civi URL from path, query and fragment.
   */
  public function __construct(
    private readonly Library $library,
    private readonly RenderCache $cache,
    ?\Closure $url = NULL,
  ) {
    $this->url = $url ?? View::defaultUrl(...);
  }

  /**
   * @return list<array{source: string, label: string, version: string, date: string|null, html: string, url: string}>
   */
  public function entries(): array {
    $entries = [];
    foreach ($this->library->pages() as $page) {
      if (!$page->isChangelog()) {
        continue;
      }
      $body = $this->library->body($page);
      $latest = self::latest($body, $this->cache->page($page, $this->library)['headings']);
      if ($latest === NULL) {
        continue;
      }
      $source = $page->source();
      $changes = ($this->url)('civicrm/handbook', ['p' => $page->id], NULL);
      $html = (new Renderer($this->url, $source))->render($page->dir(), $latest['body'])['html'];
      $entries[] = [
        'source' => $source,
        'label' => Ordering::sourceName($this->library->pages(), $this->library->sources(), $source),
        'version' => $latest['version'],
        'date' => $latest['date'],
        // A fragment link points into the full changelog, where its heading lives.
        'html' => str_replace('href="#', 'href="' . htmlspecialchars($changes) . '#', $html),
        'url' => $changes,
      ];
    }
    // Stable: releases of one day keep the table-of-contents order.
    usort($entries, static fn (array $a, array $b): int => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return $entries;
  }

  /**
   * The first level-2 section that is not Unreleased: its version, its date and its
   * Markdown, followed by the changelog's link reference definitions.
   *
   * @param list<array{level: int, text: string, slug: string, line: int}> $headings
   *   The rendered headings of $body (Renderer::render()), whose lines locate the sections.
   *
   * @return array{version: string, date: string|null, body: string}|null
   */
  public static function latest(string $body, array $headings): ?array {
    $sections = array_values(array_filter($headings, static fn (array $h): bool => $h['level'] === 2));
    $lines = explode("\n", $body);
    foreach ($sections as $i => $heading) {
      $text = trim($heading['text']);
      if (preg_match('/^\[?unreleased\]?$/i', $text) === 1) {
        continue;
      }
      // Lines are 1-based: the section runs from below its heading to above the next one.
      $end = isset($sections[$i + 1]) ? $sections[$i + 1]['line'] - 1 : count($lines);
      $bracketed = [];
      $date = [];
      preg_match('/^\[([^\]]+)\]/', $text, $bracketed);
      preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $text, $date);
      return [
        'version' => $bracketed[1] ?? explode(' ', $text)[0],
        'date' => $date[1] ?? NULL,
        'body' =>
          trim(implode("\n", array_slice($lines, $heading['line'], $end - $heading['line'])))
            . "\n\n"
            . implode("\n", array_filter(
              $lines,
              static fn (string $l): bool => preg_match('/^ {0,3}\[[^\]]+\]:\s/', $l) === 1,
            )),
      ];
    }
    return NULL;
  }

}
