<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use DateInterval;
use DateTime;
use Illuminate\Database\Capsule\Manager as DB;
use nusoap_client;
use SoapClient;
use SoapFault;
use stdClass;

require_once __DIR__ . '/nusoap/lib/nusoap.php';

class SoapWrapper {

  public static function initNusoapClient($wsdl) {
    $client = new nusoap_client($wsdl, 'wsdl');
    //important:
    $client->soap_defencoding = 'utf-8';
    $client->encode_utf8 = false;
    $client->decode_utf8 = false;

    $err = $client->getError();
    if ($err) {
      throw new Exception("NuSOAP Constructor error: " . $err);
    }
    return $client;
  }

  public static function getTypeDescription($module, $type, $lang) {
    $wsdl = 'http://gov.genealogy.net/services/SimpleService?wsdl';

    $nusoap = boolval($module->getSetting('USE_NUSOAP', '0'));
    if (!$nusoap) {
      if (!extension_loaded('soap')) {
        //use NuSOAP, auto-adjust setting
        $nusoap = true;
        $module->setSetting('USE_NUSOAP', '1');
      }
    }

    if ($nusoap) {
      $client = SoapWrapper::initNusoapClient($wsdl);
      $description = $client->call('getTypeDescription', array('typeId' => $type, 'language' => $lang));
      return SoapWrapper::nusoapArrayToObject($description);
    }

    //libxml_disable_entity_loader(false); //this was experimental
    $readclient = new SoapClient($wsdl);
    return $readclient->getTypeDescription($type, $lang);
  }

  public static function checkObjectId($module, $id) {
    $wsdl = 'http://gov.genealogy.net/services/ComplexService?wsdl';

    $nusoap = boolval($module->getSetting('USE_NUSOAP', '0'));
    if (!$nusoap) {
      if (!extension_loaded('soap')) {
        //use NuSOAP, auto-adjust setting
        $nusoap = true;
        $module->setSetting('USE_NUSOAP', '1');
      }
    }

    if ($nusoap) {
      $client = SoapWrapper::initNusoapClient($wsdl);
      return $client->call('checkObjectId', array('itemId' => $id));
    }

    //libxml_disable_entity_loader(false); //this was experimental
    $readclient = new SoapClient($wsdl);
    return $readclient->checkObjectId($id);
  }

  public static function getObject($module, $id) {
    $wsdl = 'http://gov.genealogy.net/services/ComplexService?wsdl';

    $nusoap = boolval($module->getSetting('USE_NUSOAP', '0'));
    if (!$nusoap) {
      if (!extension_loaded('soap')) {
        //use NuSOAP, auto-adjust setting
        $nusoap = true;
        $module->setSetting('USE_NUSOAP', '1');
      }
    }

    if ($nusoap) {
      $client = SoapWrapper::initNusoapClient($wsdl);
      $place = $client->call('getObject', array('itemId' => $id));
      if ($client->fault) {
        echo 'Fault: ';
        print_r($place);
      } else {
        $err = $client->getError();
        if ($err) {
          echo 'Error: ';
          print_r($err);
        }
      }

      if ($place == null) {
        return null;
      }

      $place = SoapWrapper::nusoapArrayToObject($place);
      //print_r($place);
      //echo "<br/>";

      return $place;
    }

    //libxml_disable_entity_loader(false); //this was experimental
    $readclient = new SoapClient($wsdl);
    try {
      $place = $readclient->getObject($id);
    } catch (SoapFault $fault) {
      //if (substr($fault->faultstring,0,15) === "no such object:") {
      //	return null;
      //}
      throw $fault;
    }

    return $place;
  }

  //cannot use this - have to strip nusoap's '!' prefixes, and preserve some arrays
  public static function arrayToObjectViaJson($array) {
    $json = json_encode($array);
    $object = json_decode($json);
    return $object;
  }

  public static function nusoapArrayToObject($array) {
    $obj = new stdClass;
    $arr = [];

    $asArray = false;
    foreach ($array as $k => $v) {
      if (strlen($k)) {
        $key = $k;
        if (is_int($key)) {
          $asArray = true; //must still use recursion
        }

        //strip nusoap's '!' prefixes
        if (substr($key, 0, 1) === '!') {
          $key = substr($key, 1);
        }
        if (is_array($v)) {
          $obj->{$key} = SoapWrapper::nusoapArrayToObject($v);
          $arr[$key] = SoapWrapper::nusoapArrayToObject($v);
        } else {
          $obj->{$key} = $v;
          $arr[$key] = $v;
        }
      }
    }
    if ($asArray) {
      return $arr;
    }
    return $obj;
  }

}

class GovObject {

  private $lat;
  private $lon;
  private $version;
  private $types;
  private $labels;
  private $parents;

  public function getLat() {
    return $this->lat;
  }

  public function getLon() {
    return $this->lon;
  }

  public function getVersion() {
    return $this->version;
  }

  public function getLabels() {
    return $this->labels;
  }

  public function getTypes() {
    return $this->types;
  }

  public function getParents() {
    return $this->parents;
  }

  public function __construct($lat, $lon, $version, $types, $labels, $parents) {
    $this->lat = $lat;
    $this->lon = $lon;
    $this->version = $version;
    $this->types = $types;
    $this->labels = $labels;
    $this->parents = $parents;
  }

  public function toString() {
    $str = "";
    foreach ($this->getLabels() as $label) {
      $str .= $label->toString() . ";";
    }
    if (($this->getLat() != null) && ($this->getLon() != null)) {
      $str .= " location: (" . $this->getLat() . "; " . $this->getLon() . ").";
    }
    foreach ($this->getTypes() as $type) {
      $str .= " (type " . $type->toString() . ")";
    }
    foreach ($this->getParents() as $parent) {
      $str .= " parent: " . $parent->toString() . ".";
    }

    return $str;
  }

}

class GovObjectSnapshot {

  private $lat;
  private $lon;
  private $version;
  private $type;
  private $label;
  private $parents;

  public function getLat() {
    return $this->lat;
  }

  public function getLon() {
    return $this->lon;
  }

  public function getVersion() {
    return $this->version;
  }

  public function getLabel() {
    return $this->label;
  }

  public function getType() {
    return $this->type;
  }

  public function getParents() {
    return $this->parents;
  }

  public function __construct($lat, $lon, $version, $type, $label, $parents) {
    $this->lat = $lat;
    $this->lon = $lon;
    $this->version = $version;
    $this->type = $type;
    $this->label = $label;
    $this->parents = $parents;
  }

  public function toString() {
    $str = "";
    $str .= $this->getLabel()->toString() . ";";
    if (($this->getLat() != null) && ($this->getLon() != null)) {
      $str .= " location: (" . $this->getLat() . "; " . $this->getLon() . ").";
    }
    $str .= " (type " . $this->getType()->toString() . ")";
    foreach ($this->getParents() as $parent) {
      $str .= " parent: " . $parent->toString() . ".";
    }

    return $str;
  }

}

class GovProperty {

  private $prop;
  private $language;
  private $from;
  private $to;

  public function getProp() {
    return $this->prop;
  }

  public function getLanguage() {
    return $this->language;
  }

  public function getFrom() {
    return $this->from;
  }

  public function getTo() {
    return $this->to;
  }

  public function __construct($prop, $language, $from, $to) {
    $this->prop = $prop;
    $this->language = $language;
    $this->from = $from;
    $this->to = $to;
  }

  public function toString() {
    $str = " " . $this->getProp();
    if ($this->getFrom() != null) {
      $ymd = cal_from_jd($this->getFrom(), CAL_GREGORIAN);
      $str .= " (from " . $ymd["year"] . "-" . $ymd["month"] . "-" . $ymd["day"] . ")";
    }
    if ($this->getTo() != null) {
      $ymd = cal_from_jd($this->getTo(), CAL_GREGORIAN);
      $str .= " (to " . $ymd["year"] . "-" . $ymd["month"] . "-" . $ymd["day"] . ")";
    }

    return $str;
  }

}

class FunctionsGov {

  //TODO? "Die Beschreibung der Objekttypen kann man per Webservice mit der WSDL-Operation getTypeDescription abholen und in einer Tabelle ablegen." (GOV Wiki)
  //const+array not available in php < 5.6     
  public static $TYPES_RELIGIOUS = array(6, 9, 11, 12, 13, 26, 27, 28, 29, 30, 35, 41, 42, 43, 44, 82, 91, 92, 96, 124, 153, 155, 182, 183, 206, 219, 243, 244, 245, 249, 250, 253, 260);
  public static $TYPES_ADMINISTRATIVE = array(1, 2, 4, 5, 7, 10, 14, 16, 18, 20, 22, 23, 25, 31, 32, 33, 34, 36, 37, 38, 45, 46, 48, 50, 52, 53, 56, 57, 58, 59, 60, 61, 62, 63, 70, 71, 72, 73, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 88, 93, 94, 95, 97, 99, 100, 101, 108, 109, 110, 112, 113, 114, 115, 116, 117, 122, 125, 126, 127, 128, 130, 131, 133, 134, 135, 136, 137, 138, 140, 142, 143, 144, 145, 146, 148, 149, 150, 152, 154, 156, 157, 158, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 173, 174, 175, 176, 177, 178, 179, 180, 182, 183, 184, 185, 186, 188, 189, 190, 191, 192, 194, 201, 203, 204, 205, 207, 211, 212, 213, 214, 215, 216, 217, 218, 221, 222, 223, 224, 225, 226, 227, 234, 235, 237, 239, 240, 241, 246, 247, 248, 251, 252, 254, 255, 256, 257, 258);
  public static $TYPES_SETTLEMENT = array(8, 17, 21, 24, 30, 39, 40, 51, 54, 55, 64, 65, 66, 67, 68, 69, 87, 102, 111, 118, 120, 121, 129, 139, 159, 181, 193, 229, 230, 231, 232, 233, 236, 238);

  public static function clear() {
    DB::table('gov_objects')
            ->delete();

    DB::table('gov_labels')
            ->delete();

    DB::table('gov_types')
            ->delete();

    DB::table('gov_parents')
            ->delete();

    DB::table('gov_descriptions')
            ->delete();
    
    //keep ids, but re-version (browser caches getExpandAction() responses based on version, so we have to reset here)    
    DB::table('gov_ids')->update([
        'version' => time()
    ]);
  }

  public static function getGovId($name, $type) {
    $row = DB::table('gov_ids')
            ->where('name', '=', $name)
            ->where('type', '=', $type)
            ->first();

    if ($row == null) {
      return null;
    }
    $id = $row->gov_id;
    $version = $row->version;
    return array($id, $version);
  }

  public static function getNameMappedToGovId($id) {
    $row = DB::table('gov_ids')
            ->where('gov_id', '=', $id)
            ->first();

    if ($row == null) {
      return null;
    }
    return $row->name;
  }

  public static function setGovId($name, $type, $id, $version) {
    DB::table('gov_ids')
            ->where('name', '=', $name)
            ->where('type', '=', $type)
            ->delete();

    DB::table('gov_ids')->insert([
        'name' => $name,
        'type' => $type,
        'gov_id' => $id,
        'version' => $version
    ]);
  }

  public static function toLang($locale) {

    //currently, only 'eng','rus','dut' seem to have translated descriptions at all (and even these are incomplete)
    if (substr($locale, 0, 2) === "en") {
      return 'eng';
    }

    if (substr($locale, 0, 2) === "ru") {
      return 'rus';
    }

    if (substr($locale, 0, 2) === "nl") {
      return 'dut';
    }

    return 'deu';
  }

  public static function getTypeDescription($type, $lang) {
    $row = DB::table('gov_descriptions')
            ->where('type', '=', $type)
            ->where('lang', '=', $lang)
            ->first();

    if ($row == null) {
      return null;
    }
    $description = $row->description;
    return $description;
  }

  public static function setTypeDescription($type, $lang, $description) {
    DB::table('gov_descriptions')->insert([
        'type' => $type,
        'lang' => $lang,
        'description' => $description
    ]);
  }

  public static function loadTypeDescription($module, $type, $lang) {
    if ($type == '...') {
      //type may not be set for all dates (although it should be, in clean GOV)
      return '...';
    }
    $description = SoapWrapper::getTypeDescription($module, $type, $lang);
    return $description->item[0];
  }

  public static function retrieveTypeDescription($module, $type, $locale) {
    $lang = FunctionsGov::toLang($locale);

    $description = FunctionsGov::getTypeDescription($type, $lang);
    if ($description != null) {
      return $description;
    }

    $description = FunctionsGov::loadTypeDescription($module, $type, $lang);
    if ($description == null) {
      return $description;
    }

    FunctionsGov::setTypeDescription($type, $lang, $description);
    return $description;
  }

  public static function retrieveGovObject($module, $id) {
    $gov = FunctionsGov::getGovObject($id);
    if ($gov != null) {
      return $gov;
    }

    //not loaded at all
    $version = round(microtime(true) * 1000);
    $gov = FunctionsGov::loadGovObject($module, $id, $version);
    if ($gov == null) {
      return null;
    }

    FunctionsGov::setGovObject($id, $gov);
    return FunctionsGov::getGovObject($id);
  }

  //should only be used internally! use retrieveGovObject instead!
  public static function getGovObject($id) {
    $row = DB::table('gov_objects')
            ->where('gov_id', '=', $id)
            ->first();

    if ($row == null) {
      return null;
    }

    $lat = $row->lat;
    $lon = $row->lon;
    $version = $row->version;

    $types = FunctionsGov::getTypes($id);
    $labels = FunctionsGov::getLabels($id);
    $parents = FunctionsGov::getParents($id);
    return new GovObject($lat, $lon, $version, $types, $labels, $parents);
  }

  /**
   * return array (of GovProperty)
   */
  public static function getTypes($id) {
    $rows = DB::table('gov_types')
            ->where('gov_id', '=', $id)
            ->get();

    $props = array();
    foreach ($rows as $row) {
      $props[] = new GovProperty($row->type, null, $row->from, $row->to);
    }

    return $props;
  }

  public static function getLabels($id) {
    $rows = DB::table('gov_labels')
            ->where('gov_id', '=', $id)
            ->get();

    $props = array();
    foreach ($rows as $row) {
      $props[] = new GovProperty($row->label, $row->language, $row->from, $row->to);
    }

    return $props;
  }

  public static function getParents($id) {
    $rows = DB::table('gov_parents')
            ->where('gov_id', '=', $id)
            ->get();

    $props = array();
    foreach ($rows as $row) {
      $props[] = new GovProperty($row->parent_id, null, $row->from, $row->to);
    }

    return $props;
  }

  public static function setGovObject($id, GovObject $govObject) {

    // Run in a transaction (to prevent concurrent access of delete data)
    //probably redundant, see UseTransaction! 
    DB::connection()->beginTransaction();

    DB::table('gov_objects')->updateOrInsert([
        'gov_id' => $id,
            ], [
        'lat' => $govObject->getLat(),
        'lon' => $govObject->getLon(),
        'version' => $govObject->getVersion()
    ]);

    DB::table('gov_types')
            ->where('gov_id', '=', $id)
            ->delete();

    DB::table('gov_labels')
            ->where('gov_id', '=', $id)
            ->delete();

    DB::table('gov_parents')
            ->where('gov_id', '=', $id)
            ->delete();

    if (count($govObject->getTypes()) > 0) {
      $insertData = array();
      foreach ($govObject->getTypes() as $prop) {
        $insertData[] = [
            'gov_id' => $id,
            'type' => $prop->getProp(),
            'from' => $prop->getFrom(),
            'to' => $prop->getTo()
        ];
      }

      DB::table('gov_types')->
              insert($insertData);
    }

    if (count($govObject->getLabels()) > 0) {
      $insertData = array();
      foreach ($govObject->getLabels() as $prop) {
        $insertData[] = [
            'gov_id' => $id,
            'label' => $prop->getProp(),
            'language' => $prop->getLanguage(),
            'from' => $prop->getFrom(),
            'to' => $prop->getTo()
        ];
      }

      DB::table('gov_labels')->
              insert($insertData);
    }

    if (count($govObject->getParents()) > 0) {
      $insertData = array();
      foreach ($govObject->getParents() as $prop) {
        $insertData[] = [
            'gov_id' => $id,
            'parent_id' => $prop->getProp(),
            'from' => $prop->getFrom(),
            'to' => $prop->getTo()
        ];
      }

      DB::table('gov_parents')->
              insert($insertData);
    }

    //conclude transaction
    DB::connection()->commit();
  }

  public static function loadGovObject($module, $id, $version) {

    //first check for existence, maybe use replacement id
    $id = SoapWrapper::checkObjectId($module, $id);
    if ($id === "") {
      //object doesn't exist
      return null;
    }

    $place = SoapWrapper::getObject($module, $id);

    $lat = null;
    $lon = null;
    if (property_exists($place, "position")) {
      $lat = $place->position->lat;
      $lon = $place->position->lon;
    }
    $types = array();
    $labels = array();
    $parents = array();

    if (property_exists($place, "type")) {
      if (is_array($place->type)) {
        foreach ($place->type as $key => $type) {
          $from = FunctionsGov::getBeginAsJulianDate($type);
          $to = FunctionsGov::getEndAsJulianDateExclusively($type);
          $types[] = new GovProperty($type->value, null, $from, $to);
        }
      } else if ($place->type != null) {
        $from = FunctionsGov::getBeginAsJulianDate($place->type);
        $to = FunctionsGov::getEndAsJulianDateExclusively($place->type);
        $types[] = new GovProperty($place->type->value, null, $from, $to);
      }
    }

    if (property_exists($place, "name")) {
      if (is_array($place->name)) {
        foreach ($place->name as $key => $label) {
          $from = FunctionsGov::getBeginAsJulianDate($label);
          $to = FunctionsGov::getEndAsJulianDateExclusively($label);
          $lang = null;
          if (property_exists($label, "lang")) {
            $lang = $label->lang;
          }
          $labels[] = new GovProperty($label->value, $lang, $from, $to);
        }
      } else if ($place->name != null) {
        $from = FunctionsGov::getBeginAsJulianDate($place->name);
        $to = FunctionsGov::getEndAsJulianDateExclusively($place->name);
        $lang = null;
        if (property_exists($place->name, "lang")) {
          $lang = $place->name->lang;
        }
        $labels[] = new GovProperty($place->name->value, $lang, $from, $to);
      }
    }

    if (property_exists($place, "part-of")) {
      if (is_array($place->{'part-of'})) {
        foreach ($place->{'part-of'} as $key => $parent) {
          $from = FunctionsGov::getBeginAsJulianDate($parent);
          $to = FunctionsGov::getEndAsJulianDateExclusively($parent);
          $parents[] = new GovProperty($parent->ref, null, $from, $to);
        }
      } else if ($place->{'part-of'} != null) {
        $from = FunctionsGov::getBeginAsJulianDate($place->{'part-of'});
        $to = FunctionsGov::getEndAsJulianDateExclusively($place->{'part-of'});
        $parents[] = new GovProperty($place->{'part-of'}->ref, null, $from, $to);
      }
    }

    return new GovObject($lat, $lon, $version, $types, $labels, $parents);
  }

  //should only be used internally! use retrieveGovObjectSnapshot instead!
  public static function getGovObjectSnapshot($julianDay, $id, $lang) {
    $row = DB::table('gov_objects')
            ->where('gov_id', '=', $id)
            ->first();

    if ($row == null) {
      return null;
    }

    $lat = $row->lat;
    $lon = $row->lon;
    $version = $row->version;

    $type = FunctionsGov::getTypeSnapshot($julianDay, $id, $lang);
    $label = FunctionsGov::getLabelSnapshot($julianDay, $id, $lang);
    $parents = FunctionsGov::getParents($id);
    return new GovObjectSnapshot($lat, $lon, $version, $type, $label, $parents);
  }

  public static function getTypeSnapshot($julianDay, $id, $lang) {
    $row = DB::table('gov_types')
            ->where('gov_id', '=', $id)
            ->where(function($q) use ($julianDay) {
              $q->whereNull('from')->orWhere('from', '<=', $julianDay);
            })
            ->where(function($q) use ($julianDay) {
              $q->whereNull('to')->orWhere('to', '>', $julianDay);
            })
            ->first();

    if ($row == null) {
      return "...";
    }

    return $row->type;
  }

  public static function getLabelSnapshot1($julianDay, $id, $lang) {
    $rows = DB::table('gov_labels')
            ->where('gov_id', '=', $id)
            ->where(function($q) use ($julianDay) {
              $q->whereNull('from')->orWhere('from', '<=', $julianDay);
            })
            ->where(function($q) use ($julianDay) {
              $q->whereNull('to')->orWhere('to', '>', $julianDay);
            })
            ->orderBy('language', 'asc') //in order to obtain a consistent fallback
            ->get();

    $fallback = "...";
    foreach ($rows as $row) {
      $fallback = $row->label; //anything will do ...
      if ($row->language === $lang) {
        return $row->label;
      }
    }

    return $fallback;
  }

  public static function getLabelSnapshot($julianDay, $id, $lang) {
    $rows = DB::table('gov_labels')
            ->where('gov_id', '=', $id)
            ->where(function($q) use ($julianDay) {
              $q->whereNull('from')->orWhere('from', '<=', $julianDay);
            })
            ->where(function($q) use ($julianDay) {
              $q->whereNull('to')->orWhere('to', '>', $julianDay);
            })
            ->orderBy('language', 'asc') //in order to obtain a consistent fallback
            ->get();

    $fallback = "...";
    $fallbackDeu = "...";
    foreach ($rows as $row) {
      if ($fallback === "...") {
        $fallback = $row->label; //anything will do ... (but return a consistent fallback!)
      }
      if ($row->language === "deu") {
        $fallbackDeu = $row->label;
      }
      if ($row->language === $lang) {
        return $row->label;
      }
    }

    //if (empty($rows)) {
    //	return 'X'.$fallback.'Y'.$id.'Z'.$lang;
    //}
    //prefer German fallback
    if ($fallbackDeu !== "...") {
      return $fallbackDeu;
    }
    return $fallback;
  }

  public static function retrieveGovObjectSnapshot($module, $julianDay, $id, $version, $locale) {
    $lang = FunctionsGov::toLang($locale);

    $gov = FunctionsGov::getGovObjectSnapshot($julianDay, $id, $lang);
    if ($gov != null) {
      if ($gov->getVersion() >= $version) {
        return $gov;
      }
    }

    //version newer (or not loaded at all)
    $gov = FunctionsGov::loadGovObject($module, $id, $version);
    if ($gov == null) {
      return null;
    }

    FunctionsGov::setGovObject($id, $gov);
    return FunctionsGov::getGovObjectSnapshot($julianDay, $id, $lang);
  }

  public static function loadNonLoaded($module, $ids, $version) {
    $loadedIds = array();

    $rows = DB::table('gov_objects')
            ->whereIn('gov_id', $ids)
            ->where('version', '>=', $version)
            ->get();

    foreach ($rows as $row) {
      $loadedIds[] = $row->gov_id;
    }

    ////////

    foreach ($ids as $id) {
      if (!in_array($id, $loadedIds)) {
        //echo "LOAD " . $id . "<br/>";
        $gov = FunctionsGov::loadGovObject($module, $id, $version);
        if ($gov != null) {
          FunctionsGov::setGovObject($id, $gov);
        }
      }
    }
  }

  //TODO WARN IF AMBIGUOUS PARENTS (would have to be corrected in GOV directly)
  public static function findGovParentOfType($module, $id, $gov, $julianDay, $types, $version) {
    $ids = array();
    foreach ($gov->getParents() as $parent) {
      $ids[] = $parent->getProp();
    }

    if (count($ids) == 0) {
      return;
    }
    return FunctionsGov::findGovParentOfTypeViaIds($module, $id, $ids, $julianDay, $types, $version);
  }

  //TODO WARN IF AMBIGUOUS PARENTS (would have to be corrected in GOV directly)
  public static function findGovParentOfTypeViaIds($module, $id, $ids, $julianDay, $types, $version) {
    if (count($types) == 0) {
      return;
    }

    FunctionsGov::loadNonLoaded($module, $ids, $version);

    ////////

    $row = DB::table('gov_parents')
            ->join('gov_types', 'gov_parents.parent_id', '=', 'gov_types.gov_id')
            ->where('gov_parents.gov_id', '=', $id)
            ->whereIn('gov_types.type', $types)
            ->where(function($q) use ($julianDay) {
              $q->whereNull('gov_types.from')->orWhere('gov_types.from', '<=', $julianDay);
            })
            ->where(function($q) use ($julianDay) {
              $q->whereNull('gov_types.to')->orWhere('gov_types.to', '>', $julianDay);
            })
            ->where(function($q) use ($julianDay) {
              $q->whereNull('gov_parents.from')->orWhere('gov_parents.from', '<=', $julianDay);
            })
            ->where(function($q) use ($julianDay) {
              $q->whereNull('gov_parents.to')->orWhere('gov_parents.to', '>', $julianDay);
            })
            ->first();

    if ($row == null) {
      return null;
    }

    $id = $row->gov_id;
    return $id;
  }

  public static function getBeginAsJulianDate($prop) {
    if (property_exists($prop, "timespan")) {
      $timespan = $prop->timespan;
      if (property_exists($timespan, "begin")) {
        $begin = $timespan->begin;
        if (property_exists($begin, "jd")) {
          $jd = $begin->jd;
          return $jd;
        }
      }
    }

    if (property_exists($prop, "begin-year")) {
      $year = $prop->{'begin-year'};
      $jd = cal_to_jd(CAL_GREGORIAN, 1, 1, $year);
      return $jd;
    }

    if (property_exists($prop, "year")) {
      $year = $prop->year;
      $jd = cal_to_jd(CAL_GREGORIAN, 1, 1, $year);
      return $jd;
    }

    return null;
  }

  public static function getEndAsJulianDateExclusively($prop) {
    if (property_exists($prop, "timespan")) {
      $timespan = $prop->timespan;
      if (property_exists($timespan, "end")) {
        $end = $timespan->end;
        if (property_exists($end, "jd")) {
          $jd = $end->jd;

          $precision = 2; //fallback
          if (property_exists($end, "precision")) {
            $precision = $end->precision;
          }
          if ($precision = 2) {
            return $jd + 1; //next day
          }

          if ($precision = 1) {

            //what a mess
            //2016_12 UNDEPLOYED BUGFIX
            $ymd = cal_from_jd($jd, CAL_GREGORIAN);
            $dateTime = new DateTime();
            $dateTime->setDate($ymd["year"], $ymd["month"], $ymd["day"]);
            $dateTime->add(new DateInterval("P1M")); //next month
            $dateTime->format('Y-m-d');
            $jd = cal_to_jd(CAL_GREGORIAN, $dateTime->format("m"), $dateTime->format("d"), $dateTime->format("Y"));

            return $jd;
          }

          if ($precision = 0) {
            //what a mess
            //2016_12 UNDEPLOYED BUGFIX
            $ymd = cal_from_jd($jd, CAL_GREGORIAN);
            $dateTime = new DateTime();
            $dateTime->setDate($ymd["year"], $ymd["month"], $ymd["day"]);
            $dateTime->add(new DateInterval("P1Y")); //next year
            $dateTime->format('Y-m-d');
            $jd = cal_to_jd(CAL_GREGORIAN, $dateTime->format("m"), $dateTime->format("d"), $dateTime->format("Y"));

            return $jd;
          }

          //unexpected = fall-through
        }
      }
    }

    if (property_exists($prop, "end-year")) {
      $year = $prop->{'end-year'};
      $jd = cal_to_jd(CAL_GREGORIAN, 1, 1, $year + 1); //+1!
      return $jd;
    }

    if (property_exists($prop, "year")) {
      $year = $prop->year;
      $jd = cal_to_jd(CAL_GREGORIAN, 1, 1, $year + 1); //+1!
      return $jd;
    }

    return null;
  }

}
