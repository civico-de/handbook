<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * Reads one Markdown file: optional YAML frontmatter (title, order, section, routes),
 * title fallback to the first H1, then the filename. A language variant
 * builds on its default page.
 */
final class PageLoader {

  /**
   * @param \Civi\Handbook\Page|null $default
   *   The default page a variant builds on: the variant's own frontmatter keys
   *   win, the others come from $default; its title follows the variant's heading.
   *
   * @throws \Civi\Handbook\HandbookException
   *   With a message naming the file when it cannot be a page.
   */
  public static function load(string $source, string $file, ?Page $default = NULL): Page {
    $name = $source . '/' . basename($file);
    $parsed = PageName::parse(basename($file));
    if ($parsed === NULL) {
      throw HandbookException::badRequest(E::ts('%1: invalid file name.', [1 => $name]));
    }
    [$yaml, $body] = Frontmatter::split((string) file_get_contents($file));
    $own = Frontmatter::parse($name, $yaml);
    $meta = $own;
    $meta['title'] ??= self::firstH1($body) ?? $default->title ?? $parsed[0];
    $meta = PageMeta::validate($name, $meta);
    return new Page(
      $source . '/' . $parsed[0],
      $file,
      $default === NULL ? $meta : PageMeta::inherit($meta, $own, $default),
    );
  }

  private static function firstH1(string $body): ?string {
    return preg_match('/^# +(.+?)(?: +#+)? *$/m', $body, $m) === 1 ? $m[1] : NULL;
  }

}
