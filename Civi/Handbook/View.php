<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Template variables shared by the handbook screens.
 */
final class View {

  public function __construct(
    private readonly Library $library,
  ) {}

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
   * @return list<array{label: string, pages: list<array{id: string, title: string, url: string, current: bool}>}>
   */
  public function toc(?string $currentId = NULL): array {
    $groups = [];
    foreach ($this->library->pages() as $page) {
      $source = $page->source();
      $groups[$source] ??= ['label' => $this->library->sources()[$source]['label'] ?? $source, 'pages' => []];
      $groups[$source]['pages'][] = [
        'id' => $page->id,
        'title' => $page->title,
        'url' => \CRM_Utils_System::url('civicrm/handbook', ['p' => $page->id]),
        'current' => $page->id === $currentId,
      ];
    }
    return array_values($groups);
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
