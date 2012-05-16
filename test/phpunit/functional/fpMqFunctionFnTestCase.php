<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../lib/fpMqFunction.class.php';

/**
 * functional test case.
 */
class fpMqFunctionFnTestCase extends PHPUnit_Framework_TestCase
{

  /**
   * @test
   */
  public function initConfigs()
  {
    if (!is_file(ROOTDIR . '/lib/vendor/symfony/lib/config/sfConfig.class.php'))
    {
       $this->markTestSkipped('It will work only in Symfony 1.? environment');
    }
    fpMqFunction::loadConfig('config/fp_mq.yml');
    $this->assertEquals(array('test' => 'test'), sfConfig::get('fp_mq_test'));
    sfConfig::set('fp_mq_test', false);
    sfConfig::set('sf_environment', 'test');
    fpMqFunction::loadConfig('config/fp_mq.yml');
    $this->assertEquals(array('test' => 'test2'), sfConfig::get('fp_mq_test'));
  }
}

