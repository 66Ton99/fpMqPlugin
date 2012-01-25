<?php
define('ROOTDIR', __DIR__ . '/../../..');

/**
 * 
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqFunction
{

  /**
   * Load configs
   *
   * @param string $config
   * 
   * @todo improve
   *
   * @return void
   */
  public static function loadConfig($config)
  {
    require_once ROOTDIR . '/lib//vendor/symfony/lib/config/sfConfig.class.php';
    require_once ROOTDIR . '/lib/vendor/symfony/lib/yaml/sfYaml.php';
    $mainConfig = sfYaml::load(__DIR__ . '/../' . $config);
    $appConfig = sfYaml::load(ROOTDIR . '/' . $config);
    if (is_array($appConfig))
    {
      $configs = static::arrayMergeRecursive($mainConfig, $appConfig);
    }
    else
    {
      $configs = $mainConfig;
    }
    $configs = $configs['all'];
    foreach ($configs as $key => $val)
    {
      sfConfig::set('fp_mq_' . $key, $val);
    }
  }
  
  /**
   * Recursive merge 2 or more arrays
   *
   * @return array
   */
  public static function arrayMergeRecursive()
  {
  
    if (func_num_args() < 2) {
      trigger_error(__FUNCTION__ . ' needs two or more array arguments', E_USER_WARNING);
      return;
    }
    $arrays = func_get_args();
    $merged = array();
    while ($arrays) {
      $array = array_shift($arrays);
      if (!is_array($array)) {
        trigger_error(__FUNCTION__ . ' encountered a non array argument', E_USER_WARNING);
        return;
      }
      if (!$array) continue;
      foreach ($array as $key => $value) {
        if (is_string($key)) {
          if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
            $merged[$key] = static::arrayMergeRecursive($merged[$key], $value);
          } else {
            $merged[$key] = $value;
          }
        } else {
          $merged[] = $value;
        }
      }
    }
    return $merged;
  }
}