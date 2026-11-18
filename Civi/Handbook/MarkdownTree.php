<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * The Markdown files of a source directory and its subdirectories.
 */
final class MarkdownTree {

  /**
   * @var list<string>
   */
  public array $errors = [];

  /**
   * Paths relative to $dir, sorted, without hidden entries and without
   * following directory links. An unreadable directory is reported.
   *
   * @return list<string>
   */
  public function files(string $source, string $dir): array {
    if (!is_readable($dir)) {
      $this->errors[] = E::ts('%1: directory not readable.', [1 => $source]);
      return [];
    }
    $found = [];
    $entries = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      fn (mixed $f): bool => $f instanceof \SplFileInfo && $this->accept($source, $dir, $f),
    ));
    foreach ($entries as $entry) {
      /** @var \SplFileInfo $entry */
      if (!$entry->isFile() || !str_ends_with($entry->getFilename(), '.md')) {
        continue;
      }
      $found[] = substr($entry->getPathname(), strlen($dir) + 1);
    }
    sort($found);
    return $found;
  }

  /**
   * A subdirectory path whose every segment could be a slug.
   */
  public static function isPath(string $path): bool {
    return (
      $path === '.'
      || array_filter(
        explode('/', $path),
        static fn (string $segment): bool => PageName::parse($segment . '.md') !== [$segment, NULL],
      ) === []
    );
  }

  private function accept(string $source, string $dir, \SplFileInfo $f): bool {
    if (str_starts_with($f->getFilename(), '.')) {
      return FALSE;
    }
    if ($f->isDir() && !$f->isReadable()) {
      $this->errors[] = E::ts('%1: directory not readable.', [
        1 => $source . '/' . substr($f->getPathname(), strlen($dir) + 1),
      ]);
      return FALSE;
    }
    return TRUE;
  }

}
