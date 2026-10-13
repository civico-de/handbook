<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Reads the pages of one source directory as a reader of one locale sees
 * them: per slug the best language variant, else the default file.
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
   * One page per slug. Every file is read, so a broken variant is reported in every locale.
   *
   * @return list<\Civi\Handbook\Page>
   */
  public function scan(string $source, string $dir): array {
    $files = [];
    foreach (glob($dir . '/*.md') ?: [] as $file) {
      $parsed = PageName::parse(basename($file));
      if ($parsed === NULL) {
        $this->errors[] = E::ts('%1: invalid file name.', [1 => $source . '/' . basename($file)]);
        continue;
      }
      $files[$parsed[0]][$parsed[1] ?? ''] = $file;
    }
    $group = [];
    foreach ($files as $slug => $variants) {
      $group[] = $this->pick($source, (string) $slug, $variants);
    }
    return Ordering::source(array_values(array_filter($group)));
  }

  /**
   * @param array<string, string> $variants
   *   Files of one slug keyed by locale, '' for the default page.
   */
  private function pick(string $source, string $slug, array $variants): ?Page {
    if (!isset($variants[''])) {
      foreach ($variants as $file) {
        $this->errors[] = E::ts('%1: translation without the default page %2.', [
          1 => $source . '/' . basename($file),
          2 => $slug . '.md',
        ]);
      }
      return NULL;
    }
    $default = $this->load($source, $variants['']);
    unset($variants['']);
    // A variant of a broken default is still read, so its own errors show.
    $loaded = array_map(fn (string $file): ?Page => $this->load($source, $file, $default), $variants);
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

  private function load(string $source, string $file, ?Page $default = NULL): ?Page {
    try {
      return PageLoader::load($source, $file, $default);
    }
    catch (HandbookException $e) {
      $this->errors[] = $e->getMessage();
      return NULL;
    }
  }

}
