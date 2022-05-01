<?php

use Composer\Autoload\ClassLoader;

$loader = new ClassLoader();
$loader->addPsr4('Cissee\\Webtrees\\Module\\Gov4Webtrees\\', __DIR__);
$loader->addPsr4('Cissee\\Webtrees\\Module\\Gov4Webtrees\\Model\\', __DIR__ . "/Model");
$loader->addPsr4('Cissee\\WebtreesExt\\Elements\\', __DIR__ . "/patchedWebtrees/Elements");
$loader->register();

//why don't we have to autoload Cissee\Webtrees\Module\Gov4Webtrees\Http\RequestHandlers?
