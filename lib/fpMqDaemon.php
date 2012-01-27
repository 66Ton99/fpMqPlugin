<?php
declare(ticks = 1);
require_once __DIR__ . '/../lib/fpMqFunction.php';

/**
 * 
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqDaemon
{
  protected $interval = 2;
  protected $callback;
  protected $notifier;
  
  /**
   * Constructor
   *
   * @return void
   */
  public function __construct($callback)
  {
    $file = __DIR__ . '/../../fpErrorNotifierPlugin/config/include.php';
    if (is_readable($file))
    {
      fpMqFunction::loadConfig('config/notify.yml', 'sf_notify');
      require_once $file;
      $this->notifier = new fpErrorNotifier();
      fpErrorNotifier::setInstance($this->notifier);
      $this->notifier->handler()->initialize();
    }
    $this->callback = $callback;
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