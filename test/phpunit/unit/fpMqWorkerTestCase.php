<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../autoload.php';

/**
 * Test case of worker.
 */
class fpMqWorkerTestCase extends PHPUnit_Framework_TestCase
{

  const MESSAGE = 'test';
  const QUEUENAME = 'testQueue';

  public function callback($message, $queueName)
  {
    $this->assertEquals(static::MESSAGE, $message);
    $this->assertEquals(static::QUEUENAME, $queueName);
    exit; // It must stop thread but main test will continue work
  }

  /**
   * @test
   */
  public function createFork()
  {
    fpMqWorker::createFork(array(static::MESSAGE, static::QUEUENAME), array($this, 'callback'));
  }

  /**
   * @test
   */
  public function process()
  {
    $queueMock = $this->getMock('fpMqQueue', array('getQueues', 'receive'), array(), '', false);
    $queueMock->expects($this->once())
      ->method('getQueues')
      ->will(
        $this->returnValue(
          array(
            'http://Someurl.com/134435/' . static::QUEUENAME,
            'http://Someurl.com/134435/' . static::QUEUENAME . '2'
          )
        )
      );

    $obj = new stdClass();
    $obj->body = static::MESSAGE;
    $queueMock->expects($this->exactly(2))
      ->method('receive')
      ->will($this->returnValue(new ArrayIterator(array($obj))));
    $queueMock->expects($this->never())
      ->method('callback')
      ->will($this->returnValue(true));

    $mock = $this->getMock('fpMqWorker', array('createFork', 'deamonFactory'), array(array($queueMock, 'callback'), $queueMock), '', true);
    $mockStatic = get_class($mock);
    $mockStatic::staticExpects($this->exactly(2))
      ->method('createFork')
//       ->with(
//         $this->arrayHasKey('body'),
//         $this->stringContains(static::QUEUENAME)
//       )
    ;

    $mock->process();

    // TODO add more asserts
  }

}

