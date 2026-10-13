<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * All manual pages of all sources as a reader of one locale sees them, with
 * the file list cached per locale until the next system flush.
 */
final class Library {

  /**
   * @var list<\Civi\Handbook\Page>|null
   */
  private ?array $pages = NULL;

  /**
   * @var list<string>
   */
  private array $errors = [];

  /**
   * @param array<string, array{dir: string, label: string}> $sources
   *   Readable source directories keyed by source name.
   * @param list<string> $sourceErrors
   *   Problems with configured sources, shown on the index.
   * @param string $locale
   *   The reader's CiviCRM locale; it picks the language variant of each page.
   */
  public function __construct(
    private readonly array $sources,
    private readonly array $sourceErrors = [],
    private readonly ?\CRM_Utils_Cache_Interface $cache = NULL,
    private readonly string $locale = 'en_US',
  ) {}

  /**
   * The library for the current user's locale, once per request and locale.
   */
  public static function fromSystem(): self {
    $locale = \CRM_Core_I18n::getLocale();
    /** @var array<string, self> $libraries */
    $libraries = \Civi::$statics[self::class] ?? [];
    if (!isset($libraries[$locale])) {
      $sources = Sources::fromSystem();
      $libraries[$locale] = new self($sources->dirs, $sources->errors, \Civi::cache('long'), $locale);
      \Civi::$statics[self::class] = $libraries;
    }
    return $libraries[$locale];
  }

  public function locale(): string {
    return $this->locale;
  }

  /**
   * @return array<string, array{dir: string, label: string}>
   */
  public function sources(): array {
    return $this->sources;
  }

  /**
   * Problems with sources or files, for display on the index.
   *
   * @return list<string>
   */
  public function errors(): array {
    $this->pages();
    return [...$this->sourceErrors, ...$this->errors];
  }

  /**
   * All pages in table-of-contents order: grouped by source (landing-page `order`, then
   * label), within a source as in Ordering::source().
   *
   * @return list<\Civi\Handbook\Page>
   */
  public function pages(): array {
    if ($this->pages !== NULL) {
      return $this->pages;
    }
    $key = $this->cacheKey();
    $cached = $this->cache?->get($key);
    if (is_array($cached) && isset($cached['pages'], $cached['errors'])) {
      /** @var array{pages: list<\Civi\Handbook\Page>, errors: list<string>} $cached */
      $this->errors = $cached['errors'];
      return $this->pages = $cached['pages'];
    }
    $variants = new Variants($this->locale);
    $groups = [];
    foreach ($this->sources as $source => $info) {
      $groups[$source] = $variants->scan($source, $info['dir']);
    }
    $pages = Ordering::sources($groups, $this->sources);
    $this->errors = $variants->errors;
    $this->cache?->set($key, ['pages' => $pages, 'errors' => $this->errors]);
    return $this->pages = $pages;
  }

  public function find(string $id): Page {
    foreach ($this->pages() as $page) {
      if ($page->id === $id) {
        return $page;
      }
    }
    throw HandbookException::notFound("No handbook page {$id}");
  }

  /**
   * Pages whose frontmatter `routes` name this Civi path.
   *
   * @return list<\Civi\Handbook\Page>
   */
  public function forRoute(string $path): array {
    $path = trim($path, '/');
    return array_values(array_filter($this->pages(), static fn (Page $p): bool => in_array($path, $p->routes, TRUE)));
  }

  /**
   * The Markdown of a page without its frontmatter.
   */
  public function body(Page $page): string {
    $content = is_file($page->file) && is_readable($page->file) ? file_get_contents($page->file) : FALSE;
    if ($content === FALSE) {
      // The cached list is stale: drop it so the next listing rescans.
      $this->cache?->delete($this->cacheKey());
      $this->pages = NULL;
      $this->errors = [];
      throw HandbookException::notFound("Cannot read {$page->file}");
    }
    return Frontmatter::split($content)[1];
  }

  private function cacheKey(): string {
    return self::pagesKey(
      $this->sources,
      $this->locale,
      array_map(
        static fn (\ReflectionProperty $p): string => $p->getName(),
        (new \ReflectionClass(Page::class))->getProperties(),
      ),
    );
  }

  /**
   * Cache key of the page list for one locale. It includes Page's property
   * names, so a changed Page class never unserializes a stale shape.
   *
   * @param array<string, array{dir: string, label: string}> $sources
   * @param list<string> $pageProperties
   */
  public static function pagesKey(array $sources, string $locale, array $pageProperties): string {
    return 'handbook_pages_' . md5(serialize([$sources, $locale, $pageProperties]));
  }

}
