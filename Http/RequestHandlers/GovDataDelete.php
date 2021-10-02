<?php

declare(strict_types=1);

namespace Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function redirect;
use function route;

/**
 * Delete GOV data.
 */
class GovDataDelete implements RequestHandlerInterface {

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
        
        FunctionsGov::deletePropIfSticky($type, $key);
        
        $govId = $prop->getGovId();        
        $url = route(GovData::class, ['gov_id' => $govId]);
        return redirect($url);
    }
}
