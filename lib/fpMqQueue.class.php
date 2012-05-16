<?php

/**
 * Decorator-Wrapper for Zend_Queue
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

  /**
   * @var array
   */
  protected $queuesList = null;

  /**
   * @var Zend_Queue
   */
  protected $zendQueue;

  /**
   * @var string
   */
  protected $amazonUrl;

  protected $sender;

  protected $prefix;

  protected $driverClassName = 'Zend_Queue_Adapter_Memcacheq';

  /**
   * Constructor
   *
   * @return void
   */
  protected function __construct(array $options)
  {

    $options = array_merge(array('options' => array()), $options);

    if (!empty($options['sender'])) {
      $this->sender = $options['sender'];
    }
    if (!empty($options['prefix'])) {
      $this->prefix = $options['prefix'];
    }
    if (!empty($options['options']['name'])) {
      $this->amazonUrl = $options['options']['name'];
    }
    if (!empty($options['class'])) {
      $this->driverClassName = $options['class'];
    }
    $this->zendQueue = $this->queueFactory($this->driverFactory($options['options']));
  }

  /**
   * Sets prefix
   *
   * @param string $prefix
   *
   * @return fpMqQueue
   */
  public function setPrefix($prefix)
  {
    $this->prefix = $prefix;
    return $this;
  }

  /**
   *
   * @return Zend_Queue
   */
  public function getQueue()
  {
    return $this->zendQueue;
  }


  /**
   * Magic method which transports all methods to connected class
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
    if (!method_exists($this->getQueue(), $method)) {
      require_once __DIR__ . '/fpMqException.class.php';
      throw new fpMqException("Called '{$method}' method does not exist in " . get_class($this));
    }
    $return = call_user_func_array(array($this->getQueue(), $method), $params);
    return $return;
  }

  /**
   * Main wrapper factory
   *
   * @param Zend_Queue_Adapter_AdapterAbstract $driver
   *
   * @return Zend_Queue
   */
  protected function queueFactory(Zend_Queue_Adapter_AdapterAbstract $driver)
  {
    return new Zend_Queue($driver);
  }

  /**
   * Factory of driver
   *
   * @param array $options
   *
   * @return Zend_Queue_Adapter_AdapterAbstract
   */
  protected function driverFactory(array $options)
  {
    $class = $this->driverClassName;
    return new $class($options);
  }

  /**
   * Initialization
   *options
   * @param array $options
   * @param strings $amazonUrl
   *
   * @return fpMqQueue
   */
  public static function init(array $options)
  {
    return static::$instance = new static($options);
  }

  /**
   * Initialization for symfony
   *
   * @param array $options
   * @param strings $amazonUrl
   *
   * @return void
   */
  public static function sfInit()
  {
    if (!fpMqFunction::loadConfig('config/fp_mq.yml')) return false;
    static::$instance = new static(sfConfig::get('fp_mq_driver'));
    return true;
  }

  /**
   * Return singleton
   *
   * @return fpMqQueue
   */
  public static function getInstance()
  {
    if (empty(static::$instance) && !static::sfInit()) {
      require_once __DIR__ . '/fpMqException.class.php';
      throw new fpMqException('You must initialize it at first');
    }
    return static::$instance;
  }

  protected function nomalizeQueueName($name)
  {
    if ($this->prefix) {
      $name = $this->prefix . '_' . $name;
    }
    return $name;
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::create()
   */
  public function createQueue($name, $timeout = null)
  {
    $this->zendQueue = $this->getQueue()->createQueue($this->nomalizeQueueName($name), $timeout);
    return $this;
  }

  /**
   *
   *
   * @param mixed $data
   *
   * @return fpMqQueue
   */
  public function send($data, $queueName)
  {
    $this->getQueue()->setOption('name', $this->amazonUrl . $this->nomalizeQueueName($queueName));
    $container = new fpMqContainer($data);
    if (!empty($this->sender)) {
      $container->addMetaData('sender', $this->sender);
    }
    $this->getQueue()->send($container->encode());
    return $this;
  }

  /**
   * @see fpMqAmazonQueue::receive()
   */
  public function receive($queueName, $maxMessages = null, $timeout = null, Zend_Queue $queue = null)
  {
    $this->getQueue()->setOption('name', $this->amazonUrl . $this->nomalizeQueueName($queueName));
    $messages = $this->getQueue()->receive($maxMessages, $timeout, $queue);
    $container = new fpMqContainer(null);
    $return = array();
    /* @var $message Zend_Queue_Message */
    foreach ($messages as $key => $message) {
      $message->body = $container->setData($message->body)->decode();
      if (!empty($this->sender) && $container->getMetaData('sender') == $this->sender) {
        continue;
      }
      $return[] = $message->toArray();
    }
    return new Zend_Queue_Message_Iterator(array(
      'queue' => $this->getQueue(),
      'messageClass' => $this->getQueue()->getMessageClass(),
      'data' => $return
    ));
  }

  /**
   * @see fpMqAmazonQueue::getQueues()
   *
   * @param bool $refresh
   * @param string $prefix
   *
   * @return array - array of queues names without url
   */
  public function getQueues($refresh = false)
  {
    if (null === $this->queuesList) {
      $refresh = true;
    }
    if (!$refresh) {
      return $this->queuesList;
    }
    $this->queuesList = array();
    $queuesList = $this->getQueue()->getQueues();
    foreach ($queuesList as $key => $queueName) {
      $queueName = substr(strrchr($queueName, '/'), 1);
      if ($this->prefix) {
        $prefixLength = strlen($this->prefix) + 1;
        if ($this->prefix . '_' != substr($queueName, 0, $prefixLength)) {
          continue;
        }
        $queueName = substr($queueName, $prefixLength);
      }
      $this->queuesList[] = $queueName;
    }
    return $this->queuesList;
  }
}
