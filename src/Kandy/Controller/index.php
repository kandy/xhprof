<?php

$index = $app['controllers_factory'];

$index->get('/', function() use ($app) {
    /** @var \PDO $db */
    $db = $app['db'];

    $result = [
        'rows' => $db->query('
              select * from  requests;
          ')->fetchAll(PDO::FETCH_ASSOC)
    ];
    return $app['twig']->render('index.html.twig', $result);
})->bind('index');

return $index;