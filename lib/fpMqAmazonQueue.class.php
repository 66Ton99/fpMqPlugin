<?php

/**
 * Amazon SQS Queue
 *
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqAmazonQueue extends Zend_Queue_Adapter_AdapterAbstract
{

  /**
   * Default timeout for create() function
   */
  const CREATE_TIMEOUT_DEFAULT = 30;

  /**
   * @var Zend_Service_Amazon_Sqs
   */
  protected $service;

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::__construct()
   */
  public function __construct($options, Zend_Queue $queue = null)
  {
    parent::__construct($options, $queue);
    $options = $this->_options['driverOptions'];
    if (empty($options['id']) || empty($options['key']))
    {
      require_once 'Zend/Queue/Exception.php';
      throw new Zend_Queue_Exception('driverOptions: "id" and "key" are required');
    }
    $this->service = new Zend_Service_Amazon_Sqs($options['id'], $options['key']);
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::create()
   */
  public function create($name, $timeout = null)
  {
    if (null === $timeout) {
      $timeout = self::CREATE_TIMEOUT_DEFAULT;
    }
    return $this->service->create($name, $timeout);
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::delete()
   */
  public function delete($name)
  {
    return $this->service->delete($name);
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::deleteMessage()
   */
  public function deleteMessage(Zend_Queue_Message $message)
  {
    return $this->service->deleteMessage($this->getQueue()->getName(), $message->handle);
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::getQueues()
   */
  public function getQueues()
  {
    return $this->service->getQueues();
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::receive()
   */
  public function receive($maxMessages = null, $timeout = null, Zend_Queue $queue = null)
  {
    if (null !== $queue) {
      $this->setQueue($queue);
    }
    $responseArr = $this->service->receive($this->getQueue()->getName(), $maxMessages, $timeout);
    return new Zend_Queue_Message_Iterator(array(
      'queue' => $this->getQueue(),
      'messageClass' => $this->getQueue()->getMessageClass(),
      'data' => $responseArr
    ));
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::send()
   */
  public function send($message, Zend_Queue $queue = null)
  {
    if (null !== $queue) {
      $this->setQueue($queue);
    }
    return $this->service->send($this->getQueue()->getName(), $message);
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::count()
   */
  public function count(Zend_Queue $queue = null)
  {
    if (null !== $queue) {
      $this->setQueue($queue);
    }
    return $this->service->count($this->getQueue()->getName());
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::isExists()
   */
  public function isExists($name)
  {
    if (in_array($name, $this->service->getQueues())) {
      return true;
    }
    return false;
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::getCapabilities()
   */
  public function getCapabilities()
  {
    return array(
      'create'        => true,
      'delete'        => true,
      'send'          => true,
      'receive'       => true,
      'deleteMessage' => true,
      'getQueues'     => true,
      'count'         => true,
      'isExists'      => true,
    );
  }
}
