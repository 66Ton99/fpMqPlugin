<?php

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

  protected $resiveAtOnce = 1;

  /**
   * @var string
   */
  protected $prefix;

  /**
   * Constructor
   *
   * @return void
   */
  public function __construct($callback, fpMqQueue $queue)
  {
    $this->queue = $queue;
    $this->callback = $callback;
    $this->daemon = $this->deamonFactory();
  }

  /**
   *
   *
   * @return void
   */
  public function process()
  {
    foreach ($this->queue->getQueues() as $queue) {
      $this->queue->setOption('queueUrl', $queue);
      $messages = $this->queue->receive($this->resiveAtOnce, $this->lockTime);
      if (count($messages) && $message = $messages->current()) {
        static::createFork(array($message, $queue), array($this, 'execute'));
      }
    }
  }

  /**
   * Init deamon
   *
   * @return fpMqDaemon
   */
  protected function deamonFactory()
  {
    return new fpMqDaemon(array($this, 'process'));
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
    if (call_user_func($this->callback, $message->body, $queueName)) {
      $this->queue->deleteMessage($message);
      if (defined('DEBUG')) {
        echo "Deleted\n";
      }
    }
  }

  /**
   * Creates separate process (fork)
   *
   * @param array $params
   * @param callback $callback
   *
   * @return boolean
   */
  public static function createFork($params, $callback)
  {
    switch ($pid = pcntl_fork()) {
      case -1: // Fail
        return false;

      case 0:
        call_user_func_array($callback, $params);
        exit; // The end of the forked process

      default:
        pcntl_wait($status, WNOHANG); //Protects against Zombie children
        break;
    }
    return true;
  }
}
