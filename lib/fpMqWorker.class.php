<?php

require_once __DIR__ . '/fpMqDaemon.class.php';
require_once __DIR__ . '/fpMqQueue.class.php';

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
   * @var fpMqQueue
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
    $this->queue = fpMqQueue::getInstance();
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
        static::createFork(array($message, $queueName), array($this, 'execute'));
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
   * Executes external callback (gets message) and deletes the message
   *
   * @param Zend_Queue_Message $message
   * @param string $queueName
   *
   * @return void
   */
  public function execute($message, $queueName)
  {
    if (call_user_func($this->callback, json_decode($message->body), $queueName))
    {
      $this->queue->deleteMessage($message);
    }
  }
  
  /**
   * Creates separate process (fork)
   *
   * @param array $params
   * @param callback $callback
   * 
   * @throws Exception
   * 
   * @return boolean
   */
  public static function createFork($params, $callback)
  {
    switch ($pid = pcntl_fork()) {
      case -1:
        throw new Exception('Fork failed');
        break;
  
      case 0:
        call_user_func_array($callback, $params);
        exit; // The end of the forked process
  
      default:
        pcntl_wait($status, WNOHANG); //Protects against Zombie children
        break;
    }
    return false;
  }
}