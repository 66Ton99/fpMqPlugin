<?php

/**
 * 
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqDaemon
{
  protected $interval = 2;
  protected $callback;

  
  /**
   * Constructor
   *
   * @return void
   */
  public function __construct($callback)
  {
    $this->callback = $callback;
    register_shutdown_function(array($this, '__destruct'));
  }
  
  /**
   * Destructor
   *
   * @return void
   */
  public function __destruct()
  {
    
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