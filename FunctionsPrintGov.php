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
      
    //https://github.com/vesta-webtrees-2-custom-modules/vesta_gov4webtrees/issues/3
    //$type is legacy!
    $type = 'MAIN';
    $fallbackType = 'KSP';
    /*    
    //don't even switch for legacy ids - confusing wrt resetting gov id
    switch ($place->getEventType()) {
      case 'CHR':
      case 'MARR':
      case 'BAPM':
      case 'BURI':
        $type = 'KSP';
        $fallbackType = 'MAIN';
        break;
      default:
        break;
    }
    */

    $id = null;
    
    //note: version in id mapping table is nowhere used, i.e. deprecated!
    $version = null;

    $idAndVersion = FunctionsGov::getGovId($fullName, $type);
    if ($idAndVersion) {
      $version = array_pop($idAndVersion);
      $id = array_pop($idAndVersion);
    } else {
      //fallback
      $idAndVersion = FunctionsGov::getGovId($fullName, $fallbackType);
      if ($idAndVersion) {
        $version = array_pop($idAndVersion);
        $id = array_pop($idAndVersion);
      }
    }

    return $id;
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
