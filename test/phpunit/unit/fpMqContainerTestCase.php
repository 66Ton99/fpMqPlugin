<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once __DIR__ . '/../../../autoload.php';

/**
 * Test case of container.
 */
class fpMqContainerTestCase extends PHPUnit_Framework_TestCase
{

  /**
   * Data for encodeDecode
   *
   * @return array
   */
  public function dataTypes()
  {
   return array(
      array(
        array('test' => 'test data'),
      ),
      array(
        array('some string'),
      ),
    );
  }

  /**
   * @test
   * @dataProvider dataTypes
   */
  public function encodeDecodeData($data)
  {
    $containerOr = new fpMqContainer($data);
    $containerRecived = new fpMqContainer($containerOr->encode());
    $this->assertEquals($data, $containerRecived->decode());
  }


  /**
   * Data for encodeDecode
   *
   * @return array
   */
  public function metaAata()
  {
    return array(
      array(
        array(),
        array('test meta' => 'test meta', 'some more meta' => 'some more meta'),
      ),
      array(
        array(),
        array(),
      ),
    );
  }

  /**
   * @test
   * @dataProvider metaAata
   */
  public function encodeDecodeMeta($data, $meata)
  {
    $containerOr = new fpMqContainer($data);
    $containerOr->setMetaData($meata);
    $containerRecived = new fpMqContainer($containerOr->encode());
    $containerRecived->decode();
    $this->assertEquals($meata, $containerRecived->getAllMetaData());
    foreach ($meata as $name => $val) {
      $this->assertTrue($containerRecived->hasMetaData($name));
      $this->assertEquals($val, $containerRecived->getMetaData($name));
    }
  }


  /**
   * @test
   * @expectedException fpMqException
   */
  public function encode_wrongDataType()
  {
    $container = new fpMqContainer(new fpMqContainer(null));
    $container->encode();
  }

  /**
   * @test
   * @expectedException fpMqException
   */
  public function decode_wrongDataType()
  {
    $container = new fpMqContainer('{"data":{"test":"test"},"type":"someWrongType"}');
    $container->decode();
  }

}

