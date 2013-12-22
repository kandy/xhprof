<?php
require __DIR__.'/../src/init.php';

$app['debug'] = true;
$controllerDir = __DIR__ . '/../src/Kandy/Controller/';

$app->mount('/',       include $controllerDir . 'index.php');
$app->mount('/server', include $controllerDir . 'server.php');

$app->run();