<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use Fisharebest\ExtCalendar\GregorianCalendar;
use Vesta\Model\PlaceStructure;

class FunctionsPrintGov {

  //we need 'getSetting', 'getName', and 'assetUrl()' here!
  protected $module;

  function __construct($module) {
    $this->module = $module;
  }

  /**
   * Get the value of level n data in the tag
   * Allow for multi-line values
   *
   * @return string|null
   */
  public static function getValue($gedcom, $level, $tag) {
    if (preg_match('/(?:^|\n)' . $level . ' (?:' . $tag . ') ?(.*(?:(?:\n2 CONT ?.*)*))/', $gedcom, $match)) {
      return preg_replace("/\n" . ($level + 1) . " CONT ?/", "\n", $match[1]);
    } else {
      return null;
    }
  }

  public static function getGovId(PlaceStructure $place): ?string {
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
}
