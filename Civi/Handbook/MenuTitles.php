<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;
use CRM_Handbook_ExtensionUtil as E;

/**
 * Translates the route titles of xml/Menu/handbook.xml when the menu is rebuilt:
 * core translates stored menu titles in its own domain only.
 */
final class MenuTitles extends AutoSubscriber {

  /**
   * @return array<string, string>
   */
  public static function getSubscribedEvents(): array {
    return ['hook_civicrm_alterMenu' => 'onAlterMenu'];
  }

  /**
   * Route titles, as in xml/Menu/handbook.xml, in the current locale.
   *
   * @return array<string, string>
   */
  public static function titles(): array {
    return [
      'civicrm/handbook' => E::ts('Handbook'),
      'civicrm/handbook/search' => E::ts('Search the handbook'),
      'civicrm/handbook/asset' => E::ts('Handbook image'),
      'civicrm/admin/setting/handbook' => E::ts('Handbook settings'),
    ];
  }

  public static function onAlterMenu(GenericHookEvent $event): void {
    /** @var array<string, array<string, mixed>> $items */
    $items = &$event->items;
    foreach (self::titles() as $path => $title) {
      if (!isset($items[$path])) {
        continue;
      }
      $items[$path]['title'] = $title;
    }
  }

}
