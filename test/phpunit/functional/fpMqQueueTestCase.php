<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../autoload.php';

/**
 * Amazon SQS functional test case.
 *
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqQueueFnTestCase extends PHPUnit_Framework_TestCase
{

  static protected $message = array('test' => 'Test message');

  static protected $messageId;

  /**
   * Connection
   *
   * @var fpMqQueue
   */
  protected static $service;

  /**
   * @test
   *
   * @todo remove this message
   */
  public function resive_noOwn()
  {
    if (!fpMqFunction::loadConfig('config/fp_mq.yml')) {
      $this->markTestIncomplete('Now test works only in Symfony environment');
    }
    static::$service = fpMqQueue::getInstance();
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
    sfConfig::set('fp_mq_test', false);
    sfConfig::set('sf_environment', 'test');
    $options = sfConfig::get('fp_mq_driver_options');
    $options['sender'] = '';
    static::$service = fpMqQueue::init($options, sfConfig::get('fp_mq_amazon_url'));
    $this->assertNotNull(static::$service);

    static::$service->setOption(
      'queueUrl',
      sfConfig::get('fp_mq_amazon_url') . (empty($options['prefix'])?'':$options['prefix'] . '_') .
        sfConfig::get('fp_mq_amazon_sqs_test_queue')
    );
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
    static::$messageId = static::$service->send(static::$message, sfConfig::get('fp_mq_amazon_sqs_test_queue'));
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

