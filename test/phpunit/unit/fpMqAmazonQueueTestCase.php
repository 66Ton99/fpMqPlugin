<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Zend/Queue.php';
require_once 'Zend/Queue/Message/Iterator.php';
require_once 'Zend/Queue/Message.php';
require_once __DIR__ . '/../../../lib/fpMqAmazonQueue.class.php';
require_once __DIR__ . '/../../../lib/fpMqFunction.class.php';

/**
 * Amazon SQS test case.
 * @todo add own queue for each test
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqAmazonQueueTestCase extends PHPUnit_Framework_TestCase
{
  
  const MESSAGE = 'Test message';
  
  static protected $messageId; 
  
  /**
   * Connection
   * 
   * @var Zend_Queue
   */
  protected static $service;
  
  /**
   * @test
   */
  public function connect()
  {
    fpMqFunction::loadConfig('config/fp_mq.yml');
    $options = sfConfig::get('fp_mq_driver_options');
    $connection = new fpMqAmazonQueue($options);
    static::$service = new Zend_Queue($connection);
    static::$service->setOption('queueUrl', sfConfig::get('fp_mq_amazon_sqs_test_queue'));
    $this->assertNotNull(static::$service);
  }
  
  /**
   * @test
   * @depends connect
   */
  public function send()
  {
    static::$messageId = static::$service->send(static::MESSAGE);
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
    $this->assertEquals(static::MESSAGE, $response->body);
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

