<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../lib/autoload.php';

/**
 * Amazon SQS test case.
 *
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
    $this->markTestIncomplete('Find the way get configuration');
    static::$service = fpMqQueue::init(array('id' => '', 'key' => ''), '');
    $this->assertNotNull(static::$service);
  }

  /**
   * @test
   * @depends connect
   *
   * @todo add own queue for each test
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

