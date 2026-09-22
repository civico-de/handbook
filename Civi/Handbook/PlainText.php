<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Plain text of one Markdown line, for search result snippets.
 */
final class PlainText {

  /**
   * A Markdown line as plain text: heading markers, emphasis, code spans and
   * link/image syntax reduced to their text.
   */
  public static function fromLine(string $line): string {
    $line = preg_replace('/^\s{0,3}#{1,6}(?:\s+|$)|\s+#+\s*$/', '', trim($line)) ?? $line;
    return self::inline($line);
  }

  private static function inline(string $text): string {
    // Left to right, so a code span keeps its asterisks and a link its underscores.
    $pattern = '/(`+)(.+?)\1|!?\[([^\]]*)\]\([^)]*\)|(\*\*|\*|~~)(\S(?:.*?\S)?)\4|(?<!\w)(__|_)(\S(?:.*?\S)?)\6(?!\w)/u';
    return (
      preg_replace_callback(
        $pattern,
        static fn (array $m): string => match (TRUE) {
          ($m[1] ?? '') !== '' => trim($m[2]),
          ($m[4] ?? '') !== '' => self::inline($m[5]),
          ($m[6] ?? '') !== '' => self::inline($m[7]),
          default => self::inline($m[3] ?? ''),
        },
        $text,
      ) ?? $text
    );
  }

}
