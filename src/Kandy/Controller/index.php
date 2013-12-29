<?php

$index = $app['controllers_factory'];

$index->get('/', function() use ($app) {
    /** @var \PDO $db */
    $db = $app['db'];

    $result = [
        'options' => [
            'enableCellNavigation' => false,
            'forceFitColumns'=> true,
         ],
        'rows' => $db->query('
              select * from  requests;
          ')->fetchAll(PDO::FETCH_ASSOC)
    ];
    return $app['twig']->render('index.html.twig', $result);
})->bind('index');

$app->get(
    '/request/{request}/',
    function ($request) use ($app) {
        /** @var \PDO $db */
        $db = $app['db'];
        $st = $db->prepare('
            SELECT
              c.callee,
              total(c.wt)  as fwt,
              (total(c.wt) - ifnull(cc.wt, 0)) AS wt,
              total(c.wt) * 100 / (
                select wt
                  from calls
                  where request_id = :id
                  and callee="main()"
              )  as mu,
              total(c.ct) as ct
            FROM "calls" AS c
              LEFT JOIN (
                  select total(wt) as wt, request_id, caller
                  from "calls"
                  where request_id = :id
                  group by caller
              ) AS cc
              ON (cc.request_id = c.request_id
                    AND c.callee = cc.caller
              )
            WHERE
              c.request_id = :id
            group by callee
            ORDER BY wt DESC ;
        ');
        $st->bindValue(':id', $request->id);
        $st->execute();
        $result = [
            'options' => [
                'enableCellNavigation' => false,
                'forceFitColumns' => true,
            ],
            'request' => $request,
            'rows' => $st->fetchAll(PDO::FETCH_ASSOC)
        ];
        return $app['twig']->render('request.html.twig', $result);
    }
)->convert('request', function($request_id) use($app) {
    /** @var \PDO $db */
    $db = $app['db'];
    $st = $db->prepare('select * from requests where id= :id');
    $st->execute(['id'=> $request_id]);
    return $st->fetch(PDO::FETCH_OBJ);
})->bind('request');
return $index;