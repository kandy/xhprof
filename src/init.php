<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;
$configFile = __DIR__ . '/../config/config.dist.php';
$app['params'] = require (file_exists($configFile) ? $configFile : __DIR__ . '/../config/config.dist.php');

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app['base_path'] =  dirname($_SERVER['SCRIPT_NAME']) . '/';
$app['twig'] = $app->share(
    $app->extend(
        'twig',
        function ($twig, $app) {
            $twig->addFunction(
                new \Twig_SimpleFunction('asset', function ($asset) use ($app) {
                    // implement whatever logic you need to determine the asset path
                    return sprintf($app['base_path'] .'%s', ltrim($asset, '/'));
                })
            );

            return $twig;
        }
    )
);

$app['db'] = $app->share(function($app){
    $db = new PDO($app['params']['dsn']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
});

return $app;
