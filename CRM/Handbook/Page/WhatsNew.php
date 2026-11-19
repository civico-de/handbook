<?php

declare(strict_types = 1);

use Civi\Handbook\Dependencies;
use Civi\Handbook\Library;
use Civi\Handbook\RenderCache;
use Civi\Handbook\View;
use Civi\Handbook\WhatsNew;
use CRM_Handbook_ExtensionUtil as E;

/**
 * civicrm/handbook/whats-new: the newest release of every extension with a changelog.
 */
class CRM_Handbook_Page_WhatsNew extends CRM_Core_Page {

  public function run(): void {
    CRM_Utils_System::setTitle(E::ts("What's new"));
    if (!Dependencies::available()) {
      $this->assign('handbookFatal', Dependencies::message());
      parent::run();
      return;
    }
    $library = Library::fromSystem();
    (new View($library))->assignChrome($this, '');
    $this->assign('handbookReleases', (new WhatsNew($library, RenderCache::fromSystem()))->entries());
    parent::run();
  }

  public function getTemplateFileName(): string {
    return Dependencies::available() ? parent::getTemplateFileName() : 'CRM/Handbook/Missing.tpl';
  }

}
