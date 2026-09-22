<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Template variables shared by the handbook screens.
 */
final class View {

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
   * Search form, stylesheet, errors and table of contents for a screen.
   */
  public function assignChrome(\CRM_Core_Page $page, string $term = '', ?string $currentId = NULL): void {
    \Civi::resources()->addStyleFile(E::LONG_NAME, 'css/handbook.css');
    $page->assign('handbookSearch', self::searchForm());
    $page->assign('handbookTerm', $term);
    $page->assign('handbookErrors', $this->library->errors());
    $page->assign('handbookToc', $this->toc($currentId));
  }

  /**
   * A GET parameter as string; anything else counts as absent.
   */
  public static function param(string $name): string {
    $value = \CRM_Utils_Request::retrieveValue($name, 'String', '', FALSE, 'GET');
    return is_string($value) ? trim($value) : '';
  }

  /**
   * Table of contents: one group per source, pages in display order.
   *
   * @return list<array{label: string, url: string|null, current: bool, pages: list<array{id: string, title: string, url: string, current: bool}>}>
   */
  public function toc(?string $currentId = NULL): array {
    $groups = [];
    foreach ($this->library->pages() as $page) {
      $source = $page->source();
      $groups[$source] ??= [
        'label' => $this->library->sources()[$source]['label'] ?? $source,
        'url' => NULL,
        'current' => FALSE,
        'pages' => [],
      ];
      $url = ($this->url)('civicrm/handbook', ['p' => $page->id], NULL);
      if ($page->isLanding()) {
        $groups[$source]['url'] = $url;
        $groups[$source]['current'] = $page->id === $currentId;
        continue;
      }
      $groups[$source]['pages'][] = [
        'id' => $page->id,
        'title' => $page->title,
        'url' => $url,
        'current' => $page->id === $currentId,
      ];
    }
    return array_values($groups);
  }

  /**
   * Search hits as template rows with unescaped links to their sections.
   *
   * @param list<array{page: \Civi\Handbook\Page, heading: string|null, anchor: string|null, context: string}> $hits
   *
   * @return list<array{title: string, heading: string|null, context: string, url: string}>
   */
  public function results(array $hits): array {
    return array_map(fn (array $hit): array => [
      'title' => $hit['page']->title,
      'heading' => $hit['heading'],
      'context' => $hit['context'],
      'url' => ($this->url)('civicrm/handbook', ['p' => $hit['page']->id], $hit['anchor']),
    ], $hits);
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
