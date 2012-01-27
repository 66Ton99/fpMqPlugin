<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../lib/fpMqQueue.class.php';

/**
 * Amazon SQS test case.
 * @todo add own queue for each test
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqQueueTestCase extends PHPUnit_Framework_TestCase
{
  
  const MESSAGE = 'Test message';
  
  static protected $messageId;
  
  /**
   * Connection
   * 
   * @var fpMqQueue
   */
  protected static $service;
  
  /**
   * @test
   */
  public function connect()
  {
    static::$service = fpMqQueue::getInstance();
    $this->assertNotNull(static::$service);
  }
  
  /**
   * @test
   * @depends connect
   */
  public function send()
  {
    static::$messageId = static::$service->send(static::MESSAGE, 'testQueue');
  }
  
  /**
   * @test
   * @depends send
   */
  public function resive()
  {
    $responses = array();
    $i = 0;
    do
    {
      sleep(1);
      $responses = static::$service->receive(1, 3);
      $i++;
      if (10 < $i) $this->fail('Message does not resived');
    } while(!count($responses));
    $this->assertEquals(1, count($responses));
    $this->assertInstanceOf('Zend_Queue_Message', $response = $responses->current());
    $this->assertEquals(static::MESSAGE, json_decode($response->body)); // TODO find better way
    return $response;
  }
  
  /**
   * @test
   * @depends resive
   */
  public function resive_andDelete()
  {    
    $messageHandle = $this->resive();
    $this->assertTrue(static::$service->deleteMessage($messageHandle));
  }

}

