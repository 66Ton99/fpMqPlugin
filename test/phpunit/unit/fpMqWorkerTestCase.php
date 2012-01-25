<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../lib/fpMqWorker.php';

/**
 * test case.
 */
class fpMqWorkerTestCase extends PHPUnit_Framework_TestCase
{
  
  /**
   * @test
   */
  public function createFork()
  {
    $stub = $this->getMock('fpMqWorker', array(), array(), 'fpMqWorker' . time(), false);
    $stub->createFork(array());
//     exit;
  }

}

