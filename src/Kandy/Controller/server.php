<?php
use Symfony\Component\HttpFoundation\JsonResponse as JsonResponse;
$server = $app['controllers_factory'];

function save($db) {

    header("Connection: Close");
    flush();
    $url = 'compress.zlib://php://input';
    $url = 'php://input';
    $data = file_get_contents($url);
    $data = json_decode($data, true);



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
           return $name;
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

$server->post('/{api}.{format}', function($api, $format) use ($app) {
    save($app['db']);

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

return $server;
