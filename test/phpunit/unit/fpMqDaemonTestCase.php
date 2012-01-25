<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../lib/fpMqDaemon.php';

/**
 * test case.
 */
class fpMqDaemonTestCase extends PHPUnit_Framework_TestCase
{

  public function callback($daemon)
  {
    $this->assertInstanceOf('fpMqDaemon', $daemon);
    throw new ErrorException('OK');
  }
  
  /**
   * @test
   * @expectedException ErrorException
   */
  public function testRun()
  {
    $daemon = new fpMqDaemon(array($this, 'callback'));
    $daemon->run();
  }

}

