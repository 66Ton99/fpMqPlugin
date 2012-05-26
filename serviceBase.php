<?php

$environment = empty($environment)?'service':$environment;
require_once(dirname(__FILE__) . '/../../config/ProjectConfiguration.class.php');
$configuration = ProjectConfiguration::getApplicationConfiguration(
  empty($application)?'frontend':$application,
  $environment,
  'prod'!=$environment
);
sfContext::createInstance($configuration);

// TODO:
// chdir(realpath(dirname(__FILE__) . '/..'));
// require_once(dirname(__FILE__) . '/../config/ProjectConfiguration.class.php');
// require_once(dirname(__FILE__).'/../lib/vendor/symfony/lib/autoload/sfCoreAutoload.class.php');
// sfCoreAutoload::register();
// // $dispatcher = new sfEventDispatcher();
// // $logger = new sfCommandLogger($dispatcher);

// SellerTable::getInstance(); // Now it doesn't work
