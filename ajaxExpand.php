<?php

namespace Cissee\Webtrees\Module\Gov4Webtrees;

//minimal initialization (cf index.php)
//for ajax requests, this is better wrt performance than using index.php itself!
//define('WT_ROOT', realpath(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR);
//define('WT_DEBUG_SQL', false);

require_once __DIR__ . '/../../vendor/autoload.php';

/*
  require_once "../../app/Webtrees.php";
  require_once "../../app/Database.php";
  require_once "../../app/Debugbar.php";
  require_once "../../app/Statement.php";
  require_once "../../vendor/symfony/http-foundation/Request.php";
 */

require_once "FunctionsGov.php";
require_once "AjaxRequests.php";
require_once "MinimalModule.php";

use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\Webtrees;
use Symfony\Component\HttpFoundation\Request;

// Connect to the database
// No config file? Abort
if (!file_exists(Webtrees::CONFIG_FILE)) {
  throw new Exception("invalid state.");
}

$database_config = parse_ini_file(Webtrees::CONFIG_FILE);

if ($database_config === false) {
  throw new Exception('Invalid config file: ' . Webtrees::CONFIG_FILE);
}

// Read the connection settings and create the database
Database::connect($database_config);

// Update the database schema, if necessary.
//should not be necessary here
//$module->updateSchema('\Fisharebest\Webtrees\Schema', 'WT_SCHEMA_VERSION', Webtrees::SCHEMA_VERSION);

use Cissee\Webtrees\Module\Gov4Webtrees\AjaxRequests;
use Cissee\Webtrees\Module\Gov4Webtrees\MinimalModule;

$module = new MinimalModule(basename(__DIR__));
//$nusoap = boolval($module->getSetting('USE_NUSOAP', '0'));
// The HTTP request.
$request = Request::createFromGlobals();

echo AjaxRequests::expand($module, $request);
