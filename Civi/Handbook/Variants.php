<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Reads the pages of one source directory and its subdirectories as a reader
 * of one locale sees them: per slug the best language variant, else the default file.
 */
final class Variants {

  /**
   * @var list<string>
   */
  public array $errors = [];

  public function __construct(
    private readonly string $locale,
  ) {}

  /**
   * One page per slug and directory. Every file is read, so a broken variant
   * is reported in every locale. A subdirectory's pages form a section.
   *
   * @return list<\Civi\Handbook\Page>
   */
  public function scan(string $source, string $dir): array {
    $files = [];
    foreach ($this->markdown($source, $dir) as $relative) {
      $id = $source . (dirname($relative) === '.' ? '' : '/' . dirname($relative));
      $parsed = self::isPath(dirname($relative)) ? PageName::parse(basename($relative)) : NULL;
      if ($parsed === NULL) {
        $this->errors[] = E::ts('%1: invalid file name.', [1 => $source . '/' . $relative]);
        continue;
      }
      $files[$id][$parsed[0]][$parsed[1] ?? ''] = $dir . '/' . $relative;
    }
    $group = [];
    foreach ($files as $id => $slugs) {
      $pages = [];
      foreach ($slugs as $slug => $variants) {
        $pages[] = $this->pick((string) $id, (string) $slug, $variants);
      }
      array_push($group, ...self::sectioned((string) $id !== $source, array_values(array_filter($pages))));
    }
    return Ordering::source($group);
  }

  /**
   * Markdown files below $dir as paths relative to it, without hidden entries
   * and without following directory links. An unreadable subdirectory is reported.
   *
   * @return list<string>
   */
  private function markdown(string $source, string $dir): array {
    if (!is_readable($dir)) {
      $this->errors[] = E::ts('%1: directory not readable.', [1 => $source]);
      return [];
    }
    $found = [];
    $entries = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      function (\SplFileInfo $f) use ($source, $dir): bool {
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
      },
    ));
    foreach ($entries as $entry) {
      /** @var \SplFileInfo $entry */
      if ($entry->isFile() && str_ends_with($entry->getFilename(), '.md')) {
        $found[] = substr($entry->getPathname(), strlen($dir) + 1);
      }
    }
    sort($found);
    return $found;
  }

  /**
   * A subdirectory path whose every segment could be a slug.
   */
  private static function isPath(string $path): bool {
    return $path === '.' || array_filter(
      explode('/', $path),
      static fn (string $segment): bool => PageName::parse($segment . '.md') !== [$segment, NULL],
    ) === [];
  }

  /**
   * Pages of a subdirectory without their own `section` take the directory's:
   * its index page's section or title, else the directory name.
   *
   * @param list<\Civi\Handbook\Page> $pages
   *
   * @return list<\Civi\Handbook\Page>
   */
  private static function sectioned(bool $isSubdir, array $pages): array {
    if (!$isSubdir || $pages === []) {
      return $pages;
    }
    $section = basename($pages[0]->dir());
    foreach ($pages as $page) {
      if ($page->isSectionIndex()) {
        $section = $page->section ?? $page->title;
      }
    }
    return array_map(static fn (Page $p): Page => $p->section === NULL ? $p->withSection($section) : $p, $pages);
  }

  /**
   * @param array<string, string> $variants
   *   Files of one slug keyed by locale, '' for the default page.
   */
  private function pick(string $dir, string $slug, array $variants): ?Page {
    if (!isset($variants[''])) {
      foreach ($variants as $file) {
        $this->errors[] = E::ts('%1: translation without the default page %2.', [
          1 => $dir . '/' . basename($file),
          2 => $slug . '.md',
        ]);
      }
      return NULL;
    }
    $default = $this->load($dir, $variants['']);
    unset($variants['']);
    // A variant of a broken default is still read, so its own errors show.
    $loaded = array_map(fn (string $file): ?Page => $this->load($dir, $file, $default), $variants);
    if ($default === NULL) {
      return NULL;
    }
    foreach (PageName::preference($this->locale) as $locale) {
      if (isset($loaded[$locale])) {
        return $loaded[$locale];
      }
    }
    return $default;
  }

  private function load(string $dir, string $file, ?Page $default = NULL): ?Page {
    try {
      return PageLoader::load($dir, $file, $default);
    }
    catch (HandbookException $e) {
      $this->errors[] = $e->getMessage();
      return NULL;
    }
  }

}
