<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use DateInterval;
use DateTime;
use Exception;
use Fisharebest\Localization\Locale\LocaleInterface;
use Fisharebest\Webtrees\Registry;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;
use nusoap_client;
use SoapClient;
use stdClass;
use Throwable;
use const CAL_GREGORIAN;
use function cal_from_jd;
use function cal_to_jd;

require_once __DIR__ . '/nusoap/lib/nusoap.php';

class SoapWrapper {

  public static function initSoapClient($wsdl) {
    //libxml_disable_entity_loader(false); //this was experimental
    
    /*
    $params = array(
      'trace' => 1,
      'exceptions' => 1, 
      'cache_wsdl' => WSDL_CACHE_NONE
    );
    */
    
    /*
    try {
      $file = file_get_contents($wsdl);
      error_log("wsdl loaded: ".$file);
    } catch (\Exception $ex) {
      error_log("wsdl NOT loaded: ".$ex->getTraceAsString());
    }
    */
    
    return new SoapClient($wsdl);
  }
  
  public static function initNusoapClient($wsdl) {
    //timeouts: 10 seconds
    $client = new nusoap_client($wsdl, 'wsdl', false, false, false, false, 10, 10);
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
  
  //obsolete, descriptions now managed via owl file
  public static function getTypeDescription($module, $type, $lang) {
    $wsdl = 'http://gov.genealogy.net/services/SimpleService?wsdl';

    $nusoap = boolval($module->getPreference('USE_NUSOAP', '0'));
    if (!$nusoap) {
      if (!extension_loaded('soap')) {
        //use NuSOAP, auto-adjust setting
        $nusoap = true;
        $module->setPreference('USE_NUSOAP', '1');
      } else {
        try {
          $readclient = SoapWrapper::initSoapClient($wsdl);
          return $readclient->getTypeDescription($type, $lang);
        } catch (Throwable $ex) {          
          //fall-through and retry with nusoap, in order to simplify error handling
        }        
      }
    }

    $client = SoapWrapper::initNusoapClient($wsdl);
    //$client->setGlobalDebugLevel(1);
    $description = $client->call('getTypeDescription', array('typeId' => $type, 'language' => $lang));
    $err = $client->getError();
    if ($err) {
      error_log("GOVServerUnavailable: " . print_r($err, TRUE));
      throw new GOVServerUnavailableException($err);
    }
    return SoapWrapper::nusoapArrayToObject($description);
  }

  public static function checkObjectId($module, $id) {
    $wsdl = 'http://gov.genealogy.net/services/ComplexService?wsdl';
  
    $nusoap = boolval($module->getPreference('USE_NUSOAP', '0'));
    if (!$nusoap) {
      if (!extension_loaded('soap')) {
        //use NuSOAP, auto-adjust setting
        $nusoap = true;
        $module->setPreference('USE_NUSOAP', '1');
      } else {
        try {
          $readclient = SoapWrapper::initSoapClient($wsdl);
          return $readclient->checkObjectId($id);
        } catch (Throwable $ex) {
          //fall-through and retry with nusoap, in order to simplify error handling
        }        
      }
    }

    $client = SoapWrapper::initNusoapClient($wsdl);
    //$client->setGlobalDebugLevel(1);
    $ret = $client->call('checkObjectId', array('itemId' => $id));
    $err = $client->getError();
    if ($err) {
      error_log("GOVServerUnavailable: " . print_r($err, TRUE));
      throw new GOVServerUnavailableException($err);
    }
    return $ret;
  }

  public static function getObject($module, $id) {
    $wsdl = 'http://gov.genealogy.net/services/ComplexService?wsdl';
    
    $nusoap = boolval($module->getPreference('USE_NUSOAP', '0'));
    if (!$nusoap) {
      if (!extension_loaded('soap')) {
        //use NuSOAP, auto-adjust setting
        $nusoap = true;
        $module->setPreference('USE_NUSOAP', '1');
      } else {
        try {
          $readclient = SoapWrapper::initSoapClient($wsdl);
          return $readclient->getObject($id);
        } catch (Throwable $ex) {
          //fall-through and retry with nusoap, in order to simplify error handling
        }        
      }
    }

    $client = SoapWrapper::initNusoapClient($wsdl);
    $place = $client->call('getObject', array('itemId' => $id));
    $err = $client->getError();
    if ($err) {
      error_log("GOVServerUnavailable: " . print_r($err, TRUE));
      throw new GOVServerUnavailableException($err);
    }
    if ($place == null) {
      return null;
    }

    return SoapWrapper::nusoapArrayToObject($place);
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
  private $labels;
  private $parents;

  public function getLat() {
    return $this->lat;
  }

  public function getLon() {
    return $this->lon;
  }

  public function getVersion(): int {
    return $this->version;
  }

  public function getLabels():array {
    return $this->labels;
  }

  public function getType() {
    return $this->type;
  }

  public function getParents() {
    return $this->parents;
  }

  public function __construct(
          $lat, 
          $lon, 
          int $version, 
          $type, 
          array $labels, 
          $parents) {
    
    $this->lat = $lat;
    $this->lon = $lon;
    $this->version = $version;
    $this->type = $type;
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

  //TODO update regularly! Last update: 2020/08. Source: http://gov.genealogy.net/type/list and https://gov.genealogy.net/types.owl
  
  //http://gov.genealogy.net/types.owl#group_1
  //with subs:
  //civil
  //http://gov.genealogy.net/types.owl#group_2
  //adm0
  //http://gov.genealogy.net/types.owl#group_26
  //adm1
  //http://gov.genealogy.net/types.owl#group_27
  //adm2
  //http://gov.genealogy.net/types.owl#group_28
  //adm3
  //http://gov.genealogy.net/types.owl#group_29
  //adm4
  //http://gov.genealogy.net/types.owl#group_30
  //adm5
  //http://gov.genealogy.net/types.owl#group_31
  //adm6
  //http://gov.genealogy.net/types.owl#group_32
  //excluding
  //71 'Staatenbund'
  //77 'Reichskreis'
  public static $TYPES_ADMINISTRATIVE = array(1, 2, 4, 5, 7, 10, 14, 16, 18, 20, 22, 23, 25, 31, 32, 33, 34, 36, 37, 38, 45, 46, 48, 50, 52, 53, 56, 57, 58, 59, 60, 61, 62, 63, 70, 72, 73, 75, 76, 78, 79, 80, 81, 82, 83, 84, 85, 86, 88, 93, 94, 95, 97, 99, 100, 101, 108, 109, 110, 112, 113, 114, 115, 116, 117, 122, 125, 126, 127, 128, 130, 131, 133, 134, 135, 136, 137, 138, 140, 142, 143, 144, 145, 146, 148, 149, 150, 152, 154, 156, 157, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 173, 174, 175, 176, 177, 178, 179, 180, 182, 183, 184, 185, 186, 188, 189, 190, 191, 192, 194, 201, 203, 204, 205, 207, 211, 212, 213, 214, 215, 216, 217, 218, 221, 222, 223, 224, 225, 226, 227, 234, 235, 237, 239, 240, 241, 246, 247, 248, 251, 252, 254, 255, 256, 257, 258, 259, 262, 264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274);
  
  //71 'Staatenbund'
  public static $TYPES_ORGANIZATIONAL = array(71, 77);
          
  //http://gov.genealogy.net/types.owl#group_3
  public static $TYPES_RELIGIOUS = array(6, 9, 11, 12, 13, 26, 27, 28, 29, 30, 35, 41, 42, 43, 44, 82, 91, 92, 96, 124, 153, 155, 182, 183, 206, 219, 243, 244, 245, 249, 250, 253, 260, 263);
  
  //http://gov.genealogy.net/types.owl#group_8
  public static $TYPES_SETTLEMENT = array(8, 17, 21, 24, 30, 39, 40, 51, 54, 55, 64, 65, 66, 67, 68, 69, 87, 102, 111, 118, 120, 121, 129, 139, 158, 159, 181, 193, 229, 230, 231, 232, 233, 236, 238, 261);
  
  //http://gov.genealogy.net/types.owl#group_6
  public static $TYPES_JUDICIAL = array(3, 19, 70, 79, 105, 114, 151, 154, 202, 223, 224, 228);
  
  //http://gov.genealogy.net/types.owl#group_4
  public static $TYPES_GEOGRAPHIC = array(47, 107);
  
  //http://gov.genealogy.net/types.owl#group_10
  public static $TYPES_PLACE = array(15, 89, 90, 166);
  
  //http://gov.genealogy.net/types.owl#group_9
  public static $TYPES_TRANSPORTATION = array(118, 119);

  //http://gov.genealogy.net/types.owl#group_13
  //other (deprecated)
  
  public static function allTypes(): array {
    $ret = array_merge(FunctionsGov::$TYPES_RELIGIOUS, 
            FunctionsGov::$TYPES_ADMINISTRATIVE,
            FunctionsGov::$TYPES_SETTLEMENT,
            FunctionsGov::$TYPES_JUDICIAL,
            FunctionsGov::$TYPES_GEOGRAPHIC,
            FunctionsGov::$TYPES_PLACE,
            FunctionsGov::$TYPES_TRANSPORTATION);
    
    return $ret;
  }
  
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
    
    //keep ids!
  }

  public static function getGovId($name): ?string {
    $row = DB::table('gov_ids')
            ->where('name', '=', $name)
            ->first();

    if ($row == null) {
      return null;
    }
    $id = $row->gov_id;
    return $id;
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

  /**
   * 
   * @param Collection<string> $ids
   * @return Collection<string>
   */
  public static function getNamesMappedToGovIds(Collection $ids): Collection {    
    return DB::table('gov_ids')
            ->whereIn('gov_id', $ids->toArray())
            ->get()
            ->map(function (stdClass $row): string {
                return $row->name;
            });
  }
  
  public static function deleteGovId($name) {
    DB::table('gov_ids')
            ->where('name', '=', $name)
            ->delete();
  }
  
  public static function setGovId($name, $id) {    
    FunctionsGov::deleteGovId($name);

    DB::table('gov_ids')->insert([
        'name' => $name,
        'gov_id' => $id
    ]);
  }

  //taken from https://github.com/datasets/language-codes
  protected const LANGUAGES = [
      "aa" => "aar",
      "ab" => "abk",
      "ae" => "ave",
      "af" => "afr",
      "ak" => "aka",
      "am" => "amh",
      "an" => "arg",
      "ar" => "ara",
      "as" => "asm",
      "av" => "ava",
      "ay" => "aym",
      "az" => "aze",
      "ba" => "bak",
      "be" => "bel",
      "bg" => "bul",
      "bh" => "bih",
      "bi" => "bis",
      "bm" => "bam",
      "bn" => "ben",
      "bo" => "tib",
      "br" => "bre",
      "bs" => "bos",
      "ca" => "cat",
      "ce" => "che",
      "ch" => "cha",
      "co" => "cos",
      "cr" => "cre",
      "cs" => "cze",
      "cu" => "chu",
      "cv" => "chv",
      "cy" => "wel",
      "da" => "dan",
      "de" => "ger",
      "dv" => "div",
      "dz" => "dzo",
      "ee" => "ewe",
      "el" => "gre",
      "en" => "eng",
      "eo" => "epo",
      "es" => "spa",
      "et" => "est",
      "eu" => "baq",
      "fa" => "per",
      "ff" => "ful",
      "fi" => "fin",
      "fj" => "fij",
      "fo" => "fao",
      "fr" => "fre",
      "fy" => "fry",
      "ga" => "gle",
      "gd" => "gla",
      "gl" => "glg",
      "gn" => "grn",
      "gu" => "guj",
      "gv" => "glv",
      "ha" => "hau",
      "he" => "heb",
      "hi" => "hin",
      "ho" => "hmo",
      "hr" => "hrv",
      "ht" => "hat",
      "hu" => "hun",
      "hy" => "arm",
      "hz" => "her",
      "ia" => "ina",
      "id" => "ind",
      "ie" => "ile",
      "ig" => "ibo",
      "ii" => "iii",
      "ik" => "ipk",
      "io" => "ido",
      "is" => "ice",
      "it" => "ita",
      "iu" => "iku",
      "ja" => "jpn",
      "jv" => "jav",
      "ka" => "geo",
      "kg" => "kon",
      "ki" => "kik",
      "kj" => "kua",
      "kk" => "kaz",
      "kl" => "kal",
      "km" => "khm",
      "kn" => "kan",
      "ko" => "kor",
      "kr" => "kau",
      "ks" => "kas",
      "ku" => "kur",
      "kv" => "kom",
      "kw" => "cor",
      "ky" => "kir",
      "la" => "lat",
      "lb" => "ltz",
      "lg" => "lug",
      "li" => "lim",
      "ln" => "lin",
      "lo" => "lao",
      "lt" => "lit",
      "lu" => "lub",
      "lv" => "lav",
      "mg" => "mlg",
      "mh" => "mah",
      "mi" => "mao",
      "mk" => "mac",
      "ml" => "mal",
      "mn" => "mon",
      "mr" => "mar",
      "ms" => "may",
      "mt" => "mlt",
      "my" => "bur",
      "na" => "nau",
      "nb" => "nob",
      "nd" => "nde",
      "ne" => "nep",
      "ng" => "ndo",
      "nl" => "dut",
      "nn" => "nno",
      "no" => "nor",
      "nr" => "nbl",
      "nv" => "nav",
      "ny" => "nya",
      "oc" => "oci",
      "oj" => "oji",
      "om" => "orm",
      "or" => "ori",
      "os" => "oss",
      "pa" => "pan",
      "pi" => "pli",
      "pl" => "pol",
      "ps" => "pus",
      "pt" => "por",
      "qu" => "que",
      "rm" => "roh",
      "rn" => "run",
      "ro" => "rum",
      "ru" => "rus",
      "rw" => "kin",
      "sa" => "san",
      "sc" => "srd",
      "sd" => "snd",
      "se" => "sme",
      "sg" => "sag",
      "si" => "sin",
      "sk" => "slo",
      "sl" => "slv",
      "sm" => "smo",
      "sn" => "sna",
      "so" => "som",
      "sq" => "alb",
      "sr" => "srp",
      "ss" => "ssw",
      "st" => "sot",
      "su" => "sun",
      "sv" => "swe",
      "sw" => "swa",
      "ta" => "tam",
      "te" => "tel",
      "tg" => "tgk",
      "th" => "tha",
      "ti" => "tir",
      "tk" => "tuk",
      "tl" => "tgl",
      "tn" => "tsn",
      "to" => "ton",
      "tr" => "tur",
      "ts" => "tso",
      "tt" => "tat",
      "tw" => "twi",
      "ty" => "tah",
      "ug" => "uig",
      "uk" => "ukr",
      "ur" => "urd",
      "uz" => "uzb",
      "ve" => "ven",
      "vi" => "vie",
      "vo" => "vol",
      "wa" => "wln",
      "wo" => "wol",
      "xh" => "xho",
      "yi" => "yid",
      "yo" => "yor",
      "za" => "zha",
      "zh" => "chi",
      "zu" => "zul",
  ];
  
  public static function toLang($locale) {

    //currently, only 'eng','rus','dut' seem to have translated descriptions at all (and even these are incomplete)
    if (substr($locale, 0, 2) === 'de') {
      return 'deu';
    }
    return FunctionsGov::LANGUAGES[substr($locale, 0, 2)] ?? 'deu';
  }
  
  //obsolete, descriptions now managed via owl file
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
  
  //obsolete, descriptions now managed via owl file
  public static function setTypeDescription($type, $lang, $description) {
    //updateOrInsert rather than insert in order to avoid concurrency issues
    DB::table('gov_descriptions')->updateOrInsert([
        'type' => $type,
        'lang' => $lang
    ], [    
        'description' => $description
    ]);
  }

  //obsolete, descriptions now managed via owl file
  public static function loadTypeDescription($module, $type, $lang) {
    if ($type == '...') {
      //type may not be set for all dates (although it should be, in clean GOV)
      return '...';
    }
    $description = SoapWrapper::getTypeDescription($module, $type, $lang);
    return $description->item[0];
  }
  
  /**
   * 
   * @return array key: type, value: array of languageTag:value
   */
  public static function getTypeDescriptions($module): array {
    return Registry::cache()->array()->remember('types.owl', function() use($module): array {
      return FunctionsGov::loadTypeDescriptionsFromFile($module);
    });
  }  
  
  protected static function loadTypeDescriptionsFromFile($module): array {
    $ret = [];
    
    $xml = simplexml_load_file($module->resourcesFolder() . 'lang/types.owl');
    
    $xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
    $xml->registerXPathNamespace('j.1', 'http://gov.genealogy.net/types.owl#');
    
    //not sure why some types have this namespace, seems like a bug
    $xml->registerXPathNamespace('gov', 'http://gov.genealogy.net/ontology.owl#');

    $paths = [
        '/rdf:RDF/gov:Type', 
        '/rdf:RDF/j.1:group_1',
        '/rdf:RDF/j.1:group_2',
        '/rdf:RDF/j.1:group_3',
        '/rdf:RDF/j.1:group_4',
        '/rdf:RDF/j.1:group_6',
        '/rdf:RDF/j.1:group_8',
        '/rdf:RDF/j.1:group_9',
        '/rdf:RDF/j.1:group_10',
        '/rdf:RDF/j.1:group_26',
        '/rdf:RDF/j.1:group_27',
        '/rdf:RDF/j.1:group_28',
        '/rdf:RDF/j.1:group_29',
        '/rdf:RDF/j.1:group_30',
        '/rdf:RDF/j.1:group_31',
        '/rdf:RDF/j.1:group_32'];
    
    foreach($paths as $path) {            
      foreach($xml->xpath($path) as $node) {
        $type = [];

        $key = $node->attributes('http://www.w3.org/1999/02/22-rdf-syntax-ns#')['about']->__toString();
        //error_log("key: ".$key);

        $node->registerXPathNamespace('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
        
        foreach($node->xpath('rdfs:label') as $label) {
          $languageTag = $label->attributes('http://www.w3.org/XML/1998/namespace')['lang']->__toString();
          //error_log("languageTag: ".$languageTag);
          $value = $label->__toString();
          //error_log("value: ".$value);

          $type[$languageTag] = $value;
        }      

        $ret[$key] = $type;
      }
    }  
    
    return $ret;
  }
  
  /*
  public static function retrieveTypeDescription(
          $module, 
          $type, 
          LocaleInterface $locale): ?string {
    
    $typeDescriptions = self::getTypeDescriptions($module);
    
    $key = "http://gov.genealogy.net/types.owl#".$type;
    
    if (array_key_exists($key, $typeDescriptions)) {
      $values = $typeDescriptions[$key];
      
      $languageTag = $locale->languageTag();
      
      if (array_key_exists($languageTag, $values)) {
        return $values[$languageTag];
      }
      
      //fallback to "en":
      if (array_key_exists("en", $values)) {
        return $values["en"];
      }
      
      //fallback to "de":
      if (array_key_exists("de", $values)) {
        return $values["de"];
      }
      
      //fallback to any:
      if (!empty($values)) {
        return reset($values);
      }
    }
    
    return null;
  }
  */
  
  public static function resolveTypeDescription(
          $module, 
          $type, 
          array $languages): ?string {
    
    $typeDescriptions = self::getTypeDescriptions($module);
    
    $key = "http://gov.genealogy.net/types.owl#".$type;
    
    if (array_key_exists($key, $typeDescriptions)) {
      $values = $typeDescriptions[$key];
      
      $inverse = array_flip(FunctionsGov::LANGUAGES);
      
      foreach ($languages as $lang) {
        $lang = strtolower($lang);
        
        if ($lang === 'deu') {
          $lang = 'ger';
        }
        if (array_key_exists($lang, $inverse)) {
          $languageTag = $inverse[$lang];
          
          if (array_key_exists($languageTag, $values)) {
            return $values[$languageTag];
          }
        }
      }
      
      //fallback to any:
      if (!empty($values)) {
        return reset($values);
      }
    }
    
    return null;
  }
  
  public static function retrieveGovObject($module, $id) {
    $gov = FunctionsGov::getGovObject($id);
    if ($gov != null) {
      return $gov;
    }

    //not loaded at all
    $gov = FunctionsGov::loadGovObject($module, $id);
    if ($gov == null) {
      return null;
    }

    FunctionsGov::setGovObject($id, $gov);
    return FunctionsGov::getGovObject($id);
  }

  //should only be used internally! use retrieveGovObject instead!
  public static function getGovObject($id): ?GovObject {
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

  public static function getTransitiveParentIds($id) {
    $ret = new Collection();
    $ret->add($id);

    $queue = [];        
    $queue[]=$id;

    while (!empty($queue)) {
      $current = $queue;
      
      $rows = DB::table('gov_parents')
            ->whereIn('gov_id', $current)
            ->get();

      $queue = [];
      foreach ($rows as $row) {
        $parentId = $row->parent_id;        
        if (!$ret->contains($parentId)) {
          $ret->add($parentId);
          $queue[]=$parentId;
        }
      }
    }

    return $ret->toArray();
  }
  
  //usually in order to trigger reloading
  public static function deleteGovObject($id, bool $hierarchy = true) {
    if ($hierarchy) {
      $ids = FunctionsGov::getTransitiveParentIds($id);
      DB::table('gov_objects')
            ->whereIn('gov_id', $ids)
            ->delete();
      return;
    }
    
    DB::table('gov_objects')
            ->where('gov_id', '=', $id)
            ->delete();
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

  public static function checkGovId($module, $id) {
    //check for existence, maybe return replacement id
    return SoapWrapper::checkObjectId($module, $id);
  }
  
  public static function loadGovObject($module, $id) {

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
    
    $rawParents = array();
    
    //we currently do not distinguish between the different kinds of relation!    
    if (property_exists($place, 'part-of')) {
      if (is_array($place->{'part-of'})) {
        foreach ($place->{'part-of'} as $parent) {
          $rawParents[] = $parent;
        }
      } else if ($place->{'part-of'} != null) {
        $rawParents[] = $place->{'part-of'};
      }
    }
    
    if (property_exists($place, 'located-in')) {
      if (is_array($place->{'located-in'})) {
        foreach ($place->{'located-in'} as $parent) {
          $rawParents[] = $parent;
        }
      } else if ($place->{'located-in'} != null) {
        $rawParents[] = $place->{'located-in'};
      }
    }
    
    if (property_exists($place, 'represents')) {
      if (is_array($place->{'represents'})) {
        foreach ($place->{'represents'} as $parent) {
          $rawParents[] = $parent;
        }
      } else if ($place->{'represents'} != null) {
        $rawParents[] = $place->{'represents'};
      }
    }
    
    foreach ($rawParents as $parent) {
      $from = FunctionsGov::getBeginAsJulianDate($parent);
      $to = FunctionsGov::getEndAsJulianDateExclusively($parent);
      $parents[] = new GovProperty($parent->ref, null, $from, $to);
    }
        
    $version = round(microtime(true) * 1000);
    return new GovObject($lat, $lon, $version, $types, $labels, $parents);
  }

  public static function getGovObjectLanguageOverrides(
          string $filename,
          string $id): array {

    $data = Registry::cache()->array()->remember(FunctionsGov::class . $filename, static function () use ($filename): array {
      if (file_exists($filename)) {
        $fp = fopen($filename, 'rb');
        if ($fp) {
          $datas = [];
          
          while (($data = fgetcsv($fp, 0, ';')) !== false) {
            if (sizeof($data) > 2) {
              $first = array_shift($data); //informational only (or comment)
              if (!str_starts_with($first, '#')) {
                $key = array_shift($data);
                $datas[$key] = $data;
              }            
            }            
          }
          fclose($fp);
          
          return $datas;
        }  
      }
        
      return [];
    });
    
    if (array_key_exists($id, $data)) {
      return $data[$id];
    }
    
    return [];
  }
  
  //should only be used internally! use retrieveGovObjectSnapshot instead!
  public static function getGovObjectSnapshot(
          $julianDay, 
          $id, 
          string $lang): ?GovObjectSnapshot {
    
    $row = DB::table('gov_objects')
            ->where('gov_id', '=', $id)
            ->first();

    if ($row == null) {
      return null;
    }

    $lat = $row->lat;
    $lon = $row->lon;
    
    //meh: column is int, why do we have to cast here? Use different driver?
    //https://stackoverflow.com/questions/38034996/find-on-model-gives-id-as-string-in-one-environment-and-int-in-other
    $version = (int)$row->version;

    $type = FunctionsGov::getTypeSnapshot($julianDay, $id);    
    
    $labels = FunctionsGov::retrieveLabels($julianDay, $id);
    
    $parents = FunctionsGov::getParents($id);
    
    return new GovObjectSnapshot(
            $lat, 
            $lon, 
            $version, 
            $type, 
            $labels, 
            $parents);
  }

  public static function getTypeSnapshot($julianDay, $id) {
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

  public static function retrieveLabels(
          $julianDay, 
          $id): array {
    
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

    $retrieved = [];
    foreach ($rows as $row) {
      $retrieved[$row->language] = $row->label;
    }
    
    return $retrieved;
  }
  
  /**
   * 
   * @param array $labelsIn
   * @param array $languages return label in first language from this list. 
   * Additionally return any other labels matching upper cased languages from this list.
   * As a final fallback, return any non-german label
   */
  public static function resolveLabels(
          array $labelsIn,
          array $languages):array {
    
    $main = null;
    $labelsOut = array();
    
    $usedLanguages = [];
    
    foreach ($languages as $language) {
      $lang = strtolower($language);
      if (!array_key_exists($lang, $usedLanguages)) {
        $usedLanguages[$lang] = $lang;
        
        $isAdditional = ($language !== $lang);
      
        if (array_key_exists($lang, $labelsIn)) {
          $label = $labelsIn[$lang];

          if ($main === null) {
            $main = $label;
          } else if ($isAdditional) {
            $labelsOut[] = $label;
          }
        }
      }      
    }
    
    $mainDeu = '...';
    if ($main === null) {
      foreach ($labelsIn as $key => $value) {
        if ($key === "deu") {
          $mainDeu = $value;         
        } else {
          $main = $value; //anything will do ... (but return a consistent fallback!)
          break;
        }
      }
    }
    
    if ($main === null) {
      $main = $mainDeu;
    }
    
    array_unshift($labelsOut, $main);
    return $labelsOut;
  }

  public static function retrieveGovObjectSnapshot(
          $module, 
          $julianDay, 
          $id, 
          int $version, 
          string $locale): ?GovObjectSnapshot {
    
    $lang = FunctionsGov::toLang($locale);

    $gov = FunctionsGov::getGovObjectSnapshot($julianDay, $id, $lang);
    if ($gov != null) {
      if ($gov->getVersion() >= $version) {
        return $gov;
      }
    }

    //version newer (or not loaded at all)
    $gov = FunctionsGov::loadGovObject($module, $id);
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
        $gov = FunctionsGov::loadGovObject($module, $id);
        if ($gov != null) {
          FunctionsGov::setGovObject($id, $gov);
        }
      }
    }
  }

  //TODO WARN IF AMBIGUOUS PARENTS (would have to be corrected in GOV directly)
  public static function findGovParentOfType(
          $module, 
          $id, 
          $gov, 
          $julianDay, 
          $types, 
          $version): ?string {
    
    $ids = array();
    foreach ($gov->getParents() as $parent) {
      $ids[] = $parent->getProp();
    }

    if (count($ids) == 0) {
      return null;
    }
    return FunctionsGov::findGovParentOfTypeViaIds($module, $id, $ids, $julianDay, $types, $version);
  }

  //TODO WARN IF AMBIGUOUS PARENTS (would have to be corrected in GOV directly)
  public static function findGovParentOfTypeViaIds(
          $module, 
          $id, 
          $ids, 
          $julianDay, 
          $types, 
          $version): ?string {
    
    if (count($types) == 0) {
      return null;
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
          if ($precision == 2) {
            return $jd + 1; //next day
          }

          if ($precision == 1) {
            //what a mess
            $ymd = cal_from_jd($jd, CAL_GREGORIAN);
            $dateTime = new DateTime();
            $dateTime->setDate($ymd["year"], $ymd["month"], $ymd["day"]);
            $dateTime->add(new DateInterval("P1M")); //next month
            $dateTime->format('Y-m-d');
            $jd = cal_to_jd(CAL_GREGORIAN, $dateTime->format("m"), $dateTime->format("d"), $dateTime->format("Y"));

            return $jd;
          }

          if ($precision == 0) {
            //what a mess
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

  public static function getGovTypeIds($module, LocaleInterface $locale): array {
    $types = new Collection(self::allTypes());
    
    [$overridesFilename, $languages, $languagesForTypes] = 
            $module->getLanguagesAndLanguagesForTypes($locale);
    
    return $types->mapWithKeys(static function (int $key) use ($module, $languagesForTypes): array {
                //$desc = FunctionsGov::retrieveTypeDescription($module, "".$key, $locale);
                $desc = FunctionsGov::resolveTypeDescription($module, "".$key, $languagesForTypes);
                if ($desc !== null) {
                  return [$key => $desc . " (" . $key . ")"];
                }
                return [$key => "".$key];
            })
            ->sort('\Fisharebest\Webtrees\I18N::strcasecmp')
            ->toArray();
    }
}
