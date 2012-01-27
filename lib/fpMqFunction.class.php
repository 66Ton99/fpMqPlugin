<?php
define('ROOTDIR', __DIR__ . '/../../..');

/**
 * 
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqFunction
{
  
  /**
   * Add vars of congs to system 
   *
   * @param string $sectionName
   * @param array $configs
   * @param int $levels
   *
   * @return void
   */
  protected static function registerConfigsToSystem($sectionName, $configs, $levels = 1)
  {
    $levels--;
    foreach ($configs as $name => $value) {
      if ($levels && is_array($value)) {
        static::registerConfigsToSystem($sectionName . '_' . $name, $value, $levels);
      } else {
        sfConfig::set($sectionName . '_' . $name, $value);
      }
    }
  }

  /**
   * Load configs
   *
   * @param string $config
   * 
   * @todo improve
   *
   * @return void
   */
  public static function loadConfig($config, $sectionName = 'fp_mq', $levels = 1)
  {
    require_once ROOTDIR . '/lib/vendor/symfony/lib/config/sfConfig.class.php';
    require_once ROOTDIR . '/lib/vendor/symfony/lib/yaml/sfYaml.php';
    $configs = array();
    if (($mainConfig = sfYaml::load(__DIR__ . '/../' . $config)) && is_array($mainConfig))
    {
      $configs = static::arrayMergeRecursive($configs, $mainConfig);
    }
    if (($appConfig = sfYaml::load(ROOTDIR . '/' . $config)) && is_array($appConfig))
    {
      $configs = static::arrayMergeRecursive($configs, $appConfig);
    }
    static::registerConfigsToSystem($sectionName, $configs['all'], $levels);
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