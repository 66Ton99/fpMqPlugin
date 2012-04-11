<?php

/**
 * Configuration
 *
 * @author     Ton Sharp <forma@66Ton99.org.ua>
 */
class fpMqPluginConfiguration extends sfPluginConfiguration
{
  /**
   *
   * @return void
   */
  public function initialize()
  {
    require_once __DIR__ . '/../autoload.php';
    fpMqFunction::loadConfig('config/fp_mq.yml', 'fp_mq');
  }
}
