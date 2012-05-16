<?php
/**
 * Class for using a RabbitMQ as a queue
 *
 * @category AMQP
 * @package AMQP_Queue
 * @subpackage Adapter
 * @author Ritesh Jha
 * @copyright Copyright (c) mailrkj(at)gmail(dot)com
 * @see http://riteshsblog.blogspot.com/2011/03/rabbitmq-adapter-for-zend-queue-using.html
 */

class AMQP_Queue_Adapter_Rabbitmq extends Zend_Queue_Adapter_AdapterAbstract
{
    /**
     * @var AMQPConnection
     */
    private $_cnn = array();

    /**
     * @var AMQP_Queue_Exchange
     */
    private $_exchange = null;

    /**
     * @var AMQPQueue
     */
    private $_amqpQueue = null;


    /**
     * @var int AMQP queue flags
     */
    private $_amqpQueueFlag = AMQP_DURABLE;

    /**
     * @var int count of messages we got last time
     */
    private $_count;

    /**
     * Constructor
     *
     * @param array|Zend_Config $options
     * options (host,port,login,password)
     * @param null|Zend_Queue $queue
     * @return AMQP_Queue_Adapter_Rabbitmq instance
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
                    throw new Zend_Queue_Exception("Unable to connect RabbitMQ server");
                }
                else
                {
                    $this->_cnn = $cnn;
                    $this->_amqpQueue = new AMQPQueue($this->_cnn);
                }
            } catch (Exception $e) {
                throw new Zend_Queue_Exception($e->getMessage());
            }
        }
        else
        {
            throw new Zend_Queue_Exception("The options must be an associative array of host,port,login, password ...");
        }
    }

    /**
     * Get AMQPConnection object
     * @return object
     */
    public function getConnection()
    {
        return $this->_cnn;
    }


    /**
     * Set exchange for sending message to queue
     * @param string|AMQP_Queue_Exchange $exchange
     * @param string $routingKey
     * @param int $type (AMQP_EX_TYPE_DIRECT, AMQP_EX_TYPE_FANOUT, AMQP_EX_TYPE_TOPIC or AMQP_EX_TYPE_HEADER)
     * @param int $flags (AMQP_PASSIVE, AMQP_DURABLE, AMQP_AUTODELETE)
     * @return AMQP_Queue_Exchange
     */
    public function setExchange($exchange, $routingKey = "*", $type = AMQP_EX_TYPE_DIRECT, $flags = AMQP_DURABLE)
    {
        if (! $exchange instanceof AMQP_Queue_Exchange)
        {
            $exchange = new AMQP_Queue_Exchange($this->_cnn, $exchange, $type, $flags);
        }
        $this->_exchange = $exchange;
        $this->setRoutingKey($routingKey);

        return $exchange;
    }

    /**
     * Set routing key for queu
     * @param string $routingKey
     * @param AMQP_Queue $queue
     * @return bool
     */
    public function setRoutingKey($routingKey, AMQP_Queue $queue = null)
    {
        if ($queue)
        {
            $queueName = $queue->getName();
        } else {
            $queueName = $this->_queue->getName();
        }
        return $this->_exchange->bind($queueName, $routingKey);
    }

    /**
     * set AMQPQueue flag(s)
     * @param int $flag
     */
    public function setQueueFlag($flag)
    {
        $this->_amqpQueueFlag = $flag;
    }

    /**
     * create queue
     * @param string $name
     * @param int $timeout
     * @return int
     */
    public function create($name, $timeout = null)
    {
        try {
            $this->_count = $this->_amqpQueue->declare($name, $this->_amqpQueueFlag);
        } catch (Exception $e)
        {
            return false;
        }
        return true;
    }

    /**
     * delete queue
     * @param $name
     * @return bool
     */
    public function delete($name)
    {
        return $this->_amqpQueue->delete($name);
    }

    /**
     * Publish message to queue
     * @param mixed $message (array or string)
     * @param Zend_Queue $queue
     * @return boolean
     */
    public function send($message, Zend_Queue $queue = null)
    {
        if (is_array($message)) {
            $message = Zend_Json_Encoder::encode($message);
        }

        if ($queue)
        {
            $routingKey = $queue->getOption('routingKey');
        } else {
            $routingKey = $this->_queue->getOption('routingKey');
        }

        if ($this->_exchange)
        {
            return $this->_exchange->publish($message, $routingKey, AMQP_MANDATORY, array('delivery_mode' => 2));
        } else {
            throw new Zend_Queue_Exception("Rabbitmq exchange not found");
        }
    }

    /**
     * Get messages in the queue
     *
     * @param  integer|null $maxMessages Maximum number of messages to return
     * @param  integer|null $timeout Visibility timeout for these messages
     * @param  Zend_Queue|null $queue
     * @return Zend_Queue_Message_Iterator
     */
    public function receive($maxMessages = null, $timeout = null, Zend_Queue $queue = null)
    {
        $maxMessages = (int) $maxMessages ? (int) $maxMessages : 1;
        if (isset($this->_options['method']) && 'consume' == $this->_options['method'])
        {
            // use new AMQP_Queue_Adapter_Rabbitmq(array('method' => 'consume')) to use CONSUME approach
            $consumeOptions = array(
                'min' => 1,
                'max' => $maxMessages,
                'ack' => false,
            );
            $result = $this->_amqpQueue->consume($consumeOptions);
            $this->_count -= sizeof($result);
        } else {
            // default approach is GET
            $result = array();
            for ($i = $maxMessages; $i > 0; $i--)
            {
                $message = $this->_amqpQueue->get();
                if (isset($message['delivery_tag']))
                {
                    $result[] = $message;
                    $this->_count = $message['count'];
                }
                if ($message['count'] <= 0)
                {
                    break;
                }
            }
        }
        return new Zend_Queue_Message_Iterator(array('data' => $result));
    }

    public function getCapabilities()
    {
        return array(
            'create' => true,
            'delete' => true,
            'send' => true,
            'count' => true,
            'deleteMessage' => true,
        );
    }

    /**
     * Does a queue already exist?
     *
     * Use isSupported('isExists') to determine if an adapter can test for
     * queue existance.
     *
     * @param  string $name Queue name
     * @return boolean
     */
    public function isExists($name)
    {
        return isset($this->_count);
    }

    /**
     * Get an array of all available queues
     *
     * Not all adapters support getQueues(); use isSupported('getQueues')
     * to determine if the adapter supports this feature.
     *
     * @return array
     */
    public function getQueues()
    {
        return array($this->_queue);
    }

    /**
     * Return the approximate number of messages in the queue
     *
     * @param  Zend_Queue|null $queue
     * @return integer
     */
    public function count(Zend_Queue $queue = null)
    {
        return $this->_count;
    }

    /**
     * Delete a message from the queue
     *
     * Return true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param  Zend_Queue_Message $message
     * @return boolean
     */
    public function deleteMessage(Zend_Queue_Message $message)
    {
        if (!isset($message->delivery_tag))
        {
            throw new Zend_Queue_Exception('No delivery tag for Acking!');
        }
        return $this->_amqpQueue->ack($message->delivery_tag);
    }
}
