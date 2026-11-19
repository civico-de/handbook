<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;
use CRM_Handbook_ExtensionUtil as E;
use Psr\Log\LogLevel;

/**
 * System status: flags missing Composer libraries, and names extensions
 * updated recently with a link to their changes.
 */
final class StatusCheck extends AutoSubscriber {

  /**
   * @return array<string, string>
   */
  public static function getSubscribedEvents(): array {
    return ['hook_civicrm_check' => 'onCheck'];
  }

  public static function onCheck(GenericHookEvent $event): void {
    /** @var list<\CRM_Utils_Check_Message> $messages */
    $messages = &$event->messages;
    /** @var list<string> $names */
    $names = $event->statusNames ?? [];
    $wanted = static fn (string $name): bool => $names === [] || in_array($name, $names, TRUE);
    if (Dependencies::available()) {
      $updated = $wanted('handbook_updates') ? Updates::fromSystem() : [];
      if ($updated !== []) {
        $messages[] = self::updatesMessage($updated);
      }
      return;
    }
    if (!$wanted('handbook_dependencies')) {
      return;
    }
    $messages[] = new \CRM_Utils_Check_Message(
      'handbook_dependencies',
      Dependencies::message(),
      E::ts('Handbook: libraries missing'),
      LogLevel::ERROR,
      'fa-book',
    );
  }

  /**
   * @param list<string> $keys
   */
  private static function updatesMessage(array $keys): \CRM_Utils_Check_Message {
    $library = Library::fromSystem();
    $labels = array_map(
      static fn (string $key): string => htmlspecialchars(Ordering::sourceName(
        $library->pages(),
        $library->sources(),
        $key,
      )),
      $keys,
    );
    $url = htmlspecialchars(View::defaultUrl('civicrm/handbook/whats-new', []));
    return new \CRM_Utils_Check_Message(
      'handbook_updates',
      E::ts('Updated recently: %1. <a href="%2">See what changed</a>.', [
        1 => implode(', ', $labels),
        2 => $url,
      ]),
      E::ts('Handbook: extensions updated'),
      LogLevel::INFO,
      'fa-book',
    );
  }

}
