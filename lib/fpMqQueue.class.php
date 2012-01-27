<?php

require_once __DIR__ . '/fpMqFunction.class.php';
require_once __DIR__ . '/fpMqAmazonQueue.class.php';
require_once 'Zend/Queue.php';
require_once 'Zend/Queue/Message/Iterator.php';
require_once 'Zend/Queue/Message.php';


/**
 * Decorator for Zend_Queue
 * 
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqQueue
{
  /**
   * Object instance
   *
   * @var Queue
   */
  protected static $instance;
  
  protected $zendQueue;
  
  /**
   * Constructor
   *
   * @return void
   */
  protected function __construct()
  {
    fpMqFunction::loadConfig('config/fp_mq.yml');
    $options = sfConfig::get('fp_mq_driver_options');
    $driver = new fpMqAmazonQueue($options);
    $this->zendQueue = new Zend_Queue($driver);
  }
  
  /**
   * Return singleton
   *
   * @return Queue
   */
  public static function getInstance()
  {
    if (empty(static::$instance)) {
      static::$instance = new static();
    }
    return static::$instance;
  }
  
  /**
   * 
   *
   * @param mixed $data
   *
   * @return fpMqQueue
   */
  public function send($data, $queue)
  {
    $this->zendQueue->setOption('queueUrl', sfConfig::get('fp_mq_amazon_url') . $queue);
    $this->zendQueue->send(json_encode($data));
    return $this;
  }
  
  /**
   * Decorated object
   *
   * @var object
   */
  protected $object = null;
  
  /**
   * Magic method
   *
   * @param string $method
   * @param array $params
   *
   * @throws sfException
   *
   * @return mixed
   */
  public function __call($method, $params)
  {
    if (!method_exists($this->zendQueue, $method)) {
      throw new sfException("Called '{$method}' method does not exist in " . get_class($this));
    }
    $return = call_user_func_array(array($this->zendQueue, $method), $params);
    return $return;
  }
  
}