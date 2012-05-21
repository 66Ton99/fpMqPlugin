<?php

require_once __DIR__ . '/autoload.php';
$file = __DIR__ . '/../fpErrorNotifierPlugin/config/include.php';
if (is_readable($file))
{
    fpMqFunction::loadConfig('config/notify.yml', 'sf_notify');
    require_once $file;
    require_once __DIR__ . '/../../lib/vendor/symfony/lib/cache/sfCache.class.php';
    require_once __DIR__ . '/../../lib/vendor/symfony/lib/cache/sfFileCache.class.php';
    $notifier = new fpErrorNotifier();
    fpErrorNotifier::setInstance($notifier);
    $notifier->handler()->initialize();
}