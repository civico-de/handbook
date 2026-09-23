<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Plain text of one Markdown line, for search result snippets.
 */
final class PlainText {

  /**
   * A Markdown line as plain text: quote, list and heading markers dropped, a
   * table row as its cells joined by " · ", emphasis, code spans and link/image
   * syntax reduced to their text. Rules and table separator rows give ''.
   */
  public static function fromLine(string $line): string {
    $line = preg_replace('/^(?:>\s?)+/', '', trim($line)) ?? $line;
    if (preg_match('/^\|?\s*:?-+:?\s*(?:\|\s*:?-+:?\s*)*\|?$|^([-*_=])(?:\s*\1){2,}$/', $line) === 1) {
      return '';
    }
    if (str_starts_with($line, '|')) {
      return self::row($line);
    }
    $line = preg_replace('/^(?:[-*+]|\d{1,9}[.)])\s+|^\s{0,3}#{1,6}(?:\s+|$)|\s+#+\s*$/', '', $line) ?? $line;
    return self::inline($line);
  }

  /**
   * Non-empty cells of a table row, split on unescaped pipes.
   */
  private static function row(string $line): string {
    $cells = preg_split('/(?<!\\\\)\|/', $line) ?: [];
    $cells = array_map(static fn (string $cell): string => self::inline(str_replace('\\|', '|', trim($cell))), $cells);
    return implode(' · ', array_filter($cells, static fn (string $cell): bool => $cell !== ''));
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
