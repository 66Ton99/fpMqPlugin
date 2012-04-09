<?php

require_once __DIR__ . '/lib/autoload.php';
fpMqFunction::loadConfig('config/fp_mq.yml');
sfConfig::set('sf_symfony_lib_dir', ROOTDIR . '/lib/vendor/symfony/lib');
$file = __DIR__ . '/../fpErrorNotifierPlugin/config/include.php';
if (is_readable($file))
{
  fpMqFunction::loadConfig('config/notify.yml', 'sf_notify');
  require_once $file;
  $notifier = new fpErrorNotifier();
  fpErrorNotifier::setInstance($notifier);
  $notifier->handler()->initialize();
}

require_once __DIR__ . '/lib/fpMqWorker.class.php';
