<?php

require_once __DIR__ . '/../lib/fpMqDaemon.php';
require_once __DIR__ . '/../lib/fpMqAmazonQueue.php';
require_once 'Zend/Queue.php';
require_once 'Zend/Queue/Message/Iterator.php';
require_once 'Zend/Queue/Message.php';

/**
 * 
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqWorker
{
  
  /**
   * Daemon
   * 
   * @var fpMqDaemon
   */
  protected $daemon;
  
  /**
   * Queue
   * 
   * @var Zend_Queue
   */
  protected $queue;
  
  /**
   * Callback
   * 
   * @var callback
   */
  protected $callback;
  
  /**
   * Lock time
   * 
   * @var int
   */
  protected $lockTime = 10;

  /**
   * Constructor
   *
   * @return void
   */
  public function __construct($callback)
  {
    $this->daemon = new fpMqDaemon(array($this, 'process'));
    $this->callback = $callback;
    $options = sfConfig::get('fp_mq_driver_options');
    $driver = new fpMqAmazonQueue($options);
    $this->queue = new Zend_Queue($driver);
  }
  
  /**
   * 
   *
   * @return void
   */
  public function process()
  {
    foreach ($this->queue->getQueues() as $queue)
    {
      $this->queue->setOption('queueUrl', $queue);
      $messages = $this->queue->receive(1, $this->lockTime);
      if (count($messages) && $message = $messages->current())
      {
        $tmp = explode('/', $queue);
        $queueName = array_pop($tmp);
        $this->createFork($message, $queueName);
      }
    }
  }
  
  /**
   * Run daemon process
   *
   * @return void
   */
  public function run()
  {
    $this->daemon->run();
  }
  
  /**
   * Creates separate process
   *
   * @return bool
   */
  public function createFork($message, $queueName)
  {
    switch ($pid = pcntl_fork()) {
      case -1:
        echo "Fork failed\n";
        break;
  
      case 0:
        if (call_user_func($this->callback, json_decode($message->body), $queueName))
        {
          $this->queue->deleteMessage($message);
        }
        exit; // The end of the forked process
  
      default:
        pcntl_wait($status, WNOHANG); //Protects against Zombie children
        break;
    }
    return false;
  }
}