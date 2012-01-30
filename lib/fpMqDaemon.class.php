<?php

/**
 * 
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqDaemon
{
  protected $interval = 2;
  protected $callback;
  protected $notifier;

  protected $pidFile = 'data/worker.pid';
  
  /**
   * Constructor
   *
   * @return void
   */
  public function __construct($callback)
  {
    $this->pidFile = __DIR__ . '/../' . $this->pidFile;
    switch (strtolower(@$_SERVER['argv'][1]))
    {
      case 'stop':
        echo $this->stop(), "\n";
        exit;

      case 'start':
      default:
        if (false === $this->start()) {
          echo 'It is already running', "\n";
          exit;
        }
    }
    $this->callback = $callback;
  }

  public function getPid()
  {
    $pid = null;
    if (is_file($this->pidFile))
    {
      $pid = file_get_contents($this->pidFile);
    }
    return $pid;
  }

  public function start()
  {
    if(($pid = $this->getPid()) && posix_kill($pid, 0))
    {
      return false;
    }
    $pid = getmypid();
    unlink($this->pidFile);
    file_put_contents($this->pidFile, $pid, FILE_APPEND);
    return true;
  }

  public function stop()
  {
    $pid = $this->getPid();
    if(!posix_kill($pid, 0))
    {
      return 'It is already stopped';
    }
    elseif (!posix_kill($pid, SIGKILL))
    {
      return 'Can not stop process ' . $pid;
    }
    return 'Stoped';
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
//      echo '.';
    }
  }
}