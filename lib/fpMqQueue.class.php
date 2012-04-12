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

  /**
   * @var string
   */
  protected $queueNamePrefix;

  protected $sender;

  /**
   * Constructor
   *
   * @return void
   */
  protected function __construct(array $options, $amazonUrl, $queueNamePrefix = null)
  {
    if (!empty($options['sender'])) {
      $this->sender = $options['sender'];
    }
    $this->queueNamePrefix = $queueNamePrefix;
    $this->amazonUrl = $amazonUrl;
    $this->zendQueue = $this->queueFactory($this->driverFactory($options));
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
    return new fpMqAmazonQueue($options);
  }

  /**
   * Initialization
   *
   * @param array $options
   * @param strings $amazonUrl
   *
   * @return fpMqQueue
   */
  public static function init(array $options, $amazonUrl)
  {
    return static::$instance = new static($options, $amazonUrl);
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
    static::$instance = new static(
      sfConfig::get('fp_mq_driver_options'),
      sfConfig::get('fp_mq_amazon_url')
    );
    return true;
  }

  /**
   * Return singleton
   *
   * @return Queue
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
    $options = $this->getQueue()->getAdapter()->getOptions();
    if (!empty($options['prefix'])) {
      $name = $options['prefix'] . '_' . $name;
    }
    return $name;
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
    $this->getQueue()->setOption('queueUrl', $this->amazonUrl . $this->nomalizeQueueName($queueName));
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
  public function receive($maxMessages = null, $timeout = null, Zend_Queue $queue = null)
  {
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
    $options = $this->getQueue()->getAdapter()->getOptions();
    foreach ($queuesList as $key => $queueName) {
      $queueName = substr(strrchr($queueName, '/'), 1);
      if (!empty($options['prefix'])) {
        $prefixLength = strlen($options['prefix']) + 1;
        if ($options['prefix'] . '_' != substr($queueName, 0, $prefixLength)) {
          continue;
        }
        $queueName = substr($queueName, $prefixLength);
      }
      $this->queuesList[] = $queueName;
    }
    return $this->queuesList;
  }
}
