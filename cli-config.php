<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;

require 'vendor/autoload.php';

include 'settings.php';

$doctrine_conf = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
    ['src/classes/Entity'],
    true,
    __DIR__ . '/cache/proxies',
    null,
    false
);

$em = \Doctrine\ORM\EntityManager::create($config['db_connection'], $doctrine_conf);

return ConsoleRunner::createHelperSet($em);