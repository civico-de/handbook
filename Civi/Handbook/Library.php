<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * All manual pages of all sources, with the file list cached until the next
 * system flush.
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
   */
  public function __construct(
    private readonly array $sources,
    private readonly array $sourceErrors = [],
    private readonly ?\CRM_Utils_Cache_Interface $cache = NULL,
  ) {}

  public static function fromSystem(): self {
    if (!isset(\Civi::$statics[self::class])) {
      $sources = Sources::fromSystem();
      \Civi::$statics[self::class] = new self($sources->dirs, $sources->errors, \Civi::cache('long'));
    }
    /** @var self */
    return \Civi::$statics[self::class];
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
   * All pages, grouped by source in source order, each group sorted by
   * `order` (pages without one last), then title ignoring case.
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
    $pages = [];
    foreach ($this->sources as $source => $info) {
      array_push($pages, ...$this->scan($source, $info['dir']));
    }
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

  /**
   * @return list<\Civi\Handbook\Page>
   */
  private function scan(string $source, string $dir): array {
    $group = [];
    foreach (glob($dir . '/*.md') ?: [] as $file) {
      try {
        $group[] = PageLoader::load($source, $file);
      }
      catch (HandbookException $e) {
        $this->errors[] = $e->getMessage();
      }
    }
    usort($group, self::compare(...));
    return $group;
  }


  private function cacheKey(): string {
    return 'handbook_pages_' . md5(serialize($this->sources));
  }


  /**
   * `order` ascending (none last), then title ignoring case, then id.
   */
  public static function compare(Page $a, Page $b): int {
    return [$a->order ?? PHP_INT_MAX, mb_strtolower($a->title), $a->id]
      <=> [$b->order ?? PHP_INT_MAX, mb_strtolower($b->title), $b->id];
  }

}
