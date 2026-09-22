<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * One manual page: a Markdown file of a source directory. The id is
 * `<source>/<slug>`, the slug being the filename without `.md`.
 */
final class Page {

  public const LANDING = 'index';

  /**
   * @param list<string> $routes
   */
  public function __construct(
    public readonly string $id,
    public readonly string $title,
    public readonly ?int $order,
    public readonly array $routes,
    public readonly string $file,
  ) {}

  public function source(): string {
    return explode('/', $this->id, 2)[0];
  }

  /**
   * handbook/index.md: the source's landing page, whose `order` orders the source.
   */
  public function isLanding(): bool {
    return str_ends_with($this->id, '/' . self::LANDING);
  }

}
