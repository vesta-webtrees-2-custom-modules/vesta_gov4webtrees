<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Cissee\Webtrees\Hook\HookInterfaces\EmptyIndividualFactsTabExtender;
use Cissee\Webtrees\Hook\HookInterfaces\IndividualFactsTabExtenderInterface;
use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsPrintGov;
use Cissee\WebtreesExt\AbstractModule;
use Cissee\WebtreesExt\FactPlaceAdditions;
use Cissee\WebtreesExt\Requests;
use DateTime;
use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Functions\Functions;
use Fisharebest\Webtrees\Http\Controllers\Admin\ModuleController;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionObject;
use Vesta\Hook\HookInterfaces\EmptyFunctionsPlace;
use Vesta\Hook\HookInterfaces\FunctionsPlaceInterface;
use Vesta\Hook\HookInterfaces\FunctionsPlaceUtils;
use Vesta\Hook\HookInterfaces\GovIdEditControlsInterface;
use Vesta\Model\GedcomDateInterval;
use Vesta\Model\GenericViewElement;
use Vesta\Model\GovReference;
use Vesta\Model\MapCoordinates;
use Vesta\Model\PlaceStructure;
use Vesta\Model\Trace;
use Vesta\VestaAdminController;
use Vesta\VestaModuleTrait;
use function app;
use function redirect;
use function response;
use function route;
use function view;

class Gov4WebtreesModule extends AbstractModule implements ModuleCustomInterface, ModuleConfigInterface, IndividualFactsTabExtenderInterface, FunctionsPlaceInterface, GovIdEditControlsInterface {
  
  //cannot use original AbstractModule because we override setPreference, setName
  
  use VestaModuleTrait;
  use Gov4WebtreesModuleTrait;
  use EmptyIndividualFactsTabExtender;
  use EmptyFunctionsPlace;

  protected $module_service;
  
  public function __construct(ModuleService $module_service) {
    $this->module_service = $module_service;
  }
  
  protected function onBoot(): void {
    //cannot do this in constructor: module name not set yet!    
    //migrate (we need the module name here to store the setting)
    $this->updateSchema('\Cissee\Webtrees\Module\Gov4Webtrees\Schema', 'SCHEMA_VERSION', 1);
  }
  
  public function customModuleAuthorName(): string {
    return 'Richard Cissée';
  }

  public function customModuleVersion(): string {
    return '2.0.1.1';
  }

  public function customModuleLatestVersionUrl(): string {
    return 'https://cissee.de';
  }

  public function customModuleSupportUrl(): string {
    return 'https://cissee.de';
  }

  public function description(): string {
    return $this->getShortDescription();
  }

  /**
   * Where does this module store its resources
   *
   * @return string
   */
  public function resourcesFolder(): string {
    return __DIR__ . '/resources/';
  }

  public function getHelpAction(ServerRequestInterface $request): ResponseInterface {
    $topic = Requests::getString($request, 'topic');
    return response(HelpTexts::helpText($topic));
  }
  
  //TODO adjust: use plac2gov?
  //HookInterface: FunctionsPlaceInterface
  public function hPlacesGetParentPlaces(PlaceStructure $place, $typesOfLocation, $recursively = false) {
    $useMedianDate = boolval($this->getPreference('USE_MEDIAN_DATE', '0'));
    $allowSettlements = boolval($this->getPreference('ALLOW_SETTLEMENTS', '1'));

    //$date = $place->getEventDateInterval();

    if ($useMedianDate) {
      $julianDay = $place->getEventDateInterval()->getMedian();
    } else {
      $julianDay = $place->getEventDateInterval()->getMin();
    }

    if ($julianDay === null) {
      return array();
    }

    $govId = FunctionsPrintGov::getGovId($place);
    if ($govId === null) {
      return array();
    }

    $placeStructures = array();

    $gov = FunctionsGov::retrieveGovObject($this, $govId);

    //load hierarchy (one per type of location)
    foreach ($typesOfLocation as $typeOfLocation) {
      $types = array();
      $typesFallback = array();

      if ("POLI" === $typeOfLocation) {
        $types = FunctionsGov::$TYPES_ADMINISTRATIVE;
        if ($allowSettlements) {
          $typesFallback = FunctionsGov::$TYPES_SETTLEMENT;
        }
      } else if ("RELI" === $typeOfLocation) {
        $types = FunctionsGov::$TYPES_RELIGIOUS;
      }
      //"GEOG", "CULT" not supported yet!

      $currentGov = $gov;
      $currentGovId = $govId;
      //TODO: use from/to rather than single julianDay?

      while ($currentGov !== null) {
        //next hierarchy level (if any)
        $nextGovId = null;
        if (sizeOf($types) > 0) {
          $nextGovId = FunctionsGov::findGovParentOfType($this, $currentGovId, $currentGov, $julianDay, $types, $gov->getVersion());
        }
        if (($govId === null) && (sizeOf($typesFallback) > 0)) {
          $nextGovId = FunctionsGov::findGovParentOfType($this, $currentGovId, $currentGov, $julianDay, $typesFallback, $gov->getVersion());
        }
        if ($nextGovId === null) {
          break;
        }
        $currentGovId = $nextGovId;
        $currentGov = FunctionsGov::retrieveGovObject($this, $currentGovId);

        if ($currentGov !== null) {
          //if we have a GOV-Id mapping, use that instead
          //(e.g. place name in GOV is "Verden St.Andreas", but we use "Verden (St. Andreas)")
          //TODO: this does not take into account GOV mappings elsewhere (e.g. via _GOV in shared place)!						
          $mappedName = FunctionsGov::getNameMappedToGovId($currentGovId);
          if ($mappedName !== null) {
            $placeStructures[] = new PlaceStructure("2 PLAC " . $mappedName, $place->getTree(), null, GedcomDateInterval::createEmpty());
          } else {
            //which label to use as place name here? we cannot decide = use all of them!
            foreach ($currentGov->getLabels() as $placeName) {
              $placeStructures[] = new PlaceStructure("2 PLAC " . $placeName->getProp(), $place->getTree(), null, GedcomDateInterval::createEmpty());
            }
          }
        }

        if (!$recursively) {
          break;
        }
      }
    }

    return $placeStructures;
  }

  public function assetsViaViews(): array {
    return [
        'css/style.css' => 'css/style',
        'css/webtrees.css' => 'css/webtrees',
        'css/minimal.css' => 'css/minimal'];
  }
  
  public function hFactsTabGetOutputBeforeTab(Individual $person) {
    /*
    //legacy
    //load script once
    //
    //TODO: not great because timestamp is added, preventing caching
    //(timestamp because this is loaded via jquery via ajax)
    //not sure this still applies for 2.x
    $pre = '<script src="' . $this->assetUrl('js/jquery-ui.js') . '"></script>';
    $pre .= '<script src="' . $this->assetUrl('js/widgets.js') . '"></script>';
    */
    $pre = '';
    
    //note: content actually served via style.phtml!
    $html = '<link href="' . $this->assetUrl('css/style.css') . '" type="text/css" rel="stylesheet" />';
    
    //note: content actually served via <theme>.phtml!
    $html .= '<link href="' . $this->assetUrl('css/'.$this->getThemeForCss().'.css') . '" type="text/css" rel="stylesheet" />';

    return new GenericViewElement($html, $pre);
  }

  protected function getThemeForCss(): string {
    //align with current theme (supporting - for now - the default webtrees themes)
    $themeName = Session::get('theme');
    if ('minimal' !== $themeName) {
      if ('fab' === $themeName) {
        //fab also uses font awesome icons
        $themeName = 'minimal';
      } else {
        //default
        $themeName = 'webtrees';
      }      
    }
    return $themeName;
  }
  
  //legacy
  /*
  public function hFactsTabGetOutputAfterTab(Individual $person) {
    //refresh (= initially show) all widgets (these are created via FunctionsPrintGov)!
    //(further optimization: could restrict to visible facts here, and refresh others on toggle of 'Events of close relatives')
    $post = '<script>' .
            '$(".govWidget").each(function() {' .
            '	$(this).gov("instance")._refresh();' .
            '});' .
            '</script>';

    return new GenericViewElement('', $post);
  }

  public function hFactsTabGetFormatPlaceAdditions(PlaceStructure $place) {
    $fpg = new FunctionsPrintGov($this);
    $gve = $fpg->getGovForFactPlace($place);
    $html = $gve->getMain();
    $script = $gve->getScript();
    $ll = $this->hPlacesGetLatLon($place);
    $tooltip = null;
    if ($ll) {
      $tooltip = 'via GOV';
    }

    return new FormatPlaceAdditions('', $ll, $tooltip, $html, '', $script);
  }

  public function getExpandAction(ServerRequestInterface $request): ResponseInterface {
    $switchToSlowAjax = Requests::getBool($request, 'switchToSlowAjax');
    if ($switchToSlowAjax) {
      //auto-adjust
      $this->setPreference('FAST_AJAX', '0');
    }
    
    //we can cache here (response is immutable for given version)!
    //$response->headers->set('Cache-Control', 'public,max-age=31536000,immutable');
    $expiry_date = Carbon::now()->addYears(10)->toDateTimeString();
    
    $response = response(AjaxRequests::expand($this, $request), 200, [
      'Expires' => $expiry_date,
    ]);
    
    return $response;
  }
  */

  public function setPreference(string $setting_name, string $setting_value): void {
    if ('RESET' === $setting_name) {
      if ('1' === $setting_value) {
        FunctionsGov::clear();
      }
    }

    parent::setPreference($setting_name, $setting_value);
  }

  ////////////////////////////////////////////////////////////////////////////////
  
  //GovIdEditControlsInterface
  
  public function govIdEditControl(
          ?string $govId, 
          string $id, 
          string $name, 
          string $placeName,
          bool $forModal,
          bool $withLabel): GenericViewElement {
    
    if (!boolval($this->getPreference('SUPPORT_EDITING_ELSEWHERE', '1'))) {
      return new GenericViewElement('', '');
    }
    
    $html = '';
    $html .= '<link href="' . $this->assetUrl('css/'.$this->getThemeForCss().'.css') . '" type="text/css" rel="stylesheet" />';
    $html .= view($this->name() . '::edit/gov-id-edit-control', [
            'moduleName' => $this->name(), 
            'withLabel' => $withLabel, 
            'id' => $id, 
            'name' => $name, 
            'placeName' => $placeName, 
            'internal' => false,
            'modal' => $forModal,
            'govId' => $govId]);
    
    $script = View::stack('javascript');
    
    return new GenericViewElement($html, $script);
  }
  
  ////////////////////////////////////////////////////////////////////////////////
  
  public function postSelect2GovIdAction(ServerRequestInterface $request): ResponseInterface {    
    $controller = new EditGovMappingController($this);
    return $controller->select2GovId($request);
  }
  
  public function getEditGovMappingAction(ServerRequestInterface $request): ResponseInterface {
    //'tree' is handled specifically in Router.php
    $tree = $request->getAttribute('tree');
    assert($tree instanceof Tree);
    
    $controller = new EditGovMappingController($this);
    return $controller->editGovMapping($request, $tree);
  }

  public function postEditGovMappingAction(ServerRequestInterface $request): ResponseInterface {
    $controller = new EditGovMappingController($this);
    return $controller->editGovMappingAction($request);
  }

  public function postReloadGovHierarchyAction(ServerRequestInterface $request): ResponseInterface {
    $placeName = Requests::getString($request, 'place-name');
    $govId = Requests::getString($request, 'gov-id');
    
    //reset in order to reload hierarchy
    FunctionsGov::deleteGovObject($govId);
    
    FlashMessages::addMessage(I18N::translate('GOV place hierarchy has been reloaded from GOV server for %1$s.', $placeName));
    
    //no need to return data
    return response();
  }
  
  ////////////////////////////////////////////////////////////////////////////////
  
  public function hFactsTabGetAdditionalEditControls(
          Fact $fact): GenericViewElement {
    
    $canEdit = false;
    if (boolval($this->getPreference('VISITORS_MAY_EDIT', '0'))) {
      $canEdit = true;
    } else {
      $canEdit = $fact->canEdit();
    }
    
    if (!$canEdit) {
      //not editable
      return new GenericViewElement('', '');
    }
    
    if ($fact->attribute('PLAC') === '') {
      //no PLAC, doesn't make sense to edit here
      return new GenericViewElement('', '');
    }
    
    $ps = PlaceStructure::create("2 PLAC " . $fact->place()->gedcomName(), $fact->record()->tree());
    
    $readonly = boolval($this->getPreference('NO_ONE_MAY_EDIT', '0'));
    if ($readonly) {
      $placerec = Functions::getSubRecord(2, '2 PLAC', $fact->gedcom());
      if (empty($placerec)) {
        //nothing to offer here
        return new GenericViewElement('', '');
      };
      
      //get a gov reference (may be provided by ourselves ultimately)
      $govReference = FunctionsPlaceUtils::plac2gov($this, $ps, false);
    
      if ($govReference === null) {
        //nothing to offer here
        return new GenericViewElement('', '');
      }
      
      //allow to reload the gov hierarchy
      $html = view($this->name() . '::edit/icon-fact-reload-gov', [        
        'moduleName' => $this->name(),
        'title' => I18N::translate('reload the GOV place hierarchy'),
        'route' => route('module', [
            'module' => $this->name(),
            'action' => 'ReloadGovHierarchy',
            'gov-id' => $govReference->getId(),
            'place-name' => $fact->place()->gedcomName()
        ])]);
    
      return new GenericViewElement($html, '');
    }
    
    //do not use plac2gov here - we're only interested in actual direct mappings at this point!
    $govId = FunctionsPrintGov::getGovId($ps);
    
    //ok to edit
    if ($govId === null) {
      $title = I18N::translate('Set GOV id (outside GEDCOM)');
    } else {
      $title = I18N::translate('Reset GOV id (outside GEDCOM) and reload the GOV place hierarchy');
    }
    
    $html = view($this->name() . '::edit/icon-fact-map-gov', [
        'fact' => $fact, 
        'moduleName' => $this->name(),
        'title' => $title]);
    
    return new GenericViewElement($html, '');
  }
  
  public function plac2Gov(PlaceStructure $ps): ?GovReference {
    //1. _GOV set directly?

    //previous versions of this module excluded newly created (not yet approved) places,
    //this seems unnecessary and has been discontinued
    
    //supposed to be under 2 PLAC, that's not checked here though!
    $govId = FunctionsPrintGov::getValue($ps->getGedcom(), 3, '_GOV');
    if ($govId !== null) {
      $trace = new Trace('GOV-Id via Gov4Webtrees module (_GOV tag)');
      return new GovReference($govId, $trace);
    }
    
    //2. id set via mapping table?
    $govId = FunctionsPrintGov::getGovId($ps);
    if ($govId !== null) {
      $trace = new Trace('GOV-Id via Gov4Webtrees module (mapping outside GEDCOM)');
      return new GovReference($govId, $trace);      
    }
    
    return null;
  }
  
  public function gov2map(GovReference $govReference): ?MapCoordinates {
    $gov = FunctionsGov::retrieveGovObject($this, $govReference->getId());
    if ($gov === null) {
      return null;
    }
    if ($gov->getLat() === null) {
      return null;
    }
    if ($gov->getLon() === null) {
      return null;
    }
    
    $trace = $govReference->getTrace();
    $trace->add('map coordinates via Gov4Webtrees module (data from GOV server)');
    return new MapCoordinates($gov->getLat(), $gov->getLon(), $trace);
  }
  
  public function factPlaceAdditions(PlaceStructure $place): ?FactPlaceAdditions {
    //get a gov reference (may be provided by ourselves ultimately)
    $govReference = FunctionsPlaceUtils::plac2gov($this, $place, false);
    
    if ($govReference === null) {
      return null;
    }
    
    $govId = $govReference->getId();
    
    $locale = I18N::locale();
        
    $compactDisplay = boolval($this->getPreference('COMPACT_DISPLAY', '1'));
    $showCurrentDateGov = intval($this->getPreference('SHOW_CURRENT_DATE', '0'));
    $allowSettlements = boolval($this->getPreference('ALLOW_SETTLEMENTS', '1'));
    $useMedianDate = boolval($this->getPreference('USE_MEDIAN_DATE', '0'));

    if ($useMedianDate) {
      $julianDay1 = $place->getEventDateInterval()->getMedian();
    } else {
      $julianDay1 = $place->getEventDateInterval()->getMin();
    }
    
    $dateTime = new DateTime();
    $dateTime->format('Y-m-d');
    $julianDay2 = cal_to_jd(CAL_GREGORIAN, $dateTime->format("m"), $dateTime->format("d"), $dateTime->format("Y"));

    $tooltip = null;
    $debugGovSource = $this->getPreference('DEBUG_GOV_SOURCE', '1');
    if ($debugGovSource) {
      $tooltip .= $govReference->getTrace()->getAll();
    }
    
    $str1 = GenericViewElement::createEmpty();
    if (($julianDay1) && ($showCurrentDateGov !== 2)) {
      $julianDayText = FunctionsPrintGov::gregorianYear($julianDay1);
      $str1 = $this->getHierarchy($compactDisplay, $allowSettlements, $locale->languageTag(), $julianDay1, $julianDayText, $govId, $tooltip);
    }
    $str2 = GenericViewElement::createEmpty();
    if (!$julianDay1 || ($showCurrentDateGov !== 0)) {
      $julianDayText = I18N::translate('today');
      $str2 = $this->getHierarchy($compactDisplay, $allowSettlements, $locale->languageTag(), $julianDay2, $julianDayText, $govId, $tooltip);
    }
    $gve = GenericViewElement::implode([$str1, $str2]);
    
    return new FactPlaceAdditions(GenericViewElement::createEmpty(), $gve, GenericViewElement::createEmpty());
  }
  
  protected function getHierarchy(
          bool $compactDisplay, 
          bool $allowSettlements, 
          string $locale,
          string $julianDay, 
          string $julianDayText, 
          string $id,
          ?string $tooltip): GenericViewElement {

    //initialize with placeholder
    $version = -1;
    
    $hierarchy = '';
    $hierarchy2 = '';
    
    $nextId = $id;
    while ($nextId !== null) {
      $data = $this->getDataAndNextId($allowSettlements, $locale, $julianDay, $nextId, $version);
      
      if ($data === []) {
        $nextId = null;
      } else {
        if ($hierarchy !== '') {
          $hierarchy .= ', ';
        }

        $hierarchy .= '<a href="http://gov.genealogy.net/item/show/' . $nextId . '" target="_blank" title="' . $data['type'] . ' ' . $data['label'] . '">';
        $hierarchy .= $data['label'];
        $hierarchy .= '</a>';
  
        if (!$compactDisplay) {
          if ($hierarchy2 !== '') {
            $hierarchy2 .= ', ';
          }
          $hierarchy2 .= $data['type'];
        }
        
        $nextId = $data['nextId'];
        
        if ($version === -1) {
          //replace placeholder version:
          //after a reset, object is reloaded from server, and now() is used as version (triggering reloading of parents)
          //otherwise, use version as set on initial object
          $version = $data['version'];
        }
      }
    }
    
    $span = '<div><span class="govText">';
    $span .= '<a href="http://gov.genealogy.net/" target="_blank">';
    //we'd like to use far fa-compass but we'd have to import explicitly
    //TODO: use proper modal here? tooltip isn't helpful on tablets etc
    if ($tooltip) {
      $span .= '<span class="wt-icon-map-gov" title="' . $tooltip . '"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
    } else {
      $span .= '<span class="wt-icon-map-gov"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
    }
    $span .= 'GOV</a> (';
    $span .= $julianDayText;
    $span .= '): ';
    $span .= $hierarchy;
    $span .= '</span>';

    if (!$compactDisplay) {
      $span .= '<div><span class="govText2">';
      $span .= '('.I18N::translate('Administrative levels').': ';
      $span .= $hierarchy2;
      $span .= ')</span>';
    }
    
    $span .= '</div>';

    //doesn't work "Error resolving module specifier"
    //and anyway seems too much effort just for adding another icon
    /*
    $script = 
      '<script type="module">' .
      'import { dom, library } from "@fortawesome/fontawesome-svg-core";' .
      'import {faCompass} from "@fortawesome/free-solid-svg-icons";' .
      'library.add(faCompass);' .
      'dom.watch();' .
      '</script>';
    */
    
    return GenericViewElement::create($span);    
  }    
  
  protected function getDataAndNextId(
          bool $allowSettlements, 
          string $locale,
          string $julianDay, 
          string $id, 
          int $version): array {
    
    $gov = FunctionsGov::retrieveGovObjectSnapshot($this, $julianDay, $id, $version, $locale);

    if ($gov == null) {
      //invalid id!
      return [];
    }
    
    //data and next id
    $type = FunctionsGov::retrieveTypeDescription($this, $gov->getType(), $locale);
    $label = $gov->getLabel();

    //next hierarchy level (if any)
    $nextId = FunctionsGov::findGovParentOfType($this, $id, $gov, $julianDay, FunctionsGov::$TYPES_ADMINISTRATIVE, $version);
    if ($allowSettlements && !$nextId) {
      $nextId = FunctionsGov::findGovParentOfType($this, $id, $gov, $julianDay, FunctionsGov::$TYPES_SETTLEMENT, $version);
    }
    
    return ['type' => $type, 'label' => $label, 'nextId' => $nextId, 'version' => $gov->getVersion()];
  }
  
  ////////////////////////////////////////////////////////////////////////////////
  
  private function title1(): string {
    return /* I18N: Module Configuration */I18N::translate('Gov4Webtrees Module Location Data Providers');
  }
  
  private function description1(): string {
    return /* I18N: Module Configuration */I18N::translate('Modules listed here are used (in the configured order) to determine GOV Ids of places.');
  }
  
  //hook management - generalize?
  //adapted from ModuleController (e.g. listFooters)
  public function getProvidersAction(): ResponseInterface {
    $modules = FunctionsPlaceUtils::modules($this, true);

    $controller = new VestaAdminController($this->name());
    return $controller->listHooks(
                    $modules,
                    FunctionsPlaceInterface::class,
                    $this->title1(),
                    $this->description1(),
                    true,
                    true);
  }

  public function postProvidersAction(ServerRequestInterface $request): ResponseInterface {
    $modules = FunctionsPlaceUtils::modules($this, true);

    $controller1 = new ModuleController($this->module_service, app(TreeService::class));
    $reflector = new ReflectionObject($controller1);

    //private!
    //$controller1->updateStatus($modules, $request);

    $method = $reflector->getMethod('updateStatus');
    $method->setAccessible(true);
    $method->invoke($controller1, $modules, $request);

    FunctionsPlaceUtils::updateOrder($this, $request);

    //private!
    //$controller1->updateAccessLevel($modules, FunctionsPlaceInterface::class, $request);

    $method = $reflector->getMethod('updateAccessLevel');
    $method->setAccessible(true);
    $method->invoke($controller1, $modules, FunctionsPlaceInterface::class, $request);

    $url = route('module', [
        'module' => $this->name(),
        'action' => 'Providers'
    ]);

    return redirect($url);
  }

  protected function editConfigBeforeFaq() {
    $modules = FunctionsPlaceUtils::modules($this, true);

    $url1 = route('module', [
        'module' => $this->name(),
        'action' => 'Providers'
    ]);

    //cf control-panel.phtml
    ?>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-9">
                <ul class="fa-ul">
                    <li>
                        <span class="fa-li"><?= view('icons/block') ?></span>
                        <a href="<?= e($url1) ?>">
                            <?= $this->title1() ?>
                        </a>
                        <?= view('components/badge', ['count' => $modules->count()]) ?>
                        <p class="small text-muted">
                          <?= $this->description1() ?>
                        </p>
                    </li>
                </ul>
            </div>
        </div>
    </div>		

    <?php
  }

}
