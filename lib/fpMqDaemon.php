<?php

require_once __DIR__ . '/../lib/fpMqFunction.php';

/**
 * 
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqDaemon
{
  protected $interval = 2;
  protected $callback;
  protected $heandler;
  
  /**
   * Constructor
   *
   * @return void
   */
  public function __construct($callback)
  {
    $this->callback = $callback;
    $file = __DIR__ . '/../../fpErrorNotifierPlugin/config/include.php';
    if (is_readable($file))
    {
      fpMqFunction::loadConfig('config/notify.yml', 'sf_notify', 1);
      require_once $file;
      $notifier = new fpErrorNotifier();
      fpErrorNotifier::setInstance($notifier);
      $this->heandler = $notifier->handler();
      $this->heandler->initialize();
    }
  }
  
  /**
   * Destructor
   *
   * @return void
   */
  public function __destruct()
  {
    throw new Exception('Demon is down');
  }
  
  /**
   * Run
   *
   * @return void
   */
  public function run()
  {
//     echo "\n", 'Start daemon ', date('Y-m-d H:i:s'), "\n";
    while (true) {
      call_user_func($this->callback);
      sleep($this->interval);
    }
  }
}