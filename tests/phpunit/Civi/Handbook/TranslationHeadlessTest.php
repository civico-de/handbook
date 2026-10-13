<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use CRM_Handbook_ExtensionUtil as E;

/**
 * The German catalogue in l10n/de_DE: it covers every source string, its
 * compiled .mo matches the .po, and route titles follow the rebuild locale.
 *
 * @group headless
 * @covers \Civi\Handbook\MenuTitles
 */
final class TranslationHeadlessTest extends HandbookTestCase {

  public function testCatalogueHoldsExactlyTheSourceStrings(): void {
    $catalogue = array_map(static fn (array $entry): ?string => $entry['plural'], Catalogue::entries());
    ksort($catalogue);
    $source = SourceStrings::all();
    ksort($source);
    $this->assertSame($source, $catalogue);
  }

  public function testGermanSiteGetsEveryTranslationFromTheCompiledCatalogue(): void {
    $this->useLocale('de_DE');
    foreach (Catalogue::entries() as $msgid => $entry) {
      if ($entry['plural'] === NULL) {
        $this->assertSame($entry['msgstr'][0], E::ts($msgid), $msgid);
        continue;
      }
      foreach ([1 => 0, 2 => 1, 7 => 1] as $count => $form) {
        $this->assertSame(
          str_replace('%count', (string) $count, $entry['msgstr'][$form]),
          E::ts($msgid, ['count' => $count, 'plural' => $entry['plural']]),
          "{$msgid} ({$count})",
        );
      }
    }
  }

  public function testOtherLocalesGetTheEnglishSource(): void {
    foreach (['en_US', 'fr_FR'] as $locale) {
      $this->useLocale($locale);
      $this->assertSame('On this page', E::ts('On this page'), $locale);
      $this->assertSame('2 sections', E::ts('One section', ['count' => 2, 'plural' => '%count sections']), $locale);
    }
  }

  public function testMenuTitlesTranslateTheRoutesOfTheMenuFile(): void {
    $xml = simplexml_load_file(Catalogue::ROOT . '/xml/Menu/handbook.xml');
    $this->assertNotFalse($xml);
    $routes = [];
    foreach ($xml->item as $item) {
      $routes[(string) $item->path] = (string) $item->title;
    }
    $this->assertSame($routes, MenuTitles::titles(), 'English titles match the menu file');
    $this->assertSame('Handbook settings', \CRM_Core_Menu::get('civicrm/admin/setting/handbook')['title'] ?? NULL);

    $this->useLocale('de_DE');
    $items = array_map(static fn (string $title): array => ['title' => $title], $routes)
    + ['civicrm/other' => ['title' => 'Other']];
    $event = \Civi\Core\Event\GenericHookEvent::create(['items' => $items]);
    MenuTitles::onAlterMenu($event);
    $items = $event->items;
    $this->assertSame('Handbuch-Einstellungen', $items['civicrm/admin/setting/handbook']['title']);
    $this->assertSame('Handbuch durchsuchen', $items['civicrm/handbook/search']['title']);
    $this->assertSame('Other', $items['civicrm/other']['title'], 'other routes keep their title');
  }

}
