<?php

declare(strict_types=1);

namespace Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Cissee\Webtrees\Module\Gov4Webtrees\Gov4WebtreesModule;
use Cissee\WebtreesExt\MoreI18N;
use Fisharebest\Webtrees\Http\RequestHandlers\ControlPanel;
use Fisharebest\Webtrees\Http\RequestHandlers\ModulesAllPage;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Webtrees;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function redirect;
use function route;
use function str_starts_with;

/**
 * Show GOV data from the GOV server, and local modifications.
 */
class GovData implements RequestHandlerInterface
{
    use ViewResponseTrait;

    /** @var Gov4WebtreesModule */
    private $module;

    public function __construct(
        Gov4WebtreesModule $module
    ) {
        $this->module = $module;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $govId = $request->getAttribute('gov_id');
        
        //TODO force reload while we're at it?
        //but perhaps not after each single edit?
        //add 'reload' button?
        $gov = FunctionsGov::retrieveGovObject($this->module, $govId);
    
        if ($gov == null) {
          //$error = I18N::translate('Invalid GOV id! Valid GOV ids are e.g. \'EITTZE_W3091\', \'object_1086218\'.');      
          //return response(['html' => $error], StatusCodeInterface::STATUS_CONFLICT);

          return redirect(route(GovDataList::class));
        }

        $locale = I18N::locale();        
        $languages = $this->module->getResolvedLanguages($locale, $govId);
        $languagesForTypes = $this->module->getResolvedLanguagesForTypes($locale);
        $label = $gov->getResolvedLabel($languages)->getProp();

        $breadcrumbs = [];

        $icon = '<span class="wt-icon-map-gov"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
        
        $title = I18N::translate('GOV data for %1$s', $label);
        $titlePlus = I18N::translate('GOV data for %1$s', $icon . FunctionsGov::aToGovServer($govId, $label));

        $breadcrumbs[route(ControlPanel::class)] = MoreI18N::xlate('Control panel');
        $breadcrumbs[route(ModulesAllPage::class)] = MoreI18N::xlate('Modules');
        $breadcrumbs[$this->module->getConfigLink()] = $this->module->title();
        $breadcrumbs[route(GovDataList::class)] = I18N::translate('GOV data');
        $breadcrumbs[] = $label;
        
        $this->layout = 'layouts/administration';

        if (str_starts_with(Webtrees::VERSION, '2.1')) {
            $view = $this->module->name() . '::admin/gov-data';
        } else {
            $view = $this->module->name() . '::admin/gov-data_20';
        }
        
        return $this->viewResponse($this->module->name() . '::admin/gov-data', [
            'title'             => $title,
            'breadcrumbs'       => $breadcrumbs,
            'module'            => $this->module,
            'titlePlus'         => $titlePlus,
            'gov'               => $gov,
            'languages'         => $languages,
            'languagesForTypes' => $languagesForTypes,            
        ]);
    }
}
