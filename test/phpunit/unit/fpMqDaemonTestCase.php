<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../lib/fpMqDaemon.php';

/**
 * test case.
 */
class fpMqDaemonTestCase extends PHPUnit_Framework_TestCase
{
  
  private $iterator = 0;

  public function callback()
  {
    if (2 <= $this->iterator) throw new ErrorException('OK');
    $this->iterator++;
  }
  
  /**
   * @test
   * @expectedException ErrorException
   * @expectedExceptionMessage OK
   */
  public function testRun()
  {
    $daemon = new fpMqDaemon(array($this, 'callback'));
    $daemon->run();
  }

}

