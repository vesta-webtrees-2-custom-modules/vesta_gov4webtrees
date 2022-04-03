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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function redirect;
use function route;

/**
 * Edit GOV data.
 */
class GovDataEdit implements RequestHandlerInterface
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
        $type = $request->getAttribute('type');
        $key = (int)$request->getAttribute('key');
    
        
        if (($type != null) && ($key != null)) {
          $prop = FunctionsGov::getProp($type, $key);
        }
        
        if ($prop === null) {
          return redirect(route(GovDataList::class));
        } 
        
        $govId = $prop->getGovId();
        $gov = FunctionsGov::retrieveGovObject($this->module, $govId);
    
        if ($gov == null) {
          return redirect(route(GovDataList::class));
        }
        
        $locale = I18N::locale();
        $languages = $this->module->getResolvedLanguages($locale, $govId);
        $label = $gov->getResolvedLabel($languages)->getProp();

        $breadcrumbs = [];
        
        $typeLabel = '';
        if ($type == 'type') {
          $typeLabel = I18N::translate('GOV Object Type');
        } else if ($type == 'label') {
          $typeLabel = I18N::translate('GOV Name');
        } else if ($type == 'parent') {
          $typeLabel = I18N::translate('GOV Parent');
        }

        $placename = null;
        if ($type == 'parent') {
          $govIdParent = $prop->getProp();
          $gov = FunctionsGov::retrieveGovObject($this->module, $govIdParent);
          if ($gov !== null) {
            $languages = $this->module->getResolvedLanguages($locale, $govIdParent);
            $placename = $gov->getResolvedLabel($languages)->getProp();
          } //else inconsistency
        }
        
        $icon = '<span class="wt-icon-map-gov"><i class="fas fa-play fa-fw" aria-hidden="true"></i></span>';
        
        $title = I18N::translate('Edit %1$s for %2$s', $typeLabel, $label);
        $titlePlus = I18N::translate('Edit %1$s for %2$s', $typeLabel, $icon . FunctionsGov::aToGovServer($govId, $label));

        $breadcrumbs[route(ControlPanel::class)] = MoreI18N::xlate('Control panel');
        $breadcrumbs[route(ModulesAllPage::class)] = MoreI18N::xlate('Modules');
        $breadcrumbs[$this->module->getConfigLink()] = $this->module->title();
        $breadcrumbs[route(GovDataList::class)] = I18N::translate('GOV data');
        $breadcrumbs[route(GovData::class, ['gov_id' => $govId])] = $label;
        $breadcrumbs[] = $typeLabel;
        
        $this->layout = 'layouts/administration';

        return $this->viewResponse($this->module->name() . '::admin/gov-data-edit', [
            'title'             => $title,
            'breadcrumbs'       => $breadcrumbs,
            'module'            => $this->module,
            'titlePlus'         => $titlePlus,
            'prop'              => $prop,
            'type'              => $type,
            'typeLabel'         => $typeLabel,
            'gov'               => $gov,
            'placename'         => $placename,
        ]);
    }
}
