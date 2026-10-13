<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Test support: the translatable strings of the extension's source.
 */
final class SourceStrings {

  /**
   * E::ts() literals in the PHP files and {ts} blocks in the templates, with their plural form.
   *
   * @return array<string, string|null>
   */
  public static function all(): array {
    $strings = [];
    foreach (self::files() as $path) {
      $code = (string) file_get_contents($path);
      $strings += str_ends_with($path, '.tpl') ? self::templateStrings($code) : self::phpStrings($code);
    }
    return $strings;
  }

  /**
   * @return list<string>
   */
  private static function files(): array {
    $files = glob(Catalogue::ROOT . '/*.php') ?: [];
    foreach (['Civi', 'CRM', 'managed', 'settings', 'templates'] as $dir) {
      $tree = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
        Catalogue::ROOT . '/' . $dir,
        \FilesystemIterator::SKIP_DOTS,
      ));
      foreach ($tree as $file) {
        $files[] = $file->getPathname();
      }
    }
    return array_values(array_filter($files, static fn (string $f): bool => preg_match('/\.(php|tpl)$/', $f) === 1));
  }

  /**
   * The first argument of each E::ts() call, read with the PHP tokenizer.
   *
   * @return array<string, null>
   */
  private static function phpStrings(string $code): array {
    $tokens = array_values(array_filter(
      token_get_all($code),
      static fn (array|string $t): bool => (
        !is_array($t) || !in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], TRUE)
      ),
    ));
    $text = array_map(static fn (array|string $t): string => is_array($t) ? $t[1] : $t, $tokens);
    $strings = [];
    foreach (array_keys($text, 'E', TRUE) as $i) {
      $argument = $tokens[$i + 4] ?? NULL;
      if (
        array_slice($text, $i, 4) !== ['E', '::', 'ts', '(']
        || !is_array($argument)
        || $argument[0] !== T_CONSTANT_ENCAPSED_STRING
      ) {
        continue;
      }
      $strings[self::unquote($argument[1])] = NULL;
    }
    return $strings;
  }

  private static function unquote(string $literal): string {
    $inner = substr($literal, 1, -1);
    return $literal[0] === "'" ? strtr($inner, ['\\\\' => '\\', "\\'" => "'"]) : stripcslashes($inner);
  }

  /**
   * @return array<string, string|null>
   */
  private static function templateStrings(string $template): array {
    $template = (string) preg_replace('/\{\*.*?\*\}/s', '', $template);
    preg_match_all('#\{ts\b([^}]*)\}(.*?)\{/ts\}#s', $template, $blocks, PREG_SET_ORDER);
    $strings = [];
    foreach ($blocks as [, $params, $text]) {
      $strings[$text] = preg_match('/\bplural="([^"]*)"/', $params, $m) === 1 ? $m[1] : NULL;
    }
    return $strings;
  }

}
