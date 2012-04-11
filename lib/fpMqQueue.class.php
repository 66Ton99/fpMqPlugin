<?php

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
   * Constructor
   *
   * @return void
   */
  protected function __construct(array $options, $amazonUrl)
  {
    $this->amazonUrl = $amazonUrl;
    $this->zendQueue = $this->queueFactory($this->driverFactory($options));
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
    if (!method_exists($this->zendQueue, $method)) {
      require_once __DIR__ . '/fpMqException.class.php';
      throw new fpMqException("Called '{$method}' method does not exist in " . get_class($this));
    }
    $return = call_user_func_array(array($this->zendQueue, $method), $params);
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
    static::$instance = new static(sfConfig::get('fp_mq_driver_options'), sfConfig::get('fp_mq_amazon_url'));
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

  /**
   *
   *
   * @param mixed $data
   *
   * @return fpMqQueue
   */
  public function send($data, $queue)
  {
    $this->zendQueue->setOption('queueUrl', $this->amazonUrl . $queue);
    $this->zendQueue->send(json_encode($data));
    return $this;
  }

  /**
   * @see fpMqAmazonQueue::getQueues()
   *
   * @param bool $refresh
   * @param string $prefix
   *
   * @return array
   */
  public function getQueues($refresh = false, $prefix = null)
  {

     if (null === $this->queuesList) {
        $refresh = true;
     }
     if (!$refresh) {
        return $this->queuesList;
     }
     $this->queuesList = $this->zendQueue->getQueues();
     if ($prefix) {
        foreach ($this->queuesList as $key => $queueName) {
           $queueName = substr(strrchr($queueName, '/'), 1);
           if ($prefix . '_' != substr($queueName, 0, strlen($prefix) + 1)) {
              unset($this->queuesList[$key]);
           }
        }
     }
     return $this->queuesList;
  }

}
