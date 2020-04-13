<?php

declare(strict_types=1);

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Cissee\WebtreesExt\Requests;
use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\Controllers\AbstractEditController;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Vesta\Model\PlaceStructure;
use function response;
use function view;

//cf EditRepositoryController
class EditGovMappingController extends AbstractEditController {

  protected $module;

  public function __construct($module) {
    $this->module = $module;
  }

  /**
   * Show a form to create or update a gov mapping.
   *
   * @return Response
   */
  public function editGovMapping(ServerRequestInterface $request, Tree $tree): ResponseInterface {
    //set delay here to mimic slow servers and to test whether select2 is properly initialized (issue #9)
    //sleep(2);
    
    $placeName = Requests::getString($request, 'place-name');
    $ps = PlaceStructure::create("2 PLAC " . $placeName, $tree);
    
    //do not use plac2gov here - we're only interested in actual direct mappings at this point!
    $govId = FunctionsPrintGov::getGovId($ps);
    
    //'cleanup' use case (multiple GOV ids mapped): handled silently now
    //should we address this explicitly? E.g. show warning icon next to edit control?
    if ($govId === null) {
      $title = I18N::translate('Set GOV id for %1$s', $placeName);
    } else {
      $title = I18N::translate('Reset GOV id for %1$s', $placeName);
    }
    
    $html = view($this->module->name() . '::modals/edit-gov-mapping', [
                'moduleName' => $this->module->name(),
                'placeName' => $placeName,
                'title' => $title,
                'govId' => $govId,
    ]);
            
    return response($html);
  }

  /**
   * Process a form to create or update a gov mapping.
   *
   * @param Request $request
   * @param Tree    $tree
   *
   * @return JsonResponse
   */
  public function editGovMappingAction(ServerRequestInterface $request): ResponseInterface {
    $placeName = Requests::getString($request, 'place-name');
    $govId = Requests::getString($request, 'gov-id');

    if ($govId === '') {
      FunctionsGov::deleteGovId($placeName);
      FlashMessages::addMessage(I18N::translate('GOV id for %1$s has been removed.', $placeName));

      //no need to return data, we'll just close the modal from which this has been called
      return response();
    }
    
    //test whether id is valid
    $gov = FunctionsGov::loadGovObject($this->module, $govId);
    
    //unexpected to occur anymore now that we validate via select2GovId (where the same I18N string is used)
    if ($gov == null) {
      $error = I18N::translate('Invalid GOV id! Valid GOV ids are e.g. \'EITTZE_W3091\', \'object_1086218\'.');
      
      //try again
      return response(['html' => $error], StatusCodeInterface::STATUS_CONFLICT);
    }
    
    //reset in order to reload hierarchy
    FunctionsGov::deleteGovObject($govId);
    
    FunctionsGov::setGovId($placeName, $govId);
    
    FlashMessages::addMessage(I18N::translate('GOV id for %1$s has been set to %2$s.', $placeName, $govId));
    
    //no need to return data, we'll just close the modal from which this has been called
    return response();
  }

  public function select2GovId(ServerRequestInterface $request): ResponseInterface {
      //$page  = (int) ($request->getParsedBody()['page'] ?? 1);
      $govId = $request->getParsedBody()['q'] ?? '';
      
      $ret = FunctionsGov::checkGovId($this->module, $govId);

      $results = ($ret !== null)?collect([[
                    'id'    => $ret,
                    'text'  => $ret,
                    'title' => ' ',
                ]]):collect([]);

      return response([
          'results'    => $results,
          'pagination' => [
              'more' => false,
          ],
      ]);
  }
}
