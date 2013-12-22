<?php

$index = $app['controllers_factory'];

$index->get('/', function() use ($app) {
    $result = [];
    return $app['twig']->render('index.html.twig', $result);
})->bind('index');

return $index;