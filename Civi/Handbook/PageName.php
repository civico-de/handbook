<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * The filename rule: `<slug>.md` is a default page, `<slug>.<locale>.md` its
 * language variant, the locale being `xx` or `xx_YY`.
 */
final class PageName {

  private const SLUG = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/';

  /**
   * A last dot segment of this form names the variant's locale, not part of the slug.
   */
  private const LOCALE = '[a-z]{2}(?:_[A-Z]{2})?';

  /**
   * Slug and locale of a filename: `events.md` gives ['events', NULL],
   * `events.de_DE.md` ['events', 'de_DE'], `v1.2.md` ['v1.2', NULL].
   *
   * @return array{0: string, 1: string|null}|null
   *   NULL for a name that cannot be a page.
   */
  public static function parse(string $basename): ?array {
    if (!str_ends_with($basename, '.md')) {
      return NULL;
    }
    $stem = substr($basename, 0, -3);
    if (preg_match('/^(.+)\.(' . self::LOCALE . ')$/', $stem, $m) === 1 && preg_match(self::SLUG, $m[1]) === 1) {
      return [$m[1], $m[2]];
    }
    return preg_match(self::SLUG, $stem) === 1 ? [$stem, NULL] : NULL;
  }

  /**
   * The slug a link to `<basename>` points at: a variant names its page.
   */
  public static function slug(string $basename): string {
    return self::parse($basename)[0] ?? basename($basename, '.md');
  }

  /**
   * Locales whose variant a reader of $locale sees, best first: the full
   * locale, then its language. The default page comes after these.
   *
   * @return list<string>
   */
  public static function preference(string $locale): array {
    if (preg_match('/^' . self::LOCALE . '$/', $locale) !== 1) {
      return [];
    }
    $language = substr($locale, 0, 2);
    return $language === $locale ? [$locale] : [$locale, $language];
  }

}
