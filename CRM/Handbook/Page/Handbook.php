<?php

declare(strict_types = 1);

use Civi\Handbook\Dependencies;
use Civi\Handbook\HandbookException;
use Civi\Handbook\Library;
use Civi\Handbook\Renderer;
use Civi\Handbook\View;
use CRM_Handbook_ExtensionUtil as E;

/**
 * civicrm/handbook: the index, or one page with ?p=<source>/<slug>.
 */
class CRM_Handbook_Page_Handbook extends CRM_Core_Page {

  public function run(): void {
    if (!Dependencies::available()) {
      $this->assign('handbookFatal', Dependencies::message());
      CRM_Utils_System::setTitle(E::ts('Handbuch'));
      parent::run();
      return;
    }
    $id = View::param('p');
    $library = Library::fromSystem();
    $page = NULL;
    $body = '';
    try {
      $page = $id === '' ? NULL : $library->find($id);
      $body = $page === NULL ? '' : $library->body($page);
    }
    catch (HandbookException $e) {
      $page = NULL;
      CRM_Core_Session::setStatus(E::ts('Diese Handbuchseite gibt es nicht.'), '', 'error');
    }
    (new View($library))->assignChrome($this, '', $page?->id);
    $this->assign('handbookPage', $page === NULL ? NULL : ['id' => $page->id, 'title' => $page->title]);
    CRM_Utils_System::setTitle($page->title ?? E::ts('Handbuch'));
    if ($page !== NULL) {
      $rendered = (new Renderer())->render($page->source(), $body);
      $this->assign('handbookHtml', $rendered['html']);
      $this->assign(
        'handbookSections',
        array_values(array_filter(
          $rendered['headings'],
          static fn (array $h): bool => $h['level'] >= 2 && $h['level'] <= 3,
        )),
      );
    }
    parent::run();
  }

  public function getTemplateFileName(): string {
    return Dependencies::available() ? parent::getTemplateFileName() : 'CRM/Handbook/Missing.tpl';
  }

}
