<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * One manual page: a Markdown file of a source directory, the language variant
 * for the reader's locale if there is one. The id is `<source>/<path>/<slug>`,
 * the path being its subdirectory (if any), the slug the filename without
 * locale suffix and `.md`.
 */
final class Page {

  public const LANDING = 'index';

  /**
   * Slug of the page an extension's CHANGELOG.md becomes.
   */
  public const CHANGELOG = 'changes';

  public readonly string $title;

  public readonly ?int $order;

  public readonly ?string $section;

  /**
   * @var list<string>
   */
  public readonly array $routes;

  /**
   * @param array{title: string, order?: int|null, section?: string|null, routes?: list<string>} $meta
   *   The frontmatter keys, title resolved.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $file,
    array $meta,
  ) {
    $this->title = $meta['title'];
    $this->order = $meta['order'] ?? NULL;
    $this->section = $meta['section'] ?? NULL;
    $this->routes = $meta['routes'] ?? [];
  }

  public function source(): string {
    return explode('/', $this->id, 2)[0];
  }

  /**
   * The id of the directory the page sits in: `<source>` or `<source>/<path>`.
   */
  public function dir(): string {
    return substr($this->id, 0, (int) strrpos($this->id, '/'));
  }

  /**
   * index.md at the top of the source: its landing page, whose `order` orders the source.
   */
  public function isLanding(): bool {
    return $this->id === $this->source() . '/' . self::LANDING;
  }

  /**
   * index.md of a subdirectory: the first page of the directory's section.
   */
  public function isSectionIndex(): bool {
    return !$this->isLanding() && str_ends_with($this->id, '/' . self::LANDING);
  }

  /**
   * The page an extension's CHANGELOG.md becomes, not a manual page of that slug.
   */
  public function isChangelog(): bool {
    return str_ends_with($this->id, '/' . self::CHANGELOG) && basename($this->file) === 'CHANGELOG.md';
  }

  /**
   * The same page in the section $section.
   */
  public function withSection(string $section): self {
    return new self($this->id, $this->file, [
      'title' => $this->title,
      'order' => $this->order,
      'section' => $section,
      'routes' => $this->routes,
    ]);
  }

}
