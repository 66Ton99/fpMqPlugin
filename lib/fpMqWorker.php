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
  
  protected $queue;
  
  protected $callback;
  
  protected $lockTime = 10;

  /**
   * Constructor
   *
   * @return void
   */
  public function __construct($callback)
  {
    $this->callback = $callback;
    $options = sfConfig::get('fp_mq_driver_options');
    $options['queue_url'] = sfConfig::get('fp_mq_amazon_sqs_test_queue'); // TODO change
    $driver = new fpMqAmazonQueue($options);
    $this->queue = new Zend_Queue($driver);
  }
  
  /**
   * 
   *
   * @param fpMqDaemon $daemon
   *
   * @return void
   */
  public function process($daemon)
  {
    $messages = $this->queue->receive(1, $this->lockTime);
    if (count($messages) && $message = $messages->current()) {
      $this->createFork($message);
    }
  }
  
  public function run()
  {
    $daemon = new fpMqDaemon(array($this, 'process'));
    $daemon->run();
  }
  
  /**
   * Creates separate process
   *
   * @return bool
   */
  public function createFork($message)
  {
    switch ($pid = pcntl_fork()) {
      case -1:
        echo "Fork failed\n";
        break;
  
      case 0:
        call_user_func($this->callback, $message->body);
        $this->queue->deleteMessage($message);
        exit; // The end of the forked process
  
      default:
        pcntl_wait($status, WNOHANG); //Protects against Zombie children
        break;
    }
    return false;
  }
}