<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../lib/fpMqDaemon.class.php';

/**
 * test case.
 *
 * @todo write tests
 */
class fpMqDaemonTestCase extends PHPUnit_Framework_TestCase
{

  private $iterator = 0;

  private $daemon;

  public function callback()
  {
    if (2 <= $this->iterator) {
      throw new ErrorException('OK');
    }
    $this->iterator++;
  }

  /**
   * @test
   * @expectedException ErrorException
   * @expectedExceptionMessage OK
   */
  public function testRun()
  {
    $this->daemon = new fpMqDaemon(array($this, 'callback'));
    $this->daemon->run();
  }
}

