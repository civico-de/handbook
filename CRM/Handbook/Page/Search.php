<?php

declare(strict_types = 1);

use Civi\Handbook\Dependencies;
use Civi\Handbook\Library;
use Civi\Handbook\Renderer;
use Civi\Handbook\Search;
use Civi\Handbook\View;
use CRM_Handbook_ExtensionUtil as E;

/**
 * civicrm/handbook/search?term=…
 */
class CRM_Handbook_Page_Search extends CRM_Core_Page {

  public function run(): void {
    if (!Dependencies::available()) {
      $this->assign('handbookFatal', Dependencies::message());
      CRM_Utils_System::setTitle(E::ts('Handbuch'));
      parent::run();
      return;
    }
    $term = View::param('term');
    $library = Library::fromSystem();
    (new View($library))->assignChrome($this, $term);
    CRM_Utils_System::setTitle(E::ts('Handbuch durchsuchen'));
    $results = (new View($library))->results((new Search($library, new Renderer()))->run($term));
    $this->assign('handbookResults', $results);
    parent::run();
  }

  public function getTemplateFileName(): string {
    return Dependencies::available() ? parent::getTemplateFileName() : 'CRM/Handbook/Missing.tpl';
  }

}
