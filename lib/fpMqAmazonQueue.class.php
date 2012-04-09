<?php

/**
 *
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpMqAmazonQueue extends Zend_Queue_Adapter_AdapterAbstract
{

  protected $service;

  protected $queueUrl;

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::__construct()
   */
  public function __construct($options, Zend_Queue $queue = null)
  {
    if (empty($options['id']) || empty($options['key']))
    {
      throw new fpMqException('Options: "id" and "key" are required');
    }
    $this->service = new Zend_Service_Amazon_Sqs($options['id'], $options['key']);
    parent::__construct($options, $queue);
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::create()
   */
  public function create($name, $timeout = null)
  {
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
    return $this->service->deleteMessage($this->getQueueUrl(), $message->handle);
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
    $responseArr = $this->service->receive($this->getQueueUrl(), $maxMessages, $timeout);
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
    return $this->service->send($this->getQueueUrl(), $message);
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
    return $this->service->count($this->getQueueUrl());
  }

  /**
   * (non-PHPdoc)
   * @see Zend_Queue_Adapter_AdapterInterface::isExists()
   */
  public function isExists($name)
  {
    return $this->isExists($name);
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

  /**
   * Get queue url
   *
   * @return string
   */
  protected function getQueueUrl()
  {
    return $this->getQueue()->getOption('queueUrl');
  }
}
