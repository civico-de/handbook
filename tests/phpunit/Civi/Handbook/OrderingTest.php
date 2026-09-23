<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Handbook\Ordering
 * @covers \Civi\Handbook\Page
 */
final class OrderingTest extends TestCase {

  private static function page(string $slug, ?int $order, ?string $section = NULL): Page {
    return new Page('s/' . $slug, '/' . $slug . '.md', ['title' => $slug, 'order' => $order, 'section' => $section]);
  }

  public function testSectionsSitWhereTheirFirstPageSortsLandingFirst(): void {
    $pages = [
      self::page('ohne-order-a', NULL, 'A'),
      self::page('b2', 4, 'B'),
      self::page('frei2', 5),
      self::page('a1', 3, 'A'),
      self::page('b1', 2, 'B'),
      self::page('frei1', 1),
      self::page(Page::LANDING, NULL, 'Ignoriert'),
    ];
    $ids = array_map(static fn (Page $p): string => $p->id, Ordering::source($pages));
    $this->assertSame(
      ['s/index', 's/frei1', 's/frei2', 's/b1', 's/b2', 's/a1', 's/ohne-order-a'],
      $ids,
    );
  }

  public function testNumericSectionNamesStayApart(): void {
    $pages = [self::page('x', 1, '10'), self::page('y', 2, '2'), self::page('z', 3, '10')];
    $ids = array_map(static fn (Page $p): string => $p->id, Ordering::source($pages));
    $this->assertSame(['s/x', 's/z', 's/y'], $ids);
  }

}
