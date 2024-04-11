<?php

declare(strict_types=1);

namespace Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Cissee\Webtrees\Module\Gov4Webtrees\Gov4WebtreesModule;
use Cissee\Webtrees\Module\Gov4Webtrees\Model\GovHierarchyUtils;
use Cissee\WebtreesExt\MoreI18N;
use Fisharebest\Webtrees\Http\RequestHandlers\ControlPanel;
use Fisharebest\Webtrees\Http\RequestHandlers\ModulesAllPage;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function route;

/**
 * Show list of GOV data.
 */
class GovDataList implements RequestHandlerInterface
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

        $breadcrumbs = [];

        $title = I18N::translate('GOV data');

        $breadcrumbs[route(ControlPanel::class)] = MoreI18N::xlate('Control panel');
        $breadcrumbs[route(ModulesAllPage::class)] = MoreI18N::xlate('Modules');
        $breadcrumbs[$this->module->getConfigLink()] = $this->module->title();
        $breadcrumbs[] = $title;

        $this->layout = 'layouts/administration';

        $locale = I18N::locale();

        $rows = [];

        foreach (FunctionsGov::allGovIds() as $govId) {
          $gov = FunctionsGov::retrieveGovObject($this->module, $govId);

          if ($gov != null) {
            $languages = GovHierarchyUtils::getResolvedLanguages($this->module, $locale, $govId);
            $sortBy = $gov->getResolvedLabel($languages)->getProp();
            $label = $gov->formatForAdminView($this->module, $languages);
            $lat = $gov->getLat();
            $lon = $gov->getLon();
            $hasStickyProp = $gov->hasStickyProp();

            $rows[$govId] = [
                'sortBy'        => $sortBy,
                'label'         => $label,
                'lat'           => $lat,
                'lon'           => $lon,
                'hasStickyProp' => $hasStickyProp,
            ];
          }
        }

        uasort($rows, static function (array $x, array $y): int {
            return I18N::comparator()($x['sortBy'], $y['sortBy']);
        });

        $view = $this->module->name() . '::admin/gov-data-list';

        return $this->viewResponse($view, [
            'title'       => $title,
            'breadcrumbs' => $breadcrumbs,
            'rows'        => $rows,
        ]);
    }
}
