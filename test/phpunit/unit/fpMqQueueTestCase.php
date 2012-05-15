<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../autoload.php';

/**
 * Amazon SQS unit test case.
 *
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqQueueTestCase extends PHPUnit_Framework_TestCase
{

  /**
   * @param array $queues
   *
   * @return fpMqQueue
   */
  protected function getMokedQueue($queues, $prefix = null, $getQueuesExpacts = 1)
  {
    $mockZendQueue = $this->getMocK('Zend_Queue', array('getQueues', 'getAdapter'), array(), '', false);
    $mockZendQueue->expects($this->exactly($getQueuesExpacts))
      ->method('getQueues')
      ->will($this->returnValue($queues));

    $mockQueue = $this->getMocK('fpMqQueue', array('getQueue'), array(), '', false);
    $mockQueue->expects($this->atLeastOnce())
      ->method('getQueue')
      ->will($this->returnValue($mockZendQueue));

    $mockQueue->setPrefix($prefix);

    return $mockQueue;
  }

  /**
   * @test
   */
  public function getQueues()
  {
    $queues = array('http://amazon.site/id/test_queue');
    $queue = $this->getMokedQueue($queues);
    $this->assertEquals(array('test_queue'), $queue->getQueues());
    $this->assertEquals(array('test_queue'), $queue->getQueues(), 'Cache does not work');
  }

  /**
   * @test
   */
  public function getQueues_prefix()
  {
    $queues = array(
      'http://amazon.site/id/test_queue',
      'http://amazon.site/id/test2_queue',
    );
    $queue = $this->getMokedQueue($queues, 'test');
    $this->assertEquals(array('queue'), $queue->getQueues());
  }

  /**
   * @test
   */
  public function getQueues_refrash()
  {
    $queues = array('http://amazon.site/id/test_queue');
    $queue = $this->getMokedQueue($queues, null, 2);
    $this->assertEquals(array('test_queue'), $queue->getQueues());
    $this->assertEquals(array('test_queue'), $queue->getQueues(true), 'Cannot clear cache');
  }

}

