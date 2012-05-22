<?php

/**
 * Message container
 *
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqContainer
{

  protected $data;
  protected $metaData;

  public function __construct($data, array $metaData = array())
  {
    $this->data = $data;
    $this->metaData = $metaData;
  }

  /**
   *
   *
   * @param mixed $data
   *
   * @return fpMqContainer
   */
  public function setData($data)
  {
    $this->data = $data;
    return $this;
  }

  /**
   *
   *
   * @param array $metaData
   *
   * @return fpMqContainer
   */
  public function setMetaData(array $metaData)
  {
    $this->metaData = $metaData;
    return $this;
  }

  /**
   *
   *
   * @param string $name
   * @param string $val
   *
   * @return fpMqContainer
   */
  public function addMetaData($name, $val)
  {
    $this->metaData[$name] = $val;
    return $this;
  }

  /**
   *
   * @return bool
   */
  public function hasMetaData($name)
  {
    return isset($this->metaData[$name]);
  }

  /**
   *
   * @return string
   */
  public function getMetaData($name)
  {
    if (!$this->hasMetaData($name)) return null;
    return $this->metaData[$name];
  }

  /**
   *
   * @return array
   */
  public function getAllMetaData()
  {
    return $this->metaData;
  }

  protected function convertToString($data)
  {
    return (array)$data;
  }

  protected function convertToArray($data)
  {
    return (array)$data;
  }

  /**
   *
   *
   * @param mixed $data
   *
   * @return string
   */
  public function encode()
  {
    switch ($type = gettype($this->data)) {
      case 'string':
      case 'array':
        break;

      default:
        require_once __DIR__ . '/fpMqException.class.php';
        throw new fpMqException("Data type '{$type}' does not supported");
    }
    $retrun = array();
    $retrun['data'] = $this->data;
    $retrun['type'] = $type;
    if (!empty($this->metaData)) {
      $retrun['meta'] = $this->metaData;
    }
    return json_encode($retrun);
  }

  /**
   *
   *
   * @param mixed $data
   *
   * @todo improve
   *
   * @return fpMqContainer
   */
  public function decode()
  {
    $data = json_decode($this->data);
    $convector = 'convertTo' . ucfirst($data->type);
    if (!method_exists($this, $convector)) {
      require_once __DIR__ . '/fpMqException.class.php';
      throw new fpMqException("Data type '{$data->type}' does not supported");
    }
    if (!empty($data->meta)) {
      $this->metaData = (array)$data->meta;
    }
    return $this->$convector($data->data);
  }
}
