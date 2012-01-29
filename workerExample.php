<?php

// Run in background:
// nohup php workerExample.php > /dev/null &

require_once __DIR__ . '/workerBase.php';

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