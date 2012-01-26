<?php

require_once __DIR__ . '/../lib/fpMqFunction.php';
require_once __DIR__ . '/../lib/fpMqWorker.php';

fpMqFunction::loadConfig('config/fp_mq.yml');

/**
 * Call service
 *
 * @param string $message
 * @param string $queueName
 *
 * @return bool
 */
function callService($message, $queueName)
{
  echo 'Queue name: ', $queueName, "\n";
  echo 'Message: ', $message, "\n";
  return true;
}

$worker = new fpMqWorker('callService');
$worker->run();