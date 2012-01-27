<?php

require_once __DIR__ . '/lib/fpMqFunction.class.php';
require_once __DIR__ . '/lib/fpMqWorker.class.php';

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
  echo 'Message: ', print_r($message, true), "\n";
  return true;
}

$worker = new fpMqWorker('callService');
$worker->run();