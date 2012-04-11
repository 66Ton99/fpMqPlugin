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

  protected $pidFile = 'worker.pid';


  /**
   * Returns tmp dir
   *
   * @return string
   */
  public function getTmpDir()
  {
    $tmpDir = __DIR__ . '/../data/';
    if (!is_writable($tmpDir)) {
      trigger_error("Directory '{$tmpDir}' does not have write permission, system tmp dir will be used", E_WARNING);
      $tmpDir = sys_get_temp_dir();
    }
    return $tmpDir;
  }

  /**
   * Constructor
   *
   * @return void
   */
  public function __construct($callback)
  {
    $this->pidFile = $this->getTmpDir() . $this->pidFile;
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
    if(($pid = $this->getPid()) && posix_kill($pid, SIG_DFL))
    {
      return false;
    }
    $pid = getmypid();
    @unlink($this->pidFile);
    file_put_contents($this->pidFile, $pid, FILE_APPEND);
    return true;
  }

  public function stop()
  {
    $pid = $this->getPid();
    if(!posix_kill($pid, SIG_DFL))
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
    if (defined('DEBUG')) {
      echo "\n", 'Daemon was started at ', date('Y-m-d H:i:s'), "\n";
    }
    while (true) {
      call_user_func($this->callback);
      sleep($this->interval);
      if (defined('DEBUG')) {
        echo '.';
      }
    }
  }
}
