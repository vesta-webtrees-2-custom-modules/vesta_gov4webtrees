<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Psr\Http\Message\ServerRequestInterface;
use Cissee\WebtreesExt\Requests;

class AjaxRequests {

  public static function expand($module, ServerRequestInterface $request) {
    $placeId = Requests::getStringOrNull($request, 'placeId'); //optional (used for set id)
    $type = Requests::getStringOrNull($request, 'type'); //optional (used for set id)
    $id = Requests::getString($request, 'id');
    $julianDay = Requests::getString($request, 'julianDay');
    $version = Requests::getString($request, 'version');
    $locale = Requests::getString($request, 'locale');
    $allowSettlements = Requests::getBool($request, 'allowSettlements');

    $gov = FunctionsGov::retrieveGovObjectSnapshot($module, $julianDay, $id, $version, $locale);

    if ($gov == null) {
      //invalid id!
      return '{}';
    }

    //set id
    if (($placeId) && ($type)) {
      FunctionsGov::setGovId($placeId, $type, $id, $version);
    }

    //data and next id
    $type = FunctionsGov::retrieveTypeDescription($module, $gov->getType(), $locale);
    $label = $gov->getLabel();

    //next hierarchy level (if any)
    $nextId = FunctionsGov::findGovParentOfType($module, $id, $gov, $julianDay, FunctionsGov::$TYPES_ADMINISTRATIVE, $version);
    if ($allowSettlements && !$nextId) {
      $nextId = FunctionsGov::findGovParentOfType($module, $id, $gov, $julianDay, FunctionsGov::$TYPES_SETTLEMENT, $version);
    }

    //return data as json
    $json = '{' .
            '"type":' . json_encode($type) . ',' .
            '"label":' . json_encode($label);

    if ($nextId) {
      $json .= ',"nextId":' . json_encode($nextId);
    }

    $json .= '}';

    return $json;
  }

}
