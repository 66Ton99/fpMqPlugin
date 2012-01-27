<?php

require_once(dirname(__FILE__) . '/../../config/ProjectConfiguration.class.php');
$configuration = ProjectConfiguration::getApplicationConfiguration('frontend', 'prod', false); //TODO change to prod
sfContext::createInstance($configuration);

// TODO:
// chdir(realpath(dirname(__FILE__) . '/..'));
// require_once(dirname(__FILE__) . '/../config/ProjectConfiguration.class.php');
// require_once(dirname(__FILE__).'/../lib/vendor/symfony/lib/autoload/sfCoreAutoload.class.php');
// sfCoreAutoload::register();
// // $dispatcher = new sfEventDispatcher();
// // $logger = new sfCommandLogger($dispatcher);

// SellerTable::getInstance(); // Now it doesn't work
