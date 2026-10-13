<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;
use CRM_Handbook_ExtensionUtil as E;
use Psr\Log\LogLevel;

/**
 * System status: flags missing Composer libraries.
 */
final class StatusCheck extends AutoSubscriber {

  /**
   * @return array<string, string>
   */
  public static function getSubscribedEvents(): array {
    return ['hook_civicrm_check' => 'onCheck'];
  }

  public static function onCheck(GenericHookEvent $event): void {
    if (Dependencies::available()) {
      return;
    }
    /** @var list<\CRM_Utils_Check_Message> $messages */
    $messages = &$event->messages;
    $messages[] = new \CRM_Utils_Check_Message(
      'handbook_dependencies',
      Dependencies::message(),
      E::ts('Handbook: libraries missing'),
      LogLevel::ERROR,
      'fa-book',
    );
  }

}
