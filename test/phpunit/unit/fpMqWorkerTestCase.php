<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../lib/fpMqWorker.class.php';
require_once __DIR__ . '/../../../lib/fpMqFunction.class.php';

/**
 * test case.
 * @todo implement
 */
class fpMqWorkerTestCase //extends PHPUnit_Framework_TestCase
{
  
  
  
  const MESSAGE = 'test';
  const QUEUENAME = 'testQueue';
  
  public function callback($message, $queueName)
  {
    $this->assertEquals(static::MESSAGE, $message);
    $this->assertEquals(static::QUEUENAME, $queueName);
    exit;
  }
  
  /**
   * @test
   */
  public function createFork()
  {
    fpMqFunction::loadConfig('config/fp_mq.yml');
    $worker = new fpMqWorker(array($this, 'callback'));
    $worker->createFork(static::MESSAGE, static::QUEUENAME);
  }
  
//   /**
//    * @test
//    * @todo finish
//    */
//   public function process()
//   {
    
//     $queueMock = $this->getMock('Zend_Queue', array(), array(), '', false);
//     $queueMock->expects($this->once())
//       ->method('getQueues')
//       ->will($this->returnValue(array('http://Someurl.com/134435/' . static::QUEUENAME)));
//     $objArr = new ArrayObject(array());
//     $objArr->body = static::MESSAGE;
//     $queueMock->expects($this->once())
//       ->method('receive')
//       ->will($this->returnValue(new ArrayIterator(array($objArr))));
    
    
//     $workerClassName = 'fpMqWorker' . time();
//     $mock = $this->getMock('fpMqWorker', array(), array(), $workerClassName, false);
//     $mock->expects($this->any())
//         ->method('createFork')
//         ->with($this->stringContains(static::MESSAGE),
//                $this->stringContains(static::QUEUENAME));
//     $class = new ReflectionClass($workerClassName);
//     $queueProperty = $class->getProperty('queue');
//     $queueProperty->setAccessible(true);
//     $queueProperty->setValue($mock, $queueMock);
//     $callbackProperty = $class->getProperty('callback');
//     $callbackProperty->setAccessible(true);
//     $callbackProperty->setValue($mock, array($this, 'callback'));
    
//     $mock->process();
//   }

}

