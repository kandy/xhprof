<?php
use Symfony\Component\HttpFoundation\JsonResponse as JsonResponse;
$server = $app['controllers_factory'];

function save() {

    header("Connection: Close");
    flush();
    $url = 'compress.zlib://php://input';

    $data = json_decode(file_get_contents($url), true);

    $db = new PDO('sqlite:data/test.sqlite3');
    // Set errormode to exceptions
    $db->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );
    $db->beginTransaction();
    $db->prepare(
        "
            INSERT INTO
                requests(request_host, request_uri, request_timestamp)
            VALUES (
                :host,
                :uri,
                :timestamp
            );
        "
    )->execute(
            array(
                ':host' => $_GET['host'],
                ':uri' => $_GET['uri'],
                ':timestamp' => $_GET['timestamp']
            )
        );
    $requestId = $db->lastInsertId();

    $sth1 = $db->prepare(
        "
            INSERT INTO
                calls (
                     request_id,
                     ct,
                     wt,
                     cpu,
                     mu,
                     pmu,
                     caller,
                     callee
                 )
            VALUES (
                :request_id,
                :ct,
                :wt,
                :cpu,
                :mu,
                :pmu,
                :caller,
                :callee
            );
        "
    );
    $trim = function ($name) {
        if (strpos($name, '@')) {
            return explode('@', $name, 2)[0];
        } else {
            return $name;
        }
    };
    foreach ($data as $call => $row) {
        $call = explode('==>', $call);
        if (count($call) == 1) {
            $caller = null;
            $callee = $trim($call[0]);
        } else {
            $caller = $trim($call[0]);
            $callee = $trim($call[1]);
        }

        $sth1->execute(
            array(
                ':request_id' => $requestId,
                ':ct' => $row['ct'],
                ':wt' => $row['wt'],
                ':cpu' => $row['cpu'],
                ':mu' => $row['mu'],
                ':pmu' => $row['pmu'],
                ':caller' => $caller,
                ':callee' => $callee,
            )
        );
    }
    $db->commit();
}

$server->get('/{api}.{format}', function($api, $format) use ($app) {
    save();

    $result = [
        'method' => $api,
        'format' => $format,
    ];
    $result['success'] = 'true';
    $response = new JsonResponse($result);
    $response->setStatusCode(200);
    return $response;

})
    ->value('api', 'all')
    ->value('format', 'json')
    ->assert('format', 'json|jsonp')
    ->bind('server');

/*

SELECT
  c.callee,
  (c.wt - total(cc.wt) + 0.0) / 1000.0 AS time_main,
  (c.cpu - total(cc.cpu) + 0.0) / 1000.0 AS cpu_main
FROM "calls" AS c
  LEFT JOIN "calls" AS cc
    ON (cc.request_id = c.request_id
        AND c.callee = cc.caller
  )
WHERE
  c.request_id = 5
GROUP BY
  c.callee
ORDER BY time_main DESC ;


 */
return $server;
