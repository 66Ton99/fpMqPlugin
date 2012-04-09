<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../lib/fpMqFunction.class.php';

/**
 * test case.
 */
class fpMqFunctionTestCase extends PHPUnit_Framework_TestCase
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
    $this->assertEquals('test', sfConfig::get('fp_mq_test'));
  }

  /**
   * Data for arrayMergeRecursive
   *
   * @return array
   */
  public function arrays()
  {
    return array(
        array(
            array(),
            array(),
            array(),
        ),
        array(
            array('one' => 1),
            array('one' => 2),
            array('one' => 2),
        ),
        array(
            array('one' => 1),
            array('two' => 2),
            array('one' => 1, 'two' => 2),
        ),
        array(
            array('one' => array('sub1' => 1, 'sub2' => 2)),
            array('one' => array('sub1' => 3)),
            array('one' => array('sub1' => 3, 'sub2' => 2)),
        ),
        array(
            array('one' => array('sub1' => 3)),
            array('one' => array('sub1' => 1, 'sub2' => 2)),
            array('one' => array('sub1' => 1, 'sub2' => 2)),
        ),
        array(
            array('one' => array('sub1' => array('sub21' => 1))),
            array('one' => array('sub1' => array('sub21' => 12))),
            array('one' => array('sub1' => array('sub21' => 12))),
        ),
    );
  }

  /**
   * @test
   * @dataProvider arrays
   */
  public function arrayMergeRecursive($first, $second, $result)
  {
    $this->assertEquals($result, fpMqFunction::arrayMergeRecursive($first, $second));
  }
}

