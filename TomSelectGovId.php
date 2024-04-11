<?php

declare(strict_types=1);

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Fisharebest\Webtrees\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function response;

class TomSelectGovId implements RequestHandlerInterface
{
    protected $module;

    public function __construct($module) {
        $this->module = $module;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        //$tree = $request->getAttribute('tree');
        //assert($tree instanceof Tree);

        //$at    = Validator::queryParams($request)->string('at');
        //$page  = Validator::queryParams($request)->integer('page') ?? 1;

        $query = Validator::queryParams($request)->string('query');

        $govId = $query;

        try {
            $ret = FunctionsGov::checkGovId($this->module, $govId);

            $results = ($ret !== null)?collect([[
                          'id'    => $ret,
                          'text'  => $ret,
                          'title' => ' ',
                      ]]):collect([]);

            return response([
                'total_count' => sizeof($results),
                'incomplete_results' => false,
                'items' => $results,
            ]);

        } catch (GOVServerUnavailableException $ex) {
            $this->module->flashGovServerUnavailable();
            return response([
                'error'    => 'GOVServerUnavailable',
            ], StatusCodeInterface::STATUS_SERVICE_UNAVAILABLE);
        }
    }
}
