<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

use Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers\GovData;
use DateInterval;
use DateTime;
use Exception;
use Fisharebest\Localization\Locale\LocaleInterface;
use Fisharebest\Webtrees\I18N;
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
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
use function route;
use function str_starts_with;

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

class GovIdPlus {
  
  private $id;
  private $hasLocalModifications;
  private $ambiguous;
  
  public function getId(): ?string {
    return $this->id;
  }
  
  public function getHasLocalModifications(): bool {
    return $this->hasLocalModifications;
  }
  
  //note: currently not evaluated anywhere, we could print a warning in this case
  public function getAmbiguous(): bool {
    return $this->ambiguous;
  }
  
  public function __construct(
          ?string $id, 
          bool $hasLocalModifications, 
          bool $ambiguous) {
    
    $this->id = $id;
    $this->hasLocalModifications = $hasLocalModifications;
    $this->ambiguous = $ambiguous;
  }
  
  public function withHasLocalModifications(
          bool $hasLocalModifications): GovIdPlus {
  
    return new GovIdPlus($this->getId(), $hasLocalModifications, $this->getAmbiguous());
  }
  
  public static function empty(): GovIdPlus {
    return new GovIdPlus(null, false, false);
  }
}

class GovObject {

  private $id;
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

  public function hasStickyProp(): bool {
    
    foreach ($this->getLabels() as $prop) {
      if ($prop->getSticky()) {
        return true;
      }
    }
    
    foreach ($this->getTypes() as $prop) {
      if ($prop->getSticky()) {
        return true;
      }
    }
    
    foreach ($this->getParents() as $prop) {
      if ($prop->getSticky()) {
        return true;
      }
    }
    
    return false;
  }
  
  public function __construct(
          $id, 
          $lat, 
          $lon, 
          $version, 
          $types, 
          $labels, 
          $parents) {
    
    $this->id = $id;
    $this->lat = $lat;
    $this->lon = $lon;
    $this->version = $version;
    $this->types = $types;
    $this->labels = $labels;
    $this->parents = $parents;
  }

  public function getResolvedLabel(
          array $languages): ResolvedProperty {
    
    $julianDay = FunctionsGov::todayAsJulianDay();
    
    //ok to use internal function here
    $snapshot = FunctionsGov::getGovObjectSnapshot($julianDay, $this->id);
    
    //$snapshot->getType();
            
    return FunctionsGov::getResolvedLabel(
            $snapshot->getLabels(), 
            $languages,
            $this->getLabels());
  }
  
  public function formatForAdminView(
          $module,
          array $languages): string {
    
    $julianDay = FunctionsGov::todayAsJulianDay();
    
    //ok to use internal function here
    $snapshot = FunctionsGov::getGovObjectSnapshot($julianDay, $this->id);
            
    $label = FunctionsGov::getResolvedLabel(
            $snapshot->getLabels(), 
            $languages,
            $this->getLabels());
    
    $type = $snapshot->getType();
    if (($type === null) && (sizeof($this->types) > 0)) {
      $typeProp = end($this->types); //last element is the most recent one
      $type = $typeProp;
    }
    
    if ($type === null) {
      $resolvedType = '?';
    } else {
      $resolvedType = FunctionsGov::resolveTypeDescription($module, $type->getProp(), $languages);
    }    
    
    $url = route(GovData::class, ['gov_id' => $this->id]);
    $html = '<a href="' . $url . '">' . $label->getProp() . ' (' . $resolvedType . ')' .'</a>';
    
    return $html;
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

  /**
   * 
   * @return array<ResolvedProperty>
   */
  public function getLabels():array {
    return $this->labels;
  }

  public function getType(): ?ResolvedProperty {
    return $this->type;
  }

  public function getParents() {
    return $this->parents;
  }

  public function __construct(
          $lat, 
          $lon, 
          int $version, 
          ?ResolvedProperty $type, 
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
      $str .= $label->getProp()->toString() . ";";
    }
    if (($this->getLat() != null) && ($this->getLon() != null)) {
      $str .= " location: (" . $this->getLat() . "; " . $this->getLon() . ").";
    }
    $str .= " (type " . $this->getType()->getProp() . ")";
    foreach ($this->getParents() as $parent) {
      $str .= " parent: " . $parent->toString() . ".";
    }

    return $str;
  }

}

class FunctionsGov {

  //TODO update regularly! Last update: 2021/05. Source: http://gov.genealogy.net/type/list and https://gov.genealogy.net/types.owl
  public static $MAX_KNOWN_TYPE = 277;
  
  //http://gov.genealogy.net/types.owl#group_1
  //with subs:
  //civil (apparently unused)
  //http://gov.genealogy.net/types.owl#group_2
  //adm0
  //http://gov.genealogy.net/types.owl#group_26
  //adm1 Bundesländer u.ä.
  //http://gov.genealogy.net/types.owl#group_27
  //adm2 Regierungsbezirke u.ä.
  //http://gov.genealogy.net/types.owl#group_28
  //adm3 Kreise (wie Deutschland heute), Amtshauptmannschaften, historische (kreisähnliche) Ämter u.ä.
  //http://gov.genealogy.net/types.owl#group_29
  //adm4 Ämter (Ebene zwischen Gemeinde und Kreis), Verwaltungsgemeinschaften, Verbandsgemeinden u.a.
  //http://gov.genealogy.net/types.owl#group_30
  //adm5 Gemeinde
  //http://gov.genealogy.net/types.owl#group_31
  //adm6 Strukturen unterhalb von Gemeinden
  //http://gov.genealogy.net/types.owl#group_32
  //excluding organizational, see below
  
  protected static $TYPES_ADM0 = array(31, 50, 56, /*71,*/ 72, 214);
  
  protected static $TYPES_ADM1 = array(7, 16, 25, 33, 34, 58, 59, 130, 133, 142, 160, 168, 221, 256 );
  
  protected static $TYPES_ADM2 = array(5, 10, 45, 46, 100, 101, 138, 161, 201, 211, 217, 222, 225);
  
  protected static $TYPES_ADM3 = array(32, 36, 37, 53, 57, 63, 75, 78, 88, 99, 110, 126, 134, 146, 149, 171, 185, 186, 203, 204, 207, 212, 223, 241, 239, 270);
      
  protected static $TYPES_ADM4 = array(1, 2, /*48,*/ /*94,*/ 97, 122, /*127,*/ 152, 205, 226, 259, 264, 266);
  
  protected static $TYPES_ADM5 = array(4, 14, 18, 76, 83, 85, 95, 108, 109, 136, 140, 143, 145, /*148,*/ 150, 154, 162, 163, 165, 169, 180, 213, 218, 227, 246, 255, 257, 258, 267, 268, 269, 271, 272, 273, 274, 275);
  
  protected static $TYPES_ADM6 = array(52, 144, 156, 164, 181, 247, 262);
  
  protected static $TYPES_WITHOUT_ADM = array(20, 22, 23, 38, 60, 61, 62, 73, /*77,*/ 80, 81, 82, 84, 86, 93, 112, 113, /*115, 116, 117,*/ 125, 128, 131, 135, 137, 157, 167, 170, 173, 174, 175, 176, /*177, 178, 179,*/ 182, 183, /*184,*/ 188, 189, 190, 191, 192, 194, 215, 216, 234, 235, 237, 240, 248, 251, 252, 254, 265, 276, 277);
  
  //20 countship adm0 (adm1)
  //22 dominion adm4?
  //23 duchy adm0 (adm1)
  //38 oblast adm1
  //60 principality adm0
  //61 grand duchy adm0
  //62 margravate adm0 (adm1)
  //71 confederation speziell über adm0
  //73 Landdrostei adm2
  //77 Reichskreis speziell über adm0/1
  //80 Landschaft adm3
  //81 monastery adm5
  //82 Domkapitel adm2? or org
  //84 Kirchspielvogtei adm4 or org
  //86 part province adm1 liegt brach?
  //93 Reichsstadt adm0 (adm1)
  //112 Gespanschaft adm2?
  //113 comitatus adm2? same as above
  //115 forestry org!
  //116 Oberförsterei org!
  //117 Unterförsterei org!
  //125 imperial abbey wie Reichsstadt adm0 (adm1)
  //128 Landgrafschaft adm0 (adm1)
  //131 Weichbild adm5
  //135 canton adm4
  //137 region (administrative unit) adm3
  //157 Guberniya adm4 u.a.
  //167 mandate territory adm0
  //170 district adm4
  //173 Reichskommissariat adm2 od. adm1
  //174 general district adm2
  //175 Kreisgebiet adm3 or adm4
  //176 protectorate adm1
  //177 Reichsritterschaft vgl. Reichskreis, org! 
  //178 Ritterkanton org!
  //179 Ritterkreis org!
  //182 Erzstift adm0/1
  //183 Hochstift adm0/1
  //184 Kammerschreiberei org!
  //188 Ritterorden adm0
  //189 Großpriorat adm2 v Ritterorden
  //190 Ballei adm3 v Ritterorden
  //191 Kommende adm4 v Ritterorden
  //192 zone of occupation adm0
  //194 Distrikts-Amt ???
  //215 Reichshälfte speziell über adm0/1
  //216 Landesteil adm1
  //234 borough adm4 (3?)
  //235 unitary authority adm3/4?
  //237 Siedlungsrat adm5
  //240 uyezd adm2/3
  //248 Schulzenamt adm6
  //251 autonome Gemeinschaft kein text
  //252 local government kein text
  //254 Okrug adm3
  //265 sultanate adm0
  //276 ???
  //277 colony (territory) adm0
  
  //extra 
  //223 Landgericht (älterer Ordnung) - primarily in Judicial
  //154 Honschaft - primarily in Judicial
  //181 Rotte - primarily settlement
  
  //own adjustment
  //
  //48 adm4 Samtgemeinde
  //71 no adm_ 'Staatenbund'
  //77 no adm_ 'Reichskreis' unclear
  //94 adm4 Verwaltungsgemeinschaft
  //115 forestry
  //116 Oberförsterei
  //117 Unterförsterei
  //127 adm4 Verwaltungsverband
  //148 adm4 Erfüllende Gemeinde
  //177 no adm_ Reichsritterschaft
  //178 no adm_ Ritterkanton
  //179 no adm_ Ritterkreis
  //184 no adm_ Kammerschreiberei
  //
  //plus all from adm6
  protected static $TYPES_ORGANIZATIONAL = array(48, 71, 77, 94, 115, 116, 117, 127, 148, 177, 178, 179, 184);
          
  //http://gov.genealogy.net/types.owl#group_3
  protected static $TYPES_RELIGIOUS = array(6, 9, 11, 12, 13, 26, 27, 28, 29, 30, 35, 41, 42, 43, 44, 82, 91, 92, 96, 124, 153, 155, 182, 183, 206, 219, 243, 244, 245, 249, 250, 253, 260, 263);
  
  //http://gov.genealogy.net/types.owl#group_8
  protected static $TYPES_SETTLEMENT = array(8, 17, 21, 24, 30, 39, 40, 51, 54, 55, 64, 65, 66, 67, 68, 69, 87, 102, 111, 120, 121, 129, 139, 158, 159, 181, 193, 229, 230, 231, 232, 233, 236, 238, 261);
  
  //http://gov.genealogy.net/types.owl#group_6
  protected static $TYPES_JUDICIAL = array(3, 19, 70, 79, 105, 114, 151, 154, 202, 223, 224, 228);
  
  //http://gov.genealogy.net/types.owl#group_4
  protected static $TYPES_GEOGRAPHIC = array(47, 107);
  
  //http://gov.genealogy.net/types.owl#group_10
  protected static $TYPES_PLACE = array(15, 89, 90, 166);
  
  //http://gov.genealogy.net/types.owl#group_9
  protected static $TYPES_TRANSPORTATION = array(118, 119);

  //http://gov.genealogy.net/types.owl#group_13
  //other (deprecated)
  
  //see also
  //http://wiki-de.genealogy.net/GOV/Mini-GOV
  //where only select adms are used
  
  public static function admTypes(): array {
    return array_merge(
            FunctionsGov::$TYPES_ADM0,
            FunctionsGov::$TYPES_ADM1,
            FunctionsGov::$TYPES_ADM2,
            FunctionsGov::$TYPES_ADM3, 
            FunctionsGov::$TYPES_ADM4,
            FunctionsGov::$TYPES_ADM5,
            FunctionsGov::$TYPES_WITHOUT_ADM);
  }
  
  public static function orgTypes(): array {
    return array_merge(
            FunctionsGov::$TYPES_ADM6,
            FunctionsGov::$TYPES_ORGANIZATIONAL);
  }
  
  public static function settlementTypes(): array {
    return FunctionsGov::$TYPES_SETTLEMENT;
  }
  
  public static function religiousTypes(): array {
    return FunctionsGov::$TYPES_RELIGIOUS;
  }
  
  public static function judicialTypes(): array {
    return FunctionsGov::$TYPES_JUDICIAL;
  }
  
  public static function otherTypes(): array {
    return array_merge(
                FunctionsGov::$TYPES_GEOGRAPHIC,
                FunctionsGov::$TYPES_PLACE,
                FunctionsGov::$TYPES_TRANSPORTATION);
  }
  
  public static function allTypes(): array {
    $ret = array_merge(
            FunctionsGov::admTypes(),
            FunctionsGov::orgTypes(),
            FunctionsGov::settlementTypes(),
            FunctionsGov::religiousTypes(), 
            FunctionsGov::judicialTypes(),
            FunctionsGov::otherTypes());
    
    return $ret;
  }
    
  public static function getAllGovTypeIds($module, LocaleInterface $locale): array {
    return FunctionsGov::mapGovTypeIds($module, $locale, FunctionsGov::allTypes());
  }
  
  public static function getGovTypeIdsByTypeGroup($module, LocaleInterface $locale): array {
    $typeGroups = [];    
    
    $typeGroups[I18N::translate('Administrative (level %1$s)','0')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::$TYPES_ADM0);

    $typeGroups[I18N::translate('Administrative (level %1$s)','1')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::$TYPES_ADM1);

    $typeGroups[I18N::translate('Administrative (level %1$s)','2')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::$TYPES_ADM2);

    $typeGroups[I18N::translate('Administrative (level %1$s)','3')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::$TYPES_ADM3);

    $typeGroups[I18N::translate('Administrative (level %1$s)','4')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::$TYPES_ADM4);

    $typeGroups[I18N::translate('Administrative (level %1$s)','5')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::$TYPES_ADM5);

    $typeGroups[I18N::translate('Administrative (other)')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::$TYPES_WITHOUT_ADM);
    
    $typeGroups[I18N::translate('Organizational')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::orgTypes());
    
    $typeGroups[I18N::translate('Settlement')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::settlementTypes());
    
    $typeGroups[I18N::translate('Religious')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::religiousTypes());
    
    $typeGroups[I18N::translate('Judicial')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::judicialTypes());
    
    $typeGroups[I18N::translate('Other')] = FunctionsGov::mapGovTypeIds(
            $module, $locale, FunctionsGov::otherTypes());
    
    return $typeGroups;
  }
  
  public static function mapGovTypeIds(
          $module, 
          LocaleInterface $locale, 
          array $typeIds): array {
    
    $languagesForTypes = 
            $module->getResolvedLanguagesForTypes($locale);
    
    $types = new Collection($typeIds);
    
    return $types->mapWithKeys(static function (int $key) use ($module, $languagesForTypes): array {
                //$desc = FunctionsGov::retrieveTypeDescription($module, "".$key, $locale);
                $desc = FunctionsGov::resolveTypeDescription($module, $key, $languagesForTypes);
                if ($desc !== null) {
                  return [$key => $desc . " (" . $key . ")"];
                }
                return [$key => "".$key];
            })
            ->sort('\Fisharebest\Webtrees\I18N::strcasecmp')
            ->toArray();
  }
  
  public static function clear() {
    DB::table('gov_objects')
            ->delete();

    DB::table('gov_labels')
            ->where('sticky', '=', false)
            ->delete();

    DB::table('gov_types')
            ->where('sticky', '=', false)
            ->delete();

    DB::table('gov_parents')
            ->where('sticky', '=', false)
            ->delete();

    //deprecated
    //DB::table('gov_descriptions')
    //        ->delete();
    
    //keep ids!
  }

  public static function allGovIds(): array {
    $rows = DB::table('gov_objects')
            ->get();

    $ret = array();
    foreach ($rows as $row) {
      $ret[$row->gov_id] = $row->gov_id;
    }

    return $ret;
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
            
            //newest first! (likely to be the most relevant)
            //do we instead need a way to mark mapping specifically as 'use for back-mapping'?
            //(in case same GOV id is actually and intentionally used for more than one place)
            ->orderByDesc('id') 
            
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

    //note: currently, only 'eng','rus','dut' seem to have translated descriptions at all (and even these are incomplete)
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
  
  public static function resolveTypeDescription(
          $module, 
          ?int $type, 
          array $languages): ?string {
    
    if ($type === null) {
      return null;
    }
        
    if ($type > FunctionsGov::$MAX_KNOWN_TYPE) {
      return I18N::translate('unknown GOV type (newly introduced?)');
    }

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
  
  public static function retrieveGovObject(
          $module, 
          $id,
          bool $forceReload = false): ?GovObject {
    
    if (!$forceReload) {
      $gov = FunctionsGov::getGovObject($id);
      if ($gov != null) {
        return $gov;
      }
    }    

    //not loaded at all, or force reload
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
    return new GovObject($id, $lat, $lon, $version, $types, $labels, $parents);
  }
  
  public static function setPropLabel(
          GovProperty $prop) {
    
    if ($prop->getSticky()) {      
      DB::table('gov_labels')
                ->where('key', '=', $prop->getKey())
                ->update([
                    'label' => $prop->getProp(),
                    'language' => $prop->getLanguage(),
                    'from' => $prop->getFrom(),
                    'to' => $prop->getTo(),
                ]);
    
      return;
    }
    
    //create new sticky row
    $insertData = [
            'gov_id' => $prop->getGovId(),
            'label' => $prop->getProp(),
            'language' => $prop->getLanguage(),
            'from' => $prop->getFrom(),
            'to' => $prop->getTo(),
            'sticky' => true,
        ];

    DB::table('gov_labels')->insert($insertData);    
  }
  
  public static function setPropType(
          GovProperty $prop) {
    
    if ($prop->getSticky()) {      
      DB::table('gov_types')
                ->where('key', '=', $prop->getKey())
                ->update([
                    'type' => $prop->getProp(),
                    'from' => $prop->getFrom(),
                    'to' => $prop->getTo(),
                ]);
    
      return;
    }
    
    //create new sticky row
    $insertData = [
            'gov_id' => $prop->getGovId(),
            'type' => $prop->getProp(),
            'from' => $prop->getFrom(),
            'to' => $prop->getTo(),
            'sticky' => true,
        ];

    DB::table('gov_types')->insert($insertData);    
  }
  
  public static function setPropParent(
          GovProperty $prop) {
    
    if ($prop->getSticky()) {      
      DB::table('gov_parents')
                ->where('key', '=', $prop->getKey())
                ->update([
                    'parent_id' => $prop->getProp(),
                    'from' => $prop->getFrom(),
                    'to' => $prop->getTo(),
                ]);
    
      return;
    }
    
    //create new sticky row
    $insertData = [
            'gov_id' => $prop->getGovId(),
            'parent_id' => $prop->getProp(),
            'from' => $prop->getFrom(),
            'to' => $prop->getTo(),
            'sticky' => true,
        ];

    DB::table('gov_parents')->insert($insertData);    
  }
  
  public static function deletePropIfSticky(
          string $type, 
          int $key) {
    
    if ($type == 'type') {
      DB::table('gov_types')
            ->where('key', '=', $key)
            ->where('sticky', '=', true)
            ->delete();
      
      return;
    }
    
    if ($type == 'label') {
      DB::table('gov_labels')
            ->where('key', '=', $key)
            ->where('sticky', '=', true)
            ->delete();
      
      return;
    }
    
    if ($type == 'parent') {
      DB::table('gov_parents')
            ->where('key', '=', $key)
            ->where('sticky', '=', true)
            ->delete();
      
      return;
    }
  }
  
  public static function getProp(
          string $type, 
          int $key): ?GovProperty {
    
    if ($type == 'type') {
      $row = DB::table('gov_types')
            ->where('key', '=', $key)
            ->first();
      
      if ($row !== null) {
        return new GovProperty($row->key, $row->gov_id, $row->type, null, $row->from, $row->to, $row->sticky);
      }
    } else if ($type == 'label') {
      $row = DB::table('gov_labels')
            ->where('key', '=', $key)
            ->first();
      
      if ($row !== null) {
        return new GovProperty($row->key, $row->gov_id, $row->label, $row->language, $row->from, $row->to, $row->sticky);
      }
    } else if ($type == 'parent') {
      $row = DB::table('gov_parents')
            ->where('key', '=', $key)
            ->first();
      
      if ($row !== null) {
        return new GovProperty($row->key, $row->gov_id, $row->parent_id, null, $row->from, $row->to, $row->sticky);
      }
    }
    
    return null;
  }
  
  /**
   * return array (of GovProperty)
   */
  public static function getTypes($id) {
    $rows = DB::table('gov_types')
            ->where('gov_id', '=', $id)
            ->orderByDesc('sticky')
            ->orderBy('from')
            ->orderBy('to')
            ->get();

    $props = array();
    foreach ($rows as $row) {
      $props[] = new GovProperty($row->key, $row->gov_id, $row->type, null, $row->from, $row->to, $row->sticky);
    }

    return $props;
  }

  public static function getLabels($id) {
    $rows = DB::table('gov_labels')
            ->where('gov_id', '=', $id)
            ->orderByDesc('sticky')
            ->orderBy('from')
            ->orderBy('to')
            ->orderBy('language')
            ->get();

    $props = array();
    foreach ($rows as $row) {
      $props[] = new GovProperty($row->key, $row->gov_id, $row->label, $row->language, $row->from, $row->to, $row->sticky);
    }

    return $props;
  }

  public static function getParents($id) {
    $rows = DB::table('gov_parents')
            ->where('gov_id', '=', $id)
            ->orderByDesc('sticky')
            ->orderBy('from')
            ->orderBy('to')
            ->get();

    $props = array();
    foreach ($rows as $row) {
      $props[] = new GovProperty($row->key, $row->gov_id, $row->parent_id, null, $row->from, $row->to, $row->sticky);
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
            ->where('sticky', '=', false)
            ->delete();

    DB::table('gov_labels')
            ->where('gov_id', '=', $id)
            ->where('sticky', '=', false)
            ->delete();

    DB::table('gov_parents')
            ->where('gov_id', '=', $id)
            ->where('sticky', '=', false)
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
  
  public static function loadGovObject($module, $id): ?GovObject {

    //error_log("LOAD " . $id);
    
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
      
      $type = $place->position->type;
      if ('c' == $type) {
        //calculated, drop excessive precision which is mainly an artifact of floating point representation anyway
        //4 decimal places ~10 metres
        $lat = round($lat, 4);
        $lon = round($lon, 4);
      }
    }
    $types = array();
    $labels = array();
    $parents = array();

    if (property_exists($place, "type")) {
      if (is_array($place->type)) {
        foreach ($place->type as $key => $type) {
          $from = FunctionsGov::getBeginAsJulianDate($type);
          $to = FunctionsGov::getEndAsJulianDateExclusively($type);
          $types[] = new GovProperty(-1, $id, $type->value, null, $from, $to, false);
        }
      } else if ($place->type != null) {
        $from = FunctionsGov::getBeginAsJulianDate($place->type);
        $to = FunctionsGov::getEndAsJulianDateExclusively($place->type);
        $types[] = new GovProperty(-1, $id, $place->type->value, null, $from, $to, false);
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
          $labels[] = new GovProperty(-1, $id, $label->value, $lang, $from, $to, false);
        }
      } else if ($place->name != null) {
        $from = FunctionsGov::getBeginAsJulianDate($place->name);
        $to = FunctionsGov::getEndAsJulianDateExclusively($place->name);
        $lang = null;
        if (property_exists($place->name, "lang")) {
          $lang = $place->name->lang;
        }
        $labels[] = new GovProperty(-1, $id, $place->name->value, $lang, $from, $to, false);
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
      $parents[] = new GovProperty(-1, $id, $parent->ref, null, $from, $to, false);
    }
        
    $version = round(microtime(true) * 1000);
    return new GovObject($id, $lat, $lon, $version, $types, $labels, $parents);
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
  
  //should only be used internally (or if you're certain the $id is loaded)! 
  //otherwise use retrieveGovObjectSnapshot instead!
  public static function getGovObjectSnapshot(
          $julianDay, 
          $id): ?GovObjectSnapshot {
    
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

  public static function getTypeSnapshot(
          string $julianDay, 
          string $id): ?ResolvedProperty {
    
    $row = DB::table('gov_types')
            ->where('gov_id', '=', $id)
            ->where(function($q) use ($julianDay) {
              $q->whereNull('from')->orWhere('from', '<=', $julianDay);
            })
            ->where(function($q) use ($julianDay) {
              $q->whereNull('to')->orWhere('to', '>', $julianDay);
            })
            ->orderBy('sticky', 'desc') //prefer sticky
            ->first();

    if ($row == null) {
      return null;
    }

    return new ResolvedProperty($row->type, $row->sticky);
  }

  /**
   * 
   * @param type $julianDay
   * @param type $id
   * @param bool $restrictToSticky
   * @return array<ResolvedProperty>
   */
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
    
    //pick any, per language
    foreach ($rows as $row) {
      //but prefer stickies
      if (!array_key_exists($row->language, $retrieved) || $row->sticky) {
        $retrieved[$row->language] = new ResolvedProperty($row->label, $row->sticky);
      }
    }
    
    return $retrieved;
  }
  
  /**
   * 
   * @param array<string => ResolvedProperty> $labels (keyed by language)
   * @param array $languages
   * @return ResolvedProperty
   */
  public static function getResolvedLabel(
          array $labels,
          array $languages,
          array $fallbackLabels = null): ResolvedProperty {
    
    $resolvedLabels = FunctionsGov::resolveLabels($labels, $languages, $fallbackLabels);
    $label = array_shift($resolvedLabels);
    
    if (sizeof($resolvedLabels) === 0) {
      return $label;
    }    
    
    $add = [];
    $sticky = $label->getSticky();
    
    foreach ($resolvedLabels as $additional) {
      $add []= $additional->getProp();
      $sticky = $sticky || $additional->getSticky();
    }
    
    return new ResolvedProperty(
            $label->getProp() . ' ('. implode('/', $add) . ')', 
            $sticky);
  }
  
  /**
   * 
   * @param array<string => ResolvedProperty> $labelsIn (keyed by language)
   * @param array $languages return label in first language from this list. 
   * Additionally return any other labels matching upper cased languages from this list.
   * As a final fallback, return any non-german label
   * 
   * @return array<ResolvedProperty>
   * 
   */
  public static function resolveLabels(
          array $labelsIn,
          array $languages,
          array $fallbackLabels = null): array {
    
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
    
    $mainDeu = new ResolvedProperty('...', false);
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
      if ($fallbackLabels !== null) {
        return FunctionsGov::resolveLabels($fallbackLabels, $languages);
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
          int $version): ?GovObjectSnapshot {
    
    //$lang = FunctionsGov::toLang($locale);

    $gov = FunctionsGov::getGovObjectSnapshot($julianDay, $id);
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
    return FunctionsGov::getGovObjectSnapshot($julianDay, $id);
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
        $gov = FunctionsGov::loadGovObject($module, $id);
        if ($gov != null) {
          FunctionsGov::setGovObject($id, $gov);
        }
      }
    }
  }

  public static function findGovParentOfType(
          $module, 
          string $id, 
          /*GovObject|GovObjectSnapshot*/ $gov, 
          $julianDay,
          $types, 
          $version): GovIdPlus {
    
    $ids = array();
    foreach ($gov->getParents() as $parent) {
      $ids[] = $parent->getProp();
    }

    if (count($ids) == 0) {
      return GovIdPlus::empty();
    }
    return FunctionsGov::findGovParentOfTypeViaIds(
            $module, 
            $id, 
            $ids, 
            $julianDay, 
            $types, 
            $version);
  }

  public static function findGovParentOfTypeViaIds(
          $module, 
          string $id, 
          $ids, 
          $julianDay, 
          array $types, 
          $version): GovIdPlus {
    
    if (count($types) == 0) {
      return null;
    }

    FunctionsGov::loadNonLoaded($module, $ids, $version);

    ////////

    $rows = DB::table('gov_parents')
            ->join('gov_types', 'gov_parents.parent_id', '=', 'gov_types.gov_id')
            ->where('gov_parents.gov_id', '=', $id)
            ->where(function($q) use ($types) {
              //sticky type supersedes other definitions, so we must load as well
              $q->whereIn('gov_types.type', $types)->orWhere('gov_types.sticky', '=', true);
            })
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
            ->select('parent_id', 'gov_types.type', 'gov_parents.sticky as sticky_parent')
            ->get();

    if (sizeof($rows) == 0) {
      return GovIdPlus::empty();
    }        

    //disregard ids with a non-matching (sticky) type (non-matching non-sticky aren't returned at all),
    //even if there are other entries for this id
    
    $disregardedIds = [];
    foreach ($rows as $row) {
      //error_log("disregard? type = " . $row->type);
      if (!in_array($row->type, $types)) {
        //error_log("disregard: " . $row->parent_id);
        $disregardedIds []= $row->parent_id;
      }
    }
    
    $id1 = FunctionsGov::fromRow($rows, $disregardedIds);
    
    if (!$id1->getHasLocalModifications() && sizeof($disregardedIds) !== 0) {
      //hasLocalModifications via disregarded id? Including case where null id is returned
      $id2 = FunctionsGov::fromRow($rows, []);
      
      if ($id1->getId() !== $id2->getId()) {
        return $id1->withHasLocalModifications(true);
      }
    }
    
    return $id1;
  }

  protected function fromRow(
          $rows,
          $disregardedIds): GovIdPlus {
   
    //no particular order, but prefer sticky entries
    $sticky = false;
    $ambiguous = false;
    $parentId = null;
    
    foreach ($rows as $row) {
      if (in_array($row->parent_id, $disregardedIds)) {
        continue;
      }
      
      if ($row->sticky_parent) {
        if (!$sticky) {
          //first sticky
          $sticky = true;
          $ambiguous = false; //reset, non-stickies are irrelevant
          $parentId = $row->parent_id;
        } else {
          //further ambiguous sticky
          $ambiguous = true;
        }
      } else {
        if (!$sticky) {
          if ($parentId === null) {
            //first non-sticky
            $parentId = $row->parent_id;
          } else {
            //further ambiguous non-sticky
            $ambiguous = true;
          }
        } else {
          //disregard
        }
      }
      
      //error_log("parent of " . $id . " is " . $parentId);
      //error_log("with sticky_parent" . $row->sticky_parent);
      //
      //error_log("intermediate result");
      //error_log("sticky? " . $sticky);
      //error_log("ambiguous? " . $ambiguous);
      //error_log("parentId: " . $parentId);
    }
    
    if ($parentId === null) {
      return GovIdPlus::empty();
    }
    
    return new GovIdPlus($parentId, $sticky, $ambiguous);
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
  
  public static function getGovLanguages(): array {
    //seems sensible to restrict to this
    $activeLocales = new Collection(I18N::activeLocales());
    
    return $activeLocales
            ->sort(static function (LocaleInterface $x, LocaleInterface $y): int {
                return $x->endonymSortable() <=> $y->endonymSortable();
            })
            
            //TODO should merge multiple endonyms here (AE/BE etc) 
            ->mapWithKeys(static function (LocaleInterface $locale): array {
              $lang = FunctionsGov::toLang($locale->languageTag());
              $endonym = $locale->endonym();
              return [$lang => $endonym];
            })
            ->unique()
            ->toArray();
  }
  
  public static function aToGovServer(
          string $id,
          string $label,
          ?string $title = null): string {
    
    $str = '>';
    if ($title !== null) {
      $str = ' title="' . $title . '">';
    }
    
    return '<a href="http://gov.genealogy.net/item/show/' . $id . '" target="_blank"' . $str . $label . '</a>';
  }
  
  public static function todayAsJulianDay(): string {
    $dateTime = new DateTime();
    $dateTime->format('Y-m-d');
    return cal_to_jd(
              CAL_GREGORIAN, 
              $dateTime->format("m"), 
              $dateTime->format("d"), 
              $dateTime->format("Y"));
  }
}
