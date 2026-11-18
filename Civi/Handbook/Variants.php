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
    $tree = new MarkdownTree();
    $relatives = $tree->files($source, $dir);
    array_push($this->errors, ...$tree->errors);
    $files = [];
    foreach ($relatives as $relative) {
      $id = $source . (dirname($relative) === '.' ? '' : '/' . dirname($relative));
      $parsed = MarkdownTree::isPath(dirname($relative)) ? PageName::parse(basename($relative)) : NULL;
      if ($parsed === NULL) {
        $this->errors[] = E::ts('%1: invalid file name.', [1 => $source . '/' . $relative]);
        continue;
      }
      $files[$id][$parsed[0]][$parsed[1] ?? ''] = $dir . '/' . $relative;
    }
    $group = [];
    foreach ($files as $id => $slugs) {
      $pages = array_values(array_filter(array_map(
        fn (string|int $slug, array $variants): ?Page => $this->pick((string) $id, (string) $slug, $variants),
        array_keys($slugs),
        $slugs,
      )));
      if ((string) $id !== $source) {
        $pages = DirectorySection::apply($pages);
      }
      array_push($group, ...$pages);
    }
    return Ordering::source($group);
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
