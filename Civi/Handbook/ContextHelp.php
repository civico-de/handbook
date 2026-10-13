<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;
use CRM_Handbook_ExtensionUtil as E;

/**
 * Declares the permission and links the pages whose frontmatter `routes`
 * name the current path above the content of pages and forms.
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
      'hook_civicrm_buildForm' => 'onBuildForm',
    ];
  }

  public static function onPermission(GenericHookEvent $event): void {
    /** @var array<string, array<string, mixed>> $permissions */
    $permissions = &$event->permissions;
    $permissions['access handbook'] = [
      'label' => E::ts('Handbook: read manuals'),
      'description' => E::ts('Open and search the handbook of user manuals.'),
    ];
  }

  public static function onPageRun(GenericHookEvent $event): void {
    self::link();
  }

  public static function onBuildForm(GenericHookEvent $event): void {
    self::link();
  }

  /**
   * Links the pages whose routes name the current path, once per request:
   * pageRun and buildForm fire for every page, form and snippet.
   */
  private static function link(): void {
    if (
      isset(\Civi::$statics[self::class])
      || !Dependencies::available()
      || !\CRM_Core_Permission::check(self::PERMISSION)
    ) {
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
   * @param (\Closure(string, array<string, string>): string)|null $url
   *   Builds an unescaped Civi URL.
   */
  public static function markup(array $pages, ?\Closure $url = NULL): ?string {
    $url ??= View::defaultUrl(...);
    if ($pages === []) {
      return NULL;
    }
    $items = '';
    foreach ($pages as $page) {
      $label = E::ts('Handbook: %1', [1 => $page->title]);
      $items .=
        '<li><a href="'
        . htmlspecialchars($url('civicrm/handbook', ['p' => $page->id]))
        . '">'
        . htmlspecialchars($label)
        . '</a></li>';
    }
    return (
      '<nav class="handbook-context" aria-label="'
      . htmlspecialchars(E::ts('Manuals for this page'))
      . '"><ul>'
      . $items
      . '</ul></nav>'
    );
  }

}
