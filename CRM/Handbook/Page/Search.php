<?php

declare(strict_types = 1);

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
    $term = View::param('term');
    $library = Library::fromSystem();
    (new View($library))->assignChrome($this, $term);
    CRM_Utils_System::setTitle(E::ts('Handbuch durchsuchen'));
    $results = [];
    foreach ((new Search($library, new Renderer()))->run($term) as $hit) {
      $results[] = [
        'title' => $hit['page']->title,
        'heading' => $hit['heading'],
        'context' => $hit['context'],
        'url' => CRM_Utils_System::url('civicrm/handbook', ['p' => $hit['page']->id], FALSE, $hit['anchor']),
      ];
    }
    $this->assign('handbookResults', $results);
    parent::run();
  }

}
