<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../autoload.php';

/**
 * Rabbit Message Queue functional test case.
 *
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqQueueRabbitmqFnTestCase extends PHPUnit_Framework_TestCase
{

  static protected $message = array('test' => 'Test message');

  static protected $messageId;

  static protected $testQueueName = 'queue';

  /**
   * Connection
   *
   * @var fpMqQueue
   */
  protected static $service;

  protected function getTestOptions()
  {
    $options = array();
    $options['class'] = 'Zend_Queue_Adapter_Rabbitmq';
    $options['sender'] = '';
    $options['options']['exchange']['name'] = 'topic';
    $options['prefix'] = 'test';
    return $options;
  }


  protected function init($options = array())
  {
    static::$service = null;
    $options = array_merge($this->getTestOptions(), $options);
    static::$service = fpMqQueue::init($options);
    $this->assertNotNull(static::$service);
    static::$service = static::$service->createQueue(static::$testQueueName);
  }

  /**
   * @todo add own queue for each test
   */
  protected function send()
  {
    static::$messageId = static::$service->send(static::$message, static::$testQueueName);
    $this->assertTrue((bool)static::$messageId);
  }

  protected function resive()
  {
    $responses = array();
    $i = 0;
    do
    {
      sleep(1);
      $responses = static::$service->receive('queue', 1, 3);
      $i++;
      if (3 < $i) $this->fail('Message does not resived');
    } while(!count($responses));
    $this->assertEquals(1, count($responses));
    $this->assertInstanceOf('Zend_Queue_Message', $response = $responses->current());
    $this->assertEquals(static::$message, $response->body->decode()); // TODO find better way
    return $response;
  }

  /**
   * @test
   *
   * @todo remove this message
   */
  public function resive_noOwn()
  {
    $this->markTestIncomplete('Some strange bugs in AMQP extension');
    $options = array('sender' => 'me');
    $this->init($options);
    $this->send();
    $this->init($options);

    $resived = true;
    try {
      $this->resive();
    } catch(PHPUnit_Framework_AssertionFailedError $e) {
      if ('Message does not resived' == $e->getMessage()) {
        $resived = false;
      } else {
        throw $e;
      }
    }
    $this->assertFalse($resived, 'Own message was resived');
  }

  /**
   * @test
   *
   * @depends resive_noOwn
   */
  public function send_and_recive()
  {
//     $this->init();
    $this->send();
//     $this->init();
//     $this->resive();
//     static::$service = null;
  }

  /**
   * @test
   * -runInSeparateProcess
   * @depends resive_noOwn
   */
  public function resive_andDelete()
  {
    $this->init();
    $messageHandle = $this->resive();
    $this->assertTrue(static::$service->deleteMessage($messageHandle));
  }

  /**
   * @test
   * @depends resive_andDelete
   *
   * @expectedException PHPUnit_Framework_AssertionFailedError
   * @expectedExceptionMessage Message does not resived
   */
  public function resive_isDeleted()
  {
//     $this->init();
    $this->resive();
  }

  public function __destruct()
  {
    $this->init();
    static::$service->deleteQueue();
  }
}

