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
    $options['prefix'] = 'test'; // TODO fixed
    return $options;
  }

  /**
   * @test
   */
  public function init()
  {
//     $this->markTestIncomplete('Need to finish');
    $options = $this->getTestOptions();
    $options['sender'] = 'me';
    static::$service = fpMqQueue::init($options);
    $this->assertNotNull(static::$service);
    if (!static::$service->isExists(static::$testQueueName))
    {
      static::$service = static::$service->createQueue(static::$testQueueName);
    }
  }

  /**
   * @test
   *
   * @depends init
   * @todo remove this message
   */
  public function resive_noOwn()
  {
    $this->send();
    $resived = true;
    try {
      $this->resive();
    } catch(PHPUnit_Framework_AssertionFailedError $e) {
      if ('Message does not resived' == $e->getMessage()) {
          $resived = false;
      }
    }
    $this->assertFalse($resived, 'Own message was resived');
  }

  /**
   * @test
   * @depends resive_noOwn
   */
  public function connect()
  {
    $options = $this->getTestOptions();
    static::$service = fpMqQueue::init($options);
    $this->assertNotNull(static::$service);

    static::$service->setOption('name', 'queue');
    $messageHandle = $this->resive(); // resive previos message and delete
    $this->assertTrue(static::$service->deleteMessage($messageHandle));
  }

  /**
   * @test
   * @depends connect
   *
   * @todo add own queue for each test
   */
  public function send()
  {
    static::$messageId = static::$service->send(static::$message, static::$testQueueName);
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
      $responses = static::$service->receive('queue', 1, 3);
      $i++;
      if (10 < $i) $this->fail('Message does not resived');
    } while(!count($responses));
    $this->assertEquals(1, count($responses));
    $this->assertInstanceOf('Zend_Queue_Message', $response = $responses->current());
    $this->assertEquals(static::$message, $response->body); // TODO find better way
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

  /**
   * @test
   * @depends resive
   *
   * @expectedException PHPUnit_Framework_AssertionFailedError
   * @expectedExceptionMessage Message does not resived
   */
  public function resive_isDeleted()
  {
    $this->resive();
  }
}

