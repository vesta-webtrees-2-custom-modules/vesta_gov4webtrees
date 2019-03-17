<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Symfony\Component\HttpFoundation\Request;

class AjaxRequests {

  public static function expand($module, Request $request) {
    $placeId = $request->get('placeId'); //optional (used for set id)
    $type = $request->get('type'); //optional (used for set id)
    $id = $request->get('id');
    $julianDay = $request->get('julianDay');
    $version = $request->get('version');
    $locale = $request->get('locale');
    $allowSettlements = boolval($request->get('allowSettlements'));

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
