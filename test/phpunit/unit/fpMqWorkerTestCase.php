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
    $queueMock = $this->getMock('fpMqQueue', array('getName',  'receive'), array(), '', false);
    $queueMock->expects($this->exactly(2))
      ->method('getName')
      ->will(
        $this->returnValue(
          'http://Someurl.com/134435/' . static::QUEUENAME
        )
      );

    $obj = new stdClass();
    $obj->body = static::MESSAGE;
    $queueMock->expects($this->exactly(1))
      ->method('receive')
      ->will($this->returnValue(new ArrayIterator(array('test1' => $obj, 'test2' => $obj))));
    $queueMock->expects($this->never())
      ->method('callback')
      ->will($this->returnValue(true));

    $mock = $this->getMock('fpMqWorker', array(/* 'createFork',  */'deamonFactory', 'execute'), array(array($queueMock, 'callback'), $queueMock), '', true);
    $mockStatic = get_class($mock);
//     $mockStatic::staticExpects($this->exactly(2))
//       ->method('createFork')
// //       ->with(
// //         $this->arrayHasKey('body'),
// //         $this->stringContains(static::QUEUENAME)
// //       )
//     ;
    $mock->expects($this->exactly(2))
      ->method('execute');

    $mock->process();

    // TODO add more asserts
  }

}

