<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Renderer output per page, keyed by file path and content hash, the reader's
 * locale, the handbook base URL and this extension's version, so edited text
 * and a new release show at once. A hit never builds the CommonMark environment.
 */
final class RenderCache {

  /**
   * @var \Closure(string, array<string, string>, string|null): string
   */
  private \Closure $url;

  /**
   * @param (\Closure(string, array<string, string>, string|null): string)|null $url
   *   Builds an unescaped Civi URL from path, query and fragment.
   * @param string $version
   *   Part of the key; fromSystem() passes the installed extension version.
   */
  public function __construct(
    private readonly ?\CRM_Utils_Cache_Interface $cache,
    ?\Closure $url = NULL,
    private readonly string $version = '',
  ) {
    $this->url = $url ?? View::defaultUrl(...);
  }

  public static function fromSystem(): self {
    return new self(\Civi::cache('long'), NULL, self::installedVersion());
  }

  public static function installedVersion(): string {
    return (string) \CRM_Extension_System::singleton()->getMapper()->keyToInfo(E::LONG_NAME)->version;
  }

  /**
   * @return array{html: string, headings: list<array{level: int, text: string, slug: string, line: int}>}
   *
   * @throws \Civi\Handbook\HandbookException
   *   When the file is gone (Library::body()).
   */
  public function page(Page $page, Library $library): array {
    clearstatcache(TRUE, $page->file);
    $key = is_file($page->file) ? $this->key($page, $library->locale()) : NULL;
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

  private function key(Page $page, string $locale): string {
    return 'handbook_render_'
    . md5(serialize([
      $page->id,
      $page->file,
      hash_file('xxh128', $page->file),
      $locale,
      ($this->url)('civicrm/handbook', [], NULL),
      $this->version,
    ]));
  }

}
