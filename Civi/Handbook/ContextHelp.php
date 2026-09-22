<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;
use CRM_Handbook_ExtensionUtil as E;

/**
 * Declares the permission and links the pages whose frontmatter `routes`
 * name the current path above the page content.
 */
final class ContextHelp extends AutoSubscriber {

  public const PERMISSION = 'access handbook';

  /**
   * @return array<string, string>
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_permission' => 'onPermission',
      'hook_civicrm_pageRun' => 'onPageRun',
    ];
  }

  public static function onPermission(GenericHookEvent $event): void {
    /** @var array<string, array<string, mixed>> $permissions */
    $permissions = &$event->permissions;
    $permissions['access handbook'] = [
      'label' => E::ts('Handbuch: Anleitungen lesen'),
      'description' => E::ts('Das Handbuch mit den Bedienungsanleitungen öffnen und durchsuchen.'),
    ];
  }

  public static function onPageRun(GenericHookEvent $event): void {
    // pageRun fires for every page and snippet of a request; link once.
    if (isset(\Civi::$statics[self::class]) || !\CRM_Core_Permission::check(self::PERMISSION)) {
      return;
    }
    $path = \CRM_Utils_System::currentPath();
    if ($path === NULL || str_starts_with($path, 'civicrm/handbook')) {
      return;
    }
    $markup = self::markup(Library::fromSystem()->forRoute($path));
    if ($markup === NULL) {
      return;
    }
    \Civi::$statics[self::class] = TRUE;
    \Civi::resources()->addStyleFile(E::LONG_NAME, 'css/handbook.css');
    \CRM_Core_Region::instance('page-body')->add(['markup' => $markup, 'weight' => -100]);
  }

  /**
   * @param list<\Civi\Handbook\Page> $pages
   */
  public static function markup(array $pages): ?string {
    if ($pages === []) {
      return NULL;
    }
    $items = '';
    foreach ($pages as $page) {
      $url = \CRM_Utils_System::url('civicrm/handbook', ['p' => $page->id]);
      $label = E::ts('Handbuch: %1', [1 => $page->title]);
      $items .= '<li><a href="' . $url . '">' . htmlspecialchars($label) . '</a></li>';
    }
    return (
      '<nav class="handbook-context" aria-label="'
      . htmlspecialchars(E::ts('Anleitungen zu dieser Seite'))
      . '"><ul>'
      . $items
      . '</ul></nav>'
    );
  }

}
