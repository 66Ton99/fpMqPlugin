<?php

require_once 'Zend/Queue.php';
require_once 'Zend/Queue/Message/Iterator.php';
require_once 'Zend/Queue/Message.php';
require_once 'Zend/Queue/Adapter/AdapterAbstract.php';
require_once 'Zend/Service/Amazon/Sqs.php';
require_once __DIR__ . '/fpMqFunction.class.php';
require_once __DIR__ . '/fpMqAmazonQueue.class.php';
require_once __DIR__ . '/fpMqDaemon.class.php';
require_once __DIR__ . '/fpMqQueue.class.php';
require_once __DIR__ . '/fpMqWorker.class.php';
