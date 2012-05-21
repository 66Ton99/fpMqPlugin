<?php
if (!defined('ROOTDIR'))
{
  define('ROOTDIR', __DIR__ . '/../../..');
}

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

  public static function init()
  {
    $file = ROOTDIR . '/config/ProjectConfiguration.class.php';
    if (!is_readable($file)) return false;
    require_once $file;
    $configuration = new ProjectConfiguration();
    $configuration->initConfiguration();
    return true;
  }

  /**
   * Load configs
   *
   * @param string $config
   *
   * @todo improve
   *
   * @return bool
   */
  public static function loadConfig($config, $sectionName = 'fp_mq', $levels = 1)
  {
    $file = ROOTDIR . '/lib/vendor/symfony/lib/config/sfConfig.class.php';
    if (!is_readable($file)) return false;
    require_once $file;
    if (sfConfig::get('fp_mq_test')) return true;

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
    $env = sfConfig::get('sf_environment', 'all');
    $envConfigs = $configs['all'];
    if ('all' != $env && !empty($configs[$env])) {
      $envConfigs = static::arrayMergeRecursive($envConfigs, $configs[$env]);
    }

    static::registerConfigsToSystem($sectionName, $envConfigs, $levels);
    return true;
  }

  /**
   * Recursive merge 2 or more arrays
   *
   * @param array $arr1
   * @param array $arr2
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
