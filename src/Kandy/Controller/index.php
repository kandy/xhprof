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
              c.id,
              c.id - (
                select min(id)
                  from calls
                  where request_id = :id
              )  as num,
              c.callee,
              c.caller,
              c.wt  as wt,
              c.wt  as fwt,
              (c.wt) * 100 / (
                select wt
                  from calls
                  where request_id = :id
                  and callee="main()"
              )  as mu,
              c.ct as ct
            FROM "calls" AS c
            WHERE
              c.request_id = :id
            order by c.id desc
        ');
        $st->bindValue(':id', $request->id);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $stack = [];
        $stack[] = end($rows);
        $stack[0]['mt'] = $stack[0]['wt'];
        array_walk($rows, function(&$el, $key) use(&$stack, &$rows) {
            while (count($stack) > 1  // todo: use for
                && end($stack)['callee'] != $el['caller']
            ) {
                array_pop($stack);
            }
            $el['l'] = count($stack)-1;
            if (!is_null($el['caller']) && $stack[$el['l']]['callee'] == $el['caller']) {
                $stack[$el['l']]['mt'] -= $el['wt'];
            }
            $stack[] = &$el;
            $el['mt'] = $el['wt'];
            return $el;
        });
        $trim = function ($name) {
            if (strpos($name, '@')) {
                return explode('@', $name, 2)[0];
            } else {
                return $name;
            }
        };
        $result = [
            'options' => [
                'enableCellNavigation' => false,
                'forceFitColumns' => true,
            ],
            'request' => $request,
            'rows' => $rows
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