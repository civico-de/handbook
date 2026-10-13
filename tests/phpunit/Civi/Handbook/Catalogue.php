<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Test support: the entries of the German .po file.
 */
final class Catalogue {

  public const ROOT = __DIR__ . '/../../../..';

  private const PO = self::ROOT . '/l10n/de_DE/LC_MESSAGES/handbook.po';

  private const ENTRY = '/^msgid "(.*)"\n(?:msgid_plural "(.*)"\n)?((?:msgstr(?:\[\d\])? ".*"\n)+)/m';

  /**
   * Entries of the .po file keyed by msgid, without the header entry. Each
   * msgid and msgstr sits on one line, as the file's header asks.
   *
   * @return array<string, array{plural: string|null, msgstr: list<string>}>
   */
  public static function entries(): array {
    preg_match_all(self::ENTRY, (string) file_get_contents(self::PO), $blocks, PREG_SET_ORDER);
    $entries = [];
    foreach ($blocks as $block) {
      if ($block[1] === '') {
        continue;
      }
      preg_match_all('/^msgstr(?:\[\d\])? "(.*)"$/m', $block[3], $forms);
      $entries[stripcslashes($block[1])] = [
        'plural' => $block[2] === '' ? NULL : stripcslashes($block[2]),
        'msgstr' => array_map('stripcslashes', $forms[1]),
      ];
    }
    return $entries;
  }

}
