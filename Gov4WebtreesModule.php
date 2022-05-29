<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovData;
use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovDataDelete;
use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovDataEdit;
use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovDataList;
use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovDataSave;
use Cissee\Webtrees\Module\Gov4Webtrees\Model\GovHierarchy;
use Cissee\Webtrees\Module\Gov4Webtrees\Model\GovHierarchyUtils;
use Cissee\Webtrees\Module\Gov4Webtrees\Model\JulianDayInterval;
use Cissee\WebtreesExt\AbstractModule;
use Cissee\WebtreesExt\Elements\GovIdentifierExt;
use Cissee\WebtreesExt\Http\RequestHandlers\FunctionsPlaceProvidersAction;
use Cissee\WebtreesExt\Module\ModuleExtGlobalInterface;
use Cissee\WebtreesExt\Module\ModuleExtGlobalTrait;
use Cissee\WebtreesExt\Module\ModuleMetaInterface;
use Cissee\WebtreesExt\Module\ModuleMetaTrait;
use Cissee\WebtreesExt\MoreI18N;
use Cissee\WebtreesExt\Requests;
use Cissee\WebtreesExt\VirtualFact;
use DateTime;
use Exception;
use Fisharebest\ExtCalendar\GregorianCalendar;
use Fisharebest\Localization\Locale\LocaleInterface;
use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Http\Middleware\AuthAdministrator;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Location;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Place;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Report\ReportParserGenerate;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Services\SearchService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Fisharebest\Webtrees\Webtrees;
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
    use ModuleCustomTrait,
        ModuleMetaTrait,
        ModuleConfigTrait,
        ModuleExtGlobalTrait,
        VestaModuleTrait {
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

        $this->flashWhatsNew('\Cissee\Webtrees\Module\Gov4Webtrees\WhatsNew', 6);

        $router = Registry::routeFactory()->routeMap();

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
        
        //replace GovIdentifier (change its edit control)
        $ef = Registry::elementFactory();

        //note: hopefully no other _custom_ module after this one registers the same tags,
        //otherwise we lose
        $ef->registerTags([
            //these would require a different edit control (to get the PLAC name)
            //'FAM:*:PLAC:_GOV'  => new GovIdentifierExt(MoreI18N::xlate('GOV identifier')),
            //'INDI:*:PLAC:_GOV' => new GovIdentifierExt(MoreI18N::xlate('GOV identifier')),

            '_LOC:_GOV'        => new GovIdentifierExt(MoreI18N::xlate('GOV identifier')),
        ]);

        //do we want to make _GOV available everywhere for editing though?
        //for now, no. otherwise cf SharedPlacesModule customSubTags
    }

    public function messageGovServerUnavailable(): string {
        return I18N::translate("The GOV server seems to be temporarily unavailable.");
    }
    
    public function flashGovServerUnavailable() {
        //ongoing - error handling in case GOV server is unavailable
        //problematic: flash message aren't thead-safe, see webtrees issue #3138
        //but we can live with the current fix
        $messages = Session::get('flash_messages', []);
        if (empty($messages)) {
            FlashMessages::addMessage($this->messageGovServerUnavailable());
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
        $html .= '<link href="' . $this->assetUrl('css/' . $this->getThemeForCss() . '.css') . '" type="text/css" rel="stylesheet" />';

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
            } else if ('_jc-theme-justlight2_' === $themeName) {
                //and the custom 'JustLight' theme, version 2
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

    public function govIdEditControlSelectScriptSnippet(): string {
        return $this->govIdEditControlSelectScriptSnippetInternal(true);
    }

    public function govIdEditControlSelectScriptSnippetInternal(bool $withinModal): string {
        $html = view($this->name() . '::script/tom-select-initializer-gov', [
            //'withinModal' => $withinModal
        ]);

        return $html;
    }

    public function govIdEditControl(
            ?string $govId,
            string $id,
            string $name,
            string $placeName,
            ?string $placeNameSelector,
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
                'placeNameSelector' => $placeNameSelector,
                'internal' => false,
                'selectInitializer' => $forModal ? null : $this->govIdEditControlSelectScriptSnippetInternal(false),
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

    public function getTomSelectGovIdAction(ServerRequestInterface $request): ResponseInterface {
        if (!str_starts_with(Webtrees::VERSION, '2.1')) {
            throw new Exception;
        }
        
        $requestHandler = new TomSelectGovId($this);
        return $requestHandler->handle($request);
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

        //trigger add the vesta modal placeholder, with custom select2 snippet
        $script = $this->govIdEditControlSelectScriptSnippet();
        return $script;
    }

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

        $tag = explode(':', $fact->tag())[1];

        if ($tag === '_GOV') {
            //direct gov tag (on place or shared place)

            $govReference = new GovReference($fact->value(), new Trace(""));

            $viewName = $this->name() . '::edit/icon-fact-reload-gov';
        
            //allow to reload the gov hierarchy
            $html = view($viewName, [
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
            
            //cannot use this - skips levels 3 tags
            //$placerec = '2 PLAC ' . $event->attribute('PLAC');
            $placerec = ReportParserGenerate::getSubRecord(2, '2 PLAC', $fact->gedcom());
                        
            if (empty($placerec)) {
                //nothing to offer here
                return GenericViewElement::createEmpty();
            }

            //get a gov reference (may be provided by ourselves ultimately)
            $govReference = FunctionsPlaceUtils::plac2gov($this, $ps, false);

            if ($govReference === null) {
                //nothing to offer here
                return GenericViewElement::createEmpty();
            }

            $viewName = $this->name() . '::edit/icon-fact-reload-gov';
            
            //allow to reload the gov hierarchy
            $html = view($viewName, [
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

        $viewName = $this->name() . '::edit/icon-fact-map-gov';

        $html = view($viewName, [
            'fact' => $fact,
            'moduleName' => $this->name(),
            'title' => $title]);

        return GenericViewElement::create($html);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    public function hFactsTabGetOutputBeforeTab(
        GedcomRecord $record): GenericViewElement {
        
        //loadded uncondditionally anyway!
        //$pre = '<link href="' . $this->assetUrl('css/style.css') . '" type="text/css" rel="stylesheet" />';
	//return new GenericViewElement($pre, '');
        
        return GenericViewElement::createEmpty();
    }
  
    public function hFactsTabGetStyleadds(
        GedcomRecord $record,
        Fact $fact): array {
        
        $styleadds = [];
	if ($fact->id() === 'gov-history') {
            $styleadds[] = 'wt-gov-history-fact-pfh collapse'; //see style.css, and hFactsTabGetOutputInDBox
        }	
	return $styleadds;
    }

    public function hFactsTabGetOutputInDBox(
        GedcomRecord $record): GenericViewElement {
        
        if (sizeof($this->hFactsTabGetAdditionalFacts($record)) === 0) {
            return GenericViewElement::createEmpty();
        }
        
	return $this->getOutputInDescriptionBox(
            true, 
            'show-gov-history-factstab', 
            'wt-gov-history-fact-pfh', 
            I18N::translate('GOV Hierarchies'));
    }
  
    protected function getOutputInDescriptionBox(
        bool $toggleable, 
        string $id, 
        string $targetClass,           
        string $label) {
      
        ob_start();
        if ($toggleable) {
          ?>
          <label>
              <input id="<?php echo $id; ?>" type="checkbox" data-bs-toggle="collapse" data-bs-target=".<?php echo $targetClass; ?>" data-wt-persist="<?php echo $targetClass; ?>" autocomplete="off">
              <?php echo $label; ?>
          </label>
          <?php
        }

        return new GenericViewElement(ob_get_clean(), '');
    }
  
    public function hFactsTabGetOutputAfterTab(
        GedcomRecord $record,
        bool $ajax): GenericViewElement {
        
        if (!$ajax) {
            //nothing to do - in fact must not initialize twice!
            return GenericViewElement::createEmpty();
        }
        
        if (sizeof($this->hFactsTabGetAdditionalFacts($record)) === 0) {
            return GenericViewElement::createEmpty();
        }
        
        return $this->getOutputAfterTab(true, 'show-gov-history-factstab');
    }
  
    protected function getOutputAfterTab(
        bool $toggleable, 
        string $toggle): GenericViewElement {
        
        $post = "";

        if ($toggleable) {
          $post = $this->getScript($toggle);
        }

        return new GenericViewElement('', $post);
    }

    protected function getScript($toggle) {
        ob_start();
        ?>
        <script>
          webtrees.persistentToggle(document.querySelector('#<?php echo $toggle; ?>'));
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function hFactsTabGetAdditionalFacts(
        GedcomRecord $record) {
        
        $cacheKey = Gov4WebtreesModule::class . '_hFactsTabGetAdditionalFacts_' . $record->tree()->id() . $record->xref();
        $self = $this;
        $ret = Registry::cache()->array()->remember($cacheKey, static function () use ($self, $record): array {
            if (!($record instanceof Location)) {
                return [];
            }

            return $self->govHierarchiesAsFacts($record);
        });
        
        return $ret;
    }
    
    protected function govHierarchiesAsFacts(
        Location $location) {
      
        $loc = $location->xref();
        if ($loc === '') {
            
            //dummy record, should at least have PLAC
            //
            $placerec = ReportParserGenerate::getSubRecord(1, '1 PLAC', $location->gedcom());
            
            //strip the '1 PLAC '
            $placerec = substr($placerec, 7);
            
            $ps = PlaceStructure::fromName($placerec, $location->tree());
            if ($ps === null) {
                return [];
            }
            
            $gov = FunctionsPlaceUtils::plac2gov($this, $ps, false);
            $plac = $placerec;
        } else {
            $locReference = new LocReference($loc, $location->tree(), new Trace(''));
            $gov = FunctionsPlaceUtils::loc2gov($this, $locReference);
            $ps = FunctionsPlaceUtils::loc2plac($this, $locReference);
            if ($ps !== null) {
                $plac = $ps->getGedcomName();
            } else {
                $plac = '?'; //unexpected!
            }
        }
        
        if ($gov === null) {
            return [];
        }
        
        return $this->govHierarchiesAsFactsViaGov($gov, $location, $plac);
    }
    
    protected function govHierarchiesAsFactsViaGov(
        GovReference $gov,
        GedcomRecord $record,
        string $placeName) {        
        
        /*
        error_log("--------------------------------------------------------------------------------------");
        error_log("--------------------------------------------------------------------------------------");
        error_log("--------------------------------------------------------------------------------------");
        */

        try {
            $hierarchies = GovHierarchyUtils::hierarchies($this, $record->tree(), $gov, JulianDayInterval::open());
        } catch (GOVServerUnavailableException $ex) {
            $hierarchies = [];
            $this->flashGovServerUnavailable();
        }
        
        $facts = [];
        
        /** @var GovHierarchy $hierarchy */
        foreach ($hierarchies as $hierarchy) {
            $gedcom = "1 FACT";
            $gedcom .= "\n2 TYPE GOV Hierarchy";
            $gedcom .= "\n2 PLAC " . $placeName;
            
            //we know the _GOV already, more efficient this way than to re-resolve it
            //(resolving via plac2gov anyway isn't certain to succeed if _GOV originates from _LOC)
            $gedcom .= "\n3 _GOV " . $gov->getId();
            
            $gedcom .= $hierarchy->interval()->asGedcomDateInterval()->toGedcomString(2, true);
            
            //id must match styleadds key in hFactsTabGetStyleadds
            $fact = new VirtualFact($gedcom, $record, 'gov-history');
            $facts[] = $fact;
        }
        
        return $facts;
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
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

    public function gov2html(
        GovReference $govReference, 
        Tree $tree): ?GenericViewElement {
        
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
        $valid_place_id = FunctionsGov::getNamesMappedToGovIds($ids)
                //there may be more than one mapping, we use the first for which a place exists
                ->filter(function (string $place_id) use ($tree): bool {

                    // Request for a non-existent place?
                    //WTF SearchService
                    $query = implode(',', explode(', ', $place_id));
                    //searching for 'x' may find place 'x, y, z'
                    //we're only interested in exact matches!
                    //there should be a better SearchService method for this!

                    $place = $this->search_service->searchPlaces($tree, $query, 0, 1)
                            ->filter(function (Place $place) use ($place_id): bool {
                                return ($place->gedcomName() == $place_id);
                            })
                            ->first();

                    if ($place === null) {
                        //gov id has been mapped, but place no longer exists (at least not in this tree)
                        //or it's a gov-id retrieved e.g. via gov parent hierarchy
                        return false;
                    }

                    return true;
                })
                ->first();

        if ($valid_place_id === null) {
            //we haven't mapped this gov id at all, cannot use the $place_id
            //(we mustn't use the gov name of the place - it may clash with other placenames!)
            //or
            //gov id has been mapped, but place no longer exists (at least not in this tree)
            //or it's a gov-id retrieved e.g. via gov parent hierarchy
            return null;
        }

        //set the _GOV tag to make further operations on this object more efficient 
        //(we don't have to look it up again)
        return PlaceStructure::fromNameAndGov($valid_place_id, $gov->getId(), $tree);
    }

    ////////////////////////////////////////////////////////////////////////////////

    public function factPlaceAdditionsBeforePlace(PlaceStructure $place): ?string {
        return null;
    }

    public function factPlaceAdditionsAfterMap(PlaceStructure $place): ?string {
        $fpa = $this->factPlaceAdditions($place);

        return ($fpa === null) ? null : $fpa->getMain();
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

        /*
        $hierarchiesLegacy = $this->getHierarchies(
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
                        $hierarchiesLegacy);
        */
        
        $hierarchies = GovHierarchyUtils::hierarchy(
                $this, 
                $tree,
                $govReference,
                (int)$julianDay);
        
        return GovHierarchyUtils::getHierarchyGVE(
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
            /*
            $hierarchies1 = $this->getHierarchies(
                    $compactDisplay,
                    $withInternalLinks,
                    $showSettlements,
                    $showOrganizational,
                    $locale,
                    $julianDay1,
                    $govReference,
                    $tree);
            */
            
            $hierarchies1m = GovHierarchyUtils::hierarchy(
                $this, 
                $tree,
                $govReference,
                (int)$julianDay1);
        }

        if ($julianDay2 !== null) {
            /*
            $hierarchies2 = $this->getHierarchies(
                    $compactDisplay,
                    $withInternalLinks,
                    $showSettlements,
                    $showOrganizational,
                    $locale,
                    $julianDay2,
                    $govReference,
                    $tree);
            */
            
            $hierarchies2m = GovHierarchyUtils::hierarchy(
                $this, 
                $tree,
                $govReference,
                (int)$julianDay2);
        }

        if (
            ($julianDay1 !== null) && 
            ($julianDay2 !== null) && 
            ($hierarchies1m->govId() === $hierarchies2m->govId()) && 
            //($hierarchies1m->adjustedLanguages() === $hierarchies2m->adjustedLanguages()) && 
            ($hierarchies1m->labelsHtml() === $hierarchies2m->labelsHtml()) && 
            ($hierarchies1m->typesHtml() === $hierarchies2m->typesHtml()) && 
            ($hierarchies1m->hasLocalModifications() === $hierarchies2m->hasLocalModifications())) {
            
            /*
            $gveLegacy = $this->getHierarchyGVE(
                            $compactDisplay,
                            $julianDayTextCombined,
                            $tooltip,
                            $hierarchies1);
            
            return $gveLegacy;
            */
            
            $gveModernized = GovHierarchyUtils::getHierarchyGVE(
                            $compactDisplay,
                            $julianDayTextCombined,
                            $tooltip,
                            $hierarchies1m);
            
            return $gveModernized;
        }

        if ($julianDay1 !== null) {
            /*
            $gve1legacy = $this->getHierarchyGVE(
                    $compactDisplay,
                    $julianDayText1,
                    $tooltip,
                    $hierarchies1);
            */
            
            $gve1modernized = GovHierarchyUtils::getHierarchyGVE(
                    $compactDisplay,
                    $julianDayText1,
                    $tooltip,
                    $hierarchies1m);
            
        } else {
            //$gve1legacy = GenericViewElement::createEmpty();
            $gve1modernized = GenericViewElement::createEmpty();
        }

        if ($julianDay2 !== null) {
            /*
            $gve2legacy = $this->getHierarchyGVE(
                    $compactDisplay,
                    $julianDayText2,
                    $tooltip,
                    $hierarchies2);
            */
            
            $gve2modernized = GovHierarchyUtils::getHierarchyGVE(
                    $compactDisplay,
                    $julianDayText2,
                    $tooltip,
                    $hierarchies2m);
        } else {
            //$gve2legacy = GenericViewElement::createEmpty();
            $gve2modernized = GenericViewElement::createEmpty();
        }

        return GenericViewElement::implode([/*$gve1legacy,*/ $gve1modernized, /*$gve2legacy,*/ $gve2modernized]);
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

        $controller = new VestaAdminController($this);
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

    //[2022/05]
    //now that this is configurable per target module,
    //it doesn't make sense to edit here!
    protected function editConfigBeforeFaq_disabled() {
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
