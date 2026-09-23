<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Where a page sits: its breadcrumb and its neighbours in table-of-contents order.
 */
final class PageNav {

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
    $this->url = $url ?? View::defaultUrl(...);
  }

  /**
   * Handbuch › source › section; the source links to its landing page, if it has one.
   *
   * @return list<array{label: string, url: string|null}>
   */
  public function breadcrumb(Page $page): array {
    $landing = array_filter(
      $this->library->pages(),
      static fn (Page $p): bool => $p->source() === $page->source() && $p->isLanding() && $p->id !== $page->id,
    );
    $crumbs = [
      ['label' => E::ts('Handbuch'), 'url' => ($this->url)('civicrm/handbook', [], NULL)],
      [
        'label' => $this->library->sources()[$page->source()]['label'] ?? $page->source(),
        'url' => $landing === [] ? NULL : $this->pageUrl(reset($landing)),
      ],
    ];
    if ($page->section !== NULL && !$page->isLanding()) {
      $crumbs[] = ['label' => $page->section, 'url' => NULL];
    }
    return $crumbs;
  }

  /**
   * The pages before and after this one in Library::pages() order.
   *
   * @return array{prev: array{title: string, url: string}|null, next: array{title: string, url: string}|null}
   */
  public function pager(Page $page): array {
    $pages = $this->library->pages();
    $index = array_search($page->id, array_map(static fn (Page $p): string => $p->id, $pages), TRUE);
    if (!is_int($index)) {
      return ['prev' => NULL, 'next' => NULL];
    }
    return [
      'prev' => $this->link($pages[$index - 1] ?? NULL),
      'next' => $this->link($pages[$index + 1] ?? NULL),
    ];
  }

  /**
   * @return array{title: string, url: string}|null
   */
  private function link(?Page $page): ?array {
    return $page === NULL ? NULL : ['title' => $page->title, 'url' => $this->pageUrl($page)];
  }

  private function pageUrl(Page $page): string {
    return ($this->url)('civicrm/handbook', ['p' => $page->id], NULL);
  }

}
