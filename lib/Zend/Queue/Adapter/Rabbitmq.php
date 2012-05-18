<?php

/**
 * Class for using a RabbitMQ as a queue
 *
 * @category AMQP
 * @package Zend_Queue
 * @subpackage Adapter
 * @author Ritesh Jha
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 * @copyright Copyright (c) mailrkj(at)gmail(dot)com
 * @see http://riteshsblog.blogspot.com/2011/03/rabbitmq-adapter-for-zend-queue-using.html
 */
class Zend_Queue_Adapter_Rabbitmq extends Zend_Queue_Adapter_AdapterAbstract
{
    /**
     * @var AMQPConnection
     */
    private $_cnn;

    /**
     * @var AMQPExchange
     */
    private $_exchange = null;

    /**
     * @var AMQPQueue
     */
    private $_amqpQueue = null;

    /**
     * @var AMQPChannel
     */
    private $_channel = null;

    /**
     * @var int count of messages we got last time
     */
    private $_count;

    /**
     * Constructor
     *
     * @param array|Zend_Config $options - host, port, login, password
     * @param null|Zend_Queue $queue
     */
    public function __construct($options, Zend_Queue $queue = null)
    {
        parent::__construct($options, $queue);

        if (is_array($options))
        {
            try
            {
                $cnn = new AMQPConnection($options);
                $cnn->connect();

                if (!$cnn->isConnected())
                {
                    require_once 'Zend/Queue/Exception.php';
                    throw new Zend_Queue_Exception("Unable to connect RabbitMQ server");
                }
                else
                {
                    $this->_cnn = $cnn;
                    $this->_channel = new AMQPChannel($this->_cnn);
                    $this->_amqpQueue = new AMQPQueue($this->_channel);
                }
            } catch (Exception $e) {
                require_once 'Zend/Queue/Exception.php';
                throw new Zend_Queue_Exception($e->getMessage());
            }
        }
        else
        {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception("The options must be an associative empty array or array of host,port,login,password...");
        }
    }

    /**
     * Get AMQPConnection object
     *
     * @deprecated - please don't use it
     *
     * @return object
     */
    public function getConnection()
    {
        return $this->_cnn;
    }

    /**
     * Returns exchange options
     *
     * @return array
     */
    protected function getExchangeOptions()
    {
      return array_merge(
          array(/* 'routingKey' => '*',  */'type' => AMQP_EX_TYPE_DIRECT, 'flags' => AMQP_DURABLE/*  | AMQP_NOASK */),
          $this->getQueue()->getOption('exchange')?:array()
      );
    }


    /**
     * Set exchange for sending message to queue
     *
     * @return Zend_Queue_Adapter_Rabbitmq
     */
    protected function initExchange($reload = false)
    {
        if (!$this->_exchange || $reload) {
            $exchangeOptions = $this->getExchangeOptions();
            $this->_exchange = new AMQPExchange($this->_channel);
            $this->_exchange->setName($this->getQueue()->getName());
            $this->_exchange->setType($exchangeOptions['type']);
            $this->_exchange->setFlags($exchangeOptions['flags']);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     * @see Zend_Queue_Adapter_AdapterInterface::create()
     */
    public function create($name, $timeout = null)
    {
        try {
            $reload = $this->getQueue()->getName() != $name;
            $this->getQueue()->setOption(Zend_Queue::NAME, $name);
            $this->initExchange($reload);
            $this->_exchange->declare();

            $this->_amqpQueue->setName($this->getQueue()->getName());
            $this->_count = $this->_amqpQueue->declare();

            $exchangeOptions = $this->getExchangeOptions();
            $this->_amqpQueue->bind($name, $exchangeOptions['routingKey']);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     * @see Zend_Queue_Adapter_AdapterInterface::delete()
     */
    public function delete($name)
    {
        $reload = $this->getQueue()->getName() != $name;
        $this->getQueue()->setOption(Zend_Queue::NAME, $name);
        $this->initExchange($reload);
        return $this->_exchange->delete();
    }

    /**
     * {@inheritdoc}
     * @see Zend_Queue_Adapter_AdapterInterface::send()
     */
    public function send($message, Zend_Queue $queue = null)
    {
        if (is_array($message)) {
            $message = Zend_Json_Encoder::encode($message);
        }
        $reload = false;
        if ($queue)
        {
            $this->setQueue($queue);
            $reload = true;
        }
        $this->initExchange($reload);

        return $this->_exchange->publish(
            $message,
            $this->getQueue()->getOption('routingKey')?:'*',
            AMQP_NOPARAM,
            array('delivery_mode' => 2)
        );
    }

    /**
     * {@inheritdoc}
     * @see Zend_Queue_Adapter_AdapterInterface::receive()
     */
    public function receive($maxMessages = null, $timeout = null, Zend_Queue $queue = null)
    {
        $reload = false;
        if ($queue) {
            $this->setQueue($queue);
            $reload = true;
        }
        $this->initExchange($reload);

        $this->_amqpQueue->setName($this->getQueue()->getName());

        $result = array();
        $maxMessages = (int) $maxMessages ? (int) $maxMessages : 1;
        if (isset($this->_options['method']) && 'consume' == $this->_options['method']) {
            // use new AMQP_Queue_Adapter_Rabbitmq(array('method' => 'consume')) to use CONSUME approach
            $consumeOptions = array(
                'min' => 1,
                'max' => $maxMessages,
                'ack' => false,
            );
            $result[] = $this->_amqpQueue->consume($consumeOptions);// TODO check
            $this->_count -= sizeof($result);
        } else {
            // default approach is GET
            for ($i = $maxMessages; $i > 0; $i--) {
                if ($message = $this->_amqpQueue->get()) {
                    $result[] = array(
                      'body' =>$message->getBody(),
                    );
                }
            }
        }
        return new Zend_Queue_Message_Iterator(array('data' => $result));
    }

    /**
     * {@inheritdoc}
     * @see Zend_Queue_Adapter_AdapterInterface::deleteMessage()
     */
    public function deleteMessage(Zend_Queue_Message $message)
    {
        if (!isset($message->delivery_tag)) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('No delivery tag for Acking!');
        }
        return $this->_amqpQueue->ack($message->delivery_tag);
    }

    /**
     * {@inheritdoc}
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
            'isExists'      => false,
            'getQueues'     => false,
            'count'         => false,
        );
    }

    /**
     * {@inheritdoc}
     * @see Zend_Queue_Adapter_AdapterInterface::isExists()
     */
    public function isExists($name)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * @see Zend_Queue_Adapter_AdapterInterface::getQueues()
     */
    public function getQueues()
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('getQueues() is not supported in this adapter');
//         return array($this->_queue);
    }

    /**
     * {@inheritdoc}
     * @see Zend_Queue_Adapter_AdapterInterface::count()
     */
    public function count(Zend_Queue $queue = null)
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('count() is not supported in this adapter');
//         $reload = false;
//         if ($queue)
//         {
//             $this->setQueue($queue);
//             $reload = true;
//         }
// //         $this->initExchange($reload);
//         return $this->_count;
    }

}
