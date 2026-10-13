<?php

declare(strict_types = 1);

use Civi\Handbook\Dependencies;
use Civi\Handbook\Library;
use Civi\Handbook\RenderCache;
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
      CRM_Utils_System::setTitle(E::ts('Handbook'));
      parent::run();
      return;
    }
    $term = View::param('term');
    $library = Library::fromSystem();
    $view = new View($library);
    $view->assignChrome($this, $term);
    CRM_Utils_System::setTitle(E::ts('Search the handbook'));
    $hits = (new Search($library, RenderCache::fromSystem()))->run($term);
    $this->assign('handbookResults', $view->results($hits));
    $this->assign('handbookSectionCount', count($hits));
    parent::run();
  }

  public function getTemplateFileName(): string {
    return Dependencies::available() ? parent::getTemplateFileName() : 'CRM/Handbook/Missing.tpl';
  }

}
