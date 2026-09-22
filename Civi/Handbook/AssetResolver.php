<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Maps `<source>/<relpath>` to an image file inside that source directory.
 * The real path must stay inside the directory; only image types are served.
 */
final class AssetResolver {

  public const TYPES = [
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
  ];

  public function __construct(
    private readonly Library $library,
  ) {}

  /**
   * @return array{file: string, type: string}
   */
  public function resolve(string $p): array {
    if (str_contains($p, "\0") || !str_contains($p, '/')) {
      throw HandbookException::badRequest('Malformed asset path');
    }
    [$source, $relative] = explode('/', $p, 2);
    $type = self::TYPES[strtolower(pathinfo($relative, PATHINFO_EXTENSION))] ?? NULL;
    if ($type === NULL) {
      throw HandbookException::badRequest('Not an image type');
    }
    $dir = $this->library->sources()[$source]['dir'] ?? NULL;
    if ($dir === NULL) {
      throw HandbookException::notFound('Unknown source');
    }
    $file = realpath($dir . '/' . $relative);
    if ($file === FALSE || !is_file($file)) {
      throw HandbookException::notFound('No such asset');
    }
    if (!str_starts_with($file, $dir . DIRECTORY_SEPARATOR)) {
      throw HandbookException::badRequest('Path outside the source directory');
    }
    return ['file' => $file, 'type' => $type];
  }

}
