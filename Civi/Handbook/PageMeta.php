<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * The frontmatter keys of a page: their types, and what a language variant
 * takes from its default page.
 */
final class PageMeta {

  /**
   * @param array<string, mixed> $meta
   *
   * @return array{title: string, order?: int|null, section: string|null, routes: list<string>}
   */
  public static function validate(string $name, array $meta): array {
    $meta['routes'] ??= [];
    if (
      !is_string($meta['title'] ?? NULL)
      || !is_int($meta['order'] ?? 0)
      || !is_string($meta['section'] ?? '')
      || !self::isPathList($meta['routes'])
    ) {
      throw HandbookException::badRequest(E::ts('%1: title and section must be text, order a whole number and routes a list of paths.', [
        1 => $name,
      ]));
    }
    /** @var array{title: string, order?: int|null, section?: string|null, routes: list<string>} $meta */
    $meta['routes'] = array_map(static fn (string $r): string => trim($r, '/'), $meta['routes']);
    $meta['section'] = trim($meta['section'] ?? '') ?: NULL;
    return $meta;
  }

  /**
   * The default page's order, section and routes for the keys the variant's frontmatter leaves out.
   *
   * @param array{title: string, order?: int|null, section: string|null, routes: list<string>} $meta
   * @param array<string, mixed> $own
   *
   * @return array{title: string, order: int|null, section: string|null, routes: list<string>}
   */
  public static function inherit(array $meta, array $own, Page $default): array {
    return [
      'title' => $meta['title'],
      'order' => array_key_exists('order', $own) ? $meta['order'] ?? NULL : $default->order,
      'section' => array_key_exists('section', $own) ? $meta['section'] : $default->section,
      'routes' => array_key_exists('routes', $own) ? $meta['routes'] : $default->routes,
    ];
  }

  private static function isPathList(mixed $routes): bool {
    return (
      is_array($routes)
      && array_is_list($routes)
      && array_filter($routes, static fn (mixed $r): bool => !is_string($r)) === []
    );
  }

}
