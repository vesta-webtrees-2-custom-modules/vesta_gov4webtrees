<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Cissee\Webtrees\Module\Gov4Webtrees\FunctionsGov;
use DateTime;
use Fisharebest\ExtCalendar\GregorianCalendar;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Webtrees;
use Ramsey\Uuid\Uuid;
use Vesta\Model\GenericViewElement;
use Vesta\Model\PlaceStructure;

class FunctionsPrintGov {

  protected $directory;
  //we only need 'getSetting' and 'getName' here!
  protected $module;

  function __construct($directory, $module) {
    $this->directory = $directory;
    $this->module = $module;
  }

  public function getGovForFactPlace(PlaceStructure $place): GenericViewElement {
    return $this->govForFactPlace($this->module, $place);
  }

  /**
   * Get the value of level n data in the tag
   * Allow for multi-line values
   *
   * @return string|null
   */
  protected static function getValue($gedcom, $level, $tag) {
    if (preg_match('/(?:^|\n)' . $level . ' (?:' . $tag . ') ?(.*(?:(?:\n2 CONT ?.*)*))/', $gedcom, $match)) {
      return preg_replace("/\n" . ($level + 1) . " CONT ?/", "\n", $match[1]);
    } else {
      return null;
    }
  }

  public static function getGovId(PlaceStructure $place) {
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

    $type = 'MAIN';
    switch ($place->getEventType()) {
      case 'CHR':
      case 'MARR':
      case 'BAPM':
      case 'BURI':
        $type = 'KSP';
        break;
      default:
        break;
    }

    $id = null;
    $version = null;
    $idViaGedcom = false;

    $idViaGedcom = self::getValue($place->getGedcom(), 3, '_GOV');
    if ($idViaGedcom) {
      $id = $idViaGedcom;
      $version = 0;
      $idViaGedcom = true;
    } else {
      $idAndVersion = FunctionsGov::getGovId($fullName, $type);
      if ($idAndVersion) {
        $version = array_pop($idAndVersion);
        $id = array_pop($idAndVersion);
      }
    }

    return $id;
  }

  //TODO refactor use getGovId()
  protected function govForFactPlace($module, PlaceStructure $place): GenericViewElement {
    $name = $place->getPlace()->placeName();
    $fullName = $place->getPlace()->gedcomName();

    $placeId = $place->getPlace()->id();

    //this occurs in case of new place names (respective change not approved yet) 
    if (!$placeId) {
      return "";
    }

    //Filter::escapeHtml($place)
    if (!$name) {
      return "";
    }

    $type = 'MAIN';
    $fallbackType = 'KSP';
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

    $useMedianDate = boolval($module->getSetting('USE_MEDIAN_DATE', '0'));

    if ($useMedianDate) {
      $julianDay1 = $place->getEventDateInterval()->getMedian();
    } else {
      $julianDay1 = $place->getEventDateInterval()->getMin();
    }

    $dateTime = new DateTime();
    $dateTime->format('Y-m-d');
    $julianDay2 = cal_to_jd(CAL_GREGORIAN, $dateTime->format("m"), $dateTime->format("d"), $dateTime->format("Y"));

    $id = null;
    $version = null;
    $idViaGedcom = false;

    //supposed to be under 2 PLAC, that's not checked here though!
    $idViaGedcom = self::getValue($place->getGedcom(), 3, '_GOV');
    if ($idViaGedcom) {
      $id = $idViaGedcom;
      $version = 0;
      $idViaGedcom = true;
    } else {
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
    }

    $typeHint = "";
    if ("KSP" === $type) {
      $typeHint = ' (' . I18N::translate('use separate parish GOV id if appropriate') . ')';
    }

    $i18nJson = '{' .
            'setText:' . json_encode(I18N::translate('No GOV id set for \'%1$s\'%2$s!', $name, $typeHint)) . ', ' .
            'resetText:' . json_encode(I18N::translate('Reset GOV id for \'%1$s\'%2$s and reload the place hierarchy:', $name, $typeHint)) . ', ' .
            'reloadText:' . json_encode(I18N::translate('GOV id set via GEDCOM and not editable here! Reload the place hierarchy:')) . ', ' .
            'setCommand:' . json_encode(I18N::translate('Set id')) . ', ' .
            'resetCommand:' . json_encode(I18N::translate('Reset id')) . ', ' .
            'reloadCommand:' . json_encode(I18N::translate('Reload')) . ', ' .
            'invalidIdText:' . json_encode(I18N::translate('Invalid GOV id! Valid ids are e.g. \'EITTZE_W3091\', \'object_1086218\'. See the GOV website.')) . ', ' .
            'invalidIdViaGedcomText:' . json_encode(I18N::translate('Invalid GOV id set via GEDCOM! Edit the GEDCOM!')) . ', ' .
            'resetButton:' . json_encode(I18N::translate('Reset')) . ', ' .
            'adminLevels:' . json_encode(I18N::translate('Administrative levels')) .
            '}';

    $canEdit = false;
    $readonly = boolval($module->getSetting('NO_ONE_MAY_EDIT', '0'));
    if (!$readonly) {
      $relaxed = boolval($module->getSetting('VISITORS_MAY_EDIT', '0'));
      if ($relaxed) {
        $canEdit = true;
      } else {
        $canEdit = Auth::isManager($place->getTree()) || Auth::isEditor($place->getTree());
      }
    }

    $locale = WT_LOCALE;

    $compactDisplay = boolval($module->getSetting('COMPACT_DISPLAY', '1'));
    $showCurrentDateGov = boolval($module->getSetting('SHOW_CURRENT_DATE', '0'));
    $allowSettlements = boolval($module->getSetting('ALLOW_SETTLEMENTS', '1'));

    $str1 = GenericViewElement::createEmpty();
    $str2 = GenericViewElement::createEmpty();

    $fastAjax = boolval($module->getSetting('FAST_AJAX', '1'));

    if ($julianDay1) {
      $julianDayText = FunctionsPrintGov::gregorianYear($julianDay1);
      $str1 = $this->widget($fastAjax, $canEdit, $idViaGedcom, $compactDisplay, $allowSettlements, $locale, $julianDay1, $julianDayText, $name, $fullName, $type, $i18nJson, $id, $version);
    }
    if (!$julianDay1 || $showCurrentDateGov) {
      $julianDayText = I18N::translate('today');
      $str2 = $this->widget($fastAjax, $canEdit, $idViaGedcom, $compactDisplay, $allowSettlements, $locale, $julianDay2, $julianDayText, $name, $fullName, $type, $i18nJson, $id, $version);
    }
    return GenericViewElement::implode([$str1, $str2]);
  }

  //see Date.php
  //we're not supposed to use that in webtrees, nevermind
  //we don't necessarily want the median year, so we have to use our own function 
  public static function gregorianYear($julianDay) {
    $gregorian_calendar = new GregorianCalendar;
    list($year) = $gregorian_calendar->jdToYmd($julianDay);
    return $year;
  }

  public function widget(
          $fastAjax,
          $canEdit,
          $idViaGedcom,
          $compactDisplay,
          $allowSettlements,
          $locale,
          $julianDay,
          $julianDayText,
          $name,
          $fullName,
          $type,
          $i18nJson,
          $id = null,
          $version = null): GenericViewElement {

    $uuid = Uuid::uuid4();
    $idString = '';
    if (($id !== null) && ($version !== null)) {
      $idString = ', id:' . json_encode($id) . ', version:' . $version;
    }

    $html = //css loaded elsewhere (once)      
            //'<link href="'. Webtrees::MODULES_PATH . basename($this->directory) . '/style.css" type="text/css" rel="stylesheet" />'.
            //'<script src="'. WT_STATIC_URL . WT_MODULES_DIR . 'gov4webtrees/widgets.js"/>'.
            '<div id="govWidget-' . $uuid . '" class="govWidget"></div>';

    $slowUrl = route('module', [
        'module' => $this->module->name(),
        'action' => 'Expand'
    ]);

    $fastUrl = Webtrees::MODULES_PATH . basename($this->directory) . "/ajaxExpand.php";

    $script = '<script>$("#govWidget-' . $uuid . '").gov({' .
            'slowUrl:' . json_encode($slowUrl . "&") . ', ' .
            'fastUrl:' . json_encode($fastUrl . "?") . ', ' .
            'fastAjax:' . json_encode($fastAjax) . ', ' .
            'canEdit:' . json_encode($canEdit) . ', ' .
            'idViaGedcom:' . json_encode($idViaGedcom) . ', ' .
            'julianDay:' . $julianDay . ', ' .
            'julianDayText:' . json_encode($julianDayText) . ', ' .
            'name:' . json_encode($name) . ', ' .
            'placeId:' . json_encode($fullName) . ', ' .
            'type:' . json_encode($type) . ', ' .
            'i18n:' . $i18nJson . ', ' .
            'locale:' . json_encode($locale) . ', ' .
            'compactDisplay:' . json_encode($compactDisplay) . ', ' .
            'allowSettlements:' . json_encode($allowSettlements) .
            $idString .
            '});</script>';

    return new GenericViewElement($html, $script);
  }

}
