<?php

require_once 'Zend/Queue.php';
require_once 'Zend/Queue/Message/Iterator.php';
require_once 'Zend/Queue/Message.php';
require_once 'Zend/Queue/Adapter/AdapterAbstract.php';
require_once 'Zend/Service/Amazon/Sqs.php';
require_once __DIR__ . '/lib/fpMqFunction.class.php';
require_once __DIR__ . '/lib/fpMqAmazonQueue.class.php';
require_once __DIR__ . '/lib/fpMqDaemon.class.php';
require_once __DIR__ . '/lib/fpMqQueue.class.php';
require_once __DIR__ . '/lib/fpMqWorker.class.php';
require_once __DIR__ . '/lib/fpMqContainer.class.php';
