<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Aura\Router\RouterContainer;
use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovData;
use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovDataDelete;
use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovDataEdit;
use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovDataList;
use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovDataSave;
use Cissee\WebtreesExt\AbstractModule;
use Cissee\WebtreesExt\Http\RequestHandlers\FunctionsPlaceProvidersAction;
use Cissee\WebtreesExt\Module\ModuleExtGlobalInterface;
use Cissee\WebtreesExt\Module\ModuleExtGlobalTrait;
use Cissee\WebtreesExt\Module\ModuleMetaInterface;
use Cissee\WebtreesExt\Module\ModuleMetaTrait;
use Cissee\WebtreesExt\MoreI18N;
use Cissee\WebtreesExt\Requests;
use DateTime;
use Fisharebest\ExtCalendar\GregorianCalendar;
use Fisharebest\Localization\Locale\LocaleInterface;
use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Functions\Functions;
use Fisharebest\Webtrees\Http\Middleware\AuthAdministrator;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Services\SearchService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vesta\CommonI18N;
use Vesta\Hook\HookInterfaces\EmptyFunctionsPlace;
use Vesta\Hook\HookInterfaces\EmptyIndividualFactsTabExtender;
use Vesta\Hook\HookInterfaces\EmptyPrintFunctionsPlace;
use Vesta\Hook\HookInterfaces\FunctionsPlaceInterface;
use Vesta\Hook\HookInterfaces\FunctionsPlaceUtils;
use Vesta\Hook\HookInterfaces\GovIdEditControlsInterface;
use Vesta\Hook\HookInterfaces\IndividualFactsTabExtenderInterface;
use Vesta\Hook\HookInterfaces\PrintFunctionsPlaceInterface;
use Vesta\Model\GedcomDateInterval;
use Vesta\Model\GenericViewElement;
use Vesta\Model\GovReference;
use Vesta\Model\LocReference;
use Vesta\Model\MapCoordinates;
use Vesta\Model\PlaceStructure;
use Vesta\Model\Trace;
use Vesta\VestaAdminController;
use Vesta\VestaModuleTrait;
use const CAL_GREGORIAN;
use function app;
use function cal_to_jd;
use function response;
use function route;
use function view;

class Gov4WebtreesModule extends AbstractModule implements 
  ModuleCustomInterface, 
  ModuleMetaInterface, 
  ModuleConfigInterface, 
  ModuleExtGlobalInterface,
  IndividualFactsTabExtenderInterface, 
  FunctionsPlaceInterface, 
  PrintFunctionsPlaceInterface, 
  GovIdEditControlsInterface {
  
  //cannot use original AbstractModule because we override setPreference, setName
  use ModuleCustomTrait, ModuleMetaTrait, ModuleConfigTrait, ModuleExtGlobalTrait, VestaModuleTrait {
    VestaModuleTrait::customTranslations insteadof ModuleCustomTrait;
    VestaModuleTrait::getAssetAction insteadof ModuleCustomTrait;
    VestaModuleTrait::assetUrl insteadof ModuleCustomTrait;    
    VestaModuleTrait::getConfigLink insteadof ModuleConfigTrait;
    ModuleMetaTrait::customModuleVersion insteadof ModuleCustomTrait;
    ModuleMetaTrait::customModuleLatestVersion insteadof ModuleCustomTrait;
  }
  
  use Gov4WebtreesModuleTrait;
  use EmptyIndividualFactsTabExtender;
  use EmptyFunctionsPlace;
  use EmptyPrintFunctionsPlace;

  protected $module_service;
  protected $search_service;
          
  public function __construct(ModuleService $module_service, SearchService $search_service) {
    $this->module_service = $module_service;
    $this->search_service = $search_service;
  }
  
  protected function onBoot(): void {
    //cannot do this in constructor: module name not set yet!    
    //migrate (we need the module name here to store the setting)
    $this->updateSchema('\Cissee\Webtrees\Module\Gov4Webtrees\Schema', 'SCHEMA_VERSION', 3);
    
    $this->flashWhatsNew('\Cissee\Webtrees\Module\Gov4Webtrees\WhatsNew', 5);
    
    $router_container = app(RouterContainer::class);
    assert($router_container instanceof RouterContainer);
    $router = $router_container->getMap();
    
    //http://localhost/dev/webtrees_releases/webtrees/admin/gov-data/object_156114
    
    $router->get(GovDataList::class, '/admin/gov-data', new GovDataList($this))
            ->extras(['middleware' => [AuthAdministrator::class]]);
    
    $router->get(GovData::class, '/admin/gov-data/{gov_id}', new GovData($this))
            ->extras(['middleware' => [AuthAdministrator::class]]);
    
    $router->get(GovDataEdit::class, '/admin/gov-data-edit/{type}/{key}', new GovDataEdit($this))
            ->extras(['middleware' => [AuthAdministrator::class]]);
    
    $router->post(GovDataSave::class, '/admin/gov-data-update', GovDataSave::class)
            ->extras(['middleware' => [AuthAdministrator::class]]);
    
    $router->post(GovDataDelete::class, '/admin/gov-data-delete/{type}/{key}', GovDataDelete::class)
            ->extras(['middleware' => [AuthAdministrator::class]]);
  }
   
  public function flashGovServerUnavailable() {
    //ongoing - error handling in case GOV server is unavailable
    //problematic: flash message aren't thead-safe, see webtrees issue #3138
    //but we can live with the current fix
    $messages = Session::get('flash_messages', []);
    if (empty($messages)) {      
      FlashMessages::addMessage(I18N::translate("The GOV server seems to be temporarily unavailable."));
    }
  }
  
  public function customModuleAuthorName(): string {
    return 'Richard Cissée';
  }

  public function customModuleMetaDatasJson(): string {
    return file_get_contents(__DIR__ . '/metadata.json');
  } 
  
  public function customModuleLatestMetaDatasJsonUrl(): string {
    return 'https://raw.githubusercontent.com/vesta-webtrees-2-custom-modules/vesta_gov4webtrees/master/metadata.json';
  }

  public function customModuleSupportUrl(): string {
    return 'https://cissee.de';
  }

  public function resourcesFolder(): string {
    return __DIR__ . '/resources/';
  }

  public function headContentOnAdminPage(): string {
    return $this->headContent();
  }
  
  public function headContent(): string {
    //easier to serve this globally, even if not strictly required on each page
    //(but required e.g. for pages where gov2html is shown)
    
    $html = '<link href="' . $this->assetUrl('css/style.css') . '" type="text/css" rel="stylesheet" />';    
    $html .= '<link href="' . $this->assetUrl('css/'.$this->getThemeForCss().'.css') . '" type="text/css" rel="stylesheet" />';

    return $html;
  }
    
  public function getHelpAction(ServerRequestInterface $request): ResponseInterface {
    $topic = Requests::getString($request, 'topic');
    return response(HelpTexts::helpText($topic));
  }

  //no longer required - css is static now
  //public function assetsViaViews(): array {
  //  return [
  //      'css/style.css' => 'css/style',
  //      'css/webtrees.css' => 'css/webtrees',
  //      'css/minimal.css' => 'css/minimal'];
  //}

  protected function getThemeForCss(): string {
    //align with current theme (supporting the default webtrees themes, and specific custom themes)
    $themeName = Session::get('theme');
    if ('minimal' !== $themeName) {
      if ('fab' === $themeName) {
        //fab also uses font awesome icons
        $themeName = 'minimal';
      } else if ('_myartjaub_ruraltheme_' === $themeName) {
        //and the custom 'rural' theme
        $themeName = 'minimal';
      } else if ('_jc-theme-justlight_' === $themeName) {
        //and the custom 'JustLight' theme
        $themeName = 'minimal';
      } else {
        //default
        $themeName = 'webtrees';
      }      
    }
    return $themeName;
  }

  public function setPreference(string $setting_name, string $setting_value): void {
    if ('RESET' === $setting_name) {
      if ('1' === $setting_value) {
        FunctionsGov::clear();
        
        //we didn't mean to store this preference at all,
        //but we accidentally did. Safer to reset always, that's anyway the intended behavior
        $setting_value = '0';
      }
    }

    parent::setPreference($setting_name, $setting_value);
  }

  ////////////////////////////////////////////////////////////////////////////////
  
  //GovIdEditControlsInterface
  
  public function govIdEditControlSelect2ScriptSnippet(): string {
    return $this->govIdEditControlSelect2ScriptSnippetInternal(true);
  }
  
  public function govIdEditControlSelect2ScriptSnippetInternal(bool $withinModal): string {
    $html = view($this->name() . '::script/select2-initializer-gov', [
        'withinModal' => $withinModal]);
    
    return $html;
  }
  
  public function govIdEditControl(
          ?string $govId, 
          string $id, 
          string $name, 
          string $placeName,
          ?string $placeNameInputSelector,
          bool $forModal,
          bool $withLabel): GenericViewElement {
    
    if (!boolval($this->getPreference('SUPPORT_EDITING_ELSEWHERE', '1'))) {
      return GenericViewElement::createEmpty();
    }
    
    $html = '';
    
    //now loaded globally
    //$html .= '<link href="' . $this->assetUrl('css/'.$this->getThemeForCss().'.css') . '" type="text/css" rel="stylesheet" />';
    
    $html .= view($this->name() . '::edit/gov-id-edit-control', [
            'moduleName' => $this->name(), 
            'withLabel' => $withLabel, 
            'id' => $id, 
            'name' => $name, 
            'placeName' => $placeName, 
            'placeNameInputSelector' => $placeNameInputSelector, 
            'internal' => false,
            'select2Initializer' => $forModal?null:$this->govIdEditControlSelect2ScriptSnippetInternal(false),
            'govId' => $govId]);
    
    $script = View::stack('javascript');
    
    return new GenericViewElement($html, $script);
  }
  
  public function govTypeIdEditControl(
          ?string $govTypeId, 
          string $id, 
          string $name): GenericViewElement {
    
    $locale = I18N::locale();
    $govTypeIdsByTypeGroup = FunctionsGov::getGovTypeIdsByTypeGroup($this, $locale);
    
    $html = view($this->name() . '::edit/gov-type-id-edit-control', [
            'moduleName' => $this->name(), 
            'id' => $id, 
            'name' => $name, 
            'govTypeIdsByTypeGroup' => $govTypeIdsByTypeGroup,
            'govTypeId' => $govTypeId]);
    
    $script = View::stack('javascript');
    
    return new GenericViewElement($html, $script);
  }
  
  public function govLanguageEditControl(
          ?string $govLanguage, 
          string $id, 
          string $name): GenericViewElement {
    
    $locale = I18N::locale();
    $govLanguages = FunctionsGov::getGovLanguages();
    
    $html = view($this->name() . '::edit/gov-language-edit-control', [
            'moduleName' => $this->name(), 
            'id' => $id, 
            'name' => $name, 
            'govLanguages' => $govLanguages,
            'govLanguage' => $govLanguage]);
    
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
    
    if ($placeName !== '') {
      FlashMessages::addMessage(I18N::translate('GOV place hierarchy for %1$s has been reloaded from GOV server.', $placeName));
    } else {
      FlashMessages::addMessage(I18N::translate('GOV place hierarchy has been reloaded from GOV server.'));
    }    
    
    //no need to return data
    return response();
  }
  
  ////////////////////////////////////////////////////////////////////////////////
  
  public function hFactsTabRequiresModalVesta(Tree $tree): ?string {
    //add the vesta modal placeholder, with custom select2 snippet
    $script = $this->govIdEditControlSelect2ScriptSnippet();
    return $script;
  }
  
  //now handled by the vesta_personal_facts module itself!
  /*
  public function hFactsTabGetOutputBeforeTab(Individual $person) {
    //add the vesta modal placeholder, with custom select2 snippet
    $script = $this->govIdEditControlSelect2ScriptSnippet();
    
    $html = view(VestaAdminController::vestaViewsNamespace() . '::modals/ajax-modal-vesta', [
                'ajax' => true, //tab is loaded via ajax!
                'select2Initializers' => [$script]
    ]);
    
    return GenericViewElement::create($html);
  }
  */
  
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
      return GenericViewElement::createEmpty();
    }    
    
    if ($fact->getTag() === '_GOV') {
      //direct gov tag (on place or shared place)
      
      $govReference = new GovReference($fact->value(), new Trace(""));
      
      //allow to reload the gov hierarchy
      $html = view($this->name() . '::edit/icon-fact-reload-gov', [        
        'moduleName' => $this->name(),
        'title' => I18N::translate('reload the GOV place hierarchy'),
        'route' => route('module', [
            'module' => $this->name(),
            'action' => 'ReloadGovHierarchy',
            'gov-id' => $govReference->getId(),
            'place-name' => ""
        ])]);
    
      return GenericViewElement::create($html);
    }
    
    $ps = PlaceStructure::fromFact($fact);
    if ($ps === null) {
      //no PLAC (or empty), doesn't make sense to edit here
      return GenericViewElement::createEmpty();
    }
    
    $readonly = boolval($this->getPreference('NO_ONE_MAY_EDIT', '0')) && 
            !boolval($this->getPreference('VISITORS_MAY_EDIT', '0'));
    
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
        return GenericViewElement::createEmpty();
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
    
      return GenericViewElement::create($html);
    }
    
    //do not use plac2gov here - we're only interested in actual direct mappings at this point!
    $govId = Gov4WebtreesModule::plac2govViaMappingTable($ps);
    
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
     
    return GenericViewElement::create($html);
  }
    
  ////////////////////////////////////////////////////////////////////////////////
  
  public static function plac2govViaMappingTable(PlaceStructure $place): ?string {
    $name = $place->getPlace()->placeName();
    $fullName = $place->getPlace()->gedcomName();

    $placeId = $place->getPlace()->id();

    //this occurs in case of new place names (respective change not approved yet) 
    if (!$placeId) {
      return null;
    }

    if (!$name) {
      return null;
    }

    return FunctionsGov::getGovId($fullName);
  }

  //see Date.php
  //we're not supposed to use that in webtrees, nevermind
  //we don't necessarily want the median year, so we have to use our own function 
  public static function gregorianYear($julianDay) {
    $gregorian_calendar = new GregorianCalendar;
    list($year) = $gregorian_calendar->jdToYmd($julianDay);
    return $year;
  }
  
  public function plac2govSupported(): bool {
    return true;
  }
  
  public function plac2gov(PlaceStructure $ps): ?GovReference {
    //1. _GOV set directly?    
    $govId = $ps->getGov();
    if ($govId !== null) {
      $trace = new Trace('GOV-Id via Gov4Webtrees module (_GOV tag)');
      return new GovReference($govId, $trace, $ps->getLevel());
    }
    
    //2. id set via mapping table?
    $govId = Gov4WebtreesModule::plac2govViaMappingTable($ps);
    if ($govId !== null) {
      $trace = new Trace('GOV-Id via Gov4Webtrees module (mapping outside GEDCOM)');
      return new GovReference($govId, $trace, $ps->getLevel());      
    }
    
    return null;
  }
  
  public function gov2map(GovReference $govReference): ?MapCoordinates {
    try {
      $gov = FunctionsGov::retrieveGovObject($this, $govReference->getId());
    } catch (GOVServerUnavailableException $ex) {
      $gov = null;
      $this->flashGovServerUnavailable();
    } 
    
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
  
  public function gov2html(GovReference $govReference, Tree $tree): ?GenericViewElement {
    $locale = I18N::locale();
        
    $compactDisplay = boolval($this->getPreference('COMPACT_DISPLAY', '1'));
    $withInternalLinks = intval($this->getPreference('DISPLAY_INTERNAL_LINKS', '1'));
    $showSettlements = boolval($this->getPreference('ALLOW_SETTLEMENTS', '1'));
    $showOrganizational = boolval($this->getPreference('ALLOW_ORGANIZATIONAL', '1'));

    $dateTime = new DateTime();
    $dateTime->format('Y-m-d');
    $julianDay2 = cal_to_jd(CAL_GREGORIAN, $dateTime->format("m"), $dateTime->format("d"), $dateTime->format("Y"));

    $tooltip = null;
    $debugGovSource = $this->getPreference('DEBUG_GOV_SOURCE', '1');
    if ($debugGovSource) {
      $tooltip .= $govReference->getTrace()->getAll();
    }
    
    $str2 = GenericViewElement::createEmpty();
    $julianDayText = I18N::translate('today');
    $str2 = $this->getHierarchy(
            $compactDisplay, 
            $withInternalLinks, 
            $showSettlements, 
            $showOrganizational, 
            $locale, 
            $julianDay2, 
            $julianDayText, 
            $govReference, 
            $tree, 
            $tooltip);
    
    return $str2;
  }
  
  public function govPgov(
          GovReference $govReference, 
          GedcomDateInterval $dateInterval, 
          Collection $typesOfLocation, 
          int $maxLevels = PHP_INT_MAX): Collection {
    
    try {
      return $this->govPgovInternal(
              $govReference, 
              $dateInterval, 
              $typesOfLocation, 
              $maxLevels);
      
    } catch (GOVServerUnavailableException $ex) {
      $this->flashGovServerUnavailable();
      return new Collection();
    }
  }
    
  protected function govPgovInternal(
          GovReference $govReference, 
          GedcomDateInterval $dateInterval, 
          Collection $typesOfLocation, 
          int $maxLevels = PHP_INT_MAX): Collection {   
    
    $useMedianDate = boolval($this->getPreference('USE_MEDIAN_DATE', '0'));
    $showSettlements = boolval($this->getPreference('ALLOW_SETTLEMENTS', '1'));
    $showOrganizational = boolval($this->getPreference('ALLOW_ORGANIZATIONAL', '1'));

    if ($useMedianDate) {
      $julianDay = $dateInterval->getMedian();
    } else {
      $julianDay = $dateInterval->getMin();
    }

    if ($julianDay === null) {
      return new Collection();
    }
    
    $govId = $govReference->getId();

    $ret = new Collection();

    $gov = FunctionsGov::retrieveGovObject($this, $govId);

    //load hierarchy (one per type of location)
    foreach ($typesOfLocation as $typeOfLocation) {
      $types = array();
      $typesFallback1 = array();
      $typesFallback2 = array();

      if ("POLI" === $typeOfLocation) {
        $types = FunctionsGov::admTypes();
        //if ($showSettlements) {
          //allways fallback, but perhaps don't show
          $typesFallback1 = FunctionsGov::settlementTypes();
        //}
        //if ($showOrganizational) {
          //allways fallback, but perhaps don't show
          $typesFallback2 = FunctionsGov::orgTypes();
        //}        
      } else if ("RELI" === $typeOfLocation) {
        $types = FunctionsGov::religiousTypes();
      }
      //"GEOG", "CULT" not supported yet!

      $currentGov = $gov;
      $currentGovId = $govId;
      $currentLevel = 0;
      //TODO: use from/to rather than single julianDay?
      
      while (($currentGov !== null) && ($maxLevels-- > 0)) {
        //next hierarchy level (if any)
        $nextGovId = null;
        $currentLevel++;
        
        if (sizeOf($types) > 0) {
          $nextGovId = FunctionsGov::findGovParentOfType($this, $currentGovId, $currentGov, $julianDay, $types, $gov->getVersion());
          $show = true;
        }        
        
        if (($nextGovId->getId() === null) && (sizeOf($typesFallback1) > 0)) {
          $nextGovId = FunctionsGov::findGovParentOfType($this, $currentGovId, $currentGov, $julianDay, $typesFallback1, $gov->getVersion());
          $show = $showSettlements;
        }
        
        if (($nextGovId->getId() === null) && (sizeOf($typesFallback2) > 0)) {
          $nextGovId = FunctionsGov::findGovParentOfType($this, $currentGovId, $currentGov, $julianDay, $typesFallback2, $gov->getVersion());
          $show = $showOrganizational;
        }
        
        if ($nextGovId->getId() === null) {
          break;
        }
        
        $currentGovId = $nextGovId->getId();
        $currentGov = FunctionsGov::retrieveGovObject($this, $currentGovId);

        if ($show && ($currentGov !== null)) {
          $trace = $govReference->getTrace();
          $trace->add('GOV-Id via Gov4Webtrees module (hierarchy)');
          $ret->add(new GovReference(
                  $currentGovId, 
                  $trace, 
                  $currentLevel));
        }
      }
    }

    return $ret;
  }

  public function gov2plac(GovReference $gov, Tree $tree): ?PlaceStructure {
    $ids = new Collection([$gov->getId()]);
    $place_id = FunctionsGov::getNamesMappedToGovIds($ids)->first();    
    if ($place_id === null) {
      //we haven't mapped this gov id at all, cannot use the $place_id
      //(we mustn't use the gov name of the place - it may clash with other placenames!)
      return null;
    }

    // Request for a non-existent place?
    
    //WTF SearchService
    $query = implode(',',explode(', ', $place_id));
    $place = $this->search_service->searchPlaces($tree, $query, 0, 1)            
            ->first();
            
    if ($place === null) {
      //gov id has been mapped, but place no longer exists (at least not in this tree)
      //or its a gov-id retrieved e.g. via gov parent hierarchy
      return null;
    }
    
    //set the _GOV tag to make further operations on this object more efficient 
    //(we don't have to look it up again)
    return PlaceStructure::fromNameAndGov($place_id, $gov->getId(), $tree);
  }
  
  ////////////////////////////////////////////////////////////////////////////////
 
  public function factPlaceAdditionsBeforePlace(PlaceStructure $place): ?string {
    return null;
  }
  
  public function factPlaceAdditionsAfterMap(PlaceStructure $place): ?string {
    $fpa = $this->factPlaceAdditions($place);
    
    return ($fpa === null)?null:$fpa->getMain();
  }
  
  public function factPlaceAdditionsAfterNotes(PlaceStructure $place): ?string {
    return null;
  }
  
  protected function factPlaceAdditions(PlaceStructure $place): ?GenericViewElement {
    //get a gov reference (may be provided by ourselves ultimately)
    $govReference = FunctionsPlaceUtils::plac2gov($this, $place, false);
    
    if ($govReference === null) {
      return null;
    }
    
    $tree = $place->getTree();
    $locale = I18N::locale();
        
    $compactDisplay = boolval($this->getPreference('COMPACT_DISPLAY', '1'));
    $withInternalLinks = intval($this->getPreference('DISPLAY_INTERNAL_LINKS', '1'));
    $showCurrentDateGov = intval($this->getPreference('SHOW_CURRENT_DATE', '0'));
    $showSettlements = boolval($this->getPreference('ALLOW_SETTLEMENTS', '1'));
    $showOrganizational = boolval($this->getPreference('ALLOW_ORGANIZATIONAL', '1'));
    $useMedianDate = boolval($this->getPreference('USE_MEDIAN_DATE', '0'));
    
    if ($useMedianDate) {
      $julianDay1 = $place->getEventDateInterval()->getMedian();
    } else {
      $julianDay1 = $place->getEventDateInterval()->getMin();
    }

    $tooltip = null;
    $debugGovSource = $this->getPreference('DEBUG_GOV_SOURCE', '1');
    if ($debugGovSource) {
      $tooltip .= $govReference->getTrace()->getAll();
    }    
    
    $julianDayText1 = "n/a"; //placeholder, will never be displayed!
    if (($julianDay1 !== null) && ($showCurrentDateGov !== 2)) {
      $julianDayText1 = Gov4WebtreesModule::gregorianYear($julianDay1);
    }
    
    $julianDay2 = null;
    $julianDayText2 = I18N::translate('today');
    if (($julianDay1 === null) || ($showCurrentDateGov !== 0)) {
      $dateTime = new DateTime();
      $dateTime->format('Y-m-d');
      $julianDay2 = FunctionsGov::todayAsJulianDay();
    }
    
    $julianDayTextCombined = $julianDayText1 . "–" . $julianDayText2;
    
    $gve = $this->getHierarchyMaybeCombined(
          $compactDisplay, 
          $julianDayText1, 
          $julianDayText2, 
          $julianDayTextCombined, 
          $tooltip, 
          $julianDay1, 
          $julianDay2, 
          $withInternalLinks, 
          $showSettlements, 
          $showOrganizational, 
          $locale,
          $govReference,
          $tree);
          
    return $gve;
  }
    
  protected function plac2linkIcon(PlaceStructure $ps): string {
    return $this->linkIcon(
            $this->name() . '::icons/place', 
            MoreI18N::xlate('Place'), 
            $ps->getPlace()->url());
  }
    
  public function linkIcon($view, $title, $url) {
    return '<a href="' . $url . '" rel="nofollow" title="' . $title . '">' .
            view($view) .
            '<span class="sr-only">' . $title . '</span>' .
            '</a>';
  }
  
  //empty key = global
  public function getResolvedLanguages(
          LocaleInterface $locale,
          string $govId = '',
          bool $returnNullInCaseOfNoOverrides = false): ?array {
   
    $overridesFilename = $this->resourcesFolder() . 'gov/languages.csv';

    $languageOverrides = FunctionsGov::getGovObjectLanguageOverrides(
              $overridesFilename,
              $govId);
        
    $languages = [];
    
    //$lang is always first!
    $lang = FunctionsGov::toLang($locale->languageTag());
    $languages []= $lang;
    
    if (sizeof($languageOverrides) === 0) {
      if ($returnNullInCaseOfNoOverrides) {
        return null;
      }
    } else {
      $languages = array_merge($languages, $languageOverrides);
    }
    
    $fallbackPreferDeu = boolval($this->getPreference('FALLBACK_LANGUAGE_PREFER_DEU', '1'));
    
    if ($fallbackPreferDeu) {
      $languages []= 'deu';
    }
        
    return $languages;
  }
  
  
  public function getResolvedLanguagesForTypes(
          LocaleInterface $locale): array {
   
    $overridesFilename = $this->resourcesFolder() . 'gov/languages.csv';

    $languageOverrides = FunctionsGov::getGovObjectLanguageOverrides(
              $overridesFilename,
              ''); //empty key = global
        
    $languagesForTypes = [];
    
    //$lang is always first!
    $lang = FunctionsGov::toLang($locale->languageTag());
    $languagesForTypes []= $lang;
    
    if (sizeof($languageOverrides) === 0) {
      //cf fallback is old FunctionsGov::retrieveTypeDescription method
      $languagesForTypes []= 'eng';
      $languagesForTypes []= 'deu';
    } else {
      $languagesForTypes = array_merge($languagesForTypes, $languageOverrides);
    }
    
    return $languagesForTypes;
  }
  
  protected function getHierarchies(
          bool $compactDisplay, 
          int $withInternalLinks, 
          bool $showSettlements, 
          bool $showOrganizational, 
          LocaleInterface $locale,
          string $julianDay, 
          GovReference $govReference,
          Tree $tree): array {
    
    $id = $govReference->getId();
 
    //retrieve hierarchy (has to be done separately in order to determine languages for labels)
    $datas = [];
    
    //initialize with placeholder
    $version = -1;
    
    $nextId = $id;    
    while ($nextId !== null) {
      $data = $this->getDataAndNextId(
              $showSettlements, 
              $showOrganizational, 
              //$locale, 
              $julianDay, 
              $nextId, 
              $version);
      
      if ($data === []) {
        $nextId = null;
      } else {
        $nextId = $data['nextId']->getId();
        
        if ($version === -1) {
          //replace placeholder version:
          //after a reset, object is reloaded from server, and now() is used as version (triggering reloading of parents)
          //otherwise, use version as set on initial object
          $version = $data['version'];
        }
        
        $datas []= $data;
      }
    }

    ///////////////////////////////////////////////////////////////////////////
    
    //restructure and determine whether there are local modifications
    $datas1 = [];
    
    //initially false
    $hasLocalModifications = false;
    
    $thisId = new GovIdPlus($id, $hasLocalModifications, false);
    $showThisId = true;
       
    foreach ($datas as $data) {
            
      //sanity check
      if ($thisId->getId() !== $data['id']) {
        throw new \Exception();
      }
      
      if ($data['type'] !== null) {
        $hasLocalModifications = $hasLocalModifications || $data['type']->getSticky();
      }
      
      if ($showThisId) {
        $datas1 []= [
              'type' => $data['type'], 
              'labels' => $data['labels'], 
              'thisId' => $thisId,
              'govReference' => $govReference];        
      }
      
      $thisId = $data['nextId'];
      $showThisId = $data['showNextId'];
      if ($thisId->getId() !== null) {
        $govReference = new GovReference($thisId->getId(), new Trace(''));
      } else {
        $govReference = null; //irrelevant, actually
      }
      
      $hasLocalModifications = $hasLocalModifications || 
        //may be set even if getId is null (final in hierarchy)!
        ($thisId->getHasLocalModifications());
    }
   
    ///////////////////////////////////////////////////////////////////////////

    //enhance with languages and resolve labels in reverse order
    $datas2 = [];
    
    $languages = $this->getResolvedLanguages($locale);
    $languagesForTypes = $this->getResolvedLanguagesForTypes($locale);
    
    
    foreach (array_reverse($datas1) as $data) {
      
      //$returnNullInCaseOfNoOverrides: preserve any higher level overrides 
      //(without this param would revert to defaults)
      $languagesAdjusted = $this->getResolvedLanguages($locale, $data['thisId']->getId(), true);
      if ($languagesAdjusted !== null) {
        $languages = $languagesAdjusted;
      }

      $label = FunctionsGov::getResolvedLabel($data['labels'], $languages);      
      $hasLocalModifications = $hasLocalModifications || $label->getSticky();

      $resolvedType = null;
      if ($data['type'] !== null) {
        $resolvedType = FunctionsGov::resolveTypeDescription($this, $data['type']->getProp(), $languagesForTypes);
      }

      $datas2 []= [
          'type' => $resolvedType, 
          'label' => $label->getProp(), 
          'thisId' => $data['thisId'],
          'govReference' => $data['govReference']];
    }
    
    ///////////////////////////////////////////////////////////////////////////
    
    $hierarchy = '';
    $hierarchy2 = '';
    
    foreach (array_reverse($datas2) as $data) {
      if ($hierarchy !== '') {
        $hierarchy .= ', ';
      }
              
      $nullType = I18N::translate('this place does not exist at this point in time');
      $typeAndLabel = $data['type'] . ' ' . $data['label'];
      if ($data['type'] === null) {
        $typeAndLabel = $data['label'] . ' (' . $nullType .')';
      }
      $displayedLabel = $data['label'];
      if ($data['type'] === null) {
        $displayedLabel = '<s>' . $data['label'] . '</s>';
      }
          
      switch ($withInternalLinks) {
        case 0: //classic
          $hierarchy .= '<a href="http://gov.genealogy.net/item/show/' . $data['thisId']->getId() . '" target="_blank" title="' . $typeAndLabel . '">';
          $hierarchy .= $displayedLabel;
          $hierarchy .= '</a>';
          break;
        case 1: //classic plus place/shared place icons
        case 2: //names and main links to place, plus gov icons
          $pre = '';

          //Issue #13: is this a known webtrees place?
          //(note: there may be more than one - we restrict to first found)
          $ps = FunctionsPlaceUtils::gov2plac($this, $data['govReference'], $tree);
          if ($ps !== null) {

            //link to location? note: for now not indirectly = only if location defines the GOV!
            $loc = $ps->getLoc();
            if ($loc !== null) {
              $locLink = FunctionsPlaceUtils::loc2linkIcon($this, new LocReference($loc, $tree, new Trace('')));
              if ($locLink !== null) {
                $pre = $locLink;
              }
            }
          }
          
          switch ($withInternalLinks) {
            case 1: //classic plus place/shared place icons
              if (($ps !== null) && ($pre === '')) {
                $pre = $this->plac2LinkIcon($ps);
              }
              $hierarchy .= $pre;
              $hierarchy .= '<a href="http://gov.genealogy.net/item/show/' . $data['thisId']->getId() . '" target="_blank" title="' . $typeAndLabel . '">';
              $hierarchy .= $displayedLabel;
              $hierarchy .= '</a>';
              break;
            case 2: //names and main links to place, plus gov icons
              if ($ps !== null) {
                $hierarchy .= '<a href="http://gov.genealogy.net/item/show/' . $data['thisId']->getId() . '" target="_blank" title="GOV: ' . $typeAndLabel . '">';
                $hierarchy .= '<span class="wt-icon-map-gov"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
                $hierarchy .= '&#8239;'; //meh (Narrow no-break space), should do this with css instead
                $hierarchy .= '</a>';
                $hierarchy .= $pre;
                $hierarchy .= '<a dir="auto" href="' . e($ps->getPlace()->url()) . '">' . $ps->getPlace()->placeName() . '</a>';

              } else {
                $hierarchy .= '<a href="http://gov.genealogy.net/item/show/' . $data['thisId']->getId() . '" target="_blank" title="GOV: ' . $typeAndLabel . '">';
                $hierarchy .= '<span class="wt-icon-map-gov"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
                $hierarchy .= '&#8239;'; //meh (Narrow no-break space), should do this with css instead
                $hierarchy .= '</a>';
                $hierarchy .= '<i>' . $displayedLabel . '</i>';
              }
              break;
            default:
              break;
          }  
          break;
        default:
          break;
      }

      if (!$compactDisplay) {
        if ($hierarchy2 !== '') {
          $hierarchy2 .= ', ';
        }
        
        if ($data['type'] === null) {
          $hierarchy2 .= $nullType;
        } else {
          $hierarchy2 .= $data['type'];
        }        
      }
    }
    
    return [$id, $hierarchy, $hierarchy2, $hasLocalModifications];
  }
  
  protected function getHierarchyGVE(
          bool $compactDisplay, 
          string $julianDayText,
          ?string $tooltip,
          array $hierarchies): GenericViewElement {
    
    [$id, $hierarchy, $hierarchy2, $hasLocalModifications] = $hierarchies;
    
    if ($id !== null) {
      //Issue #11
      $link = "http://gov.genealogy.net/item/show/" . $id;
    } else {
      //not sure what this fallback is good for
      $link = "http://gov.genealogy.net/";
    }

    $span = '<div><span class="govText">';
    $span .= '<a href="'. $link . '" target="_blank">';
    //we'd like to use far fa-compass but we'd have to import explicitly
    //TODO: use proper modal here? tooltip isn't helpful on tablets etc
    if ($tooltip) {
      $span .= '<span class="wt-icon-map-gov" title="' . $tooltip . '"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
    } else {
      $span .= '<span class="wt-icon-map-gov"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
    }
    $span .= ' GOV</a>';
    if ($hasLocalModifications) {
      //TODO shorter? replace by help icon?
      $span .= ' (' . I18N::translate('with local modifications') . ')';
    }
    $span .= ' (';
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
  
  protected function getHierarchy(
          bool $compactDisplay, 
          int $withInternalLinks, 
          bool $showSettlements, 
          bool $showOrganizational, 
          LocaleInterface $locale,
          string $julianDay, 
          string $julianDayText, 
          GovReference $govReference,
          Tree $tree,
          ?string $tooltip): GenericViewElement {
 
    $hierarchies = $this->getHierarchies(
          $compactDisplay, 
          $withInternalLinks, 
          $showSettlements, 
          $showOrganizational, 
          $locale,
          $julianDay, 
          $govReference,
          $tree);
        
    return $this->getHierarchyGVE(            
            $compactDisplay, 
            $julianDayText,
            $tooltip,
            $hierarchies);   
  }
  
  protected function getHierarchyMaybeCombined(
          bool $compactDisplay, 
          string $julianDayText1, 
          string $julianDayText2, 
          string $julianDayTextCombined, 
          ?string $tooltip, 
          ?string $julianDay1, 
          ?string $julianDay2, 
          int $withInternalLinks, 
          bool $showSettlements, 
          bool $showOrganizational, 
          LocaleInterface $locale,
          GovReference $govReference,
          Tree $tree): GenericViewElement {
    
    if ($julianDay1 !== null) {
      $hierarchies1 = $this->getHierarchies(
          $compactDisplay, 
          $withInternalLinks, 
          $showSettlements, 
          $showOrganizational, 
          $locale,
          $julianDay1, 
          $govReference,
          $tree);
    }
    
    if ($julianDay2 !== null) {
      $hierarchies2 = $this->getHierarchies(
          $compactDisplay, 
          $withInternalLinks, 
          $showSettlements, 
          $showOrganizational, 
          $locale,
          $julianDay2, 
          $govReference,
          $tree);
    }
    
    if (($julianDay1 !== null) && ($julianDay2 !== null) && ($hierarchies1 === $hierarchies2)) {
      return $this->getHierarchyGVE(            
            $compactDisplay, 
            $julianDayTextCombined,
            $tooltip,
            $hierarchies1);
    }
    
    if ($julianDay1 !== null) {
      $gve1 = $this->getHierarchyGVE(            
            $compactDisplay, 
            $julianDayText1,
            $tooltip,
            $hierarchies1);
    } else {
      $gve1 = GenericViewElement::createEmpty();
    }
            
    if ($julianDay2 !== null) {
      $gve2 = $this->getHierarchyGVE(            
            $compactDisplay, 
            $julianDayText2,
            $tooltip,
            $hierarchies2);
    } else {
      $gve2 = GenericViewElement::createEmpty();
    }
    
    return GenericViewElement::implode([$gve1, $gve2]);
  }
  
  protected function getDataAndNextId(
          bool $showSettlements, 
          bool $showOrganizational, 
          //LocaleInterface $locale,
          string $julianDay, 
          string $id, 
          int $version): array {
    
    //$lang = $locale->languageTag();
    
    try {
      $gov = FunctionsGov::retrieveGovObjectSnapshot(
              $this, 
              $julianDay, 
              $id, 
              $version);
      
      if ($gov == null) {
        //invalid id!
        return [];
      }

      //data and next id
      //$type = FunctionsGov::retrieveTypeDescription($this, $gov->getType(), $locale);
      $labels = $gov->getLabels();
      
      //next hierarchy level (if any)
      $nextId = FunctionsGov::findGovParentOfType(
              $this, 
              $id, 
              $gov, 
              $julianDay, 
              FunctionsGov::admTypes(), 
              $version);

      $show = true;
      
      if (/*$allowSettlements &&*/ ($nextId->getId() === null)) {
        $nextId = FunctionsGov::findGovParentOfType(
                $this, 
                $id, 
                $gov, 
                $julianDay, 
                FunctionsGov::settlementTypes(), 
                $version);
        
        $show = $showSettlements;
      }
      
      if (/*$allowOrganizational &&*/ ($nextId->getId() === null)) {
        $nextId = FunctionsGov::findGovParentOfType(
              $this, 
              $id, 
              $gov, 
              $julianDay, 
              FunctionsGov::orgTypes(), 
              $version);
        
        $show = $showOrganizational;
      }

      return [
          'type' => $gov->getType(), 
          'labels' => $labels, 
          'id' => $id,
          'nextId' => $nextId, 
          'showNextId' => $show, 
          'version' => $gov->getVersion()];
      
    } catch (GOVServerUnavailableException $ex) {
      $this->flashGovServerUnavailable();
      return [];
    }    
  }
  
  ////////////////////////////////////////////////////////////////////////////////
  
  private function title1(): string {
    return CommonI18N::locationDataProviders();
  }
  
  private function description1(): string {
    return CommonI18N::mapCoordinates();
  }
  
  //hook management - generalize?
  //adapted from ModuleController (e.g. listFooters)
  public function getFunctionsPlaceProvidersAction(): ResponseInterface {
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

  public function postFunctionsPlaceProvidersAction(ServerRequestInterface $request): ResponseInterface {
    $controller = new FunctionsPlaceProvidersAction($this);
    return $controller->handle($request);
  }

  protected function editConfigBeforeFaq() {
    $modules = FunctionsPlaceUtils::modules($this, true);

    $url1 = route('module', [
        'module' => $this->name(),
        'action' => 'FunctionsPlaceProviders'
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
