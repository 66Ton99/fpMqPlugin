<?php

/**
 * Daemon
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
   * @param callback $callback
   *
   * @return void
   */
  public function __construct($callback)
  {
    if (!is_callable($callback)) {
      throw new fpMqException('Provided callback is invalid');
    }

    $this->pidFile = $this->getTmpDir() . $this->pidFile;
    switch (strtolower(@$_SERVER['argv'][1]))
    {
      case 'stop':
        echo $this->stop(), "\n";
        exit;

      case 'watchdog':
        if (true === $this->isStarted()) {
          if (defined('DEBUG')) {
              echo '.';
          }
          exit;
        }
        echo 'Restarting', "\n";


      case 'start':
      default:
        if (false === $this->start()) {
          echo 'It is already running', "\n";
          exit;
        }
    }
    $this->callback = $callback;
  }

  /**
   * Returns pid
   *
   * @return int|null
   */
  public function getPid()
  {
    $pid = null;
    if (is_file($this->pidFile))
    {
      $pid = file_get_contents($this->pidFile);
    }
    return $pid;
  }

  /**
   * Checks is daemon started or not
   *
   * @return boolean
   */
  public function isStarted()
  {
    return ($pid = $this->getPid()) && posix_kill($pid, SIG_DFL);
  }

  /**
   * Starts daemon
   *
   * @return boolean
   */
  public function start()
  {
    if($this->isStarted())
    {
      return false;
    }
    $pid = getmypid();
    @unlink($this->pidFile);
    file_put_contents($this->pidFile, $pid, FILE_APPEND);
    return true;
  }

  /**
   * Stops daemon
   *
   * @return string
   */
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
    @unlink($this->pidFile);
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

  /**
   * Watchdog
   *
   * @return bool
   */
  public function watchdog()
  {
    if (!$this->isStarted())
    {
      $this->start();
      return false;
    }
    if (defined('DEBUG')) {
      echo '.';
    }
    return true;
  }
}
