<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Template variables shared by the handbook screens.
 */
final class View {

  public const HITS_PER_PAGE = 5;

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
    ?\Closure $url = NULL,
  ) {
    $this->url = $url ?? self::defaultUrl(...);
  }

  /**
   * @param array<string, string> $query
   */
  public static function defaultUrl(string $path, array $query, ?string $fragment = NULL): string {
    return \CRM_Utils_System::url($path, $query, FALSE, $fragment, FALSE);
  }

  /**
   * Search form, stylesheet, errors and table of contents for a screen. Every
   * source is open on the index, otherwise only the current page's source.
   */
  public function assignChrome(
    \CRM_Core_Page $page,
    string $term = '',
    ?string $currentId = NULL,
    bool $openAll = FALSE,
  ): void {
    \Civi::resources()->addStyleFile(E::LONG_NAME, 'css/handbook.css');
    $page->assign('handbookSearch', self::searchForm());
    $page->assign('handbookTerm', $term);
    $page->assign('handbookErrors', $this->library->errors());
    $page->assign('handbookToc', $this->toc($currentId, $openAll));
  }

  /**
   * A GET parameter as string; anything else counts as absent.
   */
  public static function param(string $name): string {
    $value = \CRM_Utils_Request::retrieveValue($name, 'String', '', FALSE, 'GET');
    return is_string($value) ? trim($value) : '';
  }

  /**
   * Table of contents: one group per source with its landing page and its
   * pages by section, in Library::pages() order.
   *
   * @return list<array{source: string, label: string, open: bool, landing: array{id: string, title: string, url: string, current: bool}|null, sections: list<array{name: string|null, pages: list<array{id: string, title: string, url: string, current: bool}>}>}>
   */
  public function toc(?string $currentId = NULL, bool $openAll = FALSE): array {
    $bySource = [];
    foreach ($this->library->pages() as $page) {
      $bySource[$page->source()][] = $page;
    }
    $toc = [];
    foreach ($bySource as $source => $pages) {
      $landing = NULL;
      $bySection = [];
      foreach ($pages as $page) {
        if ($page->isLanding()) {
          $landing = $this->entry($page, $currentId);
          continue;
        }
        // Prefixed so a numeric section name stays a string key.
        $bySection['s' . ($page->section ?? '')][] = $page;
      }
      $toc[] = [
        'source' => (string) $source,
        'label' => $this->library->sources()[$source]['label'] ?? (string) $source,
        'open' => $openAll || in_array($currentId, array_map(static fn (Page $p): string => $p->id, $pages), TRUE),
        'landing' => $landing,
        'sections' => array_map(fn (array $group): array => [
          'name' => $group[0]->section,
          'pages' => array_map(fn (Page $p): array => $this->entry($p, $currentId), $group),
        ], array_values($bySection)),
      ];
    }
    return $toc;
  }

  /**
   * Search hits grouped by page, in hit order, with unescaped links to their
   * sections; `more` counts the hits beyond HITS_PER_PAGE.
   *
   * @param list<array{page: \Civi\Handbook\Page, heading: string|null, anchor: string|null, context: string}> $hits
   *
   * @return list<array{title: string, place: string, url: string, hits: list<array{heading: string|null, context: string, url: string}>, more: int}>
   */
  public function results(array $hits): array {
    $byPage = [];
    foreach ($hits as $hit) {
      $byPage[$hit['page']->id][] = $hit;
    }
    return array_map(function (array $pageHits): array {
      $page = $pageHits[0]['page'];
      $place = [$this->library->sources()[$page->source()]['label'] ?? $page->source(), $page->section];
      return [
        'title' => $page->title,
        'place' => implode(' › ', array_filter($place, 'is_string')),
        'url' => $this->entry($page, NULL)['url'],
        'hits' => array_map(
          fn (array $hit): array => [
            'heading' => $hit['heading'],
            'context' => $hit['context'],
            'url' => ($this->url)('civicrm/handbook', ['p' => $page->id], $hit['anchor']),
          ],
          array_slice($pageHits, 0, self::HITS_PER_PAGE),
        ),
        'more' => max(0, count($pageHits) - self::HITS_PER_PAGE),
      ];
    }, array_values($byPage));
  }

  /**
   * @return array{id: string, title: string, url: string, current: bool}
   */
  private function entry(Page $page, ?string $currentId): array {
    return [
      'id' => $page->id,
      'title' => $page->title,
      'url' => ($this->url)('civicrm/handbook', ['p' => $page->id], NULL),
      'current' => $page->id === $currentId,
    ];
  }

  /**
   * GET form target. Query parameters of the route URL (non-clean CMS URLs
   * carry the path in one) become hidden fields, since GET drops them.
   *
   * @return array{action: string, hidden: array<string, string>}
   */
  public static function searchForm(): array {
    $url = \CRM_Utils_System::url('civicrm/handbook/search', '', FALSE, NULL, FALSE);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    $hidden = [];
    foreach ($query as $name => $value) {
      if (!is_string($value)) {
        continue;
      }
      $hidden[(string) $name] = $value;
    }
    return ['action' => strtok($url, '?') ?: $url, 'hidden' => $hidden];
  }

}
