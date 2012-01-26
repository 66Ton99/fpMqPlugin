<?php

require_once __DIR__ . '/../lib/fpMqFunction.php';
require_once __DIR__ . '/../lib/fpMqWorker.php';

fpMqFunction::loadConfig('config/fp_mq.yml');

function callService($message)
{
  var_dump($message);
}

$worker = new fpMqWorker('callService');
$worker->run();