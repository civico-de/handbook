<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Renderer output per page, cached by file path, mtime, size and the handbook
 * base URL, so edited text shows at once. A hit never builds the CommonMark environment.
 */
final class RenderCache {

  /**
   * @var \Closure(string, array<string, string>, string|null): string
   */
  private \Closure $url;

  /**
   * @param (\Closure(string, array<string, string>, string|null): string)|null $url
   *   Builds an unescaped Civi URL from path, query and fragment.
   */
  public function __construct(
    private readonly ?\CRM_Utils_Cache_Interface $cache,
    ?\Closure $url = NULL,
  ) {
    $this->url = $url ?? View::defaultUrl(...);
  }

  public static function fromSystem(): self {
    return new self(\Civi::cache('long'));
  }

  /**
   * @return array{html: string, headings: list<array{level: int, text: string, slug: string, line: int}>}
   *
   * @throws \Civi\Handbook\HandbookException
   *   When the file is gone (Library::body()).
   */
  public function page(Page $page, Library $library): array {
    clearstatcache(TRUE, $page->file);
    $key = is_file($page->file) ? $this->key($page) : NULL;
    $cached = $key === NULL ? NULL : $this->cache?->get($key);
    if (is_array($cached) && is_string($cached['html'] ?? NULL) && is_array($cached['headings'] ?? NULL)) {
      /** @var array{html: string, headings: list<array{level: int, text: string, slug: string, line: int}>} $cached */
      return $cached;
    }
    $rendered = (new Renderer($this->url))->render($page->source(), $library->body($page));
    if ($key !== NULL) {
      $this->cache?->set($key, $rendered);
    }
    return $rendered;
  }

  private function key(Page $page): string {
    return 'handbook_render_'
    . md5(serialize([
      $page->id,
      $page->file,
      filemtime($page->file),
      filesize($page->file),
      ($this->url)('civicrm/handbook', [], NULL),
    ]));
  }

}
