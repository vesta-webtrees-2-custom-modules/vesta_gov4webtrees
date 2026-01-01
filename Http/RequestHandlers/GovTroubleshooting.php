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
use function route;

/**
 * test GOV server connection
 */
class GovTroubleshooting implements RequestHandlerInterface
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

        $title = I18N::translate('GOV troubleshooting');

        $breadcrumbs[route(ControlPanel::class)] = MoreI18N::xlate('Control panel');
        $breadcrumbs[route(ModulesAllPage::class)] = MoreI18N::xlate('Modules');
        $breadcrumbs[$this->module->getConfigLink()] = $this->module->title();
        $breadcrumbs[] = $title;

        $ret = FunctionsGov::checkGovIdRaw($this->module, "SCHERGJO54EJ");

        $this->layout = 'layouts/administration';

        $view = $this->module->name() . '::admin/gov-troubleshooting';

        return $this->viewResponse($view, [
            'title'       => $title,
            'breadcrumbs' => $breadcrumbs
        ] + $ret);
    }
}
